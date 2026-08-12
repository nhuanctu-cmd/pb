<?php

namespace App\Services;

use App\Models\CompetitionRulesetModel;
use App\Models\CompetitionRulesetVersionModel;
use App\Models\OrganizationMembershipModel;
use App\Models\PlatformCountryModel;
use Config\Database;

class InternationalFoundationService
{
    public function countries(): array { return model(PlatformCountryModel::class)->active(); }

    public function countryContext(string $countryCode): array
    {
        $country = model(PlatformCountryModel::class)->byCode($countryCode);
        return $country ? ['success' => true, 'country' => $country] : ['success' => false, 'message' => 'Mã quốc gia không được hỗ trợ.'];
    }

    public function upsertMembership(int $tenantId, int $userId, string $roleCode, ?int $branchId = null, bool $primary = false): array
    {
        if ($tenantId <= 0 || $userId <= 0 || trim($roleCode) === '') return ['success' => false, 'message' => 'Organization membership không hợp lệ.'];
        $model = model(OrganizationMembershipModel::class);
        $existing = $model->where('tenant_id', $tenantId)->where('user_id', $userId)->where('role_code', trim($roleCode))->first();
        $data = ['tenant_id' => $tenantId, 'user_id' => $userId, 'branch_id' => $branchId, 'role_code' => trim($roleCode), 'status' => 'active', 'is_primary' => $primary ? 1 : 0, 'starts_at' => date('Y-m-d H:i:s')];
        $id = $existing ? ($model->update($existing->id, $data) ? $existing->id : 0) : $model->insert($data);
        return $id ? ['success' => true, 'membership' => $model->find($id)] : ['success' => false, 'message' => 'Không lưu được organization membership.'];
    }

    public function attachRuleset(int $tournamentId, int $tenantId, int $rulesetId, int $versionId, ?int $userId = null): array
    {
        $db = Database::connect();
        $tournament = $db->table('tournaments')->where('id', $tournamentId)->where('tenant_id', $tenantId)->where('deleted_at', null)->get()->getRow();
        $ruleset = model(CompetitionRulesetModel::class)->where('id', $rulesetId)->where('status', 'active')->first();
        $version = model(CompetitionRulesetVersionModel::class)->where('id', $versionId)->where('ruleset_id', $rulesetId)->whereIn('status', ['active', 'locked'])->first();
        if (! $tournament || ! $ruleset || ! $version) return ['success' => false, 'message' => 'Tournament, ruleset hoặc version không hợp lệ.'];
        $db->table('tournaments')->where('id', $tournamentId)->where('tenant_id', $tenantId)->update(['ruleset_id' => $rulesetId, 'ruleset_version_id' => $versionId, 'updated_at' => date('Y-m-d H:i:s')]);
        $db->table('data_provenance')->insert(['tenant_id' => $tenantId, 'entity_type' => 'tournament_ruleset', 'entity_id' => $tournamentId, 'source_type' => 'platform_ruleset', 'source_id' => $versionId . ':' . $version->version, 'verification_status' => 'verified', 'actor_id' => $userId, 'evidence' => json_encode(['ruleset_code' => $ruleset->code])]);
        return ['success' => true, 'tournament_id' => $tournamentId, 'ruleset' => $ruleset, 'version' => $version];
    }

    public function formatMoney(float $amount, string $currency, string $locale = 'vi-VN'): string
    {
        $currency = strtoupper(trim($currency));
        if (! in_array($currency, ['VND', 'USD', 'THB', 'SGD'], true)) return number_format($amount, 2, '.', ',') . ' ' . $currency;
        return number_format($amount, $currency === 'VND' ? 0 : 2, $locale === 'vi-VN' ? ',' : '.', $locale === 'vi-VN' ? '.' : ',') . ' ' . $currency;
    }
}
