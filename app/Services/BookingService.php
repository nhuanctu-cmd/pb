<?php

namespace App\Services;

use App\Models\BookingModel;
use App\Models\BookingItemModel;
use App\Models\BookingQrCodeModel;
use App\Models\BookingLogModel;
use App\Models\CourtModel;

class BookingService
{
    protected BookingModel $bookingModel;
    protected BookingItemModel $bookingItemModel;
    protected BookingQrCodeModel $bookingQrCodeModel;
    protected BookingLogModel $bookingLogModel;
    protected CourtModel $courtModel;
    protected PricingService $pricingService;

    public function __construct()
    {
        $this->bookingModel      = model(BookingModel::class);
        $this->bookingItemModel  = model(BookingItemModel::class);
        $this->bookingQrCodeModel = model(BookingQrCodeModel::class);
        $this->bookingLogModel   = model(BookingLogModel::class);
        $this->courtModel        = model(CourtModel::class);
        $this->pricingService    = new PricingService();
    }

    /**
     * Create a new booking with items
     */
    public function createBooking(array $data): array
    {
        $tenantId = $data['tenant_id'];
        $branchId = $data['branch_id'];

        // Validate court availability for each court in items
        foreach ($data['items'] as $item) {
            $bookable = $this->validateCourtBookable((int) $item['court_id'], (int) $branchId, $data['booking_date'], $item['start_time'], $item['end_time']);
            if (! $bookable['success']) {
                return $bookable;
            }

            if (!$this->checkCourtAvailable($item['court_id'], $data['booking_date'], $item['start_time'], $item['end_time'])) {
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

        $db = \Config\Database::connect();
        $db->transStart();

        // Create booking
        $bookingId = $this->bookingModel->insert([
            'tenant_id'       => $tenantId,
            'branch_id'       => $branchId,
            'player_id'       => $data['player_id'] ?? null,
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
            'expires_at'      => $expiresAt,
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

        $depositAmount = $totalAmount * ($depositPercent / 100);
        $this->bookingModel->update($bookingId, [
            'total_amount'    => $totalAmount,
            'deposit_amount'  => $depositAmount,
            'pricing_rule_id' => $selectedRuleIds[0] ?? null,
            'price_breakdown' => json_encode($priceBreakdown, JSON_UNESCAPED_UNICODE),
        ]);

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
        $data['status'] = 'pending';
        return $this->createBooking($data);
    }

    /**
     * Confirm payment for a booking
     */
    public function confirmPayment(int $bookingId, float $amount, ?int $userId = null): array
    {
        $booking = $this->bookingModel->find($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => lang('App.booking_not_found')];
        }

        $oldStatus = $booking->status;
        $oldPaymentStatus = $booking->payment_status;

        $paidAmount = $booking->paid_amount + $amount;
        $paymentStatus = $paidAmount >= $booking->total_amount ? 'paid' : 'partial';
        $newStatus = $paymentStatus === 'paid' ? 'reserved' : $booking->status;

        $this->bookingModel->update($bookingId, [
            'paid_amount'    => $paidAmount,
            'payment_status' => $paymentStatus,
            'status'         => $newStatus,
            'updated_by'     => $userId,
        ]);

        // Log
        $this->bookingLogModel->addLog(
            $booking->tenant_id, $bookingId,
            'payment_confirmed',
            $oldStatus, $newStatus,
            lang('App.payment_confirmed_amount', [$amount]),
            $userId
        );

        // If fully paid, update payment status log
        if ($paymentStatus === 'paid' && $oldPaymentStatus !== 'paid') {
            $this->bookingLogModel->addLog(
                $booking->tenant_id, $bookingId,
                'payment_status_changed',
                $oldPaymentStatus, 'paid',
                lang('App.fully_paid'),
                $userId
            );
        }

        return [
            'success' => true,
            'message' => lang('App.payment_confirmed'),
            'booking' => $this->bookingModel->find($bookingId),
        ];
    }

    /**
     * Cancel a booking
     */
    public function cancelBooking(int $bookingId, ?string $reason = null, ?int $userId = null): array
    {
        $booking = $this->bookingModel->find($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => lang('App.booking_not_found')];
        }

        if (in_array($booking->status, ['completed', 'cancelled', 'refunded'])) {
            return ['success' => false, 'message' => lang('App.cannot_cancel_booking')];
        }

        $oldStatus = $booking->status;
        $newStatus = $booking->paid_amount > 0 ? 'refunded' : 'cancelled';

        $this->bookingModel->update($bookingId, [
            'status'          => $newStatus,
            'payment_status'  => $booking->paid_amount > 0 ? 'refunded' : 'unpaid',
            'cancelled_at'    => date('Y-m-d H:i:s'),
            'cancelled_reason' => $reason,
            'updated_by'      => $userId,
        ]);

        // Cancel all active items
        $this->bookingItemModel->where('booking_id', $bookingId)
                               ->where('status', 'active')
                               ->set(['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')])
                               ->update();

        // Revoke QR codes
        $this->bookingQrCodeModel->invalidateByBooking($bookingId);

        // Log
        $this->bookingLogModel->addLog(
            $booking->tenant_id, $bookingId,
            $newStatus === 'refunded' ? 'refunded' : 'cancelled',
            $oldStatus, $newStatus,
            $reason ?? lang('App.booking_cancelled'),
            $userId
        );

        return [
            'success' => true,
            'message' => lang('App.booking_cancelled_success'),
            'booking' => $this->bookingModel->find($bookingId),
        ];
    }

    /**
     * Reschedule a booking
     */
    public function rescheduleBooking(int $bookingId, array $newData, ?int $userId = null): array
    {
        $booking = $this->bookingModel->find($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => lang('App.booking_not_found')];
        }

        if (in_array($booking->status, ['completed', 'cancelled', 'refunded', 'no_show'])) {
            return ['success' => false, 'message' => lang('App.cannot_reschedule_booking')];
        }

        $newDate = $newData['booking_date'] ?? $booking->booking_date;
        $newStartTime = $newData['start_time'] ?? $booking->start_time;
        $newEndTime = $newData['end_time'] ?? $booking->end_time;

        // Check availability for all courts in booking items
        $items = $this->bookingItemModel->getByBooking($bookingId);
        foreach ($items as $item) {
            if (!$this->checkCourtAvailable($item->court_id, $newDate, $newStartTime, $newEndTime, $bookingId)) {
                $court = $this->courtModel->find($item->court_id);
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

        return [
            'success' => true,
            'message' => lang('App.booking_rescheduled_success'),
            'booking' => $this->bookingModel->find($bookingId),
        ];
    }

    /**
     * Check-in using QR token
     */
    public function checkInByQr(string $token, ?int $userId = null): array
    {
        $qrCode = $this->bookingQrCodeModel->findActiveByToken($token);
        if (!$qrCode) {
            return ['success' => false, 'message' => lang('App.qr_invalid_or_expired')];
        }

        if ($qrCode->expired_at && strtotime($qrCode->expired_at) < time()) {
            $this->bookingQrCodeModel->update($qrCode->id, ['status' => 'expired']);
            return ['success' => false, 'message' => lang('App.qr_expired')];
        }

        $booking = $this->bookingModel->find($qrCode->booking_id);
        if (!$booking) {
            return ['success' => false, 'message' => lang('App.booking_not_found')];
        }

        if ($booking->status === 'checked_in') {
            return ['success' => true, 'message' => lang('App.already_checked_in'), 'booking' => $booking];
        }

        if (!in_array($booking->status, ['reserved', 'paid'])) {
            return ['success' => false, 'message' => lang('App.cannot_check_in_status')];
        }

        $oldStatus = $booking->status;

        $db = \Config\Database::connect();
        $db->transStart();

        $this->bookingModel->update($bookingId = $booking->id, [
            'status'        => 'checked_in',
            'checked_in_at' => date('Y-m-d H:i:s'),
            'updated_by'    => $userId,
        ]);

        // Mark QR as used
        $this->bookingQrCodeModel->update($qrCode->id, [
            'status'  => 'used',
            'used_at' => date('Y-m-d H:i:s'),
        ]);

        // Log
        $this->bookingLogModel->addLog(
            $booking->tenant_id, $bookingId,
            'checked_in',
            $oldStatus, 'checked_in',
            lang('App.checked_in_via_qr'),
            $userId
        );

        $db->transComplete();

        if ($db->transStatus() === false) {
            return ['success' => false, 'message' => lang('App.check_in_failed')];
        }

        // Optionally update court status to occupied
        $items = $this->bookingItemModel->getByBooking($bookingId);
        foreach ($items as $item) {
            $this->courtModel->update($item->court_id, ['status' => 'occupied']);
        }

        return [
            'success' => true,
            'message' => lang('App.check_in_success'),
            'booking' => $this->bookingModel->find($bookingId),
        ];
    }

    /**
     * Mark booking as completed
     */
    public function markCompleted(int $bookingId, ?int $userId = null): array
    {
        $booking = $this->bookingModel->find($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => lang('App.booking_not_found')];
        }

        if ($booking->status !== 'checked_in') {
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

        return [
            'success' => true,
            'message' => lang('App.booking_completed_success'),
            'booking' => $this->bookingModel->find($bookingId),
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

            $this->bookingModel->update($booking->id, [
                'status'          => 'cancelled',
                'cancelled_reason' => lang('App.auto_cancelled_expired'),
                'cancelled_at'    => date('Y-m-d H:i:s'),
            ]);

            $this->bookingItemModel->where('booking_id', $booking->id)
                                   ->where('status', 'active')
                                   ->set(['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')])
                                   ->update();

            $this->bookingLogModel->addLog(
                $booking->tenant_id, $booking->id,
                'auto_cancelled',
                'pending', 'cancelled',
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

    protected function validateCourtBookable(int $courtId, int $branchId, string $date, string $startTime, string $endTime): array
    {
        $court = $this->courtModel->find($courtId);
        if (! $court) {
            return ['success' => false, 'message' => 'Không tìm thấy sân.'];
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
                ->where('end_time >', $startAt)
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

    /**
     * Get available time slots for a court on a date
     */
    public function getAvailableSlots(int $courtId, string $date, int $slotDurationMinutes = 60): array
    {
        $court = $this->courtModel->find($courtId);
        if (!$court || $court->status === 'inactive') {
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
                                         ->where('end_time >=', $date . ' 00:00:00')
                                         ->findAll();

        // Get existing bookings for this date
        $bookedItems = $this->bookingItemModel
            ->select('booking_items.start_time, booking_items.end_time')
            ->join('bookings', 'bookings.id = booking_items.booking_id')
            ->where('booking_items.court_id', $courtId)
            ->where('bookings.booking_date', $date)
            ->where('bookings.deleted_at', null)
            ->where('bookings.status !=', 'cancelled')
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
}
