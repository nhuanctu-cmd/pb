<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class GrowthController extends BaseController
{
    private $service;
    public function __construct() { $this->service=service('growthService'); }
    public function index() { $tenantId=(int)current_tenant_id(); return $this->render('admin/growth/index',['pageTitle'=>'Growth & CRM','promotions'=>$tenantId?$this->service->promotions($tenantId):[],'referrals'=>$tenantId?$this->service->referrals($tenantId):[],'reviews'=>$tenantId?$this->service->reviews($tenantId):[]]); }
    public function storePromotion() { return $this->message($this->service->createPromotion($this->request->getPost(),(int)current_tenant_id(),(int)user_id())); }
    public function qualifyReferral(int $id) { return $this->message($this->service->qualifyReferral($id,(int)current_tenant_id())); }
    public function rewardReferral(int $id) { return $this->message($this->service->rewardReferral($id,(int)current_tenant_id(),(int)user_id())); }
    public function reviewStatus(int $id) { return $this->message($this->service->setReviewStatus($id,(string)$this->request->getPost('status'),(int)current_tenant_id())); }
    private function message(array $result) { return redirect()->back()->with($result['success']?'success':'error',$result['message']??'Đã xử lý.'); }
}
