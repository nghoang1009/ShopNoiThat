<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Chỉnh Sửa Nhân Viên: <?php echo htmlspecialchars($staff['ho_ten']); ?></h1>
    </div>
    <a href="<?php echo base_url('admin/nhan-vien'); ?>" style="color: #935832; font-weight:600;">&larr; Quay lại danh sách</a>
</div>

<div class="card-box" style="max-width: 700px;">
    <form action="<?php echo base_url('admin/nhan-vien/sua/' . $staff['id']); ?>" method="POST">
        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Tài khoản (không thể đổi)</label>
            <input type="text" value="<?php echo htmlspecialchars($staff['tai_khoan']); ?>" disabled style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: #f1f5f9;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Họ và tên <span style="color:red;">*</span></label>
            <input type="text" name="ho_ten" required value="<?php echo htmlspecialchars($staff['ho_ten']); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Email <span style="color:red;">*</span></label>
            <input type="email" name="email" required value="<?php echo htmlspecialchars($staff['email']); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Số điện thoại</label>
            <input type="tel" name="sdt" value="<?php echo htmlspecialchars($staff['sdt'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Mật khẩu mới (bỏ trống nếu không muốn đổi)</label>
            <input type="password" name="mat_khau" placeholder="Nhập mật khẩu mới..." style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Vai trò</label>
            <select name="vai_tro" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                <option value="nhan_vien" <?php echo ($staff['vai_tro'] === 'nhan_vien') ? 'selected' : ''; ?>>Nhân Viên</option>
                <option value="admin" <?php echo ($staff['vai_tro'] === 'admin') ? 'selected' : ''; ?>>Quản Trị Viên</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 25px;">
            <label style="display:flex; align-items:center; gap: 8px; font-size: 14px; cursor: pointer;">
                <input type="checkbox" name="trang_thai" value="1" <?php echo ($staff['trang_thai'] == 1) ? 'checked' : ''; ?> style="accent-color: #935832;">
                <span>Kích hoạt hoạt động</span>
            </label>
        </div>

        <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px;">
            Cập Nhật Tài Khoản
        </button>
    </form>
</div>

<?php
require_once __DIR__ . '/../../../includes/admin_footer.php';
?>
