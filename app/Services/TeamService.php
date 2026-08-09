<?php

namespace App\Services;

use App\Models\TeamMemberModel;
use App\Models\TeamModel;

class TeamService
{
    protected TeamModel $teamModel;
    protected TeamMemberModel $teamMemberModel;

    public function __construct()
    {
        $this->teamModel = model(TeamModel::class);
        $this->teamMemberModel = model(TeamMemberModel::class);
    }

    public function createTeam(array $data): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $teamId = $this->teamModel->insert([
            'tenant_id' => $data['tenant_id'],
            'club_id' => $data['club_id'] ?? null,
            'team_name' => $data['team_name'],
            'captain_player_id' => $data['captain_player_id'],
            'team_type' => $data['team_type'] ?? 'group',
            'rating_avg' => 0,
            'status' => $data['status'] ?? 'active',
        ]);

        if (! $teamId) {
            $db->transRollback();
            return ['success' => false, 'message' => implode(' ', $this->teamModel->errors()) ?: 'Không thể tạo team.'];
        }

        $this->teamMemberModel->insert([
            'tenant_id' => $data['tenant_id'],
            'team_id' => $teamId,
            'player_id' => $data['captain_player_id'],
            'role' => 'captain',
            'status' => 'accepted',
        ]);

        $db->transComplete();
        $this->calculateTeamRating((int) $teamId);

        return [
            'success' => $db->transStatus(),
            'message' => $db->transStatus() ? 'Đã tạo team.' : 'Không thể tạo team.',
            'team' => $this->teamModel->find($teamId),
        ];
    }

    public function inviteMember(int $teamId, int $playerId, int $tenantId): array
    {
        $team = $this->teamModel->find($teamId);
        if (! $team || (int) $team->tenant_id !== $tenantId) {
            return ['success' => false, 'message' => 'Không tìm thấy team.'];
        }

        $existing = $this->teamMemberModel
            ->where('team_id', $teamId)
            ->where('player_id', $playerId)
            ->withDeleted()
            ->first();

        $payload = [
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'player_id' => $playerId,
            'role' => 'member',
            'status' => 'invited',
            'deleted_at' => null,
        ];

        if ($existing) {
            $ok = $this->teamMemberModel->update($existing->id, $payload);
        } else {
            $ok = (bool) $this->teamMemberModel->insert($payload);
        }

        return ['success' => $ok, 'message' => $ok ? 'Đã gửi lời mời.' : 'Không thể mời thành viên.'];
    }

    public function acceptInvite(int $teamId, int $playerId, int $tenantId): array
    {
        $member = $this->teamMemberModel
            ->where('tenant_id', $tenantId)
            ->where('team_id', $teamId)
            ->where('player_id', $playerId)
            ->where('status', 'invited')
            ->first();

        if (! $member) {
            return ['success' => false, 'message' => 'Không tìm thấy lời mời.'];
        }

        $ok = $this->teamMemberModel->update($member->id, ['status' => 'accepted']);
        $this->calculateTeamRating($teamId);

        return ['success' => $ok, 'message' => $ok ? 'Đã nhận lời mời.' : 'Không thể nhận lời mời.'];
    }

    public function removeMember(int $teamId, int $playerId, int $tenantId): array
    {
        $member = $this->teamMemberModel
            ->where('tenant_id', $tenantId)
            ->where('team_id', $teamId)
            ->where('player_id', $playerId)
            ->first();

        if (! $member || $member->role === 'captain') {
            return ['success' => false, 'message' => 'Không thể xóa thành viên này.'];
        }

        $ok = $this->teamMemberModel->update($member->id, ['status' => 'removed']);
        $this->calculateTeamRating($teamId);

        return ['success' => $ok, 'message' => $ok ? 'Đã xóa thành viên khỏi team.' : 'Không thể xóa thành viên.'];
    }

    public function calculateTeamRating(int $teamId): float
    {
        $row = $this->teamMemberModel->db->table('team_members')
            ->select('AVG(COALESCE(players.rating_score, 0)) as rating_avg')
            ->join('players', 'players.id = team_members.player_id')
            ->where('team_members.team_id', $teamId)
            ->where('team_members.status', 'accepted')
            ->where('team_members.deleted_at', null)
            ->get()
            ->getRow();

        $rating = round((float) ($row->rating_avg ?? 0), 2);
        $this->teamModel->skipValidation(true)->update($teamId, ['rating_avg' => $rating]);
        $this->teamModel->skipValidation(false);

        return $rating;
    }
}
