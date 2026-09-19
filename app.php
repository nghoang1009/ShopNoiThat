<?php
/**
 * Định nghĩa toàn bộ Route trong hệ thống Shop Nội Thất
 * Hỗ trợ MVC Web + RESTful API v1 Chuẩn mực
 */

// ==========================================
// 1. CLIENT WEB ROUTES
// ==========================================
App::get('', ['HomeController', 'index']);
App::get('trang-chu', ['HomeController', 'index']);

// Sản phẩm
App::get('san-pham', ['SanPhamController', 'index']);
App::get('danh-muc/{slug}', ['SanPhamController', 'category']);
App::get('san-pham/{slug}', ['SanPhamController', 'detail']);

// Giỏ hàng
App::get('gio-hang', ['GioHangController', 'index']);

// Đơn hàng, Vận chuyển & Thanh toán
App::get('don-hang/thanh-toan', ['DonHangController', 'checkout']);
App::post('don-hang/thanh-toan', ['DonHangController', 'store']);
App::get('don-hang/thanh-cong', ['DonHangController', 'success']);
App::get('don-hang/lich-su', ['DonHangController', 'history']);
App::get('don-hang/chi-tiet/{id}', ['DonHangController', 'detail']);
App::get('don-hang/thanh-toan-online/{id}', ['DonHangController', 'payment']);
App::get('don-hang/tra-cuu', ['DonHangController', 'tracking']);
App::get('don-hang/tra-cuu/{id}', ['DonHangController', 'tracking']);

// Xác thực & Tài khoản khách hàng
App::get('auth/login', ['AuthController', 'login']);
App::post('auth/login', ['AuthController', 'login']);
App::get('auth/register', ['AuthController', 'register']);
App::post('auth/register', ['AuthController', 'register']);
App::get('auth/logout', ['AuthController', 'logout']);
App::get('auth/profile', ['AuthController', 'profile']);
App::post('auth/profile', ['AuthController', 'profile']);


// ==========================================
// 2. RESTFUL API v1 ROUTES
// ==========================================

// --- 2.1. Danh mục Resource: /api/v1/danh-muc ---
App::get('api/v1/danh-muc', ['DanhMucApiController', 'index']);
App::get('api/v1/danh-muc/{id}', ['DanhMucApiController', 'detail']);
App::post('api/v1/danh-muc', ['DanhMucApiController', 'create']);
App::put('api/v1/danh-muc/{id}', ['DanhMucApiController', 'update']);
App::delete('api/v1/danh-muc/{id}', ['DanhMucApiController', 'delete']);

// --- 2.2. Sản phẩm Resource: /api/v1/san-pham ---
App::get('api/v1/san-pham', ['SanPhamApiController', 'index']);
App::get('api/v1/san-pham/{id}', ['SanPhamApiController', 'detail']);
App::post('api/v1/san-pham', ['SanPhamApiController', 'create']);
App::put('api/v1/san-pham/{id}', ['SanPhamApiController', 'update']);
App::patch('api/v1/san-pham/{id}', ['SanPhamApiController', 'patchUpdate']);
App::delete('api/v1/san-pham/{id}', ['SanPhamApiController', 'delete']);

// --- 2.3. Giỏ hàng Resource: /api/v1/gio-hang ---
App::get('api/v1/gio-hang', ['GioHangApiController', 'index']);
App::post('api/v1/gio-hang', ['GioHangApiController', 'create']);
App::patch('api/v1/gio-hang/{id}', ['GioHangApiController', 'update']);
App::put('api/v1/gio-hang/{id}', ['GioHangApiController', 'update']);
App::delete('api/v1/gio-hang/{id}', ['GioHangApiController', 'delete']);
App::delete('api/v1/gio-hang', ['GioHangApiController', 'clear']);

