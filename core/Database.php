<?php
/**
 * Core Database - Quản lý kết nối PDO (Singleton Pattern)
 */
class Database {
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Kiểm tra nếu là API request thì trả về JSON
                $isApi = str_starts_with($_GET['url'] ?? '', 'api/');
                if ($isApi) {
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage()
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                die("<div style='padding:20px; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; border-radius:5px; font-family:sans-serif;'>
                    <h3>Lỗi kết nối Cơ sở dữ liệu!</h3>
                    <p>{$e->getMessage()}</p>
                    <small>Vui lòng đảm bảo MySQL đã bật trên XAMPP và cơ sở dữ liệu `<strong>" . DB_NAME . "</strong>` đã được import từ file <code>qlnoithat.sql</code>.</small>
                </div>");
            }
        }
        return self::$instance;
    }
}
