<?php
/**
 * Core Controller - Lớp cơ sở cho mọi Controller trong ứng dụng
 */
class Controller {
    /**
     * Khởi tạo Model
     */
    protected function model(string $modelName): object {
        $modelFile = __DIR__ . '/../model/' . $modelName . '.php';
        if (file_exists($modelFile)) {
            require_once $modelFile;
            return new $modelName();
        }
        die("Model {$modelName} không tồn tại.");
    }

    /**
     * Render View HTML
     */
    protected function view(string $viewPath, array $data = []): void {
        // Trích xuất mảng thành các biến cục bộ
        extract($data);

        $viewFile = __DIR__ . '/../views/' . $viewPath . '.php';
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die("View `{$viewPath}` không tồn tại tại: {$viewFile}");
        }
    }

    /**
     * Trả về kết quả dạng JSON chuẩn RESTful
     */
    protected function json(array $data, int $statusCode = 200): void {
        // Xóa sạch buffer trước khi gửi header JSON
        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);

        $isSuccess = ($statusCode >= 200 && $statusCode < 300);

        if (!isset($data['success'])) {
            $formatted = [
                'success' => $isSuccess,
                'data'    => $isSuccess ? ($data['data'] ?? $data) : ($data['data'] ?? null),
                'message' => $data['message'] ?? ''
            ];
            if (isset($data['errors'])) {
                $formatted['errors'] = $data['errors'];
            }
            $data = $formatted;
        } else {
            // Đảm bảo data là null khi success = false nếu chưa có
            if ($data['success'] === false && !array_key_exists('data', $data)) {
                $data['data'] = null;
            }
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Trả về response dạng phân trang chuẩn RESTful
     */
    protected function paginate(array $items, int $total, int $page = 1, int $limit = 20, string $message = ''): void {
        $this->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'total' => $total,
                'page'  => $page,
                'limit' => $limit
            ],
            'message' => $message
        ], 200);
    }

    /**
     * Trả về response tạo mới 201 Created (kèm header Location nếu có)
     */
    protected function created($data, string $message = '', ?string $location = null): void {
        if ($location) {
            header('Location: ' . $location);
        }
        $this->json([
            'success' => true,
            'data'    => $data,
            'message' => $message
        ], 201);
    }

    /**
     * Kiểm tra phương thức HTTP
     */
    protected function isGet(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    protected function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isPut(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'PUT';
    }

    protected function isPatch(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'PATCH';
    }

    protected function isDelete(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'DELETE';
    }

    /**
     * Lấy Bearer token từ header Authorization
     */
    protected function getBearerToken(): ?string {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Lấy dữ liệu từ $_GET đã sanitize
     */
    protected function getQuery(?string $key = null, $default = null) {
        if ($key === null) {
            return sanitize($_GET);
        }
        return isset($_GET[$key]) ? sanitize($_GET[$key]) : $default;
    }

    /**
     * Lấy dữ liệu từ $_POST đã sanitize
     */
    protected function getPost(?string $key = null, $default = null) {
        if ($key === null) {
            return sanitize($_POST);
        }
        return isset($_POST[$key]) ? sanitize($_POST[$key]) : $default;
    }

    /**
     * Lấy dữ liệu JSON từ request body (cho API POST / PUT / DELETE)
     */
    protected function getBody(): array {
        $rawInput = file_get_contents('php://input');
        $json = json_decode($rawInput, true);
        if (is_array($json)) {
            return sanitize($json);
        }
        // Fallback sang $_POST nếu gửi dạng form-urlencoded/multipart
        return sanitize($_POST);
    }

    /**
     * Kiểm tra quyền đăng nhập Khách Hàng
     */
    protected function requireClientAuth(): array {
        $user = $_SESSION['user'] ?? null;
        $token = $this->getBearerToken();
        if (!$user && $token) {
            $decoded = json_decode(base64_decode($token), true);
            if (!empty($decoded['id']) && ($decoded['type'] ?? '') === 'client') {
                $user = $decoded;
            }
        }

        if (!$user || empty($user['id'])) {
            if ($this->isApiRequest()) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Yêu cầu đăng nhập tài khoản khách hàng.'], 401);
            }
            set_flash('auth_error', 'Vui lòng đăng nhập để tiếp tục.', 'warning');
            redirect('auth/login');
        }
        return $user;
    }

    /**
     * Kiểm tra quyền đăng nhập Quản Trị / Nhân Viên
     */
    protected function requireAdminAuth(): array {
        $admin = $_SESSION['admin'] ?? null;
        $token = $this->getBearerToken();
        if (!$admin && $token) {
            $decoded = json_decode(base64_decode($token), true);
            if (!empty($decoded['id']) && in_array($decoded['vai_tro'] ?? '', ['admin', 'nhan_vien'])) {
                $admin = $decoded;
            }
        }

        if (!$admin || empty($admin['id'])) {
            if ($this->isApiRequest()) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Yêu cầu xác thực quản trị viên (Authorization Bearer Token hoặc Session).'], 401);
            }
            set_flash('admin_auth_error', 'Vui lòng đăng nhập trang quản trị.', 'warning');
            redirect('admin/login');
        }
        return $admin;
    }

    /**
     * Lấy Session ID hiện tại cho giỏ hàng khách vãng lai
     */
    protected function getCartSessionId(): string {
        if (!isset($_SESSION['cart_session_id'])) {
            $_SESSION['cart_session_id'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['cart_session_id'];
    }

    /**
     * Kiểm tra xem request có phải là API hay không
     */
    protected function isApiRequest(): bool {
        $url = $_GET['url'] ?? '';
        return str_starts_with($url, 'api/') || (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
    }

    /**
     * Mẫu chuẩn Validate theo do { ... } while(false) để gom lỗi và return sớm
     */
    protected function validateInputs(array $rules, array $data): array {
        $errors = [];
        do {
            foreach ($rules as $field => $fieldRules) {
                $value = $data[$field] ?? null;
                $label = $fieldRules['label'] ?? $field;

                // Required check
                if (!empty($fieldRules['required']) && ($value === null || trim((string)$value) === '')) {
                    $errors[$field] = "{$label} không được để trống.";
                    continue;
                }

                // Nếu có giá trị thì kiểm tra tiếp
                if ($value !== null && trim((string)$value) !== '') {
                    // Min length
                    if (isset($fieldRules['min']) && mb_strlen($value) < $fieldRules['min']) {
                        $errors[$field] = "{$label} phải có tối thiểu {$fieldRules['min']} ký tự.";
                    }
                    // Max length
                    if (isset($fieldRules['max']) && mb_strlen($value) > $fieldRules['max']) {
                        $errors[$field] = "{$label} không được vượt quá {$fieldRules['max']} ký tự.";
                    }
                    // Email format
                    if (!empty($fieldRules['email']) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field] = "{$label} không đúng định dạng email.";
                    }
                    // Numeric format
                    if (!empty($fieldRules['numeric']) && !is_numeric($value)) {
                        $errors[$field] = "{$label} phải là số.";
                    }
                    // Phone format VN
                    if (!empty($fieldRules['phone']) && !preg_match('/^(0[3|5|7|8|9])+([0-9]{8})$/', (string)$value)) {
                        $errors[$field] = "{$label} không đúng định dạng số điện thoại Việt Nam.";
                    }
                }
            }
        } while (false);

        return $errors;
    }
}
