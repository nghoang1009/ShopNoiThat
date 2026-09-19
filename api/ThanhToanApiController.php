<?php
require_once __DIR__ . '/../core/Controller.php';

class ThanhToanApiController extends Controller {
    private ThanhToanModel $thanhToanModel;
    private DonHangModel $donHangModel;

    public function __construct() {
        $this->thanhToanModel = $this->model('ThanhToanModel');
        $this->donHangModel = $this->model('DonHangModel');
    }

    /**
     * GET /api/v1/thanh-toan?tu_ngay=...&den_ngay=...&trang_thai=...&phuong_thuc_id=...
     * Admin tra cứu & đối soát toàn bộ giao dịch thanh toán
     */
    public function index(): void {
        $this->requireAdminAuth();

        $page = max(1, (int)$this->getQuery('page', 1));
        $limit = max(1, min(100, (int)$this->getQuery('limit', 20)));

        $filters = [
            'trang_thai'     => $this->getQuery('trang_thai'),
            'phuong_thuc_id' => $this->getQuery('phuong_thuc_id'),
            'keyword'        => $this->getQuery('keyword'),
            'tu_ngay'        => $this->getQuery('tu_ngay'),
            'den_ngay'       => $this->getQuery('den_ngay')
        ];

        $transactions = $this->thanhToanModel->getTransactionList($filters);
        $total = count($transactions);

        $pagedItems = array_slice($transactions, ($page - 1) * $limit, $limit);
        foreach ($pagedItems as &$t) {
            $t['so_tien_dinh_dang'] = format_currency($t['so_tien']);
            $t['tong_tien_don_dinh_dang'] = format_currency($t['tong_tien_don']);
        }

        $this->paginate($pagedItems, $total, $page, $limit, 'Lấy danh sách giao dịch thanh toán thành công.');
    }

    /**
     * GET /api/v1/phuong-thuc-thanh-toan
     * Lấy danh sách các phương thức thanh toán đang hoạt động
     */
    public function methods(): void {
        $methods = $this->thanhToanModel->getActiveMethods();
        foreach ($methods as &$m) {
            $m['hinh_anh_url'] = !empty($m['hinh_anh']) ? asset_url($m['hinh_anh']) : null;
        }

        $this->json([
            'success' => true,
            'data'    => $methods,
            'message' => 'Lấy danh sách phương thức thanh toán thành công.'
        ], 200);
    }

    /**
     * POST /api/v1/phuong-thuc-thanh-toan
     * Thêm phương thức thanh toán mới (Admin)
     */
    public function createMethod(): void {
        $this->requireAdminAuth();
        $body = $this->getBody();

        $rules = [
            'ten_pt' => ['required' => true, 'min' => 2, 'label' => 'Tên phương thức'],
            'ma_pt'  => ['required' => true, 'min' => 2, 'label' => 'Mã phương thức']
        ];

        do {
            $errors = $this->validateInputs($rules, $body);
            if (!empty($errors)) {
                $this->json(['success' => false, 'data' => null, 'errors' => $errors, 'message' => reset($errors)], 400);
                break;
            }

            $data = [
                'ten_pt'     => trim($body['ten_pt']),
                'ma_pt'      => slugify($body['ma_pt']),
                'mo_ta'      => trim($body['mo_ta'] ?? ''),
                'huong_dan'  => trim($body['huong_dan'] ?? ''),
                'hinh_anh'   => trim($body['hinh_anh'] ?? ''),
                'trang_thai' => isset($body['trang_thai']) ? (int)$body['trang_thai'] : 1
            ];

            $id = $this->thanhToanModel->createMethod($data);
            if ($id) {
                $created = $this->thanhToanModel->getMethodById((int)$id);
                $this->created($created, 'Thêm phương thức thanh toán thành công.', base_url('api/v1/phuong-thuc-thanh-toan/' . $id));
                return;
            }

            $this->json(['success' => false, 'data' => null, 'message' => 'Không thể thêm phương thức thanh toán.'], 500);
            return;
        } while (false);
    }

