<?php
require_once __DIR__ . '/../core/Controller.php';

class AdminController extends Controller {
    private NhanVienModel $nhanVienModel;
    private DanhMucModel $danhMucModel;
    private SanPhamModel $sanPhamModel;
    private DonHangModel $donHangModel;
    private KhachHangModel $khachHangModel;
    private ThongKeModel $thongKeModel;
    private VanChuyenModel $vanChuyenModel;
    private ThanhToanModel $thanhToanModel;
    private TuVanModel $tuVanModel;

    public function __construct() {
        $this->nhanVienModel = $this->model('NhanVienModel');
        $this->danhMucModel = $this->model('DanhMucModel');
        $this->sanPhamModel = $this->model('SanPhamModel');
        $this->donHangModel = $this->model('DonHangModel');
        $this->khachHangModel = $this->model('KhachHangModel');
        $this->thongKeModel = $this->model('ThongKeModel');
        $this->vanChuyenModel = $this->model('VanChuyenModel');
        $this->thanhToanModel = $this->model('ThanhToanModel');
        $this->tuVanModel = $this->model('TuVanModel');
    }

    /**
     * Đăng nhập Admin
     */
    public function login(): void {
        if (isset($_SESSION['admin']['id'])) {
            redirect('admin');
            return;
        }

        if ($this->isPost()) {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            do {
                if (empty($username) || empty($password)) {
                    set_flash('admin_login_error', 'Vui lòng nhập tài khoản và mật khẩu.', 'danger');
                    redirect('admin/login');
                    break;
                }

                $admin = $this->nhanVienModel->authenticate($username, $password);
                if ($admin) {
                    $_SESSION['admin'] = $admin;
                    set_flash('admin_msg', 'Chào mừng ' . $admin['ho_ten'] . ' quay lại trang quản trị!', 'success');
                    redirect('admin');
                    return;
                }

                set_flash('admin_login_error', 'Tài khoản hoặc mật khẩu không chính xác.', 'danger');
                redirect('admin/login');
                break;
            } while (false);
            return;
        }

        $this->view('admin/auth/login', [
            'pageTitle' => 'Đăng Nhập Quản Trị'
        ]);
    }

    /**
     * Đăng xuất Admin
     */
    public function logout(): void {
        unset($_SESSION['admin']);
        set_flash('admin_msg', 'Đã đăng xuất thành công.', 'info');
        redirect('admin/login');
    }

    /**
     * Dashboard / Thống kê nâng cao
     */
    public function index(): void {
        $admin = $this->requireAdminAuth();

        $fromDate = $this->getQuery('tu_ngay', date('Y-m-d', strtotime('-6 days')));
        $toDate = $this->getQuery('den_ngay', date('Y-m-d'));

        $summary = $this->thongKeModel->getOverviewSummary();
        $topProducts = $this->thongKeModel->getTopSellingProducts(5);
        $chartData = $this->thongKeModel->getRevenueByDateRange($fromDate, $toDate);
        $paymentStats = $this->thongKeModel->getPaymentMethodStats();
        $shippingStats = $this->thongKeModel->getShippingStatusStats();
        $recentOrders = $this->donHangModel->getOrders(['limit' => 6]);

        $this->view('admin/dashboard/index', [
            'pageTitle'     => 'Bảng Điều Khiển & Thống Kê',
            'admin'         => $admin,
            'summary'       => $summary,
            'topProducts'   => $topProducts,
            'chartData'     => $chartData,
            'paymentStats'  => $paymentStats,
            'shippingStats' => $shippingStats,
            'recentOrders'  => $recentOrders,
            'fromDate'      => $fromDate,
            'toDate'        => $toDate
        ]);
    }

    // ==========================================
    // MODULE VẬN CHUYỂN
    // ==========================================

    public function vanchuyenIndex(): void {
        $this->requireAdminAuth();

        $filters = [
            'trang_thai_giao_hang' => $this->getQuery('trang_thai_giao_hang'),
            'keyword'              => $this->getQuery('keyword')
        ];

        $shipments = $this->vanChuyenModel->getShipmentList($filters);

        $this->view('admin/vanchuyen/index', [
            'pageTitle' => 'Quản Lý Vận Chuyển & Giao Hàng',
            'shipments' => $shipments,
            'filters'   => $filters
        ]);
    }

