<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BackfillCustomersFromBookings extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('customers') || ! $this->db->tableExists('customer_timeline_events') || ! $this->db->tableExists('bookings')) return;

        $bookings = $this->db->query('SELECT * FROM bookings WHERE customer_id IS NULL ORDER BY id ASC')->getResult();
        foreach ($bookings as $booking) {
            $phone = trim((string) ($booking->customer_phone ?? '')) ?: null;
            $email = strtolower(trim((string) ($booking->customer_email ?? ''))) ?: null;
            $playerId = null;
            if (! empty($booking->player_id) && $this->db->tableExists('players')) {
                $player = $this->db->table('players')->where('tenant_id', $booking->tenant_id)->groupStart()->where('user_id', $booking->player_id)->orWhere('id', $booking->player_id)->groupEnd()->where('deleted_at', null)->get(1)->getRow();
                $playerId = $player ? (int) $player->id : null;
            }

            $customer = null;
            if ($playerId) $customer = $this->db->table('customers')->where('tenant_id', $booking->tenant_id)->where('player_id', $playerId)->where('deleted_at', null)->get(1)->getRow();
            if (! $customer && $phone) $customer = $this->db->table('customers')->where('tenant_id', $booking->tenant_id)->where('phone', $phone)->where('deleted_at', null)->get(1)->getRow();
            if (! $customer && $email) $customer = $this->db->table('customers')->where('tenant_id', $booking->tenant_id)->where('email', $email)->where('deleted_at', null)->get(1)->getRow();

            $now = $booking->created_at ?: date('Y-m-d H:i:s');
            if (! $customer) {
                $this->db->table('customers')->insert([
                    'tenant_id' => (int) $booking->tenant_id,
                    'player_id' => $playerId,
                    'full_name' => trim((string) $booking->customer_name) ?: 'Khách hàng',
                    'phone' => $phone,
                    'email' => $email,
                    'status' => 'active',
                    'source' => $playerId ? 'player' : 'booking',
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                    'last_booking_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $customerId = (int) $this->db->insertID();
            } else {
                $customerId = (int) $customer->id;
            }

            if ($customerId <= 0) continue;
            $this->db->table('bookings')->where('id', $booking->id)->where('tenant_id', $booking->tenant_id)->update(['customer_id' => $customerId]);
            $exists = $this->db->table('customer_timeline_events')->where('tenant_id', $booking->tenant_id)->where('customer_id', $customerId)->where('source_type', 'booking')->where('source_id', $booking->id)->countAllResults();
            if (! $exists) {
                $this->db->table('customer_timeline_events')->insert([
                    'tenant_id' => (int) $booking->tenant_id,
                    'customer_id' => $customerId,
                    'event_type' => 'booking_backfilled',
                    'title' => 'Backfill booking ' . $booking->id,
                    'source_type' => 'booking',
                    'source_id' => (int) $booking->id,
                    'payload' => json_encode(['booking_code' => $booking->booking_code], JSON_UNESCAPED_UNICODE),
                    'created_at' => $now,
                ]);
            }
        }

        $this->db->query("UPDATE customers c SET c.total_bookings = (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = c.id AND b.tenant_id = c.tenant_id AND b.deleted_at IS NULL), c.completed_bookings = (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = c.id AND b.tenant_id = c.tenant_id AND b.status = 'completed' AND b.deleted_at IS NULL), c.no_show_count = (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = c.id AND b.tenant_id = c.tenant_id AND b.status = 'no_show' AND b.deleted_at IS NULL), c.total_spend = (SELECT COALESCE(SUM(b.total_amount), 0) FROM bookings b WHERE b.customer_id = c.id AND b.tenant_id = c.tenant_id AND b.deleted_at IS NULL), c.last_booking_at = (SELECT MAX(CONCAT(b.booking_date, ' ', b.start_time)) FROM bookings b WHERE b.customer_id = c.id AND b.tenant_id = c.tenant_id AND b.deleted_at IS NULL)");
    }

    public function down()
    {
        // Backfill is intentionally non-destructive. Customer records and booking links remain for auditability.
    }
}
