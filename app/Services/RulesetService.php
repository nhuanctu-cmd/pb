<?php

namespace App\Services;

use App\Models\DrawPolicyVersionModel;
use App\Models\EligibilityPolicyVersionModel;
use App\Models\RulesetModel;
use App\Models\RulesetVersionModel;
use App\Models\SeedingPolicyVersionModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use InvalidArgumentException;

/** Creates immutable competition rules and policy versions. */
class RulesetService
{
    private RulesetModel $rulesetModel;
    private RulesetVersionModel $versionModel;
    private BaseConnection $db;

    public function __construct()
    {
        $this->rulesetModel = model(RulesetModel::class);
        $this->versionModel = model(RulesetVersionModel::class);
        $this->db = Database::connect();
    }

    public function createVersion(int $rulesetId, array $content, string $version, ?int $actorId = null, ?string $effectiveFrom = null): array
    {
        if ($rulesetId <= 0 || trim($version) === '' || $content === []) throw new InvalidArgumentException('Ruleset version needs an id, version and content.');
        $payload = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hash = hash('sha256', $payload);
        $existing = $this->versionModel->where('ruleset_id', $rulesetId)->where('version', $version)->first();
        if ($existing) return ['success' => false, 'code' => 'RULESET_VERSION_IMMUTABLE', 'message' => 'Ruleset version already exists.'];
        $id = $this->versionModel->insert(['ruleset_id' => $rulesetId, 'version' => $version, 'content' => $payload, 'content_hash' => $hash, 'effective_from' => $effectiveFrom ?: date('Y-m-d H:i:s'), 'status' => 'active', 'created_by' => $actorId, 'created_at' => date('Y-m-d H:i:s')]);
        return $id ? ['success' => true, 'version_id' => (int) $id, 'content_hash' => $hash] : ['success' => false, 'message' => 'Unable to create ruleset version.'];
    }

    public function effectiveVersion(int $rulesetId, ?string $at = null): ?object
    {
        $at = $at ?: date('Y-m-d H:i:s');
        return $this->versionModel->where('ruleset_id', $rulesetId)->where('status', 'active')->where('effective_from <=', $at)->groupStart()->where('effective_to >=', $at)->orWhere('effective_to', null)->groupEnd()->orderBy('effective_from', 'DESC')->first();
    }

    public function snapshot(int $versionId): ?array
    {
        $version = $this->versionModel->find($versionId);
        if (! $version) return null;
        $content = is_string($version->content) ? json_decode($version->content, true) : $version->content;
        return ['id' => (int) $version->id, 'ruleset_id' => (int) $version->ruleset_id, 'version' => $version->version, 'content_hash' => $version->content_hash, 'content' => is_array($content) ? $content : []];
    }

    public function createPolicyVersion(string $type, string $code, string $version, array $policy, ?int $actorId = null, ?string $effectiveFrom = null): array
    {
        $models = ['eligibility' => EligibilityPolicyVersionModel::class, 'seeding' => SeedingPolicyVersionModel::class, 'draw' => DrawPolicyVersionModel::class];
        if (! isset($models[$type])) throw new InvalidArgumentException('Unsupported policy version type.');
        $model = model($models[$type]);
        $payload = json_encode($policy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hash = hash('sha256', $payload);
        if ($model->where('code', $code)->where('version', $version)->first()) return ['success' => false, 'code' => 'POLICY_VERSION_IMMUTABLE'];
        $id = $model->insert(['code' => $code, 'version' => $version, 'policy' => $payload, 'content_hash' => $hash, 'effective_from' => $effectiveFrom ?: date('Y-m-d H:i:s'), 'status' => 'active', 'created_by' => $actorId, 'created_at' => date('Y-m-d H:i:s')]);
        return $id ? ['success' => true, 'version_id' => (int) $id, 'content_hash' => $hash] : ['success' => false, 'message' => 'Unable to create policy version.'];
    }
}
