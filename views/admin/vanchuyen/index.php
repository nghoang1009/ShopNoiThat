<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Quản Lý Vận Chuyển & Giao Hàng</h1>
        <p style="color: #64748b; font-size: 14px;">Theo dõi vận đơn, cập nhật trạng thái giao hàng và điều phối xe</p>
    </div>
    <a href="<?php echo base_url('admin/van-chuyen/phuong-thuc'); ?>" class="btn-admin btn-admin-primary">
        ⚙️ Cấu Hình Phương Thức Vận Chuyển
    </a>
</div>

<!-- Bộ lọc vận chuyển -->
<div class="card-box" style="padding: 16px 24px; margin-bottom: 20px;">
    <form action="<?php echo base_url('admin/van-chuyen'); ?>" method="GET" style="display:flex; gap:15px; flex-wrap:wrap; align-items:center;">
        <input type="text" name="keyword" placeholder="Tìm mã vận đơn, mã đơn, tên khách..." value="<?php echo htmlspecialchars($filters['keyword'] ?? ''); ?>" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; min-width: 280px;">

        <select name="trang_thai_giao_hang" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
            <option value="">-- Tất cả trạng thái vận chuyển --</option>
            <option value="cho_lay_hang" <?php echo (isset($filters['trang_thai_giao_hang']) && $filters['trang_thai_giao_hang'] === 'cho_lay_hang') ? 'selected' : ''; ?>>Chờ lấy hàng / Đóng gói</option>
            <option value="dang_van_chuyen" <?php echo (isset($filters['trang_thai_giao_hang']) && $filters['trang_thai_giao_hang'] === 'dang_van_chuyen') ? 'selected' : ''; ?>>Đang trung chuyển</option>
            <option value="dang_giao" <?php echo (isset($filters['trang_thai_giao_hang']) && $filters['trang_thai_giao_hang'] === 'dang_giao') ? 'selected' : ''; ?>>Đang giao hàng</option>
            <option value="da_giao" <?php echo (isset($filters['trang_thai_giao_hang']) && $filters['trang_thai_giao_hang'] === 'da_giao') ? 'selected' : ''; ?>>Giao hàng thành công</option>
            <option value="giao_that_bai" <?php echo (isset($filters['trang_thai_giao_hang']) && $filters['trang_thai_giao_hang'] === 'giao_that_bai') ? 'selected' : ''; ?>>Giao thất bại</option>
            <option value="chuyen_hoan" <?php echo (isset($filters['trang_thai_giao_hang']) && $filters['trang_thai_giao_hang'] === 'chuyen_hoan') ? 'selected' : ''; ?>>Chuyển hoàn</option>
        </select>

        <button type="submit" class="btn-admin btn-admin-primary">Lọc Dữ Liệu</button>
        <a href="<?php echo base_url('admin/van-chuyen'); ?>" style="font-size: 13px; color: #64748b; margin-left: 5px;">Đặt lại</a>
    </form>
</div>

