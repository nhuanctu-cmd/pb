<?php

namespace App\Services;

use App\Models\PlayerModel;
use App\Models\PromotionModel;
use App\Models\PromotionRedemptionModel;
use App\Models\ReferralCodeModel;
use App\Models\ReferralModel;
use App\Models\ReviewModel;
use App\Services\NotificationService;

class GrowthService
{
    private PromotionModel $promotionModel; private PromotionRedemptionModel $redemptionModel; private ReferralCodeModel $referralCodeModel; private ReferralModel $referralModel; private ReviewModel $reviewModel; private PlayerModel $playerModel;
    public function __construct() { $this->promotionModel=new PromotionModel(); $this->redemptionModel=new PromotionRedemptionModel(); $this->referralCodeModel=new ReferralCodeModel(); $this->referralModel=new ReferralModel(); $this->reviewModel=new ReviewModel(); $this->playerModel=new PlayerModel(); }
    public function promotions(int $tenantId): array { return $this->promotionModel->getByTenant($tenantId); }
    public function referrals(int $tenantId): array { return $this->referralModel->getByTenant($tenantId); }
    public function reviews(int $tenantId,array $filters=[]): array { return $this->reviewModel->getByTenant($tenantId,$filters); }

    public function createPromotion(array $data,int $tenantId,?int $userId=null): array
    {
        $code=strtoupper(trim((string)($data['code']??''))); $type=(string)($data['discount_type']??'percent'); $value=(float)($data['discount_value']??0);
        if(!$tenantId||$code===''||!preg_match('/^[A-Z0-9_-]{3,50}$/',$code)||!in_array($type,['percent','fixed'],true)||$value<=0||($type==='percent'&&$value>100)||!self::isValidDateTimeRange((string)($data['starts_at']??''),(string)($data['ends_at']??''))) return ['success'=>false,'message'=>'Promotion không hợp lệ.'];
        if($this->promotionModel->findCode($code,$tenantId)) return ['success'=>false,'message'=>'Mã promotion đã tồn tại.'];
        $id=$this->promotionModel->insert(['tenant_id'=>$tenantId,'code'=>$code,'name'=>trim((string)($data['name']??$code)),'discount_type'=>$type,'discount_value'=>round($value,2),'max_discount'=>($data['max_discount']??'')!==''?round((float)$data['max_discount'],2):null,'min_order_amount'=>max(0,round((float)($data['min_order_amount']??0),2)),'usage_limit'=>($data['usage_limit']??'')!==''?max(1,(int)$data['usage_limit']):null,'per_customer_limit'=>max(1,(int)($data['per_customer_limit']??1)),'starts_at'=>$data['starts_at'],'ends_at'=>$data['ends_at'],'status'=>'active','created_by'=>$userId,'updated_by'=>$userId]);
        return $id?['success'=>true,'message'=>'Đã tạo promotion.']:['success'=>false,'message'=>'Không thể tạo promotion.'];
    }

    public function quote(string $code,float $orderAmount,int $tenantId,?int $playerId=null): array
    {
        $promotion=$this->promotionModel->findCode($code,$tenantId); $now=date('Y-m-d H:i:s');
        if(!$promotion||$promotion->status!=='active'||($promotion->starts_at&&$now<$promotion->starts_at)||($promotion->ends_at&&$now>$promotion->ends_at)||$orderAmount<(float)$promotion->min_order_amount) return ['success'=>false,'message'=>'Mã promotion không áp dụng được.'];
        if($promotion->usage_limit!==null&&(int)$this->redemptionModel->where('promotion_id',$promotion->id)->where('tenant_id',$tenantId)->countAllResults()>=(int)$promotion->usage_limit) return ['success'=>false,'message'=>'Promotion đã hết lượt.'];
        if($playerId&&$this->redemptionModel->countForPlayer((int)$promotion->id,$playerId,$tenantId)>=(int)$promotion->per_customer_limit) return ['success'=>false,'message'=>'Bạn đã dùng hết lượt promotion.'];
        $discount=$promotion->discount_type==='percent'?$orderAmount*((float)$promotion->discount_value/100):(float)$promotion->discount_value; if($promotion->max_discount!==null)$discount=min($discount,(float)$promotion->max_discount); $discount=min(max(0,$discount),max(0,$orderAmount));
        return ['success'=>true,'promotion'=>$promotion,'discount_amount'=>round($discount,2),'net_amount'=>round($orderAmount-$discount,2)];
    }

