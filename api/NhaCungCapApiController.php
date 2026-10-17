<?php
require_once __DIR__ . '/../core/Controller.php';

class NhaCungCapApiController extends Controller {
    private NhaCungCapModel $nhaCungCapModel;

    public function __construct() {
        $this->nhaCungCapModel = $this->model('NhaCungCapModel');
    }

    /**
     * Body JSON thô (không escape HTML) - dữ liệu được escape khi hiển thị
     */
    private function rawBody(): array {
        $json = json_decode(file_get_contents('php://input'), true);
        return is_array($json) ? $json : $_POST;
    }

    /**
     * GET /api/v1/nha-cung-cap?search=
     */
    public function index(): void {
        $this->requireAdminAuth();
        $search = trim((string)($_GET['search'] ?? ''));

        $this->json([
            'success' => true,
            'data'    => $this->nhaCungCapModel->listWithStats($search),
            'message' => 'Lấy danh sách nhà cung cấp thành công.'
        ], 200);
    }

    /**
     * GET /api/v1/nha-cung-cap/{id}
     */
    public function detail($id): void {
        $this->requireAdminAuth();
        $supplier = $this->nhaCungCapModel->find((int)$id);
        if (!$supplier) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Nhà cung cấp không tồn tại.'], 404);
            return;
        }
        $supplier['so_san_pham'] = $this->nhaCungCapModel->countProducts((int)$id);

        $this->json(['success' => true, 'data' => $supplier, 'message' => 'Lấy chi tiết nhà cung cấp thành công.'], 200);
    }

    /**
     * POST /api/v1/nha-cung-cap
     */
    public function create(): void {
        $this->requireAdminAuth();
        $body = $this->rawBody();

        $errors = $this->nhaCungCapModel->validate($body);
        if (!empty($errors)) {
            $this->json(['success' => false, 'data' => null, 'errors' => $errors, 'message' => reset($errors)], 422);
            return;
        }

        $id = (int)$this->nhaCungCapModel->create($this->nhaCungCapModel->normalize($body));
        $this->created($this->nhaCungCapModel->find($id), 'Thêm nhà cung cấp thành công.', base_url('api/v1/nha-cung-cap/' . $id));
    }

    /**
     * PUT /api/v1/nha-cung-cap/{id}
     */
    public function update($id): void {
        $this->requireAdminAuth();
        $id = (int)$id;
        $supplier = $this->nhaCungCapModel->find($id);
        if (!$supplier) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Nhà cung cấp không tồn tại.'], 404);
            return;
        }

        // Cho phép gửi một phần trường: trường thiếu giữ nguyên giá trị cũ
        $body = array_merge($supplier, $this->rawBody());
        $errors = $this->nhaCungCapModel->validate($body, $id);
        if (!empty($errors)) {
            $this->json(['success' => false, 'data' => null, 'errors' => $errors, 'message' => reset($errors)], 422);
            return;
        }

        $this->nhaCungCapModel->update($id, $this->nhaCungCapModel->normalize($body));
        $this->json(['success' => true, 'data' => $this->nhaCungCapModel->find($id), 'message' => 'Cập nhật nhà cung cấp thành công.'], 200);
    }

    /**
     * DELETE /api/v1/nha-cung-cap/{id} (chỉ admin; từ chối nếu còn sản phẩm liên kết)
     */
    public function delete($id): void {
        $this->requireAdminRole();
        $id = (int)$id;
        if (!$this->nhaCungCapModel->find($id)) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Nhà cung cấp không tồn tại.'], 404);
            return;
        }

        $count = $this->nhaCungCapModel->countProducts($id);
        if ($count > 0) {
            $this->json(['success' => false, 'data' => null, 'message' => "Không thể xóa: còn {$count} sản phẩm thuộc nhà cung cấp này."], 409);
            return;
        }

        $this->nhaCungCapModel->delete($id);
        $this->json(['success' => true, 'data' => ['id' => $id], 'message' => 'Đã xóa nhà cung cấp.'], 200);
    }
}
