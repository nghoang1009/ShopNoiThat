<?php
require_once __DIR__ . '/../core/Controller.php';

class ThongKeApiController extends Controller {
    private ThongKeModel $thongKeModel;

    public function __construct() {
        $this->thongKeModel = $this->model('ThongKeModel');
    }

    /**
     * GET /api/v1/thong-ke/tong-quan
     * Số liệu nhanh cho các thẻ Dashboard
     */
    public function summary(): void {
        $this->requireAdminAuth();

        $summary = $this->thongKeModel->getOverviewSummary();
        $summary['total_revenue_formatted'] = format_currency($summary['total_revenue']);
        $summary['month_revenue_formatted'] = format_currency($summary['month_revenue']);
        $summary['revenue_today_formatted'] = format_currency($summary['revenue_today']);

        $topSelling = $this->thongKeModel->getTopSellingProducts(5);
        foreach ($topSelling as &$p) {
            $p['tong_doanh_thu_dinh_dang'] = format_currency($p['tong_doanh_thu']);
            $p['hinh_anh_url'] = !empty($p['hinh_anh']) ? asset_url($p['hinh_anh']) : asset_url('assets/images/default.jpg');
        }

        $this->json([
            'success' => true,
            'data'    => [
                'summary'     => $summary,
                'top_selling' => $topSelling
            ],
            'message' => 'Lấy thống kê tổng quan thành công.'
        ], 200);
    }

    /**
     * GET /api/v1/thong-ke/doanh-thu?tu_ngay=...&den_ngay=...&nhom_theo=ngay|thang
     * Dữ liệu biểu đồ doanh thu
     */
    public function revenue(): void {
        $this->requireAdminAuth();

        $groupBy = in_array($this->getQuery('nhom_theo'), ['thang', 'month']) ? 'thang' : 'ngay';
        $defaultFrom = ($groupBy === 'thang') ? date('Y-m-01', strtotime('-5 months')) : date('Y-m-d', strtotime('-6 days'));

        $fromDate = $this->getQuery('tu_ngay', $defaultFrom);
        $toDate = $this->getQuery('den_ngay', date('Y-m-d'));

        $chartData = $this->thongKeModel->getRevenueByDateRange($fromDate, $toDate, $groupBy);

        $this->json([
            'success' => true,
            'data'    => [
                'chart_data' => $chartData,
                'nhom_theo'  => $groupBy,
                'from_date'  => $fromDate,
                'to_date'    => $toDate
            ],
            'message' => 'Lấy dữ liệu biểu đồ doanh thu thành công.'
        ], 200);
    }

    /**
     * GET /api/v1/thong-ke/san-pham-ban-chay?tu_ngay=...&den_ngay=...&limit=10
     * Top sản phẩm bán chạy
     */
    public function topSelling(): void {
        $this->requireAdminAuth();

        $limit = max(1, min(50, (int)$this->getQuery('limit', 10)));
        $fromDate = $this->getQuery('tu_ngay');
        $toDate = $this->getQuery('den_ngay');

        $topProducts = $this->thongKeModel->getTopSellingProducts($limit, $fromDate, $toDate);

        foreach ($topProducts as &$tp) {
            $tp['tong_doanh_thu_dinh_dang'] = format_currency($tp['tong_doanh_thu']);
            $tp['hinh_anh_url'] = !empty($tp['hinh_anh']) ? asset_url($tp['hinh_anh']) : asset_url('assets/images/default.jpg');
        }

        $this->json([
            'success' => true,
            'data'    => $topProducts,
            'message' => 'Lấy danh sách sản phẩm bán chạy thành công.'
        ], 200);
    }

    /**
     * GET /api/v1/thong-ke/dashboard
     * Toàn bộ dữ liệu tổng hợp cho trang Dashboard
     */
    public function dashboard(): void {
        $this->requireAdminAuth();

        $groupBy = in_array($this->getQuery('nhom_theo'), ['thang', 'month']) ? 'thang' : 'ngay';
        $defaultFrom = ($groupBy === 'thang') ? date('Y-m-01', strtotime('-5 months')) : date('Y-m-d', strtotime('-6 days'));

        $fromDate = $this->getQuery('tu_ngay', $defaultFrom);
        $toDate = $this->getQuery('den_ngay', date('Y-m-d'));

        $summary = $this->thongKeModel->getOverviewSummary();
        $summary['revenue_today_formatted'] = format_currency($summary['revenue_today']);
        $summary['month_revenue_formatted'] = format_currency($summary['month_revenue']);
        $summary['total_revenue_formatted'] = format_currency($summary['total_revenue']);

        foreach ($summary['low_stock_products'] as &$lsp) {
            $lsp['gia_dinh_dang'] = format_currency($lsp['gia']);
            $lsp['hinh_anh_url'] = !empty($lsp['hinh_anh']) ? asset_url($lsp['hinh_anh']) : asset_url('assets/images/default.jpg');
        }

        $chartData = $this->thongKeModel->getRevenueByDateRange($fromDate, $toDate, $groupBy);
        $topSelling = $this->thongKeModel->getTopSellingProducts(5, $fromDate, $toDate);
        foreach ($topSelling as &$tp) {
            $tp['tong_doanh_thu_dinh_dang'] = format_currency($tp['tong_doanh_thu']);
            $tp['hinh_anh_url'] = !empty($tp['hinh_anh']) ? asset_url($tp['hinh_anh']) : asset_url('assets/images/default.jpg');
        }

        $paymentStats = $this->thongKeModel->getPaymentMethodStats();
        $shippingStats = $this->thongKeModel->getShippingStatusStats();

        $this->json([
            'success' => true,
            'data'    => [
                'summary'        => $summary,
                'chart_data'     => $chartData,
                'top_selling'    => $topSelling,
                'payment_stats'  => $paymentStats,
                'shipping_stats' => $shippingStats,
                'date_range'     => [
                    'from'      => $fromDate,
                    'to'        => $toDate,
                    'nhom_theo' => $groupBy
                ]
            ],
            'message' => 'Lấy dữ liệu Dashboard thành công.'
        ], 200);
    }
}
