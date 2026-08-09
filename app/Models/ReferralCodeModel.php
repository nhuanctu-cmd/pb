<?php
namespace App\Models;
use CodeIgniter\Model;
class ReferralCodeModel extends Model
{
    protected $table='referral_codes'; protected $primaryKey='id'; protected $returnType='object'; protected $allowedFields=['tenant_id','player_id','code','reward_amount','uses_count','max_uses','status']; protected $useTimestamps=true;
    public function findCode(string $code,int $tenantId): ?object { return $this->where('tenant_id',$tenantId)->where('code',strtoupper(trim($code)))->where('status','active')->first(); }
    public function findForPlayer(int $playerId,int $tenantId): ?object { return $this->where('tenant_id',$tenantId)->where('player_id',$playerId)->first(); }
}
