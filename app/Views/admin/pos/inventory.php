<?= $this->extend('layouts/admin_master') ?>
<?= $this->section('content') ?>
<!-- Inventory Management Page -->
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h3><?= lang('Pos.inventory_stock') ?></h3>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fas fa-download"></i> <?= lang('Pos.inventory_import') ?>
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6><?= lang('Pos.total_products') ?></h6>
                    <h3><?= count($inventories) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6><?= lang('Pos.low_stock') ?></h6>
                    <h3><?= count(array_filter($inventories, fn($i) => $i['quantity'] < 10)) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6><?= lang('Pos.out_of_stock') ?></h6>
                    <h3><?= count(array_filter($inventories, fn($i) => $i['quantity'] <= 0)) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6><?= lang('Pos.total_value') ?></h6>
                    <h3><?= number_format(array_sum(array_map(fn($i) => $i['quantity'] * $i['sale_price'], $inventories))) ?>đ</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="inventoryTable">
                    <thead>
                        <tr>
                            <th><?= lang('Pos.sku') ?></th>
                            <th><?= lang('Pos.product_name') ?></th>
                            <th><?= lang('Pos.category') ?></th>
                            <th><?= lang('Pos.unit') ?></th>
                            <th><?= lang('Pos.cost_price') ?></th>
                            <th><?= lang('Pos.sale_price') ?></th>
                            <th><?= lang('Pos.current_stock') ?></th>
                            <th><?= lang('Pos.stock_value') ?></th>
                            <th><?= lang('Pos.status') ?></th>
                            <th><?= lang('Pos.actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($inventories)): ?>
                            <?php foreach ($inventories as $inventory): ?>
                                <tr>
                                    <td><?= $inventory['sku'] ?: '-' ?></td>
                                    <td><?= $inventory['name_vi'] ?></td>
                                    <td><?= $inventory['category_name'] ?></td>
                                    <td><?= $inventory['unit'] ?></td>
                                    <td><?= number_format($inventory['cost_price']) ?>đ</td>
                                    <td><?= number_format($inventory['sale_price']) ?>đ</td>
                                    <td>
                                        <span class="badge bg-<?= $inventory['quantity'] > 10 ? 'success' : ($inventory['quantity'] > 0 ? 'warning' : 'danger') ?>">
                                            <?= $inventory['quantity'] ?>
                                        </span>
                                    </td>
                                    <td><?= number_format($inventory['quantity'] * $inventory['sale_price']) ?>đ</td>
                                    <td>
                                        <span class="badge bg-<?= $inventory['status'] == 'active' ? 'success' : 'secondary' ?>">
                                            <?= $inventory['status'] == 'active' ? lang('Pos.active') : lang('Pos.inactive') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="adjustStock(<?= $inventory['product_id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted"><?= lang('Pos.no_inventory') ?></td>
                            </tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang('Pos.inventory_import') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="importForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= lang('Pos.product') ?></label>
                        <select class="form-select" name="product_id" required>
                            <option value=""><?= lang('Pos.select_product') ?></option>
                            <?php foreach ($products ?? [] as $product): ?>
                                <option value="<?= $product['id'] ?>"><?= $product['name_vi'] ?> (<?= $product['sku'] ?: 'N/A' ?>)</option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= lang('Pos.import_quantity') ?></label>
                        <input type="number" class="form-control" name="quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= lang('Pos.import_note') ?></label>
                        <textarea class="form-control" name="note" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('Pos.close') ?></button>
                    <button type="submit" class="btn btn-primary"><?= lang('Pos.import') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Adjust Modal -->
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang('Pos.adjust_stock') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="adjustForm">
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="adjustProductId">
                    <div class="mb-3">
                        <label class="form-label"><?= lang('Pos.current_stock') ?></label>
                        <input type="text" class="form-control" id="currentStock" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= lang('Pos.new_quantity') ?></label>
                        <input type="number" class="form-control" name="new_quantity" id="newQuantity" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= lang('Pos.note') ?></label>
                        <textarea class="form-control" name="note" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('Pos.close') ?></button>
                    <button type="submit" class="btn btn-warning"><?= lang('Pos.adjust') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#importForm').submit(function(e) {
    e.preventDefault();

    $.post('<?= base_url('admin/pos/importStock') ?>', $(this).serialize(), function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert(response.message);
        }
    });
});

function adjustStock(productId) {
    $.get(`<?= base_url('admin/pos/getStock/') ?>${productId}`, function(data) {
        if (data.stock) {
            $('#adjustProductId').val(productId);
            $('#currentStock').val(data.stock.quantity);
            $('#newQuantity').val(data.stock.quantity);
            $('#adjustModal').modal('show');
        }
    });
}

$('#adjustForm').submit(function(e) {
    e.preventDefault();

    $.post('<?= base_url('admin/pos/adjustStock') ?>', $(this).serialize(), function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert(response.message);
        }
    });
});
</script>
<?= $this->endSection() ?>
