<?= $this->extend('player/layouts/mobile') ?>

<?= $this->section('title') ?>Pickleball<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Welcome -->
<div class="card-mobile" style="background: linear-gradient(135deg, #1a73e8, #0d47a1); color: #fff;">
    <div class="card-body">
        <p style="font-size:14px;opacity:0.9;margin-bottom:4px">Xin chào,</p>
        <h3 style="margin:0;font-weight:700"><?= esc($playerName ?? 'Người chơi') ?> 🏓</h3>
    </div>
</div>

<!-- Quick Stats -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px">
    <div class="card-mobile" style="text-align:center">
        <div class="card-body" style="padding:12px">
            <div style="font-size:24px;font-weight:700;color:var(--primary)"><?= $stats['matches_played'] ?? 0 ?></div>
            <div style="font-size:11px;color:#666">Trận đã chơi</div>
        </div>
    </div>
    <div class="card-mobile" style="text-align:center">
        <div class="card-body" style="padding:12px">
            <div style="font-size:24px;font-weight:700;color:var(--success)"><?= $stats['wins'] ?? 0 ?></div>
            <div style="font-size:11px;color:#666">Thắng</div>
        </div>
    </div>
    <div class="card-mobile" style="text-align:center">
        <div class="card-body" style="padding:12px">
            <div style="font-size:24px;font-weight:700;color:var(--warning)">
                <?= number_format($wallet['balance'] ?? 0, 0, ',', '.') ?>đ
            </div>
            <div style="font-size:11px;color:#666">Số dư</div>
        </div>
    </div>
</div>

<!-- Upcoming Bookings -->
<div class="card-mobile">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Lịch sắp tới</span>
        <a href="/player/bookings" style="font-size:13px;color:var(--primary);text-decoration:none">Xem tất cả</a>
    </div>
    <div class="card-body">
        <?php if (empty($upcomingBookings)): ?>
        <div class="empty-state" style="padding:24px">
            <i class="bi bi-calendar-plus"></i>
            <p>Chưa có lịch đặt sân nào</p>
            <button class="btn-mobile btn-mobile-primary" style="width:auto;padding:10px 24px;font-size:14px" onclick="location.href='/player/booking/create'">
                <i class="bi bi-plus-circle"></i> Đặt sân ngay
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($upcomingBookings as $b): ?>
        <a href="/player/booking/<?= $b['id'] ?>" style="text-decoration:none;color:inherit">
            <div style="display:flex;gap:12px;padding:8px 0;border-bottom:1px solid #f0f0f0">
                <div style="text-align:center;min-width:48px">
                    <div style="font-size:20px;font-weight:700;color:var(--primary)"><?= date('d', strtotime($b['start_time'])) ?></div>
                    <div style="font-size:11px;color:#666"><?= date('M', strtotime($b['start_time'])) ?></div>
                </div>
                <div style="flex:1">
                    <div style="font-weight:600;font-size:14px">Sân <?= esc($b['court_name'] ?? ($b['court_id'] ?? 'chưa gán')) ?></div>
                    <div style="font-size:12px;color:#666">
                        <?= date('H:i', strtotime($b['start_time'])) ?> - <?= date('H:i', strtotime($b['end_time'])) ?>
                    </div>
                </div>
                <span class="status-chip status-confirmed">Xác nhận</span>
            </div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px">
    <div class="card-mobile" onclick="location.href='/player/booking/create'" style="cursor:pointer">
        <div class="card-body" style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:20px">
            <i class="bi bi-calendar-plus" style="font-size:28px;color:var(--primary)"></i>
            <span style="font-size:13px;font-weight:600">Đặt sân</span>
        </div>
    </div>
    <div class="card-mobile" onclick="location.href='/player/tournaments'" style="cursor:pointer">
        <div class="card-body" style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:20px">
            <i class="bi bi-trophy" style="font-size:28px;color:var(--warning)"></i>
            <span style="font-size:13px;font-weight:600">Giải đấu</span>
        </div>
    </div>
    <div class="card-mobile" onclick="location.href='/player/teams'" style="cursor:pointer">
        <div class="card-body" style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:20px">
            <i class="bi bi-people" style="font-size:28px;color:var(--success)"></i>
            <span style="font-size:13px;font-weight:600">Tìm kèo</span>
        </div>
    </div>
    <div class="card-mobile" onclick="location.href='/player/ranking'" style="cursor:pointer">
        <div class="card-body" style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:20px">
            <i class="bi bi-bar-chart-line" style="font-size:28px;color:var(--danger)"></i>
            <span style="font-size:13px;font-weight:600">Xếp hạng</span>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