    public function redeem(string $code,float $orderAmount,int $tenantId,?int $playerId=null,?int $bookingId=null,?string $idempotencyKey=null): array
    {
        if($idempotencyKey&&($existing=$this->redemptionModel->findIdempotent($idempotencyKey,$tenantId))) return ['success'=>true,'duplicate'=>true,'discount_amount'=>(float)$existing->discount_amount];
        $db=\Config\Database::connect(); $db->transStart(); $promotion=$this->promotionModel->findCode($code,$tenantId); if(!$promotion){$db->transRollback();return ['success'=>false,'message'=>'Promotion không tồn tại.'];} $locked=$this->promotionModel->findForUpdate((int)$promotion->id,$tenantId); $quote=$locked?$this->quote((string)$locked->code,$orderAmount,$tenantId,$playerId):['success'=>false,'message'=>'Promotion không áp dụng được.']; if(empty($quote['success'])){$db->transRollback();return $quote;}
        $id=$this->redemptionModel->insert(['tenant_id'=>$tenantId,'promotion_id'=>$locked->id,'player_id'=>$playerId,'booking_id'=>$bookingId,'discount_amount'=>$quote['discount_amount'],'idempotency_key'=>$idempotencyKey,'created_at'=>date('Y-m-d H:i:s')]); $db->transComplete(); if(!$id||!$db->transStatus())return ['success'=>false,'message'=>'Không thể ghi nhận promotion.']; return ['success'=>true,'discount_amount'=>$quote['discount_amount'],'net_amount'=>$quote['net_amount'],'message'=>'Đã áp dụng promotion.'];
    }

    public function ensureReferralCode(int $playerId,int $tenantId): array
    {
        if(!$this->playerModel->findForTenant($playerId,$tenantId))return ['success'=>false,'message'=>'Player không hợp lệ.']; $existing=$this->referralCodeModel->findForPlayer($playerId,$tenantId); if($existing)return ['success'=>true,'code'=>$existing->code,'referral_code'=>$existing];
        $code='PB'.str_pad((string)$playerId,5,'0',STR_PAD_LEFT).strtoupper(bin2hex(random_bytes(3))); $id=$this->referralCodeModel->insert(['tenant_id'=>$tenantId,'player_id'=>$playerId,'code'=>$code,'reward_amount'=>50000,'max_uses'=>null,'status'=>'active']); return $id?['success'=>true,'code'=>$code,'referral_code'=>$this->referralCodeModel->find($id)]:['success'=>false,'message'=>'Không tạo được referral code.'];
    }

    public function applyReferral(string $code,int $referredPlayerId,int $tenantId): array
    {
        $referee=$this->playerModel->findForTenant($referredPlayerId,$tenantId); $referralCode=$this->referralCodeModel->findCode($code,$tenantId); if(!$referee||!$referralCode||(int)$referralCode->player_id===$referredPlayerId)return ['success'=>false,'message'=>'Referral code không hợp lệ.']; if($referralCode->max_uses!==null&&(int)$referralCode->uses_count>=(int)$referralCode->max_uses)return ['success'=>false,'message'=>'Referral code đã hết lượt.']; if($this->referralModel->where('tenant_id',$tenantId)->where('referred_player_id',$referredPlayerId)->first())return ['success'=>true,'duplicate'=>true,'message'=>'Referral đã được áp dụng.']; $id=$this->referralModel->insert(['tenant_id'=>$tenantId,'referrer_player_id'=>$referralCode->player_id,'referred_player_id'=>$referredPlayerId,'code'=>$referralCode->code,'reward_amount'=>$referralCode->reward_amount,'status'=>'pending']); if(!$id)return ['success'=>false,'message'=>'Không thể áp dụng referral.']; $this->referralCodeModel->update($referralCode->id,['uses_count'=>(int)$referralCode->uses_count+1]); return ['success'=>true,'message'=>'Đã ghi nhận referral.'];
    }

