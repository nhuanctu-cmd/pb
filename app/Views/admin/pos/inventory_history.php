<!-- Inventory History Page -->
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h3><?= lang('Pos.inventory_history') ?></h3>
        </div>
        <div class="col-md-6">
            <div class="d-flex gap-2 justify-content-end">
                <select class="form-select" id="filterType" style="width: 200px;">
                    <option value=""><?= lang('Pos.all_movements') ?></option>
                    <option value="import"><?= lang('Pos.import_movements') ?></option>
                    <option value="sale"><?= lang('Pos.sale_movements') ?></option>
                    <option value="return"><?= lang('Pos.return_movements') ?></option>
                    <option value="adjust"><?= lang('Pos.adjust_movements') ?></option>
                </select>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="historyTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?= lang('Pos.movement_date') ?></th>
                            <th><?= lang('Pos.movement_type') ?></th>
                            <th><?= lang('Pos.product_name') ?></th>
                            <th><?= lang('Pos.category') ?></th>
                            <th><?= lang('Pos.before_qty') ?></th>
                            <th><?= lang('Pos.quantity') ?></th>
                            <th><?= lang('Pos.after_qty') ?></th>
                            <th><?= lang('Pos.note') ?></th>
                            <th><?= lang('Pos.created_by') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($movements)): ?>
                            <?php foreach ($movements as $index => $movement): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($movement['created_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $movement['movement_type'] === 'import' ? 'success' :
                                            ($movement['movement_type'] === 'sale' ? 'info' :
                                            ($movement['movement_type'] === 'return' ? 'warning' : 'secondary')) ?>">
                                            <?= $movement['movement_type'] ?>
                                        </span>
                                    </td>
                                    <td><?= $movement['name_vi'] ?></td>
                                    <td><?= $movement['category_name'] ?></td>
                                    <td><?= $movement['before_qty'] ?></td>
                                    <td class="<?= $movement['quantity'] > 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $movement['quantity'] > 0 ? '+' : '' ?><?= $movement['quantity'] ?>
                                    </td>
                                    <td><?= $movement['after_qty'] ?></td>
                                    <td><?= $movement['note'] ?: '-' ?></td>
                                    <td><?= $movement['created_by'] ?: '-' ?></td>
                                </tr>
                            <?php endforeach ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted"><?= lang('Pos.no_movements') ?></td>
                            </tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$('#filterType').change(function() {
    const type = $(this).val();
    window.location.href = `<?= current_url() ?>?type=${type}`;
});

$(document).ready(function() {
    $('#historyTable').DataTable({
        "order": [[1, "desc"]],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
        }
    });
});
</script>
