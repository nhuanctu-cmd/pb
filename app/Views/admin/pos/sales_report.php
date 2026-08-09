<!-- Sales Report Page -->
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h3><?= lang('Pos.sales_report') ?></h3>
        </div>
        <div class="col-md-6 text-end">
            <input type="date" class="form-control d-inline-block w-auto" id="fromDate" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
            <span class="mx-2">đến</span>
            <input type="date" class="form-control d-inline-block w-auto" id="toDate" value="<?= date('Y-m-d') ?>">
            <button class="btn btn-primary" onclick="filterReport()">
                <i class="fas fa-filter"></i> <?= lang('Pos.filter') ?>
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6><?= lang('Pos.total_orders') ?></h6>
                    <h3><?= count($orders) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6><?= lang('Pos.total_revenue') ?></h6>
                    <h3><?= number_format(array_sum(array_column($orders, 'total_amount'))) ?>đ</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6><?= lang('Pos.total_items_sold') ?></h6>
                    <h3><?= array_sum(array_column($orders, 'item_count')) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6><?= lang('Pos.average_order_value') ?></h6>
                    <h3><?= count($orders) > 0 ? number_format(array_sum(array_column($orders, 'total_amount')) / count($orders)) : 0 ?>đ</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="salesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?= lang('Pos.order_code') ?></th>
                            <th><?= lang('Pos.created_at') ?></th>
                            <th><?= lang('Pos.customer') ?></th>
                            <th><?= lang('Pos.booking') ?></th>
                            <th><?= lang('Pos.items') ?></th>
                            <th><?= lang('Pos.total_amount') ?></th>
                            <th><?= lang('Pos.payment_status') ?></th>
                            <th><?= lang('Pos.status') ?></th>
                            <th><?= lang('Pos.actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $index => $order): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= $order['order_code'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td><?= $order['player_name'] ?: '-' ?></td>
                                    <td><?= $order['booking_code'] ?: '-' ?></td>
                                    <td><?= $order['item_count'] ?? 0 ?></td>
                                    <td class="text-end"><?= number_format($order['total_amount']) ?>đ</td>
                                    <td>
                                        <span class="badge bg-<?= $order['payment_status'] == 'paid' ? 'success' : 'warning' ?>">
                                            <?= lang('Pos.' . $order['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $order['status'] == 'completed' ? 'success' : ($order['status'] == 'pending' ? 'warning' : 'danger') ?>">
                                            <?= lang('Pos.' . $order['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewOrder(<?= $order['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary" onclick="printBill(<?= $order['id'] ?>)">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted"><?= lang('Pos.no_orders') ?></td>
                            </tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function filterReport() {
    const fromDate = $('#fromDate').val();
    const toDate = $('#toDate').val();
    window.location.href = `<?= current_url() ?>?from=${fromDate}&to=${toDate}`;
}

function viewOrder(orderId) {
    window.open(`<?= base_url('admin/pos/getOrder/') ?>${orderId}`, '_blank');
}

function printBill(orderId) {
    window.open(`<?= base_url('admin/pos/printBill/') ?>${orderId}`, '_blank');
}

$(document).ready(function() {
    $('#salesTable').DataTable({
        "order": [[2, "desc"]],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
        }
    });
});
</script>
