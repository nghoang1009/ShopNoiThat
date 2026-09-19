<?php
require_once __DIR__ . '/../../../includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <div class="hero-subtitle">Bộ Sưu Tập Mới 2026</div>
                <h1 class="hero-title">Kiến Tạo Không Gian Sống Sang Trọng & Tinh Tế</h1>
                <p class="hero-desc">Khám phá các thiết kế nội thất cao cấp từ gỗ tự nhiên, da Ý và đá Ceramic chống trầy. Mang đến sự tiện nghi và đẳng cấp cho ngôi nhà của bạn.</p>
                <div style="display: flex; gap: 15px;">
                    <a href="<?php echo base_url('san-pham'); ?>" class="btn btn-primary">Khám Phá Ngay</a>
                    <a href="<?php echo base_url('danh-muc/sofa-ghe-thu-gian'); ?>" class="btn btn-outline">Xem Sofa Cao Cấp</a>
                </div>
            </div>
            <div class="hero-image" style="flex: 1; text-align: center;">
                <img src="<?php echo asset_url('assets/images/banner-hero.jpg'); ?>" onerror="this.src='https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80'" alt="Nội thất sang trọng" style="border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); width: 100%; max-height: 400px; object-fit: cover;">
            </div>
        </div>
    </div>
</section>

<!-- Features Bar -->
<div class="container">
    <div class="features-bar">
        <div class="feature-card">
            <div class="feature-icon">🚚</div>
            <div class="feature-info">
                <h4>Giao Hàng & Lắp Đặt</h4>
                <p>Miễn phí toàn quốc cho đơn từ 5tr</p>
            </div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🛡️</div>
            <div class="feature-info">
                <h4>Bảo Hành 5 Năm</h4>
                <p>Cam kết gỗ thật và da cao cấp</p>
            </div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🔄</div>
            <div class="feature-info">
                <h4>Đổi Trả Dễ Dàng</h4>
                <p>Đổi mới trong 30 ngày nếu có lỗi</p>
            </div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">⭐</div>
            <div class="feature-info">
                <h4>Chất Lượng Thượng Hạng</h4>
                <p>Tiêu chuẩn xuất khẩu Châu Âu</p>
            </div>
        </div>
    </div>
</div>

<!-- Danh mục nổi bật -->
<section class="container" style="margin-top: 20px;">
    <div class="section-header">
        <h2 class="section-title">Danh Mục Nội Thất Nổi Bật</h2>
        <a href="<?php echo base_url('san-pham'); ?>" class="view-all-link">Xem tất cả &rarr;</a>
    </div>

    <div class="category-grid">
        <?php foreach ($categories as $cat): ?>
            <a href="<?php echo base_url('danh-muc/' . $cat['slug']); ?>" class="category-card">
                <div class="category-img-wrap">
                    <img src="<?php echo !empty($cat['hinh_anh']) ? asset_url($cat['hinh_anh']) : asset_url('assets/images/default.jpg'); ?>" onerror="this.src='https://images.unsplash.com/photo-1586023492125-27b2c045efd7?auto=format&fit=crop&w=200&q=80'" alt="<?php echo htmlspecialchars($cat['ten_danh_muc']); ?>">
                </div>
                <h3><?php echo htmlspecialchars($cat['ten_danh_muc']); ?></h3>
                <span><?php echo (int)($cat['tong_san_pham'] ?? 0); ?> sản phẩm</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Sản phẩm nổi bật -->
