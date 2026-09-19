<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Cấu Hình Phương Thức & Phí Vận Chuyển</h1>
        <p style="color: #64748b; font-size: 14px;">Quản lý bảng giá cước giao hàng đồ nội thất</p>
    </div>
    <div style="display:flex; gap:10px;">
        <a href="<?php echo base_url('admin/van-chuyen'); ?>" class="btn-admin" style="background:#e2e8f0; color:#334155;">&larr; Về Vận Đơn</a>
        <a href="<?php echo base_url('admin/van-chuyen/phuong-thuc/them'); ?>" class="btn-admin btn-admin-primary">
            + Thêm Phương Thức Mới
        </a>
    </div>
</div>

<div class="card-box">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Tên phương thức</th>
                    <th>Mã định danh</th>
                    <th>Phí cước</th>
                    <th>Thời gian dự kiến</th>
                    <th>Mô tả</th>
                    <th>Trạng thái</th>
                    <th style="text-align: right;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($methods)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 30px;">Chưa có phương thức vận chuyển nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($methods as $m): ?>
                        <tr>
                            <td><?php echo $m['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($m['ten_pt']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($m['ma_pt']); ?></code></td>
                            <td>
                                <strong style="color: #e67e22;"><?php echo $m['phi_van_chuyen'] == 0 ? 'Miễn phí' : format_currency($m['phi_van_chuyen']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($m['thoi_gian_du_kien']); ?></td>
                            <td><small style="color: #64748b;"><?php echo htmlspecialchars($m['mo_ta'] ?? '-'); ?></small></td>
                            <td>
                                <span class="badge <?php echo ($m['trang_thai'] == 1) ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ($m['trang_thai'] == 1) ? 'Đang bật' : 'Tắt'; ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?php echo base_url('admin/van-chuyen/phuong-thuc/sua/' . $m['id']); ?>" class="btn-admin btn-admin-primary btn-admin-sm">Sửa</a>
                                <button type="button" onclick="confirmDelete('<?php echo base_url('admin/van-chuyen/phuong-thuc/xoa/' . $m['id']); ?>')" class="btn-admin btn-admin-danger btn-admin-sm">Xóa</button>
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
