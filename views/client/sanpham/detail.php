<?php
$extraCss = ['assets/css/client/sanpham.css'];
require_once __DIR__ . '/../../../includes/header.php';

$discount = ($product['gia_khuyen_mai'] > 0 && $product['gia'] > 0) ? round((($product['gia'] - $product['gia_khuyen_mai']) / $product['gia']) * 100) : 0;
$realPrice = $product['gia_khuyen_mai'] > 0 ? $product['gia_khuyen_mai'] : $product['gia'];
$extraImages = !empty($product['hinh_anh_phu']) ? json_decode($product['hinh_anh_phu'], true) : [];
?>

<div class="container">
    <!-- Breadcrumb -->
    <div style="padding: 20px 0 10px 0; font-size: 14px; color: var(--text-muted);">
        <a href="<?php echo base_url(); ?>">Trang chủ</a> &raquo;
        <a href="<?php echo base_url('danh-muc/' . ($product['danh_muc_slug'] ?? '')); ?>"><?php echo htmlspecialchars($product['ten_danh_muc'] ?? 'Danh mục'); ?></a> &raquo;
        <span><?php echo htmlspecialchars($product['ten_san_pham']); ?></span>
    </div>

    <div class="product-detail-layout">
        <!-- Gallery Images -->
        <div class="detail-gallery">
            <div class="gallery-main">
                <img id="main-product-img" src="<?php echo !empty($product['hinh_anh']) ? asset_url($product['hinh_anh']) : asset_url('assets/images/default.jpg'); ?>" onerror="this.src='https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80'" alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">
            </div>

            <?php if (!empty($extraImages)): ?>
            <div class="gallery-thumbs">
                <div class="thumb-item active" onclick="changeImage(this, '<?php echo !empty($product['hinh_anh']) ? asset_url($product['hinh_anh']) : asset_url('assets/images/default.jpg'); ?>')">
                    <img src="<?php echo !empty($product['hinh_anh']) ? asset_url($product['hinh_anh']) : asset_url('assets/images/default.jpg'); ?>" onerror="this.src='https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=200&q=80'" alt="Thumbnail">
                </div>
                <?php foreach ($extraImages as $img): ?>
                    <div class="thumb-item" onclick="changeImage(this, '<?php echo asset_url($img); ?>')">
                        <img src="<?php echo asset_url($img); ?>" onerror="this.src='https://images.unsplash.com/photo-1586023492125-27b2c045efd7?auto=format&fit=crop&w=200&q=80'" alt="Thumbnail">
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Info & Actions -->
        <div class="detail-info">
            <h1><?php echo htmlspecialchars($product['ten_san_pham']); ?></h1>

            <div class="detail-meta-row">
                <span>Danh mục: <strong><?php echo htmlspecialchars($product['ten_danh_muc'] ?? 'Nội thất'); ?></strong></span>
                <span>Tình trạng: <strong><?php echo ($product['so_luong_ton'] > 0) ? 'Còn hàng (' . $product['so_luong_ton'] . ')' : 'Hết hàng'; ?></strong></span>
                <span>Lượt xem: <strong><?php echo (int)$product['luot_xem']; ?></strong></span>
            </div>

            <div class="detail-price-box">
                <span class="price-current" style="font-size: 28px;">
                    <?php echo format_currency($realPrice); ?>
                </span>
                <?php if ($product['gia_khuyen_mai'] > 0): ?>
                    <span class="price-old" style="font-size: 18px;">
                        <?php echo format_currency($product['gia']); ?>
                    </span>
                    <span class="badge badge-danger" style="background:#fee2e2; color:#b91c1c; padding:3px 8px; border-radius:4px; font-weight:700;">
                        Tiết kiệm <?php echo $discount; ?>%
                    </span>
                <?php endif; ?>
            </div>

            <!-- Specifications Table -->
            <table class="detail-specs">
                <?php if (!empty($product['chat_lieu'])): ?>
                <tr>
                    <td>Chất liệu:</td>
                    <td><strong><?php echo htmlspecialchars($product['chat_lieu']); ?></strong></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($product['kich_thuoc'])): ?>
                <tr>
                    <td>Kích thước:</td>
                    <td><strong><?php echo htmlspecialchars($product['kich_thuoc']); ?></strong></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($product['mau_sac'])): ?>
                <tr>
                    <td>Màu sắc:</td>
                    <td><strong><?php echo htmlspecialchars($product['mau_sac']); ?></strong></td>
                </tr>
                <?php endif; ?>

                <tr>
                    <td>Bảo hành:</td>
                    <td><strong>5 năm chính hãng</strong> (bảo trì trọn đời)</td>
                </tr>
                <tr>
                    <td>Vận chuyển:</td>
                    <td>Miễn phí giao hàng & lắp đặt tại nhà</td>
                </tr>
            </table>

            <!-- Quantity & Add to Cart -->
            <?php if ($product['so_luong_ton'] > 0): ?>
            <div class="quantity-control">
                <label style="font-weight:600; font-size:14px;">Số lượng:</label>
                <div class="qty-input-group">
                    <button type="button" class="qty-btn" onclick="decreaseDetailQty()">-</button>
                    <input type="text" id="detail-quantity" class="qty-val" value="1" readonly>
                    <button type="button" class="qty-btn" onclick="increaseDetailQty(<?php echo $product['so_luong_ton']; ?>)">+</button>
                </div>
            </div>

            <div style="display: flex; gap: 15px;">
                <button type="button" id="btn-detail-add-cart" data-product-id="<?php echo $product['id']; ?>" class="btn btn-outline" style="flex:1;">
                    🛒 Thêm vào giỏ hàng
                </button>
                <button type="button" onclick="buyNow(<?php echo $product['id']; ?>)" class="btn btn-primary" style="flex:1;">
                    ⚡ Mua ngay
                </button>
            </div>
            <?php else: ?>
                <div class="alert alert-warning" style="margin-top:20px;">
                    Sản phẩm này tạm thời hết hàng trong kho. Vui lòng liên hệ hotline để đặt trước!
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Description Details -->
    <div style="background:#fff; border-radius:var(--radius-md); border:1px solid var(--border-color); padding:30px; margin-bottom:50px;">
        <h3 style="font-size:20px; color:var(--secondary-color); margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
            Mô Tả Sản Phẩm
        </h3>
        <div style="font-size:15px; line-height:1.8; color:#475569;">
            <?php echo !empty($product['mo_ta']) ? $product['mo_ta'] : '<p>Chưa có mô tả chi tiết cho sản phẩm này.</p>'; ?>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
    <div style="margin-bottom:60px;">
        <div class="section-header">
            <h2 class="section-title">Sản Phẩm Tương Tự</h2>
        </div>
        <div class="product-grid">
            <?php foreach ($relatedProducts as $rel): ?>
                <?php
                    $relDiscount = ($rel['gia_khuyen_mai'] > 0 && $rel['gia'] > 0) ? round((($rel['gia'] - $rel['gia_khuyen_mai']) / $rel['gia']) * 100) : 0;
                    $relPrice = $rel['gia_khuyen_mai'] > 0 ? $rel['gia_khuyen_mai'] : $rel['gia'];
                    $relUrl = base_url('san-pham/' . ($rel['slug'] ?? $rel['id']));
                ?>
                <div class="product-card">
                    <?php if ($relDiscount > 0): ?>
                        <div class="product-badge-sale">-<?php echo $relDiscount; ?>%</div>
                    <?php endif; ?>
                    <div class="product-img-box">
                        <a href="<?php echo $relUrl; ?>">
                            <img src="<?php echo !empty($rel['hinh_anh']) ? asset_url($rel['hinh_anh']) : asset_url('assets/images/default.jpg'); ?>" onerror="this.src='https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=400&q=80'" alt="<?php echo htmlspecialchars($rel['ten_san_pham']); ?>">
                        </a>
                    </div>
                    <div class="product-body">
                        <div class="product-cat"><?php echo htmlspecialchars($rel['ten_danh_muc'] ?? 'Nội thất'); ?></div>
                        <h3 class="product-title">
                            <a href="<?php echo $relUrl; ?>"><?php echo htmlspecialchars($rel['ten_san_pham']); ?></a>
                        </h3>
                        <div class="product-price-box">
                            <span class="price-current"><?php echo format_currency($relPrice); ?></span>
                            <?php if ($rel['gia_khuyen_mai'] > 0): ?>
                                <span class="price-old"><?php echo format_currency($rel['gia']); ?></span>
                            <?php endif; ?>
                        </div>
                        <button class="btn-add-cart" onclick="quickAddToCart(<?php echo $rel['id']; ?>)">
                            Thêm vào giỏ hàng
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function changeImage(el, src) {
    document.getElementById('main-product-img').src = src;
    document.querySelectorAll('.thumb-item').forEach(item => item.classList.remove('active'));
    el.classList.add('active');
}

function decreaseDetailQty() {
    const input = document.getElementById('detail-quantity');
    let val = parseInt(input.value) || 1;
    if (val > 1) input.value = val - 1;
}

function increaseDetailQty(maxStock) {
    const input = document.getElementById('detail-quantity');
    let val = parseInt(input.value) || 1;
    if (val < maxStock) input.value = val + 1;
    else showToast('Đã đạt số lượng tồn kho tối đa!', 'warning');
}

function buyNow(productId) {
    const qtyInput = document.getElementById('detail-quantity');
    const quantity = qtyInput ? parseInt(qtyInput.value) : 1;

    fetch(`${window.BASE_URL}/api/v1/gio-hang`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ san_pham_id: productId, so_luong: quantity })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            window.location.href = `${window.BASE_URL}/don-hang/thanh-toan`;
        } else {
            showToast(res.message || 'Lỗi thêm giỏ hàng!', 'danger');
        }
    });
}
</script>

<?php
$extraJs = ['assets/js/client/giohang.js', 'assets/js/client/sanpham.js'];
require_once __DIR__ . '/../../../includes/footer.php';
?>
