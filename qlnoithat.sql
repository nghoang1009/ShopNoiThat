-- Cơ sở dữ liệu Quản lý Shop Nội Thất: qlnoithat
CREATE DATABASE IF NOT EXISTS `qlnoithat` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `qlnoithat`;

-- Xóa bảng cũ theo thứ tự ràng buộc
DROP TABLE IF EXISTS `thanh_toan`;
DROP TABLE IF EXISTS `van_chuyen`;
DROP TABLE IF EXISTS `chi_tiet_don_hang`;
DROP TABLE IF EXISTS `don_hang`;
DROP TABLE IF EXISTS `gio_hang`;
DROP TABLE IF EXISTS `san_pham`;
DROP TABLE IF EXISTS `danh_muc`;
DROP TABLE IF EXISTS `khach_hang`;
DROP TABLE IF EXISTS `nhan_vien`;
DROP TABLE IF EXISTS `nha_cung_cap`;
DROP TABLE IF EXISTS `phuong_thuc_van_chuyen`;
DROP TABLE IF EXISTS `phuong_thuc_thanh_toan`;

-- 1. Bảng danh_muc
CREATE TABLE `danh_muc` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ten_danh_muc` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `mo_ta` TEXT NULL,
  `hinh_anh` VARCHAR(255) NULL,
  `trang_thai` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bảng nha_cung_cap
CREATE TABLE `nha_cung_cap` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ten_nha_cung_cap` VARCHAR(255) NOT NULL,
  `sdt` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NULL,
  `dia_chi` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bảng san_pham
CREATE TABLE `san_pham` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ten_san_pham` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `danh_muc_id` INT NOT NULL,
  `nha_cung_cap_id` INT NULL,
  `gia` DECIMAL(12,2) NOT NULL,
  `gia_khuyen_mai` DECIMAL(12,2) DEFAULT 0,
  `chat_lieu` VARCHAR(255) NULL,
  `kich_thuoc` VARCHAR(255) NULL,
  `mau_sac` VARCHAR(100) NULL,
  `mo_ta` LONGTEXT NULL,
  `hinh_anh` VARCHAR(255) NULL,
  `hinh_anh_phu` TEXT NULL COMMENT 'JSON array danh sách URL hình ảnh',
  `so_luong_ton` INT DEFAULT 0,
  `luot_xem` INT DEFAULT 0,
  `trang_thai` TINYINT(1) DEFAULT 1 COMMENT '1: Kinh doanh, 0: Ngừng',
  `noi_bat` TINYINT(1) DEFAULT 0 COMMENT '1: Nổi bật, 0: Thường',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`danh_muc_id`) REFERENCES `danh_muc`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`nha_cung_cap_id`) REFERENCES `nha_cung_cap`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Bảng khach_hang
CREATE TABLE `khach_hang` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ho_ten` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `sdt` VARCHAR(20) NOT NULL,
  `mat_khau` VARCHAR(255) NOT NULL,
  `dia_chi` TEXT NULL,
  `trang_thai` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bảng nhan_vien
CREATE TABLE `nhan_vien` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ho_ten` VARCHAR(100) NOT NULL,
  `tai_khoan` VARCHAR(50) NOT NULL UNIQUE,
  `mat_khau` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `sdt` VARCHAR(20) NULL,
  `vai_tro` ENUM('admin', 'nhan_vien') DEFAULT 'nhan_vien',
  `trang_thai` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Bảng phuong_thuc_van_chuyen
CREATE TABLE `phuong_thuc_van_chuyen` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ten_pt` VARCHAR(150) NOT NULL,
  `ma_pt` VARCHAR(50) NOT NULL UNIQUE,
  `phi_van_chuyen` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `thoi_gian_du_kien` VARCHAR(100) NOT NULL,
  `mo_ta` TEXT NULL,
  `trang_thai` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Bảng phuong_thuc_thanh_toan
CREATE TABLE `phuong_thuc_thanh_toan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ten_pt` VARCHAR(150) NOT NULL,
  `ma_pt` VARCHAR(50) NOT NULL UNIQUE,
  `mo_ta` TEXT NULL,
  `hinh_anh` VARCHAR(255) NULL,
  `huong_dan` TEXT NULL,
  `trang_thai` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Bảng don_hang
