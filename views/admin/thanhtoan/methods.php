<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Cấu Hình & Quản Lý Cổng Thanh Toán</h1>
        <p style="color: #64748b; font-size: 14px;">Bật hoặc tắt các phương thức thanh toán hiển thị cho khách hàng</p>
    </div>
    <a href="<?php echo base_url('admin/thanh-toan'); ?>" class="btn-admin" style="background:#e2e8f0; color:#334155;">
        &larr; Về Danh Sách Giao Dịch
    </a>
</div>

<div class="card-box">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Tên phương thức</th>
                    <th>Mã định danh</th>
                    <th>Mô tả</th>
                    <th>Trạng thái hoạt động</th>
                    <th style="text-align: right;">Bật / Tắt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($methods as $m): ?>
                    <tr>
                        <td><?php echo $m['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($m['ten_pt']); ?></strong>
                        </td>
                        <td><code><?php echo htmlspecialchars($m['ma_pt']); ?></code></td>
                        <td><small style="color: #64748b;"><?php echo htmlspecialchars($m['mo_ta'] ?? '-'); ?></small></td>
                        <td>
                            <span class="badge <?php echo ($m['trang_thai'] == 1) ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo ($m['trang_thai'] == 1) ? 'Đang hoạt động' : 'Tạm tắt'; ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <a href="<?php echo base_url('admin/thanh-toan/doi-trang-thai/' . $m['id']); ?>" class="btn-admin <?php echo ($m['trang_thai'] == 1) ? 'btn-admin-danger' : 'btn-admin-primary'; ?> btn-admin-sm">
                                <?php echo ($m['trang_thai'] == 1) ? 'Tắt cổng này' : 'Bật kích hoạt'; ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../../../includes/admin_footer.php';
?>
