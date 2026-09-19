<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Chỉnh Sửa Danh Mục: <?php echo htmlspecialchars($category['ten_danh_muc']); ?></h1>
    </div>
    <a href="<?php echo base_url('admin/danh-muc'); ?>" style="color: #935832; font-weight:600;">&larr; Quay lại danh sách</a>
</div>

<div class="card-box" style="max-width: 700px;">
    <form action="<?php echo base_url('admin/danh-muc/sua/' . $category['id']); ?>" method="POST" enctype="multipart/form-data">
        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Tên danh mục <span style="color:red;">*</span></label>
            <input type="text" name="ten_danh_muc" required value="<?php echo htmlspecialchars($category['ten_danh_muc']); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Đường dẫn Slug</label>
            <input type="text" name="slug" value="<?php echo htmlspecialchars($category['slug']); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Hình ảnh đại diện</label>
            <input type="file" name="hinh_anh" accept="image/*" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
            <?php if (!empty($category['hinh_anh'])): ?>
                <img src="<?php echo asset_url($category['hinh_anh']); ?>" class="preview-img" alt="Current Image">
            <?php else: ?>
                <img class="preview-img" style="display:none;" alt="Preview">
            <?php endif; ?>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Mô tả ngắn</label>
            <textarea name="mo_ta" rows="3" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;"><?php echo htmlspecialchars($category['mo_ta'] ?? ''); ?></textarea>
        </div>

        <div class="form-group" style="margin-bottom: 25px;">
            <label style="display:flex; align-items:center; gap: 8px; font-size: 14px; cursor: pointer;">
                <input type="checkbox" name="trang_thai" value="1" <?php echo ($category['trang_thai'] == 1) ? 'checked' : ''; ?> style="accent-color: #935832;">
                <span>Kích hoạt hiển thị</span>
            </label>
        </div>

        <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px;">
            Cập Nhật Danh Mục
        </button>
    </form>
</div>

<?php
require_once __DIR__ . '/../../../includes/admin_footer.php';
?>
