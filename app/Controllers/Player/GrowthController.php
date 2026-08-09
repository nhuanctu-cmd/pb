<?php

namespace App\Controllers\Player;

use App\Controllers\BaseController;
use App\Models\PlayerModel;

class GrowthController extends BaseController
{
    private $service;
    public function __construct() { $this->service=service('growthService'); }
    public function index() { $tenantId=(int)session('tenant_id');$playerId=$this->currentPlayerId();$code=$tenantId&&$playerId?$this->service->ensureReferralCode($playerId,$tenantId):['success'=>false];return view('player/growth/index',['referralCode'=>$code['code']??null,'reviews'=>$tenantId?$this->service->reviews($tenantId,['status'=>'published']):[]]); }
    public function applyReferral() { $result=$this->service->applyReferral((string)$this->request->getPost('code'),$this->currentPlayerId(),(int)session('tenant_id'));return redirect()->back()->with($result['success']?'success':'error',$result['message']??'Đã xử lý.'); }
    public function review() { $result=$this->service->createReview($this->request->getPost(),(int)session('tenant_id'),$this->currentPlayerId());return redirect()->back()->with($result['success']?'success':'error',$result['message']??'Đã xử lý.'); }
    private function currentPlayerId(): int { $p=model(PlayerModel::class)->findPlayerByUser((int)session('user_id'),(int)session('tenant_id'));return (int)($p->id??0); }
}
