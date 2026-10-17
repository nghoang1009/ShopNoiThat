<?php
require_once __DIR__ . '/../core/Model.php';

class SanPhamModel extends Model {
    protected string $table = 'san_pham';

    /** Kích thước cấu trúc (cm) cho sản phẩm mẫu: slug => [dài, rộng/sâu, cao, hình dạng, độ cao đặt so với sàn] */
    private const SEED_SIZES = [
        'sofa-bang-da-y-cao-cap-milano' => [220, 90, 85, 'hop', 0],
        'sofa-goc-chu-l-boc-vai-ni-nordic' => [260, 160, 80, 'chu_l', 0],
        'ghe-thu-gian-bap-benh-poang' => [68, 82, 100, 'hop', 0],
        'bo-ban-an-6-ghe-mat-da-concorde' => [160, 80, 75, 'hop', 0],
        'ban-tra-tron-doi-khung-thep-ma-vang' => [70, 70, 45, 'tron', 0],
        'giuong-ngu-go-soi-nga-tokyo-1m8' => [180, 200, 90, 'hop', 0],
        'giuong-ngu-boc-nem-ni-luxury-royal' => [180, 200, 120, 'hop', 0],
        'tu-quan-ao-canh-kinh-4-canh' => [200, 60, 240, 'hop', 0],
        'ke-tivi-rut-2-dau-da-nang' => [160, 40, 45, 'hop', 0],
        'ghe-cong-thai-hoc-sihoo-m57' => [65, 65, 120, 'hop', 0],
        'den-chum-pha-le-nordic-sputnik' => [80, 80, 60, 'tron', 180],
        'den-tha-ban-an-ba-chao' => [80, 20, 20, 'hop', 120],
        'sofa-giuong-da-nang-thong-minh-cosy' => [180, 90, 85, 'hop', 0],
        'ghe-banh-thu-gian-nhung-retro-velvet' => [80, 75, 88, 'hop', 0],
        'ghe-luoi-hat-xop-giot-nuoc-king-size' => [110, 110, 90, 'tron', 0],
        'ban-an-mo-rong-thong-minh-marble' => [200, 85, 75, 'hop', 0],
        'ban-tra-sofa-nang-ha-lam-viec' => [110, 60, 45, 'hop', 0],
        'bo-ban-an-tron-mam-xoay-go-soi' => [135, 135, 75, 'tron', 0],
        'giuong-ngu-go-oc-cho-walnut-2m' => [200, 220, 105, 'hop', 0],
        'giuong-ngu-phan-kieu-nhat-tatami' => [180, 200, 35, 'hop', 0],
        'giuong-tang-tre-em-da-nang' => [140, 200, 160, 'hop', 0],
        'tu-giay-thong-minh-cua-lat-3-tang' => [100, 24, 120, 'hop', 0],
        'tu-ruou-phong-khach-canh-kinh' => [120, 40, 210, 'hop', 0],
        'ke-sach-treo-tuong-xuong-ca' => [45, 20, 140, 'hop', 30],
        'tu-dau-giuong-go-soi-nordic' => [45, 40, 50, 'hop', 0],
        'ghe-giam-doc-da-bo-that-luxury' => [75, 80, 130, 'hop', 0],
        'ban-lam-viec-nang-ha-dual-motor' => [140, 70, 95, 'hop', 0],
        'ghe-quay-bar-chan-ma-vang' => [40, 40, 70, 'tron', 0],
        'den-cay-dung-phong-khach-arco' => [160, 40, 210, 'hop', 0],
        'den-ngu-de-ban-gom-su-cao-cap' => [30, 30, 52, 'tron', 0],
    ];

    private static bool $sizeColumnsChecked = false;

    public function __construct() {
        parent::__construct();
        $this->ensureSizeColumns();
    }

