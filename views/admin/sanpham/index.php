<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Quản Lý Sản Phẩm Nội Thất</h1>
        <p style="color: #64748b; font-size: 14px;">Quản lý kho hàng, giá niêm yết và tồn kho sản phẩm</p>
    </div>
    <a href="<?php echo base_url('admin/san-pham/them'); ?>" class="btn-admin btn-admin-primary">
        + Thêm Sản Phẩm Mới
    </a>
</div>

<!-- Lọc sản phẩm -->
<div class="card-box" style="padding: 16px 24px; margin-bottom: 20px;">
    <form action="<?php echo base_url('admin/san-pham'); ?>" method="GET" style="display:flex; gap:15px; flex-wrap:wrap; align-items:center;">
        <input type="text" name="keyword" placeholder="Tìm tên sản phẩm..." value="<?php echo htmlspecialchars($filters['keyword'] ?? ''); ?>" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; min-width: 250px;">

        <select name="danh_muc_id" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
            <option value="">-- Tất cả danh mục --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo (isset($filters['danh_muc_id']) && $filters['danh_muc_id'] == $cat['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['ten_danh_muc']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn-admin btn-admin-primary">Lọc Dữ Liệu</button>
        <a href="<?php echo base_url('admin/san-pham'); ?>" style="font-size: 13px; color: #64748b; margin-left: 5px;">Đặt lại</a>
    </form>
</div>

<div class="card-box">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 70px;">Hình</th>
                    <th>Tên sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Giá bán</th>
                    <th>Tồn kho</th>
                    <th>Nổi bật</th>
                    <th>Trạng thái</th>
                    <th style="text-align: right;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: #94a3b8; padding: 30px;">Không tìm thấy sản phẩm nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?php echo $p['id']; ?></td>
                            <td>
                                <img src="<?php echo !empty($p['hinh_anh']) ? asset_url($p['hinh_anh']) : asset_url('assets/images/default.jpg'); ?>" onerror="this.src='https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=100&q=80'" style="width: 50px; height: 50px; border-radius: 6px; object-fit: cover; border: 1px solid #e2e8f0;">
                            </td>
                            <td>
                                <div><strong><?php echo htmlspecialchars($p['ten_san_pham']); ?></strong></div>
                                <?php if (!empty($p['chat_lieu'])): ?>
                                    <small style="color: #64748b;">CL: <?php echo htmlspecialchars($p['chat_lieu']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($p['ten_danh_muc'] ?? 'Chưa phân loại'); ?></td>
                            <td>
                                <strong style="color: #e67e22;"><?php echo format_currency($p['gia_khuyen_mai'] > 0 ? $p['gia_khuyen_mai'] : $p['gia']); ?></strong>
                                <?php if ($p['gia_khuyen_mai'] > 0): ?>
                                    <br><small style="color: #94a3b8; text-decoration: line-through;"><?php echo format_currency($p['gia']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: <?php echo ($p['so_luong_ton'] <= 5) ? '#ef4444' : '#10b981'; ?>;">
                                    <?php echo $p['so_luong_ton']; ?>
                                </strong>
                            </td>
                            <td>
                                <?php if ($p['noi_bat'] == 1): ?>
                                    <span style="color: #f59e0b; font-weight:700;">★ Nổi bật</span>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo ($p['trang_thai'] == 1) ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ($p['trang_thai'] == 1) ? 'Kinh doanh' : 'Ngừng bán'; ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?php echo base_url('admin/san-pham/sua/' . $p['id']); ?>" class="btn-admin btn-admin-primary btn-admin-sm">Sửa</a>
                                <button type="button" onclick="confirmDelete('<?php echo base_url('admin/san-pham/xoa/' . $p['id']); ?>')" class="btn-admin btn-admin-danger btn-admin-sm">Xóa</button>
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
