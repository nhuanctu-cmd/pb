<?php
namespace App\Models;
use CodeIgniter\Model;
class ReferralModel extends Model
{
    protected $table='referrals'; protected $primaryKey='id'; protected $returnType='object'; protected $allowedFields=['tenant_id','referrer_player_id','referred_player_id','code','reward_amount','status','qualified_at','rewarded_at']; protected $useTimestamps=true;
    public function getByTenant(int $tenantId): array { return $this->select('referrals.*, p1.full_name as referrer_name, p2.full_name as referred_name')->join('players p1','p1.id=referrals.referrer_player_id','left')->join('players p2','p2.id=referrals.referred_player_id','left')->where('referrals.tenant_id',$tenantId)->orderBy('referrals.created_at','DESC')->findAll(); }
    public function findForUpdate(int $id,int $tenantId): ?object { $r=$this->db->query('SELECT * FROM referrals WHERE id=? AND tenant_id=? LIMIT 1 FOR UPDATE',[$id,$tenantId])->getRowArray(); return $r?(object)$r:null; }
}
