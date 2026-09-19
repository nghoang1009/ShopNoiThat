    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4>🏛️ Shop Nội Thất Sang Trọng</h4>
                    <p>Chuyên cung cấp các sản phẩm nội thất gia đình, văn phòng cao cấp: Sofa da Ý, bàn ăn mặt đá Ceramic, giường ngủ gỗ sồi, đèn chùm pha lê...</p>
                    <p>📍 <strong>Showroom:</strong> 123 Nguyễn Huệ, P. Bến Nghé, Quận 1, TP.HCM</p>
                    <p>📞 <strong>Hotline:</strong> 1900 6868 - 0901 234 567</p>
                    <p>✉️ <strong>Email:</strong> contact@shopnoithat.vn</p>
                </div>

                <div class="footer-col">
                    <h4>Danh Mục Nổi Bật</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo base_url('danh-muc/sofa-ghe-thu-gian'); ?>">Sofa & Ghế Thư Giãn</a></li>
                        <li><a href="<?php echo base_url('danh-muc/ban-an-ban-tra'); ?>">Bàn Ăn & Bàn Trà</a></li>
                        <li><a href="<?php echo base_url('danh-muc/giuong-ngu'); ?>">Giường Ngủ Hiện Đại</a></li>
                        <li><a href="<?php echo base_url('danh-muc/tu-ke-trang-tri'); ?>">Tủ Quần Áo Cánh Kính</a></li>
                        <li><a href="<?php echo base_url('danh-muc/den-trang-tri'); ?>">Đèn Trang Trí Cao Cấp</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Chính Sách & Hỗ Trợ</h4>
                    <ul class="footer-links">
                        <li><a href="#">Chính sách bảo hành 5 năm</a></li>
                        <li><a href="#">Vận chuyển & Lắp đặt miễn phí</a></li>
                        <li><a href="#">Chính sách đổi trả 30 ngày</a></li>
                        <li><a href="#">Hướng dẫn mua hàng & Trả góp</a></li>
                        <li><a href="#">Bảo mật thông tin khách hàng</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Đăng Ký Nhận Khuyến Mãi</h4>
                    <p>Nhận ngay voucher giảm giá <strong>500.000đ</strong> cho đơn hàng đầu tiên.</p>
                    <div style="display:flex; gap:5px; margin-top:10px;">
                        <input type="email" placeholder="Email của bạn..." style="padding:10px; border-radius:4px; border:none; outline:none; flex:1; font-size:13px;">
                        <button class="btn btn-primary btn-sm" style="padding:10px 15px;">Gửi</button>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Shop Nội Thất - Tất cả các quyền được bảo lưu. Phát triển bởi PHP MVC & API Engine.</p>
            </div>
        </div>
    </footer>

    <!-- AI Chatbot Assistant Widget -->
    <?php require_once __DIR__ . '/chat_widget.php'; ?>

    <!-- JS chung -->
    <script src="<?php echo asset_url('assets/js/client/main.js'); ?>"></script>
    <script src="<?php echo asset_url('assets/js/client/chat-widget.js'); ?>"></script>
    <?php if (isset($extraJs)): ?>
        <?php foreach ($extraJs as $js): ?>
            <script src="<?php echo asset_url($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>
