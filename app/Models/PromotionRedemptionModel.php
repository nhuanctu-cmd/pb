<?php
namespace App\Models;
use CodeIgniter\Model;
class PromotionRedemptionModel extends Model
{
    protected $table='promotion_redemptions'; protected $primaryKey='id'; protected $returnType='object';
    protected $allowedFields=['tenant_id','promotion_id','player_id','booking_id','order_id','discount_amount','idempotency_key','created_at']; protected $useTimestamps=false; protected $createdField='created_at';
    public function countForPlayer(int $promotionId,int $playerId,int $tenantId): int { return (int)$this->where('promotion_id',$promotionId)->where('player_id',$playerId)->where('tenant_id',$tenantId)->countAllResults(); }
    public function findIdempotent(string $key,int $tenantId): ?object { return $this->where('tenant_id',$tenantId)->where('idempotency_key',$key)->first(); }
}
