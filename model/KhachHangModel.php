<?php
require_once __DIR__ . '/../core/Model.php';

class KhachHangModel extends Model {
    protected string $table = 'khach_hang';

    /**
     * Tìm khách hàng theo Email
     */
    public function findByEmail(string $email): ?array {
        return $this->findBy('email', $email);
    }

    /**
     * Xác thực đăng nhập
     */
    public function authenticate(string $email, string $password): ?array {
        $user = $this->findByEmail($email);
        if ($user && $user['trang_thai'] == 1) {
            if (password_verify($password, $user['mat_khau']) || $user['mat_khau'] === $password) {
                unset($user['mat_khau']); // Xóa hash khỏi session
                return $user;
            }
        }
        return null;
    }

    /**
     * Đăng ký tài khoản mới
     */
    public function register(array $data): int|string {
        $data['mat_khau'] = password_hash($data['mat_khau'], PASSWORD_BCRYPT);
        return $this->create($data);
    }

    /**
     * Cập nhật mật khẩu
     */
    public function updatePassword(int $id, string $newPassword): bool {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        return $this->update($id, ['mat_khau' => $hashed]);
    }

    /**
     * Lấy danh sách khách hàng kèm thống kê đơn hàng và tổng chi tiêu
     */
    public function getCustomersWithOrderStats(?string $search = null): array {
        $sql = "SELECT kh.*,
                       COUNT(dh.id) as tong_don_hang,
                       COALESCE(SUM(CASE WHEN dh.trang_thai = 'hoan_tat' THEN dh.tong_tien ELSE 0 END), 0) as tong_chi_tieu
                FROM {$this->table} kh
                LEFT JOIN don_hang dh ON kh.id = dh.khach_hang_id";

        $params = [];
        if (!empty($search)) {
            $sql .= " WHERE kh.ho_ten LIKE :search OR kh.email LIKE :search2 OR kh.sdt LIKE :search3";
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
        }

        $sql .= " GROUP BY kh.id ORDER BY kh.id DESC";
        return $this->fetchAll($sql, $params);
    }
}
