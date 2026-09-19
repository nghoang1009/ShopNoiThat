<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Chi Tiết Đơn Hàng #<?php echo htmlspecialchars($order['ma_don_hang']); ?></h1>
        <p style="color: #64748b; font-size: 14px;">Thời gian đặt: <?php echo date('d/m/Y H:i:s', strtotime($order['ngay_dat'])); ?></p>
    </div>
    <a href="<?php echo base_url('admin/don-hang'); ?>" style="color: #935832; font-weight:600;">&larr; Quay lại danh sách đơn</a>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
    <!-- Cột trái: Danh sách sản phẩm & Tổng tiền -->
    <div class="card-box">
        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 20px; color: #1e293b;">
            🛋️ Sản Phẩm Đã Đặt
        </h3>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Đơn giá</th>
                        <th style="text-align:center;">Số lượng</th>
                        <th style="text-align:right;">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order['items'] as $item): ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <img src="<?php echo !empty($item['hinh_anh']) ? asset_url($item['hinh_anh']) : asset_url('assets/images/default.jpg'); ?>" onerror="this.src='https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=100&q=80'" style="width:50px; height:50px; border-radius:6px; object-fit:cover; border:1px solid #e2e8f0;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['ten_san_pham']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo format_currency($item['don_gia']); ?></td>
                            <td style="text-align:center;"><strong>x<?php echo $item['so_luong']; ?></strong></td>
                            <td style="text-align:right;">
                                <strong style="color: #e67e22;"><?php echo format_currency($item['thanh_tien']); ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="border-top: 2px dashed #e2e8f0; margin-top: 20px; padding-top: 15px; text-align: right;">
            <span style="font-size: 16px;">Tổng thanh toán:</span>
            <strong style="color: #e67e22; font-size: 24px; margin-left: 15px;"><?php echo format_currency($order['tong_tien']); ?></strong>
        </div>
    </div>

    <!-- Cột phải: Thông tin khách hàng & Form cập nhật trạng thái -->
    <div>
        <!-- Cập nhật trạng thái -->
        <div class="card-box" style="margin-bottom: 25px;">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 20px; color: #1e293b;">
                ⚡ Cập Nhật Trạng Thái
            </h3>

            <form action="<?php echo base_url('admin/don-hang/chi-tiet/' . $order['id']); ?>" method="POST">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display:block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Trạng thái đơn hàng</label>
                    <select name="trang_thai" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                        <option value="cho_xu_ly" <?php echo ($order['trang_thai'] === 'cho_xu_ly') ? 'selected' : ''; ?>>Chờ xử lý</option>
                        <option value="dang_giao" <?php echo ($order['trang_thai'] === 'dang_giao') ? 'selected' : ''; ?>>Đang giao hàng</option>
                        <option value="hoan_tat" <?php echo ($order['trang_thai'] === 'hoan_tat') ? 'selected' : ''; ?>>Hoàn tất đơn hàng</option>
                        <option value="da_huy" <?php echo ($order['trang_thai'] === 'da_huy') ? 'selected' : ''; ?>>Hủy đơn hàng (Hoàn tồn kho)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display:block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Trạng thái thanh toán</label>
                    <select name="trang_thai_thanh_toan" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                        <option value="chua_thanh_toan" <?php echo ($order['trang_thai_thanh_toan'] === 'chua_thanh_toan') ? 'selected' : ''; ?>>Chưa thanh toán</option>
                        <option value="da_thanh_toan" <?php echo ($order['trang_thai_thanh_toan'] === 'da_thanh_toan') ? 'selected' : ''; ?>>Đã thanh toán</option>
                    </select>
                </div>

                <button type="submit" class="btn-admin btn-admin-primary" style="width: 100%; justify-content: center; padding: 10px;">
                    Lưu Thay Đổi
                </button>
            </form>
        </div>

        <!-- Thông tin người nhận -->
        <div class="card-box">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 15px; color: #1e293b;">
                👤 Thông Tin Nhận Hàng
            </h3>

            <div style="font-size: 14px; line-height: 1.8;">
                <div>Họ tên: <strong><?php echo htmlspecialchars($order['ho_ten_nhan']); ?></strong></div>
                <div>Điện thoại: <strong><?php echo htmlspecialchars($order['sdt_nhan']); ?></strong></div>
                <div>Địa chỉ: <span><?php echo htmlspecialchars($order['dia_chi_giao']); ?></span></div>
                <div>Phương thức: <strong><?php echo htmlspecialchars($order['ten_pt_thanh_toan'] ?? $order['phuong_thuc_thanh_toan']); ?></strong></div>
                <?php if (!empty($order['ma_van_don'])): ?>
                    <div style="margin-top: 5px;">Mã vận đơn: <strong style="color:#935832;">#<?php echo htmlspecialchars($order['ma_van_don']); ?></strong></div>
                <?php endif; ?>
                <?php if (!empty($order['ghi_chu'])): ?>
                    <div style="margin-top: 8px; color: #64748b; font-style: italic;">
                        Ghi chú: <?php echo htmlspecialchars($order['ghi_chu']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../../includes/admin_footer.php';
?>
