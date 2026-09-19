<?php
require_once __DIR__ . '/../core/Model.php';

class ChiTietDonHangModel extends Model {
    protected string $table = 'chi_tiet_don_hang';

    public function getByOrderId(int $orderId): array {
        $sql = "SELECT ct.*, sp.slug, sp.chat_lieu, sp.mau_sac
                FROM {$this->table} ct
                LEFT JOIN san_pham sp ON ct.san_pham_id = sp.id
                WHERE ct.don_hang_id = :order_id";
        return $this->fetchAll($sql, [':order_id' => $orderId]);
    }
}
