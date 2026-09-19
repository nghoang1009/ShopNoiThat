<?php
require_once __DIR__ . '/../core/Controller.php';

class SanPhamApiController extends Controller {
    private SanPhamModel $sanPhamModel;
    private DanhMucModel $danhMucModel;

    public function __construct() {
        $this->sanPhamModel = $this->model('SanPhamModel');
        $this->danhMucModel = $this->model('DanhMucModel');
    }

    /**
     * GET /api/v1/san-pham
     * Lấy danh sách sản phẩm (có lọc, tìm kiếm, sắp xếp, phân trang)
     */
    public function index(): void {
        $page = max(1, (int)$this->getQuery('page', 1));
        $limit = max(1, min(100, (int)$this->getQuery('limit', 12)));
        $offset = ($page - 1) * $limit;

        $filters = [
            'danh_muc'    => $this->getQuery('danh_muc'),
            'danh_muc_id' => $this->getQuery('danh_muc_id'),
            'gia_min'     => $this->getQuery('gia_min'),
            'gia_max'     => $this->getQuery('gia_max'),
            'chat_lieu'   => $this->getQuery('chat_lieu'),
            'mau_sac'     => $this->getQuery('mau_sac'),
            'sort'        => $this->getQuery('sort', 'moi_nhat'),
            'keyword'     => $this->getQuery('keyword'),
            'limit'       => $limit,
            'offset'      => $offset
        ];

        $products = $this->sanPhamModel->filterProducts($filters);

        // Đếm tổng số lượng cho phân trang
        $countFilters = $filters;
        unset($countFilters['limit'], $countFilters['offset']);
        $total = $this->sanPhamModel->countFilteredProducts($countFilters);

        // Format tiền tệ & URL ảnh
        foreach ($products as &$p) {
            $p['gia_dinh_dang'] = format_currency($p['gia']);
            $p['gia_km_dinh_dang'] = $p['gia_khuyen_mai'] > 0 ? format_currency($p['gia_khuyen_mai']) : null;
            $p['phan_tram_giam'] = ($p['gia_khuyen_mai'] > 0 && $p['gia'] > 0) ? round((($p['gia'] - $p['gia_khuyen_mai']) / $p['gia']) * 100) : 0;
            $p['hinh_anh_url'] = !empty($p['hinh_anh']) ? asset_url($p['hinh_anh']) : asset_url('assets/images/default.jpg');
            $p['detail_url'] = base_url('san-pham/' . ($p['slug'] ?? $p['id']));
        }

        $this->paginate($products, $total, $page, $limit, 'Lấy danh sách sản phẩm thành công.');
    }

    /**
     * GET /api/v1/san-pham/{id}
     * Chi tiết một sản phẩm
     */
    public function detail($id): void {
        do {
            if (empty($id)) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Mã sản phẩm không hợp lệ.'], 400);
                break;
            }

            $product = $this->sanPhamModel->getDetailWithRelations($id);
            if (!$product) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Không tìm thấy sản phẩm.'], 404);
                break;
            }

            // Tăng lượt xem
            $this->sanPhamModel->incrementViews((int)$product['id']);

            $product['gia_dinh_dang'] = format_currency($product['gia']);
            $product['gia_km_dinh_dang'] = $product['gia_khuyen_mai'] > 0 ? format_currency($product['gia_khuyen_mai']) : null;
            $product['hinh_anh_url'] = !empty($product['hinh_anh']) ? asset_url($product['hinh_anh']) : asset_url('assets/images/default.jpg');
            $product['hinh_anh_phu_list'] = !empty($product['hinh_anh_phu']) ? json_decode($product['hinh_anh_phu'], true) : [];

            // Lấy sản phẩm liên quan
            $related = $this->sanPhamModel->getRelatedProducts((int)$product['danh_muc_id'], (int)$product['id'], 4);
            foreach ($related as &$r) {
                $r['gia_dinh_dang'] = format_currency($r['gia']);
                $r['gia_km_dinh_dang'] = $r['gia_khuyen_mai'] > 0 ? format_currency($r['gia_khuyen_mai']) : null;
                $r['hinh_anh_url'] = !empty($r['hinh_anh']) ? asset_url($r['hinh_anh']) : asset_url('assets/images/default.jpg');
                $r['detail_url'] = base_url('san-pham/' . ($r['slug'] ?? $r['id']));
            }

