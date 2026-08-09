<?php
namespace App\Models;
use CodeIgniter\Model;
class CompetitionLadderChallengeModel extends Model
{
    protected $table='competition_ladder_challenges'; protected $primaryKey='id'; protected $returnType='object'; protected $allowedFields=['tenant_id','event_id','challenger_id','opponent_id','fixture_id','scheduled_date','start_time','score_challenger','score_opponent','winner_id','status','expires_at','created_by']; protected $useTimestamps=true;
    public function findForUpdate(int $id,int $tenantId): ?object { $r=$this->db->query('SELECT * FROM competition_ladder_challenges WHERE id=? AND tenant_id=? LIMIT 1 FOR UPDATE',[$id,$tenantId])->getRowArray(); return $r?(object)$r:null; }
    public function getByEvent(int $eventId,int $tenantId): array { return $this->select('competition_ladder_challenges.*, cp.display_name as challenger_name, op.display_name as opponent_name')->join('competition_participants cp','cp.id=competition_ladder_challenges.challenger_id','left')->join('competition_participants op','op.id=competition_ladder_challenges.opponent_id','left')->where('competition_ladder_challenges.event_id',$eventId)->where('competition_ladder_challenges.tenant_id',$tenantId)->orderBy('created_at','DESC')->findAll(); }
}
