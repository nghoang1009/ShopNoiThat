<?php
$extraCss = ['assets/css/client/checkout.css'];
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="container" style="max-width: 480px; margin: 60px auto;">
    <div class="checkout-box">
        <h1 style="font-size: 24px; text-align: center; color: var(--secondary-color); margin-bottom: 10px;">
            Quên Mật Khẩu
        </h1>
        <p style="text-align: center; color: var(--text-muted); font-size: 14px; margin-bottom: 25px;">
            Nhập email đã đăng ký, chúng tôi sẽ gửi liên kết để bạn tạo mật khẩu mới.
        </p>

        <form action="<?php echo base_url('auth/quen-mat-khau'); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label class="form-label" for="email">Địa chỉ Email</label>
                <input type="email" id="email" name="email" class="form-control" required maxlength="100"
                       placeholder="example@gmail.com" autocomplete="email">
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; margin-top: 20px;">
                Gửi Liên Kết Đặt Lại
            </button>

            <div style="text-align: center; margin-top: 20px; font-size: 14px; color: var(--text-muted);">
                <a href="<?php echo base_url('auth/login'); ?>" style="color: var(--primary-color); font-weight: 600;">&larr; Quay lại đăng nhập</a>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>
