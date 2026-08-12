<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/** Creates visible V1 demo data through the same official-result path as production. */
class CanonicalRatingDemoSeeder extends Seeder
{
    public function run()
    {
        foreach (['matches', 'match_participants', 'match_results', 'match_result_versions', 'player_rating_profiles', 'rating_transactions'] as $table) {
            if (! $this->db->tableExists($table)) { echo "Rating foundation chưa migrate.\n"; return; }
        }
        $tenant = $this->db->table('tenants')->where('id', 1)->where('status', 'active')->get()->getRow();
        if (! $tenant) $tenant = $this->db->table('tenants')->where('status', 'active')->orderBy('id', 'ASC')->get()->getRow();
        if (! $tenant) { echo "Không có tenant active để seed rating.\n"; return; }
        $players = $this->db->table('players')->where('tenant_id', $tenant->id)->where('status', 'active')->where('deleted_at', null)->orderBy('id', 'ASC')->limit(12)->get()->getResult();
        if (count($players) < 4) { echo "Cần ít nhất 4 player active trong tenant {$tenant->id}.\n"; return; }
        $created = 0; $processed = 0;
        $sets = [
            ['discipline' => 'singles', 'teams' => [[0], [1]], 'rounds' => 3],
            ['discipline' => 'doubles', 'teams' => [[0, 1], [2, 3]], 'rounds' => 3],
            ['discipline' => 'mixed_doubles', 'teams' => [[0, 2], [1, 3]], 'rounds' => 3],
        ];
        foreach ($sets as $setIndex => $set) {
            for ($round = 0; $round < $set['rounds']; $round++) {
                $sourceId = 920000 + ($setIndex * 100) + $round;
                $existing = $this->db->table('matches')->where('tenant_id', $tenant->id)->where('source_type', 'friendly')->where('source_id', $sourceId)->get()->getRow();
                if ($existing) { $processed += $this->process((int) $existing->id); continue; }
                $teams = $this->rotateTeams($set['teams'], $round);
                $completedAt = date('Y-m-d H:i:s', strtotime('-' . (12 - $round - $setIndex) . ' days'));
                $matchData = ['public_id' => $this->publicId($sourceId), 'tenant_id' => $tenant->id, 'source_type' => 'friendly', 'source_id' => $sourceId, 'discipline' => $set['discipline'], 'status' => 'official', 'result_type' => 'normal', 'verification_status' => 'official', 'completed_at' => $completedAt, 'metadata' => json_encode(['seed' => 'canonical-rating-v1', 'source_key' => $sourceId]), 'created_at' => $completedAt, 'updated_at' => $completedAt];
                $this->db->table('matches')->insert($matchData); $matchId = (int) $this->db->insertID();
                foreach ([1, 2] as $side) foreach ($teams[$side - 1] as $sort => $playerIndex) $this->db->table('match_participants')->insert(['match_id' => $matchId, 'player_id' => $players[$playerIndex % count($players)]->id, 'side' => $side, 'participant_role' => 'player', 'result' => $side === 1 ? 'won' : 'lost', 'score' => $side === 1 ? '11-7' : '7-11', 'sort_order' => $sort, 'created_at' => $completedAt, 'updated_at' => $completedAt]);
                $this->db->table('match_games')->insert(['match_id' => $matchId, 'game_no' => 1, 'side_a_score' => 11, 'side_b_score' => 7, 'raw_score' => '11-7', 'created_at' => $completedAt, 'updated_at' => $completedAt]);
                $this->db->table('match_results')->insert(['match_id' => $matchId, 'version_no' => 1, 'status' => 'official', 'result_type' => 'normal', 'winner_side' => 1, 'published_at' => $completedAt, 'created_at' => $completedAt, 'updated_at' => $completedAt]); $resultId = (int) $this->db->insertID();
                $this->db->table('match_result_versions')->insert(['match_id' => $matchId, 'version_no' => 1, 'status' => 'official', 'result_type' => 'normal', 'winner_side' => 1, 'payload' => json_encode(['discipline' => $set['discipline'], 'games' => [['side_a_score' => 11, 'side_b_score' => 7]], 'seed' => 'canonical-rating-v1']), 'submitted_by' => null, 'confirmed_by' => null, 'change_reason' => 'Canonical demo seed', 'created_at' => $completedAt]); $versionId = (int) $this->db->insertID();
                $this->db->table('match_results')->where('id', $resultId)->update(['current_version_id' => $versionId]);
                $created++; $processed += $this->process($matchId);
            }
        }
        $fixtures = $this->seedReviewFixtures($tenant, $players);
        echo "Canonical rating demo: {$created} matches created, {$processed} matches processed, {$fixtures} review fixtures ready.\n";
    }

