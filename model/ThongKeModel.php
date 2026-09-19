<?php
require_once __DIR__ . '/../core/Model.php';

class ThongKeModel extends Model {
    protected string $table = 'don_hang';

    /**
     * Tổng hợp các chỉ số tổng quan Dashboard nâng cao
     */
    public function getOverviewSummary(): array {
        // 1. Doanh thu hôm nay
        $todayRow = $this->fetch("SELECT SUM(tong_tien) as today_revenue FROM don_hang WHERE trang_thai = 'hoan_tat' AND DATE(ngay_dat) = CURDATE()");
        $revenueToday = (float)($todayRow['today_revenue'] ?? 0);

        // 2. Doanh thu tháng này
        $monthRevenueRow = $this->fetch("SELECT SUM(tong_tien) as month_revenue FROM don_hang WHERE trang_thai = 'hoan_tat' AND MONTH(ngay_dat) = MONTH(CURRENT_DATE()) AND YEAR(ngay_dat) = YEAR(CURRENT_DATE())");
        $monthRevenue = (float)($monthRevenueRow['month_revenue'] ?? 0);

        // 3. Doanh thu toàn bộ
        $revenueRow = $this->fetch("SELECT SUM(tong_tien) as total_revenue FROM don_hang WHERE trang_thai = 'hoan_tat'");
        $totalRevenue = (float)($revenueRow['total_revenue'] ?? 0);

        // 4. Đơn hàng mới chờ xử lý
        $pendingRow = $this->fetch("SELECT COUNT(*) as pending_count FROM don_hang WHERE trang_thai = 'cho_xu_ly'");
        $pendingOrders = (int)($pendingRow['pending_count'] ?? 0);

        // 5. Đơn hàng đang vận chuyển/giao
        $shippingRow = $this->fetch("SELECT COUNT(*) as shipping_count FROM don_hang WHERE trang_thai = 'dang_giao'");
        $shippingOrders = (int)($shippingRow['shipping_count'] ?? 0);

        // 6. Đơn hoàn tất & Đơn hủy
        $completedRow = $this->fetch("SELECT COUNT(*) as c_count FROM don_hang WHERE trang_thai = 'hoan_tat'");
        $completedOrders = (int)($completedRow['c_count'] ?? 0);

        $cancelledRow = $this->fetch("SELECT COUNT(*) as c_count FROM don_hang WHERE trang_thai = 'da_huy'");
        $cancelledOrders = (int)($cancelledRow['c_count'] ?? 0);

        $totalOrdersRow = $this->fetch("SELECT COUNT(*) as total_orders FROM don_hang");
        $totalOrders = (int)($totalOrdersRow['total_orders'] ?? 0);

        // 7. Sản phẩm sắp hết hàng (tồn kho <= 5)
        $lowStockRows = $this->fetchAll("SELECT id, ten_san_pham, hinh_anh, gia, so_luong_ton FROM san_pham WHERE so_luong_ton <= 5 AND trang_thai = 1 ORDER BY so_luong_ton ASC LIMIT 6");
        $lowStockCountRow = $this->fetch("SELECT COUNT(*) as total FROM san_pham WHERE so_luong_ton <= 5 AND trang_thai = 1");
        $lowStockCount = (int)($lowStockCountRow['total'] ?? 0);

        // 8. Tổng sản phẩm & tổng khách hàng
        $totalProductsRow = $this->fetch("SELECT COUNT(*) as total_products, SUM(so_luong_ton) as total_stock FROM san_pham WHERE trang_thai = 1");
        $totalProducts = (int)($totalProductsRow['total_products'] ?? 0);
        $totalStock = (int)($totalProductsRow['total_stock'] ?? 0);

        $totalCustomersRow = $this->fetch("SELECT COUNT(*) as total_customers FROM khach_hang");
        $totalCustomers = (int)($totalCustomersRow['total_customers'] ?? 0);

        return [
            'revenue_today'      => $revenueToday,
            'month_revenue'      => $monthRevenue,
            'total_revenue'      => $totalRevenue,
            'pending_orders'     => $pendingOrders,
            'shipping_orders'    => $shippingOrders,
            'completed_orders'   => $completedOrders,
            'cancelled_orders'   => $cancelledOrders,
            'total_orders'       => $totalOrders,
            'low_stock_count'    => $lowStockCount,
            'low_stock_products' => $lowStockRows,
            'total_products'     => $totalProducts,
            'total_stock'        => $totalStock,
            'total_customers'    => $totalCustomers
        ];
    }

