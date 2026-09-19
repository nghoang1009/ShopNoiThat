<?php
require_once __DIR__ . '/../core/Model.php';

class SanPhamModel extends Model {
    protected string $table = 'san_pham';

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