CREATE TABLE `don_hang` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ma_don_hang` VARCHAR(30) NOT NULL UNIQUE,
  `khach_hang_id` INT NULL,
  `phuong_thuc_van_chuyen_id` INT NULL,
  `phuong_thuc_thanh_toan_id` INT NULL,
  `ho_ten_nhan` VARCHAR(100) NOT NULL,
  `sdt_nhan` VARCHAR(20) NOT NULL,
  `dia_chi_giao` TEXT NOT NULL,
  `tinh_thanh` VARCHAR(100) NULL,
  `ghi_chu` TEXT NULL,
  `tien_hang` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `phi_van_chuyen` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `tong_tien` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `phuong_thuc_thanh_toan` VARCHAR(50) DEFAULT 'cod',
  `trang_thai_thanh_toan` ENUM('chua_thanh_toan', 'da_thanh_toan') DEFAULT 'chua_thanh_toan',
  `trang_thai` ENUM('cho_xu_ly', 'dang_giao', 'hoan_tat', 'da_huy') DEFAULT 'cho_xu_ly',
  `ngay_dat` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`khach_hang_id`) REFERENCES `khach_hang`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`phuong_thuc_van_chuyen_id`) REFERENCES `phuong_thuc_van_chuyen`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`phuong_thuc_thanh_toan_id`) REFERENCES `phuong_thuc_thanh_toan`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Bảng chi_tiet_don_hang
CREATE TABLE `chi_tiet_don_hang` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `don_hang_id` INT NOT NULL,
  `san_pham_id` INT NULL,
  `ten_san_pham` VARCHAR(255) NOT NULL,
  `hinh_anh` VARCHAR(255) NULL,
  `so_luong` INT NOT NULL DEFAULT 1,
  `don_gia` DECIMAL(12,2) NOT NULL,
  `thanh_tien` DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Bảng gio_hang
CREATE TABLE `gio_hang` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `khach_hang_id` INT NULL,
  `session_id` VARCHAR(100) NULL,
  `san_pham_id` INT NOT NULL,
  `so_luong` INT NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`khach_hang_id`) REFERENCES `khach_hang`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Bảng van_chuyen
CREATE TABLE `van_chuyen` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `don_hang_id` INT NOT NULL UNIQUE,
  `phuong_thuc_van_chuyen_id` INT NULL,
  `don_vi_van_chuyen` VARCHAR(100) DEFAULT 'Giao Hàng Nội Bộ Shop',
  `ma_van_don` VARCHAR(50) NULL UNIQUE,
  `trang_thai_giao_hang` ENUM('cho_lay_hang', 'dang_van_chuyen', 'dang_giao', 'da_giao', 'giao_that_bai', 'chuyen_hoan') DEFAULT 'cho_lay_hang',
  `phi_van_chuyen` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `ngay_giao_du_kien` DATE NULL,
  `ngay_giao_thuc_te` DATETIME NULL,
  `ghi_chu` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`phuong_thuc_van_chuyen_id`) REFERENCES `phuong_thuc_van_chuyen`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Bảng thanh_toan
CREATE TABLE `thanh_toan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `don_hang_id` INT NOT NULL,
  `phuong_thuc_thanh_toan_id` INT NULL,
  `so_tien` DECIMAL(12,2) NOT NULL,
  `trang_thai` ENUM('cho_thanh_toan', 'da_thanh_toan', 'that_bai', 'hoan_tien') DEFAULT 'cho_thanh_toan',
  `ma_giao_dich` VARCHAR(100) NULL,
  `ngay_thanh_toan` DATETIME NULL,
  `ghi_chu` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`phuong_thuc_thanh_toan_id`) REFERENCES `phuong_thuc_thanh_toan`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Bảng phien_tu_van (AI Chat Sessions)
