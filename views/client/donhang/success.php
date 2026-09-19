<?php
$extraCss = ['assets/css/client/checkout.css', 'assets/css/client/vanchuyen_thanhtoan.css'];
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="container">
    <div class="success-card">
        <div class="success-icon">✓</div>
        <h1 style="font-size: 26px; color: var(--secondary-color); margin-bottom: 10px;">Đặt Hàng Thành Công!</h1>
        <p style="color: var(--text-muted); font-size: 15px;">
            Cảm ơn quý khách đã tin tưởng và mua sắm tại <strong>Shop Nội Thất</strong>. Đơn hàng của bạn đã được tiếp nhận và nhân viên chăm sóc khách hàng sẽ liên hệ xác nhận trong thời gian sớm nhất.
        </p>

        <?php if ($order): ?>
            <div style="margin: 25px 0; padding: 20px; background: #f8fafc; border-radius: 8px; text-align: left; border: 1px solid var(--border-color);">
                <div style="display:flex; justify-content:space-between; margin-bottom: 8px;">
                    <span>Mã đơn hàng:</span>
                    <strong style="color: var(--primary-color); font-size: 16px;">#<?php echo htmlspecialchars($order['ma_don_hang']); ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom: 8px;">
                    <span>Người nhận:</span>
                    <strong><?php echo htmlspecialchars($order['ho_ten_nhan']); ?> (<?php echo htmlspecialchars($order['sdt_nhan']); ?>)</strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom: 8px;">
                    <span>Địa chỉ giao hàng:</span>
                    <span><?php echo htmlspecialchars($order['dia_chi_giao']); ?></span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom: 8px;">
                    <span>Phương thức:</span>
                    <strong><?php echo htmlspecialchars($order['ten_pt_thanh_toan'] ?? $order['phuong_thuc_thanh_toan']); ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; border-top: 1px dashed var(--border-color); padding-top: 8px; margin-top: 8px;">
                    <span>Tổng thanh toán:</span>
                    <strong style="color: var(--accent-color); font-size: 18px;"><?php echo format_currency($order['tong_tien']); ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: center; gap: 15px; margin-top: 30px; flex-wrap: wrap;">
            <?php if ($order): ?>
                <a href="<?php echo base_url('don-hang/tra-cuu?ma=' . $order['ma_don_hang']); ?>" class="btn btn-primary">
                    🚚 Theo Dõi Tiến Trình Giao Hàng
                </a>
                <?php if ($order['trang_thai_thanh_toan'] !== 'da_thanh_toan' && in_array($order['phuong_thuc_thanh_toan'], ['banking', 'momo', 'vnpay', 'zalopay'])): ?>
                    <a href="<?php echo base_url('don-hang/thanh-toan-online/' . $order['ma_don_hang']); ?>" class="btn btn-outline" style="background: #fbbf24; color: #1e293b; border-color: #f59e0b; font-weight:700;">
                        💳 Thanh Toán Trực Tuyến Ngay
                    </a>
                <?php endif; ?>
            <?php endif; ?>
            <a href="<?php echo base_url(); ?>" class="btn btn-outline">Quay Về Trang Chủ</a>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>
