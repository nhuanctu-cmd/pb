<?php
namespace App\Models;
use CodeIgniter\Model;
class ReviewModel extends Model
{
    protected $table='reviews'; protected $primaryKey='id'; protected $returnType='object'; protected $allowedFields=['tenant_id','player_id','entity_type','entity_id','rating','title','body','status']; protected $useTimestamps=true;
    public function getByTenant(int $tenantId,array $filters=[]): array { $b=$this->select('reviews.*, players.full_name as player_name')->join('players','players.id=reviews.player_id','left')->where('reviews.tenant_id',$tenantId); if(!empty($filters['status']))$b->where('reviews.status',$filters['status']); return $b->orderBy('reviews.created_at','DESC')->findAll(); }
    public function findForPlayer(int $playerId,string $type,int $entityId,int $tenantId): ?object { return $this->where('tenant_id',$tenantId)->where('player_id',$playerId)->where('entity_type',$type)->where('entity_id',$entityId)->first(); }
}