// --- 2.4. Đơn hàng Resource & Nested: /api/v1/don-hang ---
App::post('api/v1/don-hang', ['DonHangApiController', 'create']);
App::get('api/v1/don-hang', ['DonHangApiController', 'index']);
App::get('api/v1/don-hang/{id}', ['DonHangApiController', 'detail']);
App::patch('api/v1/don-hang/{id}', ['DonHangApiController', 'updateStatus']);
App::put('api/v1/don-hang/{id}', ['DonHangApiController', 'update']);

// Nested Resource: /api/v1/don-hang/{id}/van-chuyen
App::get('api/v1/don-hang/{id}/van-chuyen', ['DonHangApiController', 'getShipping']);
App::put('api/v1/don-hang/{id}/van-chuyen', ['DonHangApiController', 'updateShipping']);
App::patch('api/v1/don-hang/{id}/van-chuyen', ['DonHangApiController', 'updateShipping']);

// Nested Resource: /api/v1/don-hang/{id}/thanh-toan
App::get('api/v1/don-hang/{id}/thanh-toan', ['ThanhToanApiController', 'getOrderPayment']);
App::post('api/v1/don-hang/{id}/thanh-toan', ['ThanhToanApiController', 'createOrderPayment']);
App::patch('api/v1/don-hang/{id}/thanh-toan', ['ThanhToanApiController', 'patchOrderPayment']);

// --- 2.5. Phương thức Vận chuyển & Vận chuyển Resource ---
App::get('api/v1/phuong-thuc-van-chuyen', ['VanChuyenApiController', 'methods']);
App::get('api/v1/phuong-thuc-van-chuyen/{id}', ['VanChuyenApiController', 'methodDetail']);
App::post('api/v1/phuong-thuc-van-chuyen', ['VanChuyenApiController', 'createMethod']);
App::put('api/v1/phuong-thuc-van-chuyen/{id}', ['VanChuyenApiController', 'updateMethod']);
App::delete('api/v1/phuong-thuc-van-chuyen/{id}', ['VanChuyenApiController', 'deleteMethod']);

App::get('api/v1/van-chuyen/phuong-thuc', ['VanChuyenApiController', 'methods']);
App::get('api/v1/van-chuyen/phi', ['VanChuyenApiController', 'calculateFee']);
App::get('api/v1/van-chuyen/tra-cuu/{code}', ['VanChuyenApiController', 'track']);
App::get('api/v1/van-chuyen/{code}', ['VanChuyenApiController', 'track']);

// --- 2.6. Phương thức Thanh toán & Thanh toán Resource ---
App::get('api/v1/thanh-toan', ['ThanhToanApiController', 'index']);
App::get('api/v1/phuong-thuc-thanh-toan', ['ThanhToanApiController', 'methods']);
App::post('api/v1/phuong-thuc-thanh-toan', ['ThanhToanApiController', 'createMethod']);
App::patch('api/v1/phuong-thuc-thanh-toan/{id}', ['ThanhToanApiController', 'patchMethod']);

App::get('api/v1/thanh-toan/phuong-thuc', ['ThanhToanApiController', 'methods']);
App::post('api/v1/thanh-toan', ['ThanhToanApiController', 'createOrderPayment']);
App::post('api/v1/thanh-toan/simulate', ['ThanhToanApiController', 'simulate']);

// --- 2.7. Thống kê & Dashboard: /api/v1/thong-ke ---
App::get('api/v1/thong-ke/tong-quan', ['ThongKeApiController', 'summary']);
App::get('api/v1/thong-ke/doanh-thu', ['ThongKeApiController', 'revenue']);
App::get('api/v1/thong-ke/san-pham-ban-chay', ['ThongKeApiController', 'topSelling']);
App::get('api/v1/thong-ke/dashboard', ['ThongKeApiController', 'dashboard']);

// --- 2.8. Khách hàng Resource (Admin): /api/v1/khach-hang ---
App::get('api/v1/khach-hang', ['KhachHangApiController', 'index']);
App::get('api/v1/khach-hang/{id}', ['KhachHangApiController', 'detail']);
App::patch('api/v1/khach-hang/{id}', ['KhachHangApiController', 'updateStatus']);

