<?php
require_once __DIR__ . '/../core/Controller.php';

class DonHangController extends Controller {
    private DonHangModel $donHangModel;
    private GioHangModel $gioHangModel;
    private VanChuyenModel $vanChuyenModel;
    private ThanhToanModel $thanhToanModel;

    public function __construct() {
        $this->donHangModel = $this->model('DonHangModel');
        $this->gioHangModel = $this->model('GioHangModel');
        $this->vanChuyenModel = $this->model('VanChuyenModel');
        $this->thanhToanModel = $this->model('ThanhToanModel');
    }

    /**
     * Trang thanh toán / Đặt hàng
     */
    public function checkout(): void {
        $userId = $_SESSION['user']['id'] ?? null;
        $sessionId = $userId ? null : $this->getCartSessionId();

        $cart = $this->gioHangModel->getCartItems($userId, $sessionId);
        if (empty($cart['items'])) {
            set_flash('cart_empty', 'Giỏ hàng của bạn đang trống. Vui lòng chọn sản phẩm!', 'warning');
            redirect('gio-hang');
            return;
        }

        $user = $_SESSION['user'] ?? [];
        $shippingMethods = $this->vanChuyenModel->getActiveMethods();
        $paymentMethods = $this->thanhToanModel->getActiveMethods();

        $this->view('client/donhang/checkout', [
            'pageTitle'       => 'Thanh Toán Đơn Hàng',
            'cart'            => $cart,
            'user'            => $user,
            'shippingMethods' => $shippingMethods,
            'paymentMethods'  => $paymentMethods
        ]);
    }

    /**
     * Xử lý đặt hàng qua Form POST (Validate do-while)
     */
    public function store(): void {
        if (!$this->isPost()) {
            redirect('don-hang/thanh-toan');
            return;
        }

        $post = $this->getPost();
        $rules = [
            'ho_ten_nhan'  => ['required' => true, 'min' => 2, 'label' => 'Họ tên người nhận'],
            'sdt_nhan'     => ['required' => true, 'phone' => true, 'label' => 'Số điện thoại nhận hàng'],
            'dia_chi_giao' => ['required' => true, 'min' => 5, 'label' => 'Địa chỉ nhận hàng']
        ];

        do {
            $errors = $this->validateInputs($rules, $post);
            $userId = $_SESSION['user']['id'] ?? null;
            $sessionId = $userId ? null : $this->getCartSessionId();

            $cart = $this->gioHangModel->getCartItems($userId, $sessionId);
            if (empty($cart['items'])) {
                set_flash('order_error', 'Giỏ hàng của bạn đang trống!', 'danger');
                redirect('gio-hang');
                break;
            }

            if (!empty($errors)) {
                set_flash('order_errors', $errors, 'danger');
                set_flash('old_input', $post, 'info');
                redirect('don-hang/thanh-toan');
                break;
            }

            // Tính phí vận chuyển theo phương thức đã chọn
            $shippingMethodId = (int)($post['phuong_thuc_van_chuyen_id'] ?? 1);
            $province = trim($post['tinh_thanh'] ?? '');
            $shippingCalc = $this->vanChuyenModel->calculateShippingFee($shippingMethodId, $cart['total_amount'], $province);
            $shippingFee = (float)$shippingCalc['fee'];

            // Lấy thông tin phương thức thanh toán
            $paymentMethodId = (int)($post['phuong_thuc_thanh_toan_id'] ?? 1);
            $paymentMethod = $this->thanhToanModel->getMethodById($paymentMethodId);
            $paymentCode = $paymentMethod['ma_pt'] ?? 'cod';

            $subtotal = (float)$cart['total_amount'];
            $grandTotal = $subtotal + $shippingFee;

            $orderData = [
                'khach_hang_id'              => $userId,
                'phuong_thuc_van_chuyen_id'  => $shippingMethodId,
                'phuong_thuc_thanh_toan_id'  => $paymentMethodId,
                'ho_ten_nhan'                => trim($post['ho_ten_nhan']),
                'sdt_nhan'                   => trim($post['sdt_nhan']),
                'dia_chi_giao'               => trim($post['dia_chi_giao']),
                'tinh_thanh'                 => $province,
                'ghi_chu'                    => trim($post['ghi_chu'] ?? ''),
                'tien_hang'                  => $subtotal,
                'phi_van_chuyen'             => $shippingFee,
                'tong_tien'                  => $grandTotal,
                'phuong_thuc_thanh_toan'     => $paymentCode,
                'trang_thai_thanh_toan'      => 'chua_thanh_toan',
                'trang_thai'                 => 'cho_xu_ly'
            ];

            $result = $this->donHangModel->createOrderFromCart($orderData, $cart['items']);
            if ($result['success']) {
                $this->gioHangModel->clearCart($userId, $sessionId);

                // Nếu là thanh toán online (chuyển khoản VietQR, MoMo, VNPAY) -> chuyển sang trang thanh toán riêng
                if (in_array($paymentCode, ['banking', 'momo', 'vnpay', 'zalopay'])) {
                    redirect('don-hang/thanh-toan-online/' . $result['ma_don_hang']);
                    return;
                }

                // COD -> đến trang hoàn tất
                redirect('don-hang/thanh-cong?ma=' . $result['ma_don_hang']);
                return;
            } else {
                set_flash('order_error', $result['message'] ?? 'Đặt hàng thất bại.', 'danger');
                redirect('don-hang/thanh-toan');
                return;
            }
        } while (false);
    }

