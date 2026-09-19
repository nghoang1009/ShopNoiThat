<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Quản Trị - Shop Nội Thất</title>
    <link rel="stylesheet" href="<?php echo asset_url('assets/css/admin/admin.css'); ?>">
</head>
<body class="admin-login-body">
    <div class="admin-login-box">
        <div class="admin-login-title">
            <h1 style="font-size: 24px; color: #1e293b; margin-bottom: 8px;">🏛️ Đăng Nhập Quản Trị</h1>
            <p style="color: #64748b; font-size: 14px;">Hệ thống Quản lý Cửa hàng Nội thất</p>
        </div>

        <?php if (has_flash('admin_login_error')): $err = get_flash('admin_login_error'); ?>
            <div class="badge badge-danger" style="display:block; padding:10px 15px; margin-bottom: 20px; font-size: 13px; text-align: center;">
                <?php echo $err['message']; ?>
            </div>
        <?php endif; ?>

        <?php if (has_flash('admin_auth_error')): $err = get_flash('admin_auth_error'); ?>
            <div class="badge badge-warning" style="display:block; padding:10px 15px; margin-bottom: 20px; font-size: 13px; text-align: center;">
                <?php echo $err['message']; ?>
            </div>
        <?php endif; ?>

        <?php if (has_flash('admin_msg')): $msg = get_flash('admin_msg'); ?>
            <div class="badge badge-info" style="display:block; padding:10px 15px; margin-bottom: 20px; font-size: 13px; text-align: center;">
                <?php echo $msg['message']; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo base_url('admin/login'); ?>" method="POST">
            <div style="margin-bottom: 20px;">
                <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #334155;">Tài khoản / Email</label>
                <input type="text" name="username" required placeholder="admin hoặc nhanvien" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #334155;">Mật khẩu</label>
                <input type="password" name="password" required placeholder="••••••••" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
            </div>

            <button type="submit" class="btn-admin btn-admin-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 15px;">
                Đăng Nhập Quản Trị
            </button>
        </form>

        <div style="text-align: center; margin-top: 25px;">
            <a href="<?php echo base_url(); ?>" style="font-size: 13px; color: #935832; font-weight: 600;">&larr; Về trang chủ Shop</a>
        </div>
    </div>
</body>
</html>
