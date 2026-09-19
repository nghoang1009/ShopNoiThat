<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Quản Lý Đơn Hàng</h1>
        <p style="color: #64748b; font-size: 14px;">Theo dõi và xử lý trạng thái đơn hàng của khách hàng</p>
    </div>
</div>

<!-- Lọc đơn hàng -->
<div class="card-box" style="padding: 16px 24px; margin-bottom: 20px;">
    <form action="<?php echo base_url('admin/don-hang'); ?>" method="GET" style="display:flex; gap:15px; flex-wrap:wrap; align-items:center;">
        <input type="text" name="keyword" placeholder="Tìm mã đơn, tên khách, số điện thoại..." value="<?php echo htmlspecialchars($filters['keyword'] ?? ''); ?>" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; min-width: 280px;">

        <select name="trang_thai" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
            <option value="">-- Tất cả trạng thái --</option>
            <option value="cho_xu_ly" <?php echo (isset($filters['trang_thai']) && $filters['trang_thai'] === 'cho_xu_ly') ? 'selected' : ''; ?>>Chờ xử lý</option>
            <option value="dang_giao" <?php echo (isset($filters['trang_thai']) && $filters['trang_thai'] === 'dang_giao') ? 'selected' : ''; ?>>Đang giao hàng</option>
            <option value="hoan_tat" <?php echo (isset($filters['trang_thai']) && $filters['trang_thai'] === 'hoan_tat') ? 'selected' : ''; ?>>Hoàn tất</option>
            <option value="da_huy" <?php echo (isset($filters['trang_thai']) && $filters['trang_thai'] === 'da_huy') ? 'selected' : ''; ?>>Đã hủy</option>
        </select>

        <button type="submit" class="btn-admin btn-admin-primary">Lọc Đơn Hàng</button>
        <a href="<?php echo base_url('admin/don-hang'); ?>" style="font-size: 13px; color: #64748b; margin-left: 5px;">Đặt lại</a>
    </form>
</div>

<div class="card-box">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Người nhận & SĐT</th>
                    <th>Ngày đặt</th>
                    <th>Hình thức</th>
                    <th>Tổng tiền</th>
                    <th>Thanh toán</th>
                    <th>Trạng thái đơn</th>
                    <th style="text-align: right;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 30px;">Không tìm thấy đơn hàng nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong>#<?php echo htmlspecialchars($o['ma_don_hang']); ?></strong></td>
                            <td>
                                <div><strong><?php echo htmlspecialchars($o['ho_ten_nhan']); ?></strong></div>
                                <small style="color: #64748b;">📞 <?php echo htmlspecialchars($o['sdt_nhan']); ?></small>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($o['ngay_dat'])); ?></td>
                            <td>
                                <?php echo ($o['phuong_thuc_thanh_toan'] === 'banking') ? '💳 Chuyển khoản' : '💵 Tiền mặt (COD)'; ?>
                            </td>
                            <td>
                                <strong style="color: #e67e22; font-size: 15px;"><?php echo format_currency($o['tong_tien']); ?></strong>
                            </td>
                            <td>
                                <span class="badge <?php echo ($o['trang_thai_thanh_toan'] === 'da_thanh_toan') ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo ($o['trang_thai_thanh_toan'] === 'da_thanh_toan') ? 'Đã thu tiền' : 'Chưa thu tiền'; ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                    $sMap = [
                                        'cho_xu_ly' => ['Chờ xử lý', 'badge-warning'],
                                        'dang_giao' => ['Đang giao hàng', 'badge-info'],
                                        'hoan_tat'  => ['Hoàn tất', 'badge-success'],
                                        'da_huy'    => ['Đã hủy', 'badge-danger'],
                                    ];
                                    $st = $sMap[$o['trang_thai']] ?? ['Không rõ', 'badge-secondary'];
                                ?>
                                <span class="badge <?php echo $st[1]; ?>"><?php echo $st[0]; ?></span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?php echo base_url('admin/don-hang/chi-tiet/' . $o['id']); ?>" class="btn-admin btn-admin-primary btn-admin-sm">
                                    Chi tiết & Đổi trạng thái &rarr;
                                </a>
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
