<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Quản Lý Khách Hàng</h1>
        <p style="color: #64748b; font-size: 14px;">Danh sách tài khoản thành viên đã đăng ký</p>
    </div>
</div>

<div class="card-box">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Họ và tên</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Địa chỉ</th>
                    <th>Ngày tham gia</th>
                    <th>Trạng thái</th>
                    <th style="text-align: right;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 30px;">Chưa có khách hàng nào đăng ký.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td><?php echo $c['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($c['ho_ten']); ?></strong></td>
                            <td><?php echo htmlspecialchars($c['email']); ?></td>
                            <td><?php echo htmlspecialchars($c['sdt']); ?></td>
                            <td><?php echo htmlspecialchars($c['dia_chi'] ?? 'Chưa cập nhật'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($c['created_at'])); ?></td>
                            <td>
                                <span class="badge <?php echo ($c['trang_thai'] == 1) ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ($c['trang_thai'] == 1) ? 'Hoạt động' : 'Bị khóa'; ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?php echo base_url('admin/khach-hang/doi-trang-thai/' . $c['id']); ?>" class="btn-admin <?php echo ($c['trang_thai'] == 1) ? 'btn-admin-danger' : 'btn-admin-primary'; ?> btn-admin-sm">
                                    <?php echo ($c['trang_thai'] == 1) ? 'Khóa' : 'Mở khóa'; ?>
                                </a>
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
