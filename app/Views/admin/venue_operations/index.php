<?= $this->extend('layouts/admin_master') ?>
<?= $this->section('content') ?>
<?php $stats = $stats ?? []; ?>
<style>
.venue-os{--os-ink:#102b33;--os-teal:#087a6d;--os-line:#dce8e4}.venue-os .hero{background:linear-gradient(120deg,#092b34,#087a6d);color:#fff;border-radius:18px;padding:28px;margin-bottom:22px}.venue-os .hero h1{margin:0 0 7px;font-size:28px}.venue-os .hero p{margin:0;color:#c9e6df}.venue-os .hero-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:20px}.venue-os .hero-actions a{color:#fff;border:1px solid rgba(255,255,255,.35);border-radius:8px;padding:9px 13px;text-decoration:none;font-size:12px}.venue-os .hero-actions a.primary{background:#f2c36f;color:#12343a;border-color:#f2c36f;font-weight:700}.venue-os .kpis{display:grid;grid-template-columns:repeat(7,1fr);gap:10px;margin-bottom:22px}.venue-os .kpi{background:#fff;border:1px solid var(--os-line);border-radius:12px;padding:15px}.venue-os .kpi span{display:block;color:#72878a;font-size:10px;text-transform:uppercase;letter-spacing:.08em}.venue-os .kpi strong{display:block;color:var(--os-teal);font-size:24px;margin-top:8px}.venue-os .grid{display:grid;grid-template-columns:1.35fr .65fr;gap:20px}.venue-os .panel{background:#fff;border:1px solid var(--os-line);border-radius:14px;overflow:hidden;margin-bottom:20px}.venue-os .panel-head{padding:17px 19px;border-bottom:1px solid var(--os-line);display:flex;justify-content:space-between;align-items:center}.venue-os .panel-head h2{font-size:17px;margin:0;color:var(--os-ink)}.venue-os .panel-head a{font-size:12px;color:var(--os-teal);text-decoration:none}.venue-os .venue-card{padding:18px 19px;border-bottom:1px solid #edf3f1}.venue-os .venue-card:last-child{border:0}.venue-os .venue-name{display:flex;justify-content:space-between;gap:15px}.venue-os .venue-name h3{font-size:16px;margin:0;color:var(--os-ink)}.venue-os .venue-name small{color:#789092}.venue-os .venue-links{display:flex;gap:7px;flex-wrap:wrap;margin-top:12px}.venue-os .venue-links a{font-size:11px;border:1px solid var(--os-line);border-radius:6px;padding:6px 9px;text-decoration:none;color:#31565b}.venue-os .chips{display:flex;gap:5px;flex-wrap:wrap;margin-top:12px}.venue-os .chip{font-size:10px;background:#edf7f3;color:#126f61;border-radius:99px;padding:5px 8px}.venue-os .chip.warn{background:#fff3dc;color:#9a691b}.venue-os .club-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 19px;border-bottom:1px solid #edf3f1}.venue-os .club-row:last-child{border:0}.venue-os .club-row strong{font-size:13px;color:var(--os-ink)}.venue-os .club-row small{display:block;color:#7b8d90;margin-top:4px}.venue-os .empty{padding:25px 19px;color:#7b8d90;font-size:13px}.venue-os .check{padding:14px 19px;border-bottom:1px solid #edf3f1;font-size:12px}.venue-os .check i{display:inline-block;width:8px;height:8px;border-radius:50%;background:#36a981;margin-right:8px}.venue-os .check.warn i{background:#d49331}
.venue-os .ops-toolbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.venue-os .ops-toolbar input,.venue-os .ops-toolbar select{border:1px solid var(--os-line);border-radius:7px;padding:7px 9px;font-size:12px}.venue-os .ops-board{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;padding:15px}.venue-os .ops-cell{border:1px solid var(--os-line);border-radius:10px;padding:12px;min-height:105px}.venue-os .ops-cell.live{border-color:#18a27d;background:#effaf6}.venue-os .ops-cell.maintenance{border-color:#e5b766;background:#fffaf0}.venue-os .ops-cell h3{font-size:13px;margin:0 0 7px;color:var(--os-ink)}.venue-os .ops-cell small{display:block;color:#71878a;margin-bottom:5px}.venue-os .ops-cell a{font-size:11px;color:var(--os-teal);text-decoration:none}.venue-os .ops-alert{padding:12px 19px;border-bottom:1px solid #edf3f1;display:flex;justify-content:space-between;gap:12px;align-items:center}.venue-os .ops-alert strong{font-size:12px}.venue-os .ops-alert small{display:block;color:#71878a;margin-top:3px}.venue-os .ops-alert.late{background:#fff5f3}.venue-os .pulse{display:inline-block;width:8px;height:8px;border-radius:50%;background:#36a981;margin-right:5px}.venue-os .pulse.live{animation:venuepulse 1.5s infinite}@keyframes venuepulse{50%{opacity:.35}}
@media(max-width:1100px){.venue-os .kpis{grid-template-columns:repeat(4,1fr)}.venue-os .grid{grid-template-columns:1fr}}
@media(max-width:620px){.venue-os .kpis{grid-template-columns:repeat(2,1fr)}.venue-os .hero h1{font-size:22px}}
</style>
<div class="container-fluid venue-os">
    <?php $room = $controlRoom ?? ['courts' => [], 'late' => [], 'unchecked' => [], 'stats' => [], 'date' => date('Y-m-d')]; ?>
    <div class="hero">
        <div class="eyebrow text-uppercase small opacity-75">VENUE & CLUB OPERATIONS</div>
        <h1>Control Room vận hành sân</h1>
        <p>Một màn hình để chủ sân biết cơ sở, chi nhánh, sân đấu và CLB đang ở trạng thái nào.</p>
        <div class="hero-actions">
            <a class="primary" href="/admin/facilities/create"><i class="bi bi-buildings"></i> Thêm cụm sân</a>
            <a href="/admin/branches/create"><i class="bi bi-diagram-3"></i> Thêm chi nhánh</a>
            <a href="/admin/clubs/create"><i class="bi bi-building-heart"></i> Tạo CLB</a>
            <a href="/admin/courts/create"><i class="bi bi-grid-3x3"></i> Thêm sân</a>
        </div>
    </div>
    <div class="panel">
        <div class="panel-head"><h2><span class="pulse live"></span>Điều phối ngày thi đấu</h2><div class="ops-toolbar"><form method="get" class="d-flex gap-2"><input type="date" name="date" value="<?= esc($room['date'] ?? date('Y-m-d')) ?>"><select name="branch_id"><option value="">Tất cả chi nhánh</option><?php foreach (($branches ?? []) as $branch): ?><option value="<?= (int) $branch->id ?>" <?= (int) ($room['branch_id'] ?? 0) === (int) $branch->id ? 'selected' : '' ?>><?= esc($branch->name) ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-outline-primary">Lọc</button></form><small id="venue-poll-status" class="text-muted">Tự cập nhật 15 giây</small></div></div>
        <div class="ops-board">
            <?php foreach (($room['courts'] ?? []) as $row): $court = $row['court']; $booking = $row['live'] ?: $row['next']; ?>
                <article class="ops-cell <?= esc($row['state']) ?>"><h3><?= esc($court->code ?: $court->name_vi) ?></h3><small><?= esc($court->branch_name ?: 'Chi nhánh') ?> · <?= $row['state'] === 'live' ? 'ĐANG ĐÁNH' : ($row['state'] === 'next' ? 'KẾ TIẾP' : strtoupper($row['state'])) ?></small><?php if ($booking): ?><strong class="d-block small"><?= esc($booking->customer_name) ?></strong><small><?= esc(substr((string) $booking->start_time, 0, 5)) ?>–<?= esc(substr((string) $booking->end_time, 0, 5)) ?></small><a href="/admin/bookings/show/<?= (int) $booking->id ?>">Mở phiếu →</a><?php else: ?><small>Không có lịch kế tiếp.</small><?php endif; ?></article>
            <?php endforeach; ?>
            <?php if (empty($room['courts'])): ?><div class="empty">Chưa có sân trong phạm vi đang chọn.</div><?php endif; ?>
        </div>
        <div class="row g-0 border-top"><div class="col-lg-6"><div class="panel-head"><h2>Trễ / cần xử lý ngay (<?= count($room['late'] ?? []) ?>)</h2><a href="/admin/front-desk">Mở quầy →</a></div><?php foreach (array_slice($room['late'] ?? [], 0, 5) as $booking): ?><div class="ops-alert late"><div><strong><?= esc($booking->customer_name) ?> · <?= esc($booking->booking_code) ?></strong><small>Đến lúc <?= esc(substr((string) $booking->start_time, 0, 5)) ?> nhưng chưa check-in</small></div><a class="btn btn-sm btn-danger" href="/admin/bookings/show/<?= (int) $booking->id ?>">Xử lý</a></div><?php endforeach; ?><?php if (empty($room['late'])): ?><div class="empty">Không có booking trễ.</div><?php endif; ?></div><div class="col-lg-6 border-start"><div class="panel-head"><h2>Chưa check-in (<?= count($room['unchecked'] ?? []) ?>)</h2><a href="/admin/front-desk">Check-in nhanh →</a></div><?php foreach (array_slice($room['unchecked'] ?? [], 0, 5) as $booking): ?><div class="ops-alert"><div><strong><?= esc($booking->customer_name) ?></strong><small><?= esc($booking->booking_code) ?> · <?= esc(substr((string) $booking->start_time, 0, 5)) ?></small></div><a class="btn btn-sm btn-outline-primary" href="/admin/bookings/show/<?= (int) $booking->id ?>">Mở</a></div><?php endforeach; ?><?php if (empty($room['unchecked'])): ?><div class="empty">Tất cả khách trong hàng chờ đã check-in.</div><?php endif; ?></div></div>
    </div>
    <div class="kpis">
        <?php foreach ([['facilities','Cụm sân'],['branches','Chi nhánh'],['courts','Sân đấu'],['available_courts','Sân sẵn sàng'],['maintenance_courts','Đang bảo trì'],['clubs','CLB'],['today_bookings','Booking hôm nay']] as [$key,$label]): ?>
            <div class="kpi"><span><?= $label ?></span><strong><?= number_format((int) ($stats[$key] ?? 0)) ?></strong></div>
        <?php endforeach; ?>
    </div>
    <div class="grid">
        <div>
            <div class="panel">
                <div class="panel-head"><h2><i class="bi bi-buildings"></i> Cơ sở và năng lực vận hành</h2><a href="/admin/facilities">Quản lý tất cả →</a></div>
                <?php if (!empty($facilities)): foreach ($facilities as $row): $facility = $row['facility']; ?>
                    <div class="venue-card">
                        <div class="venue-name"><div><h3><?= esc($facility->getName()) ?></h3><small><?= esc($facility->code) ?> · <?= esc($facility->address ?: 'Chưa cập nhật địa chỉ') ?></small></div><span class="badge bg-<?= $facility->status === 'active' ? 'success' : 'secondary' ?>"><?= esc($facility->status) ?></span></div>
                        <div class="chips"><span class="chip"><?= count($row['branches']) ?> chi nhánh</span><span class="chip"><?= count($row['courts']) ?> sân</span><span class="chip"><?= $row['active_courts'] ?> sẵn sàng</span><?php if ($row['maintenance_courts']): ?><span class="chip warn"><?= $row['maintenance_courts'] ?> bảo trì</span><?php endif; ?></div>
                        <div class="venue-links"><a href="/admin/facilities/dashboard/<?= (int) $facility->id ?>">Dashboard</a><a href="/admin/facilities/branches/<?= (int) $facility->id ?>">Chi nhánh</a><a href="/admin/facilities/clubs/<?= (int) $facility->id ?>">CLB liên kết</a><a href="/admin/facilities/edit/<?= (int) $facility->id ?>">Cấu hình</a></div>
                    </div>
                <?php endforeach; else: ?><div class="empty">Chưa có cụm sân. Hãy tạo cơ sở đầu tiên để bắt đầu vận hành.</div><?php endif; ?>
            </div>
        </div>
        <div>
            <div class="panel"><div class="panel-head"><h2><i class="bi bi-building-heart"></i> Câu lạc bộ</h2><a href="/admin/clubs">Mở module →</a></div>
                <?php if (!empty($clubs)): foreach (array_slice($clubs, 0, 8) as $club): ?><div class="club-row"><div><strong><?= esc($club->name_vi) ?></strong><small><?= esc($club->name_en ?: 'Club trong tenant hiện tại') ?></small></div><a class="btn btn-sm btn-outline-secondary" href="/admin/clubs/edit/<?= (int) $club->id ?>">Mở</a></div><?php endforeach; else: ?><div class="empty">Chưa có CLB trong đơn vị vận hành.</div><?php endif; ?>
            </div>
            <div class="panel"><div class="panel-head"><h2><i class="bi bi-shield-check"></i> Kiểm tra nhanh</h2></div>
                <div class="check <?= ($stats['facilities'] ?? 0) ? '' : 'warn' ?>"><i></i><?= ($stats['facilities'] ?? 0) ? 'Đã có mô hình cụm sân' : 'Chưa tạo cụm sân' ?></div>
                <div class="check <?= ($stats['branches'] ?? 0) ? '' : 'warn' ?>"><i></i><?= ($stats['branches'] ?? 0) ? 'Đã có chi nhánh vận hành' : 'Chưa tạo chi nhánh' ?></div>
                <div class="check <?= ($stats['courts'] ?? 0) ? '' : 'warn' ?>"><i></i><?= ($stats['courts'] ?? 0) ? 'Đã khai báo sân đấu' : 'Chưa khai báo sân đấu' ?></div>
                <div class="check <?= ($stats['clubs'] ?? 0) ? '' : 'warn' ?>"><i></i><?= ($stats['clubs'] ?? 0) ? 'Đã có CLB liên kết' : 'Chưa có CLB' ?></div>
                <div class="check <?= ($stats['club_members'] ?? 0) ? '' : 'warn' ?>"><i></i><?= number_format((int) ($stats['club_members'] ?? 0)) ?> thành viên CLB đang hoạt động</div>
            </div>
        </div>
    </div>
</div>
<script>
(() => { const status = document.getElementById('venue-poll-status'); const params = new URLSearchParams(window.location.search); const refresh = async () => { try { const response = await fetch('/admin/venue-operations/data?' + params.toString(), {headers:{'X-Requested-With':'XMLHttpRequest'}}); if (!response.ok) throw new Error(); const payload = await response.json(); if (status && payload.success) status.textContent = 'Đã đồng bộ ' + new Date().toLocaleTimeString('vi-VN') + ' · làm mới màn hình sau 15s'; } catch (_) { if (status) status.textContent = 'Không thể đồng bộ tự động'; } }; setInterval(refresh, 15000); })();
</script>
<?= $this->endSection() ?>
