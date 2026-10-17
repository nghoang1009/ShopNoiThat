<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
$isAdminRole = (($_SESSION['admin']['vai_tro'] ?? '') === 'admin');
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Quản Lý Nhà Cung Cấp</h1>
        <p style="color: #64748b; font-size: 14px;">Danh sách đối tác cung cấp hàng nội thất và số sản phẩm liên kết</p>
    </div>
    <a href="<?php echo base_url('admin/nha-cung-cap/them'); ?>" class="btn-admin btn-admin-primary">
        + Thêm Nhà Cung Cấp
    </a>
</div>

<div class="card-box" style="margin-bottom: 20px;">
    <form action="<?php echo base_url('admin/nha-cung-cap'); ?>" method="GET" style="display:flex; gap:10px;">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm theo tên, số điện thoại hoặc email..."
               style="flex:1; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        <button type="submit" class="btn-admin btn-admin-primary">Tìm kiếm</button>
        <?php if ($search !== ''): ?>
            <a href="<?php echo base_url('admin/nha-cung-cap'); ?>" class="btn-admin">Xóa lọc</a>
        <?php endif; ?>
    </form>
</div>

<div class="card-box">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Tên nhà cung cấp</th>
                    <th>Điện thoại</th>
                    <th>Email</th>
                    <th>Địa chỉ</th>
                    <th style="text-align: center;">Sản phẩm</th>
                    <th style="text-align: right;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 30px;">
                            <?php echo $search !== '' ? 'Không tìm thấy nhà cung cấp phù hợp.' : 'Chưa có nhà cung cấp nào.'; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td><?php echo (int)$s['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($s['ten_nha_cung_cap']); ?></strong></td>
                            <td><?php echo htmlspecialchars($s['sdt']); ?></td>
                            <td><?php echo htmlspecialchars($s['email'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($s['dia_chi'] ?? '-'); ?></td>
                            <td style="text-align: center;">
                                <a href="<?php echo base_url('admin/san-pham'); ?>"><span class="badge <?php echo $s['so_san_pham'] > 0 ? 'badge-success' : 'badge-secondary'; ?>"><?php echo (int)$s['so_san_pham']; ?></span></a>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?php echo base_url('admin/nha-cung-cap/sua/' . $s['id']); ?>" class="btn-admin btn-admin-primary btn-admin-sm">Sửa</a>
                                <?php if ($isAdminRole): ?>
                                    <button type="button" onclick="confirmDelete('<?php echo base_url('admin/nha-cung-cap/xoa/' . $s['id']); ?>', 'Bạn có chắc muốn xóa nhà cung cấp này không?')" class="btn-admin btn-admin-danger btn-admin-sm">Xóa</button>
                                <?php endif; ?>
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
