<?php

namespace App\Services;

use App\Models\PlayerRatingProviderLinkModel;
use App\Models\ProviderRatingRecordModel;
use App\Models\RatingProviderModel;
use Config\Database;

/** Keeps external provider values separate from internal rating profiles. */
class ProviderRatingService
{
    private RatingProviderModel $providerModel;
    private PlayerRatingProviderLinkModel $linkModel;
    private ProviderRatingRecordModel $recordModel;

    public function __construct()
    {
        $this->providerModel = model(RatingProviderModel::class);
        $this->linkModel = model(PlayerRatingProviderLinkModel::class);
        $this->recordModel = model(ProviderRatingRecordModel::class);
    }

    public function registerProvider(array $data): array
    {
        foreach (['code', 'name', 'provider_type'] as $required) if (empty($data[$required])) return ['success' => false, 'message' => $required . ' is required.'];
        $id = $this->providerModel->insert(['code' => strtolower(trim($data['code'])), 'name' => trim($data['name']), 'provider_type' => strtoupper($data['provider_type']), 'status' => $data['status'] ?? 'active', 'config' => $this->json($data['config'] ?? null)]);
        return $id ? ['success' => true, 'provider' => $this->providerModel->find($id)] : ['success' => false, 'message' => 'Unable to register provider.'];
    }

    public function linkPlayer(int $playerId, int $providerId, string $externalPlayerId, int $actorId, bool $consent, ?string $authorizationReference = null): array
    {
        if (! $consent) return ['success' => false, 'code' => 'PROVIDER_CONSENT_REQUIRED', 'message' => 'Player consent is required before linking an external account.'];
        if ($playerId <= 0 || $providerId <= 0 || trim($externalPlayerId) === '') return ['success' => false, 'message' => 'Player, provider and external id are required.'];
        $existing = $this->linkModel->where('player_id', $playerId)->where('provider_id', $providerId)->first();
        $payload = ['player_id' => $playerId, 'provider_id' => $providerId, 'external_player_id' => trim($externalPlayerId), 'verification_status' => 'pending', 'consent_status' => 'granted', 'authorization_reference' => $authorizationReference, 'linked_at' => date('Y-m-d H:i:s'), 'sync_state' => 'active', 'metadata' => $this->json(['consented_by' => $actorId])];
        if ($existing) $this->linkModel->update($existing->id, $payload); else $this->linkModel->insert($payload);
        return ['success' => true, 'link' => $this->linkModel->where('player_id', $playerId)->where('provider_id', $providerId)->first()];
    }

    public function recordExternalRating(array $data): array
    {
        foreach (['player_id', 'provider_id', 'discipline', 'observed_at'] as $required) if (empty($data[$required])) return ['success' => false, 'message' => $required . ' is required.'];
        $id = $this->recordModel->insert(['player_id' => (int) $data['player_id'], 'provider_id' => (int) $data['provider_id'], 'discipline' => $data['discipline'], 'rating_value' => $data['rating_value'] ?? null, 'rating_label' => $data['rating_label'] ?? null, 'external_record_id' => $data['external_record_id'] ?? null, 'observed_at' => $data['observed_at'], 'synced_at' => date('Y-m-d H:i:s'), 'payload' => $this->json($data['payload'] ?? null), 'created_at' => date('Y-m-d H:i:s')]);
        if ($id) $this->linkModel->where('player_id', (int) $data['player_id'])->where('provider_id', (int) $data['provider_id'])->set(['last_synced_at' => date('Y-m-d H:i:s'), 'sync_state' => 'active'])->update();
        return $id ? ['success' => true, 'record' => $this->recordModel->find($id)] : ['success' => false, 'message' => 'Unable to store provider rating.'];
    }

    public function resolveRating(int $playerId, string $discipline, array $providerIds, ?int $tenantId = null): array
    {
        foreach ($providerIds as $providerId) {
            $link = $this->linkModel->where('player_id', $playerId)->where('provider_id', (int) $providerId)->where('consent_status', 'granted')->whereIn('sync_state', ['active', 'stale'])->first();
            if ($link) {
                $record = $this->recordModel->where('player_id', $playerId)->where('provider_id', (int) $providerId)->where('discipline', $discipline)->orderBy('observed_at', 'DESC')->first();
                if ($record) return ['found' => true, 'source' => 'external', 'provider_id' => (int) $providerId, 'record' => $record, 'sync_state' => $link->sync_state];
            }
            if ((int) $providerId === 0 && $tenantId !== null) {
                $internal = service('ratingEngine')->getPublicRating($tenantId, $playerId, $discipline);
                if ($internal && $internal['rating'] !== null) return ['found' => true, 'source' => 'internal', 'provider_id' => 0, 'record' => $internal, 'sync_state' => 'active'];
            }
        }
        return ['found' => false, 'source' => 'nr', 'record' => null];
    }

    public function markSyncState(int $linkId, string $state): bool
    {
        if (! in_array($state, ['active', 'stale', 'error', 'revoked'], true)) return false;
        return (bool) $this->linkModel->update($linkId, ['sync_state' => $state]);
    }

    private function json($value): ?string { return $value === null ? null : (is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE)); }
}