CREATE TABLE `phien_tu_van` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `khach_hang_id` INT NULL,
  `session_id` VARCHAR(100) NULL,
  `tieu_de` VARCHAR(255) DEFAULT 'Hội thoại tư vấn nội thất',
  `tao_luc` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `cap_nhat_luc` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`khach_hang_id`) REFERENCES `khach_hang`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Bảng tin_nhan_tu_van (AI Chat Messages)
CREATE TABLE `tin_nhan_tu_van` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `phien_tu_van_id` INT NOT NULL,
  `nguoi_gui` ENUM('khach', 'ai') NOT NULL DEFAULT 'khach',
  `noi_dung` LONGTEXT NOT NULL,
  `san_pham_goi_y_id` INT NULL,
  `thoi_gian` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`phien_tu_van_id`) REFERENCES `phien_tu_van`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`san_pham_goi_y_id`) REFERENCES `san_pham`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DỮ LIỆU MẪU
-- =============================================

-- Nhân viên & Khách hàng (Mật khẩu mặc định: 123456)
INSERT INTO `nhan_vien` (`id`, `ho_ten`, `tai_khoan`, `mat_khau`, `email`, `sdt`, `vai_tro`, `trang_thai`) VALUES
(1, 'Quản Trị Viên', 'admin', '123456', 'admin@shopnoithat.vn', '0901234567', 'admin', 1),
(2, 'Nhân Viên Bán Hàng', 'nhanvien', '123456', 'sales@shopnoithat.vn', '0907654321', 'nhan_vien', 1);

INSERT INTO `khach_hang` (`id`, `ho_ten`, `email`, `sdt`, `mat_khau`, `dia_chi`, `trang_thai`) VALUES
(1, 'Nguyễn Văn An', 'nguyenvanan@gmail.com', '0912345678', '123456', 'Số 123 Đường Nguyễn Huệ, Quận 1, TP.HCM', 1),
(2, 'Trần Thị Mai', 'tranthimai@gmail.com', '0987654321', '123456', 'Số 45 Đường Cầu Giấy, Quận Cầu Giấy, Hà Nội', 1);

-- Nhà cung cấp
INSERT INTO `nha_cung_cap` (`id`, `ten_nha_cung_cap`, `sdt`, `email`, `dia_chi`) VALUES
(1, 'Nội Thất Gỗ Việt Hoàng', '0283888999', 'info@goviethoang.vn', 'KCN Biên Hòa, Đồng Nai'),
(2, 'Sofa & Decor Luxury', '0243999888', 'contact@sofaluxury.vn', 'KCN Thạch Thất, Hà Nội'),
(3, 'Đèn Trang Trí Lighting Art', '0283777666', 'sales@lightingart.com', 'Quận 7, TP.HCM');

-- Danh mục
INSERT INTO `danh_muc` (`id`, `ten_danh_muc`, `slug`, `mo_ta`, `hinh_anh`, `trang_thai`) VALUES
(1, 'Sofa & Ghế Thư Giãn', 'sofa-ghe-thu-gian', 'Các loại sofa phòng khách, sofa băng, sofa góc bọc da, bọc nỉ cao cấp và ghế thư giãn.', 'assets/images/categories/sofa.jpg', 1),
(2, 'Bàn Ăn & Bàn Trà', 'ban-an-ban-tra', 'Bàn ăn gỗ sồi tự nhiên, mặt đá ceramic chống trầy, bàn trà thông minh hiện đại.', 'assets/images/categories/ban.jpg', 1),
(3, 'Giường Ngủ', 'giuong-ngu', 'Giường ngủ gỗ tự nhiên, giường bọc nệm phong cách Bắc Âu Scandinavian sang trọng.', 'assets/images/categories/giuong.jpg', 1),
(4, 'Tủ & Kệ Trang Trí', 'tu-ke-trang-tri', 'Tủ quần áo cánh kính, kệ tivi, kệ sách âm tường đa năng tối ưu không gian.', 'assets/images/categories/tu-ke.jpg', 1),
(5, 'Ghế Văn Phòng & Ergonomic', 'ghe-van-phong', 'Ghế công thái học Ergonomic chống đau lưng, ghế làm việc bọc lưới thông thoáng.', 'assets/images/categories/ghe-vp.jpg', 1),
(6, 'Đèn Trang Trí', 'den-trang-tri', 'Đèn chùm pha lê, đèn thả bàn ăn, đèn cây đứng phòng khách phong cách hiện đại.', 'assets/images/categories/den.jpg', 1);

