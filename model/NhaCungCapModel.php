<?php
require_once __DIR__ . '/../core/Model.php';

class NhaCungCapModel extends Model {
    protected string $table = 'nha_cung_cap';

    /**
     * Danh sách nhà cung cấp kèm số sản phẩm đang liên kết, có tìm kiếm theo tên / SĐT / email
     */
    public function listWithStats(string $search = ''): array {
        $sql = "SELECT ncc.*, COUNT(sp.id) AS so_san_pham
                FROM {$this->table} ncc
                LEFT JOIN san_pham sp ON sp.nha_cung_cap_id = ncc.id";
        $params = [];
        if ($search !== '') {
            $sql .= " WHERE ncc.ten_nha_cung_cap LIKE :kw1 OR ncc.sdt LIKE :kw2 OR ncc.email LIKE :kw3";
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $params = [':kw1' => $like, ':kw2' => $like, ':kw3' => $like];
        }
        $sql .= " GROUP BY ncc.id ORDER BY ncc.id ASC";
        return $this->fetchAll($sql, $params);
    }

    /**
     * Số sản phẩm đang thuộc nhà cung cấp này
     */
    public function countProducts(int $id): int {
        $row = $this->fetch("SELECT COUNT(*) AS total FROM san_pham WHERE nha_cung_cap_id = :id", [':id' => $id]);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Tên nhà cung cấp đã tồn tại chưa (không phân biệt hoa thường), bỏ qua chính bản ghi đang sửa
     */
    public function nameExists(string $name, ?int $exceptId = null): bool {
        $sql = "SELECT id FROM {$this->table} WHERE LOWER(ten_nha_cung_cap) = LOWER(:name)";
        $params = [':name' => $name];
        if ($exceptId !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = $exceptId;
        }
        return $this->fetch($sql . " LIMIT 1", $params) !== null;
    }

    /**
     * Kiểm tra dữ liệu nhà cung cấp (dữ liệu thô, chưa escape). Trả về mảng lỗi theo trường.
     */
    public function validate(array $data, ?int $exceptId = null): array {
        $errors = [];
        $name = trim((string)($data['ten_nha_cung_cap'] ?? ''));
        $phone = trim((string)($data['sdt'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $address = trim((string)($data['dia_chi'] ?? ''));

        if ($name === '' || mb_strlen($name) < 2) {
            $errors['ten_nha_cung_cap'] = 'Tên nhà cung cấp phải có tối thiểu 2 ký tự.';
        } elseif (mb_strlen($name) > 255) {
            $errors['ten_nha_cung_cap'] = 'Tên nhà cung cấp không được vượt quá 255 ký tự.';
        } elseif ($this->nameExists($name, $exceptId)) {
            $errors['ten_nha_cung_cap'] = 'Tên nhà cung cấp này đã tồn tại.';
        }

        if ($phone === '') {
            $errors['sdt'] = 'Số điện thoại không được để trống.';
        } elseif (!preg_match('/^\+?[0-9][0-9 .\-]{6,18}$/', $phone)) {
            $errors['sdt'] = 'Số điện thoại không hợp lệ (7-19 ký tự gồm số, khoảng trắng, dấu chấm hoặc gạch ngang).';
        }

        if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100)) {
            $errors['email'] = 'Email không đúng định dạng.';
        }

        if (mb_strlen($address) > 255) {
            $errors['dia_chi'] = 'Địa chỉ không được vượt quá 255 ký tự.';
        }

        return $errors;
    }

    /**
     * Chuẩn hóa dữ liệu để lưu (email / địa chỉ rỗng -> NULL)
     */
    public function normalize(array $data): array {
        $email = trim((string)($data['email'] ?? ''));
        $address = trim((string)($data['dia_chi'] ?? ''));
        return [
            'ten_nha_cung_cap' => trim((string)($data['ten_nha_cung_cap'] ?? '')),
            'sdt'              => trim((string)($data['sdt'] ?? '')),
            'email'            => $email !== '' ? $email : null,
            'dia_chi'          => $address !== '' ? $address : null,
        ];
    }
}
