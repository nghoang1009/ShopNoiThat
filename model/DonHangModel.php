<?php
require_once __DIR__ . '/../core/Model.php';

class DonHangModel extends Model {
    protected string $table = 'don_hang';

    public function __construct() {
        parent::__construct();
        $this->ensureTablesExist();
    }

    /**
     * Tự động khởi tạo bảng don_hang & chi_tiet_don_hang nếu chưa có trong MySQL
     */
    private function ensureTablesExist(): void {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS `don_hang` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `ma_don_hang` VARCHAR(30) NOT NULL UNIQUE,
              `khach_hang_id` INT NULL,
              `phuong_thuc_van_chuyen_id` INT NULL,
              `phuong_thuc_thanh_toan_id` INT NULL,
              `ho_ten_nhan` VARCHAR(100) NOT NULL,
              `sdt_nhan` VARCHAR(20) NOT NULL,
              `dia_chi_giao` TEXT NOT NULL,
              `tinh_thanh` VARCHAR(100) NULL,
              `ghi_chu` TEXT NULL,
              `tien_hang` DECIMAL(12,2) NOT NULL DEFAULT 0,
              `phi_van_chuyen` DECIMAL(12,2) NOT NULL DEFAULT 0,
              `tong_tien` DECIMAL(12,2) NOT NULL DEFAULT 0,
              `phuong_thuc_thanh_toan` VARCHAR(50) DEFAULT 'cod',
              `trang_thai_thanh_toan` ENUM('chua_thanh_toan', 'da_thanh_toan') DEFAULT 'chua_thanh_toan',
              `trang_thai` ENUM('cho_xu_ly', 'dang_giao', 'hoan_tat', 'da_huy') DEFAULT 'cho_xu_ly',
              `ngay_dat` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX (`khach_hang_id`),
              INDEX (`ma_don_hang`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $this->db->exec("CREATE TABLE IF NOT EXISTS `chi_tiet_don_hang` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `don_hang_id` INT NOT NULL,
              `san_pham_id` INT NULL,
              `ten_san_pham` VARCHAR(255) NOT NULL,
              `hinh_anh` VARCHAR(255) NULL,
              `so_luong` INT NOT NULL DEFAULT 1,
              `don_gia` DECIMAL(12,2) NOT NULL,
              `thanh_tien` DECIMAL(12,2) NOT NULL,
              INDEX (`don_hang_id`),
              INDEX (`san_pham_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        } catch (Exception $e) {
            // Đã tồn tại
        }
    }

    /**
     * Tạo đơn hàng mới từ giỏ hàng (Transaction an toàn)
     */
    public function createOrderFromCart(array $orderData, array $cartItems): array {
        try {
            $this->db->beginTransaction();

            // 1. Tạo mã đơn hàng duy nhất
            $maDonHang = 'DH' . date('Ymd') . strtoupper(substr(uniqid(), -5));
            $orderData['ma_don_hang'] = $maDonHang;

            // 2. Insert bảng don_hang
            $columns = implode(', ', array_keys($orderData));
            $placeholders = ':' . implode(', :', array_keys($orderData));
            $sqlOrder = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
            $params = [];
            foreach ($orderData as $key => $val) {
                $params[':' . $key] = $val;
            }
            $this->query($sqlOrder, $params);
            $orderId = (int)$this->db->lastInsertId();

            // 3. Insert chi tiết đơn hàng & Giảm tồn kho
            $sqlItem = "INSERT INTO chi_tiet_don_hang (don_hang_id, san_pham_id, ten_san_pham, hinh_anh, so_luong, don_gia, thanh_tien)
                        VALUES (:order_id, :product_id, :name, :image, :qty, :price, :subtotal)";
            $stmtItem = $this->db->prepare($sqlItem);

            $sqlUpdateStock = "UPDATE san_pham SET so_luong_ton = GREATEST(0, so_luong_ton - :qty) WHERE id = :pid";
            $stmtStock = $this->db->prepare($sqlUpdateStock);

            foreach ($cartItems as $item) {
                $stmtItem->execute([
                    ':order_id'   => $orderId,
                    ':product_id' => $item['product_id'],
                    ':name'       => $item['ten_san_pham'],
                    ':image'      => $item['hinh_anh'],
                    ':qty'        => $item['cart_quantity'],
                    ':price'      => $item['don_gia_thuc_te'],
                    ':subtotal'   => $item['thanh_tien']
                ]);

                // Trừ tồn kho
                $stmtStock->execute([
                    ':qty' => $item['cart_quantity'],
                    ':pid' => $item['product_id']
                ]);
            }

            // 4. Tạo bản ghi Vận Chuyển (van_chuyen)
            $shippingMethodId = $orderData['phuong_thuc_van_chuyen_id'] ?? null;
            $shippingFee = (float)($orderData['phi_van_chuyen'] ?? 0);
            $maVanDon = 'VD' . date('Ymd') . strtoupper(substr(uniqid(), -5));

            $sqlShipping = "INSERT INTO van_chuyen (don_hang_id, phuong_thuc_van_chuyen_id, don_vi_van_chuyen, ma_van_don, trang_thai_giao_hang, phi_van_chuyen, ngay_giao_du_kien)
                            VALUES (:order_id, :method_id, :carrier, :tracking_code, 'cho_lay_hang', :fee, DATE_ADD(CURDATE(), INTERVAL 3 DAY))";
            $this->query($sqlShipping, [
                ':order_id'      => $orderId,
                ':method_id'     => $shippingMethodId,
                ':carrier'       => 'Đội Vận Tải Nội Thất Chuyên Dụng',
                ':tracking_code' => $maVanDon,
                ':fee'           => $shippingFee
            ]);

            // 5. Tạo bản ghi Thanh Toán (thanh_toan)
            $paymentMethodId = $orderData['phuong_thuc_thanh_toan_id'] ?? null;
            $paymentStatus = ($orderData['trang_thai_thanh_toan'] ?? 'chua_thanh_toan') === 'da_thanh_toan' ? 'da_thanh_toan' : 'cho_thanh_toan';

            $sqlPayment = "INSERT INTO thanh_toan (don_hang_id, phuong_thuc_thanh_toan_id, so_tien, trang_thai, ghi_chu)
                           VALUES (:order_id, :method_id, :amount, :status, :notes)";
            $this->query($sqlPayment, [
                ':order_id'  => $orderId,
                ':method_id' => $paymentMethodId,
                ':amount'    => $orderData['tong_tien'],
                ':status'    => $paymentStatus,
                ':notes'     => 'Khởi tạo thanh toán khi tạo đơn'
            ]);

            $this->db->commit();
            return [
                'success'     => true,
                'order_id'    => $orderId,
                'ma_don_hang' => $maDonHang,
                'ma_van_don'  => $maVanDon
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Lỗi tạo đơn hàng: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy danh sách đơn hàng với phân trang và bộ lọc
     */
    public function getOrders(array $filters = []): array {
        $sql = "SELECT dh.*, kh.ho_ten as ten_khach_hang_acc, kh.email as email_khach_hang,
                       vc.ma_van_don, vc.trang_thai_giao_hang, vc.don_vi_van_chuyen,
                       ptvc.ten_pt as ten_pt_van_chuyen,
                       pttt.ten_pt as ten_pt_thanh_toan
                FROM {$this->table} dh
                LEFT JOIN khach_hang kh ON dh.khach_hang_id = kh.id
                LEFT JOIN van_chuyen vc ON dh.id = vc.don_hang_id
                LEFT JOIN phuong_thuc_van_chuyen ptvc ON dh.phuong_thuc_van_chuyen_id = ptvc.id
                LEFT JOIN phuong_thuc_thanh_toan pttt ON dh.phuong_thuc_thanh_toan_id = pttt.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['trang_thai'])) {
            $sql .= " AND dh.trang_thai = :trang_thai";
            $params[':trang_thai'] = $filters['trang_thai'];
        }

        if (!empty($filters['khach_hang_id'])) {
            $sql .= " AND dh.khach_hang_id = :kh_id";
            $params[':kh_id'] = $filters['khach_hang_id'];
        }

        if (!empty($filters['keyword'])) {
            $sql .= " AND (dh.ma_don_hang LIKE :kw OR dh.ho_ten_nhan LIKE :kw2 OR dh.sdt_nhan LIKE :kw3 OR vc.ma_van_don LIKE :kw4)";
            $params[':kw'] = '%' . $filters['keyword'] . '%';
            $params[':kw2'] = '%' . $filters['keyword'] . '%';
            $params[':kw3'] = '%' . $filters['keyword'] . '%';
            $params[':kw4'] = '%' . $filters['keyword'] . '%';
        }

        $sql .= " ORDER BY dh.id DESC";

        if (!empty($filters['limit'])) {
            $limit = (int)$filters['limit'];
            $offset = (int)($filters['offset'] ?? 0);
            $sql .= " LIMIT {$offset}, {$limit}";
        }

        return $this->fetchAll($sql, $params);
    }

    /**
     * Lấy thông tin đơn hàng chi tiết đầy đủ kèm items, vận chuyển, thanh toán
     */
    public function getOrderDetail(int|string $idOrCode): ?array {
        $field = is_numeric($idOrCode) ? 'dh.id' : 'dh.ma_don_hang';
        $sql = "SELECT dh.*, kh.ho_ten as ten_khach_hang_acc, kh.email as email_khach_hang,
                       vc.id as van_chuyen_id, vc.ma_van_don, vc.don_vi_van_chuyen, vc.trang_thai_giao_hang,
                       vc.ngay_giao_du_kien, vc.ngay_giao_thuc_te, vc.ghi_chu as ghi_chu_van_chuyen,
                       ptvc.ten_pt as ten_pt_van_chuyen, ptvc.thoi_gian_du_kien,
                       tt.id as thanh_toan_id, tt.trang_thai as trang_thai_tt_chi_tiet, tt.ma_giao_dich, tt.ngay_thanh_toan,
                       pttt.ten_pt as ten_pt_thanh_toan, pttt.ma_pt as ma_pt_thanh_toan
                FROM {$this->table} dh
                LEFT JOIN khach_hang kh ON dh.khach_hang_id = kh.id
                LEFT JOIN van_chuyen vc ON dh.id = vc.don_hang_id
                LEFT JOIN phuong_thuc_van_chuyen ptvc ON dh.phuong_thuc_van_chuyen_id = ptvc.id
                LEFT JOIN thanh_toan tt ON dh.id = tt.don_hang_id
                LEFT JOIN phuong_thuc_thanh_toan pttt ON dh.phuong_thuc_thanh_toan_id = pttt.id
                WHERE {$field} = :idOrCode LIMIT 1";

        $order = $this->fetch($sql, [':idOrCode' => $idOrCode]);
        if (!$order) {
            return null;
        }

        // Lấy danh sách sản phẩm trong đơn
        $itemsSql = "SELECT ct.*, sp.slug FROM chi_tiet_don_hang ct
                     LEFT JOIN san_pham sp ON ct.san_pham_id = sp.id
                     WHERE ct.don_hang_id = :order_id";
        $order['items'] = $this->fetchAll($itemsSql, [':order_id' => $order['id']]);

        return $order;
    }

    /**
     * Cập nhật trạng thái đơn hàng (Xử lý hoàn tồn kho nếu hủy đơn)
     */
    public function updateStatus(int $orderId, string $status, ?string $paymentStatus = null): bool {
        $order = $this->find($orderId);
        if (!$order) return false;

        $oldStatus = $order['trang_thai'];

        // Nếu trạng thái đổi sang 'da_huy' và trước đó chưa hủy -> hoàn tồn kho
        if ($status === 'da_huy' && $oldStatus !== 'da_huy') {
            $items = $this->fetchAll("SELECT san_pham_id, so_luong FROM chi_tiet_don_hang WHERE don_hang_id = :oid", [':oid' => $orderId]);
            $stmtRestock = $this->db->prepare("UPDATE san_pham SET so_luong_ton = so_luong_ton + :qty WHERE id = :pid");
            foreach ($items as $item) {
                if (!empty($item['san_pham_id'])) {
                    $stmtRestock->execute([':qty' => $item['so_luong'], ':pid' => $item['san_pham_id']]);
                }
            }
        }

        $updateData = ['trang_thai' => $status];
        if ($paymentStatus !== null) {
            $updateData['trang_thai_thanh_toan'] = $paymentStatus;
        }
        if ($status === 'hoan_tat' && ($order['phuong_thuc_thanh_toan'] === 'cod' || $order['phuong_thuc_thanh_toan_id'] == 1)) {
            $updateData['trang_thai_thanh_toan'] = 'da_thanh_toan';
        }

        $res = $this->update($orderId, $updateData);

        // Đồng bộ trạng thái vận chuyển nếu hoàn tất hoặc giao hàng
        if ($status === 'dang_giao') {
            $this->query("UPDATE van_chuyen SET trang_thai_giao_hang = 'dang_giao' WHERE don_hang_id = :id AND trang_thai_giao_hang = 'cho_lay_hang'", [':id' => $orderId]);
        } elseif ($status === 'hoan_tat') {
            $this->query("UPDATE van_chuyen SET trang_thai_giao_hang = 'da_giao', ngay_giao_thuc_te = NOW() WHERE don_hang_id = :id", [':id' => $orderId]);
            $this->query("UPDATE thanh_toan SET trang_thai = 'da_thanh_toan', ngay_thanh_toan = NOW() WHERE don_hang_id = :id AND trang_thai = 'cho_thanh_toan'", [':id' => $orderId]);
        }

        return $res;
    }

    /**
     * Đếm tổng số đơn hàng theo bộ lọc
     */
    public function countOrders(array $filters = []): int {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} dh
                LEFT JOIN van_chuyen vc ON dh.id = vc.don_hang_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['trang_thai'])) {
            $sql .= " AND dh.trang_thai = :trang_thai";
            $params[':trang_thai'] = $filters['trang_thai'];
        }

        if (!empty($filters['trang_thai_thanh_toan'])) {
            $sql .= " AND dh.trang_thai_thanh_toan = :trang_thai_thanh_toan";
            $params[':trang_thai_thanh_toan'] = $filters['trang_thai_thanh_toan'];
        }

        if (!empty($filters['khach_hang_id'])) {
            $sql .= " AND dh.khach_hang_id = :kh_id";
            $params[':kh_id'] = $filters['khach_hang_id'];
        }

        if (!empty($filters['keyword']) || !empty($filters['search'])) {
            $kw = !empty($filters['keyword']) ? $filters['keyword'] : $filters['search'];
            $sql .= " AND (dh.ma_don_hang LIKE :kw OR dh.ho_ten_nhan LIKE :kw2 OR dh.sdt_nhan LIKE :kw3 OR vc.ma_van_don LIKE :kw4)";
            $params[':kw'] = '%' . $kw . '%';
            $params[':kw2'] = '%' . $kw . '%';
            $params[':kw3'] = '%' . $kw . '%';
            $params[':kw4'] = '%' . $kw . '%';
        }

        $row = $this->fetch($sql, $params);
        return (int)($row['total'] ?? 0);
    }
}