-- Phương thức vận chuyển
INSERT INTO `phuong_thuc_van_chuyen` (`id`, `ten_pt`, `ma_pt`, `phi_van_chuyen`, `thoi_gian_du_kien`, `mo_ta`, `trang_thai`) VALUES
(1, 'Giao Hàng Tiêu Chuẩn', 'tieu_chuan', 50000, '3 - 5 ngày', 'Vận chuyển tiêu chuẩn tận nhà cho đồ nội thất.', 1),
(2, 'Giao Hàng Nhanh Hỏa Tốc', 'hoa_toc', 150000, '24 - 48 giờ', 'Ưu tiên giao gấp trong ngày khu vực nội thành.', 1),
(3, 'Vận Chuyển & Lắp Đặt Trọn Gói', 'lap_dat_tron_goi', 200000, '2 - 3 ngày', 'Đội ngũ kỹ thuật giao hàng, bưng bê tận phòng và lắp ráp hoàn thiện.', 1);

-- Phương thức thanh toán
INSERT INTO `phuong_thuc_thanh_toan` (`id`, `ten_pt`, `ma_pt`, `mo_ta`, `hinh_anh`, `huong_dan`, `trang_thai`) VALUES
(1, 'Thanh toán khi nhận hàng (COD)', 'cod', 'Thanh toán tiền mặt cho nhân viên sau khi nhận và kiểm tra hàng.', 'assets/images/payments/cod.png', 'Vui lòng chuẩn bị đủ tiền mặt khi nhận hàng.', 1),
(2, 'Chuyển khoản Ngân Hàng (VietQR)', 'banking', 'Quét mã VietQR chuyển khoản nhanh 24/7 qua mọi ngân hàng.', 'assets/images/payments/vietqr.png', 'Quét mã QR hiển thị hoặc chuyển khoản theo đúng số tài khoản và cú pháp.', 1),
(3, 'Ví Điện Tử MoMo', 'momo', 'Thanh toán tiện lợi qua ứng dụng Ví MoMo bằng mã QR.', 'assets/images/payments/momo.png', 'Mở ứng dụng MoMo và quét mã QR để hoàn tất giao dịch.', 1),
(4, 'Cổng Thanh Toán VNPAY', 'vnpay', 'Thẻ ATM nội địa, Thẻ Visa/Mastercard hoặc VNPAY-QR.', 'assets/images/payments/vnpay.png', 'Thanh toán an toàn qua cổng VNPAY.', 1);