    /**
     * PATCH /api/v1/phuong-thuc-thanh-toan/{id}
     * Bật/tắt hoặc cập nhật phương thức thanh toán (Admin)
     */
    public function patchMethod($id): void {
        $this->requireAdminAuth();
        $id = (int)$id;
        $body = $this->getBody();

        do {
            $method = $this->thanhToanModel->getMethodById($id);
            if (!$method) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Phương thức thanh toán không tồn tại.'], 404);
                break;
            }

            $dataToUpdate = [];
            if (isset($body['trang_thai'])) {
                $dataToUpdate['trang_thai'] = (int)$body['trang_thai'];
            }
            if (isset($body['ten_pt'])) {
                $dataToUpdate['ten_pt'] = trim($body['ten_pt']);
            }
            if (isset($body['mo_ta'])) {
                $dataToUpdate['mo_ta'] = trim($body['mo_ta']);
            }
            if (isset($body['huong_dan'])) {
                $dataToUpdate['huong_dan'] = trim($body['huong_dan']);
            }
            if (isset($body['hinh_anh'])) {
                $dataToUpdate['hinh_anh'] = trim($body['hinh_anh']);
            }

            if (empty($dataToUpdate)) {
                // Nếu không truyền trường nào thì mặc định toggle trạng thái
                $this->thanhToanModel->toggleMethodStatus($id);
            } else {
                $this->thanhToanModel->updateMethod($id, $dataToUpdate);
            }

            $updated = $this->thanhToanModel->getMethodById($id);
            $this->json([
                'success' => true,
                'data'    => $updated,
                'message' => 'Cập nhật phương thức thanh toán thành công.'
            ], 200);
            return;
        } while (false);
    }

    /**
     * POST /api/v1/don-hang/{id}/thanh-toan
     * Tạo giao dịch thanh toán cho đơn hàng (trả 201 kèm mã QR và link thanh toán)
     */
    public function createOrderPayment($orderId): void {
        $orderId = (int)$orderId;
        $order = $this->donHangModel->find($orderId);
        if (!$order) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Đơn hàng không tồn tại.'], 404);
            return;
        }

        $body = $this->getBody();
        $methodId = !empty($body['phuong_thuc_thanh_toan_id']) ? (int)$body['phuong_thuc_thanh_toan_id'] : ($order['phuong_thuc_thanh_toan_id'] ?? 1);
        $amount = !empty($body['so_tien']) ? (float)$body['so_tien'] : (float)$order['tong_tien'];
        $notes = $body['ghi_chu'] ?? ('Khởi tạo thanh toán cho đơn hàng #' . $order['ma_don_hang']);

        $method = $this->thanhToanModel->getMethodById($methodId);
        $methodCode = $method['ma_pt'] ?? 'cod';

        $paymentId = $this->thanhToanModel->createPaymentRecord($orderId, $methodId, $amount, 'cho_thanh_toan', $notes);

        // Sinh link QR code VietQR nếu là banking
        $qrUrl = null;
        if ($methodCode === 'banking') {
            $qrUrl = "https://img.vietqr.io/image/970436-102874659999-compact2.png?amount=" . (int)$amount . "&addInfo=" . urlencode($order['ma_don_hang']) . "&accountName=SHOP%20NOI%20THAT%20LUXURY";
        }

        $this->created([
            'payment_id'   => $paymentId,
            'order_id'     => $orderId,
            'ma_don_hang'  => $order['ma_don_hang'],
            'so_tien'      => $amount,
            'so_tien_vnd'  => format_currency($amount),
            'phuong_thuc'  => $method['ten_pt'] ?? 'Thanh toán',
            'qr_url'       => $qrUrl,
            'payment_url'  => base_url('don-hang/thanh-toan-online/' . $order['ma_don_hang'])
        ], 'Khởi tạo giao dịch thanh toán thành công.');
    }

    /**
     * GET /api/v1/don-hang/{id}/thanh-toan
     * Xem trạng thái thanh toán của đơn hàng
     */
    public function getOrderPayment($orderId): void {
        $orderId = (int)$orderId;
        $order = $this->donHangModel->find($orderId);
        if (!$order) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Đơn hàng không tồn tại.'], 404);
            return;
        }

        $payment = $this->thanhToanModel->getByOrderId($orderId);
        if ($payment) {
            $payment['so_tien_dinh_dang'] = format_currency($payment['so_tien']);
        }

        $this->json([
            'success' => true,
            'data'    => $payment,
            'message' => 'Lấy thông tin thanh toán đơn hàng thành công.'
        ], 200);
    }

    /**
     * PATCH /api/v1/don-hang/{id}/thanh-toan
     * Cập nhật trạng thái thanh toán (Callback/Xác nhận thành công)
     */
    public function patchOrderPayment($orderId): void {
        $orderId = (int)$orderId;
        $order = $this->donHangModel->find($orderId);
        if (!$order) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Đơn hàng không tồn tại.'], 404);
            return;
        }

        $payment = $this->thanhToanModel->getByOrderId($orderId);
        $body = $this->getBody();
        $transCode = $body['ma_giao_dich'] ?? null;
        $notes = $body['ghi_chu'] ?? 'Xác nhận thanh toán qua API';

        $paymentId = $payment ? (int)$payment['id'] : $this->thanhToanModel->createPaymentRecord($orderId, $order['phuong_thuc_thanh_toan_id'], $order['tong_tien'], 'cho_thanh_toan', 'Tạo tự động khi xác nhận');

        $confirmed = $this->thanhToanModel->confirmPayment($paymentId, $transCode, $notes, false);
        if ($confirmed) {
            $this->json([
                'success' => true,
                'data'    => [
                    'order_id'              => $orderId,
                    'ma_don_hang'           => $order['ma_don_hang'],
                    'trang_thai_thanh_toan' => 'da_thanh_toan',
                    'ma_giao_dich'          => $transCode
                ],
                'message' => 'Cập nhật trạng thái thanh toán thành công.'
            ], 200);
            return;
        }

        $this->json(['success' => false, 'data' => null, 'message' => 'Cập nhật thanh toán thất bại.'], 500);
    }

    /**
     * POST /api/v1/thanh-toan/simulate
     * Giả lập thanh toán trực tuyến thành công
     */
    public function simulate(): void {
        $body = $this->getBody();
        $orderCode = $body['ma_don_hang'] ?? '';
        $methodCode = $body['phuong_thuc'] ?? 'banking';

        do {
            if (empty($orderCode)) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Vui lòng cung cấp mã đơn hàng.'], 400);
                break;
            }

            $result = $this->thanhToanModel->simulateOnlinePayment(trim($orderCode), trim($methodCode));
            if ($result['success']) {
                $this->json([
                    'success' => true,
                    'data'    => $result,
                    'message' => $result['message']
                ], 200);
                return;
            }

            $this->json([
                'success' => false,
                'data'    => null,
                'message' => $result['message'] ?? 'Thanh toán giả lập thất bại.'
            ], 400);
            return;
        } while (false);
    }
}
