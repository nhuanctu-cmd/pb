<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class DataQualityController extends BaseController
{
    public function index()
    {
        $tenantId = (int) current_tenant_id();
        return $this->render('admin/data_quality/index', [
            'pageTitle' => 'Data Quality',
            'report' => service('dataQualityService')->report($tenantId),
        ]);
    }
}
