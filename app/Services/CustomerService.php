<?php

namespace App\Services;

use App\Models\CustomerModel;
use App\Models\PlayerModel;

class CustomerService
{
    protected CustomerModel $customerModel;
    protected PlayerModel $playerModel;

    public function __construct()
    {
        $this->customerModel = new CustomerModel();
        $this->playerModel = new PlayerModel();
    }

    public function available(): bool
    {
        return $this->customerModel->db->tableExists('customers') && $this->customerModel->db->tableExists('customer_timeline_events');
    }

    /**
     * Resolve a booking contact to a CRM customer without treating every customer as a player.
     * Booking player_id is historically a users.id reference; it is converted to the registry player id.
     */
    public function resolveForBooking(int $tenantId, array $data, ?int $actorId = null): array
    {
        if (! $this->available()) return ['success' => true, 'customer_id' => null];

        $playerId = null;
        if (! empty($data['player_id'])) {
            $player = $this->playerModel->findPlayerByUser((int) $data['player_id'], $tenantId)
                ?: $this->playerModel->findForTenant((int) $data['player_id'], $tenantId);
            $playerId = $player ? (int) $player->id : null;
        }

        $phone = trim((string) ($data['customer_phone'] ?? '')) ?: null;
        $email = strtolower(trim((string) ($data['customer_email'] ?? ''))) ?: null;
        $now = date('Y-m-d H:i:s');
        $customer = $this->customerModel->findByIdentity($tenantId, $playerId, $phone, $email);

        if ($customer) {
            $updates = [
                'last_seen_at' => $now,
                'updated_by' => $actorId ?: null,
            ];
            if (empty($customer->full_name) && ! empty($data['customer_name'])) $updates['full_name'] = trim((string) $data['customer_name']);
            if (empty($customer->phone) && $phone) $updates['phone'] = $phone;
            if (empty($customer->email) && $email) $updates['email'] = $email;
            if (! $customer->player_id && $playerId) $updates['player_id'] = $playerId;
            $this->customerModel->update($customer->id, $updates);
            return ['success' => true, 'customer_id' => (int) $customer->id];
        }

        $customerId = $this->customerModel->insert([
            'tenant_id' => $tenantId,
            'player_id' => $playerId,
            'full_name' => trim((string) ($data['customer_name'] ?? 'Khách hàng')),
            'phone' => $phone,
            'email' => $email,
            'status' => 'active',
            'source' => $playerId ? 'player' : 'booking',
            'first_seen_at' => $now,
            'last_seen_at' => $now,
            'created_by' => $actorId ?: null,
            'updated_by' => $actorId ?: null,
        ]);

        return $customerId
            ? ['success' => true, 'customer_id' => (int) $customerId]
            : ['success' => false, 'customer_id' => null, 'message' => 'Không thể tạo hồ sơ khách hàng.'];
    }

    public function recordBooking(int $customerId, int $tenantId, int $bookingId, array $data, ?int $actorId = null): bool
    {
        if (! $this->available()) return true;
        $customer = $this->customerModel->findForTenant($customerId, $tenantId);
        if (! $customer) return false;
        $now = date('Y-m-d H:i:s');
        $this->customerModel->update($customerId, [
            'last_seen_at' => $now,
            'last_booking_at' => $now,
            'total_bookings' => (int) $customer->total_bookings + 1,
            'total_spend' => (float) $customer->total_spend + (float) ($data['total_amount'] ?? 0),
            'updated_by' => $actorId ?: null,
        ]);
        return (bool) $this->customerModel->db->table('customer_timeline_events')->insert([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'event_type' => 'booking_created',
            'title' => 'Tạo booking ' . $bookingId,
            'source_type' => 'booking',
            'source_id' => $bookingId,
            'actor_id' => $actorId ?: null,
            'payload' => json_encode(['booking_id' => $bookingId, 'amount' => (float) ($data['total_amount'] ?? 0)], JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
        ]);
    }
}
