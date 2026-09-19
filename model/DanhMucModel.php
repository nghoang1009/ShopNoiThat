<?php
require_once __DIR__ . '/../core/Model.php';

class DanhMucModel extends Model {
    protected string $table = 'danh_muc';

    /**
     * Lấy tất cả danh mục đang hoạt động kèm số lượng sản phẩm
     */
    public function getActiveCategories(): array {
        $sql = "SELECT dm.*, COUNT(sp.id) as tong_san_pham
                FROM {$this->table} dm
                LEFT JOIN san_pham sp ON dm.id = sp.danh_muc_id AND sp.trang_thai = 1
                WHERE dm.trang_thai = 1
                GROUP BY dm.id
                ORDER BY dm.id ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Lấy toàn bộ danh mục kèm số lượng sản phẩm (bao gồm cả ngừng hoạt động cho quản trị)
     */
    public function getCategoriesWithProductCount(): array {
        $sql = "SELECT dm.*, COUNT(sp.id) as tong_san_pham
                FROM {$this->table} dm
                LEFT JOIN san_pham sp ON dm.id = sp.danh_muc_id
                GROUP BY dm.id
                ORDER BY dm.id ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Tìm danh mục theo Slug
     */
    public function findBySlug(string $slug): ?array {
        return $this->findBy('slug', $slug);
    }
}
