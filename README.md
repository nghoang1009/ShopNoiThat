# Shop Nội Thất - Hệ Thống Bán Hàng Nội Thất (PHP MVC + REST API)

Dự án website bán hàng nội thất cao cấp (Sofa, Bàn ăn, Bàn trà, Giường ngủ, Tủ kệ, Đèn trang trí...) xây dựng bằng **PHP thuần theo kiến trúc MVC + RESTful API**, không sử dụng framework, tương thích chuẩn XAMPP.

---

## 1. Cấu trúc thư mục
```
ShopNoiThat/
├── .htaccess                   # Rewrite URL thân thiện về index.php
├── index.php                   # Entry Point khởi tạo ứng dụng
├── app.php                     # Khai báo bảng định tuyến (Routes Web & API)
├── qlnoithat.sql               # Database Schema và dữ liệu mẫu nội thất
├── config/
│   └── config.php              # Cấu hình kết nối DB, Base URL, Flash Message
├── core/
│   ├── App.php                 # Bộ định tuyến Router (GET, POST, PUT, DELETE)
│   ├── Controller.php          # Base Controller, render View HTML, xuất JSON API, validate dữ liệu
│   ├── Model.php               # Base Model CRUD PDO
│   └── Database.php            # Singleton PDO Connection
├── model/
│   ├── DanhMucModel.php
│   ├── SanPhamModel.php
│   ├── GioHangModel.php
│   ├── DonHangModel.php
│   ├── ChiTietDonHangModel.php
│   ├── KhachHangModel.php
│   ├── NhanVienModel.php
│   ├── NhaCungCapModel.php
│   ├── VanChuyenModel.php       # Quản lý vận chuyển, tính cước phí, timeline
│   ├── ThanhToanModel.php       # Quản lý thanh toán, VietQR, giả lập gateway
│   └── ThongKeModel.php         # Thống kê KPI, doanh thu theo ngày, top bán, tồn kho thấp
├── controller/
│   ├── HomeController.php
│   ├── SanPhamController.php
│   ├── GioHangController.php
│   ├── DonHangController.php
│   ├── AuthController.php
│   ├── AdminController.php
│   ├── ApiSanPhamController.php
│   ├── ApiGioHangController.php
│   ├── ApiDonHangController.php
│   ├── ApiVanChuyenController.php
│   ├── ApiThanhToanController.php
│   ├── ApiDashboardController.php
│   └── ApiThongKeController.php
├── views/
│   ├── client/                 # Giao diện Khách hàng (Home, Sản phẩm, Giỏ hàng, Đặt hàng, Thanh toán QR, Tra cứu vận chuyển, Auth...)
│   └── admin/                  # Giao diện Quản trị (Dashboard biểu đồ, CRUD Sản phẩm, Vận chuyển, Đối soát Thanh toán...)
├── includes/
│   ├── header.php / footer.php
│   └── admin_header.php / admin_sidebar.php / admin_footer.php
└── assets/
    ├── css/
    │   ├── client/ (style.css, sanpham.css, giohang.css, checkout.css, vanchuyen_thanhtoan.css)
    │   └── admin/ (admin.css)
    └── js/
        ├── client/ (main.js, sanpham.js, giohang.js, checkout.js, payment.js)
        └── admin/ (admin.js, dashboard.js)
```

---

## 2. Hướng dẫn cài đặt và chạy trên XAMPP

1. **Khởi động XAMPP**: Bật module **Apache** và **MySQL**.
2. **Import Database**:
   - Truy cập `http://localhost/phpmyadmin/`.
   - Tạo Database mới tên `qlnoithat` (utf8mb4_unicode_ci).
   - Import file `qlnoithat.sql` trong thư mục gốc.
3. **Cấu hình DB (nếu cần)**:
   - Mở file `config/config.php` kiểm tra `DB_USER` (mặc định `root`), `DB_PASS` (mặc định trống).
4. **Truy cập hệ thống**:
   - **Trang Khách hàng**: `http://localhost/ShopNoiThat/`
   - **Trang Tra Cứu Vận Chuyển**: `http://localhost/ShopNoiThat/don-hang/tra-cuu`
   - **Trang Quản trị Admin**: `http://localhost/ShopNoiThat/admin`

---

## 3. Tài khoản demo

