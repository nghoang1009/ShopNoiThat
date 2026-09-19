<?php
require_once __DIR__ . '/../core/Model.php';

class VanChuyenModel extends Model {
    protected string $table = 'van_chuyen';

    public function __construct() {
        parent::__construct();
        $this->ensureTablesExist();
    }

    /**
     * Tự động khởi tạo bảng van_chuyen & phuong_thuc_van_chuyen nếu chưa có
     */
    private function ensureTablesExist(): void {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS `phuong_thuc_van_chuyen` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `ten_pt` VARCHAR(150) NOT NULL,
              `ma_pt` VARCHAR(50) NOT NULL UNIQUE,
              `phi_van_chuyen` DECIMAL(12,2) NOT NULL DEFAULT 0,
              `thoi_gian_du_kien` VARCHAR(100) NOT NULL,
              `mo_ta` TEXT NULL,
              `trang_thai` TINYINT(1) DEFAULT 1,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $this->db->exec("CREATE TABLE IF NOT EXISTS `van_chuyen` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `don_hang_id` INT NOT NULL UNIQUE,
              `phuong_thuc_van_chuyen_id` INT NULL,
              `don_vi_van_chuyen` VARCHAR(100) DEFAULT 'Giao Hàng Nội Bộ Shop',
              `ma_van_don` VARCHAR(50) NULL UNIQUE,
              `trang_thai_giao_hang` ENUM('cho_lay_hang', 'dang_van_chuyen', 'dang_giao', 'da_giao', 'giao_that_bai', 'chuyen_hoan') DEFAULT 'cho_lay_hang',
              `phi_van_chuyen` DECIMAL(12,2) NOT NULL DEFAULT 0,
              `ngay_giao_du_kien` DATE NULL,
              `ngay_giao_thuc_te` DATETIME NULL,
              `ghi_chu` TEXT NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX (`don_hang_id`),
              INDEX (`ma_van_don`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Thêm dữ liệu mẫu cho phương thức vận chuyển nếu chưa có
            $count = (int)$this->db->query("SELECT COUNT(*) FROM phuong_thuc_van_chuyen")->fetchColumn();
            if ($count === 0) {
                $this->db->exec("INSERT INTO `phuong_thuc_van_chuyen` (`id`, `ten_pt`, `ma_pt`, `phi_van_chuyen`, `thoi_gian_du_kien`, `mo_ta`, `trang_thai`) VALUES
                (1, 'Giao Hàng Tiêu Chuẩn', 'tieu_chuan', 50000, '3 - 5 ngày', 'Vận chuyển tiêu chuẩn tận nhà cho đồ nội thất.', 1),
                (2, 'Giao Hàng Nhanh Hỏa Tốc', 'hoa_toc', 150000, '24 - 48 giờ', 'Ưu tiên giao gấp trong ngày khu vực nội thành.', 1),
                (3, 'Vận Chuyển & Lắp Đặt Trọn Gói', 'lap_dat_tron_goi', 200000, '2 - 3 ngày', 'Đội ngũ kỹ thuật giao hàng, bưng bê tận phòng và lắp ráp hoàn thiện.', 1);");
            }
        } catch (Exception $e) {
            // Đã tồn tại
        }
    }

    /**
     * Lấy danh sách các phương thức vận chuyển đang hoạt động
     */
    public function getActiveMethods(): array {
        $sql = "SELECT * FROM phuong_thuc_van_chuyen WHERE trang_thai = 1 ORDER BY id ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Lấy tất cả phương thức vận chuyển (kể cả ẩn - cho admin)
     */
    public function getAllMethods(): array {
        $sql = "SELECT * FROM phuong_thuc_van_chuyen ORDER BY id ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Lấy chi tiết 1 phương thức vận chuyển
     */
    public function getMethodById(int $id): ?array {
        $sql = "SELECT * FROM phuong_thuc_van_chuyen WHERE id = :id LIMIT 1";
        return $this->fetch($sql, [':id' => $id]);
    }

    /**
     * Thêm mới phương thức vận chuyển
     */
    public function createMethod(array $data): int|string {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO phuong_thuc_van_chuyen ({$columns}) VALUES ({$placeholders})";
        $params = [];
        foreach ($data as $key => $val) {
            $params[':' . $key] = $val;
        }
        $this->query($sql, $params);
        return $this->db->lastInsertId();
    }

    /**
     * Cập nhật phương thức vận chuyển
     */
    public function updateMethod(int $id, array $data): bool {
        $setClauses = [];
        $params = [':id' => $id];
        foreach ($data as $key => $val) {
            $setClauses[] = "{$key} = :{$key}";
            $params[':' . $key] = $val;
        }
        $sql = "UPDATE phuong_thuc_van_chuyen SET " . implode(', ', $setClauses) . " WHERE id = :id";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount() >= 0;
    }

    /**
     * Xóa phương thức vận chuyển
     */
    public function deleteMethod(int $id): bool {
        $sql = "DELETE FROM phuong_thuc_van_chuyen WHERE id = :id";
        $stmt = $this->query($sql, [':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Tính toán phí vận chuyển linh hoạt theo phương thức, tổng tiền và khu vực
     */
    public function calculateShippingFee(int $methodId, float $subtotal, string $province = ''): array {
        $method = $this->getMethodById($methodId);
        if (!$method) {
            // Mặc định phương thức tiêu chuẩn đầu tiên nếu không tìm thấy
            $method = $this->fetch("SELECT * FROM phuong_thuc_van_chuyen WHERE trang_thai = 1 LIMIT 1");
        }

        if (!$method) {
            return [
                'fee'                => 0,
                'fee_formatted'      => 'Miễn phí',
                'method'             => null,
                'is_free'            => true,
                'estimated_delivery' => '3 - 5 ngày'
            ];
        }

        $baseFee = (float)$method['phi_van_chuyen'];
        $isFree = false;

        // Miễn phí vận chuyển tiêu chuẩn cho đơn hàng từ 10.000.000đ trở lên
        if ($subtotal >= 10000000 && ($method['ma_pt'] === 'tieu_chuan' || $baseFee <= 50000)) {
            $baseFee = 0;
            $isFree = true;
        }

        // Phụ phí ngoại thành/tỉnh xa (nếu có)
        $province = mb_strtolower(trim($province));
        $isMajorCity = str_contains($province, 'hồ chí minh') || str_contains($province, 'hồ chí minh') || str_contains($province, 'hà nội') || str_contains($province, 'đà nẵng');
        if (!empty($province) && !$isMajorCity && $baseFee > 0) {
            $baseFee += 30000; // Phụ phí tỉnh xa
        }

        return [
            'method_id'          => (int)$method['id'],
            'method_name'        => $method['ten_pt'],
            'method_code'        => $method['ma_pt'],
            'fee'                => $baseFee,
            'fee_formatted'      => $baseFee == 0 ? 'Miễn phí' : format_currency($baseFee),
            'is_free'            => $baseFee == 0,
            'estimated_delivery' => $method['thoi_gian_du_kien'],
            'description'        => $method['mo_ta']
        ];
    }

    /**
     * Lấy thông tin vận chuyển của một đơn hàng
     */
    public function getByOrderId(int $orderId): ?array {
        $sql = "SELECT vc.*, pt.ten_pt, pt.ma_pt, pt.thoi_gian_du_kien,
                       dh.ma_don_hang, dh.ho_ten_nhan, dh.sdt_nhan, dh.dia_chi_giao, dh.tinh_thanh, dh.trang_thai as trang_thai_don_hang
                FROM {$this->table} vc
                LEFT JOIN phuong_thuc_van_chuyen pt ON vc.phuong_thuc_van_chuyen_id = pt.id
                JOIN don_hang dh ON vc.don_hang_id = dh.id
                WHERE vc.don_hang_id = :order_id LIMIT 1";
        return $this->fetch($sql, [':order_id' => $orderId]);
    }

    /**
     * Tra cứu vận chuyển theo mã vận đơn hoặc mã đơn hàng
     */
    public function trackByCode(string $code): ?array {
        $sql = "SELECT vc.*, pt.ten_pt, pt.ma_pt, pt.thoi_gian_du_kien,
                       dh.ma_don_hang, dh.ho_ten_nhan, dh.sdt_nhan, dh.dia_chi_giao, dh.tinh_thanh, dh.tong_tien, dh.ngay_dat
                FROM {$this->table} vc
                LEFT JOIN phuong_thuc_van_chuyen pt ON vc.phuong_thuc_van_chuyen_id = pt.id
                JOIN don_hang dh ON vc.don_hang_id = dh.id
                WHERE vc.ma_van_don = :code OR dh.ma_don_hang = :code2
                LIMIT 1";
        $tracking = $this->fetch($sql, [':code' => $code, ':code2' => $code]);
        if ($tracking) {
            $tracking['timeline'] = $this->generateTimeline($tracking);
        }
        return $tracking;
    }

    /**
     * Lập timeline các mốc vận chuyển
     */
    public function generateTimeline(array $shipment): array {
        $status = $shipment['trang_thai_giao_hang'];
        $createdAt = $shipment['created_at'];
        $actualDate = $shipment['ngay_giao_thuc_te'];

        $stages = [
            [
                'step'        => 1,
                'key'         => 'cho_lay_hang',
                'title'       => 'Đã tiếp nhận & Đóng gói',
                'description' => 'Kiện hàng nội thất đang được kiểm tra và đóng thùng gỗ/bọc màng PE.',
                'time'        => date('H:i d/m/Y', strtotime($createdAt)),
                'completed'   => true,
                'active'      => ($status === 'cho_lay_hang')
            ],
            [
                'step'        => 2,
                'key'         => 'dang_van_chuyen',
                'title'       => 'Đang vận chuyển liên tỉnh / kho trung chuyển',
                'description' => 'Đơn hàng đã xuất kho ' . ($shipment['don_vi_van_chuyen'] ?? 'Nội bộ') . ' và đang trung chuyển.',
                'time'        => in_array($status, ['dang_van_chuyen', 'dang_giao', 'da_giao']) ? date('H:i d/m/Y', strtotime($createdAt . ' + 1 day')) : null,
                'completed'   => in_array($status, ['dang_van_chuyen', 'dang_giao', 'da_giao']),
                'active'      => ($status === 'dang_van_chuyen')
            ],
            [
                'step'        => 3,
                'key'         => 'dang_giao',
                'title'       => 'Đang giao hàng đến bạn',
                'description' => 'Tài xế/kỹ thuật viên đang trên đường vận chuyển và liên hệ giao hàng.',
                'time'        => in_array($status, ['dang_giao', 'da_giao']) ? date('H:i d/m/Y', strtotime($createdAt . ' + 2 day')) : null,
                'completed'   => in_array($status, ['dang_giao', 'da_giao']),
                'active'      => ($status === 'dang_giao')
            ],
            [
                'step'        => 4,
                'key'         => 'da_giao',
                'title'       => 'Giao hàng & Lắp đặt thành công',
                'description' => 'Khách hàng đã nhận đủ sản phẩm và ký biên bản giao nhận.',
                'time'        => $actualDate ? date('H:i d/m/Y', strtotime($actualDate)) : null,
                'completed'   => ($status === 'da_giao'),
                'active'      => ($status === 'da_giao')
            ]
        ];

        return $stages;
    }

    /**
     * Tạo hoặc cập nhật thông tin vận chuyển cho đơn hàng
     */
    public function saveShipment(int $orderId, array $data): bool {
        $existing = $this->findBy('don_hang_id', $orderId);
        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            $data['don_hang_id'] = $orderId;
            if (empty($data['ma_van_don'])) {
                $data['ma_van_don'] = 'VD' . date('Ymd') . strtoupper(substr(uniqid(), -5));
            }
            $this->create($data);
            return true;
        }
    }

    /**
     * Admin cập nhật trạng thái vận chuyển và tự đồng bộ đơn hàng
     */
    public function updateShippingStatus(int $orderId, string $status, ?string $carrier = null, ?string $trackingCode = null, ?string $notes = null): bool {
        $shipment = $this->findBy('don_hang_id', $orderId);
        $data = ['trang_thai_giao_hang' => $status];

        if ($carrier !== null) $data['don_vi_van_chuyen'] = $carrier;
        if ($trackingCode !== null) $data['ma_van_don'] = $trackingCode;
        if ($notes !== null) $data['ghi_chu'] = $notes;

        if ($status === 'da_giao') {
            $data['ngay_giao_thuc_te'] = date('Y-m-d H:i:s');
        }

        if ($shipment) {
            $this->update($shipment['id'], $data);
        } else {
            $data['don_hang_id'] = $orderId;
            $data['ma_van_don'] = $trackingCode ?: ('VD' . date('Ymd') . strtoupper(substr(uniqid(), -5)));
            $this->create($data);
        }

        // Tự động đồng bộ sang trạng thái của bảng `don_hang`
        $orderModel = new Model();
        if ($status === 'dang_giao' || $status === 'dang_van_chuyen') {
            $orderModel->query("UPDATE don_hang SET trang_thai = 'dang_giao' WHERE id = :id AND trang_thai != 'hoan_tat' AND trang_thai != 'da_huy'", [':id' => $orderId]);
        } elseif ($status === 'da_giao') {
            $orderModel->query("UPDATE don_hang SET trang_thai = 'hoan_tat', trang_thai_thanh_toan = 'da_thanh_toan' WHERE id = :id", [':id' => $orderId]);
            $orderModel->query("UPDATE thanh_toan SET trang_thai = 'da_thanh_toan', ngay_thanh_toan = NOW() WHERE don_hang_id = :id AND trang_thai = 'cho_thanh_toan'", [':id' => $orderId]);
        }

        return true;
    }

    /**
     * Danh sách đơn hàng kèm tình trạng vận chuyển cho Admin
     */
    public function getShipmentList(array $filters = []): array {
        $sql = "SELECT dh.id as don_hang_id, dh.ma_don_hang, dh.ho_ten_nhan, dh.sdt_nhan, dh.dia_chi_giao,
                       dh.tong_tien, dh.ngay_dat, dh.trang_thai as trang_thai_don_hang,
                       vc.id as van_chuyen_id, vc.ma_van_don, vc.don_vi_van_chuyen, vc.trang_thai_giao_hang,
                       vc.phi_van_chuyen, vc.ngay_giao_du_kien, vc.ngay_giao_thuc_te,
                       pt.ten_pt as ten_phuong_thuc
                FROM don_hang dh
                LEFT JOIN van_chuyen vc ON dh.id = vc.don_hang_id
                LEFT JOIN phuong_thuc_van_chuyen pt ON vc.phuong_thuc_van_chuyen_id = pt.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['trang_thai_giao_hang'])) {
            $sql .= " AND vc.trang_thai_giao_hang = :st";
            $params[':st'] = $filters['trang_thai_giao_hang'];
        }

        if (!empty($filters['keyword'])) {
            $sql .= " AND (dh.ma_don_hang LIKE :kw OR vc.ma_van_don LIKE :kw2 OR dh.ho_ten_nhan LIKE :kw3 OR dh.sdt_nhan LIKE :kw4)";
            $params[':kw'] = '%' . $filters['keyword'] . '%';
            $params[':kw2'] = '%' . $filters['keyword'] . '%';
            $params[':kw3'] = '%' . $filters['keyword'] . '%';
            $params[':kw4'] = '%' . $filters['keyword'] . '%';
        }

        $sql .= " ORDER BY dh.id DESC";
        return $this->fetchAll($sql, $params);
    }
}
