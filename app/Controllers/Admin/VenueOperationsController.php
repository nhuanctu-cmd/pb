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

        return $this->render('admin/venue_operations/index', [
            'pageTitle' => 'Venue & Club Control Room',
            'pageDescription' => 'Trung tâm vận hành sân, chi nhánh, sân đấu và câu lạc bộ',
            ...service('venueOperationsService')->overview($tenantId),
        ]);
    }
}