-- Sản phẩm
INSERT INTO `san_pham` (`id`, `ten_san_pham`, `slug`, `danh_muc_id`, `nha_cung_cap_id`, `gia`, `gia_khuyen_mai`, `chat_lieu`, `kich_thuoc`, `mau_sac`, `mo_ta`, `hinh_anh`, `hinh_anh_phu`, `so_luong_ton`, `luot_xem`, `trang_thai`, `noi_bat`) VALUES
(1, 'Sofa Băng Da Ý Cao Cấp Milano', 'sofa-bang-da-y-cao-cap-milano', 1, 2, 18500000, 16200000, 'Khung gỗ sồi, Da bò Ý nhập khẩu', 'Dài 220cm x Rộng 90cm x Cao 85cm', 'Nâu da bò', '<p>Sofa băng Milano thiết kế hiện đại mang phong cách Ý lịch lãm. Đệm mút D40 êm ái chống xẹp lún, bọc da bò thật nhập khẩu mềm mại, chống thấm nước tốt và độ bền cao.</p>', 'assets/images/products/sofa-milano.jpg', '["assets/images/products/sofa-milano-1.jpg","assets/images/products/sofa-milano-2.jpg"]', 15, 120, 1, 1),
(2, 'Sofa Góc Chữ L Bọc Vải Nỉ Nordic', 'sofa-goc-chu-l-boc-vai-ni-nordic', 1, 2, 12800000, 11500000, 'Khung gỗ thông tự nhiên, Vải nỉ Bỉ', 'Dài 260cm x Góc L 160cm x Cao 80cm', 'Xám lông chuột', '<p>Sofa góc chữ L Scandinavian Nordic mang lại cảm giác ấm cúng cho phòng khách gia đình. Vải nỉ cao cấp dệt sợi nano chống bám bụi và dễ vệ sinh.</p>', 'assets/images/products/sofa-nordic.jpg', '["assets/images/products/sofa-nordic-1.jpg"]', 20, 95, 1, 1),
(3, 'Ghế Thư Giãn Bập Bênh Poang Đệm Da', 'ghe-thu-gian-bap-benh-poang', 1, 1, 3500000, 2990000, 'Khung gỗ uốn ép cao cấp, Đệm simili', 'Rộng 68cm x Sâu 82cm x Cao 100cm', 'Vàng kem', '<p>Ghế thư giãn Poang bập bênh nâng đỡ cột sống hoàn hảo. Thích hợp đọc sách, xem phim, nghe nhạc tại phòng khách hoặc ban công.</p>', 'assets/images/products/ghe-poang.jpg', NULL, 30, 80, 1, 0),
(4, 'Bộ Bàn Ăn 6 Ghế Mặt Đá Ceramic Concorde', 'bo-ban-an-6-ghe-mat-da-concorde', 2, 1, 15500000, 13900000, 'Khung gỗ Ash (Tần bì), Mặt đá Ceramic', 'Dài 160cm x Rộng 80cm x Cao 75cm', 'Mặt đá trắng vân mây, chân đen', '<p>Bộ bàn ăn Concorde 6 ghế ăn Grace cao cấp. Mặt đá phiến Ceramic chịu nhiệt tới 1200 độ C, chống trầy xước và chống thấm ố tuyệt đối.</p>', 'assets/images/products/ban-an-concorde.jpg', '["assets/images/products/ban-an-concorde-1.jpg"]', 12, 210, 1, 1),
(5, 'Bàn Trà Tròn Đôi Khung Thép Mạ Vàng PVD', 'ban-tra-tron-doi-khung-thep-ma-vang', 2, 2, 4200000, 3600000, 'Inox 304 mạ PVD vàng gương, Mặt kính cường lực', 'Bàn lớn D70cm x H45cm, Bàn nhỏ D50cm x H40cm', 'Đen - Vàng Gold', '<p>Bàn trà tròn đôi lồng ghép thông minh giúp tiết kiệm diện tích. Thiết kế sang trọng điểm nhấn cho phòng khách căn hộ cao cấp.</p>', 'assets/images/products/ban-tra-tron.jpg', NULL, 25, 60, 1, 0),
(6, 'Giường Ngủ Gỗ Sồi Nga Hiện Đại Tokyo 1m8', 'giuong-ngu-go-soi-nga-tokyo-1m8', 3, 1, 9800000, 8500000, 'Gỗ sồi Nga tự nhiên đã qua xử lý sấy', '180cm x 200cm x Cao đầu giường 90cm', 'Màu gỗ sồi tự nhiên', '<p>Giường ngủ phong cách Nhật Bản tối giản, đầu giường vát góc tạo cảm giác thoải mái khi tựa lưng. Nan giường gỗ khối chịu tải trọng lớn.</p>', 'assets/images/products/giuong-tokyo.jpg', '["assets/images/products/giuong-tokyo-1.jpg"]', 18, 140, 1, 1),
(7, 'Giường Ngủ Bọc Nệm Nỉ Luxury Royal 1m8', 'giuong-ngu-boc-nem-ni-luxury-royal', 3, 2, 14200000, 12800000, 'Khung gỗ gõ tự nhiên, Bọc vải nỉ nhung cao cấp', '180cm x 200cm x Cao đầu giường 120cm', 'Xanh lam đậm', '<p>Giường ngủ bọc nệm phong cách hoàng gia hiện đại. Đầu giường đính hạt cúc rút múi thủ công tỉ mỉ, mang đến giấc ngủ êm ái sang trọng.</p>', 'assets/images/products/giuong-royal.jpg', NULL, 8, 75, 1, 1),
(8, 'Tủ Quần Áo Cánh Kính Cường Lực Thông Minh 4 Cánh', 'tu-quan-ao-canh-kinh-4-canh', 4, 1, 22000000, 19500000, 'Gỗ công nghiệp MDF lõi xanh chống ẩm An Cường, Cánh kính khung nhôm', 'Rộng 200cm x Cao 240cm x Sâu 60cm', 'Thùng vân gỗ óc chó, Kính xám khói', '<p>Tủ áo cánh kính tràn viền sang chảnh, tích hợp hệ thống ray trượt giảm chấn và đèn LED cảm ứng đổi màu khi mở cửa.</p>', 'assets/images/products/tu-ao-kinh.jpg', '["assets/images/products/tu-ao-kinh-1.jpg"]', 10, 190, 1, 1),
(9, 'Kệ Tivi Rút 2 Đầu Đa Năng Gỗ MDF Phủ Melamine', 'ke-tivi-rut-2-dau-da-nang', 4, 1, 3800000, 3200000, 'Gỗ MDF chống ẩm phủ Melamine chống trầy', 'Rút co giãn từ 160cm đến 220cm, Cao 45cm', 'Vàng vân gỗ phối trắng', '<p>Kệ tivi co giãn linh hoạt phù hợp mọi kích thước phòng khách. Gồm 3 ngăn kéo chứa đồ rộng rãi, ray bi 3 tầng êm ái.</p>', 'assets/images/products/ke-tivi-rut.jpg', NULL, 4, 50, 1, 0),
(10, 'Ghế Công Thái Học Ergonomic Sihoo M57', 'ghe-cong-thai-hoc-sihoo-m57', 5, 3, 4500000, 3990000, 'Khung hợp kim nhôm, Lưới thoáng khí cao cấp', 'Tùy chỉnh chiều cao 110-130cm', 'Xám bạc', '<p>Ghế văn phòng chuẩn công thái học hỗ trợ tựa thắt lưng 2D, tay vịn 3D nâng hạ xoay, tựa đầu tùy biến góc nghiêng chống mỏi cổ vai gáy.</p>', 'assets/images/products/ghe-sihoo-m57.jpg', '["assets/images/products/ghe-sihoo-1.jpg"]', 50, 320, 1, 1),
(11, 'Đèn Chùm Pha Lê Phòng Khách Nordic Sputnik', 'den-chum-pha-le-nordic-sputnik', 6, 3, 5200000, 4500000, 'Hợp kim sơn tĩnh điện mạ vàng, Pha lê K9', 'Đường kính 80cm x Chiều cao thả 60cm', 'Vàng đồng', '<p>Đèn chùm trang trí phòng khách Sputnik 12 bóng LED ánh sáng 3 chế độ (trắng, vàng, trung tính). Tiết kiệm điện năng và tạo ánh sáng lung linh.</p>', 'assets/images/products/den-chum-sputnik.jpg', NULL, 3, 110, 1, 0),
(12, 'Đèn Thả Bàn Ăn Ba Chao Hiện Đại Minimalist', 'den-tha-ban-an-ba-chao', 6, 3, 1800000, 1450000, 'Nhôm sơn mài tĩnh điện, Đui đèn gỗ tự nhiên', 'Dài thanh ngang 80cm, Dây thả 120cm tùy chỉnh', 'Đen - Trắng - Xám', '<p>Đèn thả trần 3 bóng decor bàn ăn ấm cúng phong cách tối giản Bắc Âu. Tặng kèm 3 bóng LED Edison ánh sáng vàng ấm chuyên dụng.</p>', 'assets/images/products/den-tha-ban-an.jpg', NULL, 35, 65, 1, 0);