    public function qualifyReferral(int $id,int $tenantId): array
    {
        $db=\Config\Database::connect();$db->transStart();$ref=$this->referralModel->findForUpdate($id,$tenantId);if(!$ref||$ref->status!=='pending'){$db->transRollback();return ['success'=>false,'message'=>'Referral không ở trạng thái chờ.'];}$ok=$this->referralModel->update($id,['status'=>'qualified','qualified_at'=>date('Y-m-d H:i:s')]);$db->transComplete();return $ok&&$db->transStatus()?['success'=>true,'message'=>'Đã qualify referral.']:['success'=>false,'message'=>'Không thể qualify referral.'];
    }

    public function rewardReferral(int $id,int $tenantId,?int $userId=null): array
    {
        $db=\Config\Database::connect();$db->transStart();$ref=$this->referralModel->findForUpdate($id,$tenantId);if(!$ref||$ref->status!=='qualified'){$db->transRollback();return ['success'=>false,'message'=>'Referral chưa đủ điều kiện thưởng.'];} $wallet=new WalletService(); if(!$wallet->topup((int)$ref->referrer_player_id,$tenantId,(float)$ref->reward_amount,'Referral reward','referral',$id,$userId)){$db->transRollback();return ['success'=>false,'message'=>'Không thể cộng thưởng referral.'];}$this->referralModel->update($id,['status'=>'rewarded','rewarded_at'=>date('Y-m-d H:i:s')]);$db->transComplete();if(!$db->transStatus())return ['success'=>false,'message'=>'Không thể trả thưởng referral.'];$referrer=$this->playerModel->findForTenant((int)$ref->referrer_player_id,$tenantId);if(!empty($referrer->user_id))(new NotificationService())->notifyUser((int)$referrer->user_id,'referral_rewarded',['full_name'=>$referrer->full_name,'reward_amount'=>$ref->reward_amount],$tenantId,'/player/growth');return ['success'=>true,'message'=>'Đã trả thưởng referral.'];
    }

    public function createReview(array $data,int $tenantId,int $playerId): array
    {
        $type=(string)($data['entity_type']??'');$entityId=(int)($data['entity_id']??0);$rating=(int)($data['rating']??0); if(!$this->playerModel->findForTenant($playerId,$tenantId)||!in_array($type,['booking','court','coach','coaching_session','competition'],true)||$entityId<=0||$rating<1||$rating>5||!$this->entityBelongsToTenant($type,$entityId,$tenantId))return ['success'=>false,'message'=>'Review không hợp lệ.']; $existing=$this->reviewModel->findForPlayer($playerId,$type,$entityId,$tenantId);$payload=['tenant_id'=>$tenantId,'player_id'=>$playerId,'entity_type'=>$type,'entity_id'=>$entityId,'rating'=>$rating,'title'=>trim((string)($data['title']??''))?:null,'body'=>trim((string)($data['body']??''))?:null,'status'=>'pending'];$ok=$existing?$this->reviewModel->update($existing->id,$payload):$this->reviewModel->insert($payload);return $ok?['success'=>true,'message'=>'Review đã gửi, chờ duyệt.']:['success'=>false,'message'=>'Không thể gửi review.'];
    }

    public function setReviewStatus(int $id,string $status,int $tenantId): array { if(!in_array($status,['pending','published','hidden'],true))return ['success'=>false,'message'=>'Trạng thái review không hợp lệ.'];$ok=$this->reviewModel->where('tenant_id',$tenantId)->where('id',$id)->set(['status'=>$status])->update();return $ok?['success'=>true,'message'=>'Đã cập nhật review.']:['success'=>false,'message'=>'Không cập nhật được review.']; }
    public static function calculateDiscount(float $amount,string $type,float $value,?float $max=null): float { $d=$type==='percent'?$amount*$value/100:$value;if($max!==null)$d=min($d,$max);return round(min(max(0,$d),max(0,$amount)),2); }
    private static function isValidDateTimeRange(string $start,string $end): bool { $a=strtotime($start);$b=strtotime($end);return $a!==false&&$b!==false&&$a<$b; }
    private function entityBelongsToTenant(string $type,int $id,int $tenantId): bool { $tables=['booking'=>'bookings','court'=>'courts','coach'=>'coaches','coaching_session'=>'coaching_sessions','competition'=>'competition_events'];$table=$tables[$type]??null; if(!$table)return false;return (bool)\Config\Database::connect()->query("SELECT id FROM {$table} WHERE id=? AND tenant_id=? LIMIT 1",[$id,$tenantId])->getRow(); }
}
