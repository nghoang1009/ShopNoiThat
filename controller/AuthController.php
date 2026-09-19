<?php
require_once __DIR__ . '/../core/Controller.php';

class AuthController extends Controller {
    private KhachHangModel $khachHangModel;
    private GioHangModel $gioHangModel;

    public function __construct() {
        $this->khachHangModel = $this->model('KhachHangModel');
        $this->gioHangModel = $this->model('GioHangModel');
    }

    /**
     * Trang / Xử lý đăng nhập khách hàng
     */
    public function login(): void {
        if (isset($_SESSION['user']['id'])) {
            redirect('');
            return;
        }

        if ($this->isPost()) {
            $post = $this->getPost();
            $email = trim($post['email'] ?? '');
            $password = $post['password'] ?? '';

            do {
                if (empty($email) || empty($password)) {
                    if ($this->isApiRequest()) {
                        $this->json(['success' => false, 'message' => 'Vui lòng nhập đầy đủ email và mật khẩu.'], 400);
                        break;
                    }
                    set_flash('login_error', 'Vui lòng nhập đầy đủ email và mật khẩu.', 'danger');
                    set_flash('old_email', $email, 'info');
                    redirect('auth/login');
                    break;
                }

                $user = $this->khachHangModel->authenticate($email, $password);
                if ($user) {
                    $_SESSION['user'] = $user;

                    // Hợp nhất giỏ hàng guest vào user
                    if (!empty($_SESSION['cart_session_id'])) {
                        $this->gioHangModel->mergeGuestCartToUser($_SESSION['cart_session_id'], (int)$user['id']);
                    }

                    if ($this->isApiRequest()) {
                        $this->json(['success' => true, 'data' => $user, 'message' => 'Đăng nhập thành công!']);
                        return;
                    }

                    set_flash('auth_success', 'Chào mừng bạn ' . $user['ho_ten'] . ' đã quay trở lại!', 'success');
                    redirect('');
                    return;
                }

                if ($this->isApiRequest()) {
                    $this->json(['success' => false, 'message' => 'Email hoặc mật khẩu không chính xác.'], 401);
                    break;
                }

                set_flash('login_error', 'Email hoặc mật khẩu không chính xác.', 'danger');
                set_flash('old_email', $email, 'info');
                redirect('auth/login');
                break;
            } while (false);
            return;
        }

        $this->view('client/auth/login', [
            'pageTitle' => 'Đăng Nhập Tài Khoản'
        ]);
    }

    /**
     * Trang / Xử lý đăng ký tài khoản khách hàng
     */
    public function register(): void {
        if (isset($_SESSION['user']['id'])) {
            redirect('');
            return;
        }

        if ($this->isPost()) {
            $post = $this->getPost();
            $rules = [
                'ho_ten'   => ['required' => true, 'min' => 2, 'max' => 100, 'label' => 'Họ và tên'],
                'email'    => ['required' => true, 'email' => true, 'label' => 'Địa chỉ Email'],
                'sdt'      => ['required' => true, 'phone' => true, 'label' => 'Số điện thoại'],
                'mat_khau' => ['required' => true, 'min' => 6, 'label' => 'Mật khẩu']
            ];

            do {
                $errors = $this->validateInputs($rules, $post);

                // Kiểm tra email đã tồn tại chưa
                if (empty($errors['email'])) {
                    $existing = $this->khachHangModel->findByEmail(trim($post['email']));
                    if ($existing) {
                        $errors['email'] = 'Địa chỉ email này đã được sử dụng.';
                    }
                }

                if (!empty($errors)) {
                    if ($this->isApiRequest()) {
                        $this->json(['success' => false, 'errors' => $errors, 'message' => reset($errors)], 422);
                        break;
                    }
                    set_flash('register_errors', $errors, 'danger');
                    set_flash('old_register', $post, 'info');
                    redirect('auth/register');
                    break;
                }

                $userData = [
                    'ho_ten'   => trim($post['ho_ten']),
                    'email'    => trim($post['email']),
                    'sdt'      => trim($post['sdt']),
                    'mat_khau' => $post['mat_khau'],
                    'dia_chi'  => trim($post['dia_chi'] ?? '')
                ];

                $userId = $this->khachHangModel->register($userData);
                if ($userId) {
                    $userData['id'] = $userId;
                    unset($userData['mat_khau']);
                    $_SESSION['user'] = $userData;

                    // Gộp giỏ hàng nếu có
                    if (!empty($_SESSION['cart_session_id'])) {
                        $this->gioHangModel->mergeGuestCartToUser($_SESSION['cart_session_id'], (int)$userId);
                    }

                    if ($this->isApiRequest()) {
                        $this->json(['success' => true, 'data' => $userData, 'message' => 'Đăng ký thành công!'], 201);
                        return;
                    }

                    set_flash('auth_success', 'Đăng ký tài khoản thành công. Chào mừng bạn!', 'success');
                    redirect('');
                    return;
                }

                if ($this->isApiRequest()) {
                    $this->json(['success' => false, 'message' => 'Đăng ký thất bại. Vui lòng thử lại.'], 500);
                    break;
                }

                set_flash('register_error', 'Đăng ký thất bại. Vui lòng thử lại.', 'danger');
                redirect('auth/register');
                break;
            } while (false);
            return;
        }

        $this->view('client/auth/register', [
            'pageTitle' => 'Đăng Ký Tài Khoản Mới'
        ]);
    }

    /**
     * Đăng xuất
     */
    public function logout(): void {
        unset($_SESSION['user']);
        set_flash('auth_success', 'Đã đăng xuất thành công.', 'info');
        redirect('');
    }

    /**
     * Thông tin tài khoản cá nhân
     */
    public function profile(): void {
        $userSession = $this->requireClientAuth();
        $user = $this->khachHangModel->find($userSession['id']);

        if ($this->isPost()) {
            $post = $this->getPost();
            $rules = [
                'ho_ten' => ['required' => true, 'min' => 2, 'label' => 'Họ và tên'],
                'sdt'    => ['required' => true, 'phone' => true, 'label' => 'Số điện thoại']
            ];

            do {
                $errors = $this->validateInputs($rules, $post);
                if (!empty($errors)) {
                    set_flash('profile_error', reset($errors), 'danger');
                    redirect('auth/profile');
                    break;
                }

                $updateData = [
                    'ho_ten'  => trim($post['ho_ten']),
                    'sdt'     => trim($post['sdt']),
                    'dia_chi' => trim($post['dia_chi'] ?? '')
                ];

                if (!empty($post['mat_khau_moi'])) {
                    if (mb_strlen($post['mat_khau_moi']) < 6) {
                        set_flash('profile_error', 'Mật khẩu mới phải có tối thiểu 6 ký tự.', 'danger');
                        redirect('auth/profile');
                        break;
                    }
                    $updateData['mat_khau'] = password_hash($post['mat_khau_moi'], PASSWORD_BCRYPT);
                }

                $this->khachHangModel->update((int)$user['id'], $updateData);

                // Cập nhật lại session
                $_SESSION['user']['ho_ten'] = $updateData['ho_ten'];
                $_SESSION['user']['sdt'] = $updateData['sdt'];
                $_SESSION['user']['dia_chi'] = $updateData['dia_chi'];

                set_flash('profile_success', 'Cập nhật thông tin cá nhân thành công!', 'success');
                redirect('auth/profile');
                return;
            } while (false);
            return;
        }

        $this->view('client/auth/profile', [
            'pageTitle' => 'Thông Tin Cá Nhân',
            'user'      => $user
        ]);
    }
}
