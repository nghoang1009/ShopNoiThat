<?php
require_once __DIR__ . '/../core/Controller.php';

class GioHangApiController extends Controller {
    private GioHangModel $gioHangModel;
    private SanPhamModel $sanPhamModel;

    public function __construct() {
        $this->gioHangModel = $this->model('GioHangModel');
        $this->sanPhamModel = $this->model('SanPhamModel');
    }

    /**
     * GET /api/v1/gio-hang
     * Lấy toàn bộ thông tin giỏ hàng hiện tại
     */
    public function index(): void {
        $userId = $_SESSION['user']['id'] ?? null;
        $sessionId = $userId ? null : $this->getCartSessionId();

        $cart = $this->gioHangModel->getCartItems($userId, $sessionId);

        // Format tiền tệ & link
        foreach ($cart['items'] as &$item) {
            $item['don_gia_dinh_dang'] = format_currency($item['don_gia_thuc_te']);
            $item['thanh_tien_dinh_dang'] = format_currency($item['thanh_tien']);
            $item['hinh_anh_url'] = !empty($item['hinh_anh']) ? asset_url($item['hinh_anh']) : asset_url('assets/images/default.jpg');
            $item['detail_url'] = base_url('san-pham/' . ($item['slug'] ?? $item['product_id']));
        }

        $cart['total_amount_formatted'] = format_currency($cart['total_amount']);

        $this->json([
            'success' => true,
            'data'    => $cart,
            'message' => 'Lấy thông tin giỏ hàng thành công.'
        ], 200);
    }

    /**
     * POST /api/v1/gio-hang
     * Thêm sản phẩm vào giỏ hàng (body: san_pham_id, so_luong)
     */
    public function create(): void {
        $body = $this->getBody();
        $productId = (int)($body['san_pham_id'] ?? 0);
        $quantity = (int)($body['so_luong'] ?? 1);

        do {
            if ($productId <= 0) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Sản phẩm không hợp lệ.'], 400);
                break;
            }

            if ($quantity <= 0) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Số lượng thêm phải lớn hơn 0.'], 400);
                break;
            }

            $product = $this->sanPhamModel->find($productId);
            if (!$product || $product['trang_thai'] != 1) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.'], 404);
                break;
            }

            if ($product['so_luong_ton'] < $quantity) {
                $this->json(['success' => false, 'data' => null, 'message' => "Số lượng tồn kho không đủ (chỉ còn {$product['so_luong_ton']} sản phẩm)."], 400);
                break;
            }

            $userId = $_SESSION['user']['id'] ?? null;
            $sessionId = $userId ? null : $this->getCartSessionId();

            $result = $this->gioHangModel->addToCart($productId, $quantity, $userId, $sessionId);
            if ($result) {
                $cart = $this->gioHangModel->getCartItems($userId, $sessionId);
                $this->json([
                    'success' => true,
                    'data'    => [
                        'total_quantity'         => $cart['total_quantity'],
                        'total_amount'           => $cart['total_amount'],
                        'total_amount_formatted' => format_currency($cart['total_amount'])
                    ],
                    'message' => "Đã thêm `{$product['ten_san_pham']}` vào giỏ hàng thành công!"
                ], 201);
                return;
            }

            $this->json(['success' => false, 'data' => null, 'message' => 'Không thể thêm sản phẩm vào giỏ hàng.'], 500);
            return;
        } while (false);
    }

    /**
     * PATCH /api/v1/gio-hang/{id}
     * PUT   /api/v1/gio-hang/{id}
     * Cập nhật số lượng 1 dòng trong giỏ
     */
    public function update($cartId): void {
        $cartId = (int)$cartId;
        $body = $this->getBody();
        $quantity = isset($body['so_luong']) ? (int)$body['so_luong'] : 1;

        do {
            if ($cartId <= 0) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Mã giỏ hàng không hợp lệ.'], 400);
                break;
            }

            $userId = $_SESSION['user']['id'] ?? null;
            $sessionId = $userId ? null : $this->getCartSessionId();

            $cartItem = $this->gioHangModel->find($cartId);
            if (!$cartItem) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Mục giỏ hàng không tồn tại.'], 404);
                break;
            }

            if ($quantity > 0) {
                $product = $this->sanPhamModel->find($cartItem['san_pham_id']);
                if ($product && $product['so_luong_ton'] < $quantity) {
                    $this->json(['success' => false, 'data' => null, 'message' => "Số lượng tồn kho chỉ còn {$product['so_luong_ton']} sản phẩm."], 400);
                    break;
                }
            }

            $updated = $this->gioHangModel->updateQuantity($cartId, $quantity, $userId, $sessionId);
            if ($updated) {
                $cart = $this->gioHangModel->getCartItems($userId, $sessionId);
                $this->json([
                    'success' => true,
                    'data'    => [
                        'cart'                   => $cart,
                        'total_quantity'         => $cart['total_quantity'],
                        'total_amount'           => $cart['total_amount'],
                        'total_amount_formatted' => format_currency($cart['total_amount'])
                    ],
                    'message' => 'Cập nhật giỏ hàng thành công.'
                ], 200);
                return;
            }

            $this->json(['success' => false, 'data' => null, 'message' => 'Không thể cập nhật giỏ hàng.'], 500);
            return;
        } while (false);
    }

    /**
     * DELETE /api/v1/gio-hang/{id}
     * Xóa 1 sản phẩm khỏi giỏ hàng
     */
    public function delete($cartId): void {
        $cartId = (int)$cartId;
        do {
            if ($cartId <= 0) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Mã giỏ hàng không hợp lệ.'], 400);
                break;
            }

            $userId = $_SESSION['user']['id'] ?? null;
            $sessionId = $userId ? null : $this->getCartSessionId();

            $deleted = $this->gioHangModel->removeFromCart($cartId, $userId, $sessionId);
            if ($deleted) {
                $cart = $this->gioHangModel->getCartItems($userId, $sessionId);
                $this->json([
                    'success' => true,
                    'data'    => [
                        'total_quantity'         => $cart['total_quantity'],
                        'total_amount'           => $cart['total_amount'],
                        'total_amount_formatted' => format_currency($cart['total_amount'])
                    ],
                    'message' => 'Đã xóa sản phẩm khỏi giỏ hàng.'
                ], 200);
                return;
            }

            $this->json(['success' => false, 'data' => null, 'message' => 'Không tìm thấy sản phẩm trong giỏ để xóa.'], 404);
            return;
        } while (false);
    }

    /**
     * DELETE /api/v1/gio-hang
     * Xóa sạch toàn bộ giỏ hàng
     */
    public function clear(): void {
        $userId = $_SESSION['user']['id'] ?? null;
        $sessionId = $userId ? null : $this->getCartSessionId();

        $this->gioHangModel->clearCart($userId, $sessionId);
        $this->json([
            'success' => true,
            'data'    => ['total_quantity' => 0, 'total_amount' => 0],
            'message' => 'Đã làm trống giỏ hàng.'
        ], 200);
    }
}
