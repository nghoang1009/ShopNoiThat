<?php
require_once __DIR__ . '/../../../includes/admin_header.php';
require_once __DIR__ . '/../../../includes/admin_sidebar.php';
?>

<div class="page-header" style="flex-wrap: wrap; gap: 15px;">
    <div>
        <h1 class="page-title">Bảng Điều Khiển & Thống Kê</h1>
        <p style="color: #64748b; font-size: 14px;">Báo cáo số liệu kinh doanh, vận chuyển và đối soát doanh thu</p>
    </div>

    <!-- Bộ lọc khoảng ngày -->
    <form action="<?php echo base_url('admin'); ?>" method="GET" style="display:flex; align-items:center; gap:8px; background:#fff; padding:6px 12px; border-radius:6px; border:1px solid #cbd5e1;">
        <span style="font-size:12px; color:#64748b; font-weight:600;">Từ:</span>
        <input type="date" name="tu_ngay" value="<?php echo htmlspecialchars($fromDate); ?>" style="border:1px solid #cbd5e1; padding:4px 8px; border-radius:4px; font-size:13px;">
        <span style="font-size:12px; color:#64748b; font-weight:600;">Đến:</span>
        <input type="date" name="den_ngay" value="<?php echo htmlspecialchars($toDate); ?>" style="border:1px solid #cbd5e1; padding:4px 8px; border-radius:4px; font-size:13px;">
        <button type="submit" class="btn-admin btn-admin-primary btn-admin-sm">Lọc</button>
    </form>
</div>

<!-- 5 THẺ SỐ LIỆU NHANH -->
<div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));">
    <!-- Doanh thu hôm nay -->
    <div class="stat-card">
        <div>
            <div class="stat-title">Doanh Thu Hôm Nay</div>
            <div class="stat-value" style="color: #15803d; font-size: 22px;"><?php echo format_currency($summary['revenue_today']); ?></div>
            <small style="color: #64748b;">Tháng này: <strong><?php echo format_currency($summary['month_revenue']); ?></strong></small>
        </div>
        <div class="stat-icon icon-revenue">💵</div>
    </div>

    <!-- Đơn chờ xử lý -->
    <div class="stat-card">
        <div>
            <div class="stat-title">Đơn Mới Chờ Xử Lý</div>
            <div class="stat-value" style="color: #b45309;"><?php echo $summary['pending_orders']; ?></div>
            <small style="color: #64748b;">Tổng đơn: <?php echo $summary['total_orders']; ?></small>
        </div>
        <div class="stat-icon icon-orders">📦</div>
    </div>

    <!-- Đang giao hàng -->
    <div class="stat-card">
        <div>
            <div class="stat-title">Đang Vận Chuyển</div>
            <div class="stat-value" style="color: #0369a1;"><?php echo $summary['shipping_orders']; ?></div>
            <small style="color: #10b981;">Hoàn tất: <?php echo $summary['completed_orders']; ?></small>
        </div>
        <div class="stat-icon icon-products">🚚</div>
    </div>

    <!-- Cảnh báo sắp hết hàng -->
    <div class="stat-card" style="<?php echo ($summary['low_stock_count'] > 0) ? 'border-left: 4px solid #ef4444;' : ''; ?>">
        <div>
            <div class="stat-title" style="color: #b91c1c;">Sắp Hết Hàng (≤5)</div>
            <div class="stat-value" style="color: #ef4444;"><?php echo $summary['low_stock_count']; ?></div>
            <small style="color: #64748b;">Kho tổng: <?php echo $summary['total_stock']; ?> món</small>
        </div>
        <div class="stat-icon" style="background:#fee2e2; color:#b91c1c;">⚠️</div>
    </div>

    <!-- Khách hàng -->
    <div class="stat-card">
        <div>
            <div class="stat-title">Khách Hàng</div>
            <div class="stat-value"><?php echo $summary['total_customers']; ?></div>
            <small style="color: #7e22ce;">Thành viên</small>
        </div>
        <div class="stat-icon icon-customers">👥</div>
    </div>
