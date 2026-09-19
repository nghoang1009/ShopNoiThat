<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Quản Lý Danh Mục Nội Thất</h1>
        <p style="color: #64748b; font-size: 14px;">Danh sách các phân loại đồ nội thất trong hệ thống</p>
    </div>
    <a href="<?php echo base_url('admin/danh-muc/them'); ?>" class="btn-admin btn-admin-primary">
        + Thêm Danh Mục Mới
    </a>
</div>

<div class="card-box">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th style="width: 80px;">Hình ảnh</th>
                    <th>Tên danh mục</th>
                    <th>Đường dẫn (Slug)</th>
                    <th>Số sản phẩm</th>
                    <th>Trạng thái</th>
                    <th style="text-align: right;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 30px;">Chưa có danh mục nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?php echo $cat['id']; ?></td>
                            <td>
                                <img src="<?php echo !empty($cat['hinh_anh']) ? asset_url($cat['hinh_anh']) : asset_url('assets/images/default.jpg'); ?>" onerror="this.src='https://images.unsplash.com/photo-1586023492125-27b2c045efd7?auto=format&fit=crop&w=100&q=80'" style="width: 50px; height: 50px; border-radius: 6px; object-fit: cover; border: 1px solid #e2e8f0;">
                            </td>
                            <td><strong><?php echo htmlspecialchars($cat['ten_danh_muc']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                            <td><strong><?php echo (int)($cat['tong_san_pham'] ?? 0); ?></strong> món</td>
                            <td>
                                <span class="badge <?php echo ($cat['trang_thai'] == 1) ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ($cat['trang_thai'] == 1) ? 'Hoạt động' : 'Tạm ẩn'; ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?php echo base_url('admin/danh-muc/sua/' . $cat['id']); ?>" class="btn-admin btn-admin-primary btn-admin-sm">Sửa</a>
                                <button type="button" onclick="confirmDelete('<?php echo base_url('admin/danh-muc/xoa/' . $cat['id']); ?>')" class="btn-admin btn-admin-danger btn-admin-sm">Xóa</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../../../includes/admin_footer.php';
?>
