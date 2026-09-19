<?php
$currentAdmin = $_SESSION['admin'] ?? [];
$currentUrl = $_GET['url'] ?? '';
?>
<!-- Sidebar -->
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        🏛️ <span>NỘI THẤT ADMIN</span>
    </div>

    <div class="sidebar-nav">
        <div class="sidebar-section-title">Tổng quan</div>
        <a href="<?php echo base_url('admin'); ?>" class="sidebar-link <?php echo ($currentUrl === 'admin' || empty($currentUrl)) ? 'active' : ''; ?>">
            📊 Bảng điều khiển
        </a>

        <div class="sidebar-section-title" style="margin-top: 15px;">Quản lý kho hàng</div>
        <a href="<?php echo base_url('admin/danh-muc'); ?>" class="sidebar-link <?php echo str_starts_with($currentUrl, 'admin/danh-muc') ? 'active' : ''; ?>">
            📁 Danh mục sản phẩm
        </a>
        <a href="<?php echo base_url('admin/san-pham'); ?>" class="sidebar-link <?php echo str_starts_with($currentUrl, 'admin/san-pham') ? 'active' : ''; ?>">
            🛋️ Quản lý sản phẩm
        </a>

        <div class="sidebar-section-title" style="margin-top: 15px;">Kinh doanh & Vận hành</div>
        <a href="<?php echo base_url('admin/don-hang'); ?>" class="sidebar-link <?php echo (str_starts_with($currentUrl, 'admin/don-hang')) ? 'active' : ''; ?>">
            📦 Quản lý đơn hàng
        </a>
        <a href="<?php echo base_url('admin/van-chuyen'); ?>" class="sidebar-link <?php echo (str_starts_with($currentUrl, 'admin/van-chuyen')) ? 'active' : ''; ?>">
            🚚 Vận chuyển & Giao hàng
        </a>
        <a href="<?php echo base_url('admin/thanh-toan'); ?>" class="sidebar-link <?php echo (str_starts_with($currentUrl, 'admin/thanh-toan')) ? 'active' : ''; ?>">
            💳 Đối soát thanh toán
        </a>
        <a href="<?php echo base_url('admin/tu-van'); ?>" class="sidebar-link <?php echo (str_starts_with($currentUrl, 'admin/tu-van')) ? 'active' : ''; ?>">
            🤖 Lịch sử tư vấn AI
        </a>

        <div class="sidebar-section-title" style="margin-top: 15px;">Người dùng & Hệ thống</div>
        <a href="<?php echo base_url('admin/khach-hang'); ?>" class="sidebar-link <?php echo str_starts_with($currentUrl, 'admin/khach-hang') ? 'active' : ''; ?>">
            👥 Quản lý khách hàng
        </a>
        <a href="<?php echo base_url('admin/nhan-vien'); ?>" class="sidebar-link <?php echo str_starts_with($currentUrl, 'admin/nhan-vien') ? 'active' : ''; ?>">
            🛡️ Nhân viên & Quản trị
        </a>
    </div>

    <div class="sidebar-footer">
        <a href="<?php echo base_url(); ?>" target="_blank" style="color:#94a3b8; display:block; margin-bottom:10px;">
            🌐 Xem trang bán hàng
        </a>
        <a href="<?php echo base_url('admin/logout'); ?>" style="color:#ef4444; font-weight:600;">
            🚪 Đăng xuất
        </a>
    </div>
</aside>

<!-- Main Container -->
<div class="admin-main">
    <!-- Topbar -->
    <header class="admin-topbar">
        <div>
            <strong>Hệ Thống Quản Trị Cửa Hàng Nội Thất</strong>
        </div>
        <div class="topbar-user">
            <span>Xin chào, <strong><?php echo htmlspecialchars($currentAdmin['ho_ten'] ?? 'Quản trị viên'); ?></strong></span>
            <span class="user-badge"><?php echo htmlspecialchars($currentAdmin['vai_tro'] ?? 'admin'); ?></span>
        </div>
    </header>

    <!-- Admin Content Area -->
    <main class="admin-content">
        <!-- Flash Alert Admin -->
        <?php foreach (['admin_msg', 'admin_error', 'dm_success', 'dm_error', 'sp_success', 'sp_error', 'order_success', 'order_error', 'nv_success', 'nv_error', 'kh_success', 'vc_success', 'vc_error', 'tt_success', 'tt_error'] as $key): ?>
            <?php if (has_flash($key)): $flash = get_flash($key); ?>
                <div class="badge badge-<?php echo $flash['type']; ?>" style="display:block; padding:12px 20px; font-size:14px; margin-bottom:20px; border-radius:6px;">
                    <?php if (is_array($flash['message'])): ?>
                        <?php echo implode(', ', $flash['message']); ?>
                    <?php else: ?>
                        <?php echo $flash['message']; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
