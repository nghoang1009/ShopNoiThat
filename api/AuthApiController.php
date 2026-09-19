<?php
require_once __DIR__ . '/../core/Controller.php';

class AuthApiController extends Controller {
    private KhachHangModel $khachHangModel;
    private GioHangModel $gioHangModel;
    private NhanVienModel $nhanVienModel;

    public function __construct() {
        $this->khachHangModel = $this->model('KhachHangModel');
        $this->gioHangModel = $this->model('GioHangModel');
        $this->nhanVienModel = $this->model('NhanVienModel');
    }

    /**
     * POST /api/v1/auth/login
     * Đăng nhập (hỗ trợ cả khách hàng và nhân viên admin)
     */
    public function login(): void {
        $body = $this->getBody();
        $account = trim($body['email'] ?? $body['tai_khoan'] ?? '');
        $password = $body['mat_khau'] ?? $body['password'] ?? '';
        $type = $body['type'] ?? 'client'; // 'client' hoặc 'admin'

        do {
            if (empty($account) || empty($password)) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Vui lòng nhập đầy đủ tài khoản và mật khẩu.'], 400);
                break;
            }

            if ($type === 'admin') {
                $admin = $this->nhanVienModel->authenticate($account, $password);
                if ($admin) {
                    $_SESSION['admin'] = $admin;
                    $admin['token'] = base64_encode(json_encode([
                        'id'      => $admin['id'],
                        'email'   => $admin['email'],
                        'vai_tro' => $admin['vai_tro'],
                        'time'    => time()
                    ]));

                    $this->json([
                        'success' => true,
                        'data'    => $admin,
                        'message' => 'Đăng nhập quản trị viên thành công.'
                    ], 200);
                    return;
                }
            } else {
                $user = $this->khachHangModel->authenticate($account, $password);
                if ($user) {
                    $_SESSION['user'] = $user;
                    if (!empty($_SESSION['cart_session_id'])) {
                        $this->gioHangModel->mergeGuestCartToUser($_SESSION['cart_session_id'], (int)$user['id']);
                    }

                    $user['token'] = base64_encode(json_encode([
                        'id'    => $user['id'],
                        'email' => $user['email'],
                        'type'  => 'client',
                        'time'  => time()
                    ]));

                    $this->json([
                        'success' => true,
                        'data'    => $user,
                        'message' => 'Đăng nhập thành công.'
                    ], 200);
                    return;
                }
            }

            $this->json(['success' => false, 'data' => null, 'message' => 'Tài khoản hoặc mật khẩu không chính xác.'], 401);
            return;
        } while (false);
    }

    /**
     * POST /api/v1/auth/register
     * Đăng ký tài khoản khách hàng
     */
    public function register(): void {
        $body = $this->getBody();
        $rules = [
            'ho_ten'   => ['required' => true, 'min' => 2, 'max' => 100, 'label' => 'Họ và tên'],
            'email'    => ['required' => true, 'email' => true, 'label' => 'Địa chỉ Email'],
            'sdt'      => ['required' => true, 'phone' => true, 'label' => 'Số điện thoại'],
            'mat_khau' => ['required' => true, 'min' => 6, 'label' => 'Mật khẩu']
        ];

        do {
            $errors = $this->validateInputs($rules, $body);

            if (empty($errors['email'])) {
                $existing = $this->khachHangModel->findByEmail(trim($body['email']));
                if ($existing) {
                    $errors['email'] = 'Địa chỉ email này đã được sử dụng.';
                }
            }

            if (!empty($errors)) {
                $this->json(['success' => false, 'data' => null, 'errors' => $errors, 'message' => reset($errors)], 400);
                break;
            }

            $userData = [
                'ho_ten'   => trim($body['ho_ten']),
                'email'    => trim($body['email']),
                'sdt'      => trim($body['sdt']),
                'mat_khau' => $body['mat_khau'],
                'dia_chi'  => trim($body['dia_chi'] ?? '')
            ];

            $userId = $this->khachHangModel->register($userData);
            if ($userId) {
                $createdUser = $this->khachHangModel->find($userId);
                unset($createdUser['mat_khau']);

                $_SESSION['user'] = $createdUser;
                if (!empty($_SESSION['cart_session_id'])) {
                    $this->gioHangModel->mergeGuestCartToUser($_SESSION['cart_session_id'], (int)$userId);
                }

                $createdUser['token'] = base64_encode(json_encode([
                    'id'    => $createdUser['id'],
                    'email' => $createdUser['email'],
                    'type'  => 'client',
                    'time'  => time()
                ]));

                $this->created($createdUser, 'Đăng ký tài khoản thành công.');
                return;
            }

            $this->json(['success' => false, 'data' => null, 'message' => 'Đăng ký thất bại. Vui lòng thử lại.'], 500);
            return;
        } while (false);
    }

    /**
     * POST /api/v1/auth/logout
     * Đăng xuất
     */
    public function logout(): void {
        unset($_SESSION['user'], $_SESSION['admin']);
        $this->json([
            'success' => true,
            'data'    => null,
            'message' => 'Đã đăng xuất thành công.'
        ], 200);
    }

    /**
     * GET /api/v1/auth/me
     * Lấy thông tin tài khoản hiện tại
     */
    public function me(): void {
        $user = $_SESSION['user'] ?? $_SESSION['admin'] ?? null;
        $token = $this->getBearerToken();

        if (!$user && $token) {
            $decoded = json_decode(base64_decode($token), true);
            if (!empty($decoded['id'])) {
                if (isset($decoded['vai_tro'])) {
                    $user = $this->nhanVienModel->find($decoded['id']);
                } else {
                    $user = $this->khachHangModel->find($decoded['id']);
                }
            }
        }

        if ($user) {
            unset($user['mat_khau']);
            $this->json([
                'success' => true,
                'data'    => $user,
                'message' => 'Lấy thông tin tài khoản thành công.'
            ], 200);
            return;
        }

        $this->json(['success' => false, 'data' => null, 'message' => 'Chưa đăng nhập.'], 401);
    }
}
