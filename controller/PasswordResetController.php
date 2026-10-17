<?php
require_once __DIR__ . '/../core/Controller.php';

/**
 * Quên mật khẩu / đặt lại mật khẩu cho khách hàng qua email.
 * - Luôn trả lời giống nhau dù email có tồn tại hay không (chống dò tài khoản), phần tra cứu + gửi mail
 *   chạy sau khi đã trả phản hồi nên thời gian phản hồi cũng không để lộ thông tin.
 */
class PasswordResetController extends Controller {
    private DatLaiMatKhauModel $resetModel;

    public function __construct() {
        $this->resetModel = $this->model('DatLaiMatKhauModel');
    }

    /**
     * GET/POST auth/quen-mat-khau
     */
    public function forgot(): void {
        if ($this->isPost()) {
            $email = trim((string)($_POST['email'] ?? ''));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
                set_flash('auth_error', 'Vui lòng nhập địa chỉ email hợp lệ.', 'danger');
                redirect('auth/quen-mat-khau');
                return;
            }

            set_flash('auth_success', 'Nếu email này thuộc về một tài khoản, chúng tôi đã gửi hướng dẫn đặt lại mật khẩu. Vui lòng kiểm tra hộp thư (cả mục Spam).', 'success');
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $model = $this->resetModel;

            $this->respondEarly(302, ['Location: ' . base_url('auth/login')], '', function () use ($model, $email, $ip) {
                $result = $model->requestReset($email, $ip);
                if ($result) {
                    $model->sendResetEmail($result['user'], $result['token']);
                }
            });
            return;
        }

        $this->view('client/auth/forgot', ['pageTitle' => 'Quên Mật Khẩu']);
    }

    /**
     * GET/POST auth/dat-lai-mat-khau/{token}
     */
    public function reset($token): void {
        // Không để token rò rỉ qua header Referer sang tài nguyên bên thứ ba
        header('Referrer-Policy: no-referrer');
        header('Cache-Control: no-store');

        $record = $this->resetModel->findValid((string)$token);
        if (!$record) {
            set_flash('auth_error', 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu liên kết mới.', 'danger');
            redirect('auth/quen-mat-khau');
            return;
        }

        if ($this->isPost()) {
            // Cùng cách xử lý với đăng nhập/đăng ký (getPost) để mật khẩu khớp khi đăng nhập
            $password = (string)$this->getPost('mat_khau', '');
            $confirm  = (string)$this->getPost('xac_nhan_mat_khau', '');

            $error = null;
            if (mb_strlen($password) < 6) {
                $error = 'Mật khẩu mới phải có tối thiểu 6 ký tự.';
            } elseif (strlen($password) > 72) {
                $error = 'Mật khẩu quá dài (tối đa 72 ký tự).';
            } elseif ($password !== $confirm) {
                $error = 'Mật khẩu xác nhận không khớp.';
            }

            if ($error !== null) {
                set_flash('auth_error', $error, 'danger');
                redirect('auth/dat-lai-mat-khau/' . $token);
                return;
            }

            $user = $this->resetModel->resetPassword((string)$token, $password);
            if (!$user) {
                set_flash('auth_error', 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu liên kết mới.', 'danger');
                redirect('auth/quen-mat-khau');
                return;
            }

            // Đổi mật khẩu xong: hủy phiên hiện tại (nếu có) và gửi thông báo
            unset($_SESSION['user']);
            rotate_session();
            set_flash('auth_success', 'Đặt lại mật khẩu thành công! Bạn có thể đăng nhập bằng mật khẩu mới.', 'success');
            $model = $this->resetModel;
            $this->respondEarly(302, ['Location: ' . base_url('auth/login')], '', function () use ($model, $user) {
                $model->sendChangedNotice($user);
            });
            return;
        }

        $this->view('client/auth/reset', [
            'pageTitle' => 'Đặt Lại Mật Khẩu',
            'token'     => (string)$token,
            'email'     => $record['email']
        ]);
    }
}