    /**
     * Trang thanh toán riêng trực tuyến sau khi đặt hàng (Hiển thị QR & Giả lập thanh toán)
     */
    public function payment($orderCode): void {
        $order = $this->donHangModel->getOrderDetail($orderCode);
        if (!$order) {
            set_flash('order_error', 'Không tìm thấy đơn hàng cần thanh toán.', 'danger');
            redirect('san-pham');
            return;
        }

        $paymentInfo = $this->thanhToanModel->getByOrderId((int)$order['id']);
        $paymentMethods = $this->thanhToanModel->getActiveMethods();

        $this->view('client/donhang/payment', [
            'pageTitle'      => 'Thanh Toán Đơn Hàng #' . $order['ma_don_hang'],
            'order'          => $order,
            'paymentInfo'    => $paymentInfo,
            'paymentMethods' => $paymentMethods
        ]);
    }

    /**
     * Trang theo dõi đơn hàng & tiến độ vận chuyển (Tracking)
     */
    public function tracking($code = null): void {
        if (!$code) {
            $code = $this->getQuery('ma', '');
        }

        $tracking = null;
        if (!empty($code)) {
            $tracking = $this->vanChuyenModel->trackByCode(trim($code));
        }

        $this->view('client/donhang/tracking', [
            'pageTitle' => 'Tra Cứu & Theo Dõi Đơn Hàng',
            'code'      => $code,
            'tracking'  => $tracking
        ]);
    }

    /**
     * Trang thông báo đặt hàng thành công
     */
    public function success(): void {
        $orderCode = $this->getQuery('ma', '');
        $order = null;
        if (!empty($orderCode)) {
            $order = $this->donHangModel->getOrderDetail($orderCode);
        }

        $this->view('client/donhang/success', [
            'pageTitle' => 'Đặt Hàng Thành Công',
            'order'     => $order
        ]);
    }

    /**
     * Lịch sử đơn hàng của tài khoản đang đăng nhập
     */
    public function history(): void {
        $user = $this->requireClientAuth();

        $orders = $this->donHangModel->getOrders([
            'khach_hang_id' => $user['id']
        ]);

        $this->view('client/donhang/history', [
            'pageTitle' => 'Lịch Sử Đơn Hàng Của Tôi',
            'orders'    => $orders,
            'user'      => $user
        ]);
    }

    /**
     * Xem chi tiết đơn hàng
     */
    public function detail($idOrCode): void {
        $order = $this->donHangModel->getOrderDetail($idOrCode);
        if (!$order) {
            set_flash('order_error', 'Không tìm thấy thông tin đơn hàng.', 'danger');
            redirect('don-hang/lich-su');
            return;
        }

        // Bảo vệ quyền riêng tư nếu đang đăng nhập tài khoản khác
        if (isset($_SESSION['user']['id']) && $order['khach_hang_id'] && $order['khach_hang_id'] != $_SESSION['user']['id']) {
            set_flash('order_error', 'Bạn không có quyền truy cập đơn hàng này.', 'danger');
            redirect('don-hang/lich-su');
            return;
        }

        $this->view('client/donhang/detail', [
            'pageTitle' => 'Chi Tiết Đơn Hàng #' . $order['ma_don_hang'],
            'order'     => $order
        ]);
    }
}
