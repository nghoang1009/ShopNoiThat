<?php
require_once __DIR__ . '/../core/Model.php';

class ThanhToanModel extends Model {
    protected string $table = 'thanh_toan';

    public function __construct() {
        parent::__construct();
        $this->ensureTablesExist();
    }

    /**
     * Tự động khởi tạo bảng thanh_toan & phuong_thuc_thanh_toan nếu chưa có
     */
    private function ensureTablesExist(): void {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS `phuong_thuc_thanh_toan` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `ten_pt` VARCHAR(150) NOT NULL,
              `ma_pt` VARCHAR(50) NOT NULL UNIQUE,
              `mo_ta` TEXT NULL,
              `hinh_anh` VARCHAR(255) NULL,
              `huong_dan` TEXT NULL,
              `trang_thai` TINYINT(1) DEFAULT 1,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $this->db->exec("CREATE TABLE IF NOT EXISTS `thanh_toan` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `don_hang_id` INT NOT NULL,
              `phuong_thuc_thanh_toan_id` INT NULL,
              `so_tien` DECIMAL(12,2) NOT NULL,
              `trang_thai` ENUM('cho_thanh_toan', 'da_thanh_toan', 'that_bai', 'hoan_tien') DEFAULT 'cho_thanh_toan',
              `ma_giao_dich` VARCHAR(100) NULL,
              `ngay_thanh_toan` DATETIME NULL,
              `ghi_chu` TEXT NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX (`don_hang_id`),
              INDEX (`ma_giao_dich`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Thêm dữ liệu mẫu phương thức thanh toán nếu chưa có
            $count = (int)$this->db->query("SELECT COUNT(*) FROM phuong_thuc_thanh_toan")->fetchColumn();
            if ($count === 0) {
                $this->db->exec("INSERT INTO `phuong_thuc_thanh_toan` (`id`, `ten_pt`, `ma_pt`, `mo_ta`, `hinh_anh`, `huong_dan`, `trang_thai`) VALUES
                (1, 'Thanh toán khi nhận hàng (COD)', 'cod', 'Thanh toán tiền mặt cho nhân viên sau khi nhận và kiểm tra hàng.', 'assets/images/payments/cod.png', 'Vui lòng chuẩn bị đủ tiền mặt khi nhận hàng.', 1),
                (2, 'Chuyển khoản Ngân Hàng (VietQR)', 'banking', 'Quét mã VietQR chuyển khoản nhanh 24/7 qua mọi ngân hàng.', 'assets/images/payments/vietqr.png', 'Quét mã QR hiển thị hoặc chuyển khoản theo đúng số tài khoản và cú pháp.', 1),
                (3, 'Ví Điện Tử MoMo', 'momo', 'Thanh toán tiện lợi qua ứng dụng Ví MoMo bằng mã QR.', 'assets/images/payments/momo.png', 'Mở ứng dụng MoMo và quét mã QR để hoàn tất giao dịch.', 1),
                (4, 'Cổng Thanh Toán VNPAY', 'vnpay', 'Thẻ ATM nội địa, Thẻ Visa/Mastercard hoặc VNPAY-QR.', 'assets/images/payments/vnpay.png', 'Thanh toán an toàn qua cổng VNPAY.', 1);");
            }
        } catch (Exception $e) {
            // Đã tồn tại
        }
    }

    /**
     * Lấy các phương thức thanh toán đang hoạt động
     */
    public function getActiveMethods(): array {
        $sql = "SELECT * FROM phuong_thuc_thanh_toan WHERE trang_thai = 1 ORDER BY id ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Lấy toàn bộ phương thức thanh toán (kể cả tắt) cho Admin
     */
    public function getAllMethods(): array {
        $sql = "SELECT * FROM phuong_thuc_thanh_toan ORDER BY id ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Lấy thông tin 1 phương thức thanh toán
     */
    public function getMethodById(int $id): ?array {
        $sql = "SELECT * FROM phuong_thuc_thanh_toan WHERE id = :id LIMIT 1";
        return $this->fetch($sql, [':id' => $id]);
    }

    public function getMethodByCode(string $code): ?array {
        $sql = "SELECT * FROM phuong_thuc_thanh_toan WHERE ma_pt = :code LIMIT 1";
        return $this->fetch($sql, [':code' => $code]);
    }

    /**
     * Bật / Tắt phương thức thanh toán
     */
    public function toggleMethodStatus(int $id): bool {
        $method = $this->getMethodById($id);
        if (!$method) return false;
        $newStatus = $method['trang_thai'] == 1 ? 0 : 1;
        $stmt = $this->query("UPDATE phuong_thuc_thanh_toan SET trang_thai = :st WHERE id = :id", [':st' => $newStatus, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Tạo mới phương thức thanh toán
     */
    public function createMethod(array $data): int|string {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO phuong_thuc_thanh_toan ({$columns}) VALUES ({$placeholders})";
        $params = [];
        foreach ($data as $key => $val) {
            $params[':' . $key] = $val;
        }
        $this->query($sql, $params);
        return $this->db->lastInsertId();
    }

    /**
     * Cập nhật phương thức thanh toán
     */
    public function updateMethod(int $id, array $data): bool {
        $setClauses = [];
        $params = [':id' => $id];
        foreach ($data as $key => $val) {
            $setClauses[] = "{$key} = :{$key}";
            $params[':' . $key] = $val;
        }
        $sql = "UPDATE phuong_thuc_thanh_toan SET " . implode(', ', $setClauses) . " WHERE id = :id";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount() >= 0;
    }

    /**
     * Tạo bản ghi giao dịch thanh toán cho đơn hàng
     */
    public function createPaymentRecord(int $orderId, ?int $methodId, float $amount, string $status = 'cho_thanh_toan', ?string $notes = null): int|string {
        // Kiểm tra đã có bản ghi thanh toán chưa
        $existing = $this->findBy('don_hang_id', $orderId);
        if ($existing) {
            $this->update($existing['id'], [
                'phuong_thuc_thanh_toan_id' => $methodId,
                'so_tien'                   => $amount,
                'trang_thai'                => $status,
                'ghi_chu'                   => $notes
            ]);
            return $existing['id'];
        }

        return $this->create([
            'don_hang_id'               => $orderId,
            'phuong_thuc_thanh_toan_id' => $methodId,
            'so_tien'                   => $amount,
            'trang_thai'                => $status,
            'ghi_chu'                   => $notes
        ]);
    }

    /**
     * Lấy chi tiết thanh toán của 1 đơn hàng
     */
    public function getByOrderId(int $orderId): ?array {
        $sql = "SELECT tt.*, pt.ten_pt, pt.ma_pt, pt.mo_ta as mo_ta_pt, pt.huong_dan,
                       dh.ma_don_hang, dh.tong_tien as tong_tien_don, dh.trang_thai_thanh_toan as trang_thai_tt_don,
                       dh.ho_ten_nhan, dh.sdt_nhan, dh.dia_chi_giao, dh.ngay_dat
                FROM {$this->table} tt
                LEFT JOIN phuong_thuc_thanh_toan pt ON tt.phuong_thuc_thanh_toan_id = pt.id
                JOIN don_hang dh ON tt.don_hang_id = dh.id
                WHERE tt.don_hang_id = :order_id LIMIT 1";
        return $this->fetch($sql, [':order_id' => $orderId]);
    }

    /**
     * Xác nhận thanh toán thành công (Đồng bộ sang bảng don_hang)
     */
    public function confirmPayment(int $paymentIdOrOrderId, ?string $transCode = null, ?string $notes = null, bool $isOrderId = false): bool {
        $field = $isOrderId ? 'don_hang_id' : 'id';
        $payment = $this->findBy($field, $paymentIdOrOrderId);
        if (!$payment) return false;

        $orderId = (int)$payment['don_hang_id'];
        $transCode = $transCode ?: ('TXN' . date('YmdHis') . rand(100, 999));

        // 1. Cập nhật bảng thanh_toan
        $this->update($payment['id'], [
            'trang_thai'       => 'da_thanh_toan',
            'ma_giao_dich'     => $transCode,
            'ngay_thanh_toan'  => date('Y-m-d H:i:s'),
            'ghi_chu'          => $notes ?: $payment['ghi_chu']
        ]);

        // 2. Đồng bộ cập nhật bảng don_hang
        $this->query("UPDATE don_hang SET trang_thai_thanh_toan = 'da_thanh_toan' WHERE id = :order_id", [':order_id' => $orderId]);

        return true;
    }

    /**
     * Giả lập thanh toán online (Ví MoMo, VietQR, VNPAY)
     */
    public function simulateOnlinePayment(string $orderCode, string $methodCode = 'banking'): array {
        $order = $this->fetch("SELECT * FROM don_hang WHERE ma_don_hang = :code LIMIT 1", [':code' => $orderCode]);
        if (!$order) {
            return ['success' => false, 'message' => 'Không tìm thấy đơn hàng.'];
        }

        if ($order['trang_thai_thanh_toan'] === 'da_thanh_toan') {
            return ['success' => true, 'message' => 'Đơn hàng này đã được thanh toán trước đó.'];
        }

        $method = $this->getMethodByCode($methodCode) ?: $this->getMethodByCode('banking');
        $transCode = strtoupper($methodCode) . date('Ymd') . rand(10000, 99999);

        // Tạo/cập nhật bản ghi thanh toán
        $this->createPaymentRecord((int)$order['id'], $method ? (int)$method['id'] : null, (float)$order['tong_tien'], 'da_thanh_toan', 'Giả lập thanh toán thành công qua ' . ($method['ten_pt'] ?? $methodCode));

        // Xác nhận
        $this->confirmPayment((int)$order['id'], $transCode, 'Giao dịch trực tuyến giả lập thành công', true);

        return [
            'success'          => true,
            'ma_giao_dich'     => $transCode,
            'so_tien'          => $order['tong_tien'],
            'so_tien_dinh_dang'=> format_currency($order['tong_tien']),
            'phuong_thuc'      => $method['ten_pt'] ?? $methodCode,
            'message'          => 'Thanh toán đơn hàng #' . $orderCode . ' thành công!'
        ];
    }

    /**
     * Lấy danh sách đối soát thanh toán cho Admin
     */
    public function getTransactionList(array $filters = []): array {
        $sql = "SELECT tt.*, pt.ten_pt as ten_phuong_thuc, pt.ma_pt,
                       dh.ma_don_hang, dh.ho_ten_nhan, dh.sdt_nhan, dh.tong_tien as tong_tien_don,
                       dh.trang_thai as trang_thai_don_hang, dh.ngay_dat
                FROM {$this->table} tt
                LEFT JOIN phuong_thuc_thanh_toan pt ON tt.phuong_thuc_thanh_toan_id = pt.id
                JOIN don_hang dh ON tt.don_hang_id = dh.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['trang_thai'])) {
            $sql .= " AND tt.trang_thai = :st";
            $params[':st'] = $filters['trang_thai'];
        }

        if (!empty($filters['phuong_thuc_id'])) {
            $sql .= " AND tt.phuong_thuc_thanh_toan_id = :ptid";
            $params[':ptid'] = (int)$filters['phuong_thuc_id'];
        }

        if (!empty($filters['keyword'])) {
            $sql .= " AND (dh.ma_don_hang LIKE :kw OR tt.ma_giao_dich LIKE :kw2 OR dh.ho_ten_nhan LIKE :kw3 OR dh.sdt_nhan LIKE :kw4)";
            $params[':kw'] = '%' . $filters['keyword'] . '%';
            $params[':kw2'] = '%' . $filters['keyword'] . '%';
            $params[':kw3'] = '%' . $filters['keyword'] . '%';
            $params[':kw4'] = '%' . $filters['keyword'] . '%';
        }

        $sql .= " ORDER BY tt.id DESC";
        return $this->fetchAll($sql, $params);
    }
}
