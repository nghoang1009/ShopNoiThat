<?php
require_once __DIR__ . '/../core/Model.php';

class TuVanModel extends Model {
    protected string $table = 'phien_tu_van';

    public function __construct() {
        parent::__construct();
        $this->ensureTablesExist();
    }

    /**
     * Tự động khởi tạo bảng nếu chưa có trong MySQL
     */
    private function ensureTablesExist(): void {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS `phien_tu_van` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `khach_hang_id` INT NULL,
              `session_id` VARCHAR(100) NULL,
              `tieu_de` VARCHAR(255) DEFAULT 'Hội thoại tư vấn nội thất',
              `tao_luc` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `cap_nhat_luc` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $this->db->exec("CREATE TABLE IF NOT EXISTS `tin_nhan_tu_van` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `phien_tu_van_id` INT NOT NULL,
              `nguoi_gui` ENUM('khach', 'ai') NOT NULL DEFAULT 'khach',
              `noi_dung` LONGTEXT NOT NULL,
              `san_pham_goi_y_id` INT NULL,
              `thoi_gian` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        } catch (Exception $e) {
            // Đã tồn tại
        }
    }

    /**
     * Lấy hoặc khởi tạo phiên tư vấn của khách (theo Session ID hoặc Khách hàng ID)
     */
    public function getOrCreateSession(?int $userId = null, ?string $sessionId = null, bool $forceNew = false): array {
        if (!$forceNew) {
            $sql = "SELECT * FROM {$this->table} WHERE 1=1";
            $params = [];

            if ($userId) {
                $sql .= " AND khach_hang_id = :uid";
                $params[':uid'] = $userId;
            } elseif ($sessionId) {
                $sql .= " AND session_id = :sid";
                $params[':sid'] = $sessionId;
            } else {
                $sessionId = bin2hex(random_bytes(16));
                $sql .= " AND session_id = :sid";
                $params[':sid'] = $sessionId;
            }

            $sql .= " ORDER BY id DESC LIMIT 1";
            $session = $this->fetch($sql, $params);

            if ($session) {
                return $session;
            }
        }

        // Tạo phiên mới hoàn toàn
        $sessionId = bin2hex(random_bytes(16));
        $data = [
            'khach_hang_id' => $userId,
            'session_id'    => $sessionId,
            'tieu_de'       => 'Tư vấn ngày ' . date('d/m/Y H:i')
        ];
        $id = $this->create($data);
        return $this->find($id);
    }

    /**
     * Lấy danh sách tin nhắn của 1 phiên tư vấn kèm thông tin sản phẩm gợi ý
     */
    public function getMessagesBySessionId(int $sessionId): array {
        $sql = "SELECT tm.*,
                       sp.ten_san_pham, sp.slug as san_pham_slug, sp.gia, sp.gia_khuyen_mai, sp.hinh_anh, sp.chat_lieu, sp.kich_thuoc,
                       dm.ten_danh_muc
                FROM tin_nhan_tu_van tm
                LEFT JOIN san_pham sp ON tm.san_pham_goi_y_id = sp.id
                LEFT JOIN danh_muc dm ON sp.danh_muc_id = dm.id
                WHERE tm.phien_tu_van_id = :session_id
                ORDER BY tm.id ASC";

        $messages = $this->fetchAll($sql, [':session_id' => $sessionId]);

        foreach ($messages as &$m) {
            if (!empty($m['san_pham_goi_y_id']) && !empty($m['ten_san_pham'])) {
                $price = $m['gia_khuyen_mai'] > 0 ? $m['gia_khuyen_mai'] : $m['gia'];
                $m['san_pham_goi_y'] = [
                    'id'               => (int)$m['san_pham_goi_y_id'],
                    'ten_san_pham'     => $m['ten_san_pham'],
                    'ten_danh_muc'     => $m['ten_danh_muc'],
                    'gia'              => (float)$m['gia'],
                    'gia_khuyen_mai'   => (float)$m['gia_khuyen_mai'],
                    'gia_dinh_dang'    => format_currency($price),
                    'hinh_anh_url'     => !empty($m['hinh_anh']) ? asset_url($m['hinh_anh']) : asset_url('assets/images/default.jpg'),
                    'detail_url'       => base_url('san-pham/' . ($m['san_pham_slug'] ?? $m['san_pham_goi_y_id'])),
                    'chat_lieu'        => $m['chat_lieu'],
                    'kich_thuoc'       => $m['kich_thuoc']
                ];
            } else {
                $m['san_pham_goi_y'] = null;
            }
        }

        return $messages;
    }

    /**
     * Thêm tin nhắn mới vào phiên tư vấn
     */
    public function addMessage(int $sessionId, string $sender, string $content, ?int $suggestedProductId = null): int|string {
        $sql = "INSERT INTO tin_nhan_tu_van (phien_tu_van_id, nguoi_gui, noi_dung, san_pham_goi_y_id, thoi_gian)
                VALUES (:sid, :sender, :content, :pid, NOW())";
        $this->query($sql, [
            ':sid'     => $sessionId,
            ':sender'  => $sender,
            ':content' => $content,
            ':pid'     => $suggestedProductId
        ]);

        // Cập nhật thời gian phiên
        $this->query("UPDATE {$this->table} SET cap_nhat_luc = NOW() WHERE id = :id", [':id' => $sessionId]);

        return $this->db->lastInsertId();
    }

