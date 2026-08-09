<?php

namespace App\Services;

use App\Models\TenantModel;
use App\Models\BranchModel;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\UserRoleModel;

class TenantService
{
    protected TenantModel $tenantModel;
    protected BranchModel $branchModel;
    protected UserModel $userModel;
    protected RoleModel $roleModel;
    protected UserRoleModel $userRoleModel;

    public function __construct()
    {
        $this->tenantModel   = new TenantModel();
        $this->branchModel   = new BranchModel();
        $this->userModel     = new UserModel();
        $this->roleModel     = new RoleModel();
        $this->userRoleModel = new UserRoleModel();
    }

    public function getAll(array $filters = [], int $perPage = 20)
    {
        $query = $this->tenantModel->where('deleted_at', null);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->groupStart()
                  ->like('name', $filters['search'])
                  ->orLike('code', $filters['search'])
                  ->orLike('email', $filters['search'])
                  ->groupEnd();
        }

        return $query->paginate($perPage);
    }

    public function getById(int $id)
    {
        return $this->tenantModel->find($id);
    }

    public function getByCode(string $code)
    {
        return $this->tenantModel->getByCode($code);
    }

    public function create(array $data): ?int
    {
        $this->tenantModel->db->transStart();

        $tenantId = $this->tenantModel->insert($data);
        if (!$tenantId) {
            $this->tenantModel->db->transRollback();
            return null;
        }

        // Create default roles for tenant
        $defaultRoles = [
            ['name' => 'Owner', 'slug' => 'owner'],
            ['name' => 'Branch Manager', 'slug' => 'branch-manager'],
            ['name' => 'Staff', 'slug' => 'staff'],
            ['name' => 'Referee', 'slug' => 'referee'],
            ['name' => 'Player', 'slug' => 'player'],
        ];

        foreach ($defaultRoles as $role) {
            $this->roleModel->insert([
                'tenant_id' => $tenantId,
                'name'      => $role['name'],
                'slug'      => $role['slug'],
                'is_system' => 1,
                'status'    => 'active',
            ]);
        }

        $this->tenantModel->db->transComplete();

        if ($this->tenantModel->db->transStatus() === false) {
            $this->tenantModel->db->transRollback();
            return null;
        }

        return $tenantId;
    }

    public function update(int $id, array $data): bool
    {
        return $this->tenantModel->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->tenantModel->delete($id);
    }

    public function getBranches(int $tenantId)
    {
        return $this->branchModel->getByTenant($tenantId);
    }

    public function getUsers(int $tenantId)
    {
        return $this->userModel->getByTenant($tenantId);
    }

    public function getActiveTenants()
    {
        return $this->tenantModel->getActive();
    }
}
