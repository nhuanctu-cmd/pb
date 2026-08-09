<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantUsageModel extends Model
{
    protected $table            = 'tenant_usage';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['tenant_id', 'metric', 'period', 'used_count'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'tenant_id'  => 'required|integer',
        'metric'     => 'required|max_length[50]',
        'period'     => 'required|max_length[7]',
        'used_count' => 'required|integer',
    ];

    /**
     * Lấy mức dùng hiện tại của 1 metric trong kỳ (mặc định tháng này)
     */
    public function getUsage(int $tenantId, string $metric, ?string $period = null): int
    {
        $period = $period ?? date('Y-m');

        $row = $this->where('tenant_id', $tenantId)
                    ->where('metric', $metric)
                    ->where('period', $period)
                    ->first();

        return (int) ($row['used_count'] ?? 0);
    }

    /**
     * Tăng bộ đếm sử dụng (tạo mới nếu chưa có)
     */
    public function incrementUsage(int $tenantId, string $metric, int $amount = 1, ?string $period = null): void
    {
        $period = $period ?? date('Y-m');

        $row = $this->where('tenant_id', $tenantId)
                    ->where('metric', $metric)
                    ->where('period', $period)
                    ->first();

        if ($row) {
            $this->update($row['id'], ['used_count' => $row['used_count'] + $amount]);
            return;
        }

        $this->insert([
            'tenant_id'  => $tenantId,
            'metric'     => $metric,
            'period'     => $period,
            'used_count' => $amount,
        ]);
    }
}
