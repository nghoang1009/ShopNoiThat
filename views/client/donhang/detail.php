<?php
$extraCss = ['assets/css/client/giohang.css', 'assets/css/client/vanchuyen_thanhtoan.css'];
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="container" style="margin-top: 30px; margin-bottom: 60px;">
    <div style="padding: 10px 0 20px 0; font-size: 14px; color: var(--text-muted);">
        <a href="<?php echo base_url(); ?>">Trang chủ</a> &raquo;
        <a href="<?php echo base_url('don-hang/lich-su'); ?>">Đơn hàng của tôi</a> &raquo;
        <span>Chi tiết #<?php echo htmlspecialchars($order['ma_don_hang']); ?></span>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <h1 style="font-size: 24px; color: var(--secondary-color);">
            Chi Tiết Đơn Hàng #<?php echo htmlspecialchars($order['ma_don_hang']); ?>
        </h1>
        <div style="display:flex; gap:10px;">
            <a href="<?php echo base_url('don-hang/tra-cuu?ma=' . $order['ma_don_hang']); ?>" class="btn btn-outline btn-sm">
                🚚 Theo Dõi Vận Chuyển
            </a>
            <a href="<?php echo base_url('don-hang/lich-su'); ?>" style="color: var(--primary-color); font-weight:600; display:flex; align-items:center;">
                &larr; Quay lại danh sách
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        <!-- Bảng sản phẩm trong đơn -->
        <div style="background: #fff; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 24px;">
            <h3 style="font-size: 18px; margin-bottom: 20px; color: var(--secondary-color);">Danh Sách Sản Phẩm Đã Đặt</h3>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Đơn giá</th>
                        <th style="text-align:center;">SL</th>
                        <th style="text-align:right;">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order['items'] as $item): ?>
                        <tr>
                            <td>
                                <div class="cart-item-info">
                                    <img src="<?php echo !empty($item['hinh_anh']) ? asset_url($item['hinh_anh']) : asset_url('assets/images/default.jpg'); ?>" onerror="this.src='https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=150&q=80'" class="cart-item-img" alt="<?php echo htmlspecialchars($item['ten_san_pham']); ?>">
                                    <div>
                                        <div class="cart-item-title"><?php echo htmlspecialchars($item['ten_san_pham']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo format_currency($item['don_gia']); ?></td>
                            <td style="text-align:center;"><strong>x<?php echo $item['so_luong']; ?></strong></td>
                            <td style="text-align:right;">
                                <strong style="color: var(--accent-color);"><?php echo format_currency($item['thanh_tien']); ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="border-top: 1px solid var(--border-color); margin-top: 20px; padding-top: 15px;">
                <div style="display:flex; justify-content:space-between; margin-bottom: 8px; font-size: 14px;">
                    <span>Tiền hàng:</span>
                    <strong><?php echo format_currency($order['tien_hang'] > 0 ? $order['tien_hang'] : ($order['tong_tien'] - $order['phi_van_chuyen'])); ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom: 12px; font-size: 14px;">
                    <span>Phí vận chuyển:</span>
                    <strong><?php echo $order['phi_van_chuyen'] == 0 ? 'Miễn phí' : format_currency($order['phi_van_chuyen']); ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; border-top: 2px dashed var(--border-color); padding-top: 12px;">
                    <span style="font-size: 16px; font-weight:700;">Tổng thanh toán:</span>
                    <strong style="color: var(--accent-color); font-size: 22px;"><?php echo format_currency($order['tong_tien']); ?></strong>
                </div>
            </div>
        </div>

        <!-- Thông tin giao hàng, vận chuyển & thanh toán -->
        <div>
            <!-- Vận Chuyển -->
            <div style="background: #fff; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 24px; margin-bottom: 25px;">
                <h3 style="font-size: 17px; margin-bottom: 15px; color: var(--secondary-color);">🚚 Vận Chuyển & Giao Hàng</h3>

                <div style="margin-bottom: 12px; font-size: 14px;">
                    <span style="color: var(--text-muted);">Mã vận đơn:</span>
                    <strong style="color: var(--primary-color);">#<?php echo htmlspecialchars($order['ma_van_don'] ?? 'Chờ tạo'); ?></strong>
                </div>

                <div style="margin-bottom: 12px; font-size: 14px;">
                    <span style="color: var(--text-muted);">Gói vận chuyển:</span>
                    <strong><?php echo htmlspecialchars($order['ten_pt_van_chuyen'] ?? 'Giao tiêu chuẩn'); ?></strong>
                </div>

                <div style="margin-bottom: 12px; font-size: 14px;">
                    <span style="color: var(--text-muted);">Đơn vị giao:</span>
                    <strong><?php echo htmlspecialchars($order['don_vi_van_chuyen'] ?? 'Nội bộ Shop'); ?></strong>
                </div>

                <div style="margin-bottom: 15px; font-size: 14px;">
                    <span style="color: var(--text-muted);">Trạng thái giao:</span>
                    <?php
                        $shipMap = [
                            'cho_lay_hang'   => ['Chờ lấy hàng / Đóng gói', '#fef3c7', '#b45309'],
                            'dang_van_chuyen'=> ['Đang vận chuyển', '#e0f2fe', '#0369a1'],
                            'dang_giao'      => ['Đang giao hàng', '#e0f2fe', '#0369a1'],
                            'da_giao'        => ['Giao thành công', '#dcfce7', '#15803d'],
                            'giao_that_bai'  => ['Giao thất bại', '#fee2e2', '#b91c1c'],
                            'chuyen_hoan'    => ['Chuyển hoàn', '#fee2e2', '#b91c1c'],
                        ];
                        $stvc = $shipMap[$order['trang_thai_giao_hang'] ?? 'cho_lay_hang'] ?? ['Chờ lấy hàng', '#f1f5f9', '#475569'];
                    ?>
                    <span style="background:<?php echo $stvc[1]; ?>; color:<?php echo $stvc[2]; ?>; padding:3px 8px; border-radius:4px; font-size:12px; font-weight:700;">
                        <?php echo $stvc[0]; ?>
                    </span>
                </div>

                <a href="<?php echo base_url('don-hang/tra-cuu?ma=' . ($order['ma_van_don'] ?: $order['ma_don_hang'])); ?>" class="btn btn-primary btn-block btn-sm" style="padding: 10px;">
                    📍 Xem Tiến Trình Vận Chuyển
                </a>
            </div>

            <!-- Thông Tin Người Nhận & Thanh Toán -->
            <div style="background: #fff; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 24px;">
                <h3 style="font-size: 17px; margin-bottom: 15px; color: var(--secondary-color);">👤 Thông Tin Người Nhận</h3>

                <div style="margin-bottom: 10px; font-size: 14px;">
                    <span style="color: var(--text-muted);">Họ tên:</span>
                    <strong><?php echo htmlspecialchars($order['ho_ten_nhan']); ?></strong>
                </div>

                <div style="margin-bottom: 10px; font-size: 14px;">
                    <span style="color: var(--text-muted);">Điện thoại:</span>
                    <strong><?php echo htmlspecialchars($order['sdt_nhan']); ?></strong>
                </div>

                <div style="margin-bottom: 15px; font-size: 14px;">
                    <span style="color: var(--text-muted);">Địa chỉ nhận:</span>
                    <div><?php echo htmlspecialchars($order['dia_chi_giao']); ?></div>
                </div>

                <div style="border-top: 1px solid var(--border-color); padding-top: 15px; margin-top: 15px;">
                    <div style="margin-bottom: 10px; font-size: 14px;">
                        <span style="color: var(--text-muted);">Hình thức thanh toán:</span>
                        <strong><?php echo htmlspecialchars($order['ten_pt_thanh_toan'] ?? $order['phuong_thuc_thanh_toan']); ?></strong>
                    </div>

                    <div style="margin-bottom: 15px; font-size: 14px;">
                        <span style="color: var(--text-muted);">Trạng thái thanh toán:</span>
                        <strong><?php echo ($order['trang_thai_thanh_toan'] === 'da_thanh_toan') ? '<span style="color:green;">✓ Đã thanh toán</span>' : '<span style="color:#b45309;">⏳ Chưa thanh toán</span>'; ?></strong>
                    </div>

                    <?php if ($order['trang_thai_thanh_toan'] !== 'da_thanh_toan' && in_array($order['phuong_thuc_thanh_toan'], ['banking', 'momo', 'vnpay', 'zalopay'])): ?>
                        <a href="<?php echo base_url('don-hang/thanh-toan-online/' . $order['ma_don_hang']); ?>" class="btn btn-primary btn-block" style="padding: 10px;">
                            💳 Thanh Toán Trực Tuyến Ngay
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>
