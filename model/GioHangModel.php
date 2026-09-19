<?php
require_once __DIR__ . '/../core/Model.php';

class GioHangModel extends Model {
    protected string $table = 'gio_hang';

    public function __construct() {
        parent::__construct();
        $this->ensureTableExists();
    }

    /**
     * Tự động khởi tạo bảng gio_hang nếu chưa có trong MySQL
     */
    private function ensureTableExists(): void {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS `gio_hang` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `khach_hang_id` INT NULL,
              `session_id` VARCHAR(100) NULL,
              `san_pham_id` INT NOT NULL,
              `so_luong` INT NOT NULL DEFAULT 1,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX (`khach_hang_id`),
              INDEX (`session_id`),
              INDEX (`san_pham_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        } catch (Exception $e) {
            // Đã tồn tại
        }
    }

    /**
     * Lấy danh sách sản phẩm trong giỏ hàng theo User ID hoặc Session ID
     */
    public function getCartItems(?int $userId = null, ?string $sessionId = null): array {
        if (!$userId && !$sessionId) {
            return [];
        }

        $sql = "SELECT gh.id as cart_id, gh.so_luong as cart_quantity,
                       sp.id as product_id, sp.ten_san_pham, sp.slug, sp.hinh_anh,
                       sp.gia, sp.gia_khuyen_mai, sp.so_luong_ton, sp.chat_lieu, sp.mau_sac,
                       (CASE WHEN sp.gia_khuyen_mai > 0 THEN sp.gia_khuyen_mai ELSE sp.gia END) as don_gia_thuc_te,
                       ((CASE WHEN sp.gia_khuyen_mai > 0 THEN sp.gia_khuyen_mai ELSE sp.gia END) * gh.so_luong) as thanh_tien
                FROM {$this->table} gh
                JOIN san_pham sp ON gh.san_pham_id = sp.id
                WHERE " . ($userId ? "gh.khach_hang_id = :uid" : "gh.session_id = :sid") . "
                ORDER BY gh.id DESC";

        $params = $userId ? [':uid' => $userId] : [':sid' => $sessionId];
        $items = $this->fetchAll($sql, $params);

        $totalAmount = 0;
        $totalQuantity = 0;
        foreach ($items as $item) {
            $totalAmount += (float)$item['thanh_tien'];
            $totalQuantity += (int)$item['cart_quantity'];
        }

        return [
            'items'          => $items,
            'total_amount'   => $totalAmount,
            'total_quantity' => $totalQuantity
        ];
    }

    /**
     * Thêm sản phẩm vào giỏ hàng
     */
    public function addToCart(int $productId, int $quantity = 1, ?int $userId = null, ?string $sessionId = null): bool {
        if (!$userId && !$sessionId) {
            return false;
        }

        // Kiểm tra xem sản phẩm đã có trong giỏ chưa
        $sql = "SELECT id, so_luong FROM {$this->table}
                WHERE san_pham_id = :pid AND " . ($userId ? "khach_hang_id = :uid" : "session_id = :sid") . " LIMIT 1";
        $params = [':pid' => $productId];
        if ($userId) {
            $params[':uid'] = $userId;
        } else {
            $params[':sid'] = $sessionId;
        }

        $existing = $this->fetch($sql, $params);

        if ($existing) {
            $newQuantity = $existing['so_luong'] + $quantity;
            $updateSql = "UPDATE {$this->table} SET so_luong = :qty, updated_at = NOW() WHERE id = :id";
            $this->query($updateSql, [':qty' => $newQuantity, ':id' => $existing['id']]);
            return true;
        } else {
            $insertData = [
                'san_pham_id'   => $productId,
                'so_luong'      => $quantity,
                'khach_hang_id' => $userId,
                'session_id'    => $userId ? null : $sessionId
            ];
            $this->create($insertData);
            return true;
        }
    }

    /**
     * Cập nhật số lượng của mục trong giỏ
     */
    public function updateQuantity(int $cartId, int $quantity, ?int $userId = null, ?string $sessionId = null): bool {
        if ($quantity <= 0) {
            return $this->removeFromCart($cartId, $userId, $sessionId);
        }

        $sql = "UPDATE {$this->table} SET so_luong = :qty, updated_at = NOW()
                WHERE id = :id AND " . ($userId ? "khach_hang_id = :uid" : "session_id = :sid");
        $params = [':qty' => $quantity, ':id' => $cartId];
        if ($userId) {
            $params[':uid'] = $userId;
        } else {
            $params[':sid'] = $sessionId;
        }

        $stmt = $this->query($sql, $params);
        return $stmt->rowCount() >= 0;
    }

    /**
     * Xóa 1 mục khỏi giỏ hàng
     */
    public function removeFromCart(int $cartId, ?int $userId = null, ?string $sessionId = null): bool {
        $sql = "DELETE FROM {$this->table} WHERE id = :id AND " . ($userId ? "khach_hang_id = :uid" : "session_id = :sid");
        $params = [':id' => $cartId];
        if ($userId) {
            $params[':uid'] = $userId;
        } else {
            $params[':sid'] = $sessionId;
        }
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Xóa toàn bộ giỏ hàng
     */
    public function clearCart(?int $userId = null, ?string $sessionId = null): bool {
        if (!$userId && !$sessionId) return false;
        $sql = "DELETE FROM {$this->table} WHERE " . ($userId ? "khach_hang_id = :uid" : "session_id = :sid");
        $params = $userId ? [':uid' => $userId] : [':sid' => $sessionId];
        $this->query($sql, $params);
        return true;
    }

    /**
     * Hợp nhất giỏ hàng session vào tài khoản khi người dùng đăng nhập
     */
    public function mergeGuestCartToUser(string $sessionId, int $userId): void {
        $guestItems = $this->fetchAll("SELECT * FROM {$this->table} WHERE session_id = :sid", [':sid' => $sessionId]);
        foreach ($guestItems as $item) {
            $this->addToCart((int)$item['san_pham_id'], (int)$item['so_luong'], $userId, null);
        }
        // Xóa giỏ hàng guest cũ
        $this->query("DELETE FROM {$this->table} WHERE session_id = :sid", [':sid' => $sessionId]);
    }
}
