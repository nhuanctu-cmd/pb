<?php

namespace App\Services;

use Config\Database;

/** Explicit import state machine: upload -> preview -> identity matching -> duplicate/score validation -> source verification -> import. */
class RatingImportService
{
    protected $db;
    public function __construct() { $this->db = Database::connect(); }

    public function upload(int $tenantId, string $sourceType, array $rows, ?int $createdBy = null, ?string $sourceName = null): array
    {
        if (! $this->ready() || ! in_array($sourceType, ['club', 'coach', 'external_provider'], true) || ! $rows) return ['success' => false, 'message' => 'Import payload không hợp lệ.'];
        $now = date('Y-m-d H:i:s');
        $this->db->transStart();
        $this->db->table('rating_import_jobs')->insert(['tenant_id' => $tenantId, 'source_type' => $sourceType, 'status' => 'uploaded', 'created_by' => $createdBy, 'source_name' => $sourceName, 'metadata' => json_encode(['row_count' => count($rows)]), 'created_at' => $now, 'updated_at' => $now]);
        $jobId = $this->db->insertID();
        foreach (array_values($rows) as $index => $row) $this->db->table('rating_import_rows')->insert(['job_id' => $jobId, 'row_number' => $index + 1, 'raw_data' => json_encode((array) $row, JSON_UNESCAPED_UNICODE), 'created_at' => $now, 'updated_at' => $now]);
        $this->db->transComplete();
        return $this->db->transStatus() ? ['success' => true, 'job_id' => (int) $jobId, 'status' => 'uploaded', 'next_step' => 'preview'] : ['success' => false, 'message' => 'Không tạo được import job.'];
    }

    public function preview(int $tenantId, int $jobId): array { return $this->transition($tenantId, $jobId, 'previewed', ['uploaded']); }

    public function matchIdentities(int $tenantId, int $jobId): array
    {
        $job = $this->job($tenantId, $jobId); if (! $job || ! in_array($job->status, ['previewed', 'matching'], true)) return ['success' => false, 'message' => 'Import job chưa ở bước identity matching.'];
        $players = $this->db->table('players')->where('tenant_id', $tenantId)->where('deleted_at', null)->get()->getResult();
        $seenRows = [];
        foreach ($this->rows($jobId) as $row) {
            $raw = json_decode($row->raw_data, true) ?: []; $candidates = [];
            $nationalId = trim((string) ($raw['national_player_id'] ?? ''));
            if ($nationalId && $this->db->tableExists('player_competitive_profiles')) {
                $passport = $this->db->table('player_competitive_profiles')->select('player_id')->where('national_player_id', $nationalId)->where('deleted_at', null)->get()->getRow();
                if ($passport) $candidates[(int) $passport->player_id] = 100;
            }
            foreach (['phone' => 'phone', 'email' => 'email'] as $rawKey => $claimType) {
                $value = trim((string) ($raw[$rawKey] ?? ''));
                if ($value && $this->db->tableExists('player_identity_claims')) foreach ($this->db->table('player_identity_claims')->select('player_id')->where('claim_type', $claimType)->where('claim_value', $value)->get()->getResult() as $claim) $candidates[(int) $claim->player_id] = max($candidates[(int) $claim->player_id] ?? 0, 90);
            }
            $name = $this->normalizeName((string) ($raw['full_name'] ?? $raw['name'] ?? ''));
            foreach ($players as $player) {
                $matches = 0;
                if (! empty($raw['player_id']) && (int) $raw['player_id'] === (int) $player->id) $matches += 100;
                if (! empty($raw['phone']) && (string) $raw['phone'] === (string) $player->phone) $matches += 90;
                if (! empty($raw['email']) && strtolower((string) $raw['email']) === strtolower((string) $player->email)) $matches += 90;
                $playerName = $this->normalizeName((string) $player->full_name);
                if ($name !== '' && $name === $playerName) $matches += 70;
                elseif ($name !== '' && $playerName !== '') { similar_text($name, $playerName, $percent); if ($percent >= 88) $matches += 45; }
                if ($matches) $candidates[(int) $player->id] = max($candidates[(int) $player->id] ?? 0, $matches);
            }
            arsort($candidates); $top = array_key_first($candidates); $scores = array_values($candidates); $identity = ! $candidates ? 'unmatched' : (($scores[1] ?? -1) === $scores[0] ? 'ambiguous' : 'matched');
            $fingerprint = $this->duplicateFingerprint($raw, $identity === 'matched' ? (int) $top : null);
            if ($identity === 'matched' && $fingerprint !== null) {
                if (isset($seenRows[$fingerprint]) || $this->existingClaimFingerprint($tenantId, $raw, (int) $top)) {
                    $identity = 'duplicate';
                    $top = null;
                } else {
                    $seenRows[$fingerprint] = true;
                }
            }
            $this->db->table('rating_import_rows')->where('id', $row->id)->update(['player_id' => $identity === 'matched' ? $top : null, 'identity_status' => $identity, 'updated_at' => date('Y-m-d H:i:s')]);
        }
        $this->db->table('rating_import_jobs')->where('id', $jobId)->update(['status' => 'matching', 'updated_at' => date('Y-m-d H:i:s')]);
        return ['success' => true, 'job_id' => $jobId, 'status' => 'matching', 'next_step' => 'duplicate_check_and_score_validation'];
    }

