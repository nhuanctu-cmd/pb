cod<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <h1 class="h3 mb-4">
                <i class="bi bi-calendar-plus"></i> <?= lang('Booking.quick_booking') ?>
            </h1>

            <!-- Step Progress -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="step-progress d-flex justify-content-between">
                        <div class="step-item text-center <?= $step >= 1 ? 'active' : '' ?>">
                            <div class="step-circle">1</div>
                            <small><?= lang('Booking.step_select_court') ?></small>
                        </div>
                        <div class="step-line flex-grow-1 align-self-center mx-2"></div>
                        <div class="step-item text-center <?= $step >= 2 ? 'active' : '' ?>">
                            <div class="step-circle">2</div>
                            <small><?= lang('Booking.step_select_date') ?></small>
                        </div>
                        <div class="step-line flex-grow-1 align-self-center mx-2"></div>
                        <div class="step-item text-center <?= $step >= 3 ? 'active' : '' ?>">
                            <div class="step-circle">3</div>
                            <small><?= lang('Booking.step_confirmation') ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 1: Select Court -->
            <div id="step1" class="card shadow-sm mb-4" <?= $step !== 1 ? 'style="display:none"' : '' ?>>
                <div class="card-header">
                    <h5 class="card-title mb-0">1. <?= lang('Booking.step_select_court') ?></h5>
                </div>
                <div class="card-body">
                    <!-- Facility Select -->
                    <div class="mb-3">
                        <label class="form-label"><?= lang('Booking.step_select_facility') ?></label>
                        <select class="form-select" id="facilitySelect" onchange="loadBranches(this.value)">
                            <option value="">-- <?= lang('App.select') ?> --</option>
                            <?php foreach ($facilities as $facility): ?>
                            <option value="<?= $facility->id ?>"><?= esc($facility->getName()) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Branch Select -->
                    <div class="mb-3">
                        <label class="form-label"><?= lang('Branch.title') ?></label>
                        <select class="form-select" id="branchSelect" disabled onchange="loadCourts(this.value)">
                            <option value="">-- <?= lang('App.select') ?> --</option>
                        </select>
                    </div>

                    <!-- Court Grid -->
                    <div id="courtGrid" class="row g-2 mt-3"></div>
                </div>
            </div>

            <!-- Step 2: Select Date & Time -->
            <div id="step2" class="card shadow-sm mb-4" <?= $step !== 2 ? 'style="display:none"' : '' ?>>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">2. <?= lang('Booking.step_select_date') ?></h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="goToStep(1)">
                        <i class="bi bi-arrow-left"></i> <?= lang('App.back') ?>
                    </button>
                </div>
                <div class="card-body">
                    <!-- Date Picker -->
                    <div class="mb-3">
                        <label class="form-label"><?= lang('App.date') ?></label>
                        <input type="date" class="form-control form-control-lg" id="bookingDate"
                               min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+14 days')) ?>"
                               onchange="loadTimeSlots()">
                    </div>

                    <!-- Selected Court Info -->
                    <div id="selectedCourtInfo" class="alert alert-info d-none">
                        <i class="bi bi-info-circle"></i>
                        <span id="selectedCourtName"></span>
                    </div>

                    <!-- Time Slots (Swipeable) -->
                    <div class="mb-3">
                        <label class="form-label"><?= lang('Booking.select_time_slot') ?></label>
                        <div id="timeSlots" class="time-slots-container">
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-hand-index-thumb fs-1 d-block mb-2"></i>
                                <?= lang('Booking.swipe_to_book') ?>
                            </div>
                        </div>
                    </div>

                    <!-- Duration -->
                    <div class="mb-3">
                        <label class="form-label"><?= lang('Booking.step_select_duration') ?></label>
                        <div class="btn-group w-100" role="group" id="durationGroup">
                            <input type="radio" class="btn-check" name="duration" id="dur30" value="30">
                            <label class="btn btn-outline-primary" for="dur30">30 phút</label>
                            <input type="radio" class="btn-check" name="duration" id="dur60" value="60" checked>
                            <label class="btn btn-outline-primary" for="dur60">60 phút</label>
                            <input type="radio" class="btn-check" name="duration" id="dur90" value="90">
                            <label class="btn btn-outline-primary" for="dur90">90 phút</label>
                            <input type="radio" class="btn-check" name="duration" id="dur120" value="120">
                            <label class="btn btn-outline-primary" for="dur120">120 phút</label>
                        </div>
                    </div>

                    <!-- Price Preview -->
                    <div id="pricePreview" class="card bg-light d-none">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <span><?= lang('Booking.base_price') ?></span>
                                <span id="basePriceDisplay">0₫</span>
                            </div>
                            <div id="priceBreakdownList"></div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span><?= lang('Booking.total_amount') ?></span>
                                <span id="totalPriceDisplay" class="text-primary fs-5">0₫</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Confirmation -->
            <div id="step3" class="card shadow-sm mb-4" <?= $step !== 3 ? 'style="display:none"' : '' ?>>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">3. <?= lang('Booking.step_confirmation') ?></h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="goToStep(2)">
                        <i class="bi bi-arrow-left"></i> <?= lang('App.back') ?>
                    </button>
                </div>
                <div class="card-body">
                    <!-- Customer Info -->
                    <div class="mb-3">
                        <label class="form-label"><?= lang('App.name') ?></label>
                        <input type="text" class="form-control" id="customerName"
                               value="<?= session()->get('full_name') ?? '' ?>" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= lang('App.phone') ?></label>
                            <input type="tel" class="form-control" id="customerPhone" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= lang('App.email') ?></label>
                            <input type="email" class="form-control" id="customerEmail">
                        </div>
                    </div>

                    <!-- Booking Summary -->
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6><?= lang('App.summary') ?></h6>
                            <div id="bookingSummary"></div>
                        </div>
                    </div>

                    <!-- Payment -->
                    <div class="mb-3">
                        <label class="form-label"><?= lang('Booking.payment_method') ?></label>
                        <select class="form-select" id="paymentMethod">
                            <option value="cash"><?= lang('App.cash') ?></option>
                            <option value="transfer"><?= lang('App.bank_transfer') ?></option>
                            <option value="wallet"><?= lang('App.wallet') ?></option>
                            <option value="momo">MoMo</option>
                            <option value="vnpay">VNPay</option>
                        </select>
                    </div>

                    <button class="btn btn-primary btn-lg w-100" onclick="submitBooking()">
                        <i class="bi bi-check-circle"></i> <?= lang('App.confirm_booking') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.step-progress .step-circle {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 5px;
    font-weight: bold;
    transition: all 0.3s;
}
.step-progress .step-item.active .step-circle {
    background: #0d6efd;
    color: white;
}
.step-progress .step-line {
    height: 3px;
    background: #e9ecef;
}
.step-progress .step-item.active ~ .step-item .step-line {
    background: #0d6efd;
}
.time-slots-container {
    max-height: 300px;
    overflow-y: auto;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 10px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
}
.time-slot {
    flex: 0 0 calc(20% - 8px);
    min-width: 80px;
    padding: 10px 5px;
    text-align: center;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.85rem;
}
.time-slot:hover { border-color: #0d6efd; background: #f0f7ff; }
.time-slot.selected { border-color: #0d6efd; background: #0d6efd; color: white; }
.time-slot.booked { border-color: #dc3545; background: #fff5f5; color: #dc3545; cursor: not-allowed; opacity: 0.6; }
.time-slot .price { font-size: 0.75rem; display: block; }
.court-card {
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
}
.court-card:hover { border-color: #0d6efd; transform: translateY(-2px); }
.court-card.selected { border-color: #0d6efd; background: #f0f7ff; }
@media (max-width: 576px) {
    .time-slot { flex: 0 0 calc(33% - 8px); }
}
</style>

<script>
let selectedCourtId = null;
let selectedSlot = null;
let currentPrice = 0;
let currentBreakdown = [];

function goToStep(step) {
    document.querySelectorAll('[id^=step]').forEach(el => el.style.display = 'none');
    document.getElementById('step' + step).style.display = 'block';
    document.querySelectorAll('.step-item').forEach((el, i) => {
        el.classList.toggle('active', i < step);
    });
}

function loadBranches(facilityId) {
    if (!facilityId) return;
    fetch('<?= base_url('api/v1/facilities') ?>/' + facilityId + '/branches')
        .then(r => r.json())
        .then(data => {
            const select = document.getElementById('branchSelect');
            select.innerHTML = '<option value="">-- Select --</option>';
            if (data.success) {
                data.data.forEach(b => {
                    select.innerHTML += `<option value="${b.id}">${b.name}</option>`;
                });
                select.disabled = false;
            }
        });
}

function loadCourts(branchId) {
    if (!branchId) return;
    fetch('<?= base_url('api/v1/facilities/branch') ?>/' + branchId + '/courts')
        .then(r => r.json())
        .then(data => {
            const grid = document.getElementById('courtGrid');
            grid.innerHTML = '';
            if (data.success) {
                data.data.forEach(c => {
                    grid.innerHTML += `
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card court-card text-center p-2" onclick="selectCourt(${c.id}, '${c.name_vi}', this)">
                                <div class="court-indicator mx-auto mb-1"
                                     style="width:30px;height:30px;border-radius:50%;background:${c.status === 'available' ? '#28a745' : '#dc3545'}">
                                </div>
                                <strong>${c.name_vi}</strong>
                                <small class="text-muted">${c.code}</small>
                            </div>
                        </div>`;
                });
            }
        });
}

function selectCourt(courtId, name, el) {
    document.querySelectorAll('.court-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    selectedCourtId = courtId;
    document.getElementById('selectedCourtName').textContent = name;
    document.getElementById('selectedCourtInfo').classList.remove('d-none');
    goToStep(2);
}

function loadTimeSlots() {
    const date = document.getElementById('bookingDate').value;
    if (!date || !selectedCourtId) return;

    const container = document.getElementById('timeSlots');
    container.innerHTML = '<div class="text-center py-3"><div class="spinner-border"></div></div>';

    fetch('<?= base_url('api/v1/booking/available-slots') ?>?court_id=' + selectedCourtId + '&date=' + date)
        .then(r => r.json())
        .then(data => {
            container.innerHTML = '';
            if (data.success && data.data) {
                data.data.forEach(slot => {
                    const cls = slot.available ? 'time-slot' : 'time-slot booked';
                    container.innerHTML += `
                        <div class="${cls}" onclick="${slot.available ? `selectSlot('${slot.start_time}', '${slot.end_time}', ${slot.final_price}, this)` : ''}">
                            <strong>${slot.start_time.substring(0,5)}</strong>
                            <span class="price">${slot.available ? formatPrice(slot.final_price) : 'Booked'}</span>
                        </div>`;
                });
            }
        });
}

function selectSlot(start, end, price, el) {
    document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
    el.classList.add('selected');
    selectedSlot = { start, end };
    currentPrice = price;

    document.getElementById('basePriceDisplay').textContent = formatPrice(price);
    document.getElementById('totalPriceDisplay').textContent = formatPrice(price);
    document.getElementById('pricePreview').classList.remove('d-none');
}

function formatPrice(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

function submitBooking() {
    const name = document.getElementById('customerName').value;
    const phone = document.getElementById('customerPhone').value;
    if (!name || !phone || !selectedCourtId || !selectedSlot) {
        alert('Please fill all required fields');
        return;
    }

    const data = {
        branch_id: document.getElementById('branchSelect').value,
        customer_name: name,
        customer_phone: phone,
        customer_email: document.getElementById('customerEmail').value,
        booking_date: document.getElementById('bookingDate').value,
        start_time: selectedSlot.start,
        end_time: selectedSlot.end,
        source: 'player_portal',
        items: [{ court_id: selectedCourtId, start_time: selectedSlot.start, end_time: selectedSlot.end }]
    };

    fetch('<?= base_url('api/v1/bookings') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            window.location.href = '<?= base_url('player/bookings/detail') ?>/' + result.booking.id;
        } else {
            alert(result.message);
        }
    });
}
</script>
<?= $this->endSection() ?>