    public function vanchuyenUpdate(): void {
        $this->requireAdminAuth();
        if ($this->isPost()) {
            $orderId = (int)$_POST['don_hang_id'];
            $status = $_POST['trang_thai_giao_hang'] ?? 'cho_lay_hang';
            $carrier = trim($_POST['don_vi_van_chuyen'] ?? '');
            $trackingCode = trim($_POST['ma_van_don'] ?? '');
            $notes = trim($_POST['ghi_chu'] ?? '');

            $this->vanChuyenModel->updateShippingStatus($orderId, $status, $carrier, $trackingCode, $notes);
            set_flash('vc_success', 'Cập nhật vận chuyển và mã vận đơn thành công!', 'success');
        }
        redirect('admin/van-chuyen');
    }

    public function vanchuyenMethods(): void {
        $this->requireAdminAuth();
        $methods = $this->vanChuyenModel->getAllMethods();

        $this->view('admin/vanchuyen/methods', [
            'pageTitle' => 'Cấu Hình Phương Thức & Phí Vận Chuyển',
            'methods'   => $methods
        ]);
    }

    public function vanchuyenMethodCreate(): void {
        $this->requireAdminAuth();
        if ($this->isPost()) {
            $post = $this->getPost();
            $data = [
                'ten_pt'            => trim($post['ten_pt']),
                'ma_pt'             => slugify($post['ten_pt']),
                'phi_van_chuyen'    => (float)($post['phi_van_chuyen'] ?? 0),
                'thoi_gian_du_kien' => trim($post['thoi_gian_du_kien'] ?? '3 - 5 ngày'),
                'mo_ta'             => trim($post['mo_ta'] ?? ''),
                'trang_thai'        => isset($post['trang_thai']) ? 1 : 0
            ];
            $this->vanChuyenModel->createMethod($data);
            set_flash('vc_success', 'Thêm phương thức vận chuyển thành công!', 'success');
            redirect('admin/van-chuyen/phuong-thuc');
            return;
        }

        $this->view('admin/vanchuyen/method_create', [
            'pageTitle' => 'Thêm Phương Thức Vận Chuyển'
        ]);
    }

    public function vanchuyenMethodEdit($id): void {
        $this->requireAdminAuth();
        $method = $this->vanChuyenModel->getMethodById((int)$id);
        if (!$method) {
            set_flash('vc_error', 'Phương thức không tồn tại.', 'danger');
            redirect('admin/van-chuyen/phuong-thuc');
            return;
        }

        if ($this->isPost()) {
            $post = $this->getPost();
            $data = [
                'ten_pt'            => trim($post['ten_pt']),
                'phi_van_chuyen'    => (float)($post['phi_van_chuyen'] ?? 0),
                'thoi_gian_du_kien' => trim($post['thoi_gian_du_kien'] ?? '3 - 5 ngày'),
                'mo_ta'             => trim($post['mo_ta'] ?? ''),
                'trang_thai'        => isset($post['trang_thai']) ? 1 : 0
            ];
            $this->vanChuyenModel->updateMethod((int)$id, $data);
            set_flash('vc_success', 'Cập nhật phương thức vận chuyển thành công!', 'success');
            redirect('admin/van-chuyen/phuong-thuc');
            return;
        }

        $this->view('admin/vanchuyen/method_edit', [
            'pageTitle' => 'Chỉnh Sửa Phương Thức Vận Chuyển',
            'method'    => $method
        ]);
    }

    public function vanchuyenMethodDelete($id): void {
        $this->requireAdminAuth();
        $this->vanChuyenModel->deleteMethod((int)$id);
        set_flash('vc_success', 'Đã xóa phương thức vận chuyển.', 'success');
        redirect('admin/van-chuyen/phuong-thuc');
    }

    // ==========================================
    // MODULE THANH TOÁN
    // ==========================================

    public function thanhtoanIndex(): void {
        $this->requireAdminAuth();

        $filters = [
            'trang_thai'     => $this->getQuery('trang_thai'),
            'phuong_thuc_id' => $this->getQuery('phuong_thuc_id'),
            'keyword'        => $this->getQuery('keyword')
        ];

        $transactions = $this->thanhToanModel->getTransactionList($filters);
        $paymentMethods = $this->thanhToanModel->getAllMethods();

        $this->view('admin/thanhtoan/index', [
            'pageTitle'      => 'Lịch Sử Giao Dịch & Đối Soát Thanh Toán',
            'transactions'   => $transactions,
            'paymentMethods' => $paymentMethods,
            'filters'        => $filters
        ]);
    }

