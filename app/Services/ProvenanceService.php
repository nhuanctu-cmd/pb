<?php

namespace App\Services;

use App\Models\DataProvenanceRecordModel;
use Config\Database;

/** Append-only source lineage for competitive data. */
class ProvenanceService
{
    private DataProvenanceRecordModel $model;

    public function __construct()
    {
        $this->model = model(DataProvenanceRecordModel::class);
    }

    public function record(array $data): ?int
    {
        if (! Database::connect()->tableExists('data_provenance_records')) return null;
        $entityType = trim((string) ($data['entity_type'] ?? ''));
        $entityId = (int) ($data['entity_id'] ?? 0);
        if ($entityType === '' || $entityId <= 0) return null;

        $id = $this->model->insert([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'source_type' => strtoupper((string) ($data['source_type'] ?? 'UNKNOWN')),
            'source_id' => $data['source_id'] ?? null,
            'source_organization_id' => $data['source_organization_id'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'verified_by' => $data['verified_by'] ?? null,
            'verification_level' => strtoupper((string) ($data['verification_level'] ?? 'UNVERIFIED')),
            'import_batch_id' => $data['import_batch_id'] ?? null,
            'external_reference' => $data['external_reference'] ?? null,
            'policy_version_id' => $data['policy_version_id'] ?? null,
            'parent_provenance_id' => $data['parent_provenance_id'] ?? null,
            'metadata' => $this->json($data['metadata'] ?? null),
            'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
            'verified_at' => $data['verified_at'] ?? null,
        ]);
        return $id ? (int) $id : null;
    }

    public function lineage(string $entityType, int $entityId, int $limit = 100): array
    {
        if (! Database::connect()->tableExists('data_provenance_records')) return [];
        return $this->model->where('entity_type', $entityType)->where('entity_id', $entityId)->orderBy('created_at', 'ASC')->findAll(max(1, min(500, $limit)));
    }

    public function verify(int $recordId, int $actorId, string $level): bool
    {
        if (! Database::connect()->tableExists('data_provenance_records')) return false;
        return (bool) $this->model->update($recordId, ['verified_by' => $actorId, 'verification_level' => strtoupper($level), 'verified_at' => date('Y-m-d H:i:s')]);
    }

    private function json($value): ?string
    {
        if ($value === null) return null;
        return is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
