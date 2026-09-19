<?php
$extraCss = [
    'assets/css/client/vanchuyen_thanhtoan.css',
    'assets/css/client/checkout.css'
];
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="container">
    <div style="padding: 20px 0 10px 0; font-size: 14px; color: var(--text-muted);">
        <a href="<?php echo base_url(); ?>">Trang chủ</a> &raquo;
        <span>Tra cứu vận chuyển & đơn hàng</span>
    </div>

    <div class="tracking-wrapper">
        <!-- Form tra cứu -->
        <div style="background: #fff; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 25px 30px; box-shadow: var(--shadow-sm); margin-bottom: 25px;">
            <h1 style="font-size: 22px; color: var(--secondary-color); margin-bottom: 8px;">
                🚚 Tra Cứu Tiến Độ Giao Hàng Nội Thất
            </h1>
            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 20px;">
                Nhập <strong>Mã đơn hàng</strong> (ví dụ: <code>DH20260901001</code>) hoặc <strong>Mã vận đơn</strong> (ví dụ: <code>VD20260901001</code>) để kiểm tra:
            </p>

            <form action="<?php echo base_url('don-hang/tra-cuu'); ?>" method="GET" style="display:flex; gap: 10px; max-width: 600px;">
                <input type="text" name="ma" value="<?php echo htmlspecialchars($code ?? ''); ?>" placeholder="Nhập mã đơn hoặc mã vận đơn..." required style="flex:1; padding: 12px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-sm); font-size: 14px; outline:none;">
                <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">Tra Cứu</button>
            </form>
        </div>

        <?php if (!empty($code) && !$tracking): ?>
            <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                <div style="font-size: 50px; color: #cbd5e1; margin-bottom: 15px;">🔍</div>
                <h3 style="color: var(--secondary-color); margin-bottom: 8px;">Không tìm thấy thông tin vận chuyển!</h3>
                <p style="color: var(--text-muted); font-size: 14px;">Vui lòng kiểm tra lại tính chính xác của mã <strong><?php echo htmlspecialchars($code); ?></strong>.</p>
            </div>
        <?php elseif ($tracking): ?>

            <!-- Thông tin đơn hàng & vận chuyển -->
            <div class="tracking-header-card">
                <div>
                    <span style="font-size: 12px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px;">Mã vận đơn:</span>
                    <h2 style="font-size: 22px; color: var(--primary-color); margin-bottom: 4px;">
                        #<?php echo htmlspecialchars($tracking['ma_van_don'] ?? 'Đang cập nhật'); ?>
                    </h2>
                    <div style="font-size: 13px; color: var(--text-muted);">
                        Mã đơn hàng liên kết: <strong>#<?php echo htmlspecialchars($tracking['ma_don_hang']); ?></strong>
                    </div>
                </div>

                <div style="text-align: right;">
                    <span style="font-size: 12px; color: var(--text-muted); display:block;">Đơn vị vận chuyển:</span>
                    <strong style="font-size: 15px; color: var(--secondary-color);"><?php echo htmlspecialchars($tracking['don_vi_van_chuyen'] ?? 'Nội bộ'); ?></strong>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                        Gói: <strong><?php echo htmlspecialchars($tracking['ten_pt'] ?? 'Tiêu chuẩn'); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Tiến trình Stepper -->
            <div class="tracking-timeline-box">
                <h3 style="font-size: 17px; color: var(--secondary-color); margin-bottom: 30px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                    📍 Trạng Thái Giao Hàng Trực Tuyến
                </h3>

                <div class="timeline-stepper">
                    <?php foreach ($tracking['timeline'] as $step): ?>
                        <div class="stepper-step <?php echo $step['completed'] ? 'completed' : ''; ?> <?php echo $step['active'] ? 'active' : ''; ?>">
                            <div class="stepper-circle">
                                <?php echo $step['completed'] ? '✓' : $step['step']; ?>
                            </div>
                            <div class="stepper-label"><?php echo htmlspecialchars($step['title']); ?></div>
                            <?php if (!empty($step['time'])): ?>
                                <div class="stepper-time"><?php echo htmlspecialchars($step['time']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Chi tiết các mốc ghi nhận -->
                <h4 style="font-size: 15px; color: var(--secondary-color); margin-bottom: 20px;">
                    Chi Tiết Các Mốc Vận Chuyển
                </h4>

                <div class="timeline-logs">
                    <?php foreach (array_reverse($tracking['timeline']) as $log): ?>
                        <?php if ($log['completed']): ?>
                            <div class="log-item <?php echo $log['active'] ? 'active' : ''; ?>">
                                <div class="log-title"><?php echo htmlspecialchars($log['title']); ?></div>
                                <div class="log-desc"><?php echo htmlspecialchars($log['description']); ?></div>
                                <?php if (!empty($log['time'])): ?>
                                    <div class="log-time">🕒 <?php echo htmlspecialchars($log['time']); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Thông tin người nhận -->
                <div style="background:#f8fafc; border-radius: var(--radius-sm); padding: 18px 20px; margin-top: 30px; border: 1px solid var(--border-color); display:flex; justify-content:space-between; flex-wrap:wrap; gap:15px;">
                    <div>
                        <div style="font-size:12px; color:var(--text-muted);">Người nhận hàng:</div>
                        <strong><?php echo htmlspecialchars($tracking['ho_ten_nhan']); ?> (<?php echo htmlspecialchars($tracking['sdt_nhan']); ?>)</strong>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-muted);">Địa chỉ giao:</div>
                        <span><?php echo htmlspecialchars($tracking['dia_chi_giao']); ?></span>
                    </div>
                </div>

                <div style="margin-top: 25px; text-align: right;">
                    <a href="<?php echo base_url('don-hang/chi-tiet/' . $tracking['don_hang_id']); ?>" class="btn btn-outline btn-sm">
                        Xem Chi Tiết Đơn Hàng &rarr;
                    </a>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>