-- Đơn hàng mẫu
INSERT INTO `don_hang` (`id`, `ma_don_hang`, `khach_hang_id`, `phuong_thuc_van_chuyen_id`, `phuong_thuc_thanh_toan_id`, `ho_ten_nhan`, `sdt_nhan`, `dia_chi_giao`, `tinh_thanh`, `ghi_chu`, `tien_hang`, `phi_van_chuyen`, `tong_tien`, `phuong_thuc_thanh_toan`, `trang_thai_thanh_toan`, `trang_thai`, `ngay_dat`) VALUES
(1, 'DH20260901001', 1, 3, 1, 'Nguyễn Văn An', '0912345678', 'Số 123 Đường Nguyễn Huệ, Quận 1, TP.HCM', 'Hồ Chí Minh', 'Giao hàng giờ hành chính', 16200000, 200000, 16400000, 'cod', 'chua_thanh_toan', 'dang_giao', '2026-09-01 10:30:00'),
(2, 'DH20260902002', 2, 1, 2, 'Trần Thị Mai', '0987654321', 'Số 45 Đường Cầu Giấy, Quận Cầu Giấy, Hà Nội', 'Hà Nội', 'Gọi trước khi giao 30p', 3990000, 50000, 4040000, 'banking', 'da_thanh_toan', 'hoan_tat', '2026-09-02 14:15:00'),
(3, 'DH20260910003', 1, 1, 3, 'Nguyễn Văn An', '0912345678', 'Số 123 Đường Nguyễn Huệ, Quận 1, TP.HCM', 'Hồ Chí Minh', '', 13900000, 0, 13900000, 'momo', 'da_thanh_toan', 'cho_xu_ly', '2026-09-10 09:00:00');

