<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BookingModel;
use App\Models\CustomerModel;
use App\Models\PlayerModel;

class CustomersController extends BaseController
{
    protected CustomerModel $customerModel;
    protected BookingModel $bookingModel;
    protected PlayerModel $playerModel;

    public function __construct()
    {
        $this->customerModel = new CustomerModel();
        $this->bookingModel  = new BookingModel();
        $this->playerModel   = new PlayerModel();
    }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $filters  = $this->collectFilters();

        $customers = $tenantId ? $this->customerModel->searchForTenant($tenantId, $filters) : [];
        $customerTagsMap = [];
        if ($tenantId && $customers) {
            foreach ($customers as $customer) {
                $customerTagsMap[(int) $customer->id] = $this->customerModel->tagRows($tenantId, (int) $customer->id);
            }
        }

        return $this->render('admin/customers/index', [
            'pageTitle'  => 'CRM Khách hàng',
            'customers'  => $customers,
            'pager'      => $tenantId ? $this->customerModel->pager : null,
            'filters'    => $filters,
            'customerTagsMap' => $customerTagsMap,
            'sourceOptions' => $tenantId ? $this->customerModel->sourceList() : ['booking'],
            'stats'      => $tenantId ? $this->customerModel->dashboardStats($tenantId, $filters) : [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
                'merged' => 0,
                'booking_count' => 0,
                'revenue' => 0,
                'new_this_month' => 0,
            ],
            'tags' => $tenantId ? $this->customerModel->availableTags($tenantId) : [],
            'hasCustomers' => (bool) $tenantId,
        ]);
    }

    public function show(int $id)
    {
        $tenantId = (int) current_tenant_id();
        $customer = $tenantId ? $this->customerModel->findForTenant($id, $tenantId) : null;
        if (! $customer) {
            return redirect()->to('/admin/customers')->with('error', lang('App.no_data'));
        }

        if ($this->customerModel->db->tableExists('bookings') && $this->customerModel->db->fieldExists('customer_id', 'bookings')) {
            $bookings = $this->bookingModel
                ->select('bookings.*, GROUP_CONCAT(DISTINCT courts.code ORDER BY courts.code SEPARATOR ", ") AS court_codes')
                ->join('booking_items', 'booking_items.booking_id = bookings.id', 'left')
                ->join('courts', 'courts.id = booking_items.court_id', 'left')
                ->where('bookings.tenant_id', $tenantId)
                ->where('bookings.customer_id', $id)
                ->groupBy('bookings.id')
                ->orderBy('bookings.booking_date', 'DESC')
                ->orderBy('bookings.start_time', 'DESC')
                ->limit(20)
                ->findAll();
        } else {
            $bookings = [];
        }

        $bookingStats = [
            'total' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'no_show' => 0,
            'pending' => 0,
            'paid' => 0,
        ];

        foreach ($bookings as $booking) {
            if (! isset($bookingStats[$booking->status])) $bookingStats[$booking->status] = 0;
            $bookingStats[$booking->status] += 1;
            if (($booking->payment_status ?? '') === 'paid') {
                $bookingStats['paid'] += 1;
            }
            $bookingStats['total'] += 1;
        }

        return $this->render('admin/customers/show', [
            'pageTitle'       => 'Hồ sơ khách hàng',
            'customer'        => $customer,
            'timeline'        => $this->customerModel->timeline($id, $tenantId),
            'bookings'        => $bookings,
            'tags'            => $this->customerModel->tagRows($tenantId, $id),
            'allTags'         => $this->customerModel->availableTags($tenantId),
            'bookingStats'    => $bookingStats,
            'linkedPlayer'    => $customer->player_id ? $this->playerModel->findForTenant((int) $customer->player_id, $tenantId) : null,
            'sourceOptions'   => $this->customerModel->sourceList(),
        ]);
    }

    public function updateStatus(int $id)
    {
        $tenantId = (int) current_tenant_id();
        $customer = $tenantId ? $this->customerModel->findForTenant($id, $tenantId) : null;
        if (! $customer) {
            return redirect()->to('/admin/customers')->with('error', lang('App.no_data'));
        }

        $status = (string) $this->request->getPost('status');
        if (! in_array($status, ['active', 'inactive', 'merged'], true)) {
            return redirect()->back()->with('error', 'Trạng thái không hợp lệ.');
        }

        $this->customerModel->update($id, [
            'status' => $status,
            'updated_by' => (int) user_id(),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->recordTimeline($tenantId, $id, 'status_changed', 'Cập nhật trạng thái khách hàng: ' . $status);

        return redirect()->to('/admin/customers/show/' . $id)->with('success', 'Đã cập nhật trạng thái khách hàng.');
    }

    public function syncTags(int $id)
    {
        $tenantId = (int) current_tenant_id();
        $customer = $tenantId ? $this->customerModel->findForTenant($id, $tenantId) : null;
        if (! $customer) {
            return redirect()->to('/admin/customers')->with('error', lang('App.no_data'));
        }

        $tagIds = [];
        $selected = (array) $this->request->getPost('tag_ids');
        foreach ($selected as $tagId) {
            $tagId = (int) $tagId;
            if ($tagId > 0) {
                $tagIds[] = $tagId;
            }
        }

        $createTag = trim((string) $this->request->getPost('new_tag'));
        if ($createTag !== '') {
            $newTag = $this->customerModel->ensureTag($tenantId, $createTag);
            if ($newTag) {
                $tagIds[] = (int) $newTag->id;
            }
        }

        $tagIds = array_values(array_unique($tagIds));
        $this->customerModel->syncTags($tenantId, $id, $tagIds);

        $this->recordTimeline($tenantId, $id, 'tagged', 'Cập nhật nhãn khách hàng', ['tag_count' => count($tagIds)]);
        return redirect()->to('/admin/customers/show/' . $id)->with('success', 'Đã cập nhật nhãn cho khách hàng.');
    }

    public function unlinkTag(int $id, int $tagId)
    {
        $tenantId = (int) current_tenant_id();
        $customer = $tenantId ? $this->customerModel->findForTenant($id, $tenantId) : null;
        if (! $customer) {
            return redirect()->to('/admin/customers')->with('error', lang('App.no_data'));
        }

        $this->customerModel->unlinkTag($tenantId, $id, $tagId);
        $this->recordTimeline($tenantId, $id, 'tag_removed', 'Xoá nhãn khách hàng', ['tag_id' => $tagId]);
        return redirect()->to('/admin/customers/show/' . $id)->with('success', 'Đã xoá nhãn.');
    }

    public function addNote(int $id)
    {
        $tenantId = (int) current_tenant_id();
        $customer = $tenantId ? $this->customerModel->findForTenant($id, $tenantId) : null;
        if (! $customer) {
            return redirect()->to('/admin/customers')->with('error', lang('App.no_data'));
        }

        $note = trim((string) $this->request->getPost('note'));
        if ($note === '') {
            return redirect()->back()->with('error', 'Nội dung ghi chú trống.');
        }

        $this->recordTimeline($tenantId, $id, 'note', 'Ghi chú CRM', ['note' => $note]);
        return redirect()->to('/admin/customers/show/' . $id)->with('success', 'Đã lưu ghi chú CRM.');
    }

    public function quickCreateBooking(int $id)
    {
        $tenantId = (int) current_tenant_id();
        $customer = $tenantId ? $this->customerModel->findForTenant($id, $tenantId) : null;
        if (! $customer) {
            return redirect()->to('/admin/customers')->with('error', lang('App.no_data'));
        }

        $query = [
            'customer_name'  => $customer->full_name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
        ];

        return redirect()->to('/admin/bookings/create?' . http_build_query($query));
    }

    protected function collectFilters(): array
    {
        $filters = [
            'search'     => trim((string) $this->request->getGet('search')),
            'status'     => (string) $this->request->getGet('status'),
            'source'     => (string) $this->request->getGet('source'),
            'tag_id'     => (string) $this->request->getGet('tag_id'),
            'has_player' => (string) $this->request->getGet('has_player'),
        ];

        if ($filters['tag_id'] !== '') {
            $filters['tag_id'] = (int) $filters['tag_id'];
            if ($filters['tag_id'] <= 0) {
                $filters['tag_id'] = '';
            }
        }

        if (isset($filters['search']) && $filters['search'] === '') {
            $filters['search'] = '';
        }
        if ($filters['status'] === 'all') $filters['status'] = '';
        if ($filters['source'] === 'all') $filters['source'] = '';
        if ($filters['has_player'] === 'all') $filters['has_player'] = '';

        return array_filter($filters, fn ($value) => $value !== null);
    }

    protected function recordTimeline(int $tenantId, int $customerId, string $eventType, string $title, array $payload = []): void
    {
        if (! $this->customerModel->db->tableExists('customer_timeline_events')) return;

        $this->customerModel->db->table('customer_timeline_events')->insert([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'event_type' => $eventType,
            'title' => $title,
            'description' => $payload['note'] ?? null,
            'source_type' => 'admin',
            'source_id' => null,
            'actor_id' => user_id(),
            'payload' => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
