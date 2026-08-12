<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table = 'customers';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'tenant_id', 'player_id', 'full_name', 'phone', 'email', 'status', 'source',
        'first_seen_at', 'last_seen_at', 'last_booking_at', 'last_visit_at',
        'total_bookings', 'completed_bookings', 'no_show_count', 'total_spend',
        'favorite_court_id', 'metadata', 'created_by', 'updated_by',
    ];

    public function findForTenant(int $customerId, int $tenantId): ?object
    {
        return $this->where('id', $customerId)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
    }

    public function findByIdentity(int $tenantId, ?int $playerId, ?string $phone, ?string $email): ?object
    {
        $base = $this->where('tenant_id', $tenantId)->where('deleted_at', null)->where('status !=', 'merged');
        if ($playerId) {
            $row = (clone $base)->where('player_id', $playerId)->first();
            if ($row) return $row;
        }
        if ($phone) {
            $row = (clone $base)->where('phone', $phone)->first();
            if ($row) return $row;
        }
        if ($email) {
            return (clone $base)->where('email', $email)->first();
        }
        return null;
    }

    public function timeline(int $customerId, int $tenantId, int $limit = 100): array
    {
        if (! $this->db->tableExists('customer_timeline_events')) return [];
        return $this->db->table('customer_timeline_events')
            ->where('customer_id', $customerId)
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get()->getResult();
    }

    public function tagRows(int $tenantId, int $customerId): array
    {
        if (! $this->db->tableExists('customer_tag_links') || ! $this->db->tableExists('customer_tags')) return [];

        return $this->db->table('customer_tag_links ctl')
            ->select('ctl.*, ct.code, ct.name, ct.color')
            ->join('customer_tags ct', 'ct.id = ctl.tag_id', 'left')
            ->where('ctl.tenant_id', $tenantId)
            ->where('ctl.customer_id', $customerId)
            ->orderBy('ct.name', 'ASC')
            ->get()->getResult();
    }

    public function availableTags(int $tenantId): array
    {
        if (! $this->db->tableExists('customer_tags')) return [];

        return $this->db->table('customer_tags')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('name', 'ASC')
            ->get()->getResult();
    }

    public function ensureTag(int $tenantId, string $name): ?object
    {
        if (! $this->db->tableExists('customer_tags')) return null;

        $name = trim($name);
        if ($name === '') return null;

        $code = preg_replace('/[^a-z0-9_\\-]+/i', '', strtolower(str_replace(' ', '_', $name)));
        if ($code === '') {
            $code = 'tag_' . date('Ymd_His');
        }

        $exists = $this->db->table('customer_tags')
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->get()->getRow();

        if ($exists) return $exists;

        $palette = ['#D3E5FF', '#DCF7E3', '#FFE4BA', '#FFD6E8', '#E9DEFF', '#D9F2F5', '#FFE7D6', '#DFF4FF'];
        $color = $palette[(int) crc32($code) % count($palette)];

        $payload = [
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => $name,
            'color' => $color,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->table('customer_tags')->insert($payload);
        return $this->db->table('customer_tags')->where('id', (int) $this->db->insertID())->get()->getRow();
    }

    public function syncTags(int $tenantId, int $customerId, array $tagIds): array
    {
        if (! $this->db->tableExists('customer_tag_links') || ! $this->db->tableExists('customer_tags')) return [];

        $validTagIds = [];
        if (! empty($tagIds)) {
            $rows = $this->db->table('customer_tags')
                ->select('id')
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $tagIds)
                ->get()->getResult();
            foreach ($rows as $row) {
                $validTagIds[] = (int) $row->id;
            }
        }

        if (! empty($validTagIds)) {
            $this->db->table('customer_tag_links')
                ->where('tenant_id', $tenantId)
                ->where('customer_id', $customerId)
                ->whereNotIn('tag_id', $validTagIds)
                ->delete();
        } else {
            $this->db->table('customer_tag_links')
                ->where('tenant_id', $tenantId)
                ->where('customer_id', $customerId)
                ->delete();
        }

        foreach ($validTagIds as $tagId) {
            $exists = $this->db->table('customer_tag_links')
                ->where('tenant_id', $tenantId)
                ->where('customer_id', $customerId)
                ->where('tag_id', $tagId)
                ->countAllResults();
            if ($exists) continue;

            $this->db->table('customer_tag_links')->insert([
                'tenant_id' => $tenantId,
                'customer_id' => $customerId,
                'tag_id' => $tagId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->tagRows($tenantId, $customerId);
    }

    public function unlinkTag(int $tenantId, int $customerId, int $tagId): void
    {
        if (! $this->db->tableExists('customer_tag_links')) return;
        $this->db->table('customer_tag_links')
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->where('tag_id', $tagId)
            ->delete();
    }

    public function sourceList(): array
    {
        return ['booking', 'player', 'admin', 'import', 'api', 'other'];
    }

    public function searchForTenant(int $tenantId, array $filters = [], int $perPage = 25)
    {
        $builder = $this->where('customers.tenant_id', $tenantId)->where('customers.deleted_at', null);

        if (! empty($filters['status'])) $builder->where('customers.status', $filters['status']);
        if (! empty($filters['source'])) $builder->where('customers.source', $filters['source']);
        if (! empty($filters['has_player'])) {
            if ($filters['has_player'] === 'yes') {
                $builder->where('customers.player_id IS NOT NULL', null, false);
            } else {
                $builder->where('customers.player_id IS NULL', null, false);
            }
        }
        if (! empty($filters['tag_id'])) {
            $builder->whereIn('customers.id', function ($sub) use ($tenantId, $filters) {
                $sub->select('customer_id')
                    ->from('customer_tag_links')
                    ->where('tenant_id', $tenantId)
                    ->where('tag_id', (int) $filters['tag_id']);
            });
        }

        if (! empty($filters['search'])) {
            $builder->groupStart()
                ->like('customers.full_name', $filters['search'])
                ->orLike('customers.phone', $filters['search'])
                ->orLike('customers.email', $filters['search'])
                ->groupEnd();
        }

        return $builder->orderBy('customers.last_seen_at', 'DESC')->paginate($perPage);
    }

    public function dashboardStats(int $tenantId, array $filters = []): array
    {
        $base = $this->where('tenant_id', $tenantId)->where('deleted_at', null);
        if (! empty($filters['search'])) {
            $base->groupStart()
                ->like('full_name', $filters['search'])
                ->orLike('phone', $filters['search'])
                ->orLike('email', $filters['search'])
                ->groupEnd();
        }
        if (! empty($filters['source'])) {
            $base->where('source', $filters['source']);
        }
        if (! empty($filters['status'])) {
            $base->where('status', $filters['status']);
        }
        if (! empty($filters['has_player'])) {
            if ($filters['has_player'] === 'yes') {
                $base->where('player_id IS NOT NULL', null, false);
            } else {
                $base->where('player_id IS NULL', null, false);
            }
        }
        if (! empty($filters['tag_id'])) {
            $base->whereIn('id', function ($sub) use ($tenantId, $filters) {
                $sub->select('customer_id')
                    ->from('customer_tag_links')
                    ->where('tenant_id', $tenantId)
                    ->where('tag_id', (int) $filters['tag_id']);
            });
        }

        $total = (clone $base)->countAllResults();
        $active = (clone $base)->where('status', 'active')->countAllResults();
        $inactive = (clone $base)->where('status', 'inactive')->countAllResults();
        $bookings = (clone $base)->selectSum('total_bookings')->get()->getRow();
        $spend = (clone $base)->selectSum('total_spend')->get()->getRow();
        $newThisMonth = (clone $base)->where('created_at >=', date('Y-m-01 00:00:00'))->countAllResults();

        return [
            'total' => (int) $total,
            'active' => (int) $active,
            'inactive' => (int) $inactive,
            'merged' => max(0, (int) $total - (int) $active - (int) $inactive),
            'booking_count' => (float) ($bookings->total_bookings ?? 0),
            'revenue' => (float) ($spend->total_spend ?? 0),
            'new_this_month' => (int) $newThisMonth,
        ];
    }
}
