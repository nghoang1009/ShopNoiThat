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
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
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


/**
 * Bảo mật: khóa bí mật ký token, token CSRF
 */
function app_secret(): string {
    static $secret = null;
    if ($secret !== null) return $secret;
    $secret = getenv('APP_SECRET') ?: '';
    if ($secret === '') {
        $file = __DIR__ . '/app_secret.key';
        if (!is_file($file)) {
            @file_put_contents($file, bin2hex(random_bytes(32)));
        }
        $secret = is_file($file) ? trim((string)file_get_contents($file)) : '';
        if ($secret === '') {
            $secret = hash('sha256', DB_NAME . DB_USER . __DIR__);
        }
    }
    return $secret;
}

/** Tạo token Bearer có chữ ký HMAC và hạn dùng (mặc định 12 giờ) */
function sign_token(array $payload, int $ttl = 43200): string {
    $payload['exp'] = time() + $ttl;
    $body = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    return $body . '.' . hash_hmac('sha256', $body, app_secret());
}

/** Xác thực token; trả về payload hoặc null nếu sai chữ ký / hết hạn */
function verify_token(?string $token): ?array {
    if (!$token || substr_count($token, '.') !== 1) return null;
    [$body, $sig] = explode('.', $token, 2);
    if (!hash_equals(hash_hmac('sha256', $body, app_secret()), $sig)) return null;
    $payload = json_decode(base64_decode(strtr($body, '-_', '+/')), true);
    if (!is_array($payload) || empty($payload['exp']) || $payload['exp'] < time()) return null;
    return $payload;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_valid(): bool {
    $sent = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($sent) && $sent !== '' && hash_equals(csrf_token(), $sent);
}

