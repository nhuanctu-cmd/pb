<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\CourtModel;
use App\Models\CourtTypeModel;
use App\Services\PricingService;

class PricingRulesController extends BaseController
{
    protected PricingService $pricingService;
    protected BranchModel $branchModel;
    protected CourtModel $courtModel;
    protected CourtTypeModel $courtTypeModel;

    public function __construct()
    {
        $this->pricingService = new PricingService();
        $this->branchModel = model(BranchModel::class);
        $this->courtModel = model(CourtModel::class);
        $this->courtTypeModel = model(CourtTypeModel::class);
    }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $filters = [
            'branch_id' => $this->request->getGet('branch_id'),
            'status' => $this->request->getGet('status'),
            'court_type_id' => $this->request->getGet('court_type_id'),
            'court_id' => $this->request->getGet('court_id'),
        ];

        return $this->render('admin/pricing_rules/index', [
            'pageTitle' => 'Dynamic Pricing',
            'pageDescription' => 'Quản lý rule giá động theo chi nhánh, loại sân, sân, khung giờ và hội viên.',
            'rules' => $this->pricingService->getAllRules($tenantId, $filters),
            'branches' => $this->branchModel->getByTenant($tenantId),
            'courtTypes' => $this->courtTypeModel->where('tenant_id', $tenantId)->where('deleted_at', null)->findAll(),
            'courts' => $this->courtModel->getByTenant($tenantId),
            'players' => model('App\Models\PlayerModel')->where('tenant_id', $tenantId)->where('deleted_at', null)->findAll(),
            'filters' => $filters,
            'logs' => $this->pricingService->getPriceLogs($tenantId, $filters['branch_id'] ? (int) $filters['branch_id'] : null, 10),
        ]);
    }

    public function create()
    {
        return $this->form('create');
    }

    public function store()
    {
        $tenantId = (int) current_tenant_id();
        $ruleId = $this->pricingService->createRule($this->ruleData($tenantId), $this->conditionData($tenantId));

        if ($ruleId) {
            return redirect()->to('/admin/pricing-rules')->with('success', 'Đã tạo rule giá động.');
        }

        return redirect()->back()->withInput()->with('error', 'Không tạo được rule giá.');
    }

    public function edit(int $id)
    {
        $rule = $this->pricingService->getRuleById($id);
        if (! $rule) {
            return redirect()->to('/admin/pricing-rules')->with('error', 'Không tìm thấy rule.');
        }

        return $this->form('edit', $rule, $this->pricingService->getConditions($id));
    }

    public function update(int $id)
    {
        $tenantId = (int) current_tenant_id();
        if ($this->pricingService->updateRule($id, $this->ruleData($tenantId), $this->conditionData($tenantId))) {
            return redirect()->to('/admin/pricing-rules')->with('success', 'Đã cập nhật rule giá.');
        }

        return redirect()->back()->withInput()->with('error', 'Không cập nhật được rule giá.');
    }

    public function toggle(int $id)
    {
        $rule = $this->pricingService->getRuleById($id);
        if (! $rule) {
            return redirect()->back()->with('error', 'Không tìm thấy rule.');
        }

        $this->pricingService->updateRule($id, [
            'tenant_id' => $rule->tenant_id,
            'branch_id' => $rule->branch_id,
            'court_type_id' => $rule->court_type_id,
            'court_id' => $rule->court_id,
            'code' => $rule->code,
            'name_vi' => $rule->name_vi,
            'name_en' => $rule->name_en,
            'priority' => $rule->priority,
            'price_type' => $rule->price_type,
            'price_amount' => $rule->price_amount,
            'member_price_amount' => $rule->member_price_amount,
            'start_date' => $rule->start_date,
            'end_date' => $rule->end_date,
            'start_time' => $rule->start_time,
            'end_time' => $rule->end_time,
            'day_of_week' => $rule->day_of_week,
            'is_holiday' => $rule->is_holiday,
            'status' => $rule->status === 'active' ? 'inactive' : 'active',
            'updated_by' => session('user_id'),
        ], $this->pricingService->getConditions($id));

        return redirect()->back()->with('success', 'Đã đổi trạng thái rule.');
    }

    public function delete(int $id)
    {
        $this->pricingService->deleteRule($id);
        return redirect()->to('/admin/pricing-rules')->with('success', 'Đã xóa rule giá.');
    }

    public function test()
    {
        $tenantId = (int) current_tenant_id();
        $result = null;
        $input = [
            'branch_id' => $this->request->getPost('branch_id') ?: $this->request->getGet('branch_id'),
            'court_id' => $this->request->getPost('court_id') ?: $this->request->getGet('court_id'),
            'date' => $this->request->getPost('date') ?: date('Y-m-d'),
            'start_time' => $this->request->getPost('start_time') ?: '18:00',
            'end_time' => $this->request->getPost('end_time') ?: '19:00',
            'player_id' => $this->request->getPost('player_id') ?: null,
        ];

        if ($this->request->getMethod() === 'POST' && $input['branch_id'] && $input['court_id']) {
            $result = $this->pricingService->getPrice(
                $tenantId,
                (int) $input['branch_id'],
                (int) $input['court_id'],
                $input['date'],
                $input['start_time'],
                $input['end_time'],
                $input['player_id'] ? (int) $input['player_id'] : null
            );
        }

        return $this->render('admin/pricing_rules/test', [
            'pageTitle' => 'Test giá sân',
            'branches' => $this->branchModel->getByTenant($tenantId),
            'courts' => $this->courtModel->getByTenant($tenantId),
            'players' => model('App\Models\PlayerModel')->where('tenant_id', $tenantId)->where('deleted_at', null)->findAll(),
            'input' => $input,
            'result' => $result,
        ]);
    }

    private function form(string $mode, $rule = null, array $conditions = [])
    {
        $tenantId = (int) current_tenant_id();

        return $this->render('admin/pricing_rules/form', [
            'pageTitle' => $mode === 'create' ? 'Thêm rule giá' : 'Sửa rule giá',
            'mode' => $mode,
            'rule' => $rule,
            'conditions' => $conditions,
            'branches' => $this->branchModel->getByTenant($tenantId),
            'courtTypes' => $this->courtTypeModel->where('tenant_id', $tenantId)->where('deleted_at', null)->findAll(),
            'courts' => $this->courtModel->getByTenant($tenantId),
        ]);
    }

    private function ruleData(int $tenantId): array
    {
        return [
            'tenant_id' => $tenantId,
            'branch_id' => $this->request->getPost('branch_id'),
            'court_type_id' => $this->request->getPost('court_type_id'),
            'court_id' => $this->request->getPost('court_id'),
            'code' => $this->request->getPost('code'),
            'name_vi' => $this->request->getPost('name_vi'),
            'name_en' => $this->request->getPost('name_en'),
            'priority' => $this->request->getPost('priority') ?: 10,
            'price_type' => $this->request->getPost('price_type') ?: 'hourly',
            'price_amount' => $this->request->getPost('price_amount') ?: 0,
            'member_price_amount' => $this->request->getPost('member_price_amount'),
            'start_date' => $this->request->getPost('start_date'),
            'end_date' => $this->request->getPost('end_date'),
            'start_time' => $this->request->getPost('start_time'),
            'end_time' => $this->request->getPost('end_time'),
            'day_of_week' => $this->request->getPost('day_of_week') ? implode(',', (array) $this->request->getPost('day_of_week')) : null,
            'is_holiday' => $this->request->getPost('is_holiday') ? 1 : 0,
            'status' => $this->request->getPost('status') ?: 'active',
            'created_by' => session('user_id'),
            'updated_by' => session('user_id'),
        ];
    }

    private function conditionData(int $tenantId): array
    {
        $conditions = [];
        foreach (['branch', 'court_type', 'court', 'weekday', 'time_range', 'holiday'] as $type) {
            $value = $this->request->getPost('condition_' . $type);
            if ($value === null || $value === '') {
                continue;
            }

            $conditions[] = [
                'tenant_id' => $tenantId,
                'condition_type' => $type,
                'operator' => in_array($type, ['weekday'], true) ? 'in' : 'equals',
                'value' => is_array($value) ? implode(',', $value) : $value,
                'value_to' => $type === 'time_range' ? $this->request->getPost('condition_time_range_to') : null,
            ];
        }

        return $conditions;
    }
}
