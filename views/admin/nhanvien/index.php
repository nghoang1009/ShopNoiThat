<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Quản Lý Nhân Viên & Quản Trị</h1>
        <p style="color: #64748b; font-size: 14px;">Danh sách tài khoản quản trị hệ thống và nhân viên bán hàng</p>
    </div>
    <a href="<?php echo base_url('admin/nhan-vien/them'); ?>" class="btn-admin btn-admin-primary">
        + Thêm Nhân Viên Mới
    </a>
</div>

<div class="card-box">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Họ và tên</th>
                    <th>Tài khoản</th>
                    <th>Email</th>
                    <th>Điện thoại</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th style="text-align: right;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($staffs)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 30px;">Chưa có tài khoản nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($staffs as $s): ?>
                        <tr>
                            <td><?php echo $s['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($s['ho_ten']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($s['tai_khoan']); ?></code></td>
                            <td><?php echo htmlspecialchars($s['email']); ?></td>
                            <td><?php echo htmlspecialchars($s['sdt'] ?? '-'); ?></td>
                            <td>
                                <span class="user-badge" style="<?php echo ($s['vai_tro'] === 'admin') ? 'background:#fef3c7; color:#b45309;' : ''; ?>">
                                    <?php echo ($s['vai_tro'] === 'admin') ? 'Quản Trị Viên' : 'Nhân Viên'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo ($s['trang_thai'] == 1) ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ($s['trang_thai'] == 1) ? 'Hoạt động' : 'Tạm khóa'; ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?php echo base_url('admin/nhan-vien/sua/' . $s['id']); ?>" class="btn-admin btn-admin-primary btn-admin-sm">Sửa</a>
                                <?php if ($s['id'] != 1 && (isset($_SESSION['admin']['id']) && $_SESSION['admin']['id'] != $s['id'])): ?>
                                    <button type="button" onclick="confirmDelete('<?php echo base_url('admin/nhan-vien/xoa/' . $s['id']); ?>')" class="btn-admin btn-admin-danger btn-admin-sm">Xóa</button>
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
