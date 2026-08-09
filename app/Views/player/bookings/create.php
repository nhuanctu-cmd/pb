<?= $this->extend('player/layouts/main') ?>

<?= $this->section('title') ?><?= lang('App.new_booking') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container py-4 booking-page">
    <div class="booking-heading mb-4">
        <div>
            <div class="eyebrow">PICKLEBALL BOOKING</div>
            <h1 class="h3 mb-1">Chọn sân và khung giờ</h1>
            <p class="text-muted mb-0">Xem nhanh lịch trống trong cả tuần rồi chọn đúng vị trí sân.</p>
        </div>
        <a href="/player/bookings" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i> Lịch đặt sân</a>
    </div>

    <div class="booking-steps mb-4">
        <div class="booking-step active"><span>1</span><div><small>BƯỚC 1</small><strong>Chọn sân & lịch</strong></div></div>
        <div class="booking-step" id="step2"><span>2</span><div><small>BƯỚC 2</small><strong>Xác nhận giờ</strong></div></div>
        <div class="booking-step" id="step3"><span>3</span><div><small>BƯỚC 3</small><strong>Thông tin đặt sân</strong></div></div>
    </div>

    <form method="post" action="/player/bookings/create" id="bookingForm">
        <?= csrf_field() ?>
        <input type="hidden" name="branch_id" id="hiddenBranchId">
        <input type="hidden" name="court_id" id="hiddenCourtId">
        <input type="hidden" name="booking_date" id="hiddenBookingDate">
        <input type="hidden" name="start_time" id="hiddenStartTime">
        <input type="hidden" name="end_time" id="hiddenEndTime">
        <input type="hidden" name="price" value="0">

        <section id="step1Content">
            <div class="card booking-card mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-end g-3">
                        <div class="col-md-5">
                            <label for="branchSelect" class="form-label fw-semibold">Chi nhánh</label>
                            <select id="branchSelect" class="form-select form-select-lg">
                                <option value="">Chọn chi nhánh</option>
                                <?php foreach ($branches as $branch): ?>
                                <option value="<?= esc($branch->id) ?>"><?= esc($branch->getName()) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold mb-0">Tuần cần đặt</label>
                                <span class="small text-muted" id="weekLabel">—</span>
                            </div>
                            <div class="week-toolbar">
                                <button type="button" class="btn btn-light border" id="prevWeek" aria-label="Tuần trước"><i class="bi bi-chevron-left"></i></button>
                                <button type="button" class="btn btn-outline-primary flex-grow-1" id="todayWeek">Tuần này</button>
                                <button type="button" class="btn btn-light border" id="nextWeek" aria-label="Tuần sau"><i class="bi bi-chevron-right"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="bookingWorkspace" class="d-none">
                <div class="row g-4">
                    <div class="col-xl-5">
                        <div class="card booking-card h-100">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <div class="eyebrow">SƠ ĐỒ KHU VỰC</div>
                                        <h2 class="h5 mb-1">Chọn sân trên bản đồ</h2>
                                    </div>
                                    <span class="map-legend"><i></i> Có thể đặt</span>
                                </div>
                                <div class="map-scroll">
                                    <div id="courtMap" class="court-map">
                                        <div class="map-net"></div>
                                        <div class="map-label map-label-one">KHU A</div>
                                        <div class="map-label map-label-two">KHU B</div>
                                    </div>
                                </div>
                                <div id="courtInfo" class="court-info mt-3">
                                    <i class="bi bi-hand-index-thumb me-2"></i> Chọn một sân để xem giờ trống.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-7">
                        <div class="card booking-card h-100">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <div class="eyebrow">LỊCH TUẦN</div>
                                        <h2 class="h5 mb-1">Khung giờ còn trống</h2>
                                    </div>
                                    <div class="availability-legend">
                                        <span><i class="legend-dot available"></i> Trống</span>
                                        <span><i class="legend-dot booked"></i> Đã kín</span>
                                    </div>
                                </div>
                                <div id="weekGrid" class="week-grid-wrap">
                                    <div class="empty-state"><i class="bi bi-calendar2-week"></i><p>Chọn chi nhánh để tải lịch tuần.</p></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="step3Content" class="d-none">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card booking-card">
                        <div class="card-body p-4 p-lg-5">
                            <button type="button" class="btn btn-link px-0 mb-3" id="backToSchedule"><i class="bi bi-arrow-left me-1"></i> Chọn lại lịch</button>
                            <div class="eyebrow">XÁC NHẬN LỰA CHỌN</div>
                            <h2 class="h4 mb-4">Thông tin đặt sân</h2>
                            <div class="booking-summary mb-4">
                                <div><small>SÂN</small><strong id="summaryCourt">—</strong></div>
                                <div><small>NGÀY</small><strong id="summaryDate">—</strong></div>
                                <div><small>KHUNG GIỜ</small><strong id="summaryTime">—</strong></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tên người đặt <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_phone" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="customer_email" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Ghi chú</label>
                                    <input type="text" name="note" class="form-control" placeholder="Ví dụ: cần thuê vợt">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mã ưu đãi</label>
                                    <input type="text" name="promotion_code" class="form-control" placeholder="Ví dụ: PBWELCOME">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100 mt-4"><i class="bi bi-check2-circle me-2"></i> Xác nhận đặt sân</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </form>
