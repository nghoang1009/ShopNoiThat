<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Lịch Sử Giao Dịch & Đối Soát Thanh Toán</h1>
        <p style="color: #64748b; font-size: 14px;">Quản lý các giao dịch thu tiền, chuyển khoản ngân hàng và ví điện tử</p>
    </div>
    <a href="<?php echo base_url('admin/thanh-toan/phuong-thuc'); ?>" class="btn-admin btn-admin-primary">
        ⚙️ Quản Lý Cổng Thanh Toán
    </a>
</div>

<!-- Bộ lọc thanh toán -->
<div class="card-box" style="padding: 16px 24px; margin-bottom: 20px;">
    <form action="<?php echo base_url('admin/thanh-toan'); ?>" method="GET" style="display:flex; gap:15px; flex-wrap:wrap; align-items:center;">
        <input type="text" name="keyword" placeholder="Tìm mã đơn, mã giao dịch, tên khách..." value="<?php echo htmlspecialchars($filters['keyword'] ?? ''); ?>" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; min-width: 260px;">

        <select name="trang_thai" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
            <option value="">-- Tất cả trạng thái --</option>
            <option value="cho_thanh_toan" <?php echo (isset($filters['trang_thai']) && $filters['trang_thai'] === 'cho_thanh_toan') ? 'selected' : ''; ?>>Chờ thanh toán</option>
            <option value="da_thanh_toan" <?php echo (isset($filters['trang_thai']) && $filters['trang_thai'] === 'da_thanh_toan') ? 'selected' : ''; ?>>Đã thanh toán thành công</option>
            <option value="that_bai" <?php echo (isset($filters['trang_thai']) && $filters['trang_thai'] === 'that_bai') ? 'selected' : ''; ?>>Thất bại</option>
            <option value="hoan_tien" <?php echo (isset($filters['trang_thai']) && $filters['trang_thai'] === 'hoan_tien') ? 'selected' : ''; ?>>Đã hoàn tiền</option>
        </select>

        <select name="phuong_thuc_id" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
            <option value="">-- Tất cả phương thức --</option>
            <?php foreach ($paymentMethods as $pm): ?>
                <option value="<?php echo $pm['id']; ?>" <?php echo (isset($filters['phuong_thuc_id']) && $filters['phuong_thuc_id'] == $pm['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($pm['ten_pt']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn-admin btn-admin-primary">Lọc Giao Dịch</button>
        <a href="<?php echo base_url('admin/thanh-toan'); ?>" style="font-size: 13px; color: #64748b; margin-left: 5px;">Đặt lại</a>
    </form>
</div>

<div class="card-box">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Phương thức</th>
                    <th>Số tiền</th>
                    <th>Mã giao dịch</th>
                    <th>Thời gian TT</th>
                    <th>Trạng thái</th>
                    <th style="text-align: right;">Đối soát / Xử lý</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 30px;">Chưa có giao dịch thanh toán nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td>
                                <a href="<?php echo base_url('admin/don-hang/chi-tiet/' . $t['don_hang_id']); ?>" style="color: #0369a1; font-weight:700;">
                                    #<?php echo htmlspecialchars($t['ma_don_hang']); ?>
                                </a>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($t['ho_ten_nhan']); ?></strong>
                                <small style="display:block; color:#64748b;"><?php echo htmlspecialchars($t['sdt_nhan']); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($t['ten_phuong_thuc'] ?? 'Chưa chọn'); ?>
                            </td>
                            <td>
                                <strong style="color: #e67e22; font-size: 15px;"><?php echo format_currency($t['so_tien']); ?></strong>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($t['ma_giao_dich'] ?: '-'); ?></code>
                            </td>
                            <td>
                                <?php echo !empty($t['ngay_thanh_toan']) ? date('d/m/Y H:i', strtotime($t['ngay_thanh_toan'])) : '<span style="color:#94a3b8;">Chờ thanh toán</span>'; ?>
                            </td>
                            <td>
                                <?php if ($t['trang_thai'] === 'da_thanh_toan'): ?>
                                    <span class="badge badge-success">✓ Đã thanh toán</span>
                                <?php elseif ($t['trang_thai'] === 'cho_thanh_toan'): ?>
                                    <span class="badge badge-warning">⏳ Chờ thanh toán</span>
                                <?php elseif ($t['trang_thai'] === 'that_bai'): ?>
                                    <span class="badge badge-danger">✗ Thất bại</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary"><?php echo $t['trang_thai']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($t['trang_thai'] !== 'da_thanh_toan'): ?>
                                    <a href="<?php echo base_url('admin/thanh-toan/xac-nhan/' . $t['id']); ?>" onclick="return confirm('Bạn có chắc muốn xác nhận đơn này đã nhận tiền thành công?')" class="btn-admin btn-admin-primary btn-admin-sm">
                                        ✓ Xác nhận đã thu
                                    </a>
                                <?php else: ?>
                                    <span style="color: #15803d; font-size: 13px; font-weight: 600;">Đã khớp</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../../../includes/admin_footer.php';
?>
