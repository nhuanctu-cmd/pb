<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<!-- POS Counter Page -->
<div class="row" id="pos-app">
    <!-- Left: Categories & Products -->
    <div class="col-md-8">
        <!-- Search & Categories -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="searchProduct" placeholder="<?= lang('Pos.search_product') ?>">
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" id="categoryFilter">
                            <option value=""><?= lang('Pos.categories') ?></option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= esc($category['name_vi']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="row" id="productGrid">
            <?php foreach ($categories as $category): ?>
                <?php if (!empty($category['products'])): ?>
                    <div class="col-12 mb-3">
                        <h6 class="border-bottom pb-2"><?= esc($category['name_vi']) ?></h6>
                        <div class="row">
                            <?php foreach ($category['products'] as $product): ?>
                                <div class="col-md-3 col-sm-4 col-6 mb-3 product-item"
                                     data-category="<?= $category['id'] ?>"
                                     data-name="<?= esc(mb_strtolower($product['name_vi'])) ?>">
                                    <div class="card product-card h-100 <?= $product['stock'] <= 0 ? 'out-of-stock' : '' ?>">
                                        <div class="card-body p-2">
                                            <h6 class="card-title mb-1"><?= esc($product['name_vi']) ?></h6>
                                            <p class="text-primary mb-1 fw-bold"><?= number_format((float) $product['sale_price']) ?>đ</p>
                                            <small class="text-muted"><?= lang('Pos.stock') ?>: <?= (int) $product['stock'] ?></small>
                                        </div>
                                        <div class="card-footer p-2 bg-white border-top-0">
                                            <button type="button" class="btn btn-sm btn-primary w-100 add-to-cart"
                                                    data-product-id="<?= $product['id'] ?>"
                                                    data-product-name="<?= esc($product['name_vi']) ?>"
                                                    data-price="<?= (float) $product['sale_price'] ?>"
                                                    data-stock="<?= (int) $product['stock'] ?>"
                                                    <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                                                <i class="bi bi-cart-plus"></i> <?= lang('Pos.add_to_cart') ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                <?php endif ?>
            <?php endforeach ?>
        </div>
    </div>

    <!-- Right: Cart -->
    <div class="col-md-4">
        <div class="card sticky-top" style="top: 20px;">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-cart3"></i> <?= lang('Pos.pos_title') ?></h5>
                <span class="badge bg-light text-dark" id="cartCount">0</span>
            </div>
            <div class="card-body">
                <!-- Attach Info -->
                <div class="mb-3">
                    <label class="form-label"><?= lang('Pos.attach_booking') ?></label>
                    <input type="text" class="form-control" id="bookingSearch" placeholder="<?= lang('Pos.search_booking') ?>" autocomplete="off">
                    <div class="list-group position-relative" id="bookingResults" style="z-index:10"></div>
                    <div id="selectedBooking" class="small text-primary mt-1"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= lang('Pos.attach_player') ?></label>
                    <input type="text" class="form-control" id="playerSearch" placeholder="<?= lang('Pos.search_player') ?>" autocomplete="off">
                    <div class="list-group position-relative" id="playerResults" style="z-index:10"></div>
                    <div id="selectedPlayer" class="small text-primary mt-1"></div>
                </div>

                <hr>

                <!-- Cart Items -->
                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th><?= lang('Pos.products') ?></th>
                                <th class="text-center"><?= lang('Pos.quantity') ?></th>
                                <th class="text-end"><?= lang('Pos.subtotal') ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cartItems">
                            <tr id="emptyCart">
                                <td colspan="4" class="text-center text-muted"><?= lang('Pos.cart_empty') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <hr>

                <!-- Totals -->
                <div class="d-flex justify-content-between mb-2">
                    <span><?= lang('Pos.total_amount') ?>:</span>
                    <strong class="text-primary fs-5" id="cartTotal">0đ</strong>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= lang('Pos.paid_amount') ?></label>
                    <input type="number" class="form-control" id="paidAmount" placeholder="0" min="0">
                    <div class="form-text"><?= lang('Pos.change_amount') ?>: <strong id="changeAmount">0đ</strong></div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= lang('Pos.note') ?></label>
                    <textarea class="form-control" id="orderNote" rows="2"></textarea>
                </div>

                <!-- Actions -->
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success btn-lg" id="checkoutBtn" disabled>
                        <i class="bi bi-check-lg"></i> <?= lang('Pos.checkout') ?>
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="cancelOrderBtn">
                        <i class="bi bi-x-lg"></i> <?= lang('Pos.cancel_order') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    'use strict';

    let orderId = <?= (int) $order['id'] ?>;
    const items = []; // {productId, name, price, stock, quantity}

    const LANG = {
        cartEmpty:        <?= json_encode(lang('Pos.cart_empty')) ?>,
        insufficientStock: <?= json_encode(lang('Pos.insufficient_stock')) ?>,
        checkoutSuccess:  <?= json_encode(lang('Pos.checkout_success')) ?>,
        cancelSuccess:    <?= json_encode(lang('Pos.cancel_success')) ?>,
        cancelConfirm:    <?= json_encode(lang('Pos.cancel_order') . '?') ?>,
        paidNotEnough:    <?= json_encode(lang('Pos.paid_amount') . ' < ' . lang('Pos.total_amount')) ?>,
    };

    const posUrl = (action, id) => `<?= base_url('admin/pos') ?>/${action}/${id ?? orderId}`;

    const formatCurrency = (amount) =>
        new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);

    const postForm = (url, data) => {
        const formData = new FormData();
        Object.entries(data).forEach(([key, value]) => formData.append(key, value));
        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        }).then((response) => response.json());
    };

    // ---------- Giỏ hàng (UI) ----------
    function updateCart() {
        const cartItems   = document.getElementById('cartItems');
        const cartTotal   = document.getElementById('cartTotal');
        const cartCount   = document.getElementById('cartCount');
        const checkoutBtn = document.getElementById('checkoutBtn');

        if (items.length === 0) {
            cartItems.innerHTML = `<tr id="emptyCart"><td colspan="4" class="text-center text-muted">${LANG.cartEmpty}</td></tr>`;
            cartTotal.textContent = '0đ';
            checkoutBtn.disabled = true;
        } else {
            cartItems.innerHTML = items.map((item, index) => `
                <tr>
                    <td>${item.name}</td>
                    <td class="text-center" style="width:110px">
                        <div class="input-group input-group-sm">
                            <button type="button" class="btn btn-outline-secondary" data-action="dec" data-index="${index}">−</button>
                            <input type="text" class="form-control text-center" value="${item.quantity}" readonly>
                            <button type="button" class="btn btn-outline-secondary" data-action="inc" data-index="${index}">+</button>
                        </div>
                    </td>
                    <td class="text-end">${formatCurrency(item.price * item.quantity)}</td>
                    <td class="text-end"><button type="button" class="btn btn-sm btn-danger" data-action="remove" data-index="${index}"><i class="bi bi-trash"></i></button></td>
                </tr>
            `).join('');

            checkoutBtn.disabled = false;
        }

        const total = items.reduce((sum, item) => sum + item.price * item.quantity, 0);
        cartTotal.textContent = formatCurrency(total);
        cartCount.textContent = items.length;

        updateChange();
    }

    function updateChange() {
        const total = items.reduce((sum, item) => sum + item.price * item.quantity, 0);
        const paid  = parseFloat(document.getElementById('paidAmount').value) || 0;
        document.getElementById('changeAmount').textContent = formatCurrency(Math.max(0, paid - total));
    }

    // Event delegation cho các nút trong giỏ
    document.getElementById('cartItems').addEventListener('click', function (event) {
        const button = event.target.closest('button[data-action]');
        if (!button) return;

        const index  = parseInt(button.dataset.index, 10);
        const action = button.dataset.action;
        const item   = items[index];

        if (action === 'inc') {
            if (item.quantity >= item.stock) { alert(LANG.insufficientStock); return; }
            item.quantity++;
            syncItem(item);
        } else if (action === 'dec') {
            item.quantity--;
            if (item.quantity <= 0) { items.splice(index, 1); } else { syncItem(item); }
        } else if (action === 'remove') {
            items.splice(index, 1);
        }
        updateCart();
    });

    // Đồng bộ số lượng lên server (best-effort)
    function syncItem(item) {
        postForm(posUrl('addItem'), { product_id: item.productId, quantity: 1, replace: 1 })
            .then((data) => { if (!data.success && data.message) console.warn(data.message); })
            .catch(() => {});
    }

    // ---------- Thêm sản phẩm ----------
    document.querySelectorAll('.add-to-cart').forEach((button) => {
        button.addEventListener('click', function () {
            const productId = this.dataset.productId;
            const existing  = items.find((item) => item.productId === productId);

            if (existing) {
                if (existing.quantity >= existing.stock) { alert(LANG.insufficientStock); return; }
                existing.quantity++;
            } else {
                items.push({
                    productId,
                    name:  this.dataset.productName,
                    price: parseFloat(this.dataset.price),
                    stock: parseInt(this.dataset.stock, 10),
                    quantity: 1,
                });
            }

            updateCart();
            postForm(posUrl('addItem'), { product_id: productId, quantity: 1 })
                .then((data) => { if (!data.success && data.message) alert(data.message); })
                .catch(() => {});
        });
    });

    // ---------- Tìm kiếm / lọc sản phẩm ----------
    document.getElementById('searchProduct').addEventListener('input', function () {
        const keyword = this.value.toLowerCase();
        document.querySelectorAll('.product-item').forEach((item) => {
            item.style.display = item.dataset.name.includes(keyword) ? '' : 'none';
        });
    });

    document.getElementById('categoryFilter').addEventListener('change', function () {
        const category = this.value;
        document.querySelectorAll('.product-item').forEach((item) => {
            item.style.display = (!category || item.dataset.category === category) ? '' : 'none';
        });
    });

    // ---------- Gắn booking / người chơi (autocomplete đơn giản) ----------
    function bindSearch(inputId, resultsId, selectedId, url, onSelect, label) {
        const input   = document.getElementById(inputId);
        const results = document.getElementById(resultsId);
        let timer;

        input.addEventListener('input', function () {
            clearTimeout(timer);
            const keyword = this.value.trim();
            if (keyword.length < 2) { results.innerHTML = ''; return; }

            timer = setTimeout(() => {
                fetch(`${url}?q=${encodeURIComponent(keyword)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then((response) => response.json())
                    .then((data) => {
                        const list = data.bookings || data.players || [];
                        results.innerHTML = list.map((row) => {
                            const text = label(row);
                            return `<button type="button" class="list-group-item list-group-item-action" data-id="${row.id}" data-label="${text}">${text}</button>`;
                        }).join('');
                    })
                    .catch(() => {});
            }, 300);
        });

        results.addEventListener('click', function (event) {
            const button = event.target.closest('button[data-id]');
            if (!button) return;
            onSelect(button.dataset.id, button.dataset.label);
            results.innerHTML = '';
            input.value = '';
        });
    }

    bindSearch('bookingSearch', 'bookingResults', 'selectedBooking',
        '<?= base_url('admin/pos/searchBookings') ?>',
        (id, text) => {
            document.getElementById('selectedBooking').textContent = text;
            postForm(posUrl('attachBooking'), { booking_id: id }).catch(() => {});
        },
        (row) => `${row.booking_code} — ${row.customer_name} (${row.customer_phone})`
    );

    bindSearch('playerSearch', 'playerResults', 'selectedPlayer',
        '<?= base_url('admin/pos/searchPlayers') ?>',
        (id, text) => {
            document.getElementById('selectedPlayer').textContent = text;
            postForm(posUrl('attachPlayer'), { player_id: id }).catch(() => {});
        },
        (row) => `${row.full_name} — ${row.phone}`
    );

    // ---------- Thanh toán / hủy đơn ----------
    document.getElementById('paidAmount').addEventListener('input', updateChange);

    document.getElementById('checkoutBtn').addEventListener('click', function () {
        const total = items.reduce((sum, item) => sum + item.price * item.quantity, 0);
        const paid  = parseFloat(document.getElementById('paidAmount').value) || 0;

        if (paid < total) { alert(LANG.paidNotEnough); return; }

        postForm(posUrl('checkout'), { paid_amount: paid, note: document.getElementById('orderNote').value })
            .then((data) => {
                if (data.success) {
                    alert(LANG.checkoutSuccess);
                    items.length = 0;
                    document.getElementById('paidAmount').value = '';
                    document.getElementById('orderNote').value = '';
                    document.getElementById('selectedBooking').textContent = '';
                    document.getElementById('selectedPlayer').textContent = '';
                    updateCart();
                    location.reload(); // server tạo đơn mới
                } else {
                    alert(data.message || 'Error');
                }
            })
            .catch(() => alert('Error'));
    });

    document.getElementById('cancelOrderBtn').addEventListener('click', function () {
        if (!confirm(LANG.cancelConfirm)) return;

        postForm(posUrl('cancel'), { reason: 'staff' })
            .then((data) => {
                if (data.success) {
                    alert(LANG.cancelSuccess);
                    if (data.order && data.order.id) { orderId = data.order.id; }
                    items.length = 0;
                    updateCart();
                } else {
                    alert(data.message || 'Error');
                }
            })
            .catch(() => alert('Error'));
    });
})();
</script>
<?= $this->endSection() ?>
