<?php
require_once __DIR__ . '/../core/Controller.php';

class KhachHangApiController extends Controller {
    private KhachHangModel $khachHangModel;

    public function __construct() {
        $this->khachHangModel = $this->model('KhachHangModel');
    }

    /**
     * GET /api/v1/khach-hang
     * Danh sách khách hàng (Admin)
     */
    public function index(): void {
        $this->requireAdminAuth();

        $page = max(1, (int)$this->getQuery('page', 1));
        $limit = max(1, min(100, (int)$this->getQuery('limit', 20)));
        $search = $this->getQuery('search');

        $customers = $this->khachHangModel->getCustomersWithOrderStats($search);
        $total = count($customers);

        // Phân trang trên mảng kết quả
        $pagedItems = array_slice($customers, ($page - 1) * $limit, $limit);
        foreach ($pagedItems as &$c) {
            unset($c['mat_khau']);
            $c['tong_chi_tieu_dinh_dang'] = format_currency($c['tong_chi_tieu'] ?? 0);
        }

        $this->paginate($pagedItems, $total, $page, $limit, 'Lấy danh sách khách hàng thành công.');
    }

    /**
     * GET /api/v1/khach-hang/{id}
     * Chi tiết một khách hàng
     */
    public function detail($id): void {
        $this->requireAdminAuth();
        $id = (int)$id;

        $customer = $this->khachHangModel->find($id);
        if (!$customer) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Không tìm thấy khách hàng.'], 404);
            return;
        }

        unset($customer['mat_khau']);
        $this->json([
            'success' => true,
            'data'    => $customer,
            'message' => 'Lấy chi tiết khách hàng thành công.'
        ], 200);
    }

    /**
     * PATCH /api/v1/khach-hang/{id}
     * Cập nhật trạng thái kích hoạt/khóa tài khoản
     */
    public function updateStatus($id): void {
        $this->requireAdminAuth();
        $id = (int)$id;
        $body = $this->getBody();

        do {
            $customer = $this->khachHangModel->find($id);
            if (!$customer) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Khách hàng không tồn tại.'], 404);
                break;
            }

            if (!isset($body['trang_thai'])) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Trường trang_thai là bắt buộc.'], 400);
                break;
            }

            $newStatus = (int)$body['trang_thai'];
            $this->khachHangModel->update($id, ['trang_thai' => $newStatus]);
            $updated = $this->khachHangModel->find($id);
            unset($updated['mat_khau']);

            $this->json([
                'success' => true,
                'data'    => $updated,
                'message' => 'Cập nhật trạng thái khách hàng thành công.'
            ], 200);
            return;
        } while (false);
    }
}
