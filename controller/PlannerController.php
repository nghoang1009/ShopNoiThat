<?php
require_once __DIR__ . '/../core/Controller.php';

/**
 * Công cụ thiết kế / sắp xếp phòng 2D - 3D: kéo thả sản phẩm vào phòng để xem đúng kích thước.
 */
class PlannerController extends Controller {
    private SanPhamModel $sanPhamModel;

    public function __construct() {
        $this->sanPhamModel = $this->model('SanPhamModel');
    }

    public function index(): void {
        $products = [];
        $categories = [];

        foreach ($this->sanPhamModel->getPlannerProducts() as $p) {
            $price = ((float)$p['gia_khuyen_mai'] > 0) ? (float)$p['gia_khuyen_mai'] : (float)$p['gia'];
            $products[] = [
                'id'    => (int)$p['id'],
                'ten'   => html_entity_decode((string)$p['ten_san_pham'], ENT_QUOTES, 'UTF-8'),
                'slug'  => $p['slug'],
                'dm'    => (int)$p['danh_muc_id'],
                'gia'   => $price,
                'ton'   => (int)$p['so_luong_ton'],
                'mau'   => html_entity_decode((string)($p['mau_sac'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'kt'    => html_entity_decode((string)($p['kich_thuoc'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'anh'   => !empty($p['hinh_anh']) ? asset_url($p['hinh_anh']) : asset_url('assets/images/default.jpg'),
                'd'     => (int)$p['dai_cm'],
                'r'     => (int)$p['rong_cm'],
                'c'     => (int)$p['cao_cm'],
                'shape' => $p['hinh_dang'],
                'elev'  => (int)$p['do_cao_cm'],
            ];
            $categories[(int)$p['danh_muc_id']] = html_entity_decode((string)$p['ten_danh_muc'], ENT_QUOTES, 'UTF-8');
        }

        $catList = [];
        foreach ($categories as $id => $name) {
            $catList[] = ['id' => $id, 'ten' => $name];
        }

        $payload = [
            'products'   => $products,
            'categories' => $catList,
            'productUrl' => base_url('san-pham/'),
        ];

        $this->view('client/thietke/index', [
            'pageTitle'   => 'Thiết Kế Phòng 2D / 3D - Kéo Thả Nội Thất Xem Kích Thước',
            'plannerJson' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
            'extraCss'    => ['assets/css/client/planner.css'],
            'extraJs'     => ['assets/js/client/planner.js'],
        ]);
    }
}
