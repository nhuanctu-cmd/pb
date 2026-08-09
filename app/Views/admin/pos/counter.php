<!-- POS Counter Page -->
<div class="row" id="pos-app">
    <!-- Left: Categories & Products -->
    <div class="col-md-8">
        <!-- Search & Categories -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="searchProduct" placeholder="<?= lang('Pos.search_product') ?>">
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" id="categoryFilter">
                            <option value=""><?= lang('Pos.categories') ?></option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category->id ?>"><?= $category->name_vi ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="row" id="productGrid">
            <?php foreach ($categories as $category): ?>
                <?php if (!empty($category->products)): ?>
                    <div class="col-12 mb-3">
                        <h6 class="border-bottom pb-2"><?= $category->name_vi ?></h6>
                        <div class="row">
                            <?php foreach ($category->products as $product): ?>
                                <div class="col-md-3 col-sm-4 col-6 mb-3 product-item"
                                     data-category="<?= $category->id ?>"
                                     data-name="<?= strtolower($product['name_vi']) ?>"
                                     data-stock="<?= $product['stock'] ?>">
                                    <div class="card product-card h-100 <?= $product['stock'] <= 0 ? 'out-of-stock' : '' ?>">
                                        <?php if ($product['image']): ?>
                                            <img src="<?= $product['image'] ?>" class="card-img-top" alt="<?= $product['name_vi'] ?>" style="height: 120px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 120px;">
                                                <span class="text-muted">No Image</span>
                                            </div>
                                        <?php endif ?>
                                        <div class="card-body p-2">
                                            <h6 class="card-title mb-1"><?= $product['name_vi'] ?></h6>
                                            <p class="text-primary mb-1"><?= number_format($product['sale_price']) ?>đ</p>
                                            <small class="text-muted">Tồn: <?= $product['stock'] ?></small>
                                        </div>
                                        <div class="card-footer p-2 bg-white border-top-0">
                                            <button class="btn btn-sm btn-primary w-100 add-to-cart"
                                                    data-product-id="<?= $product['id'] ?>"
                                                    data-product-name="<?= $product['name_vi'] ?>"
                                                    data-price="<?= $product['sale_price'] ?>"
                                                    data-stock="<?= $product['stock'] ?>"
                                                    <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                                                <i class="fas fa-cart-plus"></i> <?= lang('Pos.add_to_cart') ?>
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
                <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Giỏ hàng</h5>
                <span class="badge bg-light text-dark" id="cartCount">0</span>
            </div>
            <div class="card-body">
                <!-- Attach Info -->
                <div class="mb-3">
                    <label class="form-label"><?= lang('Pos.attach_booking') ?></label>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" id="bookingSearch" placeholder="<?= lang('Pos.search_booking') ?>">
                        <button class="btn btn-outline-secondary" type="button" id="attachBookingBtn">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div id="selectedBooking" class="small text-primary"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= lang('Pos.attach_player') ?></label>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" id="playerSearch" placeholder="<?= lang('Pos.search_player') ?>">
                        <button class="btn btn-outline-secondary" type="button" id="attachPlayerBtn">
                            <i class="fas fa-user"></i>
                        </button>
                    </div>
                    <div id="selectedPlayer" class="small text-primary"></div>
                </div>

                <hr>

                <!-- Cart Items -->
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm">
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
                    <strong class="text-primary" id="cartTotal">0đ</strong>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= lang('Pos.paid_amount') ?></label>
                    <input type="number" class="form-control" id="paidAmount" placeholder="0" min="0">
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= lang('Pos.note') ?></label>
                    <textarea class="form-control" id="orderNote" rows="2" placeholder="<?= lang('Pos.note') ?>"></textarea>
                </div>

                <!-- Actions -->
                <div class="d-grid gap-2">
                    <button class="btn btn-success btn-lg" id="checkoutBtn" disabled>
                        <i class="fas fa-check"></i> <?= lang('Pos.checkout') ?>
                    </button>
                    <button class="btn btn-danger" id="cancelOrderBtn">
                        <i class="fas fa-times"></i> <?= lang('Pos.cancel_order') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentOrder = {
    id: <?= $order['id'] ?>,
    total: 0,
    items: [],
    booking: null,
    player: null
};

// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
};

// Add to cart
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.dataset.productId;
        const productName = this.dataset.productName;
        const price = parseFloat(this.dataset.price);
        const stock = parseInt(this.dataset.stock);

        // Check if item already in cart
        const existingItem = currentOrder.items.find(item => item.productId === productId);
        if (existingItem) {
            if (existingItem.quantity >= stock) {
                alert('<?= lang('Pos.insufficient_stock') ?>');
                return;
            }
            existingItem.quantity++;
        } else {
            currentOrder.items.push({
                productId: productId,
                name: productName,
                price: price,
                quantity: 1
            });
        }

        updateCart();
        addItemToOrder(productId, 1);
    });
});