</div>

<!-- BIỂU ĐỒ DOANH THU & TOP BÁN CHẠY -->
<div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 25px; margin-bottom: 30px;">
    <!-- Biểu đồ doanh thu Canvas -->
    <div class="card-box" style="margin-bottom: 0;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px;">
            <h3 style="font-size: 16px; font-weight: 700; color: #1e293b;">
                📈 Biểu Đồ Doanh Thu & Đơn Hàng (<?php echo date('d/m', strtotime($fromDate)); ?> - <?php echo date('d/m/Y', strtotime($toDate)); ?>)
            </h3>
            <span style="font-size:12px; color:#64748b;">(Đơn vị: Triệu VNĐ)</span>
        </div>
        <canvas id="dashboardRevenueChart" width="600" height="280" style="width:100%; height:auto;"></canvas>
    </div>

    <!-- Top sản phẩm bán chạy -->
    <div class="card-box" style="margin-bottom: 0;">
        <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 20px;">
            🏆 Top Sản Phẩm Bán Chạy Nhất
        </h3>
        <?php if (empty($topProducts)): ?>
            <p style="color: #94a3b8; font-size: 14px; text-align: center; padding: 40px 0;">Chưa có dữ liệu bán chạy.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <?php foreach ($topProducts as $idx => $tp): ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-weight: 700; color: #935832; font-size: 13px; width: 18px;">#<?php echo $idx + 1; ?></span>
                            <img src="<?php echo !empty($tp['hinh_anh']) ? asset_url($tp['hinh_anh']) : asset_url('assets/images/default.jpg'); ?>" onerror="this.src='https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=100&q=80'" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover; border:1px solid #e2e8f0;">
                            <div>
                                <div style="font-size: 13px; font-weight: 600; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($tp['ten_san_pham']); ?></div>
                                <small style="color: #64748b;">Đã bán: <strong><?php echo $tp['tong_da_ban']; ?></strong> chiếc</small>
                            </div>
                        </div>
                        <strong style="color: #e67e22; font-size: 13px;"><?php echo format_currency($tp['tong_doanh_thu']); ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- SẢN PHẨM SẮP HẾT HÀNG & PHÂN BỔ THANH TOÁN -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px;">
    <!-- Cảnh báo tồn kho thấp -->
    <div class="card-box" style="margin-bottom: 0;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h3 style="font-size: 15px; font-weight: 700; color: #b91c1c;">
                ⚠️ Sản Phẩm Tồn Kho Thấp (≤ 5 cái)
            </h3>
            <a href="<?php echo base_url('admin/san-pham'); ?>" style="font-size:12px; color:#935832; font-weight:600;">Xem kho &rarr;</a>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Giá</th>
                    <th style="text-align:right;">Tồn</th>
                    <th style="text-align:right;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($summary['low_stock_products'])): ?>
                    <tr>
                        <td colspan="4" style="text-align:center; color:#10b981; padding:20px;">✓ Tất cả sản phẩm đều đủ tồn kho!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($summary['low_stock_products'] as $lsp): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($lsp['ten_san_pham']); ?></strong>
                            </td>
                            <td><?php echo format_currency($lsp['gia']); ?></td>
                            <td style="text-align:right;">
                                <span class="badge badge-danger"><?php echo $lsp['so_luong_ton']; ?></span>
                            </td>
                            <td style="text-align:right;">
                                <a href="<?php echo base_url('admin/san-pham/sua/' . $lsp['id']); ?>" class="btn-admin btn-admin-primary btn-admin-sm">Nhập thêm</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Thống kê Thanh toán & Vận chuyển -->
    <div class="card-box" style="margin-bottom: 0;">
        <h3 style="font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">
            💳 Tỷ Lệ Thanh Toán & Giao Hàng
        </h3>

        <div style="margin-bottom: 20px;">
            <div style="font-size:13px; font-weight:600; color:#64748b; margin-bottom:8px;">Phương thức thanh toán:</div>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <?php foreach ($paymentStats as $ps): ?>
                    <div style="display:flex; justify-content:space-between; font-size:13px; padding:6px 10px; background:#f8fafc; border-radius:4px;">
                        <span><?php echo htmlspecialchars($ps['ten_phuong_thuc'] ?? 'Chưa rõ'); ?></span>
                        <strong><?php echo $ps['so_luong']; ?> đơn (<?php echo format_currency($ps['tong_tien'] ?? 0); ?>)</strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <div style="font-size:13px; font-weight:600; color:#64748b; margin-bottom:8px;">Tình trạng vận chuyển:</div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <?php foreach ($shippingStats as $ss): ?>
                    <?php
                        $stMap = [
                            'cho_lay_hang'    => 'Chờ đóng gói',
                            'dang_van_chuyen' => 'Đang trung chuyển',
                            'dang_giao'       => 'Đang giao',
                            'da_giao'         => 'Đã giao'
                        ];
                    ?>
                    <span class="badge badge-info" style="padding:6px 12px;">
                        <?php echo $stMap[$ss['trang_thai_giao_hang']] ?? $ss['trang_thai_giao_hang']; ?>: <strong><?php echo $ss['so_luong']; ?></strong>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ĐƠN HÀNG MỚI NHẤT CẦN XỬ LÝ -->
