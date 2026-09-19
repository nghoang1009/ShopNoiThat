<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Quản Lý Lịch Sử Tư Vấn AI</h1>
        <p style="color: #64748b; font-size: 14px;">Theo dõi các phiên hội thoại của khách hàng với Trợ lý AI để nắm bắt nhu cầu và nâng cao chất lượng</p>
    </div>
</div>

<div class="card-box">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>Khách hàng / Session</th>
                    <th>Tiêu đề phiên</th>
                    <th>Tin nhắn cuối</th>
                    <th>Số tin nhắn</th>
                    <th>Cập nhật lúc</th>
                    <th style="text-align: right;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sessions)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 40px;">Chưa có phiên tư vấn nào được ghi nhận.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sessions as $s): ?>
                        <tr>
                            <td><strong>#<?php echo $s['id']; ?></strong></td>
                            <td>
                                <?php if (!empty($s['ten_khach_hang'])): ?>
                                    <strong><?php echo htmlspecialchars($s['ten_khach_hang']); ?></strong>
                                    <small style="display:block; color:#64748b;"><?php echo htmlspecialchars($s['email_khach_hang'] ?? ''); ?></small>
                                <?php else: ?>
                                    <span style="color:#64748b;">Khách vãng lai</span>
                                    <code style="display:block; font-size:11px;"><?php echo htmlspecialchars(substr($s['session_id'] ?? 'guest', 0, 12)); ?>...</code>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($s['tieu_de'] ?? 'Hội thoại tư vấn'); ?></strong>
                            </td>
                            <td>
                                <div style="max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 13px; color: #475569;">
                                    <?php echo htmlspecialchars($s['tin_nhan_cuoi'] ?? '-'); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo $s['tong_tin_nhan']; ?> tin</span>
                            </td>
                            <td>
                                <?php echo date('d/m/Y H:i', strtotime($s['cap_nhat_luc'])); ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?php echo base_url('admin/tu-van/chi-tiet/' . $s['id']); ?>" class="btn-admin btn-admin-primary btn-admin-sm">
                                    Xem Hội Thoại
                                </a>
                                <button type="button" onclick="confirmDelete('<?php echo base_url('admin/tu-van/xoa/' . $s['id']); ?>', 'Bạn có chắc chắn muốn xóa phiên tư vấn này không?')" class="btn-admin btn-admin-danger btn-admin-sm">
                                    Xóa
                                </button>
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
