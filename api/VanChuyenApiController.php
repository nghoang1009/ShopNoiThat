<?php
require_once __DIR__ . '/../core/Controller.php';

class VanChuyenApiController extends Controller {
    private VanChuyenModel $vanChuyenModel;

    public function __construct() {
        $this->vanChuyenModel = $this->model('VanChuyenModel');
    }

    /**
     * GET /api/v1/phuong-thuc-van-chuyen?khu_vuc=...&trong_luong=...&subtotal=...
     * Lấy danh sách phương thức vận chuyển kèm phí đã tính theo tham số
     */
    public function methods(): void {
        $khuVuc = $this->getQuery('khu_vuc', $this->getQuery('tinh_thanh', ''));
        $trongLuong = (float)$this->getQuery('trong_luong', 0);
        $subtotal = (float)$this->getQuery('subtotal', 0);

        $methods = $this->vanChuyenModel->getActiveMethods();

        foreach ($methods as &$m) {
            $calc = $this->vanChuyenModel->calculateShippingFee((int)$m['id'], $subtotal, $khuVuc);
            $calculatedFee = $calc['fee'];

            // Nếu có thêm trọng lượng phụ thu (trên 20kg phụ thu 5.000đ/kg)
            if ($trongLuong > 20 && $calculatedFee > 0) {
                $calculatedFee += ($trongLuong - 20) * 5000;
            }

            $m['phi_tinh_toan'] = $calculatedFee;
            $m['phi_dinh_dang'] = $calculatedFee == 0 ? 'Miễn phí' : format_currency($calculatedFee);
            $m['is_free'] = ($calculatedFee == 0);
        }

        $this->json([
            'success' => true,
            'data'    => $methods,
            'message' => 'Lấy danh sách phương thức vận chuyển thành công.'
        ], 200);
    }

    /**
     * GET /api/v1/phuong-thuc-van-chuyen/{id}
     * Chi tiết 1 phương thức vận chuyển
     */
    public function methodDetail($id): void {
        $method = $this->vanChuyenModel->getMethodById((int)$id);
        if (!$method) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Phương thức vận chuyển không tồn tại.'], 404);
            return;
        }