</div>

<style>
:root { --booking-ink:#102c2b; --booking-green:#1d8474; --booking-soft:#eef8f5; }
.booking-heading { display:flex; justify-content:space-between; align-items:center; gap:1rem; }
.eyebrow { color:var(--booking-green); font-size:.68rem; font-weight:800; letter-spacing:.14em; margin-bottom:.35rem; }
.booking-card { border:1px solid #e5eeec; border-radius:18px; box-shadow:0 8px 28px rgba(16,44,43,.055); }
.booking-steps { display:flex; gap:1rem; background:#f7faf9; border-radius:16px; padding:1rem 1.2rem; }
.booking-step { display:flex; align-items:center; gap:.65rem; flex:1; color:#9aa9a7; }
.booking-step span { width:31px; height:31px; border-radius:50%; display:grid; place-items:center; background:#e1e9e7; font-weight:700; }
.booking-step small { display:block; font-size:.6rem; letter-spacing:.1em; font-weight:700; }
.booking-step strong { display:block; font-size:.86rem; color:#758684; }
.booking-step.active span,.booking-step.completed span { background:var(--booking-green); color:#fff; }
.booking-step.active strong,.booking-step.completed strong { color:var(--booking-ink); }
.week-toolbar { display:flex; gap:.5rem; }
.map-scroll { overflow:auto; border-radius:15px; background:#dcefe8; }
.court-map { position:relative; width:900px; height:390px; overflow:hidden; background:linear-gradient(135deg,#d1ebe2 0%,#b9ded1 100%); background-image:linear-gradient(30deg,rgba(255,255,255,.16) 12%,transparent 12.5%,transparent 87%,rgba(255,255,255,.16) 87.5%,rgba(255,255,255,.16)),linear-gradient(150deg,rgba(255,255,255,.13) 12%,transparent 12.5%,transparent 87%,rgba(255,255,255,.13) 87.5%,rgba(255,255,255,.13)); background-size:38px 66px; }
.court-map:before,.court-map:after { content:""; position:absolute; left:18px; right:18px; border:2px dashed rgba(29,132,116,.16); border-radius:25px; }
.court-map:before { top:18px; height:166px; }.court-map:after { bottom:18px; height:166px; }
.map-net { position:absolute; z-index:1; top:194px; left:18px; right:18px; border-top:4px solid rgba(16,44,43,.22); }
.map-label { position:absolute; z-index:1; color:rgba(16,44,43,.36); font-size:.65rem; font-weight:800; letter-spacing:.2em; }.map-label-one{top:28px;left:35px}.map-label-two{bottom:28px;right:35px}
.court-marker { position:absolute; z-index:3; width:130px; height:88px; border:0; border-radius:12px; padding:.6rem; color:#fff; text-align:left; background:linear-gradient(145deg,#32a88f,#147665); box-shadow:0 7px 0 rgba(12,76,67,.17),0 10px 20px rgba(17,78,68,.18); transform:rotate(var(--rotation)); transition:transform .18s,filter .18s,box-shadow .18s; }
.court-marker:hover,.court-marker.selected { transform:rotate(var(--rotation)) translateY(-5px) scale(1.03); box-shadow:0 9px 0 rgba(12,76,67,.17),0 14px 24px rgba(17,78,68,.25); }
.court-marker.selected { outline:3px solid #f7c948; outline-offset:3px; }.court-marker.unavailable { background:linear-gradient(145deg,#8e9d9b,#637471); opacity:.7; filter:saturate(.5); cursor:not-allowed; box-shadow:0 5px 0 rgba(56,72,70,.15); }
.court-marker .court-code { display:block; font-size:1rem; font-weight:800; }.court-marker .court-name { display:block; font-size:.7rem; opacity:.88; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }.court-marker .court-status { display:block; font-size:.62rem; margin-top:.3rem; opacity:.85; }
.map-legend { font-size:.72rem; color:#58716d; white-space:nowrap; }.map-legend i { display:inline-block; width:9px;height:9px;border-radius:50%;background:#32a88f;margin-right:4px; }
.court-info { color:#607572; background:#f7faf9; border-radius:10px; padding:.75rem 1rem; font-size:.87rem; }.court-info strong { color:var(--booking-ink); }
.availability-legend { display:flex; gap:.75rem; font-size:.7rem; color:#6a7a78; white-space:nowrap; }.legend-dot { display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:3px; }.legend-dot.available{background:#32a88f}.legend-dot.booked{background:#e5b5b5}
.week-grid-wrap { overflow:auto; border:1px solid #e5eeec; border-radius:12px; }.week-grid { min-width:660px; }.week-grid-head,.week-grid-row { display:grid; grid-template-columns:76px repeat(7,minmax(82px,1fr)); }.week-grid-head { background:#f5faf8; border-bottom:1px solid #e1ece9; }.week-grid-head>div { padding:.65rem .35rem; text-align:center; font-size:.68rem; color:#6e817d; font-weight:700; }.week-grid-head .today { color:var(--booking-green); background:#e4f5ef; }.day-number { display:block; color:var(--booking-ink); font-size:.96rem; }.week-grid-row>div { min-height:59px; border-bottom:1px solid #edf2f1; border-right:1px solid #edf2f1; padding:.28rem; }.week-grid-row:last-child>div { border-bottom:0; }.time-label { color:#8a9997; font-size:.7rem; text-align:center; padding-top:1.05rem!important; }.slot-cell { display:flex; width:100%; min-height:49px; flex-direction:column; align-items:center; justify-content:center; border:1px solid transparent; border-radius:8px; font-size:.66rem; transition:.15s; }.slot-cell.available { background:#eaf8f3; color:#13725f; cursor:pointer; }.slot-cell.available:hover,.slot-cell.selected { background:#1d8474; color:#fff; border-color:#126453; transform:translateY(-1px); }.slot-cell.unavailable { background:#faf1f1; color:#b28787; cursor:not-allowed; }.slot-cell.closed { color:#c0c9c7; font-size:.65rem; }.slot-cell small { font-size:.59rem; opacity:.75; }.empty-state { text-align:center; padding:3rem 1rem; color:#94a3a1; }.empty-state i { font-size:2rem; }.empty-state p { margin:.55rem 0 0; font-size:.85rem; }
.booking-summary { display:grid; grid-template-columns:repeat(3,1fr); gap:1px; overflow:hidden; background:#dcebe7; border-radius:12px; }.booking-summary>div { padding:1rem; background:#f5faf8; }.booking-summary small { display:block; color:#7b908c; font-size:.63rem; letter-spacing:.1em; }.booking-summary strong { display:block; color:var(--booking-ink); margin-top:.25rem; }
@media (max-width:767px) { .booking-heading { align-items:flex-start; flex-direction:column; }.booking-steps { gap:.4rem; padding:.8rem; }.booking-step { gap:.35rem; }.booking-step div { display:none; }.booking-step span { margin:auto; }.booking-summary { grid-template-columns:1fr; }.booking-summary>div { padding:.7rem .9rem; }.availability-legend { flex-direction:column; gap:.15rem; } }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const today = '<?= esc($today) ?>';
const state = { branchId: '', weekStart: mondayOf(today), data: null, courtId: null, slot: null };
const $ = id => document.getElementById(id);

function mondayOf(dateString) {
    const date = new Date(dateString + 'T12:00:00');
    const day = date.getDay() || 7;
    date.setDate(date.getDate() - day + 1);
    return date.toISOString().slice(0, 10);
}
function addDays(dateString, days) {
    const date = new Date(dateString + 'T12:00:00');
    date.setDate(date.getDate() + days);
    return date.toISOString().slice(0, 10);
}
function formatDate(dateString, options = {}) { return new Intl.DateTimeFormat('vi-VN', options).format(new Date(dateString + 'T12:00:00')); }
function time(value) { return String(value || '').slice(0, 5); }
function escapeHtml(value) { const div = document.createElement('div'); div.textContent = value ?? ''; return div.innerHTML; }

function setWeekLabel() {
    $('weekLabel').textContent = state.weekStart ? `${formatDate(state.weekStart, {day:'2-digit', month:'2-digit'})} — ${formatDate(addDays(state.weekStart, 6), {day:'2-digit', month:'2-digit', year:'numeric'})}` : '—';
}
function loadAvailability() {
    setWeekLabel();
    if (!state.branchId) { $('bookingWorkspace').classList.add('d-none'); return; }
    $('bookingWorkspace').classList.remove('d-none');
    $('weekGrid').innerHTML = '<div class="empty-state"><div class="spinner-border spinner-border-sm text-success"></div><p>Đang tải lịch trống...</p></div>';
    fetch(`/player/bookings/week-availability?branch_id=${encodeURIComponent(state.branchId)}&week_start=${encodeURIComponent(state.weekStart)}`)
        .then(response => response.json())
        .then(payload => {
            if (!payload.success) throw new Error(payload.message || 'Không thể tải lịch.');
            state.data = payload.data;
            state.weekStart = payload.data.week_start;
            state.courtId = state.data.courts.find(court => court.is_bookable)?.id || null;
            state.slot = null;
            renderMap(); renderGrid();
        })
        .catch(error => { $('weekGrid').innerHTML = `<div class="empty-state text-danger"><i class="bi bi-exclamation-circle"></i><p>${escapeHtml(error.message)}</p></div>`; });
}
function renderMap() {
    const map = $('courtMap');
    map.querySelectorAll('.court-marker').forEach(marker => marker.remove());
    (state.data?.courts || []).forEach(court => {
        const marker = document.createElement('button');
        marker.type = 'button'; marker.className = 'court-marker' + (court.is_bookable ? '' : ' unavailable') + (court.id === state.courtId ? ' selected' : '');
        marker.style.setProperty('--rotation', `${court.rotation || 0}deg`);
        marker.style.left = `${court.coordinates_x}px`; marker.style.top = `${court.coordinates_y}px`;
        marker.disabled = !court.is_bookable;
        marker.innerHTML = `<span class="court-code">${escapeHtml(court.code)}</span><span class="court-name">${escapeHtml(court.name)}</span><span class="court-status">${court.is_bookable ? '● Có thể đặt' : '● ' + escapeHtml(court.status_label)}</span>`;
        if (court.is_bookable) marker.addEventListener('click', () => { state.courtId = court.id; state.slot = null; renderMap(); renderGrid(); });
        map.appendChild(marker);
    });
    const selected = (state.data?.courts || []).find(court => court.id === state.courtId);
    $('courtInfo').innerHTML = selected ? `<i class="bi bi-check-circle-fill text-success me-2"></i> Đang chọn <strong>${escapeHtml(selected.code)} — ${escapeHtml(selected.name)}</strong>. Chọn ô <strong>Trống</strong> bên phải để tiếp tục.` : '<i class="bi bi-hand-index-thumb me-2"></i> Chọn một sân để xem giờ trống.';
}
function renderGrid() {
    const days = state.data?.days || [];
    if (!days.length) return;
    const slots = [];
    days.forEach(day => day.slots.forEach(slot => { if (!slots.some(item => item.start_time === slot.start_time && item.end_time === slot.end_time)) slots.push(slot); }));
    slots.sort((a,b) => a.start_time.localeCompare(b.start_time));
    if (!slots.length) { $('weekGrid').innerHTML = '<div class="empty-state"><i class="bi bi-calendar-x"></i><p>Không có khung giờ mở cửa trong tuần này.</p></div>'; return; }
    const head = days.map(day => `<div class="${day.is_today ? 'today' : ''}">${day.weekday}<span class="day-number">${day.day_number}</span></div>`).join('');
    const rows = slots.map(slot => {
        const cells = days.map(day => {
            const daySlot = day.slots.find(item => item.start_time === slot.start_time && item.end_time === slot.end_time);
            const status = daySlot?.by_court?.[String(state.courtId)];
            if (!daySlot || !status) return '<div><div class="slot-cell closed">Đóng cửa</div></div>';
            const selected = state.slot && state.slot.date === day.date && state.slot.start === slot.start_time;
            const available = status.available;
            return `<div><button type="button" class="slot-cell ${available ? 'available' : 'unavailable'} ${selected ? 'selected' : ''}" ${available ? '' : 'disabled'} data-date="${day.date}" data-start="${slot.start_time}" data-end="${slot.end_time}">${available ? '<i class="bi bi-check2"></i><small>Trống</small>' : `<i class="bi bi-dash-circle"></i><small>${status.is_maintenance ? 'Bảo trì' : 'Đã kín'}</small>`}</button></div>`;
        }).join('');
        return `<div class="week-grid-row"><div class="time-label">${time(slot.start_time)}</div>${cells}</div>`;
    }).join('');
    $('weekGrid').innerHTML = `<div class="week-grid"><div class="week-grid-head"><div>GIỜ</div>${head}</div>${rows}</div>`;
    $('weekGrid').querySelectorAll('.slot-cell.available').forEach(button => button.addEventListener('click', () => chooseSlot(button)));
}
function chooseSlot(button) {
    state.slot = { date: button.dataset.date, start: button.dataset.start, end: button.dataset.end };
    $('hiddenBranchId').value = state.branchId; $('hiddenCourtId').value = state.courtId; $('hiddenBookingDate').value = state.slot.date; $('hiddenStartTime').value = state.slot.start; $('hiddenEndTime').value = state.slot.end;
    const court = state.data.courts.find(item => item.id === state.courtId);
    $('summaryCourt').textContent = `${court.code} — ${court.name}`; $('summaryDate').textContent = formatDate(state.slot.date, {weekday:'short', day:'2-digit', month:'2-digit', year:'numeric'}); $('summaryTime').textContent = `${time(state.slot.start)} — ${time(state.slot.end)}`;
    $('step1Content').classList.add('d-none'); $('step3Content').classList.remove('d-none'); $('step2').classList.add('completed'); $('step3').classList.add('active');
}

$('branchSelect').addEventListener('change', event => { state.branchId = event.target.value; $('hiddenBranchId').value = state.branchId; state.courtId = null; state.slot = null; loadAvailability(); });
$('prevWeek').addEventListener('click', () => { state.weekStart = addDays(state.weekStart, -7); loadAvailability(); });
$('nextWeek').addEventListener('click', () => { state.weekStart = addDays(state.weekStart, 7); loadAvailability(); });
$('todayWeek').addEventListener('click', () => { state.weekStart = mondayOf(today); loadAvailability(); });
$('backToSchedule').addEventListener('click', () => { $('step3Content').classList.add('d-none'); $('step1Content').classList.remove('d-none'); $('step3').classList.remove('active'); $('step2').classList.remove('completed'); });
setWeekLabel();
</script>
<?= $this->endSection() ?>
