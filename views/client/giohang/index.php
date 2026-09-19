<?php
$extraCss = ['assets/css/client/giohang.css'];
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="container">
    <div style="padding: 20px 0 10px 0; font-size: 14px; color: var(--text-muted);">
        <a href="<?php echo base_url(); ?>">Trang chủ</a> &raquo;
        <span>Giỏ hàng</span>
    </div>

    <h1 style="font-size: 26px; color: var(--secondary-color); margin-bottom: 25px;">
        Giỏ Hàng Của Bạn (<?php echo $cart['total_quantity']; ?> sản phẩm)
    </h1>

    <?php if (empty($cart['items'])): ?>
        <div class="empty-cart-box">
            <div class="empty-cart-icon">🛒</div>
            <h2 style="font-size: 20px; color: var(--secondary-color); margin-bottom: 10px;">Giỏ hàng của bạn đang trống</h2>
            <p style="color: var(--text-muted); margin-bottom: 25px;">Hãy khám phá các sản phẩm nội thất sang trọng của chúng tôi!</p>
            <a href="<?php echo base_url('san-pham'); ?>" class="btn btn-primary">Khám Phá Sản Phẩm Ngay</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <!-- Bảng sản phẩm -->
            <div class="cart-table-wrapper">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Đơn giá</th>
                            <th style="text-align: center;">Số lượng</th>
                            <th>Thành tiền</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart['items'] as $item): ?>
                            <tr>
                                <td>
                                    <div class="cart-item-info">
                                        <img src="<?php echo !empty($item['hinh_anh']) ? asset_url($item['hinh_anh']) : asset_url('assets/images/default.jpg'); ?>" onerror="this.src='https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=150&q=80'" class="cart-item-img" alt="<?php echo htmlspecialchars($item['ten_san_pham']); ?>">
                                        <div>
                                            <div class="cart-item-title">
                                                <a href="<?php echo base_url('san-pham/' . ($item['slug'] ?? $item['product_id'])); ?>">
                                                    <?php echo htmlspecialchars($item['ten_san_pham']); ?>
                                                </a>
                                            </div>
                                            <?php if (!empty($item['chat_lieu'])): ?>
                                                <div class="cart-item-meta">Chất liệu: <?php echo htmlspecialchars($item['chat_lieu']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="cart-item-price"><?php echo format_currency($item['don_gia_thuc_te']); ?></span>
                                </td>
                                <td style="text-align: center;">
                                    <div class="qty-input-group" style="display:inline-flex;">
                                        <button type="button" class="qty-btn" onclick="updateCartQty(<?php echo $item['cart_id']; ?>, <?php echo $item['cart_quantity'] - 1; ?>)">-</button>
                                        <input type="text" class="qty-val" value="<?php echo $item['cart_quantity']; ?>" readonly style="width:40px;">
                                        <button type="button" class="qty-btn" onclick="updateCartQty(<?php echo $item['cart_id']; ?>, <?php echo $item['cart_quantity'] + 1; ?>)">+</button>
                                    </div>
                                </td>
                                <td>
                                    <span class="cart-item-subtotal"><?php echo format_currency($item['thanh_tien']); ?></span>
                                </td>
                                <td>
                                    <button class="btn-remove-item" onclick="removeCartItem(<?php echo $item['cart_id']; ?>)" title="Xóa khỏi giỏ hàng">
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                    <a href="<?php echo base_url('san-pham'); ?>" style="color: var(--primary-color); font-weight: 600;">
                        &larr; Tiếp tục xem sản phẩm
                    </a>
                </div>
            </div>

            <!-- Tóm tắt giỏ hàng -->
            <div class="cart-summary">
                <h3>Tóm Tắt Đơn Hàng</h3>

                <div class="summary-row">
                    <span>Tổng số lượng:</span>
                    <strong><?php echo $cart['total_quantity']; ?> món</strong>
                </div>
                <div class="summary-row">
                    <span>Tạm tính:</span>
                    <strong><?php echo format_currency($cart['total_amount']); ?></strong>
                </div>
                <div class="summary-row">
                    <span>Phí vận chuyển & lắp đặt:</span>
                    <strong style="color: var(--success-color);">Miễn phí</strong>
                </div>
                <div class="summary-row total">
                    <span>Tổng thanh toán:</span>
                    <span class="summary-total-price"><?php echo format_currency($cart['total_amount']); ?></span>
                </div>

                <a href="<?php echo base_url('don-hang/thanh-toan'); ?>" class="btn btn-primary btn-block" style="margin-top: 25px; padding: 14px; font-size: 16px;">
                    Tiến Hành Đặt Hàng &rarr;
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$extraJs = ['assets/js/client/giohang.js'];
require_once __DIR__ . '/../../../includes/footer.php';
?>
