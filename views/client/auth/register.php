<?php
$extraCss = ['assets/css/client/checkout.css'];
require_once __DIR__ . '/../../../includes/header.php';

$oldRegisterFlash = get_flash('old_register');
$oldRegister = is_array($oldRegisterFlash) && isset($oldRegisterFlash['message']) ? $oldRegisterFlash['message'] : (is_array($oldRegisterFlash) ? $oldRegisterFlash : []);

$registerErrorsFlash = get_flash('register_errors');
$registerErrors = is_array($registerErrorsFlash) && isset($registerErrorsFlash['message']) && is_array($registerErrorsFlash['message'])
    ? $registerErrorsFlash['message']
    : (is_array($registerErrorsFlash) ? $registerErrorsFlash : []);
?>

<div class="container" style="max-width: 520px; margin: 50px auto;">
    <div class="checkout-box">
        <h1 style="font-size: 24px; text-align: center; color: var(--secondary-color); margin-bottom: 25px;">
            Đăng Ký Tài Khoản Khách Hàng
        </h1>

        <?php if (!empty($registerErrors)): ?>
            <div class="alert alert-danger">
                <ul style="margin-left: 20px;">
                    <?php foreach ($registerErrors as $err): ?>
                        <li><?php echo $err; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?php echo base_url('auth/register'); ?>" method="POST">
            <div class="form-group">
                <label class="form-label" for="ho_ten">Họ và tên <span style="color:red;">*</span></label>
                <input type="text" id="ho_ten" name="ho_ten" class="form-control" required
                       placeholder="Nguyễn Văn A" value="<?php echo htmlspecialchars($oldRegister['ho_ten'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Địa chỉ Email <span style="color:red;">*</span></label>
                <input type="email" id="email" name="email" class="form-control" required
                       placeholder="example@gmail.com" value="<?php echo htmlspecialchars($oldRegister['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="sdt">Số điện thoại <span style="color:red;">*</span></label>
                <input type="tel" id="sdt" name="sdt" class="form-control" required
                       placeholder="0912345678" value="<?php echo htmlspecialchars($oldRegister['sdt'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="mat_khau">Mật khẩu (tối thiểu 6 ký tự) <span style="color:red;">*</span></label>
                <input type="password" id="mat_khau" name="mat_khau" class="form-control" required
                       placeholder="••••••••">
            </div>

            <div class="form-group">
                <label class="form-label" for="dia_chi">Địa chỉ mặc định</label>
                <input type="text" id="dia_chi" name="dia_chi" class="form-control"
                       placeholder="Số nhà, tên đường, quận/huyện..." value="<?php echo htmlspecialchars($oldRegister['dia_chi'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; margin-top: 25px;">
                Đăng Ký Tài Khoản
            </button>

            <div style="text-align: center; margin-top: 20px; font-size: 14px; color: var(--text-muted);">
                Đã có tài khoản? <a href="<?php echo base_url('auth/login'); ?>" style="color: var(--primary-color); font-weight: 600;">Đăng nhập ngay</a>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>
