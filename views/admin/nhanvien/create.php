<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Thêm Nhân Viên Mới</h1>
        <p style="color: #64748b; font-size: 14px;">Tạo tài khoản quản trị viên hoặc nhân viên bán hàng</p>
    </div>
    <a href="<?php echo base_url('admin/nhan-vien'); ?>" style="color: #935832; font-weight:600;">&larr; Quay lại danh sách</a>
</div>

<div class="card-box" style="max-width: 700px;">
    <form action="<?php echo base_url('admin/nhan-vien/them'); ?>" method="POST">
        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Họ và tên <span style="color:red;">*</span></label>
            <input type="text" name="ho_ten" required placeholder="Nguyễn Văn B" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Tên tài khoản đăng nhập <span style="color:red;">*</span></label>
            <input type="text" name="tai_khoan" required placeholder="nhanvien_sales" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Email <span style="color:red;">*</span></label>
            <input type="email" name="email" required placeholder="nhanvien@shopnoithat.vn" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Số điện thoại</label>
            <input type="tel" name="sdt" placeholder="0901234567" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Mật khẩu (tối thiểu 6 ký tự) <span style="color:red;">*</span></label>
            <input type="password" name="mat_khau" required placeholder="••••••••" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 25px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Vai trò phân quyền</label>
            <select name="vai_tro" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                <option value="nhan_vien">Nhân Viên Bán Hàng</option>
                <option value="admin">Quản Trị Viên (Toàn quyền)</option>
            </select>
        </div>

        <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px;">
            Tạo Tài Khoản
        </button>
    </form>
</div>

<?php
require_once __DIR__ . '/../../../includes/admin_footer.php';
?>
