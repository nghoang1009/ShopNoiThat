<?php
require_once __DIR__ . '/../core/Controller.php';

class DanhMucApiController extends Controller {
    private DanhMucModel $danhMucModel;

    public function __construct() {
        $this->danhMucModel = $this->model('DanhMucModel');
    }

    /**
     * GET /api/v1/danh-muc
     * Lấy toàn bộ danh sách danh mục sản phẩm
     */
    public function index(): void {
        $categories = $this->danhMucModel->getCategoriesWithProductCount();
        $this->json([
            'success' => true,
            'data'    => $categories,
            'message' => 'Lấy danh sách danh mục thành công.'
        ], 200);
    }

    /**
     * GET /api/v1/danh-muc/{id}
     * Chi tiết danh mục
     */
    public function detail($id): void {
        do {
            if (empty($id)) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Mã danh mục không hợp lệ.'], 400);
                break;
            }

            $category = is_numeric($id) ? $this->danhMucModel->find($id) : $this->danhMucModel->findBySlug($id);
            if (!$category) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Không tìm thấy danh mục.'], 404);
                break;
            }

            $this->json([
                'success' => true,
                'data'    => $category,
                'message' => 'Lấy chi tiết danh mục thành công.'
            ], 200);
            return;
        } while (false);
    }

    /**
     * POST /api/v1/danh-muc
     * Tạo danh mục mới (Admin)
     */
    public function create(): void {
        $this->requireAdminAuth();
        $body = $this->getBody();

        $rules = [
            'ten_danh_muc' => ['required' => true, 'min' => 2, 'max' => 255, 'label' => 'Tên danh mục']
        ];

        do {
            $errors = $this->validateInputs($rules, $body);
            if (!empty($errors)) {
                $this->json(['success' => false, 'data' => null, 'errors' => $errors, 'message' => reset($errors)], 400);
                break;
            }

            $slug = !empty($body['slug']) ? slugify($body['slug']) : slugify($body['ten_danh_muc']);
            $existing = $this->danhMucModel->findBySlug($slug);
            if ($existing) {
                $slug .= '-' . time();
            }

            $data = [
                'ten_danh_muc' => trim($body['ten_danh_muc']),
                'slug'         => $slug,
                'mo_ta'        => trim($body['mo_ta'] ?? ''),
                'hinh_anh'     => trim($body['hinh_anh'] ?? ''),
                'trang_thai'   => isset($body['trang_thai']) ? (int)$body['trang_thai'] : 1
            ];

            $id = $this->danhMucModel->create($data);
            if ($id) {
                $newCat = $this->danhMucModel->find($id);
                $this->created($newCat, 'Tạo danh mục mới thành công.', base_url('api/v1/danh-muc/' . $id));
                return;
            }

            $this->json(['success' => false, 'data' => null, 'message' => 'Không thể tạo danh mục.'], 500);
            return;
        } while (false);
    }

    /**
     * PUT /api/v1/danh-muc/{id}
     * Cập nhật danh mục (Admin)
     */
    public function update($id): void {
        $this->requireAdminAuth();
        $id = (int)$id;
        $body = $this->getBody();

        do {
            if ($id <= 0) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Mã danh mục không hợp lệ.'], 400);
                break;
            }

            $category = $this->danhMucModel->find($id);
            if (!$category) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Danh mục không tồn tại.'], 404);
                break;
            }

            $rules = [
                'ten_danh_muc' => ['required' => true, 'min' => 2, 'max' => 255, 'label' => 'Tên danh mục']
            ];

            $errors = $this->validateInputs($rules, $body);
            if (!empty($errors)) {
                $this->json(['success' => false, 'data' => null, 'errors' => $errors, 'message' => reset($errors)], 400);
                break;
            }

            $data = [
                'ten_danh_muc' => trim($body['ten_danh_muc']),
                'mo_ta'        => trim($body['mo_ta'] ?? ''),
                'trang_thai'   => isset($body['trang_thai']) ? (int)$body['trang_thai'] : 1
            ];

            if (!empty($body['slug'])) {
                $data['slug'] = slugify($body['slug']);
            }
            if (!empty($body['hinh_anh'])) {
                $data['hinh_anh'] = trim($body['hinh_anh']);
            }

            $this->danhMucModel->update($id, $data);
            $updated = $this->danhMucModel->find($id);

            $this->json([
                'success' => true,
                'data'    => $updated,
                'message' => 'Cập nhật danh mục thành công.'
            ], 200);
            return;
        } while (false);
    }

    /**
     * DELETE /api/v1/danh-muc/{id}
     * Xóa danh mục (Admin)
     */
    public function delete($id): void {
        $this->requireAdminAuth();
        $id = (int)$id;

        do {
            if ($id <= 0) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Mã danh mục không hợp lệ.'], 400);
                break;
            }

            $category = $this->danhMucModel->find($id);
            if (!$category) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Danh mục không tồn tại.'], 404);
                break;
            }

            $deleted = $this->danhMucModel->delete($id);
            if ($deleted) {
                $this->json([
                    'success' => true,
                    'data'    => ['id' => $id],
                    'message' => 'Đã xóa danh mục thành công.'
                ], 200);
                return;
            }

            $this->json(['success' => false, 'data' => null, 'message' => 'Không thể xóa danh mục.'], 500);
            return;
        } while (false);
    }
}