    public function thanhtoanConfirm($id): void {
        $this->requireAdminAuth();
        $transCode = $_GET['ma_gd'] ?? ('TXN' . date('YmdHis'));
        $this->thanhToanModel->confirmPayment((int)$id, $transCode, 'Admin xác nhận thanh toán trực tiếp');
        set_flash('tt_success', 'Xác nhận thanh toán và đồng bộ đơn hàng thành công!', 'success');
        redirect('admin/thanh-toan');
    }

    public function thanhtoanMethods(): void {
        $this->requireAdminAuth();
        $methods = $this->thanhToanModel->getAllMethods();

        $this->view('admin/thanhtoan/methods', [
            'pageTitle' => 'Cấu Hình & Quản Lý Phương Thức Thanh Toán',
            'methods'   => $methods
        ]);
    }

    public function thanhtoanToggleMethod($id): void {
        $this->requireAdminAuth();
        $this->thanhToanModel->toggleMethodStatus((int)$id);
        set_flash('tt_success', 'Đã cập nhật trạng thái phương thức thanh toán!', 'success');
        redirect('admin/thanh-toan/phuong-thuc');
    }

    // ==========================================
    // MODULE QUẢN LÝ DANH MỤC
    // ==========================================

    public function danhmucIndex(): void {
        $this->requireAdminAuth();
        $categories = $this->danhMucModel->getActiveCategories();
        $this->view('admin/danhmuc/index', [
            'pageTitle'  => 'Quản Lý Danh Mục',
            'categories' => $categories
        ]);
    }

    public function danhmucCreate(): void {
        $this->requireAdminAuth();

        if ($this->isPost()) {
            $post = $this->getPost();
            $rules = [
                'ten_danh_muc' => ['required' => true, 'min' => 2, 'label' => 'Tên danh mục']
            ];

            do {
                $errors = $this->validateInputs($rules, $post);
                if (!empty($errors)) {
                    set_flash('dm_error', reset($errors), 'danger');
                    redirect('admin/danh-muc/them');
                    break;
                }

                $slug = !empty($post['slug']) ? slugify($post['slug']) : slugify($post['ten_danh_muc']);
                $imagePath = $this->handleUpload('hinh_anh', 'categories');

                $data = [
                    'ten_danh_muc' => trim($post['ten_danh_muc']),
                    'slug'         => $slug,
                    'mo_ta'        => trim($post['mo_ta'] ?? ''),
                    'hinh_anh'     => $imagePath,
                    'trang_thai'   => isset($post['trang_thai']) ? (int)$post['trang_thai'] : 1
                ];

                $this->danhMucModel->create($data);
                set_flash('dm_success', 'Thêm danh mục mới thành công!', 'success');
                redirect('admin/danh-muc');
                return;
            } while (false);
            return;
        }

        $this->view('admin/danhmuc/create', [
            'pageTitle' => 'Thêm Danh Mục Mới'
        ]);
    }

    public function danhmucEdit($id): void {
        $this->requireAdminAuth();
        $category = $this->danhMucModel->find($id);
        if (!$category) {
            set_flash('dm_error', 'Danh mục không tồn tại.', 'danger');
            redirect('admin/danh-muc');
            return;
        }

        if ($this->isPost()) {
            $post = $this->getPost();
            $rules = [
                'ten_danh_muc' => ['required' => true, 'min' => 2, 'label' => 'Tên danh mục']
            ];

            do {
                $errors = $this->validateInputs($rules, $post);
                if (!empty($errors)) {
                    set_flash('dm_error', reset($errors), 'danger');
                    redirect('admin/danh-muc/sua/' . $id);
                    break;
                }

                $slug = !empty($post['slug']) ? slugify($post['slug']) : slugify($post['ten_danh_muc']);
                $imagePath = $this->handleUpload('hinh_anh', 'categories') ?: $category['hinh_anh'];

                $data = [
                    'ten_danh_muc' => trim($post['ten_danh_muc']),
                    'slug'         => $slug,
                    'mo_ta'        => trim($post['mo_ta'] ?? ''),
                    'hinh_anh'     => $imagePath,
                    'trang_thai'   => isset($post['trang_thai']) ? (int)$post['trang_thai'] : 1
                ];

                $this->danhMucModel->update((int)$id, $data);
                set_flash('dm_success', 'Cập nhật danh mục thành công!', 'success');
                redirect('admin/danh-muc');
                return;
            } while (false);
            return;
        }

        $this->view('admin/danhmuc/edit', [
            'pageTitle' => 'Chỉnh Sửa Danh Mục',
            'category'  => $category
        ]);
    }

