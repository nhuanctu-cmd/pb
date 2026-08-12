<?php

namespace App\Services;

use App\Models\MatchParticipantModel;
use App\Models\MatchResultModel;
use App\Models\PlayerRatingModel;
use App\Models\RatingLedgerModel;
use App\Models\RatingProviderModel;
use App\Models\UnifiedMatchModel;
use Config\Database;

class RatingNetworkService
{
    private RatingProviderModel $providerModel;
    private RatingLedgerModel $ledgerModel;
    private PlayerRatingModel $ratingModel;
    private UnifiedMatchModel $matchModel;
    private MatchParticipantModel $participantModel;
    private MatchResultModel $resultModel;

    public function __construct()
    {
        $this->providerModel = model(RatingProviderModel::class);
        $this->ledgerModel = model(RatingLedgerModel::class);
        $this->ratingModel = model(PlayerRatingModel::class);
        $this->matchModel = model(UnifiedMatchModel::class);
        $this->participantModel = model(MatchParticipantModel::class);
        $this->resultModel = model(MatchResultModel::class);
    }

    public function applyOfficialMatch(int $matchId, ?int $tenantId = null, string $providerCode = 'internal-elo'): array
    {
        $db = Database::connect();
        if ($db->tableExists('player_rating_profiles') && $db->tableExists('rating_transactions')) {
            return service('ratingEngine')->processOfficialMatch($matchId, $tenantId);
        }
        $matchQuery = $this->matchModel->where('id', $matchId)->where('status', 'official');
        if ($tenantId !== null && $tenantId > 0) $matchQuery->where('tenant_id', $tenantId);
        $match = $matchQuery->first();
        $result = $this->resultModel->where('match_id', $matchId)->where('status', 'official')->first();
        if (! $match || ! $result) return ['success' => false, 'message' => 'Chỉ match official mới được tính rating.'];
        $tenantId = $tenantId ?: (int) $match->tenant_id;
        if ($tenantId <= 0) return ['success' => false, 'message' => 'Match cần tenant để tính rating vận hành.'];
        $provider = $this->providerModel->where('code', $providerCode)->where('status', 'active')->first();
        if (! $provider) return ['success' => false, 'message' => 'Rating provider không hoạt động.'];
        $participants = $this->participantModel->where('match_id', $matchId)->findAll();
        if (count($participants) < 2) return ['success' => false, 'message' => 'Không đủ participant để tính rating.'];

        $already = 0;
        foreach ($participants as $participant) {
            $key = sprintf('rating:%s:%s:%s', $provider->id, $matchId, $participant->player_id);
            if ($this->ledgerModel->where('idempotency_key', $key)->first()) $already++;
        }
        if ($already === count($participants)) {
            return [
                'success' => true,
                'created' => 0,
                'idempotent' => true,
                'ranking' => service('rankingNetworkService')->applyOfficialMatch($matchId, $tenantId),
            ];
        }
        if ($already > 0) return ['success' => false, 'message' => 'Rating ledger đang ở trạng thái một phần, cần rebuild trước khi chạy lại.'];

        $sideRatings = [1 => [], 2 => []];
        $ratingRows = [];
        foreach ($participants as $participant) {
            $rating = $this->ratingModel->findOrCreate($tenantId, (int) $participant->player_id);
            $ratingRows[(int) $participant->player_id] = $rating;
            $sideRatings[(int) $participant->side][] = (float) $rating->rating;
        }
        $averageA = array_sum($sideRatings[1]) / max(1, count($sideRatings[1]));
        $averageB = array_sum($sideRatings[2]) / max(1, count($sideRatings[2]));
        $expectedA = 1 / (1 + pow(10, ($averageB - $averageA) / 400));
        $actualA = empty($result->winner_side) ? 0.5 : ((int) $result->winner_side === 1 ? 1.0 : 0.0);
        $deltaA = (float) round(24 * ($actualA - $expectedA), 3);

        $db->transStart();
        $created = 0;
        foreach ($participants as $participant) {
            $before = (float) $ratingRows[(int) $participant->player_id]->rating;
            $delta = (int) $participant->side === 1 ? $deltaA : -$deltaA;
            $after = round($before + $delta, 3);
            $outcome = empty($result->winner_side) ? 'draw' : ((int) $participant->side === (int) $result->winner_side ? 'win' : 'loss');
            $key = sprintf('rating:%s:%s:%s', $provider->id, $matchId, $participant->player_id);
            $this->ledgerModel->insert([
                'rating_provider_id' => $provider->id,
                'tenant_id' => $tenantId,
                'player_id' => $participant->player_id,
                'match_id' => $matchId,
                'side' => $participant->side,
                'outcome' => $outcome,
                'rating_before' => $before,
                'rating_after' => $after,
                'rating_delta' => $delta,
                'reliability_before' => min(100, ((int) $ratingRows[(int) $participant->player_id]->games_played) * 5),
                'reliability_after' => min(100, (((int) $ratingRows[(int) $participant->player_id]->games_played) + 1) * 5),
                'calculation_version' => 'elo-v1',
                'idempotency_key' => $key,
                'metadata' => json_encode(['provider' => $providerCode, 'source_type' => $match->source_type]),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $current = $ratingRows[(int) $participant->player_id];
            $this->ratingModel->update($current->id, [
                'rating' => round($after),
                'games_played' => ((int) $current->games_played) + 1,
                'wins' => ((int) $current->wins) + ($outcome === 'win' ? 1 : 0),
                'losses' => ((int) $current->losses) + ($outcome === 'loss' ? 1 : 0),
                'last_match_at' => date('Y-m-d H:i:s'),
            ]);
            $created++;
        }
        $db->transComplete();
        if (! $db->transStatus()) return ['success' => false, 'message' => 'Không ghi được rating ledger.'];

        $ranking = service('rankingNetworkService')->applyOfficialMatch($matchId, $tenantId);
        return ['success' => true, 'created' => $created, 'ranking' => $ranking];
    }
}
