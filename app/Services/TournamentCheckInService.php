<?php

namespace App\Services;

use App\Models\TournamentCategoryModel;
use App\Models\TournamentCheckinModel;
use App\Models\TournamentModel;
use App\Models\TournamentRegistrationModel;
use Config\Database;

class TournamentCheckInService
{
    protected TournamentModel $tournamentModel;
    protected TournamentCategoryModel $categoryModel;
    protected TournamentRegistrationModel $registrationModel;
    protected TournamentCheckinModel $checkinModel;

    public function __construct()
    {
        $this->tournamentModel = model(TournamentModel::class);
        $this->categoryModel = model(TournamentCategoryModel::class);
        $this->registrationModel = model(TournamentRegistrationModel::class);
        $this->checkinModel = model(TournamentCheckinModel::class);
    }

    /**
     * API tương thích cho luồng admin cũ: nhận registration và options,
     * sau đó chuyển về hàm tenant/player-scoped chuẩn.
     */
    public function checkIn(int $registrationId, array $options = []): array
    {
        $registration = $this->registrationModel->find($registrationId);
        if (! $registration) {
            return ['success' => false, 'message' => 'Không tìm thấy đăng ký.'];
        }

        return $this->checkInPlayer(
            $registrationId,
            (int) $registration->player_id,
            isset($options['checked_in_by']) ? (int) $options['checked_in_by'] : null
        );
    }

    /**
     * Check-in VĐV bằng registration_id + player_id.
     */
    public function checkInPlayer(int $registrationId, int $playerId, ?int $checkedInBy = null): array
    {
        $registration = $this->registrationModel->where('id', $registrationId)
            ->where('player_id', $playerId)
            ->where('deleted_at', null)
            ->first();
        if (! $registration) {
            return ['success' => false, 'message' => 'Không tìm thấy đăng ký.'];
        }

        $tournament = $this->tournamentModel->findForTenant((int) $registration->tournament_id, (int) $registration->tenant_id);
        if (! $tournament || ! in_array($tournament->status, ['open', 'closed', 'running'], true)) {
            return ['success' => false, 'message' => 'Giải đấu chưa sẵn sàng check-in.'];
        }

        $existing = $this->checkinModel->findByRegistration($registrationId, $playerId);
        if ($existing && $existing->status === 'checked_in') {
            return ['success' => false, 'message' => 'VĐV đã check-in rồi.', 'errors' => ['Already checked in' => 'VĐV đã check-in rồi.']];
        }

        $db = Database::connect();
        $db->transStart();

        $qrCode = $this->generateQrCode($registrationId, $playerId);
        $checkinData = [
            'tenant_id' => $registration->tenant_id,
            'tournament_id' => $registration->tournament_id,
            'category_id' => $registration->category_id,
            'registration_id' => $registrationId,
            'player_id' => $playerId,
            'qr_code' => $qrCode,
            'status' => 'checked_in',
            'checked_in_by' => $checkedInBy,
            'checked_in_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->checkinModel->update($existing->id, $checkinData);
            $checkinId = $existing->id;
        } else {
            $checkinId = $this->checkinModel->insert($checkinData);
        }

        $this->registrationModel->update($registrationId, [
            'registration_status' => 'confirmed',
            'checked_in_at' => date('Y-m-d H:i:s'),
            'checkin_status' => 'checked_in',
            'no_show' => 0,
        ]);

        $db->transComplete();

        if (! $db->transStatus()) {
            return ['success' => false, 'message' => 'Không thể check-in.'];
        }

        return ['success' => true, 'message' => 'Đã check-in.', 'checkin' => $this->checkinModel->find($checkinId), 'qr_code' => $qrCode];
    }

    /**
     * Check-in bằng QR code.
     */
    public function checkInByQr(string $qrCode, ?int $checkedInBy = null): array
    {
        $checkin = $this->checkinModel->findByQrCode($qrCode);
        if (! $checkin) {
            return ['success' => false, 'message' => 'Mã QR không hợp lệ.'];
        }
        return $this->checkInPlayer((int) $checkin->registration_id, (int) $checkin->player_id, $checkedInBy);
    }

    /**
     * Đánh dấu no-show.
     */
    public function markNoShow(int $registrationId, int $playerId, ?string $note = null): array
    {
        $registration = $this->registrationModel->where('id', $registrationId)
            ->where('player_id', $playerId)
            ->where('deleted_at', null)
            ->first();
        if (! $registration) {
            return ['success' => false, 'message' => 'Không tìm thấy đăng ký.'];
        }

        $existing = $this->checkinModel->findByRegistration($registrationId, $playerId);
        $data = ['status' => 'no_show', 'note' => $note];
        if ($existing) {
            $this->checkinModel->update($existing->id, $data);
            $checkinId = $existing->id;
        } else {
            $checkinId = $this->checkinModel->insert([
                'tenant_id' => $registration->tenant_id,
                'tournament_id' => $registration->tournament_id,
                'category_id' => $registration->category_id,
                'registration_id' => $registrationId,
                'player_id' => $playerId,
                'qr_code' => $this->generateQrCode($registrationId, $playerId),
                'status' => 'no_show',
                'note' => $note,
            ]);
        }

        $this->registrationModel->update($registrationId, ['no_show' => 1, 'checkin_status' => 'no_show', 'registration_status' => 'cancelled']);

        return ['success' => true, 'message' => 'Đã đánh dấu không đến.', 'checkin' => $this->checkinModel->find($checkinId)];
    }

    /**
     * Danh sách check-in theo hạng mục.
     */
    public function getCheckinsByCategory(int $categoryId, ?int $tenantId = null): array
    {
        $builder = $this->checkinModel->where('category_id', $categoryId);
        if ($tenantId !== null) {
            $builder->where('tenant_id', $tenantId);
        }
        return $builder->orderBy('checked_in_at', 'DESC')->findAll();
    }

    private function generateQrCode(int $registrationId, int $playerId): string
    {
        return hash('sha256', "tournament-checkin:{$registrationId}:{$playerId}:" . time());
    }
}
