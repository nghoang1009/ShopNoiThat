<?php
require_once __DIR__ . '/../core/Controller.php';

class DonHangApiController extends Controller {
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
     * GET /api/v1/don-hang
     * Danh sách đơn hàng (khách xem của mình, admin xem tất cả)
     */
    public function index(): void {
        $page = max(1, (int)$this->getQuery('page', 1));
        $limit = max(1, min(100, (int)$this->getQuery('limit', 20)));

        $filters = [
            'trang_thai'            => $this->getQuery('trang_thai'),
            'trang_thai_thanh_toan' => $this->getQuery('trang_thai_thanh_toan'),
            'search'                => $this->getQuery('search'),
            'limit'                 => $limit,
            'offset'                => ($page - 1) * $limit
        ];

        // Nếu là khách hàng (không có admin session/token), ép lọc theo khach_hang_id
        $isAdmin = false;
        $admin = $_SESSION['admin'] ?? null;
        $token = $this->getBearerToken();
        if ($admin || ($token && str_contains(base64_decode($token), 'admin'))) {
            $isAdmin = true;
        }

        if (!$isAdmin) {
            $user = $this->requireClientAuth();
            $filters['khach_hang_id'] = $user['id'];
        }

        $orders = $this->donHangModel->getOrders($filters);
        $total = $this->donHangModel->countOrders($filters);

        foreach ($orders as &$o) {
            $o['tong_tien_dinh_dang'] = format_currency($o['tong_tien']);
            $o['phi_van_chuyen_dinh_dang'] = format_currency($o['phi_van_chuyen'] ?? 0);
            $o['tien_hang_dinh_dang'] = format_currency($o['tien_hang'] ?? $o['tong_tien']);
        }

        $this->paginate($orders, $total, $page, $limit, 'Lấy danh sách đơn hàng thành công.');
    }

    /**
     * POST /api/v1/don-hang
     * Tạo đơn hàng mới từ giỏ hàng (201 Created)
     */
    public function create(): void {
        $body = $this->getBody();

        $rules = [
            'ho_ten_nhan'  => ['required' => true, 'min' => 2, 'max' => 100, 'label' => 'Họ tên người nhận'],
            'sdt_nhan'     => ['required' => true, 'phone' => true, 'label' => 'Số điện thoại nhận hàng'],
            'dia_chi_giao' => ['required' => true, 'min' => 5, 'label' => 'Địa chỉ nhận hàng']
        ];

        do {
            $errors = $this->validateInputs($rules, $body);
            if (!empty($errors)) {
                $this->json([
                    'success' => false,
                    'data'    => null,
                    'errors'  => $errors,
                    'message' => reset($errors)
                ], 400);
                break;
            }

            $userId = $_SESSION['user']['id'] ?? null;
            $sessionId = $userId ? null : $this->getCartSessionId();

            $cart = $this->gioHangModel->getCartItems($userId, $sessionId);
            if (empty($cart['items'])) {
                $this->json([
                    'success' => false,
                    'data'    => null,
                    'message' => 'Giỏ hàng của bạn đang trống, không thể đặt hàng.'
                ], 400);
                break;
            }

            // Tính phí ship
            $shippingMethodId = (int)($body['phuong_thuc_van_chuyen_id'] ?? 1);
            $province = trim($body['tinh_thanh'] ?? '');
            $shippingCalc = $this->vanChuyenModel->calculateShippingFee($shippingMethodId, $cart['total_amount'], $province);
            $shippingFee = (float)$shippingCalc['fee'];

            // Phương thức thanh toán
            $paymentMethodId = (int)($body['phuong_thuc_thanh_toan_id'] ?? 1);
            $paymentMethod = $this->thanhToanModel->getMethodById($paymentMethodId);
            $paymentCode = $paymentMethod['ma_pt'] ?? ($body['phuong_thuc_thanh_toan'] ?? 'cod');

            $subtotal = (float)$cart['total_amount'];
            $grandTotal = $subtotal + $shippingFee;

            $orderData = [
                'khach_hang_id'             => $userId,
                'phuong_thuc_van_chuyen_id' => $shippingMethodId,
                'phuong_thuc_thanh_toan_id' => $paymentMethodId,
                'ho_ten_nhan'               => trim($body['ho_ten_nhan']),
                'sdt_nhan'                  => trim($body['sdt_nhan']),
                'dia_chi_giao'              => trim($body['dia_chi_giao']),
                'tinh_thanh'                => $province,
                'ghi_chu'                   => trim($body['ghi_chu'] ?? ''),
                'tien_hang'                 => $subtotal,
                'phi_van_chuyen'            => $shippingFee,
                'tong_tien'                 => $grandTotal,
                'phuong_thuc_thanh_toan'    => $paymentCode,
                'trang_thai_thanh_toan'     => 'chua_thanh_toan',
                'trang_thai'                => 'cho_xu_ly'
            ];

            $createResult = $this->donHangModel->createOrderFromCart($orderData, $cart['items']);
            if ($createResult['success']) {
                $this->gioHangModel->clearCart($userId, $sessionId);

                $createdOrder = $this->donHangModel->getOrderDetail($createResult['order_id']);

                $this->created([
                    'order'               => $createdOrder,
                    'order_id'            => $createResult['order_id'],
                    'ma_don_hang'         => $createResult['ma_don_hang'],
                    'tong_tien'           => $orderData['tong_tien'],
                    'tong_tien_dinh_dang' => format_currency($orderData['tong_tien']),
                    'redirect_url'        => base_url('don-hang/thanh-cong?ma=' . $createResult['ma_don_hang'])
                ], 'Đặt hàng thành công!', base_url('api/v1/don-hang/' . $createResult['order_id']));
                return;
            }

            $this->json([
                'success' => false,
                'data'    => null,
                'message' => $createResult['message'] ?? 'Không thể tạo đơn hàng.'
            ], 500);
            return;
        } while (false);
    }

