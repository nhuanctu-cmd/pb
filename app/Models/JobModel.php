<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Job queue đơn giản cho email và tác vụ nền
 */
class JobModel extends Model
{
    protected $table            = 'jobs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'queue', 'payload', 'attempts', 'max_attempts',
        'reserved_at', 'available_at', 'failed_at', 'error_message',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function push(string $queue, array $payload, int $delaySeconds = 0, int $maxAttempts = 3): int
    {
        $queue = trim($queue);
        $maxAttempts = max(1, $maxAttempts);
        if ($queue === '') {
            return 0;
        }

        return (int) $this->insert([
            'queue'        => $queue,
            'payload'      => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'attempts'     => 0,
            'max_attempts' => $maxAttempts,
            'available_at' => date('Y-m-d H:i:s', time() + $delaySeconds),
            'reserved_at'  => null,
        ]);
    }

    public function reserve(string $queue = 'default', int $limit = 10): array
    {
        $now = date('Y-m-d H:i:s');
        $limit = max(1, min(100, $limit));
        $queue = trim($queue) ?: 'default';

        // Reservation must be an atomic claim. A plain SELECT followed by an
        // UPDATE lets two workers receive the same email under load.
        $this->db->transBegin();
        $sql = 'SELECT * FROM jobs
                WHERE queue = ?
                  AND reserved_at IS NULL
                  AND failed_at IS NULL
                  AND available_at <= ?
                  AND attempts < max_attempts
                ORDER BY created_at ASC, id ASC
                LIMIT ' . $limit . ' FOR UPDATE';
        $jobs = $this->db->query($sql, [$queue, $now])->getResult();

        foreach ($jobs as $job) {
            $this->db->table($this->table)
                ->where('id', $job->id)
                ->where('reserved_at', null)
                ->set(['reserved_at' => $now])
                ->update();
            $job->reserved_at = $now;
        }

        if (!$this->db->transStatus()) {
            $this->db->transRollback();
            return [];
        }
        $this->db->transCommit();

        return $jobs;
    }

    public function markCompleted(int $jobId): bool
    {
        return $this->delete($jobId);
    }

    public function markFailed(int $jobId, string $error): bool
    {
        return $this->update($jobId, [
            'failed_at'     => date('Y-m-d H:i:s'),
            'reserved_at'   => null,
            'error_message' => $error,
        ]);
    }

    public function release(int $jobId): bool
    {
        return $this->db->table('jobs')
                        ->where('id', $jobId)
                        ->where('failed_at', null)
                        ->set('reserved_at', null)
                        ->set('attempts', 'attempts + 1', false)
                        ->update();
    }
}
