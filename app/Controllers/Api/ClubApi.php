<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ClubModel;
use App\Models\PlayerClubMembershipModel;
use App\Models\PlayerModel;
use CodeIgniter\Database\BaseConnection;

class ClubApi extends BaseController
{
    protected ClubModel $clubModel;
    protected PlayerClubMembershipModel $membershipModel;
    protected PlayerModel $playerModel;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->clubModel       = new ClubModel();
        $this->membershipModel  = new PlayerClubMembershipModel();
        $this->playerModel     = new PlayerModel();
        $this->db              = db_connect();
    }

    public function index()
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        $search = trim((string) ($this->request->getGet('q') ?? $this->request->getGet('search') ?? ''));
        $status = (string) ($this->request->getGet('status') ?? '');
        $sortBy = (string) ($this->request->getGet('sort_by') ?? 'name_vi');
        $sortBy = in_array($sortBy, ['name_vi', 'name_en', 'created_at', 'updated_at', 'status'], true) ? $sortBy : 'name_vi';
        $sortDir = strtoupper((string) ($this->request->getGet('sort_dir') ?? 'ASC'));
        $sortDir = $sortDir === 'DESC' ? 'DESC' : 'ASC';
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(100, max(1, (int) ($this->request->getGet('limit') ?? 20)));
        $format = strtolower((string) ($this->request->getGet('format') ?? 'json'));
        $offset = ($page - 1) * $limit;

        $builder = $this->db->table('clubs')
            ->select('id, name_vi, name_en, logo, description_vi, description_en, status, owner_player_id, created_at, updated_at')
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null);
        if ($status !== '') {
            $builder->where('status', $status);
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('name_vi', '%' . $this->db->escapeLikeString($search) . '%', 'both', null, true)
                ->orLike('name_en', '%' . $this->db->escapeLikeString($search) . '%', 'both', null, true)
                ->orLike('description_vi', '%' . $this->db->escapeLikeString($search) . '%', 'both', null, true)
                ->groupEnd();
        }

        $total = $this->countBuilder($builder);
        $rows = $builder
            ->orderBy($sortBy, $sortDir)
            ->limit($limit, $offset)
            ->get()
            ->getResult();

        if ($format === 'csv') {
            return $this->exportCsv($rows, [
                'Club ID',
                'Tên VI',
                'Tên EN',
                'Mô tả VI',
                'Mô tả EN',
                'Trạng thái',
                'Ngày tạo',
            ], function ($row) {
                return [
                    $row->id,
                    $row->name_vi,
                    $row->name_en,
                    $row->description_vi,
                    $row->description_en,
                    $row->status,
                    $row->created_at,
                ];
            }, 'clubs');
        }

        service('apiResponseService')->setMeta([
            'tenant_id' => $tenantId,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
        ]);
        service('apiResponseService')->setPagination($total, $limit, $page);
        return service('apiResponseService')->success($rows, 'Danh sách CLB.');
    }

    public function show(int $id)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        $club = $this->clubModel->findForTenant($id, $tenantId);
        return $club ? service('apiResponseService')->success($club) : service('apiResponseService')->notFound();
    }

    public function create()
    {
        $tenantId = $this->resolveTenantIdWithFallback();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        $payload = $this->payload();
        $payload['tenant_id'] = $tenantId;
        $payload['status'] = $payload['status'] ?? 'active';

        if (! $this->clubModel->insert($payload)) {
            return service('apiResponseService')->validationError($this->clubModel->errors() ?: ['general' => 'Không thể tạo CLB.']);
        }

        $id = (int) $this->clubModel->getInsertID();
        return service('apiResponseService')->created($this->clubModel->findForTenant($id, $tenantId), 'Đã tạo CLB.');
    }

    public function update(int $id)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        $club = $this->clubModel->findForTenant($id, $tenantId);
        if (! $club) {
            return service('apiResponseService')->notFound();
        }

        $payload = $this->payload();
        if (empty($payload)) {
            return service('apiResponseService')->validationError(['payload' => 'Không có dữ liệu thay đổi']);
        }

        $ok = $this->clubModel->update($id, $payload);
        return $ok ? service('apiResponseService')->updated($this->clubModel->findForTenant($id, $tenantId), 'Đã cập nhật CLB.') : service('apiResponseService')->validationError($this->clubModel->errors() ?: ['general' => 'Không thể cập nhật CLB.']);
    }

    public function delete(int $id)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        if (! $this->clubModel->findForTenant($id, $tenantId)) {
            return service('apiResponseService')->notFound();
        }
        $ok = $this->clubModel->delete($id);
        return $ok ? service('apiResponseService')->deleted('Đã xóa CLB.') : service('apiResponseService')->error('Không thể xóa CLB.');
    }

    public function members(int $id)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }
        if (! $this->clubModel->findForTenant($id, $tenantId)) {
            return service('apiResponseService')->notFound();
        }

        $status = (string) ($this->request->getGet('status') ?? 'active');
        $query = trim((string) ($this->request->getGet('q') ?? ''));
        $role = trim((string) ($this->request->getGet('role') ?? ''));
        $sortBy = (string) ($this->request->getGet('sort_by') ?? 'joined_at');
        $sortBy = in_array($sortBy, ['joined_at', 'left_at', 'full_name', 'role', 'is_primary'], true) ? $sortBy : 'joined_at';
        $sortDir = strtoupper((string) ($this->request->getGet('sort_dir') ?? 'DESC'));
        $sortDir = $sortDir === 'ASC' ? 'ASC' : 'DESC';
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(100, max(1, (int) ($this->request->getGet('limit') ?? 20)));
        $format = strtolower((string) ($this->request->getGet('format') ?? 'json'));
        $offset = ($page - 1) * $limit;

        $builder = $this->db->table('player_club_memberships pcm')
            ->select('pcm.id, pcm.role, pcm.status, pcm.joined_at, pcm.left_at, pcm.is_primary, p.id AS player_id, p.full_name, p.player_code, p.phone, p.region')
            ->join('players p', 'p.id = pcm.player_id AND p.deleted_at IS NULL', 'left')
            ->where('pcm.tenant_id', $tenantId)
            ->where('pcm.club_id', $id)
            ->whereIn('pcm.status', $status !== '' ? [$status] : ['pending', 'active', 'suspended', 'left']);
        if ($query !== '') {
            $builder->groupStart()
                ->like('p.full_name', '%' . $this->db->escapeLikeString($query) . '%', 'both', null, true)
                ->orLike('p.player_code', '%' . $this->db->escapeLikeString($query) . '%', 'both', null, true)
                ->groupEnd();
        }
        if ($role !== '') {
            $builder->where('pcm.role', $role);
        }

        $total = $this->countBuilder($builder);
        $rows = $builder
            ->orderBy($sortBy === 'full_name' ? 'p.full_name' : ('pcm.' . $sortBy), $sortDir)
            ->limit($limit, $offset)
            ->get()
            ->getResult();

        if ($format === 'csv') {
            return $this->exportCsv($rows, [
                'Membership ID',
                'Player ID',
                'Họ tên',
                'Mã VĐV',
                'Vai trò',
                'Tình trạng',
                'Đăng ký lúc',
                'Rời lúc',
                'Chính',
            ], function ($row) {
                return [
                    $row->id,
                    $row->player_id,
                    $row->full_name,
                    $row->player_code,
                    $row->role,
                    $row->status,
                    $row->joined_at,
                    $row->left_at,
                    $row->is_primary ? '1' : '0',
                ];
            }, 'club-memberships');
        }

        service('apiResponseService')->setMeta([
            'tenant_id' => $tenantId,
            'club_id' => $id,
            'filters' => [
                'status' => $status,
                'q' => $query,
                'role' => $role,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
        ]);
        service('apiResponseService')->setPagination($total, $limit, $page);
        return service('apiResponseService')->success($rows, 'Danh sách thành viên CLB.');
    }

    public function membersStore(int $clubId)
    {
        return $this->storeMembership($clubId);
    }

    public function storeMembership(int $clubId)
    {
        return $this->upsertMembership((int) $clubId, 'pending', 'Mời/vào nhóm membership.');
    }

    public function removeMembership(int $clubId, int $membershipId)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }
        if (! $this->clubModel->findForTenant($clubId, $tenantId)) {
            return service('apiResponseService')->notFound();
        }

        $membership = $this->membershipModel
            ->where('id', $membershipId)
            ->where('tenant_id', $tenantId)
            ->where('club_id', $clubId)
            ->first();
        if (! $membership) {
            return service('apiResponseService')->notFound();
        }

        $ok = $this->membershipModel->update((int) $membershipId, [
            'status'     => 'left',
            'left_at'    => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $ok
            ? service('apiResponseService')->updated($this->membershipModel->find($membershipId), 'Đã hủy membership.')
            : service('apiResponseService')->error('Không thể hủy membership.');
    }

    public function inviteMembership(int $clubId)
    {
        return $this->upsertMembership($clubId, 'pending', 'Mời/vào nhóm membership.');
    }

    public function approveMembership(int $clubId, int $membershipId)
    {
        return $this->changeMembershipStatus($clubId, $membershipId, 'active', [
            'joined_at'   => date('Y-m-d H:i:s'),
            'verified_at' => date('Y-m-d H:i:s'),
            'verified_by' => $this->request->api_user_id ?? user_id() ?? 0,
        ]);
    }

    public function rejectMembership(int $clubId, int $membershipId)
    {
        return $this->changeMembershipStatus($clubId, $membershipId, 'left', [
            'left_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function suspendMembership(int $clubId, int $membershipId)
    {
        return $this->changeMembershipStatus($clubId, $membershipId, 'suspended', []);
    }

    public function membershipHistory(int $clubId)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }
        if (! $this->clubModel->findForTenant($clubId, $tenantId)) {
            return service('apiResponseService')->notFound();
        }

        $playerId = (int) ($this->request->getGet('player_id') ?? 0);
        $query = $this->db->table('player_club_memberships pcm')
            ->select('pcm.*, p.full_name AS player_name, p.player_code, p.phone AS player_phone, u.full_name AS actor_name')
            ->join('players p', 'p.id = pcm.player_id', 'left')
            ->join('users u', 'u.id = pcm.verified_by', 'left')
            ->where('pcm.tenant_id', $tenantId)
            ->where('pcm.club_id', $clubId)
            ->orderBy('pcm.updated_at', 'DESC');

        if ($playerId > 0) {
            $query->where('pcm.player_id', $playerId);
        }

        return service('apiResponseService')->success($query->get()->getResult());
    }

    private function upsertMembership(int $clubId, string $status, string $note = ''): object
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }
        if (! $this->clubModel->findForTenant($clubId, $tenantId)) {
            return service('apiResponseService')->notFound();
        }

        $data = $this->payloadMembership($clubId, $tenantId);
        if (empty($data['player_id'])) {
            return service('apiResponseService')->validationError(['player_id' => 'player_id là bắt buộc']);
        }
        $player = $this->playerModel->findForTenant((int) $data['player_id'], $tenantId);
        if (! $player) {
            return service('apiResponseService')->validationError(['player_id' => 'Vận động viên không thuộc tenant.']);
        }

        $this->db->transStart();
        $existing = $this->membershipModel
            ->where('tenant_id', $tenantId)
            ->where('club_id', $clubId)
            ->where('player_id', $data['player_id'])
            ->first();

        $payload = [
            'tenant_id'  => $tenantId,
            'club_id'    => $clubId,
            'player_id'  => $data['player_id'],
            'role'       => $data['role'],
            'status'     => $status,
            'source'     => $data['source'],
            'is_primary' => $data['is_primary'],
            'metadata'   => json_encode(['invited_by' => $this->request->api_user_id ?? user_id() ?? 0], JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (! $existing) {
            $payload['joined_at'] = date('Y-m-d H:i:s');
            $payload['created_by'] = (int) ($this->request->api_user_id ?? user_id() ?? 0);
            $this->membershipModel->insert($payload);
        } else {
            $this->membershipModel->update((int) $existing->id, $payload);
        }

        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            return service('apiResponseService')->error('Không thể cập nhật membership.');
        }

        return service('apiResponseService')->updated(null, $existing ? 'Đã cập nhật membership.' : 'Đã mời thành viên.');
    }

    private function changeMembershipStatus(int $clubId, int $membershipId, string $status, array $extra): object
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        $membership = $this->membershipModel
            ->where('id', $membershipId)
            ->where('tenant_id', $tenantId)
            ->where('club_id', $clubId)
            ->first();
        if (! $membership) {
            return service('apiResponseService')->notFound();
        }

        $payload = array_merge([
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ], $extra);

        $ok = $this->membershipModel->update($membershipId, $payload);
        return $ok ? service('apiResponseService')->updated($this->membershipModel->find($membershipId), 'Đã cập nhật trạng thái membership.') : service('apiResponseService')->error('Không thể thay đổi trạng thái membership.');
    }

    private function resolveTenantId(): ?int
    {
        $tenantId = $this->request->getGet('tenant_id') ?? $this->request->api_tenant_id ?? current_tenant_id();
        if (! $tenantId) {
            return null;
        }

        $tenantId = (int) $tenantId;
        $tokenTenant = (int) ($this->request->api_tenant_id ?? 0);
        if ($tokenTenant > 0 && $tenantId !== $tokenTenant) {
            return null;
        }

        return $tenantId;
    }

    private function resolveTenantIdWithFallback(): ?int
    {
        $tenantId = $this->request->getPost('tenant_id') ?? $this->request->getGet('tenant_id') ?? $this->request->api_tenant_id ?? current_tenant_id();
        if (! $tenantId) {
            return null;
        }

        $tenantId = (int) $tenantId;
        $tokenTenant = (int) ($this->request->api_tenant_id ?? 0);
        if ($tokenTenant > 0 && $tenantId !== $tokenTenant) {
            return null;
        }

        return $tenantId;
    }

    private function countBuilder($builder): int
    {
        $clone = clone $builder;
        return (int) $clone->countAllResults();
    }

    private function exportCsv(array $rows, array $headers, callable $mapRow, string $filename): object
    {
        $fh = fopen('php://temp', 'w+');
        fputcsv($fh, $headers, ';');
        foreach ($rows as $row) {
            fputcsv($fh, (array) $mapRow($row), ';');
        }
        rewind($fh);
        $csv = stream_get_contents($fh) ?: '';
        fclose($fh);

        return service('response')
            ->setStatusCode(200)
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename=' . $filename . '-' . date('YmdHis') . '.csv')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    private function payload(): array
    {
        $raw = $this->request->getJSON(true);
        if (! is_array($raw)) {
            $raw = [];
        } else {
            $raw = (array) $raw;
        }

        return [
            'name_vi'      => trim((string) ($this->request->getPost('name_vi') ?: ($raw['name_vi'] ?? ''))),
            'name_en'      => trim((string) ($this->request->getPost('name_en') ?: ($raw['name_en'] ?? ''))),
            'logo'         => trim((string) ($this->request->getPost('logo') ?: ($raw['logo'] ?? ''))),
            'description_vi'=> trim((string) ($this->request->getPost('description_vi') ?: ($raw['description_vi'] ?? ''))),
            'description_en'=> trim((string) ($this->request->getPost('description_en') ?: ($raw['description_en'] ?? ''))),
            'owner_player_id' => $this->request->getPost('owner_player_id') ?: ($raw['owner_player_id'] ?? null),
            'status'       => $this->request->getPost('status') ?: ($raw['status'] ?? 'active'),
        ];
    }

    private function payloadMembership(int $clubId, int $tenantId): array
    {
        $raw = $this->request->getJSON(true);
        if (! is_array($raw)) {
            $raw = [];
        }
        $playerId = (int) ($this->request->getPost('player_id') ?: ($raw['player_id'] ?? 0));
        $role = (string) ($this->request->getPost('role') ?: ($raw['role'] ?? 'member'));
        $source = (string) ($this->request->getPost('source') ?: ($raw['source'] ?? 'manual'));
        $isPrimary = (int) ($this->request->getPost('is_primary') ?: ($raw['is_primary'] ?? 0));
        return [
            'player_id'  => $playerId,
            'role'       => in_array($role, ['member', 'coach', 'captain', 'manager', 'owner'], true) ? $role : 'member',
            'source'     => $source ?: 'manual',
            'is_primary' => $isPrimary > 0 ? 1 : 0,
        ];
    }
}