<div class="card-box">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Mã vận đơn</th>
                    <th>Mã đơn hàng</th>
                    <th>Người nhận & Địa chỉ</th>
                    <th>Đơn vị vận chuyển</th>
                    <th>Gói dịch vụ</th>
                    <th>Phí ship</th>
                    <th>Trạng thái giao</th>
                    <th style="text-align: right;">Cập nhật</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($shipments)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 30px;">Chưa có đơn vận chuyển nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($shipments as $s): ?>
                        <tr>
                            <td>
                                <strong style="color: #935832; font-size: 14px;">
                                    <?php echo htmlspecialchars($s['ma_van_don'] ?: 'Chờ tạo mã'); ?>
                                </strong>
                            </td>
                            <td>
                                <a href="<?php echo base_url('admin/don-hang/chi-tiet/' . $s['don_hang_id']); ?>" style="color: #0369a1; font-weight:600;">
                                    #<?php echo htmlspecialchars($s['ma_don_hang']); ?>
                                </a>
                            </td>
                            <td>
                                <div><strong><?php echo htmlspecialchars($s['ho_ten_nhan']); ?></strong> (<?php echo htmlspecialchars($s['sdt_nhan']); ?>)</div>
                                <small style="color: #64748b; display:block; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($s['dia_chi_giao']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($s['don_vi_van_chuyen'] ?? 'Nội bộ Shop'); ?></td>
                            <td><?php echo htmlspecialchars($s['ten_phuong_thuc'] ?? 'Tiêu chuẩn'); ?></td>
                            <td><strong><?php echo $s['phi_van_chuyen'] == 0 ? 'Miễn phí' : format_currency($s['phi_van_chuyen']); ?></strong></td>
                            <td>
                                <?php
                                    $stMap = [
                                        'cho_lay_hang'    => ['Chờ đóng gói', 'badge-warning'],
                                        'dang_van_chuyen' => ['Đang trung chuyển', 'badge-info'],
                                        'dang_giao'       => ['Đang giao hàng', 'badge-info'],
                                        'da_giao'         => ['Giao thành công', 'badge-success'],
                                        'giao_that_bai'   => ['Giao thất bại', 'badge-danger'],
                                        'chuyen_hoan'     => ['Chuyển hoàn', 'badge-danger'],
                                    ];
                                    $st = $stMap[$s['trang_thai_giao_hang'] ?? 'cho_lay_hang'] ?? ['Chờ lấy hàng', 'badge-secondary'];
                                ?>
                                <span class="badge <?php echo $st[1]; ?>"><?php echo $st[0]; ?></span>
                            </td>
                            <td style="text-align: right;">
                                <button type="button" onclick="openShippingModal(<?php echo htmlspecialchars(json_encode($s)); ?>)" class="btn-admin btn-admin-primary btn-admin-sm">
                                    Cập nhật
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal cập nhật vận chuyển -->
<div id="shippingModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; width:100%; max-width:500px; border-radius:8px; padding:25px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <h3 style="font-size:18px; margin-bottom:15px; color:#1e293b;" id="modalTitle">Cập Nhật Vận Đơn</h3>
        <form action="<?php echo base_url('admin/van-chuyen/cap-nhat'); ?>" method="POST">
            <input type="hidden" name="don_hang_id" id="modalOrderId">

            <div class="form-group" style="margin-bottom:15px;">
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Mã vận đơn</label>
                <input type="text" name="ma_van_don" id="modalTrackingCode" placeholder="VD20260901..." style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px;">
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Đơn vị vận chuyển</label>
                <input type="text" name="don_vi_van_chuyen" id="modalCarrier" placeholder="Đội Xe Tải Shop Nội Thất, GHTK, GHN..." style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px;">
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Trạng thái giao hàng</label>
                <select name="trang_thai_giao_hang" id="modalStatus" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px;">
                    <option value="cho_lay_hang">Chờ lấy hàng / Đóng gói</option>
                    <option value="dang_van_chuyen">Đang trung chuyển</option>
                    <option value="dang_giao">Đang giao hàng</option>
                    <option value="da_giao">Giao hàng thành công</option>
                    <option value="giao_that_bai">Giao thất bại</option>
                    <option value="chuyen_hoan">Chuyển hoàn</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:20px;">
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Ghi chú tiến trình</label>
                <textarea name="ghi_chu" id="modalNotes" rows="2" placeholder="Ví dụ: Đã giao cho tài xế Nguyễn Văn C..." style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px;"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="closeShippingModal()" class="btn-admin" style="background:#e2e8f0; color:#334155;">Hủy</button>
                <button type="submit" class="btn-admin btn-admin-primary">Lưu Thay Đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
function openShippingModal(shipment) {
    document.getElementById('modalTitle').textContent = 'Cập Nhật Vận Đơn: #' + shipment.ma_don_hang;
    document.getElementById('modalOrderId').value = shipment.don_hang_id;
    document.getElementById('modalTrackingCode').value = shipment.ma_van_don || '';
    document.getElementById('modalCarrier').value = shipment.don_vi_van_chuyen || 'Đội Xe Tải Shop Nội Thất';
    document.getElementById('modalStatus').value = shipment.trang_thai_giao_hang || 'cho_lay_hang';
    document.getElementById('shippingModal').style.display = 'flex';
}

function closeShippingModal() {
    document.getElementById('shippingModal').style.display = 'none';
}
</script>

<?php
require_once __DIR__ . '/../../../includes/admin_footer.php';
?>