| Vai trò | Tài khoản / Email | Mật khẩu | Ghi chú |
|---|---|---|---|
| **Quản trị viên (Admin)** | `admin` | `123456` | Toàn quyền quản trị |
| **Nhân viên (Staff)** | `nhanvien` | `123456` | Quản lý đơn hàng, kho |
| **Khách hàng 1** | `nguyenvanan@gmail.com` | `123456` | Khách hàng mẫu |
| **Khách hàng 2** | `tranthimai@gmail.com` | `123456` | Khách hàng mẫu |

---

## 4. Danh sách RESTful API Endpoints (JSON)

### A. Vận Chuyển & Phí Cước
| Phương thức | Endpoint | Chức năng | Body / Params |
|---|---|---|---|
| `GET` | `/api/van-chuyen/phuong-thuc` | Lấy danh sách phương thức vận chuyển | |
| `GET` | `/api/van-chuyen/phi` | Tính phí vận chuyển tự động theo tỉnh/tổng tiền | `?phuong_thuc_id=&subtotal=&tinh_thanh=` |
| `GET` | `/api/van-chuyen/tra-cuu/{code}` | Tra cứu tiến độ vận chuyển & timeline | Mã vận đơn hoặc Mã đơn hàng |
| `PUT` | `/api/van-chuyen/{don_hang_id}` | Admin cập nhật mã vận đơn, trạng thái giao | `{ "trang_thai_giao_hang": "dang_giao", "don_vi_van_chuyen": "...", "ma_van_don": "..." }` |

### B. Thanh Toán & Cổng Thanh Toán
| Phương thức | Endpoint | Chức năng | Body / Params |
|---|---|---|---|
| `GET` | `/api/thanh-toan/phuong-thuc` | Lấy danh sách phương thức thanh toán | |
| `POST` | `/api/thanh-toan` | Khởi tạo giao dịch thanh toán | `{ "don_hang_id": 1, "phuong_thuc_thanh_toan_id": 2, "so_tien": 5000000 }` |
| `PUT` | `/api/thanh-toan/{id}/xac-nhan` | Xác nhận đã nhận tiền (Admin/Callback) | `{ "ma_giao_dich": "TXN...", "ghi_chu": "..." }` |
| `POST` | `/api/thanh-toan/simulate` | Giả lập quét mã / thanh toán online thành công | `{ "ma_don_hang": "DH...", "phuong_thuc": "banking" }` |
| `GET` | `/api/thanh-toan/don-hang/{id}` | Lấy chi tiết thanh toán của đơn hàng | ID đơn hàng |

### C. Dashboard & Thống Kê
| Phương thức | Endpoint | Chức năng | Body / Params |
|---|---|---|---|
| `GET` | `/api/dashboard/thong-ke` | Toàn bộ số liệu KPI, biểu đồ, top bán, tồn kho thấp | `?tu_ngay=2026-09-01&den_ngay=2026-09-16` |
| `GET` | `/api/thong-ke/tong-quan` | Thống kê tổng quan | Yêu cầu Admin session |
| `GET` | `/api/thong-ke/doanh-thu-7-ngay` | Dữ liệu doanh thu 7 ngày | Yêu cầu Admin session |

### D. Sản Phẩm & Giỏ Hàng & Đơn Hàng
| Phương thức | Endpoint | Chức năng | Body / Params |
|---|---|---|---|
| `GET` | `/api/san-pham` | Lọc & tìm kiếm sản phẩm nội thất | `?danh_muc=&gia_min=&gia_max=&chat_lieu=&sort=&keyword=` |
| `GET` | `/api/san-pham/{id}` | Chi tiết sản phẩm & liên quan | ID hoặc Slug |
| `POST` | `/api/gio-hang/them` | Thêm sản phẩm vào giỏ (AJAX) | `{ "san_pham_id": 1, "so_luong": 1 }` |
| `PUT` | `/api/gio-hang/{id}` | Cập nhật số lượng giỏ hàng | `{ "so_luong": 2 }` |
| `DELETE` | `/api/gio-hang/{id}` | Xóa sản phẩm khỏi giỏ | |
| `POST` | `/api/don-hang` | Đặt hàng từ giỏ | `{ "ho_ten_nhan": "...", "sdt_nhan": "...", "dia_chi_giao": "...", "tinh_thanh": "...", "phuong_thuc_van_chuyen_id": 1, "phuong_thuc_thanh_toan_id": 2 }` |
| `PUT` | `/api/don-hang/{id}/trang-thai` | Admin đổi trạng thái đơn | `{ "trang_thai": "hoan_tat", "trang_thai_thanh_toan": "da_thanh_toan" }` |

---

Format response JSON chuẩn:
```json
{
  "success": true,
  "data": { ... },
  "message": "Thông báo kết quả"
}
```
