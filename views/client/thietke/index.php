<?php
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="planner-page">
    <div class="planner-head">
        <div>
            <h1>📐 Thiết Kế Phòng 2D / 3D</h1>
            <p>Kéo thả sản phẩm vào phòng, xoay và sắp xếp để xem đúng kích thước thực tế trước khi mua.</p>
        </div>
    </div>

    <div class="planner" id="planner">
        <!-- Cột trái: danh mục sản phẩm -->
        <aside class="pl-panel pl-catalog">
            <div class="pl-panel-title">Sản phẩm <span class="pl-hint">kéo vào phòng hoặc bấm +</span></div>
            <input type="search" id="pl-search" class="pl-input" placeholder="Tìm sản phẩm..." autocomplete="off">
            <div class="pl-chips" id="pl-chips"></div>
            <div class="pl-list" id="pl-list"></div>
        </aside>

        <!-- Giữa: khu vực vẽ -->
        <section class="pl-stage">
            <div class="pl-toolbar">
                <div class="pl-seg" role="tablist" aria-label="Chế độ xem">
                    <button type="button" id="pl-view-2d" class="active" role="tab">2D Mặt bằng</button>
                    <button type="button" id="pl-view-3d" role="tab">3D</button>
                </div>

                <label class="pl-field">Rộng <input type="number" id="pl-room-w" min="150" max="2000" step="10"> cm</label>
                <label class="pl-field">Dài <input type="number" id="pl-room-l" min="150" max="2000" step="10"> cm</label>
                <label class="pl-field">Cao <input type="number" id="pl-room-h" min="200" max="500" step="5"> cm</label>
                <select id="pl-preset" class="pl-input pl-select" aria-label="Kích thước phòng mẫu">
                    <option value="">Phòng mẫu…</option>
                    <option value="350x400x270">Phòng ngủ 3.5 × 4 m</option>
                    <option value="450x550x280">Phòng khách 4.5 × 5.5 m</option>
                    <option value="350x450x270">Phòng ăn 3.5 × 4.5 m</option>
                    <option value="300x350x270">Phòng làm việc 3 × 3.5 m</option>
                    <option value="400x600x300">Studio 4 × 6 m</option>
                </select>

                <span class="pl-sep"></span>
                <label class="pl-check"><input type="checkbox" id="pl-snap" checked> Bắt dính</label>
                <button type="button" id="pl-undo" class="pl-btn" title="Hoàn tác (Ctrl+Z)">↶</button>
                <button type="button" id="pl-redo" class="pl-btn" title="Làm lại (Ctrl+Y)">↷</button>
                <button type="button" id="pl-fit" class="pl-btn" title="Căn vừa khung nhìn">⤢</button>
                <button type="button" id="pl-share" class="pl-btn" title="Sao chép liên kết chia sẻ bố cục">🔗 Chia sẻ</button>
                <button type="button" id="pl-png" class="pl-btn" title="Tải ảnh bố cục">🖼 Ảnh</button>
                <button type="button" id="pl-clear" class="pl-btn pl-btn-danger" title="Xóa toàn bộ nội thất">🗑</button>
            </div>

            <div class="pl-canvas-wrap" id="pl-canvas-wrap">
                <canvas id="pl-canvas" aria-label="Khu vực thiết kế phòng"></canvas>
                <div class="pl-empty" id="pl-empty">Kéo sản phẩm từ danh sách bên trái vào đây</div>
                <div class="pl-legend" id="pl-legend"></div>
            </div>
            <div class="pl-help" id="pl-help"></div>
        </section>

        <!-- Cột phải: chi tiết + tổng hợp -->
        <aside class="pl-panel pl-side">
            <div class="pl-box" id="pl-inspector">
                <div class="pl-panel-title">Món đang chọn</div>
                <div id="pl-inspector-body" class="pl-muted">Chọn một món trong phòng để xem kích thước và xoay.</div>
            </div>

            <div class="pl-box">
                <div class="pl-panel-title">Tổng hợp</div>
                <div id="pl-summary"></div>
                <div id="pl-warnings"></div>
                <button type="button" id="pl-add-cart" class="btn btn-primary btn-block" style="margin-top:12px;" disabled>
                    🛒 Thêm tất cả vào giỏ hàng
                </button>
            </div>
        </aside>
    </div>
</div>

<script id="planner-data" type="application/json"><?php echo $plannerJson; ?></script>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>
