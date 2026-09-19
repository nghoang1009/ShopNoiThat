<?php
$extraCss = ['assets/css/admin/tu-van.css'];
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Chi Tiết Hội Thoại Tư Vấn #<?php echo $session['id']; ?></h1>
        <p style="color: #64748b; font-size: 14px;">Bắt đầu lúc: <?php echo date('d/m/Y H:i:s', strtotime($session['tao_luc'])); ?></p>
    </div>
    <a href="<?php echo base_url('admin/tu-van'); ?>" class="btn-admin" style="background:#e2e8f0; color:#334155;">
        &larr; Quay Lại Danh Sách
    </a>
</div>

<div class="tuvan-chat-container">
    <!-- Cột thông tin khách hàng -->
    <div class="tuvan-info-card">
        <h3 style="font-size: 16px; margin-bottom: 15px; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
            👤 Thông Tin Phiên Chat
        </h3>

        <div style="font-size: 13.5px; display:flex; flex-direction:column; gap:12px;">
            <div>
                <span style="color:#64748b; display:block;">Đối tượng:</span>
                <?php if ($customer): ?>
                    <strong><?php echo htmlspecialchars($customer['ho_ten']); ?></strong>
                    <div style="color:#64748b; font-size:12px;"><?php echo htmlspecialchars($customer['email']); ?></div>
                    <div style="color:#64748b; font-size:12px;"><?php echo htmlspecialchars($customer['sdt']); ?></div>
                <?php else: ?>
                    <strong>Khách vãng lai (Chưa đăng nhập)</strong>
                    <code style="font-size:11px;"><?php echo htmlspecialchars($session['session_id']); ?></code>
                <?php endif; ?>
            </div>

            <div>
                <span style="color:#64748b; display:block;">Thời gian tạo:</span>
                <span><?php echo date('d/m/Y H:i:s', strtotime($session['tao_luc'])); ?></span>
            </div>

            <div>
                <span style="color:#64748b; display:block;">Cập nhật cuối:</span>
                <span><?php echo date('d/m/Y H:i:s', strtotime($session['cap_nhat_luc'])); ?></span>
            </div>

            <div>
                <span style="color:#64748b; display:block;">Tổng tin nhắn:</span>
                <strong style="color: #935832;"><?php echo count($messages); ?> tin nhắn</strong>
            </div>

            <hr style="border:none; border-top:1px solid #e2e8f0; margin:10px 0;">

            <button type="button" onclick="confirmDelete('<?php echo base_url('admin/tu-van/xoa/' . $session['id']); ?>')" class="btn-admin btn-admin-danger" style="width:100%; text-align:center;">
                🗑️ Xóa Phiên Này
            </button>
        </div>
    </div>

    <!-- Cột nội dung tin nhắn -->
    <div class="tuvan-chat-box">
        <div class="tuvan-chat-header">
            <div style="font-weight:700; color:#1e293b;">
                💬 Lịch Sử Trò Chuyện Khách Hàng & AI
            </div>
            <span style="font-size:12px; color:#64748b;"><?php echo count($messages); ?> tin nhắn ghi nhận</span>
        </div>

        <div class="tuvan-messages-list">
            <?php if (empty($messages)): ?>
                <div style="text-align:center; color:#94a3b8; padding:50px 0;">Phiên này chưa có tin nhắn nào.</div>
            <?php else: ?>
                <?php foreach ($messages as $m): ?>
                    <?php $isUser = ($m['nguoi_gui'] === 'khach'); ?>
                    <div class="admin-chat-row <?php echo $isUser ? 'khach' : 'ai'; ?>">
                        <div class="admin-bubble">
                            <div class="admin-bubble-header">
                                <span><?php echo $isUser ? '👤 Khách hàng' : '✨ AI Assistant'; ?></span>
                                <span><?php echo date('H:i:s d/m', strtotime($m['thoi_gian'])); ?></span>
                            </div>

                            <div>
                                <?php echo nl2br(htmlspecialchars($m['noi_dung'])); ?>
                            </div>

                            <?php if (!empty($m['san_pham_goi_y'])): ?>
                                <?php $sp = $m['san_pham_goi_y']; ?>
                                <div class="admin-suggested-box">
                                    <img src="<?php echo $sp['hinh_anh_url']; ?>" alt="<?php echo htmlspecialchars($sp['ten_san_pham']); ?>" class="admin-suggested-img">
                                    <div style="flex:1; min-width:0;">
                                        <div style="font-weight:700; font-size:13px;"><?php echo htmlspecialchars($sp['ten_san_pham']); ?></div>
                                        <div style="font-size:12px; color:#e67e22; font-weight:800;"><?php echo $sp['gia_dinh_dang']; ?></div>
                                    </div>
                                    <a href="<?php echo $sp['detail_url']; ?>" target="_blank" class="btn-admin btn-admin-primary btn-admin-sm">
                                        Xem SP
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../../includes/admin_footer.php';
?>
