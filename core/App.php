<?php
/**
 * Core App - Bộ định tuyến (Router) và điều phối Request
 */
class App {
    private static array $routes = [];

    /**
     * Đăng ký route GET
     */
    public static function get(string $path, array|callable $handler): void {
        self::addRoute('GET', $path, $handler);
    }

    /**
     * Đăng ký route POST
     */
    public static function post(string $path, array|callable $handler): void {
        self::addRoute('POST', $path, $handler);
    }

    /**
     * Đăng ký route PUT
     */
    public static function put(string $path, array|callable $handler): void {
        self::addRoute('PUT', $path, $handler);
    }

    /**
     * Đăng ký route PATCH
     */
    public static function patch(string $path, array|callable $handler): void {
        self::addRoute('PATCH', $path, $handler);
    }

    /**
     * Đăng ký route DELETE
     */
    public static function delete(string $path, array|callable $handler): void {
        self::addRoute('DELETE', $path, $handler);
    }

    /**
     * Đăng ký route cho mọi method
     */
    public static function any(string $path, array|callable $handler): void {
        self::addRoute('ANY', $path, $handler);
    }

    /**
     * Thêm route vào danh sách
     */
    private static function addRoute(string $method, string $path, array|callable $handler): void {
        $path = trim($path, '/');
        // Chuyển dynamic param {id}, {slug}, {any} thành regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#i';

        self::$routes[] = [
            'method'  => strtoupper($method),
            'path'    => $path,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    /**
     * Xử lý và điều hướng request
     */
    public function run(): void {
        // Lấy HTTP method (hỗ trợ method override qua POST _method hoặc header)
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($requestMethod === 'POST') {
            if (isset($_POST['_method'])) {
                $requestMethod = strtoupper($_POST['_method']);
            } elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $requestMethod = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            }
        }

        // Lấy URL cần route
        $url = $_GET['url'] ?? '';
        $url = trim($url, '/');

        // Bỏ query string nếu có
        if (str_contains($url, '?')) {
            $url = explode('?', $url)[0];
        }

        $isApi = str_starts_with($url, 'api');

        foreach (self::$routes as $route) {
            // Khớp method
            if ($route['method'] !== 'ANY' && $route['method'] !== $requestMethod) {
                continue;
            }

            // Khớp URL pattern
            if (preg_match($route['pattern'], $url, $matches)) {
                // Lọc lấy các tham số URL và chuyển thành mảng tuần tự (tránh lỗi Named Arguments trong PHP 8)
                $params = array_values(array_filter($matches, function ($key) {
                    return !is_numeric($key);
                }, ARRAY_FILTER_USE_KEY));

                $handler = $route['handler'];

                if (is_callable($handler)) {
                    call_user_func_array($handler, $params);
                    return;
                }

                if (is_array($handler) && count($handler) === 2) {
                    [$controllerClass, $actionMethod] = $handler;

                    $possiblePaths = [
                        __DIR__ . '/../api/' . $controllerClass . '.php',
                        __DIR__ . '/../controller/' . $controllerClass . '.php',
                        __DIR__ . '/../controller/api/' . $controllerClass . '.php',
                        __DIR__ . '/../controller/admin/' . $controllerClass . '.php'
                    ];

                    $controllerFile = null;
                    foreach ($possiblePaths as $path) {
                        if (file_exists($path)) {
                            $controllerFile = $path;
                            break;
                        }
                    }

                    if (file_exists($controllerFile)) {
                        require_once $controllerFile;
                        if (class_exists($controllerClass)) {
                            $controllerInstance = new $controllerClass();
                            if (method_exists($controllerInstance, $actionMethod)) {
                                call_user_func_array([$controllerInstance, $actionMethod], $params);
                                return;
                            } else {
                                $this->handleError("Method `{$actionMethod}` không tồn tại trong Controller `{$controllerClass}`.", $isApi, 500);
                                return;
                            }
                        }
                    }

                    $this->handleError("Controller file hoặc Class `{$controllerClass}` không tồn tại.", $isApi, 500);
                    return;
                }
            }
        }

        // Không tìm thấy Route phù hợp -> 404
        $this->handleError("Đường dẫn `/{$url}` không tồn tại (404 Not Found).", $isApi, 404);
    }

    /**
     * Xử lý lỗi 404 / 500
     */
    private function handleError(string $message, bool $isApi, int $statusCode = 404): void {
        http_response_code($statusCode);
        if ($isApi) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => $message
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo "<!DOCTYPE html>
        <html lang='vi'>
        <head>
            <meta charset='UTF-8'>
            <title>{$statusCode} - Không tìm thấy trang</title>
            <style>
                body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; color: #334155; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
                .error-box { text-align: center; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); max-width: 500px; }
                h1 { font-size: 72px; margin: 0; color: #b45309; }
                p { font-size: 16px; margin: 15px 0 25px; line-height: 1.6; }
                a { display: inline-block; padding: 10px 24px; background: #b45309; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; }
                a:hover { background: #92400e; }
            </style>
        </head>
        <body>
            <div class='error-box'>
                <h1>{$statusCode}</h1>
                <p>{$message}</p>
                <a href='" . base_url() . "'>Quay về Trang Chủ</a>
            </div>
        </body>
        </html>";
        exit;
    }
}