    public function danhmucDelete($id): void {
        $this->requireAdminAuth();
        $this->danhMucModel->delete((int)$id);
        set_flash('dm_success', 'Xóa danh mục thành công!', 'success');
        redirect('admin/danh-muc');
    }

    // ==========================================
    // MODULE QUẢN LÝ SẢN PHẨM
    // ==========================================

    public function sanphamIndex(): void {
        $this->requireAdminAuth();

        $filters = [
            'danh_muc_id' => $this->getQuery('danh_muc_id'),
            'keyword'     => $this->getQuery('keyword'),
            'sort'        => $this->getQuery('sort', 'moi_nhat')
        ];

        $categories = $this->danhMucModel->all('id ASC');
        $products = $this->sanPhamModel->filterProducts($filters);

        $this->view('admin/sanpham/index', [
            'pageTitle'  => 'Quản Lý Sản Phẩm',
            'categories' => $categories,
            'products'   => $products,
            'filters'    => $filters
        ]);
    }

    public function sanphamCreate(): void {
        $this->requireAdminAuth();
        $categories = $this->danhMucModel->getActiveCategories();
        $nhaCungCapModel = $this->model('NhaCungCapModel');
        $suppliers = $nhaCungCapModel->all('id ASC');

        if ($this->isPost()) {
            $post = $this->getPost();
            $rules = [
                'ten_san_pham' => ['required' => true, 'min' => 3, 'label' => 'Tên sản phẩm'],
                'danh_muc_id'  => ['required' => true, 'numeric' => true, 'label' => 'Danh mục'],
                'gia'          => ['required' => true, 'numeric' => true, 'label' => 'Giá bán']
            ];

            do {
                $errors = $this->validateInputs($rules, $post);
                if (!empty($errors)) {
                    set_flash('sp_error', reset($errors), 'danger');
                    redirect('admin/san-pham/them');
                    break;
                }

                $slug = !empty($post['slug']) ? slugify($post['slug']) : slugify($post['ten_san_pham']);
                $imagePath = $this->handleUpload('hinh_anh', 'products') ?: 'assets/images/default.jpg';
                $extraImages = $this->handleMultipleUploads('hinh_anh_phu', 'products');

                $data = [
                    'ten_san_pham'    => trim($post['ten_san_pham']),
                    'slug'            => $slug . '-' . time(),
                    'danh_muc_id'     => (int)$post['danh_muc_id'],
                    'nha_cung_cap_id' => !empty($post['nha_cung_cap_id']) ? (int)$post['nha_cung_cap_id'] : null,
                    'gia'             => (float)$post['gia'],
                    'gia_khuyen_mai'  => !empty($post['gia_khuyen_mai']) ? (float)$post['gia_khuyen_mai'] : 0,
                    'chat_lieu'       => trim($post['chat_lieu'] ?? ''),
                    'kich_thuoc'      => trim($post['kich_thuoc'] ?? ''),
                    'mau_sac'         => trim($post['mau_sac'] ?? ''),
                    'mo_ta'           => $_POST['mo_ta'] ?? '',
                    'hinh_anh'        => $imagePath,
                    'hinh_anh_phu'    => !empty($extraImages) ? json_encode($extraImages) : null,
                    'so_luong_ton'    => (int)($post['so_luong_ton'] ?? 0),
                    'noi_bat'         => isset($post['noi_bat']) ? 1 : 0,
                    'trang_thai'      => isset($post['trang_thai']) ? (int)$post['trang_thai'] : 1
                ];

                $this->sanPhamModel->create($data);
                set_flash('sp_success', 'Thêm sản phẩm nội thất mới thành công!', 'success');
                redirect('admin/san-pham');
                return;
            } while (false);
            return;
        }

        $this->view('admin/sanpham/create', [
            'pageTitle'  => 'Thêm Sản Phẩm Mới',
            'categories' => $categories,
            'suppliers'  => $suppliers
        ]);
    }

