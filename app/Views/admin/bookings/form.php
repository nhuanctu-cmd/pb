<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="erp-grid-2">
    <form method="post" action="/admin/bookings/create" class="erp-form" id="bookingForm">
        <?= csrf_field() ?>
        <section class="erp-form-section">
            <div class="erp-form-section-header"><h2>Thông tin khách</h2></div>
            <div class="erp-form-section-body erp-form-grid">
                <div class="erp-field"><label>Tên khách <span class="erp-required">*</span></label><input name="customer_name" class="erp-control" required></div>
                <div class="erp-field"><label>Số điện thoại <span class="erp-required">*</span></label><input name="customer_phone" class="erp-control" required></div>
                <div class="erp-field"><label>Email</label><input type="email" name="customer_email" class="erp-control"></div>
                <div class="erp-field"><label>Nguồn</label><input class="erp-control" value="Admin" disabled></div>
            </div>
        </section>

        <section class="erp-form-section">
            <div class="erp-form-section-header"><h2>Chi nhánh, ngày giờ và sân</h2></div>
            <div class="erp-form-section-body erp-form-grid">
                <div class="erp-field"><label>Chi nhánh <span class="erp-required">*</span></label><select name="branch_id" id="bookingBranch" class="erp-select" required><option value="">Chọn chi nhánh</option><?php foreach ($branches ?? [] as $branch): ?><option value="<?= $branch->id ?>" <?= ($branchId ?? '') == $branch->id ? 'selected' : '' ?>><?= esc($branch->name) ?></option><?php endforeach; ?></select></div>
                <div class="erp-field"><label>Ngày chơi <span class="erp-required">*</span></label><input type="date" name="booking_date" id="bookingDate" class="erp-control" value="<?= date('Y-m-d') ?>" required></div>
                <div class="erp-field"><label>Bắt đầu</label><input type="time" name="start_time" id="bookingStart" class="erp-control" value="18:00" required></div>
                <div class="erp-field"><label>Kết thúc</label><input type="time" name="end_time" id="bookingEnd" class="erp-control" value="19:00" required></div>
            </div>
            <div class="erp-form-section-body pt-0">
                <div class="erp-section-title"><h3>Sân trống đề xuất</h3><button type="button" class="erp-btn" onclick="loadAvailableCourts()"><i class="bi bi-search"></i> Tải sân trống</button></div>
                <div id="availableCourts" class="row g-2">
                    <div class="erp-empty py-3">Chọn chi nhánh/ngày/giờ để tải sân trống.</div>
                </div>
            </div>
        </section>

        <section class="erp-form-section">
            <div class="erp-form-section-header"><h2>Giá áp dụng & thanh toán</h2></div>
            <div class="erp-form-section-body erp-form-grid">
                <div class="erp-field"><label>Giá tính từ PricingService</label><input id="calculatedPrice" class="erp-control" value="0đ" disabled><input type="hidden" name="prices[]" id="hiddenPrice" value="0"></div>
                <div class="erp-field"><label>Tiền cọc dự kiến</label><input id="depositAmount" class="erp-control" value="0đ" disabled></div>
            </div>
            <div class="erp-form-section-body pt-0"><div id="priceBreakdown" class="erp-notice">Chọn sân để xem breakdown giá.</div></div>
        </section>

        <section class="erp-form-section">
            <div class="erp-form-section-header"><h2>Ghi chú</h2></div>
            <div class="erp-form-section-body">
                <textarea name="note" class="erp-textarea" placeholder="Ghi chú cho lễ tân hoặc nhân viên sân"></textarea>
                <div class="erp-notice mt-3">Chính sách hủy: hoàn 100% cọc nếu hủy trước giờ chơi tối thiểu 2 giờ.</div>
            </div>
            <div class="erp-sticky-save">
                <a href="/admin/bookings" class="erp-btn">Hủy</a>
                <button type="button" class="erp-btn">Lưu nháp</button>
                <button class="erp-btn erp-btn-primary"><i class="bi bi-check2-circle"></i> Tạo booking</button>
            </div>
        </section>
    </form>

    <aside class="erp-dashboard-stack">
        <section class="erp-card">
            <div class="erp-card-header"><strong>Tóm tắt booking</strong><?= renderStatusBadge('pending', 'booking') ?></div>
            <div class="erp-card-body erp-info-list">
                <div class="erp-info-row"><span>Sân</span><strong id="summaryCourt">Chưa chọn</strong></div>
                <div class="erp-info-row"><span>Thời gian</span><strong id="summaryTime">18:00-19:00</strong></div>
                <div class="erp-info-row"><span>Tổng tiền</span><strong id="summaryTotal">0đ</strong></div>
                <div class="erp-info-row"><span>Cọc 30%</span><strong id="summaryDeposit">0đ</strong></div>
            </div>
        </section>
        <section class="erp-card"><div class="erp-card-header"><strong>Panel chính sách</strong></div><div class="erp-card-body"><div class="erp-notice mb-2">Booking chỉ tạo nếu sân `available`, không trùng booking, không trùng bảo trì.</div><div class="erp-notice">Giá luôn lấy từ Dynamic Pricing. Giá thủ công bị khóa.</div></div></section>
    </aside>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
