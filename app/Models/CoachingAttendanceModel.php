<?php
namespace App\Models;
use CodeIgniter\Model;
class CoachingAttendanceModel extends Model
{
    protected $table='coaching_attendance'; protected $primaryKey='id'; protected $returnType='object'; protected $allowedFields=['tenant_id','session_id','player_id','status','checkin_at','checkout_at','note']; protected $useTimestamps=true;
    public function findForEntry(int $sessionId,int $playerId,int $tenantId): ?object { return $this->where('session_id',$sessionId)->where('player_id',$playerId)->where('tenant_id',$tenantId)->first(); }
    public function getBySession(int $sessionId,int $tenantId): array { return $this->select('coaching_attendance.*, players.full_name')->join('players','players.id=coaching_attendance.player_id','left')->where('coaching_attendance.session_id',$sessionId)->where('coaching_attendance.tenant_id',$tenantId)->findAll(); }
}
