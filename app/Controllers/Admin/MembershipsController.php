<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\MembershipService;
use App\Services\PlayerService;

class MembershipsController extends BaseController
{
    protected MembershipService $membershipService;
    protected PlayerService $playerService;

    public function __construct()
    {
        $this->membershipService = new MembershipService();
        $this->playerService     = new PlayerService();
    }

    public function index()
    {
        $tenantId = current_tenant_id();
        $filters  = $this->request->getGet();

        $this->viewData['pageTitle']   = lang('App.memberships');
        $this->viewData['memberships'] = $this->membershipService->getMemberships($tenantId, $filters);
        $this->viewData['filters']     = $filters;
        $this->viewData['pager']       = model(\App\Models\MembershipModel::class)->pager;

        return $this->render('admin/memberships/index', $this->viewData);
    }

    public function create()
    {
        $tenantId = current_tenant_id();
        $this->viewData['pageTitle'] = lang('App.create_membership');
        $this->viewData['packages']  = $this->membershipService->getAllPackages($tenantId);
        $this->viewData['players']   = $this->playerService->getPlayers($tenantId, ['status' => 'active']);

        return $this->render('admin/memberships/form', $this->viewData);
    }

    public function store()
    {
        $tenantId = current_tenant_id();

        $rules = [
            'player_id'  => 'required|integer',
            'package_id' => 'required|integer',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $playerId  = (int) $this->request->getPost('player_id');
        $packageId = (int) $this->request->getPost('package_id');

        $membershipId = $this->membershipService->buyPackage($playerId, $packageId, $tenantId, user_id());
        if (!$membershipId) {
            return redirect()->back()->withInput()->with('error', lang('App.error'));
        }

        return redirect()->to('/admin/memberships')->with('success', lang('App.membership_created'));
    }

    public function cancel(int $id)
    {
        if ($this->membershipService->cancel($id, (int) current_tenant_id())) {
            return redirect()->to('/admin/memberships')->with('success', lang('App.membership_cancelled'));
        }

        return redirect()->back()->with('error', lang('App.error'));
    }

    public function renewals()
    {
        $days = max(1, min(365, (int) ($this->request->getGet('days') ?: 30)));
        $status = (string) ($this->request->getGet('status') ?: 'active');
        $search = trim((string) ($this->request->getGet('q') ?: ''));
        $packageId = (int) ($this->request->getGet('package_id') ?: 0);
        $historyMembershipId = (int) ($this->request->getGet('history_membership_id') ?: 0);
        $this->viewData['pageTitle'] = 'Gia hạn hội viên';
        $this->viewData['days'] = $days;
        $this->viewData['statuses'] = [
            'active' => 'Đang còn hiệu lực',
            'expired' => 'Đã hết hạn',
            '' => 'Tất cả',
        ];
        $this->viewData['packages'] = $this->membershipService->getPackages((int) current_tenant_id());
        $this->viewData['statusFilter'] = $status === 'active' ? 'active' : ($status === 'expired' ? 'expired' : '');
        $this->viewData['search'] = $search;
        $this->viewData['packageFilter'] = $packageId > 0 ? $packageId : 0;
        $this->viewData['renewals'] = $this->membershipService->getRenewalCandidatesFiltered(
            (int) current_tenant_id(),
            $days,
            $packageId > 0 ? $packageId : null,
            $this->viewData['statusFilter'],
            $search
        );
        $this->viewData['historyMembershipId'] = $historyMembershipId;
        $this->viewData['reminderTemplates'] = $this->membershipService->reminderTemplateDefaults();
        $this->viewData['reminderHistory'] = $this->membershipService->getRenewalHistory(
            (int) current_tenant_id(),
            $historyMembershipId > 0 ? $historyMembershipId : null,
            50
        );
        return $this->render('admin/memberships/renewals', $this->viewData);
    }

    public function exportRenewals()
    {
        $days = max(1, min(365, (int) ($this->request->getGet('days') ?: 30)));
        $status = (string) ($this->request->getGet('status') ?: 'active');
        $search = trim((string) ($this->request->getGet('q') ?: ''));
        $packageId = (int) ($this->request->getGet('package_id') ?: 0);
        $tenantId = (int) current_tenant_id();
        $statusFilter = $status === 'active' ? 'active' : ($status === 'expired' ? 'expired' : '');

        $rows = $this->membershipService->getRenewalCandidatesFiltered(
            $tenantId,
            $days,
            $packageId > 0 ? $packageId : null,
            $statusFilter,
            $search
        );

        $lines = [];
        $lines[] = '"Khach hang","Ma","SDT","Goi","Het han","Con lai","Gia"';
        foreach ($rows as $row) {
            $line = [
                (string) ($row->full_name ?? ''),
                (string) ($row->player_code ?? ''),
                (string) ($row->phone ?? ''),
                (string) ($row->package_name_vi ?? ($row->package_name_en ?? '')),
                (string) ($row->end_date ?? ''),
                (string) ($row->remaining_days ?? 0),
                (string) ($row->price ?? 0),
            ];
            $lines[] = implode(',', array_map(fn ($value) => '"' . str_replace('"', '""', (string) $value) . '"', $line));
        }

        return $this->response->download(
            'renewals-' . date('Ymd') . '.csv',
            implode("\r\n", $lines)
        );
    }

    public function renew(int $id)
    {
        $packageId = (int) ($this->request->getPost('package_id') ?: 0);
        $newId = $this->membershipService->renew($id, (int) current_tenant_id(), $packageId > 0 ? $packageId : null, user_id());
        return redirect()->to('/admin/memberships/renewals')->with($newId ? 'success' : 'error', $newId ? 'Đã gia hạn hội viên.' : 'Không thể gia hạn hội viên.');
    }

    public function sendReminder(int $id)
    {
        $tenantId = (int) current_tenant_id();
        $channel = (string) ($this->request->getPost('channel') ?: 'sms');
        $testMode = (int) ($this->request->getPost('test_mode') ?: 0) === 1;
        $recipient = trim((string) ($this->request->getPost('recipient') ?: ''));
        $messageTemplate = trim((string) ($this->request->getPost('message_template') ?: ''));
        $keepFilter = $this->normalizeRenewalFilterPayload();

        $result = $this->membershipService->sendReminder(
            $id,
            $tenantId,
            $channel,
            $testMode,
            $recipient !== '' ? $recipient : null,
            user_id(),
            $messageTemplate !== '' ? $messageTemplate : null
        );

        $redirect = '/admin/memberships/renewals?' . http_build_query(array_filter($keepFilter, static fn($value) => $value !== ''));
        return redirect()->to($redirect)->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function bulkReminders()
    {
        $tenantId = (int) current_tenant_id();
        $membershipIds = (array) $this->request->getPost('membership_ids');
        $channel = (string) ($this->request->getPost('reminder_channel') ?: 'sms');
        $testMode = (int) ($this->request->getPost('test_mode') ?: 0) === 1;
        $recipient = trim((string) ($this->request->getPost('recipient') ?: ''));
        $messageTemplate = trim((string) ($this->request->getPost('message_template') ?: ''));

        if (empty($membershipIds)) {
            $keepFilter = $this->normalizeRenewalFilterPayload();
            $redirect = '/admin/memberships/renewals?' . http_build_query(array_filter($keepFilter, static fn($value) => $value !== ''));
            return redirect()->to($redirect)->with('error', 'Chưa chọn hội viên để gửi nhắc nhở.');
        }

        $result = $this->membershipService->sendBulkReminders(
            $membershipIds,
            $tenantId,
            $channel,
            $testMode,
            $recipient !== '' ? $recipient : null,
            user_id(),
            $messageTemplate !== '' ? $messageTemplate : null
        );

        $keepFilter = $this->normalizeRenewalFilterPayload();
        $redirect = '/admin/memberships/renewals?' . http_build_query(array_filter($keepFilter, static fn($value) => $value !== ''));
        if ($result['failed'] === 0) {
            return redirect()->to($redirect)->with(
                'success',
                'Đã gửi nhắc nhở: ' . $result['success'] . '/' . $result['requested'] . ' hồ sơ.'
            );
        }

        return redirect()->to($redirect)->with(
            'error',
            'Gửi nhắc nhở một phần: ' . $result['success'] . '/' . $result['requested'] . ' thành công.'
        );
    }

    protected function normalizeRenewalFilterPayload(): array
    {
        return [
            'days' => max(1, min(365, (int) (
                $this->request->getPost('filter_days')
                ?: $this->request->getPost('days')
                ?: $this->request->getGet('days')
                ?: 30
            ))),
            'status' => (string) (
                $this->request->getPost('filter_status')
                ?: $this->request->getPost('status')
                ?: $this->request->getGet('status')
                ?: 'active'
            ),
            'package_id' => (int) (
                $this->request->getPost('filter_package_id')
                ?: $this->request->getPost('package_id')
                ?: $this->request->getGet('package_id')
                ?: 0
            ),
            'q' => trim((string) (
                $this->request->getPost('filter_q')
                ?: $this->request->getPost('q')
                ?: $this->request->getGet('q')
                ?: ''
            )),
            'history_membership_id' => (int) (
                $this->request->getPost('filter_history_membership_id')
                ?: $this->request->getPost('history_membership_id')
                ?: $this->request->getGet('history_membership_id')
                ?: 0
            ),
        ];
    }

    public function bulkRenew()
    {
        $tenantId = (int) current_tenant_id();
        $ids = (array) $this->request->getPost('membership_ids');
        $packageId = (int) ($this->request->getPost('package_id') ?: 0);
        if (empty($ids)) {
            return redirect()->back()->with('error', 'Chưa chọn hội viên để gia hạn.');
        }

        $result = $this->membershipService->bulkRenew($tenantId, $ids, $packageId > 0 ? $packageId : null);
        if ($result['failed'] > 0) {
            return redirect()->to('/admin/memberships/renewals')
                ->with('error', 'Gia hạn một phần: thành công ' . $result['success'] . ' / ' . $result['requested'] . ' hồ sơ.');
        }
        return redirect()->to('/admin/memberships/renewals')
            ->with('success', 'Gia hạn thành công ' . $result['success'] . ' hồ sơ.');
    }

    public function packages()
    {
        $tenantId = current_tenant_id();
        $this->viewData['pageTitle'] = lang('App.membership_packages');
        $this->viewData['packages']  = $this->membershipService->getAllPackages($tenantId);

        return $this->render('admin/memberships/packages', $this->viewData);
    }

    public function createPackage()
    {
        $this->viewData['pageTitle'] = lang('App.create_package');
        return $this->render('admin/memberships/package_form', $this->viewData);
    }

    public function storePackage()
    {
        $tenantId = current_tenant_id();

        $rules = [
            'name_vi'       => 'required|max_length[255]',
            'duration_days' => 'required|integer|greater_than[0]',
            'price'         => 'required|decimal|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'tenant_id'        => $tenantId,
            'name_vi'          => $this->request->getPost('name_vi'),
            'name_en'          => $this->request->getPost('name_en'),
            'duration_days'    => (int) $this->request->getPost('duration_days'),
            'price'            => (float) $this->request->getPost('price'),
            'discount_percent' => (float) ($this->request->getPost('discount_percent') ?: 0),
            'booking_priority' => (int) ($this->request->getPost('booking_priority') ?: 0),
            'status'           => $this->request->getPost('status') ?: 'active',
            'created_by'       => user_id(),
        ];

        if ($this->membershipService->createPackage($data)) {
            return redirect()->to('/admin/memberships/packages')->with('success', lang('App.package_created'));
        }

        return redirect()->back()->withInput()->with('error', lang('App.error'));
    }

    public function editPackage(int $id)
    {
        $package = $this->membershipService->getPackageById($id, (int) current_tenant_id());
        if (!$package) {
            return redirect()->to('/admin/memberships/packages')->with('error', lang('App.no_data'));
        }

        $this->viewData['pageTitle'] = lang('App.edit_package');
        $this->viewData['package']   = $package;

        return $this->render('admin/memberships/package_form', $this->viewData);
    }

    public function updatePackage(int $id)
    {
        $package = $this->membershipService->getPackageById($id, (int) current_tenant_id());
        if (!$package) {
            return redirect()->to('/admin/memberships/packages')->with('error', lang('App.no_data'));
        }

        $rules = [
            'name_vi'       => 'required|max_length[255]',
            'duration_days' => 'required|integer|greater_than[0]',
            'price'         => 'required|decimal|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name_vi'          => $this->request->getPost('name_vi'),
            'name_en'          => $this->request->getPost('name_en'),
            'duration_days'    => (int) $this->request->getPost('duration_days'),
            'price'            => (float) $this->request->getPost('price'),
            'discount_percent' => (float) ($this->request->getPost('discount_percent') ?: 0),
            'booking_priority' => (int) ($this->request->getPost('booking_priority') ?: 0),
            'status'           => $this->request->getPost('status'),
            'updated_by'       => user_id(),
        ];

        if ($this->membershipService->updatePackage($id, $data, (int) current_tenant_id())) {
            return redirect()->to('/admin/memberships/packages')->with('success', lang('App.package_updated'));
        }

        return redirect()->back()->withInput()->with('error', lang('App.error'));
    }

    public function deletePackage(int $id)
    {
        if ($this->membershipService->deletePackage($id, (int) current_tenant_id())) {
            return redirect()->to('/admin/memberships/packages')->with('success', lang('App.package_deleted'));
        }

        return redirect()->back()->with('error', lang('App.error'));
    }
}