-- Chi tiết đơn hàng
INSERT INTO `chi_tiet_don_hang` (`id`, `don_hang_id`, `san_pham_id`, `ten_san_pham`, `hinh_anh`, `so_luong`, `don_gia`, `thanh_tien`) VALUES
(1, 1, 1, 'Sofa Băng Da Ý Cao Cấp Milano', 'assets/images/products/sofa-milano.jpg', 1, 16200000, 16200000),
(2, 2, 10, 'Ghế Công Thái Học Ergonomic Sihoo M57', 'assets/images/products/ghe-sihoo-m57.jpg', 1, 3990000, 3990000),
(3, 3, 4, 'Bộ Bàn Ăn 6 Ghế Mặt Đá Ceramic Concorde', 'assets/images/products/ban-an-concorde.jpg', 1, 13900000, 13900000);

-- Vận chuyển đơn hàng mẫu
INSERT INTO `van_chuyen` (`id`, `don_hang_id`, `phuong_thuc_van_chuyen_id`, `don_vi_van_chuyen`, `ma_van_don`, `trang_thai_giao_hang`, `phi_van_chuyen`, `ngay_giao_du_kien`, `ngay_giao_thuc_te`, `ghi_chu`) VALUES
(1, 1, 3, 'Đội Xe Tải Shop Nội Thất', 'VD20260901001', 'dang_giao', 200000, '2026-09-04', NULL, 'Đang trên đường giao đến Quận 1'),
(2, 2, 1, 'Giao Hàng Tiết Kiệm', 'GHTK88992201', 'da_giao', 50000, '2026-09-05', '2026-09-05 16:30:00', 'Đã ký nhận đầy đủ'),
(3, 3, 1, 'Giao Hàng Nhanh', 'GHN77334411', 'cho_lay_hang', 0, '2026-09-13', NULL, 'Chờ đóng gói kiện gỗ');

-- Thanh toán đơn hàng mẫu
INSERT INTO `thanh_toan` (`id`, `don_hang_id`, `phuong_thuc_thanh_toan_id`, `so_tien`, `trang_thai`, `ma_giao_dich`, `ngay_thanh_toan`, `ghi_chu`) VALUES
(1, 1, 1, 16400000, 'cho_thanh_toan', NULL, NULL, 'Thu tiền mặt khi giao hàng'),
(2, 2, 2, 4040000, 'da_thanh_toan', 'FT260902998811', '2026-09-02 14:20:00', 'VietQR chuyển khoản VCB'),
(3, 3, 3, 13900000, 'da_thanh_toan', 'MOMO202609109988', '2026-09-10 09:05:00', 'Thanh toán qua ví MoMo');
