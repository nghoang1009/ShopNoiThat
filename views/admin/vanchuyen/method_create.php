<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Thêm Phương Thức Vận Chuyển Mới</h1>
    </div>
    <a href="<?php echo base_url('admin/van-chuyen/phuong-thuc'); ?>" style="color: #935832; font-weight:600;">&larr; Quay lại danh sách</a>
</div>

<div class="card-box" style="max-width: 650px;">
    <form action="<?php echo base_url('admin/van-chuyen/phuong-thuc/them'); ?>" method="POST">
        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Tên phương thức <span style="color:red;">*</span></label>
            <input type="text" name="ten_pt" required placeholder="Ví dụ: Giao Hàng Siêu Tốc 2 Giờ" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Phí cước vận chuyển (VNĐ) <span style="color:red;">*</span></label>
            <input type="number" name="phi_van_chuyen" required placeholder="50000" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Thời gian giao dự kiến <span style="color:red;">*</span></label>
            <input type="text" name="thoi_gian_du_kien" required placeholder="Ví dụ: 1 - 2 ngày" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Mô tả chi tiết</label>
            <textarea name="mo_ta" rows="3" placeholder="Mô tả phạm vi áp dụng, quy cách bốc vác..." style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;"></textarea>
        </div>

        <div class="form-group" style="margin-bottom: 25px;">
            <label style="display:flex; align-items:center; gap: 8px; font-size: 14px; cursor: pointer;">
                <input type="checkbox" name="trang_thai" value="1" checked style="accent-color: #935832;">
                <span>Kích hoạt phương thức này</span>
            </label>
        </div>

        <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px;">
            Lưu Phương Thức
        </button>
    </form>
</div>

<?php
require_once __DIR__ . '/../../../includes/admin_footer.php';
?>