// Add item to order via AJAX
function addItemToOrder(productId, quantity) {
    fetch(`<?= base_url('admin/pos/addItem/' . $order['id']) ?>`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(document.createElement('form')).append('product_id', productId).append('quantity', quantity)
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert(data.message);
        }
    });
}

// Update cart display
function updateCart() {
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    const cartCount = document.getElementById('cartCount');
    const checkoutBtn = document.getElementById('checkoutBtn');

    if (currentOrder.items.length === 0) {
        cartItems.innerHTML = `<tr id="emptyCart"><td colspan="4" class="text-center text-muted"><?= lang('Pos.cart_empty') ?></td></tr>`;
        cartTotal.textContent = '0đ';
        checkoutBtn.disabled = true;
    } else {
        cartItems.innerHTML = currentOrder.items.map((item, index) => `
            <tr>
                <td><?= lang('Pos.product_name') ?></td>
                <td>
                    <div class="input-group input-group-sm">
                        <button class="btn btn-outline-secondary" onclick="updateQuantity(${index}, ${item.quantity - 1})">-</button>
                        <input type="text" class="form-control text-center" value="${item.quantity}" readonly>
                        <button class="btn btn-outline-secondary" onclick="updateQuantity(${index}, ${item.quantity + 1})">+</button>
                    </div>
                </td>
                <td class="text-end"><?= number_format($price) ?></td>
                <td class="text-end"><?= number_format(item.price * item.quantity) ?>đ</td>
                <td><button class="btn btn-sm btn-danger" onclick="removeItem(${index})"><i class="fas fa-trash"></i></button></td>
            </tr>
        `).join('');

        currentOrder.total = currentOrder.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        cartTotal.textContent = formatCurrency(currentOrder.total);
        checkoutBtn.disabled = false;
    }

    cartCount.textContent = currentOrder.items.length;
}

// Remove item
function removeItem(index) {
    currentOrder.items.splice(index, 1);
    updateCart();
}

// Update quantity
function updateQuantity(index, newQuantity) {
    if (newQuantity <= 0) {
        removeItem(index);
    } else {
        currentOrder.items[index].quantity = newQuantity;
        updateCart();
    }
}

// Search products
document.getElementById('searchProduct').addEventListener('input', function() {
    const keyword = this.value.toLowerCase();
    document.querySelectorAll('.product-item').forEach(item => {
        const name = item.dataset.name;
        item.style.display = name.includes(keyword) ? '' : 'none';
    });
});

// Filter by category
document.getElementById('categoryFilter').addEventListener('change', function() {
    const category = this.value;
    document.querySelectorAll('.product-item').forEach(item => {
        item.style.display = (!category || item.dataset.category === category) ? '' : 'none';
    });
});

// Checkout
document.getElementById('checkoutBtn').addEventListener('click', function() {
    const paidAmount = document.getElementById('paidAmount').value;
    const note = document.getElementById('orderNote').value;

    if (!paidAmount || paidAmount < currentOrder.total) {
        alert('Số tiền nhận không đủ');
        return;
    }

    fetch(`<?= base_url('admin/pos/checkout/' . $order['id']) ?>`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ paid_amount: paidAmount, note: note })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('<?= lang('Pos.checkout_success') ?>');
            // Reset cart and create new order
            currentOrder.items = [];
            updateCart();
            document.getElementById('paidAmount').value = '';
            document.getElementById('orderNote').value = '';
        } else {
            alert(data.message);
        }
    });
});

// Cancel order
document.getElementById('cancelOrderBtn').addEventListener('click', function() {
    if (confirm('Bạn có chắc muốn hủy đơn hàng?')) {
        fetch(`<?= base_url('admin/pos/cancel/' . $order['id']) ?>`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ reason: 'Hủy bởi nhân viên' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('<?= lang('Pos.cancel_success') ?>');
                currentOrder = { id: data.order.id, total: 0, items: [] };
                updateCart();
            } else {
                alert(data.message);
            }
        });
    }
});

// Search autocomplete
$('#bookingSearch').autocomplete({
    source: `<?= base_url('admin/pos/searchBookings') ?>`,
    select: function(event, ui) {
        // Attach booking
    }
});

$('#playerSearch').autocomplete({
    source: `<?= base_url('admin/pos/searchPlayers') ?>`,
    select: function(event, ui) {
        // Attach player
    }
});
</script>
