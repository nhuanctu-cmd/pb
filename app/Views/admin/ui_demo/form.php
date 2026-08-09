<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="erp-grid-2">
    <form class="erp-form">
        <section class="erp-form-section">
            <div class="erp-form-section-header">
                <h2>1. Thông tin khách</h2>
                <div class="erp-muted">Thông tin người đại diện đặt sân.</div>
            </div>
            <div class="erp-form-section-body erp-form-grid">
                <div class="erp-field">
                    <label>Khách hàng <span class="erp-required">*</span></label>
                    <input class="erp-control" value="Nguyễn Minh Anh">
                </div>
                <div class="erp-field is-invalid">
                    <label>Số điện thoại <span class="erp-required">*</span></label>
                    <input class="erp-control" value="0901">
                    <div class="erp-validation">Số điện thoại chưa đủ định dạng.</div>
                </div>
                <div class="erp-field">
                    <label>Email</label>
                    <input class="erp-control" value="minhanh@example.com">
                </div>
                <div class="erp-field">
                    <label>Loại khách</label>
                    <select class="erp-select"><option>Hội viên Gold</option><option>Khách lẻ</option><option>Doanh nghiệp</option></select>
                    <div class="erp-hint">Hệ thống tự áp giá hội viên nếu còn hiệu lực.</div>
                </div>
            </div>
        </section>

        <section class="erp-form-section">
            <div class="erp-form-section-header"><h2>2. Chọn chi nhánh & sân</h2></div>
            <div class="erp-form-section-body erp-form-grid">
                <div class="erp-field">
                    <label>Chi nhánh <span class="erp-required">*</span></label>
                    <select class="erp-select"><option>Pickleball Riverside Quận 7</option><option>Pickleball Prime Thủ Đức</option></select>
                </div>
                <div class="erp-field erp-loading-control">
                    <label>Sân</label>
                    <select class="erp-select"><option>Sân A01 - Indoor Premium</option><option>Sân B02 - Outdoor</option></select>
                    <div class="erp-hint">Đang kiểm tra lịch trống và dynamic pricing.</div>
                </div>
                <div class="erp-field">
                    <label>Loại sân</label>
                    <input class="erp-control" value="Indoor Premium" disabled>
                </div>
                <div class="erp-field">
                    <label>Dịch vụ kèm theo</label>
                    <select class="erp-select"><option>Thuê 2 vợt + nước uống</option><option>Không dùng dịch vụ</option></select>
                </div>
            </div>
        </section>

        <section class="erp-form-section">
            <div class="erp-form-section-header"><h2>3. Chọn ngày giờ</h2></div>
            <div class="erp-form-section-body erp-form-grid-3">
                <div class="erp-field">
                    <label>Ngày chơi <span class="erp-required">*</span></label>
                    <input type="date" class="erp-control" value="2026-07-06">
                </div>
                <div class="erp-field">
                    <label>Giờ bắt đầu</label>
                    <input type="time" class="erp-control" value="18:00">
                </div>
                <div class="erp-field">
                    <label>Giờ kết thúc</label>
                    <input type="time" class="erp-control" value="19:30">
                </div>
            </div>
            <div class="erp-form-section-body pt-0">
                <div class="erp-summary-chips">
                    <span class="erp-summary-chip">18:00 <?= renderStatusBadge('warning') ?></span>
                    <span class="erp-summary-chip">19:00 <?= renderStatusBadge('success') ?></span>
                    <span class="erp-summary-chip">20:00 <?= renderStatusBadge('info') ?></span>
                </div>
            </div>
        </section>

        <section class="erp-form-section">
            <div class="erp-form-section-header"><h2>4. Thanh toán / cọc</h2></div>
            <div class="erp-form-section-body erp-form-grid">
                <div class="erp-field">
                    <label>Giá động</label>
                    <input class="erp-control" value="330,000đ" disabled>
                    <div class="erp-hint">Rule áp dụng: Giờ cao điểm 18:00-22:00.</div>
                </div>
                <div class="erp-field">
                    <label>Tiền cọc</label>
                    <input class="erp-control" value="99,000đ">
                </div>
                <div class="erp-field">
                    <label>Phương thức</label>
                    <select class="erp-select"><option>Chuyển khoản</option><option>Tiền mặt</option><option>Ví người chơi</option></select>
                </div>
                <div class="erp-field">
                    <label>Trạng thái</label>
                    <select class="erp-select"><option>Chờ thanh toán cọc</option><option>Đã thanh toán</option></select>
                </div>
            </div>
        </section>

        <section class="erp-form-section">
            <div class="erp-form-section-header"><h2>5. Ghi chú</h2></div>
            <div class="erp-form-section-body">
                <div class="erp-field">
                    <label>Ghi chú vận hành</label>
                    <textarea class="erp-textarea">Khách cần 2 vợt thuê, ưu tiên sân gần quầy lễ tân.</textarea>
                    <div class="erp-hint">Ghi chú này hiển thị cho lễ tân và nhân viên sân.</div>
                </div>
            </div>
            <div class="erp-sticky-save">
                <a class="erp-btn" href="/admin/ui-demo/list">Hủy</a>
                <button type="button" class="erp-btn">Lưu nháp</button>
                <button type="button" class="erp-btn erp-btn-primary"><i class="bi bi-check2-circle"></i> Tạo booking</button>
            </div>
        </section>
    </form>

    <aside class="erp-dashboard-stack">
        <section class="erp-card">
            <div class="erp-card-header"><strong>Tóm tắt booking</strong><?= renderStatusBadge('pending', 'booking') ?></div>
            <div class="erp-card-body erp-info-list">
                <div class="erp-info-row"><span>Khách</span><strong>Nguyễn Minh Anh</strong></div>
                <div class="erp-info-row"><span>Chi nhánh</span><strong>Riverside Q.7</strong></div>
                <div class="erp-info-row"><span>Sân</span><strong>A01 Indoor</strong></div>
                <div class="erp-info-row"><span>Thời lượng</span><strong>90 phút</strong></div>
                <div class="erp-info-row"><span>Tổng tiền</span><strong>330,000đ</strong></div>
                <div class="erp-info-row"><span>Cọc tối thiểu</span><strong>99,000đ</strong></div>
            </div>
        </section>

        <section class="erp-card">
            <div class="erp-card-header"><strong>Chính sách</strong></div>
            <div class="erp-card-body">
                <div class="erp-notice mb-3">Booking giữ chỗ tối đa 15 phút nếu chưa thanh toán cọc.</div>
                <div class="erp-notice mb-3">Hủy trước giờ chơi 2 giờ được hoàn 100% cọc.</div>
                <div class="erp-notice">Hội viên Gold được ưu tiên đổi sân nếu có sân tương đương.</div>
            </div>
        </section>

        <section class="erp-card">
            <div class="erp-card-header"><strong>Trạng thái UI mẫu</strong></div>
            <div class="erp-card-body erp-info-list">
                <div class="erp-info-row"><span>Validation</span><?= renderStatusBadge('danger') ?></div>
                <div class="erp-info-row"><span>Loading</span><?= renderStatusBadge('info') ?></div>
                <div class="erp-info-row"><span>Success</span><?= renderStatusBadge('success') ?></div>
                <div class="erp-info-row"><span>Disabled</span><input class="erp-control" value="Không cho sửa" disabled style="max-width:150px"></div>
            </div>
        </section>
    </aside>
</div>
<?= $this->endSection() ?>
