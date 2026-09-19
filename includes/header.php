<?php
$currentUser = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>

    <!-- CSS chung -->
    <link rel="stylesheet" href="<?php echo asset_url('assets/css/client/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('assets/css/client/chat-widget.css'); ?>">
    <?php if (isset($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <link rel="stylesheet" href="<?php echo asset_url($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        window.BASE_URL = "<?php echo BASE_URL; ?>";
    </script>
</head>
<body>

    <!-- Topbar -->
    <div class="topbar">
        <div class="container">
            <div class="topbar-contact">
                <span>📞 Hotline: <strong>1900 6868</strong></span>
                <span>📍 Showroom: 123 Nguyễn Huệ, Quận 1, TP.HCM</span>
            </div>
            <div class="topbar-links">
                <a href="<?php echo base_url('don-hang/tra-cuu'); ?>">🚚 Tra cứu đơn hàng</a>
                <?php if ($currentUser): ?>
                    <span>Xin chào, <strong><?php echo htmlspecialchars($currentUser['ho_ten']); ?></strong></span>
                    <a href="<?php echo base_url('auth/profile'); ?>">Tài khoản</a>
                    <a href="<?php echo base_url('don-hang/lich-su'); ?>">Đơn mua</a>
                    <a href="<?php echo base_url('auth/logout'); ?>">Đăng xuất</a>
                <?php else: ?>
                    <a href="<?php echo base_url('auth/login'); ?>">Đăng nhập</a>
                    <a href="<?php echo base_url('auth/register'); ?>">Đăng ký</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="main-header">
        <div class="container header-wrapper">
            <!-- Logo -->
            <div class="logo">
                <a href="<?php echo base_url(); ?>">
                    🏛️ <span>Shop Nội Thất</span>
                </a>
                <span class="logo-sub">Luxury Furniture & Decor</span>
            </div>

            <!-- Search bar -->
            <div class="header-search">
                <form action="<?php echo base_url('san-pham'); ?>" method="GET" class="search-form">
                    <input type="text" name="keyword" class="search-input" placeholder="Tìm sofa, bàn ăn, giường ngủ, đèn chùm..." value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>">
                    <button type="submit" class="search-btn">Tìm kiếm</button>
                </form>
            </div>

            <!-- Header Actions -->
            <div class="header-actions">
                <a href="<?php echo base_url('gio-hang'); ?>" class="action-item">
                    <div class="cart-icon-wrap">
                        🛒
                        <span class="cart-badge" style="display:none;">0</span>
                    </div>
                    <span>Giỏ hàng</span>
                </a>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="main-nav">
            <div class="container">
                <ul class="nav-list">
                    <li class="nav-item <?php echo (!isset($_GET['url']) || empty($_GET['url'])) ? 'active' : ''; ?>">
                        <a href="<?php echo base_url(); ?>">Trang chủ</a>
                    </li>
                    <li class="nav-item <?php echo (isset($_GET['url']) && $_GET['url'] === 'san-pham') ? 'active' : ''; ?>">
                        <a href="<?php echo base_url('san-pham'); ?>">Tất cả sản phẩm</a>
                    </li>
                    <li class="nav-item"><a href="<?php echo base_url('danh-muc/sofa-ghe-thu-gian'); ?>">Sofa & Ghế</a></li>
                    <li class="nav-item"><a href="<?php echo base_url('danh-muc/ban-an-ban-tra'); ?>">Bàn Ăn & Bàn Trà</a></li>
                    <li class="nav-item"><a href="<?php echo base_url('danh-muc/giuong-ngu'); ?>">Giường Ngủ</a></li>
                    <li class="nav-item"><a href="<?php echo base_url('danh-muc/tu-ke-trang-tri'); ?>">Tủ & Kệ</a></li>
                    <li class="nav-item"><a href="<?php echo base_url('danh-muc/den-trang-tri'); ?>">Đèn Trang Trí</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Thông báo Flash Messages -->
    <div class="container" style="margin-top: 20px;">
        <?php foreach (['auth_success', 'auth_error', 'login_error', 'order_success', 'order_error', 'profile_success', 'profile_error', 'cart_empty', 'register_error'] as $key): ?>
            <?php if (has_flash($key)): $flash = get_flash($key); ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php if (is_array($flash['message'])): ?>
                        <ul style="margin-left: 20px; margin-bottom: 0;">
                            <?php foreach ($flash['message'] as $msg): ?>
                                <li><?php echo is_array($msg) ? json_encode($msg) : htmlspecialchars($msg); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <?php echo $flash['message']; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