    /**
     * Tìm kiếm sản phẩm liên quan theo từ khóa (Xếp hạng theo độ khớp ngữ nghĩa Relevance Score)
     */
    public function findRelevantProducts(string $query, int $limit = 5): array {
        $query = trim($query);
        $cleanQuery = mb_strtolower($query);

        $params = [
            ':full_query'     => '%' . $query . '%',
            ':full_query_cat' => '%' . $query . '%'
        ];

        $scoreCalculations = [
            "(CASE WHEN sp.ten_san_pham LIKE :full_query THEN 100 ELSE 0 END)",
            "(CASE WHEN dm.ten_danh_muc LIKE :full_query_cat THEN 60 ELSE 0 END)"
        ];

        $whereConditions = [
            "(sp.ten_san_pham LIKE :full_query OR dm.ten_danh_muc LIKE :full_query_cat)"
        ];

        // Tách từ khóa
        $words = preg_split('/\s+/', $query);
        $keywords = array_filter($words, fn($w) => mb_strlen($w) >= 2);

        $idx = 0;
        foreach ($keywords as $kw) {
            $pName = ':p_name_' . $idx;
            $pCat  = ':p_cat_' . $idx;
            $pMat  = ':p_mat_' . $idx;

            $val = '%' . $kw . '%';
            $params[$pName] = $val;
            $params[$pCat]  = $val;
            $params[$pMat]  = $val;

            $scoreCalculations[] = "(CASE WHEN sp.ten_san_pham LIKE {$pName} THEN 30 ELSE 0 END)";
            $scoreCalculations[] = "(CASE WHEN dm.ten_danh_muc LIKE {$pCat} THEN 20 ELSE 0 END)";
            $scoreCalculations[] = "(CASE WHEN sp.chat_lieu LIKE {$pMat} THEN 10 ELSE 0 END)";

            $whereConditions[] = "(sp.ten_san_pham LIKE {$pName} OR dm.ten_danh_muc LIKE {$pCat} OR sp.chat_lieu LIKE {$pMat})";
            $idx++;
        }

        // Ưu tiên theo danh mục cốt lõi
        if (str_contains($cleanQuery, 'bàn ăn') || str_contains($cleanQuery, 'bếp') || (str_contains($cleanQuery, 'bàn') && !str_contains($cleanQuery, 'trà'))) {
            $scoreCalculations[] = "(CASE WHEN sp.danh_muc_id = 2 THEN 50 ELSE 0 END)";
        } elseif (str_contains($cleanQuery, 'sofa')) {
            $scoreCalculations[] = "(CASE WHEN sp.danh_muc_id = 1 THEN 50 ELSE 0 END)";
        } elseif (str_contains($cleanQuery, 'giường') || str_contains($cleanQuery, 'phòng ngủ')) {
            $scoreCalculations[] = "(CASE WHEN sp.danh_muc_id = 3 THEN 50 ELSE 0 END)";
        } elseif (str_contains($cleanQuery, 'tủ') || str_contains($cleanQuery, 'kệ') || str_contains($cleanQuery, 'tivi')) {
            $scoreCalculations[] = "(CASE WHEN sp.danh_muc_id = 4 THEN 50 ELSE 0 END)";
        } elseif (str_contains($cleanQuery, 'công thái học') || str_contains($cleanQuery, 'ergonomic') || str_contains($cleanQuery, 'văn phòng')) {
            $scoreCalculations[] = "(CASE WHEN sp.danh_muc_id = 5 THEN 50 ELSE 0 END)";
        } elseif (str_contains($cleanQuery, 'đèn')) {
            $scoreCalculations[] = "(CASE WHEN sp.danh_muc_id = 6 THEN 50 ELSE 0 END)";
        }

        $scoreSql = implode(' + ', $scoreCalculations);
        $whereSql = implode(' OR ', $whereConditions);

        $sql = "SELECT sp.*, dm.ten_danh_muc, dm.slug as danh_muc_slug,
                       ({$scoreSql}) as relevance_score
                FROM san_pham sp
                JOIN danh_muc dm ON sp.danh_muc_id = dm.id
                WHERE sp.trang_thai = 1 AND ({$whereSql})
                ORDER BY relevance_score DESC, sp.noi_bat DESC, sp.luot_xem DESC
                LIMIT " . (int)$limit;

        $products = $this->fetchAll($sql, $params);

        // Fallback: nếu không tìm thấy, lấy sản phẩm nổi bật
        if (empty($products)) {
            $sqlFallback = "SELECT sp.*, dm.ten_danh_muc, dm.slug as danh_muc_slug, 0 as relevance_score
                            FROM san_pham sp
                            JOIN danh_muc dm ON sp.danh_muc_id = dm.id
                            WHERE sp.trang_thai = 1
                            ORDER BY sp.noi_bat DESC, sp.id DESC LIMIT " . (int)$limit;
            $products = $this->fetchAll($sqlFallback);
        }

        return $products;
    }

    /**
     * Lấy danh sách các phiên tư vấn cho Admin
     */
    public function getSessionList(int $limit = 20, int $offset = 0): array {
        $sql = "SELECT p.*,
                       kh.ho_ten as ten_khach_hang, kh.email as email_khach_hang, kh.sdt as sdt_khach_hang,
                       COUNT(tm.id) as tong_tin_nhan,
                       MAX(tm.thoi_gian) as tin_nhan_cuoi_luc,
                       (SELECT noi_dung FROM tin_nhan_tu_van WHERE phien_tu_van_id = p.id ORDER BY id DESC LIMIT 1) as tin_nhan_cuoi
                FROM {$this->table} p
                LEFT JOIN khach_hang kh ON p.khach_hang_id = kh.id
                LEFT JOIN tin_nhan_tu_van tm ON p.id = tm.phien_tu_van_id
                GROUP BY p.id
                ORDER BY p.cap_nhat_luc DESC
                LIMIT :offset, :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Đếm tổng số phiên tư vấn
     */
    public function countSessions(): int {
        $row = $this->fetch("SELECT COUNT(*) as total FROM {$this->table}");
        return (int)($row['total'] ?? 0);
    }
}
