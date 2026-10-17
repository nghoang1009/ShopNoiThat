<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Chỉnh Sửa Nhà Cung Cấp</h1>
        <p style="color: #64748b; font-size: 14px;">Cập nhật thông tin liên hệ của đối tác</p>
    </div>
    <a href="<?php echo base_url('admin/nha-cung-cap'); ?>" style="color: #935832; font-weight:600;">&larr; Quay lại danh sách</a>
</div>

<div class="card-box" style="max-width: 700px;">
    <?php
    $formAction = base_url('admin/nha-cung-cap/sua/' . (int)$supplier['id']);
    $values = $supplier;
    $submitLabel = 'Lưu Thay Đổi';
    require __DIR__ . '/_form.php';
    ?>
</div>

<?php
require_once __DIR__ . '/../../../includes/admin_footer.php';
?>
