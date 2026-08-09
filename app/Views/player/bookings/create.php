<?= $this->extend('player/layouts/main') ?>

<?= $this->section('title') ?><?= lang('App.new_booking') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <h1 class="h3 mb-4"><?= lang('App.new_booking') ?></h1>

    <!-- Step indicator -->
    <div class="d-flex justify-content-center mb-4">
        <div class="d-flex align-items-center">
            <div class="step-circle active">1</div>
            <span class="ms-2 me-3"><?= lang('App.step_select_court') ?></span>
        </div>
        <div class="d-flex align-items-center">
            <div class="step-circle" id="step2">2</div>
            <span class="ms-2 me-3"><?= lang('App.step_select_time') ?></span>
        </div>
        <div class="d-flex align-items-center">
            <div class="step-circle" id="step3">3</div>
            <span class="ms-2"><?= lang('App.step_confirm') ?></span>
        </div>
    </div>

    <form method="post" action="/player/bookings/create" id="bookingForm">
        <?= csrf_field() ?>

        <!-- Step 1: Select Branch, Court, Date -->
        <div id="step1Content">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?= lang('App.branch') ?></label>
                        <select name="branch_id" id="branchSelect" class="form-select" required>
                            <option value=""><?= lang('App.select_branch') ?></option>
                            <?php foreach ($branches as $branch): ?>
                            <option value="<?= $branch->id ?>"><?= esc($branch->getName()) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= lang('App.select_court') ?></label>
                        <select name="court_id" id="courtSelect" class="form-select" required disabled>
                            <option value=""><?= lang('App.select_branch') ?> <?= lang('App.first') ?></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= lang('App.select_date') ?></label>
                        <input type="date" name="booking_date" id="dateSelect" class="form-control" required value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
                    </div>
                    <button type="button" class="btn btn-primary w-100" id="btnNextStep2" disabled>
                        <?= lang('App.select_time_slot') ?> <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 2: Select Time Slot -->
        <div id="step2Content" style="display:none">
            <div class="card mb-3">
                <div class="card-body">
                    <h5><?= lang('App.available_slots') ?></h5>
                    <div id="slotsContainer" class="row g-2">
                        <div class="col-12 text-center text-muted py-4">
                            <div class="spinner-border spinner-border-sm me-2"></div>
                            <?= lang('App.loading') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Confirm -->
        <div id="step3Content" style="display:none">
            <div class="card mb-3">
                <div class="card-body">
                    <h5><?= lang('App.booking_summary') ?></h5>
                    <table class="table table-sm">
                        <tr>
                            <th><?= lang('App.court') ?></th>
                            <td id="summaryCourt"></td>
                        </tr>
                        <tr>
                            <th><?= lang('App.booking_date') ?></th>
                            <td id="summaryDate"></td>
                        </tr>
                        <tr>
                            <th><?= lang('App.start_time') ?> - <?= lang('App.end_time') ?></th>
                            <td id="summaryTime"></td>
                        </tr>
                        <tr>
                            <th><?= lang('App.total_amount') ?></th>
                            <td id="summaryPrice"></td>
                        </tr>
                    </table>

                    <hr>
                    <div class="mb-3">
                        <label class="form-label"><?= lang('App.customer_name') ?> <span class="text-danger">*</span></label>
                        <input type="text" name="customer_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= lang('App.customer_phone') ?> <span class="text-danger">*</span></label>
                        <input type="text" name="customer_phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= lang('App.customer_email') ?></label>
                        <input type="email" name="customer_email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= lang('App.note') ?></label>
                        <textarea name="note" class="form-control" rows="2"></textarea>
                    </div>

                    <input type="hidden" name="start_time" id="hiddenStartTime">
                    <input type="hidden" name="end_time" id="hiddenEndTime">
                    <input type="hidden" name="price" id="hiddenPrice">

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-circle"></i> <?= lang('App.submit_booking') ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.step-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
    background: #dee2e6;
    color: #6c757d;
}
.step-circle.active {
    background: #0d6efd;
    color: white;
}
.step-circle.completed {
    background: #198754;
    color: white;
}
.slot-btn {
    min-height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
}
.slot-btn.unavailable {
    opacity: 0.5;
    cursor: not-allowed;
}
.slot-btn.selected {
    border-color: #0d6efd;
    background: #e7f1ff;
}
</style>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
let selectedCourtId = null;
let selectedDate = null;
let selectedSlot = null;
let courtsMap = {};

