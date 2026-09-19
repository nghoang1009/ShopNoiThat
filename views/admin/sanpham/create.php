<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Thêm Sản Phẩm Nội Thất Mới</h1>
        <p style="color: #64748b; font-size: 14px;">Thêm mới mẫu bàn, ghế, tủ, giường, sofa vào kho hàng</p>
    </div>
    <a href="<?php echo base_url('admin/san-pham'); ?>" style="color: #935832; font-weight:600;">&larr; Quay lại danh sách</a>
</div>

<div class="card-box" style="max-width: 900px;">
    <form action="<?php echo base_url('admin/san-pham/them'); ?>" method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group" style="grid-column: 1 / -1;">
                <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Tên sản phẩm <span style="color:red;">*</span></label>
                <input type="text" name="ten_san_pham" required placeholder="Ví dụ: Sofa Băng Da Ý Cao Cấp Milano" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
            </div>

            <div class="form-group">
                <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Danh mục <span style="color:red;">*</span></label>
                <select name="danh_muc_id" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['ten_danh_muc']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Nhà cung cấp</label>
                <select name="nha_cung_cap_id" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                    <option value="">-- Chọn nhà cung cấp (tuỳ chọn) --</option>
                    <?php foreach ($suppliers as $sup): ?>
                        <option value="<?php echo $sup['id']; ?>"><?php echo htmlspecialchars($sup['ten_nha_cung_cap']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Giá niêm yết (VNĐ) <span style="color:red;">*</span></label>
                <input type="number" name="gia" required placeholder="18500000" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
            </div>

            <div class="form-group">
                <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Giá khuyến mãi (VNĐ - nếu có)</label>
                <input type="number" name="gia_khuyen_mai" placeholder="16200000" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
            </div>

            <div class="form-group">
                <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Số lượng tồn kho <span style="color:red;">*</span></label>
                <input type="number" name="so_luong_ton" required value="10" min="0" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
            </div>

            <div class="form-group">
                <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Chất liệu</label>
                <input type="text" name="chat_lieu" placeholder="Gỗ sồi, Da bò Ý nhập khẩu..." style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
            </div>

            <div class="form-group">
                <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Kích thước</label>
                <input type="text" name="kich_thuoc" placeholder="Dài 220cm x Rộng 90cm x Cao 85cm" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
            </div>

            <div class="form-group">
                <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Màu sắc</label>
                <input type="text" name="mau_sac" placeholder="Nâu da bò, Xám lông chuột, Trắng..." style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;">
            </div>

            <div class="form-group">
                <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Hình ảnh chính</label>
                <input type="file" name="hinh_anh" accept="image/*" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                <img class="preview-img" style="display:none;" alt="Preview">
            </div>

            <div class="form-group">
                <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Ảnh phụ bổ sung (chọn nhiều ảnh)</label>
                <input type="file" name="hinh_anh_phu[]" multiple accept="image/*" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Mô tả chi tiết sản phẩm</label>
                <textarea name="mo_ta" rows="6" placeholder="Thông tin chi tiết về sản phẩm, cấu tạo, tiện ích..." style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;"></textarea>
            </div>

            <div class="form-group" style="grid-column: 1 / -1; display:flex; gap: 30px;">
                <label style="display:flex; align-items:center; gap: 8px; font-size: 14px; cursor: pointer;">
                    <input type="checkbox" name="noi_bat" value="1" style="accent-color: #935832;">
                    <span>Đánh dấu là <strong>Sản phẩm nổi bật</strong></span>
                </label>

                <label style="display:flex; align-items:center; gap: 8px; font-size: 14px; cursor: pointer;">
                    <input type="checkbox" name="trang_thai" value="1" checked style="accent-color: #935832;">
                    <span>Kích hoạt kinh doanh</span>
                </label>
            </div>
        </div>

        <button type="submit" class="btn-admin btn-admin-primary" style="padding: 12px 30px; font-size: 15px; margin-top: 15px;">
            Lưu Sản Phẩm Mới
        </button>
    </form>
</div>

<?php
require_once __DIR__ . '/../../../includes/admin_footer.php';
?>
