<?php
$extraCss = ['assets/css/client/checkout.css'];
require_once __DIR__ . '/../../../includes/header.php';
$oldEmail = get_flash('old_email') ?? '';
?>

<div class="container" style="max-width: 480px; margin: 60px auto;">
    <div class="checkout-box">
        <h1 style="font-size: 24px; text-align: center; color: var(--secondary-color); margin-bottom: 25px;">
            Đăng Nhập Tài Khoản
        </h1>

        <form action="<?php echo base_url('auth/login'); ?>" method="POST">
            <div class="form-group">
                <label class="form-label" for="email">Địa chỉ Email</label>
                <input type="email" id="email" name="email" class="form-control" required
                       placeholder="example@gmail.com" value="<?php echo htmlspecialchars($oldEmail); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" class="form-control" required
                       placeholder="Nhập mật khẩu của bạn">
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; margin-top: 20px;">
                Đăng Nhập
            </button>

            <div style="text-align: center; margin-top: 20px; font-size: 14px; color: var(--text-muted);">
                Chưa có tài khoản? <a href="<?php echo base_url('auth/register'); ?>" style="color: var(--primary-color); font-weight: 600;">Đăng ký ngay</a>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>
