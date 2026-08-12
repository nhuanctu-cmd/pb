<?php

namespace Tests\Unit;

use App\Services\UnifiedMatchService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

class UnifiedMatchServiceTest extends CIUnitTestCase
{
    public function testOfficialResultIsVersionedAndCannotBeOverwritten(): void
    {
        $db = Database::connect();
        $playerOne = $this->createPlayer($db, 'Match Test A');
        $playerTwo = $this->createPlayer($db, 'Match Test B');
        $service = new UnifiedMatchService();

        $created = $service->create([
            'source_type' => 'friendly',
            'participants' => [
                ['player_id' => $playerOne, 'side' => 1],
                ['player_id' => $playerTwo, 'side' => 2],
            ],
        ], 1, 1);
        $this->assertTrue($created['success']);
        $matchId = (int) $created['match']['match']->id;

        $this->assertTrue($service->submitResult($matchId, [
            'winner_side' => 1,
            'games' => [['side_a_score' => 11, 'side_b_score' => 7]],
        ], 1, 1)['success']);
        $this->assertTrue($service->confirmResult($matchId, 1, 1)['success']);
        $published = $service->publishOfficial($matchId, 1, 1);
        $this->assertTrue($published['success']);
        $this->assertTrue($published['network']['success']);
        $this->assertTrue($published['network']['ranking']['success']);
        $this->assertGreaterThan(0, (int) ($published['network']['ranking']['created'] ?? 0));
        $legacySync = service('ratingNetworkService')->applyOfficialMatch($matchId, 1);
        $this->assertTrue($legacySync['success']);

        $official = $service->get($matchId, 1);
        $this->assertSame('official', $official['match']->status);
        $this->assertSame('official', $official['result']->status);
        $this->assertSame(1, (int) $official['result']->version_no);
        $this->assertFalse($service->submitResult($matchId, ['winner_side' => 2], 1, 1)['success']);
    }

    public function testSourceIdentityIsIdempotentAndDoesNotCreateDuplicateCanonicalMatch(): void
    {
        $db = Database::connect();
        $playerOne = $this->createPlayer($db, 'Source Identity A');
        $playerTwo = $this->createPlayer($db, 'Source Identity B');
        $service = new UnifiedMatchService();
        $sourceId = random_int(900000, 999999);
        $data = [
            'source_type' => 'tournament',
            'source_id' => $sourceId,
            'participants' => [
                ['player_id' => $playerOne, 'side' => 1],
                ['player_id' => $playerTwo, 'side' => 2],
            ],
        ];

        $first = $service->create($data, 1, 1);
        $second = $service->create($data, 1, 1);
        $this->assertTrue($first['success']);
        $this->assertTrue($second['success']);
        $this->assertTrue($second['idempotent']);
        $this->assertSame((int) $first['match']['match']->id, (int) $second['match']['match']->id);
        $this->assertSame(1, (int) $db->table('matches')->where('tenant_id', 1)->where('source_type', 'tournament')->where('source_id', $sourceId)->countAllResults());
    }

    private function createPlayer($db, string $name): int
    {
        $db->table('players')->insert([
            'tenant_id' => 1,
            'player_code' => 'TEST-' . strtoupper(bin2hex(random_bytes(4))),
            'full_name' => $name,
            'status' => 'active',
        ]);
        return (int) $db->insertID();
    }
}
