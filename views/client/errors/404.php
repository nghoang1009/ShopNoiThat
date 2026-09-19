<?php
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="container" style="text-align: center; padding: 100px 20px;">
    <h1 style="font-size: 80px; color: var(--primary-color); margin-bottom: 10px;">404</h1>
    <h2 style="font-size: 24px; color: var(--secondary-color); margin-bottom: 15px;">
        <?php echo $message ?? 'Không tìm thấy trang yêu cầu!'; ?>
    </h2>
    <p style="color: var(--text-muted); margin-bottom: 30px;">
        Trang bạn đang tìm kiếm có thể đã bị xóa, đổi tên hoặc tạm thời không khả dụng.
    </p>
    <a href="<?php echo base_url(); ?>" class="btn btn-primary">
        &larr; Quay Về Trang Chủ
    </a>
</div>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>
