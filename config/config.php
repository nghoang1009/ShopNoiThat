<?php
// Cấu hình môi trường và cơ sở dữ liệu
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'qlnoithat');
define('DB_USER', 'root');
define('DB_PASS', '');

// Cấu hình URL hệ thống
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseUrl = rtrim($protocol . $host . $scriptDir, '/');

// Base URL dự án
define('BASE_URL', $baseUrl);
define('SITE_NAME', 'Nội Thất Sang Trọng - Luxury Decor');
define('UPLOAD_DIR', __DIR__ . '/../assets/images/uploads/');
define('UPLOAD_URL', BASE_URL . '/assets/images/uploads/');

// Cấu hình AI Assistant (Tư vấn nội thất)
define('AI_PROVIDER', getenv('AI_PROVIDER') ?: 'claude'); // 'claude', 'openai', 'gemini'
define('AI_API_KEY', getenv('AI_API_KEY') ?: getenv('ANTHROPIC_API_KEY') ?: getenv('OPENAI_API_KEY') ?: '');
define('AI_MODEL', getenv('AI_MODEL') ?: 'claude-3-5-sonnet-20241022');
define('AI_TIMEOUT', 15); // Timeout 15s tránh treo request

// Bật hiển thị lỗi khi dev (tắt khi production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Khởi tạo session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Các hàm helper tiện ích
 */
function base_url($path = '') {
    $path = ltrim($path, '/');
    return empty($path) ? BASE_URL : BASE_URL . '/' . $path;
}

function asset_url($path = '') {
    return base_url($path);
}

function format_currency($number) {
    return number_format((float)$number, 0, ',', '.') . ' ₫';
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $val) {
            $data[$key] = sanitize($val);
        }
        return $data;
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = base_url($url);
    }
    header("Location: $url");
    exit();
}

function set_flash($key, $message, $type = 'success') {
    $_SESSION['flash'][$key] = [
        'message' => $message,
        'type' => $type
    ];
}

function get_flash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $flash = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $flash;
    }
    return null;
}

function has_flash($key) {
    return isset($_SESSION['flash'][$key]);
}
