<?php
/**
 * Form dùng chung cho thêm / sửa nhà cung cấp.
 * Biến: $formAction (string), $values (array), $errors (array), $submitLabel (string)
 */
$inputStyle = 'width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;';
$fields = [
    ['ten_nha_cung_cap', 'Tên nhà cung cấp', 'text', true, 'Nội Thất Gỗ Việt Hoàng'],
    ['sdt', 'Số điện thoại', 'tel', true, '0283888999'],
    ['email', 'Email', 'email', false, 'info@nhacungcap.vn'],
    ['dia_chi', 'Địa chỉ', 'text', false, 'KCN Biên Hòa, Đồng Nai'],
];
?>
<form action="<?php echo htmlspecialchars($formAction); ?>" method="POST" novalidate>
    <?php echo csrf_field(); ?>
    <?php foreach ($fields as [$name, $label, $type, $required, $placeholder]): ?>
        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display:block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">
                <?php echo $label; ?> <?php if ($required): ?><span style="color:red;">*</span><?php endif; ?>
            </label>
            <input type="<?php echo $type; ?>" name="<?php echo $name; ?>"
                   value="<?php echo htmlspecialchars((string)($values[$name] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                   placeholder="<?php echo htmlspecialchars($placeholder); ?>"
                   style="<?php echo $inputStyle . (isset($errors[$name]) ? ' border-color:#dc2626;' : ''); ?>">
            <?php if (isset($errors[$name])): ?>
                <div style="color:#dc2626; font-size:13px; margin-top:6px;"><?php echo htmlspecialchars($errors[$name]); ?></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px;">
        <?php echo htmlspecialchars($submitLabel); ?>
    </button>
</form>