// --- 2.9. Nhân viên Resource (Admin): /api/v1/nhan-vien ---
App::get('api/v1/nhan-vien', ['NhanVienApiController', 'index']);
App::post('api/v1/nhan-vien', ['NhanVienApiController', 'create']);
App::get('api/v1/nhan-vien/{id}', ['NhanVienApiController', 'detail']);
App::put('api/v1/nhan-vien/{id}', ['NhanVienApiController', 'update']);
App::delete('api/v1/nhan-vien/{id}', ['NhanVienApiController', 'delete']);

// --- 2.10. Xác thực API: /api/v1/auth ---
App::post('api/v1/auth/login', ['AuthApiController', 'login']);
App::post('api/v1/auth/register', ['AuthApiController', 'register']);
App::post('api/v1/auth/logout', ['AuthApiController', 'logout']);
App::get('api/v1/auth/me', ['AuthApiController', 'me']);

// --- 2.11. AI Assistant Tư Vấn Resource: /api/v1/phien-tu-van ---
App::post('api/v1/phien-tu-van', ['TuVanApiController', 'createSession']);
App::get('api/v1/phien-tu-van', ['TuVanApiController', 'index']);
App::get('api/v1/phien-tu-van/{id}/tin-nhan', ['TuVanApiController', 'getMessages']);
App::post('api/v1/phien-tu-van/{id}/tin-nhan', ['TuVanApiController', 'sendMessage']);
App::delete('api/v1/phien-tu-van/{id}', ['TuVanApiController', 'deleteSession']);


// ==========================================
// 3. LEGACY API ROUTES (Tương thích ngược)
// ==========================================
App::get('api/san-pham', ['SanPhamApiController', 'index']);
App::get('api/san-pham/{id}', ['SanPhamApiController', 'detail']);
App::get('api/danh-muc', ['DanhMucApiController', 'index']);
App::get('api/gio-hang', ['GioHangApiController', 'index']);
App::post('api/gio-hang/them', ['GioHangApiController', 'create']);
App::put('api/gio-hang/{id}', ['GioHangApiController', 'update']);
App::patch('api/gio-hang/{id}', ['GioHangApiController', 'update']);
App::delete('api/gio-hang/{id}', ['GioHangApiController', 'delete']);
App::delete('api/gio-hang', ['GioHangApiController', 'clear']);
App::post('api/don-hang', ['DonHangApiController', 'create']);
App::get('api/don-hang/{id}', ['DonHangApiController', 'detail']);
App::put('api/don-hang/{id}/trang-thai', ['DonHangApiController', 'updateStatus']);
App::get('api/van-chuyen/phuong-thuc', ['VanChuyenApiController', 'methods']);
App::get('api/van-chuyen/phi', ['VanChuyenApiController', 'calculateFee']);
App::get('api/van-chuyen/tra-cuu/{id}', ['VanChuyenApiController', 'track']);
App::get('api/thanh-toan/phuong-thuc', ['ThanhToanApiController', 'methods']);
App::post('api/thanh-toan', ['ThanhToanApiController', 'createOrderPayment']);
App::post('api/thanh-toan/simulate', ['ThanhToanApiController', 'simulate']);
App::get('api/thanh-toan/don-hang/{id}', ['ThanhToanApiController', 'getOrderPayment']);
App::get('api/dashboard/thong-ke', ['ThongKeApiController', 'dashboard']);
App::get('api/thong-ke/tong-quan', ['ThongKeApiController', 'summary']);
App::get('api/thong-ke/doanh-thu-7-ngay', ['ThongKeApiController', 'revenue']);


// ==========================================
// 4. ADMIN WEB ROUTES (HTML Views)
// ==========================================
App::get('admin', ['AdminController', 'index']);
App::get('admin/login', ['AdminController', 'login']);
App::post('admin/login', ['AdminController', 'login']);
App::get('admin/logout', ['AdminController', 'logout']);

