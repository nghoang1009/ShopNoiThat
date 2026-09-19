<?php
$extraCss = [
    'assets/css/client/vanchuyen_thanhtoan.css',
    'assets/css/client/checkout.css'
];
require_once __DIR__ . '/../../../includes/header.php';

$paymentCode = $order['ma_pt_thanh_toan'] ?? $order['phuong_thuc_thanh_toan'] ?? 'banking';
$amount = (float)$order['tong_tien'];
$orderCode = $order['ma_don_hang'];

// Tạo link VietQR động
$vietQrUrl = "https://img.vietqr.io/image/vietcombank-999988886666-compact2.png?amount={$amount}&addInfo={$orderCode}&accountName=CTY%20TNHH%20NOI%20THAT%20CAO%20CAP";
?>

<div class="container">
    <div class="payment-gateway-wrapper">
        <div class="gateway-header">
            <div>
                <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 4px;">💳 Cổng Thanh Toán Đơn Hàng</h2>
                <span style="font-size: 13px; opacity: 0.85;">Mã đơn: <strong>#<?php echo htmlspecialchars($orderCode); ?></strong></span>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 12px; display: block; opacity: 0.85;">Số tiền cần thanh toán:</span>
                <strong style="font-size: 22px; color: #fbbf24;"><?php echo format_currency($amount); ?></strong>
            </div>
        </div>

        <div class="gateway-body">
            <?php if ($order['trang_thai_thanh_toan'] === 'da_thanh_toan'): ?>
                <div style="text-align: center; padding: 40px 20px;">
                    <div style="width: 70px; height: 70px; background: #dcfce7; color: #16a34a; border-radius: 50%; font-size: 36px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">✓</div>
                    <h2 style="color: var(--secondary-color); margin-bottom: 10px;">Đơn Hàng Đã Được Thanh Toán!</h2>
                    <p style="color: var(--text-muted); margin-bottom: 25px;">Hệ thống đã ghi nhận thanh toán thành công cho đơn hàng #<?php echo htmlspecialchars($orderCode); ?>.</p>
                    <div style="display:flex; justify-content:center; gap:15px;">
                        <a href="<?php echo base_url('don-hang/tra-cuu?ma=' . $orderCode); ?>" class="btn btn-primary">🚚 Theo Dõi Vận Chuyển</a>
                        <a href="<?php echo base_url(); ?>" class="btn btn-outline">Về Trang Chủ</a>
                    </div>
                </div>
            <?php else: ?>

                <!-- QR Code & Hướng Dẫn Chuyển Khoản -->
                <div class="qr-code-box">
                    <h3 style="font-size: 18px; color: var(--secondary-color); margin-bottom: 12px;">
                        <?php if ($paymentCode === 'momo'): ?>
                            🟣 Quét Mã QR Qua Ứng Dụng MoMo
                        <?php elseif ($paymentCode === 'vnpay'): ?>
                            💳 Quét Mã VNPAY-QR / Mobile Banking
                        <?php else: ?>
                            🏛️ Quét Mã VietQR Chuyển Khoản Tự Động
                        <?php endif; ?>
                    </h3>
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">Mở ứng dụng Ngân hàng hoặc Ví điện tử để quét mã thanh toán 24/7:</p>

                    <img src="<?php echo $vietQrUrl; ?>" alt="VietQR Thanh Toán" class="qr-img" onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=ShopNoiThat_<?php echo $orderCode; ?>'">

                    <div style="font-size: 13px; color: #b45309; font-weight: 600;">
                        ⚠️ Vui lòng giữ nguyên nội dung chuyển khoản: <code style="background:#fef08a; padding:2px 6px; border-radius:4px; font-size:14px;"><?php echo htmlspecialchars($orderCode); ?></code>
                    </div>
                </div>

                <!-- Bảng chi tiết tài khoản thụ hưởng -->
                <table class="transfer-details-table">
                    <tr>
                        <td>Ngân hàng thụ hưởng:</td>
                        <td><strong>Vietcombank (Ngoại thương Việt Nam)</strong></td>
                    </tr>
                    <tr>
                        <td>Số tài khoản:</td>
                        <td>
                            <strong style="color: var(--primary-color); font-size: 16px;">999988886666</strong>
                            <span class="copy-badge" onclick="copyToClipboard('999988886666', 'Số tài khoản')">📋 Sao chép</span>
                        </td>
                    </tr>
                    <tr>
                        <td>Chủ tài khoản:</td>
                        <td><strong>CTY TNHH NOI THAT CAO CAP</strong></td>
                    </tr>
                    <tr>
                        <td>Số tiền chính xác:</td>
                        <td>
                            <strong style="color: var(--accent-color); font-size: 16px;"><?php echo format_currency($amount); ?></strong>
                            <span class="copy-badge" onclick="copyToClipboard('<?php echo $amount; ?>', 'Số tiền')">📋 Sao chép</span>
                        </td>
                    </tr>
                    <tr>
                        <td>Nội dung chuyển khoản:</td>
                        <td>
                            <strong style="color: #1e293b;"><?php echo htmlspecialchars($orderCode); ?></strong>
                            <span class="copy-badge" onclick="copyToClipboard('<?php echo $orderCode; ?>', 'Nội dung chuyển khoản')">📋 Sao chép</span>
                        </td>
                    </tr>
                </table>

                <!-- Giả Lập Thanh Toán Simulator Dành Cho Môi Trường Test -->
                <div class="simulate-box">
                    <div class="simulate-title">⚡ Môi Trường Thử Nghiệm / Demo Cổng Thanh Toán</div>
                    <p style="font-size: 13px; color: #78350f; margin-bottom: 15px;">
                        Bạn đang xem bản demo cổng thanh toán. Nhấn nút bên dưới để giả lập ngân hàng/ví điện tử gửi tín hiệu thanh toán thành công ngay lập tức:
                    </p>
                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <button type="button" id="btn-simulate-payment" onclick="simulatePayment('<?php echo $orderCode; ?>', '<?php echo $paymentCode; ?>')" class="btn btn-primary" style="flex:1; padding:12px;">
                            ⚡ Giả Lập Thanh Toán Thành Công
                        </button>
                        <a href="<?php echo base_url('don-hang/tra-cuu?ma=' . $orderCode); ?>" class="btn btn-outline" style="padding:12px 20px;">
                            🚚 Để Sau & Theo Dõi Đơn
                        </a>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$extraJs = ['assets/js/client/payment.js'];
require_once __DIR__ . '/../../../includes/footer.php';
?>
