<?php
$extraCss = ['assets/css/client/sanpham.css'];
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <div style="padding: 20px 0 10px 0; font-size: 14px; color: var(--text-muted);">
        <a href="<?php echo base_url(); ?>">Trang chủ</a> &raquo;
        <span><?php echo htmlspecialchars($pageTitle); ?></span>
    </div>

    <div class="shop-layout">
        <!-- Sidebar Bộ Lọc -->
        <aside class="filter-sidebar">
            <div class="filter-header">
                <h3>Bộ Lọc Tìm Kiếm</h3>
                <button type="button" class="btn-reset-filter" id="btn-reset-filter">Xóa bộ lọc</button>
            </div>

            <form id="filter-form">
                <!-- Giữ lại từ khóa tìm kiếm nếu có -->
                <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($filters['keyword'] ?? ''); ?>">

                <!-- Lọc Danh mục -->
                <div class="filter-group">
                    <h4 class="filter-title">Danh Mục</h4>
                    <div class="filter-list">
                        <label class="filter-item">
                            <input type="radio" name="danh_muc" value="" <?php echo empty($filters['danh_muc']) ? 'checked' : ''; ?>>
                            <span>Tất cả danh mục</span>
                        </label>
                        <?php foreach ($categories as $cat): ?>
                            <label class="filter-item">
                                <input type="radio" name="danh_muc" value="<?php echo $cat['slug']; ?>" <?php echo ($filters['danh_muc'] === $cat['slug']) ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars($cat['ten_danh_muc']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Lọc Khoảng Giá -->
                <div class="filter-group">
                    <h4 class="filter-title">Khoảng Giá (VNĐ)</h4>
                    <div class="price-inputs">
                        <input type="number" name="gia_min" placeholder="Từ" value="<?php echo htmlspecialchars($filters['gia_min'] ?? ''); ?>">
                        <span>-</span>
                        <input type="number" name="gia_max" placeholder="Đến" value="<?php echo htmlspecialchars($filters['gia_max'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Lọc Chất liệu -->
                <?php if (!empty($attributes['materials'])): ?>
                <div class="filter-group">
                    <h4 class="filter-title">Chất Liệu</h4>
                    <div class="filter-list">
                        <label class="filter-item">
                            <input type="radio" name="chat_lieu" value="" <?php echo empty($filters['chat_lieu']) ? 'checked' : ''; ?>>
                            <span>Tất cả chất liệu</span>
                        </label>
                        <?php foreach ($attributes['materials'] as $mat): ?>
                            <label class="filter-item">
                                <input type="radio" name="chat_lieu" value="<?php echo htmlspecialchars($mat); ?>" <?php echo ($filters['chat_lieu'] === $mat) ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars($mat); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Lọc Màu sắc -->
                <?php if (!empty($attributes['colors'])): ?>
                <div class="filter-group">
                    <h4 class="filter-title">Màu Sắc</h4>
                    <div class="filter-list">
                        <label class="filter-item">
                            <input type="radio" name="mau_sac" value="" <?php echo empty($filters['mau_sac']) ? 'checked' : ''; ?>>
                            <span>Tất cả màu sắc</span>
                        </label>
                        <?php foreach ($attributes['colors'] as $col): ?>
                            <label class="filter-item">
                                <input type="radio" name="mau_sac" value="<?php echo htmlspecialchars($col); ?>" <?php echo ($filters['mau_sac'] === $col) ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars($col); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </aside>

        <!-- Main Product Area -->
        <main class="shop-main">
            <!-- Toolbar -->
            <div class="shop-toolbar">
                <div class="toolbar-result-count" id="result-count">
                    Hiển thị <strong><?php echo count($products); ?></strong> sản phẩm nội thất
                </div>
                <div class="toolbar-sort">
                    <label for="sort-select">Sắp xếp theo:</label>
                    <select id="sort-select" class="sort-select">
                        <option value="moi_nhat" <?php echo ($filters['sort'] === 'moi_nhat') ? 'selected' : ''; ?>>Mới nhất</option>
                        <option value="gia_asc" <?php echo ($filters['sort'] === 'gia_asc') ? 'selected' : ''; ?>>Giá tăng dần</option>
                        <option value="gia_desc" <?php echo ($filters['sort'] === 'gia_desc') ? 'selected' : ''; ?>>Giá giảm dần</option>
                        <option value="xem_nhieu" <?php echo ($filters['sort'] === 'xem_nhieu') ? 'selected' : ''; ?>>Xem nhiều nhất</option>
                    </select>
                </div>
            </div>

            <!-- Loading Spinner -->
            <div class="loading-overlay" id="loading-spinner">
                <div class="loading-spinner"></div>
                <p>Đang tải dữ liệu sản phẩm...</p>
            </div>

            <!-- Danh sách sản phẩm (AJAX Container) -->
            <div class="product-grid" id="product-list-container">
                <?php if (empty($products)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: #fff; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <h3 style="color: #64748b; margin-bottom: 10px;">Không tìm thấy sản phẩm nào!</h3>
                        <p style="color: #94a3b8; font-size: 14px;">Hãy thử điều chỉnh lại bộ lọc hoặc khoảng giá.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <?php
                            $discount = ($p['gia_khuyen_mai'] > 0 && $p['gia'] > 0) ? round((($p['gia'] - $p['gia_khuyen_mai']) / $p['gia']) * 100) : 0;
                            $realPrice = $p['gia_khuyen_mai'] > 0 ? $p['gia_khuyen_mai'] : $p['gia'];
                            $detailUrl = base_url('san-pham/' . ($p['slug'] ?? $p['id']));
                        ?>
                        <div class="product-card">
                            <?php if ($discount > 0): ?>
                                <div class="product-badge-sale">-<?php echo $discount; ?>%</div>
                            <?php endif; ?>
                            <div class="product-img-box">
                                <a href="<?php echo $detailUrl; ?>">
                                    <img src="<?php echo !empty($p['hinh_anh']) ? asset_url($p['hinh_anh']) : asset_url('assets/images/default.jpg'); ?>" onerror="this.src='https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=400&q=80'" alt="<?php echo htmlspecialchars($p['ten_san_pham']); ?>" loading="lazy">
                                </a>
                            </div>
                            <div class="product-body">
                                <div class="product-cat"><?php echo htmlspecialchars($p['ten_danh_muc'] ?? 'Nội thất'); ?></div>
                                <h3 class="product-title">
                                    <a href="<?php echo $detailUrl; ?>"><?php echo htmlspecialchars($p['ten_san_pham']); ?></a>
                                </h3>
                                <div class="product-meta">
                                    <?php if (!empty($p['chat_lieu'])): ?>
                                        <span>Chất liệu: <?php echo htmlspecialchars($p['chat_lieu']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-price-box">
                                    <span class="price-current"><?php echo format_currency($realPrice); ?></span>
                                    <?php if ($p['gia_khuyen_mai'] > 0): ?>
                                        <span class="price-old"><?php echo format_currency($p['gia']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <button class="btn-add-cart" onclick="quickAddToCart(<?php echo $p['id']; ?>)">
                                    Thêm vào giỏ hàng
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php
$extraJs = ['assets/js/client/sanpham.js'];
require_once __DIR__ . '/../../../includes/footer.php';
?>
