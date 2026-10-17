<?php
$extraCss = ['assets/css/client/checkout.css'];
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="container" style="max-width: 480px; margin: 60px auto;">
    <div class="checkout-box">
        <h1 style="font-size: 24px; text-align: center; color: var(--secondary-color); margin-bottom: 10px;">
            Đặt Lại Mật Khẩu
        </h1>
        <p style="text-align: center; color: var(--text-muted); font-size: 14px; margin-bottom: 25px;">
            Tạo mật khẩu mới cho tài khoản <strong><?php echo htmlspecialchars($email); ?></strong>
        </p>

        <form action="<?php echo base_url('auth/dat-lai-mat-khau/' . $token); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label class="form-label" for="mat_khau">Mật khẩu mới</label>
                <input type="password" id="mat_khau" name="mat_khau" class="form-control" required minlength="6" maxlength="72"
                       placeholder="Tối thiểu 6 ký tự" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label class="form-label" for="xac_nhan_mat_khau">Nhập lại mật khẩu mới</label>
                <input type="password" id="xac_nhan_mat_khau" name="xac_nhan_mat_khau" class="form-control" required minlength="6" maxlength="72"
                       placeholder="Nhập lại mật khẩu" autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; margin-top: 20px;">
                Lưu Mật Khẩu Mới
            </button>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>