    public function validate(int $tenantId, int $jobId): array
    {
        $job = $this->job($tenantId, $jobId); if (! $job) return ['success' => false, 'message' => 'Import job không tồn tại.'];
        $valid = 0; $invalid = 0;
        foreach ($this->rows($jobId) as $row) {
            $raw = json_decode($row->raw_data, true) ?: []; $errors = [];
            $rating = $raw['rating'] ?? $raw['claimed_rating'] ?? null;
            if ($rating !== null && ((float) $rating < 2 || (float) $rating > 6)) $errors[] = 'RATING_OUT_OF_RANGE';
            if ($row->identity_status !== 'matched') $errors[] = 'IDENTITY_' . strtoupper($row->identity_status);
            $status = $errors ? 'invalid' : 'valid'; $errors ? $invalid++ : $valid++;
            $this->db->table('rating_import_rows')->where('id', $row->id)->update(['validation_status' => $status, 'validation_errors' => $errors ? json_encode($errors) : null, 'updated_at' => date('Y-m-d H:i:s')]);
        }
        $this->db->table('rating_import_jobs')->where('id', $jobId)->update(['status' => 'validated', 'metadata' => json_encode(['valid_rows' => $valid, 'invalid_rows' => $invalid]), 'updated_at' => date('Y-m-d H:i:s')]);
        return ['success' => $invalid === 0, 'job_id' => $jobId, 'status' => 'validated', 'valid_rows' => $valid, 'invalid_rows' => $invalid, 'next_step' => $invalid === 0 ? 'source_verification' : 'fix_rows'];
    }

    public function verifySource(int $tenantId, int $jobId, bool $verified): array
    {
        $job = $this->job($tenantId, $jobId); if (! $job || $job->status !== 'validated') return ['success' => false, 'message' => 'Import job chưa qua validation.'];
        $status = $verified ? 'verified' : 'rejected'; $this->db->table('rating_import_rows')->where('job_id', $jobId)->where('validation_status', 'valid')->update(['verification_status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->db->table('rating_import_jobs')->where('id', $jobId)->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
        return ['success' => $verified, 'job_id' => $jobId, 'status' => $status, 'next_step' => $verified ? 'import' : 'rejected'];
    }

    public function importClaims(int $tenantId, int $jobId): array
    {
        $job = $this->job($tenantId, $jobId); if (! $job || $job->status !== 'verified') return ['success' => false, 'message' => 'Source chưa được verify.'];
        $count = 0; $disciplineMap = [];
        foreach ($this->db->table('rating_disciplines')->where('active', 1)->get()->getResult() as $d) $disciplineMap[$d->code] = $d->id;
        foreach ($this->rows($jobId) as $row) {
            if ($row->validation_status !== 'valid' || $row->verification_status !== 'verified' || ! $row->player_id) continue;
            $raw = json_decode($row->raw_data, true) ?: []; $discipline = $raw['discipline'] ?? 'singles'; $disciplineId = $disciplineMap[$discipline] ?? $disciplineMap['singles'] ?? null; if (! $disciplineId) continue;
            $this->db->table('player_skill_claims')->insert(['tenant_id' => $tenantId, 'player_id' => $row->player_id, 'discipline_id' => $disciplineId, 'source_type' => $job->source_type === 'external_provider' ? 'external_provider' : $job->source_type, 'source_organization_id' => $raw['source_organization_id'] ?? null, 'claimed_rating' => $raw['rating'] ?? $raw['claimed_rating'] ?? null, 'verification_status' => 'verified', 'external_provider' => $raw['external_provider'] ?? null, 'external_reference' => $raw['external_reference'] ?? null, 'evidence' => json_encode(['import_job_id' => $jobId, 'row_id' => $row->id], JSON_UNESCAPED_UNICODE), 'claimed_at' => date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]); $count++;
        }
        $this->db->table('rating_import_jobs')->where('id', $jobId)->update(['status' => 'imported', 'metadata' => json_encode(['imported_claims' => $count]), 'updated_at' => date('Y-m-d H:i:s')]);
        return ['success' => true, 'job_id' => $jobId, 'status' => 'imported', 'imported_claims' => $count, 'note' => 'Đã tạo skill claims; official rating chỉ phát sinh từ official match engine.'];
    }

    private function ready(): bool { return $this->db->tableExists('rating_import_jobs') && $this->db->tableExists('rating_import_rows') && $this->db->tableExists('player_skill_claims'); }
    private function job(int $tenantId, int $jobId): ?object { return $this->db->table('rating_import_jobs')->where('id', $jobId)->where('tenant_id', $tenantId)->get()->getRow(); }
    private function rows(int $jobId): array { return $this->db->table('rating_import_rows')->where('job_id', $jobId)->orderBy('row_number')->get()->getResult(); }
    private function normalizeName(string $name): string { return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower($name))); }
    private function duplicateFingerprint(array $raw, ?int $playerId): ?string
    {
        $reference = trim((string) ($raw['external_reference'] ?? $raw['match_id'] ?? $raw['source_match_id'] ?? ''));
        return $reference !== '' && $playerId ? implode(':', [$playerId, strtolower($reference), strtolower((string) ($raw['discipline'] ?? 'singles'))]) : null;
    }
    private function existingClaimFingerprint(int $tenantId, array $raw, int $playerId): bool
    {
        $reference = trim((string) ($raw['external_reference'] ?? ''));
        if ($reference === '' || ! $this->db->tableExists('player_skill_claims')) return false;
        return $this->db->table('player_skill_claims')->where('tenant_id', $tenantId)->where('player_id', $playerId)->where('external_reference', $reference)->countAllResults() > 0;
    }
    private function transition(int $tenantId, int $jobId, string $to, array $from): array { $job = $this->job($tenantId, $jobId); if (! $job || ! in_array($job->status, $from, true)) return ['success' => false, 'message' => 'Import job không ở bước hợp lệ.']; $this->db->table('rating_import_jobs')->where('id', $jobId)->update(['status' => $to, 'updated_at' => date('Y-m-d H:i:s')]); return ['success' => true, 'job_id' => $jobId, 'status' => $to, 'next_step' => $to === 'previewed' ? 'identity_matching' : null]; }
}
