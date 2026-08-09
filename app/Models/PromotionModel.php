<?php
namespace App\Models;
use CodeIgniter\Model;
class PromotionModel extends Model
{
    protected $table='promotions'; protected $primaryKey='id'; protected $returnType='object'; protected $useSoftDeletes=true;
    protected $allowedFields=['tenant_id','code','name','discount_type','discount_value','max_discount','min_order_amount','usage_limit','per_customer_limit','starts_at','ends_at','status','created_by','updated_by'];
    protected $useTimestamps=true; protected $dateFormat='datetime'; protected $deletedField='deleted_at';
    public function getByTenant(int $tenantId): array { return $this->where('tenant_id',$tenantId)->where('deleted_at',null)->orderBy('created_at','DESC')->findAll(); }
    public function findCode(string $code,int $tenantId): ?object { return $this->where('tenant_id',$tenantId)->where('code',strtoupper(trim($code)))->where('deleted_at',null)->first(); }
    public function findForUpdate(int $id,int $tenantId): ?object { $r=$this->db->query('SELECT * FROM promotions WHERE id=? AND tenant_id=? AND deleted_at IS NULL LIMIT 1 FOR UPDATE',[$id,$tenantId])->getRowArray(); return $r?(object)$r:null; }
}
