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
        return (int) $this->insert([
            'queue'        => $queue,
            'payload'      => json_encode($payload),
            'attempts'     => 0,
            'max_attempts' => $maxAttempts,
            'available_at' => date('Y-m-d H:i:s', time() + $delaySeconds),
            'reserved_at'  => null,
        ]);
    }

    public function reserve(string $queue = 'default', int $limit = 10): array
    {
        $now = date('Y-m-d H:i:s');
        $jobs = $this->where('queue', $queue)
                     ->where('reserved_at', null)
                     ->where('failed_at', null)
                     ->where('available_at <=', $now)
                     ->where('attempts < max_attempts', null, false)
                     ->orderBy('created_at', 'ASC')
                     ->limit($limit)
                     ->findAll();

        if (! empty($jobs)) {
            $ids = array_map(fn ($job) => $job->id, $jobs);
            $this->whereIn('id', $ids)->set(['reserved_at' => $now])->update();

            // Return the reserved state to callers immediately as well as
            // persisting it, so workers do not operate on stale job objects.
            foreach ($jobs as $job) {
                $job->reserved_at = $now;
            }
        }

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
                        ->set('reserved_at', null)
                        ->set('attempts', 'attempts + 1', false)
                        ->update();
    }
}
