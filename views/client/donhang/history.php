<?php
$extraCss = ['assets/css/client/giohang.css'];
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="container" style="margin-top: 30px; margin-bottom: 60px;">
    <div style="padding: 10px 0 20px 0; font-size: 14px; color: var(--text-muted);">
        <a href="<?php echo base_url(); ?>">Trang chủ</a> &raquo;
        <span>Lịch sử đơn hàng</span>
    </div>

    <h1 style="font-size: 24px; color: var(--secondary-color); margin-bottom: 25px;">
        Đơn Hàng Của Tôi
    </h1>

    <?php if (empty($orders)): ?>
        <div class="empty-cart-box">
            <div class="empty-cart-icon">📦</div>
            <h2 style="font-size: 20px; color: var(--secondary-color); margin-bottom: 10px;">Bạn chưa có đơn hàng nào</h2>
            <p style="color: var(--text-muted); margin-bottom: 25px;">Hãy bắt đầu trải nghiệm mua sắm nội thất cùng chúng tôi!</p>
            <a href="<?php echo base_url('san-pham'); ?>" class="btn btn-primary">Mua Sắm Ngay</a>
        </div>
    <?php else: ?>
        <div style="background: #fff; border-radius: var(--radius-md); border: 1px solid var(--border-color); overflow: hidden;">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Mã đơn hàng</th>
                        <th>Ngày đặt</th>
                        <th>Người nhận</th>
                        <th>Tổng tiền</th>
                        <th>Thanh toán</th>
                        <th>Trạng thái đơn</th>
                        <th style="text-align: right;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--primary-color);">#<?php echo htmlspecialchars($o['ma_don_hang']); ?></strong>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($o['ngay_dat'])); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($o['ho_ten_nhan']); ?></div>
                                <small style="color: var(--text-muted);"><?php echo htmlspecialchars($o['sdt_nhan']); ?></small>
                            </td>
                            <td>
                                <strong style="color: var(--accent-color);"><?php echo format_currency($o['tong_tien']); ?></strong>
                            </td>
                            <td>
                                <?php if ($o['trang_thai_thanh_toan'] === 'da_thanh_toan'): ?>
                                    <span class="badge badge-success" style="background:#dcfce7; color:#15803d; padding:3px 8px; border-radius:4px; font-size:12px; font-weight:600;">Đã thanh toán</span>
                                <?php else: ?>
                                    <span class="badge badge-warning" style="background:#fef3c7; color:#b45309; padding:3px 8px; border-radius:4px; font-size:12px; font-weight:600;">Chưa thanh toán</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                    $statusMap = [
                                        'cho_xu_ly' => ['Chờ xử lý', 'badge-warning', '#fef3c7', '#b45309'],
                                        'dang_giao' => ['Đang giao', 'badge-info', '#e0f2fe', '#0369a1'],
                                        'hoan_tat'  => ['Hoàn tất', 'badge-success', '#dcfce7', '#15803d'],
                                        'da_huy'    => ['Đã hủy', 'badge-danger', '#fee2e2', '#b91c1c'],
                                    ];
                                    $st = $statusMap[$o['trang_thai']] ?? ['Không rõ', 'badge-secondary', '#f1f5f9', '#475569'];
                                ?>
                                <span style="background:<?php echo $st[2]; ?>; color:<?php echo $st[3]; ?>; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600;">
                                    <?php echo $st[0]; ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?php echo base_url('don-hang/chi-tiet/' . $o['id']); ?>" class="btn btn-outline btn-sm">
                                    Chi tiết &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>
