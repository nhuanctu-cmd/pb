<?php
namespace App\Models;
use CodeIgniter\Model;
class CompetitionCheckinModel extends Model
{
    protected $table='competition_checkins'; protected $primaryKey='id'; protected $returnType='object'; protected $allowedFields=['tenant_id','event_id','participant_id','status','checkin_at']; protected $useTimestamps=true;
    public function findForParticipant(int $eventId,int $participantId,int $tenantId): ?object { return $this->where('event_id',$eventId)->where('participant_id',$participantId)->where('tenant_id',$tenantId)->first(); }
    public function getByEvent(int $eventId,int $tenantId): array { return $this->where('event_id',$eventId)->where('tenant_id',$tenantId)->findAll(); }
}
