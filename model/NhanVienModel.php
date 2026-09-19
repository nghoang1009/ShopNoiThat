<?php
require_once __DIR__ . '/../core/Model.php';

class NhanVienModel extends Model {
    protected string $table = 'nhan_vien';

    /**
     * Tìm nhân viên theo tài khoản
     */
    public function findByUsername(string $username): ?array {
        return $this->findBy('tai_khoan', $username);
    }

    /**
     * Tìm nhân viên theo email
     */
    public function findByEmail(string $email): ?array {
        return $this->findBy('email', $email);
    }

    /**
     * Xác thực đăng nhập nhân viên/quản trị
     */
    public function authenticate(string $usernameOrEmail, string $password): ?array {
        $sql = "SELECT * FROM {$this->table}
                WHERE (tai_khoan = :username OR email = :email) AND trang_thai = 1
                LIMIT 1";
        $admin = $this->fetch($sql, [
            ':username' => $usernameOrEmail,
            ':email'    => $usernameOrEmail
        ]);

        if ($admin) {
            // Hỗ trợ cả bcrypt và fallback demo text nếu có
            if (password_verify($password, $admin['mat_khau']) || $admin['mat_khau'] === $password) {
                unset($admin['mat_khau']);
                return $admin;
            }
        }
        return null;
    }

    /**
     * Thêm nhân viên mới
     */
    public function createStaff(array $data): int|string {
        $data['mat_khau'] = password_hash($data['mat_khau'], PASSWORD_BCRYPT);
        return $this->create($data);
    }

    /**
     * Cập nhật thông tin / mật khẩu
     */
    public function updateStaff(int $id, array $data): bool {
        if (!empty($data['mat_khau'])) {
            $data['mat_khau'] = password_hash($data['mat_khau'], PASSWORD_BCRYPT);
        } else {
            unset($data['mat_khau']);
        }
        return $this->update($id, $data);
    }
}