    public function sanphamEdit($id): void {
        $this->requireAdminAuth();
        $product = $this->sanPhamModel->find($id);
        if (!$product) {
            set_flash('sp_error', 'Sản phẩm không tồn tại.', 'danger');
            redirect('admin/san-pham');
            return;
        }

        $categories = $this->danhMucModel->getActiveCategories();
        $nhaCungCapModel = $this->model('NhaCungCapModel');
        $suppliers = $nhaCungCapModel->all('id ASC');

        if ($this->isPost()) {
            $post = $this->getPost();
            $rules = [
                'ten_san_pham' => ['required' => true, 'min' => 3, 'label' => 'Tên sản phẩm'],
                'danh_muc_id'  => ['required' => true, 'numeric' => true, 'label' => 'Danh mục'],
                'gia'          => ['required' => true, 'numeric' => true, 'label' => 'Giá bán']
            ];

            do {
                $errors = $this->validateInputs($rules, $post);
                if (!empty($errors)) {
                    set_flash('sp_error', reset($errors), 'danger');
                    redirect('admin/san-pham/sua/' . $id);
                    break;
                }

                $imagePath = $this->handleUpload('hinh_anh', 'products') ?: $product['hinh_anh'];
                $extraUploaded = $this->handleMultipleUploads('hinh_anh_phu', 'products');
                $extraImages = !empty($extraUploaded) ? json_encode($extraUploaded) : $product['hinh_anh_phu'];

                $data = [
                    'ten_san_pham'    => trim($post['ten_san_pham']),
                    'danh_muc_id'     => (int)$post['danh_muc_id'],
                    'nha_cung_cap_id' => !empty($post['nha_cung_cap_id']) ? (int)$post['nha_cung_cap_id'] : null,
                    'gia'             => (float)$post['gia'],
                    'gia_khuyen_mai'  => !empty($post['gia_khuyen_mai']) ? (float)$post['gia_khuyen_mai'] : 0,
                    'chat_lieu'       => trim($post['chat_lieu'] ?? ''),
                    'kich_thuoc'      => trim($post['kich_thuoc'] ?? ''),
                    'mau_sac'         => trim($post['mau_sac'] ?? ''),
                    'mo_ta'           => $_POST['mo_ta'] ?? '',
                    'hinh_anh'        => $imagePath,
                    'hinh_anh_phu'    => $extraImages,
                    'so_luong_ton'    => (int)($post['so_luong_ton'] ?? 0),
                    'noi_bat'         => isset($post['noi_bat']) ? 1 : 0,
                    'trang_thai'      => isset($post['trang_thai']) ? (int)$post['trang_thai'] : 1
                ];

                $this->sanPhamModel->update((int)$id, $data);
                set_flash('sp_success', 'Cập nhật sản phẩm thành công!', 'success');
                redirect('admin/san-pham');
                return;
            } while (false);
            return;
        }

        $this->view('admin/sanpham/edit', [
            'pageTitle'  => 'Chỉnh Sửa Sản Phẩm',
            'product'    => $product,
            'categories' => $categories,
            'suppliers'  => $suppliers
        ]);
    }

    public function sanphamDelete($id): void {
        $this->requireAdminAuth();
        $this->sanPhamModel->delete((int)$id);
        set_flash('sp_success', 'Xóa sản phẩm thành công!', 'success');
        redirect('admin/san-pham');
    }

    // ==========================================
    // MODULE QUẢN LÝ ĐƠN HÀNG
    // ==========================================

    public function donhangIndex(): void {
        $this->requireAdminAuth();

        $filters = [
            'trang_thai' => $this->getQuery('trang_thai'),
            'keyword'    => $this->getQuery('keyword')
        ];

        $orders = $this->donHangModel->getOrders($filters);

        $this->view('admin/donhang/index', [
            'pageTitle' => 'Quản Lý Đơn Hàng',
            'orders'    => $orders,
            'filters'   => $filters
        ]);
    }

    public function donhangDetail($id): void {
        $this->requireAdminAuth();
        $order = $this->donHangModel->getOrderDetail($id);
        if (!$order) {
            set_flash('order_error', 'Đơn hàng không tồn tại.', 'danger');
            redirect('admin/don-hang');
            return;
        }

        if ($this->isPost()) {
            $status = $_POST['trang_thai'] ?? '';
            $paymentStatus = $_POST['trang_thai_thanh_toan'] ?? null;
            $this->donHangModel->updateStatus((int)$id, $status, $paymentStatus);
            set_flash('order_success', 'Cập nhật trạng thái đơn hàng thành công!', 'success');
            redirect('admin/don-hang/chi-tiet/' . $id);
            return;
        }

        $this->view('admin/donhang/detail', [
            'pageTitle' => 'Chi Tiết Đơn Hàng #' . $order['ma_don_hang'],
            'order'     => $order
        ]);
    }

