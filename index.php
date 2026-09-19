<?php
/**
 * Entry Point - Điểm khởi động ứng dụng Shop Nội Thất MVC
 */

// 1. Nạp file cấu hình hệ thống
require_once __DIR__ . '/config/config.php';

// 2. Nạp các lớp Core
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Model.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/core/AiClient.php';
require_once __DIR__ . '/core/App.php';

// 3. Nạp danh sách định tuyến Route
require_once __DIR__ . '/app.php';

// 4. Khởi chạy ứng dụng Router
$app = new App();
$app->run();