    /**
     * Lấy dữ liệu thống kê doanh thu và đơn hàng theo khoảng ngày và nhóm (ngay | thang)
     */
    public function getRevenueByDateRange(?string $fromDate = null, ?string $toDate = null, string $groupBy = 'ngay'): array {
        if (empty($fromDate)) {
            $fromDate = ($groupBy === 'thang') ? date('Y-m-01', strtotime('-5 months')) : date('Y-m-d', strtotime('-6 days'));
        }
        if (empty($toDate)) {
            $toDate = date('Y-m-d');
        }

        if ($groupBy === 'thang') {
            $sql = "SELECT DATE_FORMAT(ngay_dat, '%Y-%m') as nhan_thoi_gian,
                           DATE_FORMAT(ngay_dat, 'Tháng %m/%Y') as ngay,
                           SUM(CASE WHEN trang_thai = 'hoan_tat' THEN tong_tien ELSE 0 END) as doanh_thu,
                           COUNT(id) as tong_so_don,
                           SUM(CASE WHEN trang_thai = 'hoan_tat' THEN 1 ELSE 0 END) as don_thanh_cong,
                           SUM(CASE WHEN trang_thai = 'da_huy' THEN 1 ELSE 0 END) as don_huy
                    FROM don_hang
                    WHERE DATE(ngay_dat) >= :from_date AND DATE(ngay_dat) <= :to_date
                    GROUP BY DATE_FORMAT(ngay_dat, '%Y-%m')
                    ORDER BY nhan_thoi_gian ASC";
        } else {
            $sql = "SELECT DATE(ngay_dat) as ngay,
                           SUM(CASE WHEN trang_thai = 'hoan_tat' THEN tong_tien ELSE 0 END) as doanh_thu,
                           COUNT(id) as tong_so_don,
                           SUM(CASE WHEN trang_thai = 'hoan_tat' THEN 1 ELSE 0 END) as don_thanh_cong,
                           SUM(CASE WHEN trang_thai = 'da_huy' THEN 1 ELSE 0 END) as don_huy
                    FROM don_hang
                    WHERE DATE(ngay_dat) >= :from_date AND DATE(ngay_dat) <= :to_date
                    GROUP BY DATE(ngay_dat)
                    ORDER BY ngay ASC";
        }

        return $this->fetchAll($sql, [
            ':from_date' => $fromDate,
            ':to_date'   => $toDate
        ]);
    }

    /**
     * Thống kê tỷ lệ phương thức thanh toán
     */
    public function getPaymentMethodStats(): array {
        $sql = "SELECT COALESCE(pt.ten_pt, dh.phuong_thuc_thanh_toan) as ten_phuong_thuc,
                       COUNT(dh.id) as so_luong,
                       SUM(dh.tong_tien) as tong_tien
                FROM don_hang dh
                LEFT JOIN phuong_thuc_thanh_toan pt ON dh.phuong_thuc_thanh_toan_id = pt.id
                GROUP BY ten_phuong_thuc
                ORDER BY so_luong DESC";
        return $this->fetchAll($sql);
    }

    /**
     * Thống kê tình trạng giao hàng
     */
    public function getShippingStatusStats(): array {
        $sql = "SELECT vc.trang_thai_giao_hang, COUNT(vc.id) as so_luong
                FROM van_chuyen vc
                GROUP BY vc.trang_thai_giao_hang";
        return $this->fetchAll($sql);
    }

    /**
     * Top sản phẩm bán chạy nhất kèm lọc theo thời gian
     */
    public function getTopSellingProducts(int $limit = 10, ?string $fromDate = null, ?string $toDate = null): array {
        $sql = "SELECT ct.san_pham_id, ct.ten_san_pham, ct.hinh_anh,
                       SUM(ct.so_luong) as tong_da_ban,
                       SUM(ct.thanh_tien) as tong_doanh_thu,
                       sp.gia, sp.gia_khuyen_mai, sp.so_luong_ton
                FROM chi_tiet_don_hang ct
                JOIN don_hang dh ON ct.don_hang_id = dh.id
                LEFT JOIN san_pham sp ON ct.san_pham_id = sp.id
                WHERE dh.trang_thai = 'hoan_tat'";

        $params = [];
        if (!empty($fromDate)) {
            $sql .= " AND DATE(dh.ngay_dat) >= :from_date";
            $params[':from_date'] = $fromDate;
        }
        if (!empty($toDate)) {
            $sql .= " AND DATE(dh.ngay_dat) <= :to_date";
            $params[':to_date'] = $toDate;
        }

        $sql .= " GROUP BY ct.san_pham_id, ct.ten_san_pham, ct.hinh_anh
                 ORDER BY tong_da_ban DESC
                 LIMIT " . (int)$limit;

        return $this->fetchAll($sql, $params);
    }
}