    // ==========================================
    // MODULE QUẢN LÝ KHÁCH HÀNG & NHÂN VIÊN
    // ==========================================

    public function khachhangIndex(): void {
        $this->requireAdminAuth();
        $customers = $this->khachHangModel->all('id DESC');

        $this->view('admin/khachhang/index', [
            'pageTitle' => 'Quản Lý Khách Hàng',
            'customers' => $customers
        ]);
    }

    public function khachhangToggleStatus($id): void {
        $this->requireAdminAuth();
        $customer = $this->khachHangModel->find($id);
        if ($customer) {
            $newStatus = $customer['trang_thai'] == 1 ? 0 : 1;
            $this->khachHangModel->update((int)$id, ['trang_thai' => $newStatus]);
            set_flash('kh_success', 'Đã đổi trạng thái khách hàng thành công!', 'success');
        }
        redirect('admin/khach-hang');
    }

    public function nhanvienIndex(): void {
        $this->requireAdminAuth();
        $staffs = $this->nhanVienModel->all('id ASC');

        $this->view('admin/nhanvien/index', [
            'pageTitle' => 'Quản Lý Nhân Viên & Quản Trị',
            'staffs'    => $staffs
        ]);
    }

    public function nhanvienCreate(): void {
        $this->requireAdminAuth();

        if ($this->isPost()) {
            $post = $this->getPost();
            $rules = [
                'ho_ten'    => ['required' => true, 'min' => 2, 'label' => 'Họ và tên'],
                'tai_khoan' => ['required' => true, 'min' => 3, 'label' => 'Tài khoản'],
                'email'     => ['required' => true, 'email' => true, 'label' => 'Email'],
                'mat_khau'  => ['required' => true, 'min' => 6, 'label' => 'Mật khẩu']
            ];

            do {
                $errors = $this->validateInputs($rules, $post);
                if (!empty($errors)) {
                    set_flash('nv_error', reset($errors), 'danger');
                    redirect('admin/nhan-vien/them');
                    break;
                }

                $data = [
                    'ho_ten'    => trim($post['ho_ten']),
                    'tai_khoan' => trim($post['tai_khoan']),
                    'email'     => trim($post['email']),
                    'sdt'       => trim($post['sdt'] ?? ''),
                    'mat_khau'  => $post['mat_khau'],
                    'vai_tro'   => in_array($post['vai_tro'] ?? '', ['admin', 'nhan_vien']) ? $post['vai_tro'] : 'nhan_vien',
                    'trang_thai'=> 1
                ];

                $this->nhanVienModel->createStaff($data);
                set_flash('nv_success', 'Thêm nhân viên thành công!', 'success');
                redirect('admin/nhan-vien');
                return;
            } while (false);
            return;
        }

        $this->view('admin/nhanvien/create', [
            'pageTitle' => 'Thêm Nhân Viên Mới'
        ]);
    }

    public function nhanvienEdit($id): void {
        $this->requireAdminAuth();
        $staff = $this->nhanVienModel->find($id);
        if (!$staff) {
            set_flash('nv_error', 'Nhân viên không tồn tại.', 'danger');
            redirect('admin/nhan-vien');
            return;
        }

        if ($this->isPost()) {
            $post = $this->getPost();
            $rules = [
                'ho_ten' => ['required' => true, 'min' => 2, 'label' => 'Họ và tên'],
                'email'  => ['required' => true, 'email' => true, 'label' => 'Email']
            ];

            do {
                $errors = $this->validateInputs($rules, $post);
                if (!empty($errors)) {
                    set_flash('nv_error', reset($errors), 'danger');
                    redirect('admin/nhan-vien/sua/' . $id);
                    break;
                }

                $data = [
                    'ho_ten'     => trim($post['ho_ten']),
                    'email'      => trim($post['email']),
                    'sdt'        => trim($post['sdt'] ?? ''),
                    'vai_tro'    => in_array($post['vai_tro'] ?? '', ['admin', 'nhan_vien']) ? $post['vai_tro'] : 'nhan_vien',
                    'trang_thai' => isset($post['trang_thai']) ? (int)$post['trang_thai'] : 1
                ];

                if (!empty($post['mat_khau'])) {
                    $data['mat_khau'] = $post['mat_khau'];
                }

                $this->nhanVienModel->updateStaff((int)$id, $data);
                set_flash('nv_success', 'Cập nhật thông tin nhân viên thành công!', 'success');
                redirect('admin/nhan-vien');
                return;
            } while (false);
            return;
        }

        $this->view('admin/nhanvien/edit', [
            'pageTitle' => 'Chỉnh Sửa Nhân Viên',
            'staff'     => $staff
        ]);
    }

