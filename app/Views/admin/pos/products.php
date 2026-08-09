<!-- Product Management Page -->
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h3><?= lang('Pos.products_management') ?></h3>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                <i class="fas fa-plus"></i> <?= lang('Pos.add_product') ?>
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="productsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?= lang('Pos.sku') ?></th>
                            <th><?= lang('Pos.product_name') ?></th>
                            <th><?= lang('Pos.category') ?></th>
                            <th><?= lang('Pos.unit') ?></th>
                            <th><?= lang('Pos.cost_price') ?></th>
                            <th><?= lang('Pos.sale_price') ?></th>
                            <th><?= lang('Pos.stock') ?></th>
                            <th><?= lang('Pos.status') ?></th>
                            <th><?= lang('Pos.actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($products)): ?>
                            <?php foreach ($products as $index => $product): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= $product['sku'] ?: '-' ?></td>
                                    <td><?= $product['name_vi'] ?></td>
                                    <td><?= $product['category_name'] ?></td>
                                    <td><?= $product['unit'] ?></td>
                                    <td><?= number_format($product['cost_price']) ?>đ</td>
                                    <td><?= number_format($product['sale_price']) ?>đ</td>
                                    <td>
                                        <span class="badge bg-<?= $product['stock'] > 10 ? 'success' : ($product['stock'] > 0 ? 'warning' : 'danger') ?>">
                                            <?= $product['stock'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $product['status'] == 'active' ? 'success' : 'secondary' ?>">
                                            <?= $product['status'] == 'active' ? lang('Pos.active') : lang('Pos.inactive') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editProduct(<?= $product['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-info" onclick="importStock(<?= $product['id'] ?>)">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted"><?= lang('Pos.no_products') ?></td>
                            </tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalTitle"><?= lang('Pos.add_product') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="productForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="productId">
                    <div class="mb-3">
                        <label class="form-label"><?= lang('Pos.sku') ?></label>
                        <input type="text" class="form-control" name="sku" id="sku">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= lang('Pos.product_name_vi') ?></label>
                        <input type="text" class="form-control" name="name_vi" id="name_vi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= lang('Pos.product_name_en') ?></label>
                        <input type="text" class="form-control" name="name_en" id="name_en" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= lang('Pos.category') ?></label>
                        <select class="form-select" name="category_id" id="category_id" required>
                            <option value=""><?= lang('Pos.select_category') ?></option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= $category['name_vi'] ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= lang('Pos.cost_price') ?></label>
                            <input type="number" class="form-control" name="cost_price" id="cost_price" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= lang('Pos.sale_price') ?></label>
                            <input type="number" class="form-control" name="sale_price" id="sale_price" step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= lang('Pos.unit') ?></label>
                            <select class="form-select" name="unit" id="unit">
                                <option value="pcs">pcs</option>
                                <option value="box">box</option>
                                <option value="bottle">bottle</option>
                                <option value="pack">pack</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= lang('Pos.status') ?></label>
                            <select class="form-select" name="status" id="status">
                                <option value="active"><?= lang('Pos.active') ?></option>
                                <option value="inactive"><?= lang('Pos.inactive') ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('Pos.close') ?></button>
                    <button type="submit" class="btn btn-primary"><?= lang('Pos.save') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#productForm').submit(function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const isEdit = !!$('#productId').val();
    const url = isEdit ? '<?= base_url('admin/pos/updateProduct') ?>' : '<?= base_url('admin/pos/createProduct') ?>';

    $.post(url, $(this).serialize(), function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert(response.message);
        }
    });
});

function editProduct(id) {
    $.get(`<?= base_url('admin/pos/getProduct/') ?>${id}`, function(data) {
        if (data.product) {
            $('#productId').val(data.product.id);
            $('#sku').val(data.product.sku);
            $('#name_vi').val(data.product.name_vi);
            $('#name_en').val(data.product.name_en);
            $('#category_id').val(data.product.category_id);
            $('#cost_price').val(data.product.cost_price);
            $('#sale_price').val(data.product.sale_price);
            $('#unit').val(data.product.unit);
            $('#status').val(data.product.status);
            $('#productModalTitle').text('<?= lang('Pos.edit_product') ?>');
            $('#productModal').modal('show');
        }
    });
}

function importStock(id) {
    const quantity = prompt('<?= lang('Pos.import_quantity') ?>');
    if (quantity && quantity > 0) {
        $.post('<?= base_url('admin/pos/importStock') ?>', {
            product_id: id,
            quantity: quantity,
            note: 'Nhập kho'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.message);
            }
        });
    }
}
</script>