        $method['phi_dinh_dang'] = format_currency($method['phi_van_chuyen']);
        $this->json([
            'success' => true,
            'data'    => $method,
            'message' => 'Lấy chi tiết phương thức vận chuyển thành công.'
        ], 200);
    }

    /**
     * POST /api/v1/phuong-thuc-van-chuyen
     * Tạo phương thức vận chuyển mới (Admin)
     */
    public function createMethod(): void {
        $this->requireAdminAuth();
        $body = $this->getBody();

        $rules = [
            'ten_pt'            => ['required' => true, 'min' => 2, 'label' => 'Tên phương thức'],
            'ma_pt'             => ['required' => true, 'min' => 2, 'label' => 'Mã phương thức'],
            'phi_van_chuyen'    => ['required' => true, 'numeric' => true, 'label' => 'Phí vận chuyển'],
            'thoi_gian_du_kien' => ['required' => true, 'label' => 'Thời gian dự kiến']
        ];

        do {
            $errors = $this->validateInputs($rules, $body);
            if (!empty($errors)) {
                $this->json(['success' => false, 'data' => null, 'errors' => $errors, 'message' => reset($errors)], 400);
                break;
            }

            $data = [
                'ten_pt'            => trim($body['ten_pt']),
                'ma_pt'             => slugify($body['ma_pt']),
                'phi_van_chuyen'    => (float)$body['phi_van_chuyen'],
                'thoi_gian_du_kien' => trim($body['thoi_gian_du_kien']),
                'mo_ta'             => trim($body['mo_ta'] ?? ''),
                'trang_thai'        => isset($body['trang_thai']) ? (int)$body['trang_thai'] : 1
            ];

            $id = $this->vanChuyenModel->createMethod($data);
            if ($id) {
                $created = $this->vanChuyenModel->getMethodById((int)$id);
                $this->created($created, 'Tạo phương thức vận chuyển thành công.', base_url('api/v1/phuong-thuc-van-chuyen/' . $id));
                return;
            }

            $this->json(['success' => false, 'data' => null, 'message' => 'Không thể tạo phương thức vận chuyển.'], 500);
            return;
        } while (false);
    }

    /**
     * PUT /api/v1/phuong-thuc-van-chuyen/{id}
     * Cập nhật phương thức vận chuyển (Admin)
     */
    public function updateMethod($id): void {
        $this->requireAdminAuth();
        $id = (int)$id;
        $body = $this->getBody();

        do {
            $method = $this->vanChuyenModel->getMethodById($id);
            if (!$method) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Phương thức vận chuyển không tồn tại.'], 404);
                break;
            }

            $rules = [
                'ten_pt'            => ['required' => true, 'min' => 2, 'label' => 'Tên phương thức'],
                'phi_van_chuyen'    => ['required' => true, 'numeric' => true, 'label' => 'Phí vận chuyển'],
                'thoi_gian_du_kien' => ['required' => true, 'label' => 'Thời gian dự kiến']
            ];

            $errors = $this->validateInputs($rules, $body);
            if (!empty($errors)) {
                $this->json(['success' => false, 'data' => null, 'errors' => $errors, 'message' => reset($errors)], 400);
                break;
            }

            $data = [
                'ten_pt'            => trim($body['ten_pt']),
                'phi_van_chuyen'    => (float)$body['phi_van_chuyen'],
                'thoi_gian_du_kien' => trim($body['thoi_gian_du_kien']),
                'mo_ta'             => trim($body['mo_ta'] ?? ''),
                'trang_thai'        => isset($body['trang_thai']) ? (int)$body['trang_thai'] : 1
            ];

            if (!empty($body['ma_pt'])) {
                $data['ma_pt'] = slugify($body['ma_pt']);
            }

            $this->vanChuyenModel->updateMethod($id, $data);
            $updated = $this->vanChuyenModel->getMethodById($id);

            $this->json([
                'success' => true,
                'data'    => $updated,
                'message' => 'Cập nhật phương thức vận chuyển thành công.'
            ], 200);
            return;
        } while (false);
    }

    /**
     * DELETE /api/v1/phuong-thuc-van-chuyen/{id}
     * Xóa phương thức vận chuyển (Admin)
     */
    public function deleteMethod($id): void {
        $this->requireAdminAuth();
        $id = (int)$id;

        do {
            $method = $this->vanChuyenModel->getMethodById($id);
            if (!$method) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Phương thức vận chuyển không tồn tại.'], 404);
                break;
            }

            $deleted = $this->vanChuyenModel->deleteMethod($id);
            if ($deleted) {
                $this->json([
                    'success' => true,
                    'data'    => ['id' => $id],
                    'message' => 'Đã xóa phương thức vận chuyển thành công.'
                ], 200);
                return;
            }

            $this->json(['success' => false, 'data' => null, 'message' => 'Không thể xóa phương thức vận chuyển.'], 500);
            return;
        } while (false);
    }

    /**
     * GET /api/v1/van-chuyen/phi?phuong_thuc_id=1&subtotal=15000000&khu_vuc=TP.HCM
     * Tính phí giao hàng riêng lẻ
     */
    public function calculateFee(): void {
        $methodId = (int)$this->getQuery('phuong_thuc_id', 1);
        $subtotal = (float)$this->getQuery('subtotal', 0);
        $province = (string)$this->getQuery('khu_vuc', $this->getQuery('tinh_thanh', ''));

        $result = $this->vanChuyenModel->calculateShippingFee($methodId, $subtotal, $province);

        $this->json([
            'success' => true,
            'data'    => $result,
            'message' => 'Tính phí vận chuyển thành công.'
        ], 200);
    }

    /**
     * GET /api/v1/van-chuyen/tra-cuu/{code}
     * GET /api/v1/van-chuyen/{code}
     * Tra cứu vận đơn
     */
    public function track($code): void {
        do {
            if (empty($code)) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Vui lòng cung cấp mã vận đơn hoặc mã đơn hàng.'], 400);
                break;
            }

            $tracking = $this->vanChuyenModel->trackByCode(trim($code));
            if (!$tracking) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Không tìm thấy thông tin vận chuyển cho mã này.'], 404);
                break;
            }

            $this->json([
                'success' => true,
                'data'    => $tracking,
                'message' => 'Tra cứu vận chuyển thành công.'
            ], 200);
            return;
        } while (false);
    }
}
