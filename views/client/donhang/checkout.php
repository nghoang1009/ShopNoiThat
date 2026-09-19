<?php
$extraCss = [
    'assets/css/client/checkout.css',
    'assets/css/client/vanchuyen_thanhtoan.css'
];
require_once __DIR__ . '/../../../includes/header.php';

$oldInput = get_flash('old_input');
$oldInput = is_array($oldInput) && isset($oldInput['message']) ? $oldInput['message'] : (is_array($oldInput) ? $oldInput : []);

$orderErrorsFlash = get_flash('order_errors');
$orderErrors = is_array($orderErrorsFlash) && isset($orderErrorsFlash['message']) && is_array($orderErrorsFlash['message'])
    ? $orderErrorsFlash['message']
    : (is_array($orderErrorsFlash) ? $orderErrorsFlash : []);
?>

<div class="container">
    <div style="padding: 20px 0 10px 0; font-size: 14px; color: var(--text-muted);">
        <a href="<?php echo base_url(); ?>">Trang chủ</a> &raquo;
        <a href="<?php echo base_url('gio-hang'); ?>">Giỏ hàng</a> &raquo;
        <span>Thanh toán & Vận chuyển</span>
    </div>

    <form action="<?php echo base_url('don-hang/thanh-toan'); ?>" method="POST" id="checkout-form">
        <div class="checkout-layout">
            <!-- Cột trái: Thông tin nhận hàng, Vận chuyển & Thanh toán -->
            <div class="checkout-box">
                <h2 class="checkout-title">1. Thông Tin Nhận Hàng</h2>

                <?php if (!empty($orderErrors)): ?>
                    <div class="alert alert-danger">
                        <ul style="margin-left: 20px;">
                            <?php foreach ($orderErrors as $err): ?>
                                <li><?php echo $err; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="ho_ten_nhan">Họ và tên người nhận <span style="color:red;">*</span></label>
                        <input type="text" id="ho_ten_nhan" name="ho_ten_nhan" class="form-control" required
                               placeholder="Nguyễn Văn A"
                               value="<?php echo htmlspecialchars($oldInput['ho_ten_nhan'] ?? $user['ho_ten'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="sdt_nhan">Số điện thoại <span style="color:red;">*</span></label>
                        <input type="tel" id="sdt_nhan" name="sdt_nhan" class="form-control" required
                               placeholder="0912345678"
                               value="<?php echo htmlspecialchars($oldInput['sdt_nhan'] ?? $user['sdt'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="tinh_thanh">Tỉnh / Thành phố <span style="color:red;">*</span></label>
                        <input type="text" id="tinh_thanh" name="tinh_thanh" class="form-control" required
                               placeholder="TP. Hồ Chí Minh, Hà Nội, Đà Nẵng, Đồng Nai..."
                               value="<?php echo htmlspecialchars($oldInput['tinh_thanh'] ?? 'TP. Hồ Chí Minh'); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="dia_chi_giao">Địa chỉ chi tiết (Số nhà, đường, phường/xã) <span style="color:red;">*</span></label>
                        <input type="text" id="dia_chi_giao" name="dia_chi_giao" class="form-control" required
                               placeholder="Số 123 Nguyễn Huệ, P. Bến Nghé..."
                               value="<?php echo htmlspecialchars($oldInput['dia_chi_giao'] ?? $user['dia_chi'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ghi_chu">Ghi chú đơn hàng (tuỳ chọn)</label>
                    <textarea id="ghi_chu" name="ghi_chu" class="form-control" placeholder="Ví dụ: Giao lên tầng 3 chung cư, gọi trước khi đến 30 phút..."><?php echo htmlspecialchars($oldInput['ghi_chu'] ?? ''); ?></textarea>
                </div>

                <!-- 2. PHƯƠNG THỨC VẬN CHUYỂN -->
                <h2 class="checkout-title" style="margin-top: 35px;">2. Phương Thức Vận Chuyển</h2>
                <div class="shipping-methods-list">
                    <?php foreach ($shippingMethods as $idx => $ship): ?>
                        <?php
                            $isChecked = ($idx === 0);
                            $feeDisplay = ($ship['phi_van_chuyen'] == 0 || $cart['total_amount'] >= 10000000) ? 'Miễn phí' : format_currency($ship['phi_van_chuyen']);
                        ?>
                        <label class="shipping-method-card <?php echo $isChecked ? 'active' : ''; ?>">
                            <div class="shipping-method-left">
                                <input type="radio" name="phuong_thuc_van_chuyen_id" value="<?php echo $ship['id']; ?>" <?php echo $isChecked ? 'checked' : ''; ?> style="accent-color: var(--primary-color);">
                                <div>
                                    <div class="shipping-method-title"><?php echo htmlspecialchars($ship['ten_pt']); ?></div>
                                    <div class="shipping-method-desc"><?php echo htmlspecialchars($ship['mo_ta'] ?? ''); ?> (Dự kiến: <?php echo htmlspecialchars($ship['thoi_gian_du_kien']); ?>)</div>
                                </div>
                            </div>
                            <div class="shipping-method-fee"><?php echo $feeDisplay; ?></div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- 3. PHƯƠNG THỨC THANH TOÁN -->
                <h2 class="checkout-title" style="margin-top: 35px;">3. Phương Thức Thanh Toán</h2>
                <div class="payment-methods-grid">
                    <?php foreach ($paymentMethods as $idx => $pm): ?>
                        <?php $isPayChecked = ($idx === 0); ?>
                        <label class="payment-method-item <?php echo $isPayChecked ? 'active' : ''; ?>">
                            <input type="radio" name="phuong_thuc_thanh_toan_id" value="<?php echo $pm['id']; ?>" <?php echo $isPayChecked ? 'checked' : ''; ?> style="accent-color: var(--primary-color);">
                            <div class="payment-icon">
                                <?php if ($pm['ma_pt'] === 'cod'): ?>💵
                                <?php elseif ($pm['ma_pt'] === 'banking'): ?>🏛️
                                <?php elseif ($pm['ma_pt'] === 'momo'): ?>🟣
                                <?php elseif ($pm['ma_pt'] === 'vnpay'): ?>💳
                                <?php else: ?>💰<?php endif; ?>
                            </div>
                            <div style="flex: 1;">
                                <strong><?php echo htmlspecialchars($pm['ten_pt']); ?></strong>
                                <div style="font-size: 13px; color: var(--text-muted); margin-top: 2px;">
                                    <?php echo htmlspecialchars($pm['mo_ta'] ?? ''); ?>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cột phải: Tóm tắt đơn hàng & Tổng tiền -->
            <div class="checkout-box" style="align-self: start;">
                <h2 class="checkout-title">Đơn Hàng (<?php echo $cart['total_quantity']; ?> món)</h2>

                <div class="checkout-items">
                    <?php foreach ($cart['items'] as $item): ?>
                        <div class="checkout-item">
                            <div class="checkout-item-info">
                                <img src="<?php echo !empty($item['hinh_anh']) ? asset_url($item['hinh_anh']) : asset_url('assets/images/default.jpg'); ?>" onerror="this.src='https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=100&q=80'" class="checkout-item-img" alt="<?php echo htmlspecialchars($item['ten_san_pham']); ?>">
                                <div>
                                    <div class="checkout-item-name"><?php echo htmlspecialchars($item['ten_san_pham']); ?></div>
                                    <div class="checkout-item-qty">Số lượng: <?php echo $item['cart_quantity']; ?></div>
                                </div>
                            </div>
                            <div class="checkout-item-price">
                                <?php echo format_currency($item['thanh_tien']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-row" style="margin-top: 15px;">
                    <span>Tiền hàng:</span>
                    <strong><?php echo format_currency($cart['total_amount']); ?></strong>
                </div>

                <div class="summary-row">
                    <span>Phí vận chuyển:</span>
                    <strong id="shipping-fee-display" style="color: var(--primary-color);">50.000 ₫</strong>
                </div>

                <div class="summary-row total">
                    <span>Tổng thanh toán:</span>
                    <span class="summary-total-price" id="grand-total-display"><?php echo format_currency($cart['total_amount'] + 50000); ?></span>
                </div>

                <button type="submit" id="btn-submit-order" class="btn btn-primary btn-block" style="margin-top: 25px; padding: 14px; font-size: 16px;">
                    ✅ Đặt Hàng & Tiếp Tục &rarr;
                </button>

                <div style="font-size: 12px; color: var(--text-muted); text-align: center; margin-top: 15px;">
                    🔒 Cam kết bảo mật thông tin & Hỗ trợ kỹ thuật 24/7
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    window.CART_SUBTOTAL = <?php echo (float)$cart['total_amount']; ?>;
</script>

<?php
$extraJs = ['assets/js/client/checkout.js'];
require_once __DIR__ . '/../../../includes/footer.php';
?>