    public function nhanvienDelete($id): void {
        $this->requireAdminAuth();
        if ($id == 1 || (isset($_SESSION['admin']['id']) && $_SESSION['admin']['id'] == $id)) {
            set_flash('nv_error', 'Không thể xóa tài khoản quản trị chính hoặc tài khoản đang đăng nhập.', 'danger');
            redirect('admin/nhan-vien');
            return;
        }

        $this->nhanVienModel->delete((int)$id);
        set_flash('nv_success', 'Xóa nhân viên thành công!', 'success');
        redirect('admin/nhan-vien');
    }

    /**
     * Upload 1 file ảnh
     */
    private function handleUpload(string $inputName, string $subFolder = ''): ?string {
        if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES[$inputName];
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            return null;
        }

        $targetDir = __DIR__ . '/../assets/images/' . ($subFolder ? $subFolder . '/' : '');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $filename = uniqid('img_') . '.' . $ext;
        $targetPath = $targetDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return 'assets/images/' . ($subFolder ? $subFolder . '/' : '') . $filename;
        }
        return null;
    }

    /**
     * Upload nhiều file ảnh phụ
     */
    private function handleMultipleUploads(string $inputName, string $subFolder = ''): array {
        if (!isset($_FILES[$inputName]) || !is_array($_FILES[$inputName]['name'])) {
            return [];
        }

        $uploadedPaths = [];
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $targetDir = __DIR__ . '/../assets/images/' . ($subFolder ? $subFolder . '/' : '');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        foreach ($_FILES[$inputName]['name'] as $key => $name) {
            if ($_FILES[$inputName]['error'][$key] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($ext, $allowedExts)) {
                    $filename = uniqid('sub_') . '.' . $ext;
                    $targetPath = $targetDir . $filename;
                    if (move_uploaded_file($_FILES[$inputName]['tmp_name'][$key], $targetPath)) {
                        $uploadedPaths[] = 'assets/images/' . ($subFolder ? $subFolder . '/' : '') . $filename;
                    }
                }
            }
        }

        return $uploadedPaths;
    }

    // ==========================================
    // MODULE QUẢN LÝ PHIÊN TƯ VẤN AI ASSISTANT
    // ==========================================

    public function tuvanIndex(): void {
        $this->requireAdminAuth();
        $page = max(1, (int)$this->getQuery('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $sessions = $this->tuVanModel->getSessionList($limit, $offset);
        $total = $this->tuVanModel->countSessions();

        $this->view('admin/tuvan/index', [
            'pageTitle' => 'Lịch Sử Tư Vấn Khách Hàng (AI Assistant)',
            'sessions'  => $sessions,
            'total'     => $total,
            'page'      => $page,
            'limit'     => $limit
        ]);
    }

    public function tuvanDetail($id): void {
        $this->requireAdminAuth();
        $session = $this->tuVanModel->find((int)$id);
        if (!$session) {
            set_flash('tuvan_error', 'Phiên tư vấn không tồn tại.', 'danger');
            redirect('admin/tu-van');
            return;
        }

        $messages = $this->tuVanModel->getMessagesBySessionId((int)$id);
        $customer = null;
        if (!empty($session['khach_hang_id'])) {
            $customer = $this->khachHangModel->find((int)$session['khach_hang_id']);
        }

        $this->view('admin/tuvan/detail', [
            'pageTitle' => 'Chi Tiết Hội Thoại Tư Vấn #' . $session['id'],
            'session'   => $session,
            'messages'  => $messages,
            'customer'  => $customer
        ]);
    }

    public function tuvanDelete($id): void {
        $this->requireAdminAuth();
        $this->tuVanModel->delete((int)$id);
        set_flash('tuvan_success', 'Đã xóa phiên tư vấn thành công!', 'success');
        redirect('admin/tu-van');
    }
}
