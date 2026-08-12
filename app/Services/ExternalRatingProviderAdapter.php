<?php

namespace App\Services;

use App\Contracts\RatingProviderInterface;
use Config\Database;

/** Generic verified-provider adapter. It stores external data separately and never writes canonical rating. */
class ExternalRatingProviderAdapter implements RatingProviderInterface
{
    private string $provider;
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;
    protected $db;

    public function __construct(?string $provider = null)
    {
        $this->provider = $provider ?: (string) (env('RATING_EXTERNAL_PROVIDER') ?: 'external');
        $this->baseUrl = rtrim((string) (env('RATING_EXTERNAL_BASE_URL') ?: ''), '/');
        $this->apiKey = (string) (env('RATING_EXTERNAL_API_KEY') ?: '');
        $this->timeout = max(1, min(15, (int) (env('RATING_EXTERNAL_TIMEOUT') ?: 5)));
        $this->db = Database::connect();
    }

    public function code(): string { return 'external-' . strtolower($this->provider); }

    public function findPlayer(string $query, string $discipline = 'singles'): array
    {
        $payload = $this->request('/players', ['q' => $query, 'discipline' => $discipline]);
        return is_array($payload['items'] ?? null) ? $payload['items'] : [];
    }

    public function getPlayerRating(int $tenantId, int $playerId, string $discipline): ?array
    {
        if (! $this->db->tableExists('player_external_ratings')) return null;
        $mapping = $this->db->table('player_external_ratings')->where('player_id', $playerId)->where('provider', $this->provider)->get()->getRow();
        if (! $mapping || ! $mapping->external_player_id) return null;
        $payload = $this->request('/players/' . rawurlencode((string) $mapping->external_player_id), ['discipline' => $discipline]);
        return $this->normalize($payload, $mapping->external_player_id);
    }

    public function getRatingHistory(int $tenantId, int $playerId, string $discipline, int $limit = 100): array
    {
        $mapping = $this->db->table('player_external_ratings')->where('player_id', $playerId)->where('provider', $this->provider)->get()->getRow();
        if (! $mapping || ! $mapping->external_player_id) return [];
        $payload = $this->request('/players/' . rawurlencode((string) $mapping->external_player_id) . '/history', ['discipline' => $discipline, 'limit' => max(1, min(200, $limit))]);
        return is_array($payload['items'] ?? null) ? $payload['items'] : [];
    }

    public function calculateMatchImpact(array $context): array { return ['success' => false, 'supported' => false, 'provider' => $this->code(), 'message' => 'External provider không được dùng để tính canonical impact.']; }
    public function validateMatchEligibility(array $context): array { return ['eligible' => false, 'reasons' => ['EXTERNAL_PROVIDER_NOT_CANONICAL']]; }

    public function syncPlayer(int $tenantId, int $playerId, string $discipline): array
    {
        $rating = $this->getPlayerRating($tenantId, $playerId, $discipline);
        if (! $rating) return ['success' => false, 'message' => 'Không lấy được external rating.'];
        if ($this->db->tableExists('player_external_ratings')) {
            $mapping = $this->db->table('player_external_ratings')->where('player_id', $playerId)->where('provider', $this->provider)->get()->getRow();
            if ($mapping) $this->db->table('player_external_ratings')->where('id', $mapping->id)->update(['rating' => $rating['rating'], 'reliability' => $rating['reliability'] ?? 0, 'match_count' => $rating['match_count'] ?? 0, 'last_synced_at' => date('Y-m-d H:i:s'), 'sync_payload' => json_encode($rating, JSON_UNESCAPED_UNICODE), 'updated_at' => date('Y-m-d H:i:s')]);
        }
        return ['success' => true, 'provider' => $this->provider, 'player_id' => $playerId, 'rating' => $rating];
    }

    public function submitMatchIfSupported(array $context): array { return ['success' => false, 'supported' => false, 'provider' => $this->code()]; }
    public function getReliabilityIfAvailable(int $tenantId, int $playerId, string $discipline): ?float { $rating = $this->getPlayerRating($tenantId, $playerId, $discipline); return $rating['reliability'] ?? null; }
    public function getExternalPlayerId(int $playerId): ?string { $row = $this->db->table('player_external_ratings')->where('player_id', $playerId)->where('provider', $this->provider)->get()->getRow(); return $row->external_player_id ?? null; }

    private function normalize(array $payload, string $externalId): ?array
    {
        $data = $payload['data'] ?? $payload;
        if (! isset($data['rating']) || ! is_numeric($data['rating'])) return null;
        return ['provider' => $this->provider, 'external_player_id' => $externalId, 'rating' => (float) $data['rating'], 'reliability' => isset($data['reliability']) ? (float) $data['reliability'] : 0, 'match_count' => (int) ($data['match_count'] ?? 0), 'last_synced_at' => $data['last_updated_at'] ?? date('Y-m-d H:i:s')];
    }

    private function request(string $path, array $query = []): array
    {
        if ($this->baseUrl === '') return [];
        try {
            $client = service('curlrequest', ['timeout' => $this->timeout, 'http_errors' => false]);
            $response = $client->get($this->baseUrl . $path, ['query' => $query, 'headers' => ['Accept' => 'application/json', 'Authorization' => $this->apiKey ? 'Bearer ' . $this->apiKey : '']]);
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) return [];
            return json_decode((string) $response->getBody(), true) ?: [];
        } catch (\Throwable $e) { log_message('warning', 'external_rating_provider_failed provider={provider} error={error}', ['provider' => $this->provider, 'error' => $e->getMessage()]); return []; }
    }
}
