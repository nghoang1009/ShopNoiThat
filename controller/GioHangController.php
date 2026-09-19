<?php
require_once __DIR__ . '/../core/Controller.php';

class GioHangController extends Controller {
    private GioHangModel $gioHangModel;

    public function __construct() {
        $this->gioHangModel = $this->model('GioHangModel');
    }

    /**
     * Trang giỏ hàng
     */
    public function index(): void {
        $userId = $_SESSION['user']['id'] ?? null;
        $sessionId = $userId ? null : $this->getCartSessionId();

        $cart = $this->gioHangModel->getCartItems($userId, $sessionId);

        $this->view('client/giohang/index', [
            'pageTitle' => 'Giỏ Hàng Của Bạn',
            'cart'      => $cart
        ]);
    }
}