// Quản lý Danh mục
App::get('admin/danh-muc', ['AdminController', 'danhmucIndex']);
App::get('admin/danh-muc/them', ['AdminController', 'danhmucCreate']);
App::post('admin/danh-muc/them', ['AdminController', 'danhmucCreate']);
App::get('admin/danh-muc/sua/{id}', ['AdminController', 'danhmucEdit']);
App::post('admin/danh-muc/sua/{id}', ['AdminController', 'danhmucEdit']);
App::get('admin/danh-muc/xoa/{id}', ['AdminController', 'danhmucDelete']);

// Quản lý Sản phẩm
App::get('admin/san-pham', ['AdminController', 'sanphamIndex']);
App::get('admin/san-pham/them', ['AdminController', 'sanphamCreate']);
App::post('admin/san-pham/them', ['AdminController', 'sanphamCreate']);
App::get('admin/san-pham/sua/{id}', ['AdminController', 'sanphamEdit']);
App::post('admin/san-pham/sua/{id}', ['AdminController', 'sanphamEdit']);
App::get('admin/san-pham/xoa/{id}', ['AdminController', 'sanphamDelete']);

// Quản lý Đơn hàng
App::get('admin/don-hang', ['AdminController', 'donhangIndex']);
App::get('admin/don-hang/chi-tiet/{id}', ['AdminController', 'donhangDetail']);
App::post('admin/don-hang/chi-tiet/{id}', ['AdminController', 'donhangDetail']);

// Quản lý Vận chuyển & Giao hàng
App::get('admin/van-chuyen', ['AdminController', 'vanchuyenIndex']);
App::post('admin/van-chuyen/cap-nhat', ['AdminController', 'vanchuyenUpdate']);
App::get('admin/van-chuyen/phuong-thuc', ['AdminController', 'vanchuyenMethods']);
App::get('admin/van-chuyen/phuong-thuc/them', ['AdminController', 'vanchuyenMethodCreate']);
App::post('admin/van-chuyen/phuong-thuc/them', ['AdminController', 'vanchuyenMethodCreate']);
App::get('admin/van-chuyen/phuong-thuc/sua/{id}', ['AdminController', 'vanchuyenMethodEdit']);
App::post('admin/van-chuyen/phuong-thuc/sua/{id}', ['AdminController', 'vanchuyenMethodEdit']);
App::get('admin/van-chuyen/phuong-thuc/xoa/{id}', ['AdminController', 'vanchuyenMethodDelete']);

// Quản lý & Đối soát Thanh toán
App::get('admin/thanh-toan', ['AdminController', 'thanhtoanIndex']);
App::get('admin/thanh-toan/xac-nhan/{id}', ['AdminController', 'thanhtoanConfirm']);
App::get('admin/thanh-toan/phuong-thuc', ['AdminController', 'thanhtoanMethods']);
App::get('admin/thanh-toan/doi-trang-thai/{id}', ['AdminController', 'thanhtoanToggleMethod']);

// Quản lý Khách hàng
App::get('admin/khach-hang', ['AdminController', 'khachhangIndex']);
App::get('admin/khach-hang/doi-trang-thai/{id}', ['AdminController', 'khachhangToggleStatus']);

// Quản lý Nhân viên
App::get('admin/nhan-vien', ['AdminController', 'nhanvienIndex']);
App::get('admin/nhan-vien/them', ['AdminController', 'nhanvienCreate']);
App::post('admin/nhan-vien/them', ['AdminController', 'nhanvienCreate']);
App::get('admin/nhan-vien/sua/{id}', ['AdminController', 'nhanvienEdit']);
App::post('admin/nhan-vien/sua/{id}', ['AdminController', 'nhanvienEdit']);
App::get('admin/nhan-vien/xoa/{id}', ['AdminController', 'nhanvienDelete']);

// Quản lý Lịch sử Tư vấn AI Assistant
App::get('admin/tu-van', ['AdminController', 'tuvanIndex']);
App::get('admin/tu-van/chi-tiet/{id}', ['AdminController', 'tuvanDetail']);
App::get('admin/tu-van/xoa/{id}', ['AdminController', 'tuvanDelete']);
