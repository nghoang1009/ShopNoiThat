<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * Token đặt lại mật khẩu cho khách hàng.
 * - Token ngẫu nhiên 256-bit gửi qua email; DB chỉ lưu SHA-256 của token (lộ DB không lộ link).
 * - Hạn dùng 60 phút, dùng 1 lần, yêu cầu mới sẽ vô hiệu token cũ.
 * - Giới hạn tần suất theo tài khoản và theo IP.
 */
class DatLaiMatKhauModel extends Model {
    protected string $table = 'dat_lai_mat_khau';

    public const TTL_MINUTES = 60;
    public const MAX_PER_USER_PER_HOUR = 3;
    public const MAX_PER_IP_PER_HOUR = 10;

    public function __construct() {
        parent::__construct();
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS `dat_lai_mat_khau` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `khach_hang_id` INT NOT NULL,
              `token_hash` CHAR(64) NOT NULL UNIQUE,
              `ip` VARCHAR(45) NULL,
              `expires_at` DATETIME NOT NULL,
              `used_at` DATETIME NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              INDEX (`khach_hang_id`),
              INDEX (`ip`, `created_at`),
              FOREIGN KEY (`khach_hang_id`) REFERENCES `khach_hang`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        } catch (Exception $e) {
            // Bảng đã tồn tại hoặc chưa có khach_hang
        }
    }

    private function hash(string $token): string {
        return hash('sha256', $token);
    }

    /**
     * Tạo token cho email. Trả về ['token' => ..., 'user' => ...] hoặc null nếu
     * không có tài khoản hoạt động / vượt giới hạn tần suất (người gọi không được tiết lộ lý do).
     */
    public function requestReset(string $email, string $ip): ?array {
        // Dọn token cũ
        $this->query("DELETE FROM {$this->table} WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");

        $ipCount = $this->fetch(
            "SELECT COUNT(*) AS c FROM {$this->table} WHERE ip = :ip AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [':ip' => $ip]
        );
        if ((int)($ipCount['c'] ?? 0) >= self::MAX_PER_IP_PER_HOUR) {
            return null;
        }

        $user = $this->fetch("SELECT id, ho_ten, email FROM khach_hang WHERE email = :email AND trang_thai = 1 LIMIT 1", [':email' => $email]);
        if (!$user) {
            return null;
        }

        $userCount = $this->fetch(
            "SELECT COUNT(*) AS c FROM {$this->table} WHERE khach_hang_id = :id AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [':id' => $user['id']]
        );
        if ((int)($userCount['c'] ?? 0) >= self::MAX_PER_USER_PER_HOUR) {
            return null;
        }

        // Vô hiệu các token chưa dùng trước đó
        $this->query("UPDATE {$this->table} SET used_at = NOW() WHERE khach_hang_id = :id AND used_at IS NULL", [':id' => $user['id']]);

        $token = bin2hex(random_bytes(32));
        $this->query(
            "INSERT INTO {$this->table} (khach_hang_id, token_hash, ip, expires_at)
             VALUES (:id, :hash, :ip, DATE_ADD(NOW(), INTERVAL " . self::TTL_MINUTES . " MINUTE))",
            [':id' => $user['id'], ':hash' => $this->hash($token), ':ip' => $ip]
        );

        return ['token' => $token, 'user' => $user];
    }

    /**
     * Token còn hiệu lực (chưa dùng, chưa hết hạn, tài khoản còn hoạt động) -> trả về bản ghi kèm email
     */
    public function findValid(string $token): ?array {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return $this->fetch(
            "SELECT r.*, kh.email, kh.ho_ten FROM {$this->table} r
             JOIN khach_hang kh ON kh.id = r.khach_hang_id
             WHERE r.token_hash = :hash AND r.used_at IS NULL AND r.expires_at > NOW() AND kh.trang_thai = 1
             LIMIT 1",
            [':hash' => $this->hash($token)]
        );
    }

    /**
     * Đặt mật khẩu mới bằng token (dùng 1 lần, nguyên tử). Trả về thông tin tài khoản hoặc null nếu token không hợp lệ.
     */
    public function resetPassword(string $token, string $newPassword): ?array {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        try {
            $this->db->beginTransaction();

            $row = $this->fetch(
                "SELECT r.id, r.khach_hang_id, kh.email, kh.ho_ten FROM {$this->table} r
                 JOIN khach_hang kh ON kh.id = r.khach_hang_id
                 WHERE r.token_hash = :hash AND r.used_at IS NULL AND r.expires_at > NOW() AND kh.trang_thai = 1
                 LIMIT 1 FOR UPDATE",
                [':hash' => $this->hash($token)]
            );
            if (!$row) {
                $this->db->rollBack();
                return null;
            }

            $this->query("UPDATE khach_hang SET mat_khau = :pw WHERE id = :id", [
                ':pw' => password_hash($newPassword, PASSWORD_BCRYPT),
                ':id' => $row['khach_hang_id']
            ]);
            // Dùng token này và vô hiệu mọi token còn lại của tài khoản
            $this->query("UPDATE {$this->table} SET used_at = NOW() WHERE khach_hang_id = :id AND used_at IS NULL", [':id' => $row['khach_hang_id']]);

            $this->db->commit();
            return ['id' => (int)$row['khach_hang_id'], 'email' => $row['email'], 'ho_ten' => $row['ho_ten']];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[DatLaiMatKhau] ' . $e->getMessage());
            return null;
        }
    }

    // ---------------- Email ----------------

    /**
     * Gửi email chứa link đặt lại mật khẩu
     */
    public function sendResetEmail(array $user, string $token): bool {
        $link = APP_URL . '/auth/dat-lai-mat-khau/' . $token;
        $name = htmlspecialchars($user['ho_ten'] ?? 'bạn', ENT_QUOTES, 'UTF-8');
        $minutes = self::TTL_MINUTES;
        $site = htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8');

        $html = "<div style=\"font-family:Arial,sans-serif;max-width:560px;margin:auto;color:#334155;line-height:1.6\">"
              . "<h2 style=\"color:#935832\">Đặt lại mật khẩu</h2>"
              . "<p>Xin chào {$name},</p>"
              . "<p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại {$site}. "
              . "Nhấn vào nút bên dưới để tạo mật khẩu mới (liên kết có hiệu lực trong {$minutes} phút và chỉ dùng được một lần):</p>"
              . "<p style=\"text-align:center;margin:28px 0\"><a href=\"{$link}\" style=\"background:#935832;color:#fff;padding:12px 26px;border-radius:6px;text-decoration:none;font-weight:bold\">Đặt lại mật khẩu</a></p>"
              . "<p style=\"font-size:13px;color:#64748b\">Nếu nút không hoạt động, hãy sao chép liên kết sau vào trình duyệt:<br>{$link}</p>"
              . "<p style=\"font-size:13px;color:#64748b\">Nếu bạn không yêu cầu thao tác này, hãy bỏ qua email — mật khẩu của bạn vẫn an toàn.</p></div>";

        $text = "Xin chào {$user['ho_ten']},\n\nĐặt lại mật khẩu tại " . SITE_NAME . ":\n{$link}\n\n"
              . "Liên kết có hiệu lực trong {$minutes} phút và chỉ dùng được một lần.\n"
              . "Nếu bạn không yêu cầu, hãy bỏ qua email này.";

        return Mailer::send($user['email'], 'Đặt lại mật khẩu - ' . SITE_NAME, $html, $text);
    }

    /**
     * Gửi email thông báo mật khẩu vừa được thay đổi (cảnh báo nếu không phải do chủ tài khoản)
     */
    public function sendChangedNotice(array $user): bool {
        $name = htmlspecialchars($user['ho_ten'] ?? 'bạn', ENT_QUOTES, 'UTF-8');
        $html = "<div style=\"font-family:Arial,sans-serif;max-width:560px;margin:auto;color:#334155;line-height:1.6\">"
              . "<h2 style=\"color:#935832\">Mật khẩu đã được thay đổi</h2>"
              . "<p>Xin chào {$name}, mật khẩu tài khoản của bạn vừa được đặt lại thành công vào lúc " . date('H:i d/m/Y') . ".</p>"
              . "<p>Nếu không phải bạn thực hiện, hãy yêu cầu đặt lại mật khẩu ngay hoặc liên hệ cửa hàng để được hỗ trợ.</p></div>";
        return Mailer::send($user['email'], 'Mật khẩu của bạn đã được thay đổi', $html);
    }
}
