<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Thêm Danh Mục Mới</h1>
        <p style="color: #64748b; font-size: 14px;">Tạo mới nhóm phân loại sản phẩm nội thất</p>
    </div>
    <a href="<?php echo base_url('admin/danh-muc'); ?>" style="color: #935832; font-weight:600;">&larr; Quay lại danh sách</a>
</div>

<div class="card-box" style="max-width: 700px;">
    <form action="<?php echo base_url('admin/danh-muc/them'); ?>" method="POST" enctype="multipart/form-data">
        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Tên danh mục <span style="color:red;">*</span></label>
            <input type="text" name="ten_danh_muc" required placeholder="Ví dụ: Sofa & Ghế Thư Giãn" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Đường dẫn Slug (tự động tạo nếu để trống)</label>
            <input type="text" name="slug" placeholder="sofa-ghe-thu-gian" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Hình ảnh đại diện</label>
            <input type="file" name="hinh_anh" accept="image/*" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
            <img class="preview-img" style="display:none;" alt="Preview">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Mô tả ngắn</label>
            <textarea name="mo_ta" rows="3" placeholder="Mô tả danh mục..." style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;"></textarea>
        </div>

        <div class="form-group" style="margin-bottom: 25px;">
            <label style="display:flex; align-items:center; gap: 8px; font-size: 14px; cursor: pointer;">
                <input type="checkbox" name="trang_thai" value="1" checked style="accent-color: #935832;">
                <span>Kích hoạt hiển thị ngoài trang chủ</span>
            </label>
        </div>

        <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px;">
            Lưu Danh Mục
        </button>
    </form>
</div>

<?php
require_once __DIR__ . '/../../../includes/admin_footer.php';
?>