    private function seedReviewFixtures(object $tenant, array $players): int
    {
        $created = 0;
        $now = date('Y-m-d H:i:s');
        $firstPlayer = (int) $players[0]->id;
        $demoMatch = $this->db->table('matches')->where('tenant_id', $tenant->id)->where('source_id', 920000)->get()->getRow();

        if ($this->db->tableExists('rating_integrity_flags')) {
            $exists = $this->db->table('rating_integrity_flags')->where('tenant_id', $tenant->id)->where('code', 'DEMO_REVIEW_SIGNAL')->where('player_id', $firstPlayer)->countAllResults();
            if (! $exists) {
                $this->db->table('rating_integrity_flags')->insert(['tenant_id' => $tenant->id, 'match_id' => $demoMatch->id ?? null, 'player_id' => $firstPlayer, 'code' => 'DEMO_REVIEW_SIGNAL', 'risk_score' => 25, 'status' => 'open', 'details' => json_encode(['source' => 'canonical-demo', 'note' => 'Fixture để kiểm tra quy trình integrity review'], JSON_UNESCAPED_UNICODE), 'created_at' => $now, 'updated_at' => $now]);
                $created++;
            }
        }

        if ($demoMatch && $this->db->tableExists('result_correction_requests') && $this->db->table('result_correction_requests')->where('match_id', $demoMatch->id)->where('reason', 'Canonical demo correction fixture')->countAllResults() === 0) {
            $result = $this->db->table('match_results')->where('match_id', $demoMatch->id)->get()->getRow();
            if ($result && $result->current_version_id) {
                $requester = $this->db->table('users')->select('id')->orderBy('id', 'ASC')->get()->getRow();
                $requesterId = (int) ($requester->id ?? 1);
                $this->db->table('result_correction_requests')->insert(['match_id' => $demoMatch->id, 'original_result_version_id' => $result->current_version_id, 'requested_result' => json_encode(['winner_side' => 2, 'games' => [['side_a_score' => 7, 'side_b_score' => 11]]]), 'reason' => 'Canonical demo correction fixture', 'evidence' => json_encode(['source' => 'demo-score-sheet']), 'requester_id' => $requesterId, 'status' => 'open', 'created_at' => $now]);
                $created++;
            }
        }

        if ($this->db->tableExists('rating_import_jobs') && $this->db->tableExists('rating_import_rows')) {
            $job = $this->db->table('rating_import_jobs')->where('tenant_id', $tenant->id)->where('source_name', 'canonical-demo-duplicate-import')->get()->getRow();
            if (! $job) {
                $this->db->table('rating_import_jobs')->insert(['tenant_id' => $tenant->id, 'source_type' => 'club', 'status' => 'matching', 'created_by' => null, 'source_name' => 'canonical-demo-duplicate-import', 'metadata' => json_encode(['fixture' => 'duplicate external reference']), 'created_at' => $now, 'updated_at' => $now]);
                $jobId = (int) $this->db->insertID();
                foreach ([1 => 'matched', 2 => 'duplicate'] as $rowNumber => $identityStatus) $this->db->table('rating_import_rows')->insert(['job_id' => $jobId, 'row_number' => $rowNumber, 'raw_data' => json_encode(['player_id' => $firstPlayer, 'full_name' => $players[0]->full_name, 'rating' => 3.2, 'discipline' => 'singles', 'external_reference' => 'canonical-demo-match-001']), 'player_id' => $identityStatus === 'matched' ? $firstPlayer : null, 'identity_status' => $identityStatus, 'validation_status' => 'pending', 'verification_status' => 'pending', 'created_at' => $now, 'updated_at' => $now]);
                $created++;
            }
        }

        return $created;
    }

    private function process(int $matchId): int
    {
        $result = service('ratingEngine')->processOfficialMatch($matchId, null);
        return ! empty($result['success']) ? 1 : 0;
    }

    private function rotateTeams(array $teams, int $round): array
    {
        if ($round % 2 === 0) return $teams;
        return array_map(static fn (array $team): array => array_reverse($team), array_reverse($teams));
    }

    private function publicId(int $key): string { return '00000000-0000-4000-8000-' . str_pad((string) $key, 12, '0', STR_PAD_LEFT); }
}
