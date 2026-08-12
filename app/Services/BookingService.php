<?php

namespace App\Services;

use App\Models\BookingModel;
use App\Models\BookingItemModel;
use App\Models\BookingQrCodeModel;
use App\Models\BookingLogModel;
use App\Models\CourtModel;
use App\Models\PlayerModel;

class BookingService
{
    protected BookingModel $bookingModel;
    protected BookingItemModel $bookingItemModel;
    protected BookingQrCodeModel $bookingQrCodeModel;
    protected BookingLogModel $bookingLogModel;
    protected CourtModel $courtModel;
    protected CustomerService $customerService;
    protected PricingService $pricingService;
    protected BookingStateMachine $stateMachine;

    public function __construct()
    {
        $this->bookingModel      = model(BookingModel::class);
        $this->bookingItemModel  = model(BookingItemModel::class);
        $this->bookingQrCodeModel = model(BookingQrCodeModel::class);
        $this->bookingLogModel   = model(BookingLogModel::class);
        $this->courtModel        = model(CourtModel::class);
        $this->customerService   = new CustomerService();
        $this->pricingService    = new PricingService();
        $this->stateMachine      = new BookingStateMachine();
    }

    /**
     * Create a new booking with items
     */
    public function createBooking(array $data): array
    {
        $tenantId = (int) ($data['tenant_id'] ?? 0);
        $branchId = (int) ($data['branch_id'] ?? 0);

        if (! $tenantId || ! $branchId || empty($data['items']) || ! is_array($data['items'])) {
            return [
                'success' => false,
                'message' => lang('App.invalid_data'),
            ];
        }

        foreach (['booking_date', 'start_time', 'end_time', 'customer_name', 'customer_phone'] as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => lang('App.invalid_data')];
            }
        }

        $db = \Config\Database::connect();
        $db->transStart();
        if (!empty($data['player_id']) && !(new PlayerModel())->findPlayerByUser((int) $data['player_id'], $tenantId)) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Player không thuộc tenant hiện tại.'];
        }
        $customerId = null;
        if ($this->customerService->available()) {
            $customer = $this->customerService->resolveForBooking($tenantId, $data, (int) ($data['created_by'] ?? 0));
            if (! $customer['success']) {
                $db->transRollback();
                return $customer;
            }
            $customerId = $customer['customer_id'] ?? null;
        }
        $requestedIntervals = [];

        // Lock each court row before the final availability check. Concurrent
        // requests for the same court are therefore serialized by MySQL.
        foreach ($data['items'] as $item) {
            if (empty($item['court_id']) || empty($item['start_time']) || empty($item['end_time'])) {
                $db->transRollback();
                return ['success' => false, 'message' => lang('App.invalid_data')];
            }

            if (! $this->isValidTimeRange((string) $item['start_time'], (string) $item['end_time'])) {
                $db->transRollback();
                return ['success' => false, 'message' => lang('App.invalid_data')];
            }

            $courtId = (int) ($item['court_id'] ?? 0);
            foreach ($requestedIntervals[$courtId] ?? [] as $interval) {
                if ($item['start_time'] < $interval[1] && $item['end_time'] > $interval[0]) {
                    $db->transRollback();
                    return ['success' => false, 'message' => lang('App.court_not_available', [$courtId])];
                }
            }
            $requestedIntervals[$courtId][] = [(string) $item['start_time'], (string) $item['end_time']];
            $courtRow = $db->query('SELECT id FROM courts WHERE id = ? FOR UPDATE', [$courtId])->getRow();
            if (! $courtRow) {
                $db->transRollback();
                return ['success' => false, 'message' => 'Không tìm thấy sân.'];
            }

            $bookable = $this->validateCourtBookable((int) $item['court_id'], (int) $tenantId, (int) $branchId, $data['booking_date'], $item['start_time'], $item['end_time']);
            if (! $bookable['success']) {
                $db->transRollback();
                return $bookable;
            }

            if (!$this->checkCourtAvailable($item['court_id'], $data['booking_date'], $item['start_time'], $item['end_time'])) {
                $db->transRollback();
                return [
                    'success' => false,
                    'message' => lang('App.court_not_available', [$item['court_id']]),
                ];
            }
        }

        $durationMinutes = $this->calculateDuration($data['start_time'], $data['end_time']);

        // Generate booking code
        $bookingCode = $this->bookingModel->generateBookingCode($tenantId, $branchId);

        // Determine deposit amount (from settings or default 30%)
        $depositPercent = (float) ($data['deposit_percent'] ?? 30);
        $depositAmount = 0;

        // Set expiry for pending bookings
        $expiryMinutes = (int) ($data['booking_expiry_minutes'] ?? 15);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $expiryMinutes . ' minutes'));

        // Create booking
        $bookingId = $this->bookingModel->insert([
            'tenant_id'       => $tenantId,
            'branch_id'       => $branchId,
            'player_id'       => $data['player_id'] ?? null,
            'customer_id'     => $customerId,
            'customer_name'   => $data['customer_name'],
            'customer_phone'  => $data['customer_phone'],
            'customer_email'  => $data['customer_email'] ?? null,
            'booking_code'    => $bookingCode,
            'booking_date'    => $data['booking_date'],
            'start_time'      => $data['start_time'],
            'end_time'        => $data['end_time'],
            'duration_minutes' => $durationMinutes,
            'total_amount'    => 0,
            'deposit_amount'  => $depositAmount,
            'paid_amount'     => 0,
            'status'          => $data['status'] ?? 'pending',
            'payment_status'  => 'unpaid',
            'source'          => $data['source'] ?? 'admin',
            'note'            => $data['note'] ?? null,
            'is_recurring'    => (int) ($data['is_recurring'] ?? 0),
            'recurring_pattern' => $data['recurring_pattern'] ?? null,
            'recurring_parent_id' => $data['recurring_parent_id'] ?? null,
            'expires_at'      => $expiresAt,
            'hold_until'      => $data['hold_until'] ?? null,
            'is_hold'         => (int) ($data['is_hold'] ?? 0),
            'timeout_minutes' => (int) ($data['timeout_minutes'] ?? $expiryMinutes),
            'auto_release_at' => $data['auto_release_at'] ?? null,
            'created_by'      => $data['created_by'] ?? null,
        ]);

        if (!$bookingId) {
            $db->transRollback();
            return [
                'success' => false,
                'message' => lang('App.create_booking_failed'),
            ];
        }

        // Create booking items with dynamic pricing. Manual prices are ignored unless explicitly allowed.
        $totalAmount = 0.0;
        $priceBreakdown = [];
        $selectedRuleIds = [];

        foreach ($data['items'] as $item) {
            $priceResult = $this->pricingService->getPrice(
                (int) $tenantId,
                (int) $branchId,
                (int) $item['court_id'],
                $data['booking_date'],
                $item['start_time'],
                $item['end_time'],
                $data['player_id'] ?? null,
                (int) $bookingId
            );

            $dynamicPrice = (float) ($priceResult['final_price'] ?? 0);
            $manualAllowed = (bool) ($data['allow_manual_price'] ?? false);
            $finalPrice = $dynamicPrice > 0 ? $dynamicPrice : ($manualAllowed ? (float) ($item['price'] ?? 0) : 0);
            $totalAmount += $finalPrice;

            if (! empty($priceResult['selected_rule'])) {
                $selectedRuleIds[] = (int) $priceResult['selected_rule']->id;
            }

            $priceBreakdown[] = [
                'court_id' => (int) $item['court_id'],
                'base_price' => (float) ($priceResult['base_price'] ?? 0),
                'final_price' => $finalPrice,
                'log_id' => $priceResult['log_id'] ?? null,
                'matched_rule_ids' => $priceResult['matched_rule_ids'] ?? [],
                'breakdown' => $priceResult['breakdown'] ?? [],
            ];

            $this->bookingItemModel->insert([
                'tenant_id'      => $tenantId,
                'booking_id'     => $bookingId,
                'court_id'       => $item['court_id'],
                'start_time'     => $item['start_time'],
                'end_time'       => $item['end_time'],
                'price'          => $finalPrice,
                'base_price'     => (float) ($priceResult['base_price'] ?? 0),
                'dynamic_price'  => $dynamicPrice,
                'pricing_detail' => json_encode($priceResult['breakdown'] ?? [], JSON_UNESCAPED_UNICODE),
                'status'         => 'active',
            ]);
        }

        $discountAmount = 0.0;
        if (!empty($data['promotion_code'])) {
            $promotionPlayer = !empty($data['player_id']) ? (new PlayerModel())->findPlayerByUser((int) $data['player_id'], $tenantId) : null;
            $promotionResult = service('growthService')->redeem((string) $data['promotion_code'], $totalAmount, $tenantId, $promotionPlayer ? (int) $promotionPlayer->id : null, (int) $bookingId, $data['promotion_idempotency_key'] ?? ('booking-' . $bookingId));
            if (empty($promotionResult['success'])) {
                $db->transRollback();
                return $promotionResult;
            }
            $discountAmount = (float) ($promotionResult['discount_amount'] ?? 0);
        }
        $netAmount = round(max(0, $totalAmount - $discountAmount), 2);
        $depositAmount = $netAmount * ($depositPercent / 100);
        $this->bookingModel->update($bookingId, [
            'total_amount'    => $netAmount,
            'discount_amount' => $discountAmount,
            'net_amount'      => $netAmount,
            'deposit_amount'  => $depositAmount,
            'pricing_rule_id' => $selectedRuleIds[0] ?? null,
            'price_breakdown' => json_encode($priceBreakdown, JSON_UNESCAPED_UNICODE),
        ]);

        if ($customerId && ! $this->customerService->recordBooking($customerId, $tenantId, (int) $bookingId, ['total_amount' => $netAmount], (int) ($data['created_by'] ?? 0))) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Không thể ghi timeline khách hàng.'];
        }

        // Generate QR code
        $this->generateQrCode($tenantId, $bookingId);

        // Log
        $this->bookingLogModel->addLog(
            $tenantId, $bookingId, 'created',
            null, $data['status'] ?? 'pending',
            lang('App.booking_created'),
            $data['created_by'] ?? null
        );

        $db->transComplete();

        if ($db->transStatus() === false) {
            return [
                'success' => false,
                'message' => lang('App.create_booking_failed'),
            ];
        }

        $booking = $this->bookingModel->find($bookingId);

        // Delivery is asynchronous and must never make a committed booking fail.
        try {
            service('webhookService')->dispatch($tenantId, 'booking.created', [
                'id' => (int) $bookingId,
                'tenant_id' => $tenantId,
                'booking_code' => (string) ($booking->booking_code ?? ''),
                'booking_date' => (string) ($booking->booking_date ?? ''),
                'start_time' => (string) ($booking->start_time ?? ''),
                'end_time' => (string) ($booking->end_time ?? ''),
                'status' => (string) ($booking->status ?? ''),
                'occurred_at' => date('c'),
            ]);
        } catch (\Throwable $exception) {
            log_message('error', 'booking.created webhook dispatch failed: ' . $exception->getMessage());
        }

        return [
            'success'  => true,
            'message'  => lang('App.booking_created_success'),
            'booking'  => $booking,
        ];
    }

    /**
     * Calculate booking price based on items
     */
    public function calculateBookingPrice(array $data): float
    {
        $total = 0;

        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                if (isset($item['price'])) {
                    $total += (float) $item['price'];
                }
            }
        }

        return $total;
    }

    /**
     * Hold/pending a booking
     */
    public function holdBooking(array $data): array
    {
        $timeoutMinutes = max(1, (int) ($data['timeout_minutes'] ?? 5));
        $data['status'] = 'hold';
        $data['is_hold'] = 1;
        $data['timeout_minutes'] = $timeoutMinutes;
        $data['hold_until'] = date('Y-m-d H:i:s', time() + ($timeoutMinutes * 60));
        $data['auto_release_at'] = $data['hold_until'];
        return $this->createBooking($data);
    }

    /**
     * Confirm payment for a booking
     */
    public function confirmPayment(int $bookingId, float $amount, ?int $userId = null, ?int $tenantId = null): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'message' => lang('App.invalid_data')];
        }

        $db = \Config\Database::connect();
        $db->transStart();
        $booking = $this->findBookingForContext($bookingId, $tenantId, true);
        if (! $booking) {
            $db->transRollback();
            return ['success' => false, 'message' => lang('App.booking_not_found')];
        }

        $oldStatus = $booking->status;
        $oldPaymentStatus = $booking->payment_status;
        $paidAmount = round((float) $booking->paid_amount + $amount, 2);
        $totalAmount = round((float) $booking->total_amount, 2);

        if ($paidAmount > $totalAmount + 0.01) {
            $db->transRollback();
            return ['success' => false, 'message' => lang('App.invalid_data')];
        }

        $paymentStatus = $paidAmount >= $totalAmount ? 'paid' : 'partial';
        $newStatus = $booking->status;
        if ($paymentStatus === 'paid' && $booking->status !== 'paid') {
            $newStatus = 'paid';
        }

        try {
            $this->stateMachine->assertTransition($oldStatus, $newStatus);
        } catch (\InvalidArgumentException) {
            $db->transRollback();
            return ['success' => false, 'message' => lang('App.invalid_data')];
        }

        $this->bookingModel->update($bookingId, [
            'paid_amount'    => $paidAmount,
            'payment_status' => $paymentStatus,
            'status'         => $newStatus,
            'updated_by'     => $userId,
        ]);

        $this->bookingLogModel->addLog(
            $booking->tenant_id, $bookingId, 'payment_confirmed',
            $oldStatus, $newStatus,
            lang('App.payment_confirmed_amount', [$amount]), $userId
        );

        if ($paymentStatus === 'paid' && $oldPaymentStatus !== 'paid') {
            $this->bookingLogModel->addLog(
                $booking->tenant_id, $bookingId, 'payment_status_changed',
                $oldPaymentStatus, 'paid', lang('App.fully_paid'), $userId
            );
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            return ['success' => false, 'message' => lang('App.invalid_data')];
        }

        return [
            'success' => true,
            'message' => lang('App.payment_confirmed'),
            'booking' => $this->findBookingForContext($bookingId, $tenantId),
        ];
    }

    /**
     * Cancel a booking
     */
    public function cancelBooking(int $bookingId, ?string $reason = null, ?int $userId = null, ?int $tenantId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $booking = $this->findBookingForContext($bookingId, $tenantId, true);
        if (! $booking) {
            $db->transRollback();
            return ['success' => false, 'message' => lang('App.booking_not_found')];
        }

        $oldStatus = $booking->status;
        $newStatus = (float) $booking->paid_amount > 0 ? 'refunded' : 'cancelled';
        try {
            $this->stateMachine->assertTransition($oldStatus, $newStatus);
        } catch (\InvalidArgumentException) {
            $db->transRollback();
            return ['success' => false, 'message' => lang('App.cannot_cancel_booking')];
        }

        $this->bookingModel->update($bookingId, [
            'status'           => $newStatus,
            'payment_status'   => $newStatus === 'refunded' ? 'refunded' : 'unpaid',
            'cancelled_at'     => date('Y-m-d H:i:s'),
            'cancelled_reason' => $reason,
            'is_hold'          => 0,
            'updated_by'       => $userId,
        ]);
        $this->bookingItemModel->where('booking_id', $bookingId)
            ->where('status', 'active')
            ->set(['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')])
            ->update();
        $this->bookingQrCodeModel->invalidateByBooking($bookingId);
        $this->bookingLogModel->addLog(
            $booking->tenant_id, $bookingId,
            $newStatus === 'refunded' ? 'refunded' : 'cancelled',
            $oldStatus, $newStatus, $reason ?? lang('App.booking_cancelled'), $userId
        );

        $db->transComplete();
        if ($db->transStatus() === false) {
            return ['success' => false, 'message' => lang('App.cannot_cancel_booking')];
        }

        return [
            'success' => true,
            'message' => lang('App.booking_cancelled_success'),
            'booking' => $this->findBookingForContext($bookingId, $tenantId),
        ];
    }

    /**
     * Reschedule a booking
     */
    public function rescheduleBooking(int $bookingId, array $newData, ?int $userId = null, ?int $tenantId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $booking = $this->findBookingForContext($bookingId, $tenantId, true);
        if (! $booking) {
            $db->transRollback();
            return ['success' => false, 'message' => lang('App.booking_not_found')];
        }

        if (in_array($booking->status, ['completed', 'cancelled', 'refunded', 'no_show'])) {
            $db->transRollback();
            return ['success' => false, 'message' => lang('App.cannot_reschedule_booking')];
        }

        $newDate = $newData['booking_date'] ?? $booking->booking_date;
        $newStartTime = $newData['start_time'] ?? $booking->start_time;
        $newEndTime = $newData['end_time'] ?? $booking->end_time;

        // Lock the courts before checking availability so two reschedules
        // cannot reserve the same interval concurrently.
        $items = $this->bookingItemModel->getByBooking($bookingId);
        foreach ($items as $item) {
            $db->query('SELECT id FROM courts WHERE id = ? FOR UPDATE', [(int) $item->court_id]);
            if (!$this->checkCourtAvailable($item->court_id, $newDate, $newStartTime, $newEndTime, $bookingId)) {
                $court = $this->courtModel->find($item->court_id);
                $db->transRollback();
                return [
                    'success' => false,
                    'message' => lang('App.court_not_available', [$court ? $court->code : $item->court_id]),
                ];
            }
        }

        $oldDate = $booking->booking_date;
        $oldStart = $booking->start_time;
        $oldEnd = $booking->end_time;

        $durationMinutes = $this->calculateDuration($newStartTime, $newEndTime);

        $this->bookingModel->update($bookingId, [
            'booking_date'    => $newDate,
            'start_time'      => $newStartTime,
            'end_time'        => $newEndTime,
            'duration_minutes' => $durationMinutes,
            'updated_by'      => $userId,
        ]);

        // Update items times
        foreach ($items as $item) {
            $this->bookingItemModel->update($item->id, [
                'start_time' => $newStartTime,
                'end_time'   => $newEndTime,
            ]);
        }

        // Regenerate QR code
        $this->bookingQrCodeModel->invalidateByBooking($bookingId);
        $this->generateQrCode($booking->tenant_id, $bookingId);

        // Log
        $message = lang('App.rescheduled_from_to', [
            $oldDate . ' ' . $oldStart . '-' . $oldEnd,
            $newDate . ' ' . $newStartTime . '-' . $newEndTime,
        ]);

        $this->bookingLogModel->addLog(
            $booking->tenant_id, $bookingId,
            'rescheduled',
            $booking->status, $booking->status,
            $message,
            $userId
        );

        $db->transComplete();
        if ($db->transStatus() === false) {
            return ['success' => false, 'message' => lang('App.cannot_reschedule_booking')];
        }

        return [
            'success' => true,
            'message' => lang('App.booking_rescheduled_success'),
            'booking' => $this->findBookingForContext($bookingId, $tenantId),
        ];
    }

    /**
     * Check-in using QR token
     */
    public function checkInByQr(string $token, ?int $userId = null, ?int $tenantId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        // Claim the active QR row under a lock. A second concurrent scan will
        // observe the used status after the first transaction commits.
        $qrSql = 'SELECT * FROM booking_qr_codes WHERE qr_token = ? AND status = ?';
        $qrParams = [$token, 'active'];
        if ($tenantId !== null) {
            $qrSql .= ' AND tenant_id = ?';
            $qrParams[] = $tenantId;
        }
        $qrSql .= ' LIMIT 1 FOR UPDATE';
        $qrCode = $db->query($qrSql, $qrParams)->getRow();
        if (! $qrCode) {
            $db->transRollback();
            return ['success' => false, 'message' => lang('App.qr_invalid_or_expired')];
        }

        if ($qrCode->expired_at && strtotime($qrCode->expired_at) < time()) {
            $db->table('booking_qr_codes')->where('id', $qrCode->id)->update(['status' => 'expired']);
            $db->transComplete();
            return ['success' => false, 'message' => lang('App.qr_expired')];
        }

        $booking = $this->findBookingForContext((int) $qrCode->booking_id, $tenantId, true);
        if (! $booking) {
            $db->transRollback();
            return ['success' => false, 'message' => lang('App.booking_not_found')];
        }

        if ($booking->status === 'checked_in') {
            $db->transRollback();
            return ['success' => true, 'message' => lang('App.already_checked_in'), 'booking' => $booking];
        }

        try {
            $this->stateMachine->assertTransition($booking->status, 'checked_in');
        } catch (\InvalidArgumentException) {
            $db->transRollback();
            return ['success' => false, 'message' => lang('App.cannot_check_in_status')];
        }

        $bookingId = (int) $booking->id;
        $now = date('Y-m-d H:i:s');
        $this->bookingModel->update($bookingId, [
            'status' => 'checked_in', 'checked_in_at' => $now, 'updated_by' => $userId,
        ]);
        $claimed = $db->table('booking_qr_codes')
            ->where('id', $qrCode->id)
            ->where('status', 'active')
            ->update(['status' => 'used', 'used_at' => $now]);
        if ($claimed !== true || $db->affectedRows() < 1) {
            $db->transRollback();
            return ['success' => false, 'message' => lang('App.qr_invalid_or_expired')];
        }

        $this->bookingLogModel->addLog(
            $booking->tenant_id, $bookingId, 'checked_in', $booking->status,
            'checked_in', lang('App.checked_in_via_qr'), $userId
        );
        $db->transComplete();
        if ($db->transStatus() === false) {
            return ['success' => false, 'message' => lang('App.check_in_failed')];
        }

        $this->occupyCourts($bookingId);
        return [
            'success' => true,
            'message' => lang('App.check_in_success'),
            'booking' => $this->findBookingForContext($bookingId, $tenantId),
        ];
    }

    /**
     * Manual/admin check-in uses the same lifecycle rules as QR check-in.
     */
    public function checkIn(int $bookingId, ?int $userId = null, ?int $tenantId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $booking = $this->findBookingForContext($bookingId, $tenantId, true);
        if (! $booking) {
            $db->transRollback();
            return ['success' => false, 'message' => lang('App.booking_not_found')];
        }
        if ($booking->status === 'checked_in') {
            $db->transRollback();
            return ['success' => true, 'message' => lang('App.already_checked_in'), 'booking' => $booking];
        }
        try {
            $this->stateMachine->assertTransition($booking->status, 'checked_in');
        } catch (\InvalidArgumentException) {
            $db->transRollback();
            return ['success' => false, 'message' => lang('App.cannot_check_in_status')];
        }
        $this->bookingModel->update($bookingId, [
            'status' => 'checked_in', 'checked_in_at' => date('Y-m-d H:i:s'), 'updated_by' => $userId,
        ]);
        $this->bookingLogModel->addLog(
            $booking->tenant_id, $bookingId, 'checked_in', $booking->status,
            'checked_in', lang('App.checked_in_via_admin'), $userId
        );
        $db->transComplete();
        if ($db->transStatus() === false) {
            return ['success' => false, 'message' => lang('App.check_in_failed')];
        }
        $this->occupyCourts($bookingId);
        return [
            'success' => true,
            'message' => lang('App.check_in_success'),
            'booking' => $this->findBookingForContext($bookingId, $tenantId),
        ];
    }

    protected function occupyCourts(int $bookingId): void
    {
        $items = $this->bookingItemModel->getByBooking($bookingId);
        foreach ($items as $item) {
            $this->courtModel->update($item->court_id, ['status' => 'occupied']);
        }
    }

    protected function findBookingForContext(int $bookingId, ?int $tenantId = null, bool $forUpdate = false)
    {
        if ($forUpdate) {
            return $this->bookingModel->findForUpdate($bookingId, $tenantId);
        }

        return $tenantId === null
            ? $this->bookingModel->find($bookingId)
            : $this->bookingModel->findForTenant($bookingId, $tenantId);
    }

    /**
     * Mark booking as completed
     */
    public function markCompleted(int $bookingId, ?int $userId = null, ?int $tenantId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $booking = $this->findBookingForContext($bookingId, $tenantId, true);
        if (! $booking) {
            $db->transRollback();
            return ['success' => false, 'message' => lang('App.booking_not_found')];
        }

        try {
            $this->stateMachine->assertTransition($booking->status, 'completed');
        } catch (\InvalidArgumentException) {
            $db->transRollback();
            return ['success' => false, 'message' => lang('App.cannot_complete_booking')];
        }

        $oldStatus = $booking->status;

        $this->bookingModel->update($bookingId, [
            'status'       => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'updated_by'   => $userId,
        ]);

        // Release courts
        $items = $this->bookingItemModel->getByBooking($bookingId);
        foreach ($items as $item) {
            $this->courtModel->update($item->court_id, ['status' => 'available']);
        }

        // Log
        $this->bookingLogModel->addLog(
            $booking->tenant_id, $bookingId,
            'completed',
            $oldStatus, 'completed',
            lang('App.booking_completed'),
            $userId
        );

        $db->transComplete();
        if ($db->transStatus() === false) {
            return ['success' => false, 'message' => lang('App.cannot_complete_booking')];
        }

        return [
            'success' => true,
            'message' => lang('App.booking_completed_success'),
            'booking' => $this->findBookingForContext($bookingId, $tenantId),
        ];
    }

    /**
     * Release expired pending bookings
     */
    public function releaseExpiredBookings(): array
    {
        $expiredBookings = $this->bookingModel->getExpiredPending();
        $released = 0;

        foreach ($expiredBookings as $booking) {
            $db = \Config\Database::connect();
            $db->transStart();

            $lockedBooking = $this->bookingModel->findForUpdate((int) $booking->id, (int) $booking->tenant_id);
            if (! $lockedBooking || ! in_array($lockedBooking->status, ['pending', 'hold'], true)) {
                $db->transRollback();
                continue;
            }
            try {
                $this->stateMachine->assertTransition($lockedBooking->status, 'expired');
            } catch (\InvalidArgumentException) {
                $db->transRollback();
                continue;
            }

            $this->bookingModel->update($booking->id, [
                'status'          => 'expired',
                'cancelled_reason' => lang('App.auto_cancelled_expired'),
                'cancelled_at'    => date('Y-m-d H:i:s'),
                'is_hold'         => 0,
            ]);

            $this->bookingItemModel->where('booking_id', $booking->id)
                                   ->where('status', 'active')
                                   ->set(['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')])
                                   ->update();

            $this->bookingLogModel->addLog(
                $booking->tenant_id, $booking->id,
                'auto_cancelled',
                $lockedBooking->status, 'expired',
                lang('App.auto_cancelled_expired'),
                null
            );

            $db->transComplete();

            if ($db->transStatus()) {
                $released++;
            }
        }

        return [
            'success'  => true,
            'released' => $released,
            'message'  => lang('App.released_expired_count', [$released]),
        ];
    }

    /**
     * Check court availability
     */
    public function checkCourtAvailable(int $courtId, string $date, string $startTime, string $endTime, ?int $excludeBookingId = null): bool
    {
        return $this->bookingModel->isCourtAvailable($courtId, $date, $startTime, $endTime, $excludeBookingId);
    }

    protected function validateCourtBookable(int $courtId, int $tenantId, int $branchId, string $date, string $startTime, string $endTime): array
    {
        $courtQuery = $this->courtModel->where('id', $courtId)->where('deleted_at', null);
        if ($tenantId !== null) $courtQuery->where('tenant_id', $tenantId);
        $court = $courtQuery->first();
        if (! $court) {
            return ['success' => false, 'message' => 'Không tìm thấy sân.'];
        }

        if ((int) $court->tenant_id !== $tenantId) {
            return ['success' => false, 'message' => 'Sân không thuộc tenant hiện tại.'];
        }

        if ((int) $court->branch_id !== $branchId) {
            return ['success' => false, 'message' => 'Sân không thuộc chi nhánh đã chọn.'];
        }

        if ($court->status !== 'available') {
            return ['success' => false, 'message' => 'Sân ' . $court->code . ' không ở trạng thái có thể booking.'];
        }

        $dayOfWeek = (int) date('w', strtotime($date));
        $opening = model(\App\Models\BranchOpeningHourModel::class)
            ->where('branch_id', $branchId)
            ->where('day_of_week', $dayOfWeek)
            ->where('deleted_at', null)
            ->first();

        if ($opening) {
            if ((int) $opening->is_closed === 1) {
                return ['success' => false, 'message' => 'Chi nhánh đóng cửa trong ngày đã chọn.'];
            }

            if ($opening->open_time && $opening->close_time && (substr($startTime, 0, 5) < substr($opening->open_time, 0, 5) || substr($endTime, 0, 5) > substr($opening->close_time, 0, 5))) {
                return ['success' => false, 'message' => 'Khung giờ nằm ngoài giờ mở cửa chi nhánh.'];
            }
        }

        $holiday = model(\App\Models\BranchHolidayModel::class)
            ->where('branch_id', $branchId)
            ->where('holiday_date', $date)
            ->where('is_closed', 1)
            ->where('deleted_at', null)
            ->first();

        if ($holiday) {
            return ['success' => false, 'message' => 'Chi nhánh nghỉ lễ: ' . $holiday->name_vi];
        }

        $startAt = $date . ' ' . $startTime;
        $endAt = $date . ' ' . $endTime;
        $maintenance = model(\App\Models\CourtMaintenanceModel::class)
            ->where('court_id', $courtId)
            ->whereIn('status', ['scheduled', 'doing'])
            ->where('deleted_at', null)
            ->groupStart()
                ->where('start_time <', $endAt)
                ->groupStart()
                    ->where('end_time IS NULL', null, false)
                    ->orWhere('end_time >', $startAt)
                ->groupEnd()
            ->groupEnd()
            ->first();

        if ($maintenance) {
            return ['success' => false, 'message' => 'Sân đang có lịch bảo trì trong khung giờ đã chọn.'];
        }

        return ['success' => true];
    }

    /**
     * Generate QR code record for booking
     */
    protected function generateQrCode(int $tenantId, int $bookingId): void
    {
        $token = $this->bookingQrCodeModel->generateQrToken();
        $booking = $this->bookingModel->find($bookingId);

        // Expires at end of booking date or +1 day
        $expiredAt = $booking->booking_date . ' ' . ($booking->end_time ?? '23:59:59');
        if (strtotime($expiredAt) < time()) {
            $expiredAt = date('Y-m-d 23:59:59', strtotime('+1 day'));
        }

        $this->bookingQrCodeModel->insert([
            'tenant_id'  => $tenantId,
            'booking_id' => $bookingId,
            'qr_token'   => $token,
            'qr_path'    => null, // placeholder for QR image path
            'expired_at' => $expiredAt,
            'status'     => 'active',
        ]);
    }

    /**
     * Calculate duration in minutes between two times
     */
    protected function calculateDuration(string $startTime, string $endTime): int
    {
        $start = strtotime($startTime);
        $end = strtotime($endTime);
        if ($end <= $start) {
            $end = strtotime('+1 day', $end);
        }
        return (int) (($end - $start) / 60);
    }

    protected function isValidTimeRange(string $startTime, string $endTime): bool
    {
        $start = strtotime($startTime);
        $end = strtotime($endTime);

        return $start !== false && $end !== false && $end > $start;
    }

    /**
     * Get available time slots for a court on a date
     */
    public function getAvailableSlots(int $courtId, string $date, int $slotDurationMinutes = 60, ?int $tenantId = null): array
    {
        $court = $this->courtModel->find($courtId);
        if (!$court || $court->status === 'inactive' || ($tenantId !== null && (int) $court->tenant_id !== $tenantId)) {
            return [];
        }

        // Default business hours 06:00 - 22:00
        $openTime = '06:00';
        $closeTime = '22:00';

        // Check branch opening hours
        $branchModel = model(\App\Models\BranchOpeningHourModel::class);
        $dayOfWeek = date('w', strtotime($date));
        $hours = $branchModel->where('branch_id', $court->branch_id)
                             ->where('day_of_week', $dayOfWeek)
                             ->first();
        if ($hours) {
            if ($hours->is_closed) {
                return [];
            }
            $openTime = $hours->open_time ?? $openTime;
            $closeTime = $hours->close_time ?? $closeTime;
        }

        // Check holidays
        $holidayModel = model(\App\Models\BranchHolidayModel::class);
        $holiday = $holidayModel->where('branch_id', $court->branch_id)
                               ->where('holiday_date', $date)
                               ->first();
        if ($holiday && $holiday->is_closed) {
            return [];
        }

        // Check maintenance
        $maintenanceModel = model(\App\Models\CourtMaintenanceModel::class);
        $maintenances = $maintenanceModel->where('court_id', $courtId)
                                         ->where('status !=', 'completed')
                                         ->where('start_time <=', $date . ' 23:59:59')
                                         ->groupStart()
                                            ->where('end_time IS NULL', null, false)
                                            ->orWhere('end_time >=', $date . ' 00:00:00')
                                         ->groupEnd()
                                         ->findAll();

        // Get existing bookings for this date
        $bookedItems = $this->bookingItemModel
            ->select('booking_items.start_time, booking_items.end_time')
            ->join('bookings', 'bookings.id = booking_items.booking_id')
            ->where('booking_items.court_id', $courtId)
            ->where('bookings.tenant_id', (int) $court->tenant_id)
            ->where('bookings.booking_date', $date)
            ->where('bookings.deleted_at', null)
            ->whereIn('bookings.status', [
                'draft', 'pending', 'hold', 'reserved',
                'paid', 'checked_in', 'in_progress',
            ])
            ->where('booking_items.status', 'active')
            ->findAll();

        // Generate slots
        $slots = [];
        $current = strtotime($openTime);
        $close = strtotime($closeTime);
        $now = time();
        $today = date('Y-m-d');

        while ($current + $slotDurationMinutes * 60 <= $close) {
            $slotStart = date('H:i:s', $current);
            $slotEnd = date('H:i:s', $current + $slotDurationMinutes * 60);

            // Skip past slots for today
            if ($date === $today && strtotime($slotStart) <= $now) {
                $current += $slotDurationMinutes * 60;
                continue;
            }

            // Check if slot overlaps with existing bookings
            $isBooked = false;
            foreach ($bookedItems as $bi) {
                if ($bi->start_time < $slotEnd && $bi->end_time > $slotStart) {
                    $isBooked = true;
                    break;
                }
            }

            // Check maintenance overlap
            $isMaintenance = false;
            foreach ($maintenances as $m) {
                $mStart = date('H:i:s', strtotime($m->start_time));
                $mEnd = date('H:i:s', strtotime($m->end_time ?? $closeTime));
                if ($mStart < $slotEnd && $mEnd > $slotStart) {
                    $isMaintenance = true;
                    break;
                }
            }

            $slots[] = [
                'start_time'  => $slotStart,
                'end_time'    => $slotEnd,
                'available'   => !$isBooked && !$isMaintenance,
                'is_booked'   => $isBooked,
                'is_maintenance' => $isMaintenance,
            ];

            $current += $slotDurationMinutes * 60;
        }

        return $slots;
    }

    /**
     * Return a booking-oriented availability matrix for one week.
     *
     * The matrix deliberately keeps the slot list per day because opening
     * hours, holidays and maintenance can make the list differ by date.
     */
    public function getWeeklyAvailability(int $branchId, string $weekStart, ?int $tenantId = null, int $slotDurationMinutes = 60): array
    {
        $week = \DateTimeImmutable::createFromFormat('!Y-m-d', $weekStart);
        if (! $week || $week->format('Y-m-d') !== $weekStart) {
            throw new \InvalidArgumentException('Invalid week start date.');
        }

        $week = $week->modify('monday this week');
        $courtsQuery = $this->courtModel
            ->where('branch_id', $branchId)
            ->where('deleted_at', null)
            ->where('status !=', 'inactive');
        if ($tenantId !== null) $courtsQuery->where('tenant_id', $tenantId);
        $courts = $courtsQuery->orderBy('floor', 'ASC')->orderBy('sort_order', 'ASC')->orderBy('code', 'ASC')->findAll();

        // Tenant is already validated by AvailabilityService; keep this
        // fallback filter for direct legacy callers.
        if ($tenantId !== null) $courts = array_values(array_filter($courts, static fn ($court) => (int) $court->tenant_id === $tenantId));

        $courtData = [];
        foreach ($courts as $index => $court) {
            $courtData[] = [
                'id'           => (int) $court->id,
                'code'         => $court->code,
                'name'         => $court->getName(),
                'floor'        => (int) ($court->floor ?? 1),
                'status'       => $court->status,
                'status_label' => $court->status === 'available' ? 'Sẵn sàng' : ($court->status === 'maintenance' ? 'Bảo trì' : 'Đang sử dụng'),
                'coordinates_x'=> (int) ($court->coordinates_x ?: 40 + (($index % 4) * 210)),
                'coordinates_y'=> (int) ($court->coordinates_y ?: 40 + (intdiv($index, 4) * 145)),
                'rotation'     => (int) ($court->rotation ?? 0),
                'color_scheme' => $court->color_scheme ?: 'green',
                'is_bookable'  => $court->status === 'available',
            ];
        }

        $days = [];
        foreach (range(0, 6) as $offset) {
            $date = $week->modify('+' . $offset . ' days')->format('Y-m-d');
            $slotsByKey = [];
            $availabilityByCourt = [];

            foreach ($courts as $court) {
                $courtSlots = $this->getAvailableSlots((int) $court->id, $date, $slotDurationMinutes, $tenantId);
                $availabilityByCourt[(int) $court->id] = [];

                foreach ($courtSlots as $slot) {
                    $key = $slot['start_time'] . '|' . $slot['end_time'];
                    $slotsByKey[$key] = [
                        'start_time' => $slot['start_time'],
                        'end_time'   => $slot['end_time'],
                    ];
                    $availabilityByCourt[(int) $court->id][$key] = [
                        'available'      => (bool) $slot['available'] && $court->status === 'available',
                        'is_booked'      => (bool) $slot['is_booked'],
                        'is_maintenance'=> (bool) $slot['is_maintenance'] || $court->status === 'maintenance',
                    ];
                }
            }

            ksort($slotsByKey);
            $slots = [];
            foreach ($slotsByKey as $key => $slot) {
                $byCourt = [];
                foreach ($courts as $court) {
                    $byCourt[(string) $court->id] = $availabilityByCourt[(int) $court->id][$key] ?? [
                        'available' => false,
                        'is_booked' => false,
                        'is_maintenance' => true,
                    ];
                }

                $slots[] = [
                    'start_time' => $slot['start_time'],
                    'end_time'   => $slot['end_time'],
                    'by_court'   => $byCourt,
                ];
            }

            $dateObject = $week->modify('+' . $offset . ' days');
            $days[] = [
                'date'       => $date,
                'day_number' => (int) $dateObject->format('d'),
                'weekday'    => ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][(int) $dateObject->format('w')],
                'is_today'   => $date === date('Y-m-d'),
                'slots'      => $slots,
            ];
        }

        return [
            'week_start' => $week->format('Y-m-d'),
            'week_end'   => $week->modify('+6 days')->format('Y-m-d'),
            'courts'     => $courtData,
            'days'       => $days,
        ];
    }
}
