<?php
require_once __DIR__ . '/../core/Controller.php';

class HomeController extends Controller {
    private SanPhamModel $sanPhamModel;
    private DanhMucModel $danhMucModel;

    public function __construct() {
        $this->sanPhamModel = $this->model('SanPhamModel');
        $this->danhMucModel = $this->model('DanhMucModel');
    }

    public function index(): void {
        $categories = $this->danhMucModel->getActiveCategories();
        $featuredProducts = $this->sanPhamModel->getFeaturedProducts(8);
        $newProducts = $this->sanPhamModel->getNewProducts(8);
        $saleProducts = $this->sanPhamModel->getSaleProducts(8);

        $this->view('client/home/index', [
            'pageTitle'        => 'Trang Chủ - Thế Giới Nội Thất Cao Cấp',
            'categories'       => $categories,
            'featuredProducts' => $featuredProducts,
            'newProducts'      => $newProducts,
            'saleProducts'     => $saleProducts
        ]);
    }
}
