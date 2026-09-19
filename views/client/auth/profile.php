<?php
$extraCss = ['assets/css/client/checkout.css'];
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="container" style="max-width: 600px; margin: 50px auto;">
    <div class="checkout-box">
        <h1 style="font-size: 24px; text-align: center; color: var(--secondary-color); margin-bottom: 25px;">
            Thông Tin Tài Khoản Cá Nhân
        </h1>

        <form action="<?php echo base_url('auth/profile'); ?>" method="POST">
            <div class="form-group">
                <label class="form-label" for="email">Địa chỉ Email (không thể đổi)</label>
                <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background:#f1f5f9;">
            </div>

            <div class="form-group">
                <label class="form-label" for="ho_ten">Họ và tên <span style="color:red;">*</span></label>
                <input type="text" id="ho_ten" name="ho_ten" class="form-control" required
                       value="<?php echo htmlspecialchars($user['ho_ten']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="sdt">Số điện thoại <span style="color:red;">*</span></label>
                <input type="tel" id="sdt" name="sdt" class="form-control" required
                       value="<?php echo htmlspecialchars($user['sdt']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="dia_chi">Địa chỉ giao hàng</label>
                <input type="text" id="dia_chi" name="dia_chi" class="form-control"
                       value="<?php echo htmlspecialchars($user['dia_chi'] ?? ''); ?>">
            </div>

            <div class="form-group" style="border-top: 1px solid var(--border-color); padding-top: 15px; margin-top: 20px;">
                <label class="form-label" for="mat_khau_moi">Mật khẩu mới (bỏ trống nếu không muốn đổi)</label>
                <input type="password" id="mat_khau_moi" name="mat_khau_moi" class="form-control"
                       placeholder="Nhập mật khẩu mới tối thiểu 6 ký tự">
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; margin-top: 25px;">
                Cập Nhật Thông Tin
            </button>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>