<section class="container">
    <div class="section-header">
        <h2 class="section-title">Sản Phẩm Nổi Bật</h2>
        <a href="<?php echo base_url('san-pham'); ?>" class="view-all-link">Xem tất cả &rarr;</a>
    </div>

    <div class="product-grid">
        <?php foreach ($featuredProducts as $product): ?>
            <?php
                $discountPercent = ($product['gia_khuyen_mai'] > 0 && $product['gia'] > 0) ? round((($product['gia'] - $product['gia_khuyen_mai']) / $product['gia']) * 100) : 0;
            ?>
            <div class="product-card">
                <?php if ($discountPercent > 0): ?>
                    <div class="product-badge-sale">-<?php echo $discountPercent; ?>%</div>
                <?php endif; ?>
                <div class="product-img-box">
                    <a href="<?php echo base_url('san-pham/' . ($product['slug'] ?? $product['id'])); ?>">
                        <img src="<?php echo !empty($product['hinh_anh']) ? asset_url($product['hinh_anh']) : asset_url('assets/images/default.jpg'); ?>" onerror="this.src='https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=400&q=80'" alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>" loading="lazy">
                    </a>
                </div>
                <div class="product-body">
                    <div class="product-cat"><?php echo htmlspecialchars($product['ten_danh_muc'] ?? 'Nội thất'); ?></div>
                    <h3 class="product-title">
                        <a href="<?php echo base_url('san-pham/' . ($product['slug'] ?? $product['id'])); ?>"><?php echo htmlspecialchars($product['ten_san_pham']); ?></a>
                    </h3>
                    <div class="product-meta">
                        <?php if (!empty($product['chat_lieu'])): ?>
                            <span>Chất liệu: <?php echo htmlspecialchars($product['chat_lieu']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-price-box">
                        <span class="price-current">
                            <?php echo format_currency($product['gia_khuyen_mai'] > 0 ? $product['gia_khuyen_mai'] : $product['gia']); ?>
                        </span>
                        <?php if ($product['gia_khuyen_mai'] > 0): ?>
                            <span class="price-old"><?php echo format_currency($product['gia']); ?></span>
                        <?php endif; ?>
                    </div>
                    <button class="btn-add-cart" onclick="quickAddToCart(<?php echo $product['id']; ?>)">
                        Thêm vào giỏ hàng
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Sản phẩm khuyến mãi Hot -->
<?php if (!empty($saleProducts)): ?>
<section class="container">
    <div class="section-header">
        <h2 class="section-title">Khuyến Mãi Siêu Hot 🔥</h2>
        <a href="<?php echo base_url('san-pham'); ?>" class="view-all-link">Xem tất cả &rarr;</a>
    </div>

    <div class="product-grid">
        <?php foreach ($saleProducts as $product): ?>
            <?php
                $discountPercent = ($product['gia_khuyen_mai'] > 0 && $product['gia'] > 0) ? round((($product['gia'] - $product['gia_khuyen_mai']) / $product['gia']) * 100) : 0;
            ?>
            <div class="product-card">
                <?php if ($discountPercent > 0): ?>
                    <div class="product-badge-sale">-<?php echo $discountPercent; ?>%</div>
                <?php endif; ?>
                <div class="product-img-box">
                    <a href="<?php echo base_url('san-pham/' . ($product['slug'] ?? $product['id'])); ?>">
                        <img src="<?php echo !empty($product['hinh_anh']) ? asset_url($product['hinh_anh']) : asset_url('assets/images/default.jpg'); ?>" onerror="this.src='https://images.unsplash.com/photo-1586023492125-27b2c045efd7?auto=format&fit=crop&w=400&q=80'" alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>" loading="lazy">
                    </a>
                </div>
                <div class="product-body">
                    <div class="product-cat"><?php echo htmlspecialchars($product['ten_danh_muc'] ?? 'Nội thất'); ?></div>
                    <h3 class="product-title">
                        <a href="<?php echo base_url('san-pham/' . ($product['slug'] ?? $product['id'])); ?>"><?php echo htmlspecialchars($product['ten_san_pham']); ?></a>
                    </h3>
                    <div class="product-meta">
                        <?php if (!empty($product['chat_lieu'])): ?>
                            <span>Chất liệu: <?php echo htmlspecialchars($product['chat_lieu']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-price-box">
                        <span class="price-current">
                            <?php echo format_currency($product['gia_khuyen_mai'] > 0 ? $product['gia_khuyen_mai'] : $product['gia']); ?>
                        </span>
                        <span class="price-old"><?php echo format_currency($product['gia']); ?></span>
                    </div>
                    <button class="btn-add-cart" onclick="quickAddToCart(<?php echo $product['id']; ?>)">
                        Thêm vào giỏ hàng
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php
$extraJs = ['assets/js/client/sanpham.js'];
require_once __DIR__ . '/../../../includes/footer.php';
?>