<div class="card-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="font-size: 16px; font-weight: 700; color: #1e293b;">
            📦 Đơn Hàng Mới Nhất
        </h3>
        <a href="<?php echo base_url('admin/don-hang'); ?>" style="color: #935832; font-weight: 600; font-size: 13px;">Xem tất cả đơn &rarr;</a>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách nhận</th>
                    <th>Ngày đặt</th>
                    <th>Tổng tiền</th>
                    <th>Thanh toán</th>
                    <th>Vận chuyển</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentOrders)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 30px;">Chưa có đơn hàng nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentOrders as $ro): ?>
                        <tr>
                            <td><strong>#<?php echo htmlspecialchars($ro['ma_don_hang']); ?></strong></td>
                            <td>
                                <div><?php echo htmlspecialchars($ro['ho_ten_nhan']); ?></div>
                                <small style="color: #64748b;"><?php echo htmlspecialchars($ro['sdt_nhan']); ?></small>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($ro['ngay_dat'])); ?></td>
                            <td><strong style="color: #e67e22;"><?php echo format_currency($ro['tong_tien']); ?></strong></td>
                            <td>
                                <span class="badge <?php echo ($ro['trang_thai_thanh_toan'] === 'da_thanh_toan') ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo ($ro['trang_thai_thanh_toan'] === 'da_thanh_toan') ? 'Đã thu' : 'Chưa thu'; ?>
                                </span>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($ro['ma_van_don'] ?? '-'); ?></code>
                            </td>
                            <td>
                                <?php
                                    $sMap = [
                                        'cho_xu_ly' => ['Chờ xử lý', 'badge-warning'],
                                        'dang_giao' => ['Đang giao', 'badge-info'],
                                        'hoan_tat'  => ['Hoàn tất', 'badge-success'],
                                        'da_huy'    => ['Đã hủy', 'badge-danger'],
                                    ];
                                    $st = $sMap[$ro['trang_thai']] ?? ['Không rõ', 'badge-secondary'];
                                ?>
                                <span class="badge <?php echo $st[1]; ?>"><?php echo $st[0]; ?></span>
                            </td>
                            <td>
                                <a href="<?php echo base_url('admin/don-hang/chi-tiet/' . $ro['id']); ?>" class="btn-admin btn-admin-primary btn-admin-sm">
                                    Xem & Xử lý
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    window.DASHBOARD_CHART_DATA = <?php echo json_encode($chartData); ?>;
</script>

<?php
$extraJs = ['assets/js/admin/dashboard.js'];
require_once __DIR__ . '/../../../includes/admin_footer.php';
?>