    /**
     * GET /api/v1/don-hang/{id}
     * Chi tiết đơn hàng
     */
    public function detail($id): void {
        $order = $this->donHangModel->getOrderDetail($id);
        if (!$order) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Không tìm thấy đơn hàng.'], 404);
            return;
        }

        // Kiểm tra quyền: nếu là user thường thì chỉ được xem đơn của mình
        if (isset($_SESSION['user']['id']) && !isset($_SESSION['admin'])) {
            if ($order['khach_hang_id'] && $order['khach_hang_id'] != $_SESSION['user']['id']) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Bạn không có quyền xem đơn hàng này.'], 403);
                return;
            }
        }

        $order['tong_tien_dinh_dang'] = format_currency($order['tong_tien']);
        $order['tien_hang_dinh_dang'] = format_currency($order['tien_hang'] ?? $order['tong_tien']);
        $order['phi_van_chuyen_dinh_dang'] = format_currency($order['phi_van_chuyen'] ?? 0);

        foreach ($order['items'] as &$item) {
            $item['don_gia_dinh_dang'] = format_currency($item['don_gia']);
            $item['thanh_tien_dinh_dang'] = format_currency($item['thanh_tien']);
            $item['hinh_anh_url'] = !empty($item['hinh_anh']) ? asset_url($item['hinh_anh']) : asset_url('assets/images/default.jpg');
        }

        $this->json([
            'success' => true,
            'data'    => $order,
            'message' => 'Lấy chi tiết đơn hàng thành công.'
        ], 200);
    }

    /**
     * PATCH /api/v1/don-hang/{id}
     * Admin cập nhật trạng thái đơn hàng (cho_xu_ly, dang_giao, hoan_tat, da_huy)
     */
    public function updateStatus($id): void {
        $this->requireAdminAuth();

        $body = $this->getBody();
        $status = $body['trang_thai'] ?? '';
        $paymentStatus = $body['trang_thai_thanh_toan'] ?? null;

        $validStatuses = ['cho_xu_ly', 'dang_giao', 'hoan_tat', 'da_huy'];
        $validPaymentStatuses = ['chua_thanh_toan', 'da_thanh_toan'];

        do {
            if (!in_array($status, $validStatuses)) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Trạng thái đơn hàng không hợp lệ.'], 400);
                break;
            }

            if ($paymentStatus !== null && !in_array($paymentStatus, $validPaymentStatuses)) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Trạng thái thanh toán không hợp lệ.'], 400);
                break;
            }

            $updated = $this->donHangModel->updateStatus((int)$id, $status, $paymentStatus);
            if ($updated) {
                $order = $this->donHangModel->find((int)$id);
                $this->json([
                    'success' => true,
                    'data'    => $order,
                    'message' => 'Cập nhật trạng thái đơn hàng thành công.'
                ], 200);
                return;
            }

            $this->json(['success' => false, 'data' => null, 'message' => 'Không tìm thấy đơn hàng hoặc cập nhật thất bại.'], 404);
            return;
        } while (false);
    }

    /**
     * PUT /api/v1/don-hang/{id}
     * Cập nhật thông tin giao nhận đơn hàng
     */
    public function update($id): void {
        $this->requireAdminAuth();
        $id = (int)$id;
        $body = $this->getBody();

        do {
            if ($id <= 0) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Mã đơn hàng không hợp lệ.'], 400);
                break;
            }

            $order = $this->donHangModel->find($id);
            if (!$order) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Đơn hàng không tồn tại.'], 404);
                break;
            }

            $dataToUpdate = [];
            $allowedFields = ['ho_ten_nhan', 'sdt_nhan', 'dia_chi_giao', 'tinh_thanh', 'ghi_chu', 'trang_thai', 'trang_thai_thanh_toan'];
            foreach ($allowedFields as $field) {
                if (isset($body[$field])) {
                    $dataToUpdate[$field] = trim($body[$field]);
                }
            }

            $this->donHangModel->update($id, $dataToUpdate);
            $updated = $this->donHangModel->find($id);

            $this->json([
                'success' => true,
                'data'    => $updated,
                'message' => 'Cập nhật đơn hàng thành công.'
            ], 200);
            return;
        } while (false);
    }

    /**
     * GET /api/v1/don-hang/{id}/van-chuyen
     * Lấy thông tin vận chuyển của đơn hàng
     */
    public function getShipping($id): void {
        $order = $this->donHangModel->find((int)$id);
        if (!$order) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Không tìm thấy đơn hàng.'], 404);
            return;
        }

        $tracking = $this->vanChuyenModel->getByOrderId((int)$order['id']);
        if (!$tracking) {
            $tracking = $this->vanChuyenModel->trackByCode($order['ma_don_hang']);
        }

        $this->json([
            'success' => true,
            'data'    => $tracking,
            'message' => 'Lấy thông tin vận chuyển thành công.'
        ], 200);
    }

    /**
     * PUT /api/v1/don-hang/{id}/van-chuyen
     * PATCH /api/v1/don-hang/{id}/van-chuyen
     * Cập nhật thông tin/trạng thái vận chuyển của đơn hàng (Admin)
     */
    public function updateShipping($id): void {
        $this->requireAdminAuth();
        $id = (int)$id;
        $body = $this->getBody();

        $status = $body['trang_thai_giao_hang'] ?? '';
        $carrier = $body['don_vi_van_chuyen'] ?? null;
        $trackingCode = $body['ma_van_don'] ?? null;
        $notes = $body['ghi_chu'] ?? null;

        $validStatuses = ['cho_lay_hang', 'dang_van_chuyen', 'dang_giao', 'da_giao', 'giao_that_bai', 'chuyen_hoan'];

        do {
            if (empty($status) || !in_array($status, $validStatuses)) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Trạng thái giao hàng không hợp lệ.'], 400);
                break;
            }

            $updated = $this->vanChuyenModel->updateShippingStatus($id, $status, $carrier, $trackingCode, $notes);
            if ($updated) {
                $this->json([
                    'success' => true,
                    'message' => 'Cập nhật trạng thái vận chuyển và đồng bộ đơn hàng thành công.'
                ], 200);
                return;
            }

            $this->json(['success' => false, 'data' => null, 'message' => 'Cập nhật vận chuyển thất bại.'], 500);
            return;
        } while (false);
    }

    /**
     * GET /api/v1/don-hang/{id}/thanh-toan
     * Lấy thông tin thanh toán của đơn hàng
     */
    public function getPayment($id): void {
        $order = $this->donHangModel->find((int)$id);
        if (!$order) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Không tìm thấy đơn hàng.'], 404);
            return;
        }

        $payment = $this->thanhToanModel->getByOrderId((int)$id);
        if ($payment) {
            $payment['so_tien_dinh_dang'] = format_currency($payment['so_tien']);
        }

        $this->json([
            'success' => true,
            'data'    => $payment,
            'message' => 'Lấy thông tin thanh toán thành công.'
        ], 200);
    }

    /**
     * POST /api/v1/don-hang/{id}/thanh-toan
     * Khởi tạo giao dịch thanh toán cho đơn hàng
     */
    public function createPayment($id): void {
        $id = (int)$id;
        $order = $this->donHangModel->find($id);
        if (!$order) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Đơn hàng không tồn tại.'], 404);
            return;
        }

        $body = $this->getBody();
        $methodId = !empty($body['phuong_thuc_thanh_toan_id']) ? (int)$body['phuong_thuc_thanh_toan_id'] : ($order['phuong_thuc_thanh_toan_id'] ?? 1);
        $amount = !empty($body['so_tien']) ? (float)$body['so_tien'] : (float)$order['tong_tien'];
        $notes = $body['ghi_chu'] ?? ('Thanh toán cho đơn hàng #' . $order['ma_don_hang']);

        $paymentId = $this->thanhToanModel->createPaymentRecord($id, $methodId, $amount, 'cho_thanh_toan', $notes);

        $this->created([
            'payment_id'   => $paymentId,
            'order_id'     => $id,
            'so_tien'      => $amount,
            'so_tien_vnd'  => format_currency($amount)
        ], 'Khởi tạo giao dịch thanh toán thành công.');
    }

    /**
     * PATCH /api/v1/don-hang/{id}/thanh-toan
     * Xác nhận/cập nhật trạng thái thanh toán cho đơn hàng (Admin/Callback)
     */
    public function confirmPayment($id): void {
        $id = (int)$id;
        $order = $this->donHangModel->find($id);
        if (!$order) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Đơn hàng không tồn tại.'], 404);
            return;
        }

        $payment = $this->thanhToanModel->getByOrderId($id);
        if (!$payment) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Chưa có bản ghi thanh toán cho đơn này.'], 404);
            return;
        }

        $body = $this->getBody();
        $transCode = $body['ma_giao_dich'] ?? null;
        $notes = $body['ghi_chu'] ?? 'Xác nhận thanh toán thủ công';

        $confirmed = $this->thanhToanModel->confirmPayment((int)$payment['id'], $transCode, $notes, false);
        if ($confirmed) {
            $this->json([
                'success' => true,
                'message' => 'Xác nhận thanh toán và cập nhật đơn hàng thành công.'
            ], 200);
            return;
        }

        $this->json(['success' => false, 'data' => null, 'message' => 'Không thể xác nhận thanh toán.'], 500);
    }
}