document.getElementById('branchSelect').addEventListener('change', function() {
    const branchId = this.value;
    const courtSelect = document.getElementById('courtSelect');

    if (!branchId) {
        courtSelect.innerHTML = '<option value=""><?= lang('App.select_branch') ?> <?= lang('App.first') ?></option>';
        courtSelect.disabled = true;
        document.getElementById('btnNextStep2').disabled = true;
        return;
    }

    courtSelect.disabled = true;
    courtSelect.innerHTML = '<option value=""><?= lang('App.loading') ?>...</option>';

    fetch('/api/v1/courts?branch_id=' + branchId)
        .then(r => r.json())
        .then(data => {
            courtSelect.innerHTML = '<option value=""><?= lang('App.select_court') ?></option>';
            if (data.success && data.data) {
                data.data.forEach(court => {
                    const name = court.name_vi || court.name_en || court.code;
                    courtSelect.innerHTML += `<option value="${court.id}">${court.code} - ${name}</option>`;
                    courtsMap[court.id] = court;
                });
            }
            courtSelect.disabled = false;
            checkStep1Ready();
        });
});

document.getElementById('courtSelect').addEventListener('change', checkStep1Ready);
document.getElementById('dateSelect').addEventListener('change', checkStep1Ready);

function checkStep1Ready() {
    const court = document.getElementById('courtSelect').value;
    const date = document.getElementById('dateSelect').value;
    document.getElementById('btnNextStep2').disabled = !(court && date);
}

document.getElementById('btnNextStep2').addEventListener('click', function() {
    selectedCourtId = document.getElementById('courtSelect').value;
    selectedDate = document.getElementById('dateSelect').value;

    // Show step 2
    document.getElementById('step1Content').style.display = 'none';
    document.getElementById('step2Content').style.display = 'block';
    document.querySelector('.step-circle').classList.add('completed');
    document.getElementById('step2').classList.add('active');

    // Load slots
    loadSlots(selectedCourtId, selectedDate);
});

function loadSlots(courtId, date) {
    const container = document.getElementById('slotsContainer');
    container.innerHTML = '<div class="col-12 text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2"></div><?= lang('App.loading') ?></div>';

    fetch('/player/bookings/get-slots', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'court_id=' + courtId + '&date=' + date
    })
    .then(r => r.json())
    .then(data => {
        container.innerHTML = '';
        if (data.success && data.slots && data.slots.length > 0) {
            data.slots.forEach(slot => {
                const col = document.createElement('div');
                col.className = 'col-6 col-md-4 col-lg-3';
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-outline-primary w-100 slot-btn';
                btn.textContent = slot.start_time.substring(0, 5) + ' - ' + slot.end_time.substring(0, 5);

                if (!slot.available) {
                    btn.className = 'btn btn-outline-secondary w-100 slot-btn unavailable';
                    btn.disabled = true;
                }

                btn.dataset.start = slot.start_time;
                btn.dataset.end = slot.end_time;

                btn.addEventListener('click', function() {
                    document.querySelectorAll('.slot-btn.selected').forEach(el => el.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedSlot = this.dataset;
                    showStep3();
                });

                col.appendChild(btn);
                container.appendChild(col);
            });
        } else {
            container.innerHTML = '<div class="col-12 text-center text-muted py-4"><?= lang('App.no_available_slots') ?></div>';
        }
    });
}

function showStep3() {
    // Hide step 2
    document.getElementById('step2Content').style.display = 'none';
    document.getElementById('step3Content').style.display = 'block';
    document.getElementById('step2').classList.add('completed');
    document.getElementById('step3').classList.add('active');

    // Fill summary
    const court = courtsMap[selectedCourtId];
    const name = court ? (court.name_vi || court.name_en || court.code) : '';
    document.getElementById('summaryCourt').textContent = name;
    document.getElementById('summaryDate').textContent = selectedDate;
    document.getElementById('summaryTime').textContent = selectedSlot.start.substring(0,5) + ' - ' + selectedSlot.end.substring(0,5);
    document.getElementById('summaryPrice').textContent = '<?= lang('App.pending') ?>';

    // Set hidden fields
    document.getElementById('hiddenStartTime').value = selectedSlot.start;
    document.getElementById('hiddenEndTime').value = selectedSlot.end;
}
</script>
<?= $this->endSection() ?>
