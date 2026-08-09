<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\BookingModel;
use App\Models\BookingItemModel;
use App\Models\BookingQrCodeModel;
use App\Models\BookingLogModel;

class BookingSeeder extends Seeder
{
    public function run()
    {
        // Get existing courts and branches
        $courts = $this->db->table('courts')
                          ->where('deleted_at', null)
                          ->where('status', 'available')
                          ->get()
                          ->getResult();

        $branches = $this->db->table('branches')
                            ->where('deleted_at', null)
                            ->get()
                            ->getResult();

        $tenants = $this->db->table('tenants')
                           ->where('deleted_at', null)
                           ->get()
                           ->getResult();

        if (empty($courts) || empty($branches) || empty($tenants)) {
            echo "Need at least 1 tenant, 1 branch, and 1 court to seed bookings.\n";
            return;
        }

        $tenantId = $tenants[0]->id;

        $statuses = ['pending', 'paid', 'checked_in', 'completed', 'cancelled'];
        $paymentStatuses = [
            'pending'    => 'unpaid',
            'paid'       => 'paid',
            'checked_in' => 'paid',
            'completed'  => 'paid',
            'cancelled'  => 'unpaid',
        ];
        $sources = ['admin', 'player_portal', 'public_web', 'zalo', 'phone'];
        $firstNames = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Đặng', 'Bùi'];
        $lastNames = ['Văn A', 'Thị B', 'Văn C', 'Thị D', 'Văn E', 'Thị F', 'Văn G', 'Thị H', 'Văn I', 'Thị K'];
        $phones = ['090', '091', '092', '093', '094', '096', '097', '098', '0120', '0121'];

        for ($i = 0; $i < 100; $i++) {
            $branch = $branches[array_rand($branches)];
            $court = $courts[array_rand($courts)];
            $status = $statuses[array_rand($statuses)];
            $source = $sources[array_rand($sources)];

            // Random date within last 30 days and next 30 days
            $dayOffset = rand(-30, 30);
            $bookingDate = date('Y-m-d', strtotime("{$dayOffset} days"));

            // Random time slot (06:00 - 22:00)
            $hourStart = rand(6, 20);
            $duration = rand(1, 3) * 60; // 1-3 hours in minutes
            $startTime = sprintf('%02d:00:00', $hourStart);
            $endTime = sprintf('%02d:00:00', min($hourStart + ($duration / 60), 23));

            $totalAmount = rand(100000, 500000);
            $depositAmount = $totalAmount * 0.3;
            $paidAmount = ($status === 'paid' || $status === 'checked_in' || $status === 'completed') ? $totalAmount : 0;

            $bookingCode = 'BK-SEED-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            $customerName = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
            $customerPhone = $phones[array_rand($phones)] . rand(1000000, 9999999);

            // Booking timestamps
            $createdAt = date('Y-m-d H:i:s', strtotime("-" . rand(1, 60) . " days"));
            $checkedInAt = null;
            $completedAt = null;
            $cancelledAt = null;

            if ($status === 'checked_in') {
                $checkedInAt = date('Y-m-d H:i:s', strtotime($createdAt . ' + ' . rand(1, 5) . ' hours'));
            } elseif ($status === 'completed') {
                $checkedInAt = date('Y-m-d H:i:s', strtotime($createdAt . ' + ' . rand(1, 5) . ' hours'));
                $completedAt = date('Y-m-d H:i:s', strtotime($checkedInAt . ' + ' . $duration . ' minutes'));
            } elseif ($status === 'cancelled') {
                $cancelledAt = date('Y-m-d H:i:s', strtotime($createdAt . ' + ' . rand(1, 10) . ' hours'));
            }

            $expiresAt = ($status === 'pending')
                ? date('Y-m-d H:i:s', strtotime($createdAt . ' + 15 minutes'))
                : null;

            $this->db->transStart();

            $bookingId = $this->db->table('bookings')->insert([
                'tenant_id'       => $tenantId,
                'branch_id'       => $branch->id,
                'player_id'       => null,
                'customer_name'   => $customerName,
                'customer_phone'  => $customerPhone,
                'customer_email'  => strtolower(str_replace(' ', '.', $customerName)) . '@example.com',
                'booking_code'    => $bookingCode,
                'booking_date'    => $bookingDate,
                'start_time'      => $startTime,
                'end_time'        => $endTime,
                'duration_minutes' => $duration,
                'total_amount'    => $totalAmount,
                'deposit_amount'  => $depositAmount,
                'paid_amount'     => $paidAmount,
                'status'          => $status,
                'payment_status'  => $paymentStatuses[$status],
                'source'          => $source,
                'note'            => 'Booking created by seeder',
                'cancelled_at'    => $cancelledAt,
                'checked_in_at'   => $checkedInAt,
                'completed_at'    => $completedAt,
                'expires_at'      => $expiresAt,
                'created_at'      => $createdAt,
                'updated_at'      => $createdAt,
            ]);

            $bookingId = $this->db->insertID();

            // Create booking item
            $this->db->table('booking_items')->insert([
                'tenant_id'  => $tenantId,
                'booking_id' => $bookingId,
                'court_id'   => $court->id,
                'start_time' => $startTime,
                'end_time'   => $endTime,
                'price'      => $totalAmount,
                'status'     => ($status === 'cancelled') ? 'cancelled' : 'active',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Create QR code
            $qrToken = bin2hex(random_bytes(32));
            $this->db->table('booking_qr_codes')->insert([
                'tenant_id'  => $tenantId,
                'booking_id' => $bookingId,
                'qr_token'   => $qrToken,
                'qr_path'    => null,
                'expired_at' => date('Y-m-d 23:59:59', strtotime($bookingDate)),
                'status'     => ($status === 'cancelled') ? 'revoked' : 'active',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Create booking log
            $this->db->table('booking_logs')->insert([
                'tenant_id'  => $tenantId,
                'booking_id' => $bookingId,
                'action'     => 'created',
                'old_status' => null,
                'new_status' => $status,
                'message'    => 'Booking created via seeder',
                'created_by' => null,
                'created_at' => $createdAt,
            ]);

            if ($status === 'checked_in' || $status === 'completed') {
                $this->db->table('booking_logs')->insert([
                    'tenant_id'  => $tenantId,
                    'booking_id' => $bookingId,
                    'action'     => 'checked_in',
                    'old_status' => 'paid',
                    'new_status' => 'checked_in',
                    'message'    => 'Auto checked-in (seeder)',
                    'created_by' => null,
                    'created_at' => $checkedInAt,
                ]);
            }

            if ($status === 'completed') {
                $this->db->table('booking_logs')->insert([
                    'tenant_id'  => $tenantId,
                    'booking_id' => $bookingId,
                    'action'     => 'completed',
                    'old_status' => 'checked_in',
                    'new_status' => 'completed',
                    'message'    => 'Auto completed (seeder)',
                    'created_by' => null,
                    'created_at' => $completedAt,
                ]);
            }

            if ($status === 'cancelled') {
                $this->db->table('booking_logs')->insert([
                    'tenant_id'  => $tenantId,
                    'booking_id' => $bookingId,
                    'action'     => 'cancelled',
                    'old_status' => 'pending',
                    'new_status' => 'cancelled',
                    'message'    => 'Auto cancelled (seeder)',
                    'created_by' => null,
                    'created_at' => $cancelledAt,
                ]);
            }

            $this->db->transComplete();
        }

        echo "100 sample bookings created successfully.\n";
    }
}