    /**
     * Số đo cấu trúc dùng cho công cụ sắp xếp phòng 2D/3D:
     * dai_cm, rong_cm (chiều sâu), cao_cm, hinh_dang (hop|tron|chu_l), do_cao_cm (đèn treo, kệ treo tường...)
     */
    private function ensureSizeColumns(): void {
        if (self::$sizeColumnsChecked) {
            return;
        }
        self::$sizeColumnsChecked = true;

        try {
            $has = $this->fetch("SHOW COLUMNS FROM {$this->table} LIKE 'dai_cm'");
            if ($has) {
                return;
            }
            $this->db->exec("ALTER TABLE {$this->table}
                ADD COLUMN IF NOT EXISTS `dai_cm` INT NULL,
                ADD COLUMN IF NOT EXISTS `rong_cm` INT NULL,
                ADD COLUMN IF NOT EXISTS `cao_cm` INT NULL,
                ADD COLUMN IF NOT EXISTS `hinh_dang` VARCHAR(10) NOT NULL DEFAULT 'hop',
                ADD COLUMN IF NOT EXISTS `do_cao_cm` INT NOT NULL DEFAULT 0");

            $stmt = $this->db->prepare("UPDATE {$this->table} SET dai_cm = :d, rong_cm = :r, cao_cm = :c, hinh_dang = :h, do_cao_cm = :e
                                        WHERE slug = :slug AND dai_cm IS NULL");
            foreach (self::SEED_SIZES as $slug => [$d, $r, $c, $h, $e]) {
                $stmt->execute([':d' => $d, ':r' => $r, ':c' => $c, ':h' => $h, ':e' => $e, ':slug' => $slug]);
            }
        } catch (Throwable $ex) {
            error_log('[SanPhamModel] ensureSizeColumns: ' . $ex->getMessage());
        }
    }

    /**
     * Chuẩn hóa & kiểm tra số đo từ form/API (dữ liệu thô). Trả về mảng cột hoặc lỗi.
     * Để trống cả dài/rộng/cao = sản phẩm chưa có số đo (không dùng được trong công cụ sắp xếp phòng).
     */
    public static function parseSizeInput(array $in): array {
        $num = function ($v) {
            $v = trim((string)$v);
            return ($v === '') ? null : $v;
        };
        $d = $num($in['dai_cm'] ?? ''); $r = $num($in['rong_cm'] ?? ''); $c = $num($in['cao_cm'] ?? '');
        $shape = (string)($in['hinh_dang'] ?? 'hop');
        $elev = $num($in['do_cao_cm'] ?? '0') ?? '0';

        if ($d === null && $r === null && $c === null) {
            return ['data' => ['dai_cm' => null, 'rong_cm' => null, 'cao_cm' => null, 'hinh_dang' => 'hop', 'do_cao_cm' => 0], 'error' => null];
        }
        foreach (['Chiều dài' => $d, 'Chiều rộng/sâu' => $r, 'Chiều cao' => $c] as $label => $v) {
            if ($v === null || !ctype_digit($v) || (int)$v < 5 || (int)$v > 1000) {
                return ['data' => [], 'error' => "{$label} phải là số nguyên từ 5 đến 1000 (cm); để trống cả 3 ô nếu chưa có số đo."];
            }
        }
        if (!in_array($shape, ['hop', 'tron', 'chu_l'], true)) {
            return ['data' => [], 'error' => 'Hình dạng không hợp lệ.'];
        }
        if (!ctype_digit($elev) || (int)$elev > 400) {
            return ['data' => [], 'error' => 'Độ cao đặt so với sàn phải từ 0 đến 400 (cm).'];
        }
        return ['data' => [
            'dai_cm' => (int)$d, 'rong_cm' => (int)$r, 'cao_cm' => (int)$c,
            'hinh_dang' => $shape, 'do_cao_cm' => (int)$elev,
        ], 'error' => null];
    }

    /**
     * Sản phẩm đang bán có số đo - dữ liệu cho công cụ sắp xếp phòng
     */
    public function getPlannerProducts(): array {
        return $this->fetchAll(
            "SELECT sp.id, sp.ten_san_pham, sp.slug, sp.danh_muc_id, dm.ten_danh_muc, sp.gia, sp.gia_khuyen_mai,
                    sp.so_luong_ton, sp.mau_sac, sp.kich_thuoc, sp.hinh_anh,
                    sp.dai_cm, sp.rong_cm, sp.cao_cm, sp.hinh_dang, sp.do_cao_cm
             FROM {$this->table} sp
             JOIN danh_muc dm ON dm.id = sp.danh_muc_id
             WHERE sp.trang_thai = 1 AND sp.dai_cm IS NOT NULL AND sp.rong_cm IS NOT NULL AND sp.cao_cm IS NOT NULL
             ORDER BY sp.danh_muc_id, sp.id"
        );
    }

    /**
     * Lọc và tìm kiếm danh sách sản phẩm nâng cao
     */
    public function filterProducts(array $filters = []): array {
        $sql = "SELECT sp.*, dm.ten_danh_muc, dm.slug as danh_muc_slug, ncc.ten_nha_cung_cap
                FROM {$this->table} sp
                JOIN danh_muc dm ON sp.danh_muc_id = dm.id
                LEFT JOIN nha_cung_cap ncc ON sp.nha_cung_cap_id = ncc.id
                WHERE sp.trang_thai = 1";

        $params = [];

        // Lọc theo danh mục (ID hoặc Slug)
        if (!empty($filters['danh_muc_id'])) {
            $sql .= " AND sp.danh_muc_id = :danh_muc_id";
            $params[':danh_muc_id'] = $filters['danh_muc_id'];
        } elseif (!empty($filters['danh_muc'])) {
            $sql .= " AND (dm.slug = :dm_slug OR dm.ten_danh_muc LIKE :dm_name)";
            $params[':dm_slug'] = $filters['danh_muc'];
            $params[':dm_name'] = '%' . $filters['danh_muc'] . '%';
        }

        // Lọc theo khoảng giá
        if (!empty($filters['gia_min']) && is_numeric($filters['gia_min'])) {
            $sql .= " AND (CASE WHEN sp.gia_khuyen_mai > 0 THEN sp.gia_khuyen_mai ELSE sp.gia END) >= :gia_min";
            $params[':gia_min'] = (float)$filters['gia_min'];
        }
        if (!empty($filters['gia_max']) && is_numeric($filters['gia_max'])) {
            $sql .= " AND (CASE WHEN sp.gia_khuyen_mai > 0 THEN sp.gia_khuyen_mai ELSE sp.gia END) <= :gia_max";
            $params[':gia_max'] = (float)$filters['gia_max'];
        }

        // Lọc theo chất liệu
        if (!empty($filters['chat_lieu'])) {
            $sql .= " AND sp.chat_lieu LIKE :chat_lieu";
            $params[':chat_lieu'] = '%' . $filters['chat_lieu'] . '%';
        }

        // Lọc theo màu sắc
        if (!empty($filters['mau_sac'])) {
            $sql .= " AND sp.mau_sac LIKE :mau_sac";
            $params[':mau_sac'] = '%' . $filters['mau_sac'] . '%';
        }

        // Tìm kiếm từ khóa (Keyword)
        if (!empty($filters['keyword'])) {
            $sql .= " AND (sp.ten_san_pham LIKE :keyword OR sp.mo_ta LIKE :keyword2 OR dm.ten_danh_muc LIKE :keyword3)";
            $params[':keyword'] = '%' . $filters['keyword'] . '%';
            $params[':keyword2'] = '%' . $filters['keyword'] . '%';
            $params[':keyword3'] = '%' . $filters['keyword'] . '%';
        }

        // Sắp xếp
        $sort = $filters['sort'] ?? 'moi_nhat';
        switch ($sort) {
            case 'gia_asc':
                $sql .= " ORDER BY (CASE WHEN sp.gia_khuyen_mai > 0 THEN sp.gia_khuyen_mai ELSE sp.gia END) ASC";
                break;
            case 'gia_desc':
                $sql .= " ORDER BY (CASE WHEN sp.gia_khuyen_mai > 0 THEN sp.gia_khuyen_mai ELSE sp.gia END) DESC";
                break;
            case 'xem_nhieu':
                $sql .= " ORDER BY sp.luot_xem DESC";
                break;
            case 'moi_nhat':
            default:
                $sql .= " ORDER BY sp.id DESC";
                break;
        }

        // Giới hạn số lượng
        if (!empty($filters['limit'])) {
            $limit = (int)$filters['limit'];
            $offset = (int)($filters['offset'] ?? 0);
            $sql .= " LIMIT {$offset}, {$limit}";
        }

        return $this->fetchAll($sql, $params);
    }

    /**
     * Chi tiết sản phẩm kèm danh mục và nhà cung cấp
     */
    public function getDetailWithRelations($idOrSlug): ?array {
        $field = is_numeric($idOrSlug) ? 'sp.id' : 'sp.slug';
        $sql = "SELECT sp.*, dm.ten_danh_muc, dm.slug as danh_muc_slug, ncc.ten_nha_cung_cap
                FROM {$this->table} sp
                JOIN danh_muc dm ON sp.danh_muc_id = dm.id
                LEFT JOIN nha_cung_cap ncc ON sp.nha_cung_cap_id = ncc.id
                WHERE {$field} = :idOrSlug LIMIT 1";
        return $this->fetch($sql, [':idOrSlug' => $idOrSlug]);
    }

    /**
     * Lấy danh sách sản phẩm nổi bật
     */
    public function getFeaturedProducts(int $limit = 8): array {
        $sql = "SELECT sp.*, dm.ten_danh_muc, dm.slug as danh_muc_slug
                FROM {$this->table} sp
                JOIN danh_muc dm ON sp.danh_muc_id = dm.id
                WHERE sp.trang_thai = 1 AND sp.noi_bat = 1
                ORDER BY sp.id DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách sản phẩm mới nhất
     */
    public function getNewProducts(int $limit = 8): array {
        $sql = "SELECT sp.*, dm.ten_danh_muc, dm.slug as danh_muc_slug
                FROM {$this->table} sp
                JOIN danh_muc dm ON sp.danh_muc_id = dm.id
                WHERE sp.trang_thai = 1
                ORDER BY sp.id DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Lấy sản phẩm đang có khuyến mãi
     */
    public function getSaleProducts(int $limit = 8): array {
        $sql = "SELECT sp.*, dm.ten_danh_muc, dm.slug as danh_muc_slug
                FROM {$this->table} sp
                JOIN danh_muc dm ON sp.danh_muc_id = dm.id
                WHERE sp.trang_thai = 1 AND sp.gia_khuyen_mai > 0 AND sp.gia_khuyen_mai < sp.gia
                ORDER BY ((sp.gia - sp.gia_khuyen_mai) / sp.gia) DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Lấy sản phẩm liên quan theo danh mục
     */
    public function getRelatedProducts(int $categoryId, int $excludeId, int $limit = 4): array {
        $sql = "SELECT sp.*, dm.ten_danh_muc, dm.slug as danh_muc_slug
                FROM {$this->table} sp
                JOIN danh_muc dm ON sp.danh_muc_id = dm.id
                WHERE sp.trang_thai = 1 AND sp.danh_muc_id = :dm_id AND sp.id != :exclude_id
                ORDER BY sp.id DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':dm_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Tăng số lượt xem sản phẩm
     */
    public function incrementViews(int $id): void {
        $sql = "UPDATE {$this->table} SET luot_xem = luot_xem + 1 WHERE id = :id";
        $this->query($sql, [':id' => $id]);
    }

    /**
     * Đếm tổng số sản phẩm sau khi lọc (dùng cho phân trang API & Web)
     */
    public function countFilteredProducts(array $filters = []): int {
        $sql = "SELECT COUNT(*) as total
                FROM {$this->table} sp
                JOIN danh_muc dm ON sp.danh_muc_id = dm.id
                LEFT JOIN nha_cung_cap ncc ON sp.nha_cung_cap_id = ncc.id
                WHERE sp.trang_thai = 1";

        $params = [];

        if (!empty($filters['danh_muc_id'])) {
            $sql .= " AND sp.danh_muc_id = :danh_muc_id";
            $params[':danh_muc_id'] = $filters['danh_muc_id'];
        } elseif (!empty($filters['danh_muc'])) {
            $sql .= " AND (dm.slug = :dm_slug OR dm.ten_danh_muc LIKE :dm_name)";
            $params[':dm_slug'] = $filters['danh_muc'];
            $params[':dm_name'] = '%' . $filters['danh_muc'] . '%';
        }

        if (!empty($filters['gia_min']) && is_numeric($filters['gia_min'])) {
            $sql .= " AND (CASE WHEN sp.gia_khuyen_mai > 0 THEN sp.gia_khuyen_mai ELSE sp.gia END) >= :gia_min";
            $params[':gia_min'] = (float)$filters['gia_min'];
        }
        if (!empty($filters['gia_max']) && is_numeric($filters['gia_max'])) {
            $sql .= " AND (CASE WHEN sp.gia_khuyen_mai > 0 THEN sp.gia_khuyen_mai ELSE sp.gia END) <= :gia_max";
            $params[':gia_max'] = (float)$filters['gia_max'];
        }

        if (!empty($filters['chat_lieu'])) {
            $sql .= " AND sp.chat_lieu LIKE :chat_lieu";
            $params[':chat_lieu'] = '%' . $filters['chat_lieu'] . '%';
        }

        if (!empty($filters['mau_sac'])) {
            $sql .= " AND sp.mau_sac LIKE :mau_sac";
            $params[':mau_sac'] = '%' . $filters['mau_sac'] . '%';
        }

        if (!empty($filters['keyword'])) {
            $sql .= " AND (sp.ten_san_pham LIKE :keyword OR sp.mo_ta LIKE :keyword2 OR dm.ten_danh_muc LIKE :keyword3)";
            $params[':keyword'] = '%' . $filters['keyword'] . '%';
            $params[':keyword2'] = '%' . $filters['keyword'] . '%';
            $params[':keyword3'] = '%' . $filters['keyword'] . '%';
        }

        $row = $this->fetch($sql, $params);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Lấy danh sách chất liệu và màu sắc độc nhất để hiển thị bộ lọc
     */
    public function getDistinctAttributes(): array {
        $materials = $this->fetchAll("SELECT DISTINCT chat_lieu FROM {$this->table} WHERE chat_lieu IS NOT NULL AND chat_lieu != '' AND trang_thai = 1");
        $colors = $this->fetchAll("SELECT DISTINCT mau_sac FROM {$this->table} WHERE mau_sac IS NOT NULL AND mau_sac != '' AND trang_thai = 1");

        return [
            'materials' => array_column($materials, 'chat_lieu'),
            'colors'    => array_column($colors, 'mau_sac')
        ];
    }
}