            $this->json([
                'success' => true,
                'data' => [
                    'product' => $product,
                    'related' => $related
                ],
                'message' => 'Lấy chi tiết sản phẩm thành công.'
            ], 200);
            return;
        } while (false);
    }

    /**
     * POST /api/v1/san-pham
     * Tạo mới sản phẩm (Yêu cầu quyền Admin)
     */
    public function create(): void {
        $this->requireAdminAuth();
        $body = $this->getBody();

        $rules = [
            'ten_san_pham' => ['required' => true, 'min' => 2, 'max' => 255, 'label' => 'Tên sản phẩm'],
            'danh_muc_id'  => ['required' => true, 'numeric' => true, 'label' => 'Danh mục'],
            'gia'          => ['required' => true, 'numeric' => true, 'label' => 'Giá bán']
        ];

        do {
            $errors = $this->validateInputs($rules, $body);
            if (!empty($errors)) {
                $this->json(['success' => false, 'data' => null, 'errors' => $errors, 'message' => reset($errors)], 400);
                break;
            }

            $slug = !empty($body['slug']) ? slugify($body['slug']) : slugify($body['ten_san_pham']);
            // Kiểm tra slug trùng
            $existing = $this->sanPhamModel->findBy('slug', $slug);
            if ($existing) {
                $slug .= '-' . time();
            }

            $data = [
                'ten_san_pham'    => trim($body['ten_san_pham']),
                'slug'            => $slug,
                'danh_muc_id'     => (int)$body['danh_muc_id'],
                'nha_cung_cap_id' => !empty($body['nha_cung_cap_id']) ? (int)$body['nha_cung_cap_id'] : null,
                'gia'             => (float)$body['gia'],
                'gia_khuyen_mai'  => (float)($body['gia_khuyen_mai'] ?? 0),
                'chat_lieu'       => trim($body['chat_lieu'] ?? ''),
                'kich_thuoc'      => trim($body['kich_thuoc'] ?? ''),
                'mau_sac'         => trim($body['mau_sac'] ?? ''),
                'mo_ta'           => $body['mo_ta'] ?? '',
                'hinh_anh'        => trim($body['hinh_anh'] ?? ''),
                'hinh_anh_phu'    => isset($body['hinh_anh_phu']) ? (is_array($body['hinh_anh_phu']) ? json_encode($body['hinh_anh_phu']) : $body['hinh_anh_phu']) : null,
                'so_luong_ton'    => (int)($body['so_luong_ton'] ?? 0),
                'trang_thai'      => isset($body['trang_thai']) ? (int)$body['trang_thai'] : 1,
                'noi_bat'         => isset($body['noi_bat']) ? (int)$body['noi_bat'] : 0
            ];

            $id = $this->sanPhamModel->create($data);
            if ($id) {
                $newProduct = $this->sanPhamModel->find($id);
                $this->created($newProduct, 'Tạo sản phẩm mới thành công.', base_url('api/v1/san-pham/' . $id));
                return;
            }

            $this->json(['success' => false, 'data' => null, 'message' => 'Không thể tạo sản phẩm.'], 500);
            return;
        } while (false);
    }

    /**
     * PUT /api/v1/san-pham/{id}
     * Cập nhật toàn bộ thông tin sản phẩm (Admin)
     */
    public function update($id): void {
        $this->requireAdminAuth();
        $id = (int)$id;
        $body = $this->getBody();

        do {
            if ($id <= 0) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Mã sản phẩm không hợp lệ.'], 400);
                break;
            }

            $product = $this->sanPhamModel->find($id);
            if (!$product) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Sản phẩm không tồn tại.'], 404);
                break;
            }

            $rules = [
                'ten_san_pham' => ['required' => true, 'min' => 2, 'max' => 255, 'label' => 'Tên sản phẩm'],
                'danh_muc_id'  => ['required' => true, 'numeric' => true, 'label' => 'Danh mục'],
                'gia'          => ['required' => true, 'numeric' => true, 'label' => 'Giá bán']
            ];

            $errors = $this->validateInputs($rules, $body);
            if (!empty($errors)) {
                $this->json(['success' => false, 'data' => null, 'errors' => $errors, 'message' => reset($errors)], 400);
                break;
            }

            $data = [
                'ten_san_pham'    => trim($body['ten_san_pham']),
                'danh_muc_id'     => (int)$body['danh_muc_id'],
                'nha_cung_cap_id' => !empty($body['nha_cung_cap_id']) ? (int)$body['nha_cung_cap_id'] : null,
                'gia'             => (float)$body['gia'],
                'gia_khuyen_mai'  => (float)($body['gia_khuyen_mai'] ?? 0),
                'chat_lieu'       => trim($body['chat_lieu'] ?? ''),
                'kich_thuoc'      => trim($body['kich_thuoc'] ?? ''),
                'mau_sac'         => trim($body['mau_sac'] ?? ''),
                'mo_ta'           => $body['mo_ta'] ?? '',
                'so_luong_ton'    => (int)($body['so_luong_ton'] ?? 0),
                'trang_thai'      => isset($body['trang_thai']) ? (int)$body['trang_thai'] : 1,
                'noi_bat'         => isset($body['noi_bat']) ? (int)$body['noi_bat'] : 0
            ];

            if (!empty($body['hinh_anh'])) {
                $data['hinh_anh'] = trim($body['hinh_anh']);
            }
            if (isset($body['hinh_anh_phu'])) {
                $data['hinh_anh_phu'] = is_array($body['hinh_anh_phu']) ? json_encode($body['hinh_anh_phu']) : $body['hinh_anh_phu'];
            }

            $this->sanPhamModel->update($id, $data);
            $updated = $this->sanPhamModel->find($id);

            $this->json([
                'success' => true,
                'data'    => $updated,
                'message' => 'Cập nhật sản phẩm thành công.'
            ], 200);
            return;
        } while (false);
    }

    /**
     * PATCH /api/v1/san-pham/{id}
     * Cập nhật một phần thuộc tính (tồn kho, trạng thái, nổi bật...)
     */
    public function patchUpdate($id): void {
        $this->requireAdminAuth();
        $id = (int)$id;
        $body = $this->getBody();

        do {
            if ($id <= 0) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Mã sản phẩm không hợp lệ.'], 400);
                break;
            }

            $product = $this->sanPhamModel->find($id);
            if (!$product) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Sản phẩm không tồn tại.'], 404);
                break;
            }

            $allowedFields = ['so_luong_ton', 'trang_thai', 'noi_bat', 'gia', 'gia_khuyen_mai'];
            $dataToUpdate = [];

            foreach ($allowedFields as $field) {
                if (isset($body[$field])) {
                    $dataToUpdate[$field] = $body[$field];
                }
            }

            if (empty($dataToUpdate)) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Không có dữ liệu hợp lệ để cập nhật.'], 400);
                break;
            }

            $this->sanPhamModel->update($id, $dataToUpdate);
            $updated = $this->sanPhamModel->find($id);

            $this->json([
                'success' => true,
                'data'    => $updated,
                'message' => 'Cập nhật thuộc tính sản phẩm thành công.'
            ], 200);
            return;
        } while (false);
    }

    /**
     * DELETE /api/v1/san-pham/{id}
     * Xóa sản phẩm (Admin)
     */
    public function delete($id): void {
        $this->requireAdminAuth();
        $id = (int)$id;

        do {
            if ($id <= 0) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Mã sản phẩm không hợp lệ.'], 400);
                break;
            }

            $product = $this->sanPhamModel->find($id);
            if (!$product) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Sản phẩm không tồn tại.'], 404);
                break;
            }

            $deleted = $this->sanPhamModel->delete($id);
            if ($deleted) {
                $this->json([
                    'success' => true,
                    'data'    => ['id' => $id],
                    'message' => 'Đã xóa sản phẩm thành công.'
                ], 200);
                return;
            }

            $this->json(['success' => false, 'data' => null, 'message' => 'Không thể xóa sản phẩm.'], 500);
            return;
        } while (false);
    }
}
