<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class VenueOperationsController extends BaseController
{
    public function index()
    {
        $tenantId = (int) (current_tenant_id() ?: session('tenant_id'));
        if ($tenantId <= 0) {
            return redirect()->to('/admin/tenants/select')->with('warning', 'Vui lòng chọn sân/đơn vị vận hành trước.');
        }

        $branchId = (int) $this->request->getGet('branch_id') ?: null;
        $date = (string) $this->request->getGet('date');
        $service = service('venueOperationsService');
        return $this->render('admin/venue_operations/index', [
            'pageTitle' => 'Venue & Club Control Room',
            'pageDescription' => 'Trung tâm vận hành sân, chi nhánh, sân đấu và câu lạc bộ',
            ...$service->overview($tenantId),
            'controlRoom' => $service->controlRoom($tenantId, $branchId, $date),
        ]);
    }

    public function data()
    {
        $tenantId = (int) (current_tenant_id() ?: session('tenant_id'));
        if ($tenantId <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'Chưa chọn đơn vị vận hành.']);
        }
        return $this->response->setJSON([
            'success' => true,
            'data' => service('venueOperationsService')->controlRoom(
                $tenantId,
                (int) $this->request->getGet('branch_id') ?: null,
                (string) $this->request->getGet('date')
            ),
        ]);
    }
}
