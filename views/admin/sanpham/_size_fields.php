<?php
/**
 * Ô nhập số đo cấu trúc (cm) cho công cụ sắp xếp phòng 2D/3D của khách.
 * Biến: $sizeValues (array: dai_cm, rong_cm, cao_cm, hinh_dang, do_cao_cm)
 */
$sv = $sizeValues ?? [];
$sizeInput = 'width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;';
$val = fn($k, $d = '') => htmlspecialchars((string)($sv[$k] ?? $d), ENT_QUOTES, 'UTF-8');
$shape = $sv['hinh_dang'] ?? 'hop';
?>
<div class="form-group" style="grid-column: 1 / -1; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:8px; padding:16px;">
    <label style="display:block; font-size: 14px; font-weight: 700; margin-bottom: 4px;">📐 Số đo cho sơ đồ phòng 2D/3D (cm)</label>
    <p style="color:#64748b; font-size:13px; margin-bottom:14px;">
        Dùng để khách kéo thả sản phẩm vào phòng và xem đúng kích thước. Để trống cả 3 ô số đo nếu chưa có — sản phẩm sẽ không xuất hiện trong công cụ.
    </p>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:12px;">
        <div>
            <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Dài (ngang)</label>
            <input type="number" name="dai_cm" min="5" max="1000" value="<?php echo $val('dai_cm'); ?>" placeholder="220" style="<?php echo $sizeInput; ?>">
        </div>
        <div>
            <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Rộng / Sâu</label>
            <input type="number" name="rong_cm" min="5" max="1000" value="<?php echo $val('rong_cm'); ?>" placeholder="90" style="<?php echo $sizeInput; ?>">
        </div>
        <div>
            <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Cao</label>
            <input type="number" name="cao_cm" min="5" max="1000" value="<?php echo $val('cao_cm'); ?>" placeholder="85" style="<?php echo $sizeInput; ?>">
        </div>
        <div>
            <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Hình dạng</label>
            <select name="hinh_dang" style="<?php echo $sizeInput; ?>">
                <option value="hop" <?php echo $shape === 'hop' ? 'selected' : ''; ?>>Hình hộp chữ nhật</option>
                <option value="tron" <?php echo $shape === 'tron' ? 'selected' : ''; ?>>Hình tròn</option>
                <option value="chu_l" <?php echo $shape === 'chu_l' ? 'selected' : ''; ?>>Chữ L (sofa góc)</option>
            </select>
        </div>
        <div>
            <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Độ cao đặt so với sàn</label>
            <input type="number" name="do_cao_cm" min="0" max="400" value="<?php echo $val('do_cao_cm', '0'); ?>" placeholder="0" style="<?php echo $sizeInput; ?>">
        </div>
    </div>
    <p style="color:#94a3b8; font-size:12px; margin-top:10px;">
        Độ cao đặt &gt; 0 cho đèn thả trần (đáy đèn cách sàn bao nhiêu) hoặc kệ treo tường. Hình tròn: dùng Dài = Rộng = đường kính.
    </p>
</div>
