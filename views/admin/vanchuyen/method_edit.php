<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Chỉnh Sửa Phương Thức Vận Chuyển: <?php echo htmlspecialchars($method['ten_pt']); ?></h1>
    </div>
    <a href="<?php echo base_url('admin/van-chuyen/phuong-thuc'); ?>" style="color: #935832; font-weight:600;">&larr; Quay lại danh sách</a>
</div>

<div class="card-box" style="max-width: 650px;">
    <form action="<?php echo base_url('admin/van-chuyen/phuong-thuc/sua/' . $method['id']); ?>" method="POST">
        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Tên phương thức <span style="color:red;">*</span></label>
            <input type="text" name="ten_pt" required value="<?php echo htmlspecialchars($method['ten_pt']); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Mã định danh</label>
            <input type="text" value="<?php echo htmlspecialchars($method['ma_pt']); ?>" disabled style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: #f1f5f9;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Phí cước vận chuyển (VNĐ) <span style="color:red;">*</span></label>
            <input type="number" name="phi_van_chuyen" required value="<?php echo (float)$method['phi_van_chuyen']; ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Thời gian giao dự kiến <span style="color:red;">*</span></label>
            <input type="text" name="thoi_gian_du_kien" required value="<?php echo htmlspecialchars($method['thoi_gian_du_kien']); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Mô tả chi tiết</label>
            <textarea name="mo_ta" rows="3" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;"><?php echo htmlspecialchars($method['mo_ta'] ?? ''); ?></textarea>
        </div>

        <div class="form-group" style="margin-bottom: 25px;">
            <label style="display:flex; align-items:center; gap: 8px; font-size: 14px; cursor: pointer;">
                <input type="checkbox" name="trang_thai" value="1" <?php echo ($method['trang_thai'] == 1) ? 'checked' : ''; ?> style="accent-color: #935832;">
                <span>Kích hoạt phương thức này</span>
            </label>
        </div>

        <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px;">
            Cập Nhật Phương Thức
        </button>
    </form>
</div>

<?php
require_once __DIR__ . '/../../../includes/admin_footer.php';
?>
