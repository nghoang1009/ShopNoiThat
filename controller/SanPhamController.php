<?php
require_once __DIR__ . '/../core/Controller.php';

class SanPhamController extends Controller {
    private SanPhamModel $sanPhamModel;
    private DanhMucModel $danhMucModel;

    public function __construct() {
        $this->sanPhamModel = $this->model('SanPhamModel');
        $this->danhMucModel = $this->model('DanhMucModel');
    }

    /**
     * Danh sách sản phẩm (có lọc & tìm kiếm)
     */
    public function index(): void {
        $filters = [
            'danh_muc'  => $this->getQuery('danh_muc', ''),
            'gia_min'   => $this->getQuery('gia_min', ''),
            'gia_max'   => $this->getQuery('gia_max', ''),
            'chat_lieu' => $this->getQuery('chat_lieu', ''),
            'mau_sac'   => $this->getQuery('mau_sac', ''),
            'sort'      => $this->getQuery('sort', 'moi_nhat'),
            'keyword'   => $this->getQuery('keyword', '')
        ];

        $categories = $this->danhMucModel->getActiveCategories();
        $attributes = $this->sanPhamModel->getDistinctAttributes();
        $products = $this->sanPhamModel->filterProducts($filters);

        $selectedCategory = null;
        if (!empty($filters['danh_muc'])) {
            $selectedCategory = $this->danhMucModel->findBySlug($filters['danh_muc']);
        }

        $this->view('client/sanpham/index', [
            'pageTitle'        => $selectedCategory ? $selectedCategory['ten_danh_muc'] : 'Tất Cả Sản Phẩm Nội Thất',
            'categories'       => $categories,
            'attributes'       => $attributes,
            'products'         => $products,
            'filters'          => $filters,
            'selectedCategory' => $selectedCategory
        ]);
    }

    /**
     * Xem theo danh mục cụ thể
     */
    public function category($slug): void {
        $_GET['danh_muc'] = $slug;
        $this->index();
    }

    /**
     * Chi tiết sản phẩm
     */
    public function detail($slugOrId): void {
        $product = $this->sanPhamModel->getDetailWithRelations($slugOrId);
        if (!$product) {
            http_response_code(404);
            $this->view('client/errors/404', ['message' => 'Sản phẩm nội thất không tồn tại hoặc đã ngừng kinh doanh.']);
            return;
        }

        // Tăng lượt xem
        $this->sanPhamModel->incrementViews((int)$product['id']);

        // Sản phẩm liên quan
        $relatedProducts = $this->sanPhamModel->getRelatedProducts((int)$product['danh_muc_id'], (int)$product['id'], 4);

        $this->view('client/sanpham/detail', [
            'pageTitle'       => $product['ten_san_pham'],
            'product'         => $product,
            'relatedProducts' => $relatedProducts
        ]);
    }
}