let selectedCourtId = null;
function money(n){ return Number(n || 0).toLocaleString('vi-VN') + 'đ'; }
function loadAvailableCourts(){
    const qs = new URLSearchParams({branch_id: bookingBranch.value, date: bookingDate.value, start_time: bookingStart.value, end_time: bookingEnd.value});
    availableCourts.innerHTML = '<div class="erp-empty py-3">Đang tải sân trống...</div>';
    fetch('/admin/ops/available-courts?' + qs).then(r=>r.json()).then(data => {
        if(!data.success || !data.data.length){ availableCourts.innerHTML = '<div class="erp-empty py-3">Không có sân trống phù hợp.</div>'; return; }
        availableCourts.innerHTML = data.data.map(c => `<div class="col-md-6"><label class="erp-quick-tile w-100"><input type="radio" name="court_ids[]" value="${c.id}" onchange="selectCourt(${c.id}, '${c.code} - ${c.name.replace(/'/g, '')}')"><span><strong>${c.code} - ${c.name}</strong><div class="erp-muted">${c.is_indoor ? 'Trong nhà' : 'Ngoài trời'} · ${c.status}</div></span></label></div>`).join('');
    });
}
function selectCourt(id, label){
    selectedCourtId = id;
    summaryCourt.textContent = label;
    recalcPrice();
}
function recalcPrice(){
    if(!selectedCourtId) return;
    summaryTime.textContent = bookingStart.value + '-' + bookingEnd.value;
    const params = new URLSearchParams({branch_id: bookingBranch.value, court_id: selectedCourtId, date: bookingDate.value, start_time: bookingStart.value, end_time: bookingEnd.value});
    priceBreakdown.innerHTML = 'Đang tính giá...';
    fetch('/admin/ops/pricing-test', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:params}).then(r=>r.json()).then(data => {
        if(!data.success){ priceBreakdown.innerHTML = data.message || 'Không tính được giá.'; return; }
        calculatedPrice.value = data.formatted_price;
        hiddenPrice.value = data.final_price;
        depositAmount.value = money(data.final_price * .3);
        summaryTotal.textContent = data.formatted_price;
        summaryDeposit.textContent = money(data.final_price * .3);
        priceBreakdown.innerHTML = `<strong>Rule trúng:</strong> ${data.selected_rule ? data.selected_rule.name : 'Không có'}<br>` + (data.breakdown || []).map(x => `${x.name_vi}: ${money(x.calculated_price)}`).join('<br>');
    });
}
[bookingBranch, bookingDate, bookingStart, bookingEnd].forEach(el => el.addEventListener('change', () => { loadAvailableCourts(); recalcPrice(); }));
</script>
<?= $this->endSection() ?>
