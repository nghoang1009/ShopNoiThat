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

// Cấu hình gửi email (quên mật khẩu, thông báo). Ưu tiên biến môi trường, sau đó config/mail.local.php
$mailLocal = is_file(__DIR__ . '/mail.local.php') ? (require __DIR__ . '/mail.local.php') : [];
$mailLocal = is_array($mailLocal) ? $mailLocal : [];
define('MAIL_DRIVER', getenv('MAIL_DRIVER') ?: ($mailLocal['driver'] ?? 'log'));        // 'log' (ghi file, dùng khi dev), 'smtp', 'mail'
define('MAIL_HOST', getenv('MAIL_HOST') ?: ($mailLocal['host'] ?? ''));
define('MAIL_PORT', (int)(getenv('MAIL_PORT') ?: ($mailLocal['port'] ?? 587)));
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: ($mailLocal['encryption'] ?? 'tls')); // 'tls' (STARTTLS), 'ssl', hoặc ''
define('MAIL_USER', getenv('MAIL_USER') ?: ($mailLocal['user'] ?? ''));
define('MAIL_PASS', getenv('MAIL_PASS') ?: ($mailLocal['pass'] ?? ''));
define('MAIL_FROM', getenv('MAIL_FROM') ?: ($mailLocal['from'] ?? 'no-reply@shopnoithat.vn'));
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: ($mailLocal['from_name'] ?? 'Shop Nội Thất'));
// URL gốc dùng trong link gửi qua email. NÊN đặt cố định khi triển khai thật (tránh giả mạo header Host)
define('APP_URL', rtrim(getenv('APP_URL') ?: ($mailLocal['app_url'] ?? BASE_URL), '/'));

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


/**
 * Lọc HTML theo danh sách cho phép (dùng cho mô tả sản phẩm do admin nhập).
 * Giữ lại thẻ định dạng cơ bản, loại bỏ script/style/iframe, mọi thuộc tính on*, và URL javascript:/data:.
 */
function sanitize_html(?string $html): string {
    $html = (string)$html;
    if (trim($html) === '') return '';

    $allowed = [
        'p' => [], 'br' => [], 'hr' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'blockquote' => [],
        'span' => [], 'div' => [], 'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => [],
        'a'   => ['href', 'title'],
        'img' => ['src', 'alt', 'title'],
    ];
    $drop = ['script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'form', 'input', 'button',
             'textarea', 'select', 'option', 'link', 'meta', 'base', 'svg', 'math', 'noscript', 'template', 'title', 'head'];

    $safeUrl = function (string $value): ?string {
        $clean = preg_replace('/[\x00-\x20\x7f]+/u', '', $value);
        if ($clean === null || $clean === '') return null;
        if (preg_match('#^(https?://|mailto:|/|\#)#i', $clean)) return $value;
        if (!preg_match('#^[a-z][a-z0-9+.\-]*:#i', $clean)) return $value; // đường dẫn tương đối
        return null;
    };

    $prev = libxml_use_internal_errors(true);
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->loadHTML('<?xml encoding="utf-8" ?><div id="sanitize-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $root = $doc->getElementById('sanitize-root');
    if (!$root) return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');

    $clean = function (DOMNode $parent) use (&$clean, $allowed, $drop, $safeUrl): void {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);
                if (in_array($tag, $drop, true)) {
                    $parent->removeChild($node);
                    continue;
                }
                $clean($node);
                if (!isset($allowed[$tag])) {
                    // Thẻ không được phép: giữ nội dung, bỏ thẻ
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);
                    continue;
                }
                foreach (iterator_to_array($node->attributes) as $attr) {
                    $name = strtolower($attr->name);
                    if (!in_array($name, $allowed[$tag], true)) {
                        $node->removeAttribute($attr->name);
                    } elseif (in_array($name, ['href', 'src'], true) && $safeUrl($attr->value) === null) {
                        $node->removeAttribute($attr->name);
                    }
                }
                if ($tag === 'img' && !$node->hasAttribute('src')) {
                    $parent->removeChild($node);
                } elseif ($tag === 'a') {
                    $node->setAttribute('rel', 'noopener noreferrer nofollow');
                }
            } elseif (!($node instanceof DOMText)) {
                $parent->removeChild($node); // comment, CDATA, processing instruction...
            }
        }
    };
    $clean($root);

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return $out;
}

/**
 * Đổi session ID sau khi đăng nhập/đăng xuất (chống session fixation) và xoay vòng CSRF token
 */
function rotate_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    unset($_SESSION['csrf_token']);
}
