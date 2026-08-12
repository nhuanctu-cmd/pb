<?php

namespace App\Services;

use Config\Database;

class QueueMonitorService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function report(int $tenantId): array
    {
        if ($tenantId <= 0 || ! $this->db->tableExists('jobs')) return ['tenant_id' => $tenantId, 'summary' => [], 'failed' => []];
        $summary = $this->db->table('jobs')->select("queue, COUNT(*) total, SUM(failed_at IS NULL AND reserved_at IS NULL) pending, SUM(reserved_at IS NOT NULL) reserved, SUM(failed_at IS NOT NULL) failed, SUM(dead_lettered_at IS NOT NULL) dead_lettered")->where('tenant_id', $tenantId)->groupBy('queue')->orderBy('queue')->get()->getResult();
        $failed = $this->db->table('jobs')->select('id, queue, attempts, max_attempts, error_message, dead_lettered_at, created_at, updated_at')->where('tenant_id', $tenantId)->where('failed_at IS NOT NULL', null, false)->orderBy('updated_at', 'DESC')->limit(100)->get()->getResult();
        return ['tenant_id' => $tenantId, 'summary' => $summary, 'failed' => $failed, 'generated_at' => date('Y-m-d H:i:s')];
    }

    public function retry(int $jobId, int $tenantId): array
    {
        $job = $this->db->table('jobs')->where('id', $jobId)->where('tenant_id', $tenantId)->get()->getRow();
        if (! $job || ! $job->failed_at) return ['success' => false, 'message' => 'Job không ở trạng thái failed hoặc không thuộc tenant.'];
        if ($this->db->fieldExists('dead_lettered_at', 'jobs') && $job->dead_lettered_at) return ['success' => false, 'message' => 'Job đã vào dead-letter, cần replay có chủ đích.'];
        $data = ['failed_at' => null, 'reserved_at' => null, 'available_at' => date('Y-m-d H:i:s'), 'error_message' => null, 'attempts' => 0];
        $this->db->table('jobs')->where('id', $jobId)->where('tenant_id', $tenantId)->update($data);
        return ['success' => $this->db->affectedRows() >= 0, 'message' => 'Đã đưa job về hàng đợi retry.'];
    }

    public function deadLetter(int $jobId, int $tenantId, string $reason = ''): array
    {
        $job = $this->db->table('jobs')->where('id', $jobId)->where('tenant_id', $tenantId)->get()->getRow();
        if (! $job || ! $job->failed_at) return ['success' => false, 'message' => 'Job không ở trạng thái failed hoặc không thuộc tenant.'];
        if (! $this->db->fieldExists('dead_lettered_at', 'jobs')) return ['success' => false, 'message' => 'Migration dead-letter chưa được chạy.'];
        $this->db->table('jobs')->where('id', $jobId)->where('tenant_id', $tenantId)->update(['dead_lettered_at' => date('Y-m-d H:i:s'), 'dead_letter_reason' => trim($reason) ?: ($job->error_message ?? 'Manual dead-letter')]);
        return ['success' => true, 'message' => 'Đã chuyển job vào dead-letter.'];
    }
}
