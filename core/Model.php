<?php
/**
 * Core Model - Lớp cơ sở cho tất cả Model trong hệ thống
 */
class Model {
    protected PDO $db;
    protected string $table = '';
    protected string $primaryKey = 'id';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Thực thi câu lệnh SQL với parameters
     */
    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Lấy một dòng kết quả
     */
    public function fetch(string $sql, array $params = []): ?array {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    /**
     * Lấy danh sách kết quả
     */
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Lấy tất cả bản ghi của bảng
     */
    public function all(string $orderBy = 'id DESC', ?int $limit = null): array {
        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy}";
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }
        return $this->fetchAll($sql);
    }

    /**
     * Tìm bản ghi theo Khóa chính (id)
     */
    public function find($id): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        return $this->fetch($sql, [':id' => $id]);
    }

    /**
     * Tìm 1 bản ghi theo cột chỉ định
     */
    public function findBy(string $column, $value): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = :val LIMIT 1";
        return $this->fetch($sql, [':val' => $value]);
    }

    /**
     * Lấy danh sách theo điều kiện cột
     */
    public function where(string $column, $value, string $orderBy = 'id DESC'): array {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = :val ORDER BY {$orderBy}";
        return $this->fetchAll($sql, [':val' => $value]);
    }

    /**
     * Thêm mới một bản ghi
     */
    public function create(array $data): int|string {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }

        $this->query($sql, $params);
        return $this->db->lastInsertId();
    }

    /**
     * Cập nhật bản ghi theo ID
     */
    public function update($id, array $data): bool {
        $setClauses = [];
        $params = [':pk' => $id];

        foreach ($data as $key => $value) {
            $setClauses[] = "{$key} = :{$key}";
            $params[':' . $key] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE {$this->primaryKey} = :pk";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount() >= 0;
    }

    /**
     * Xóa bản ghi theo ID
     */
    public function delete($id): bool {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->query($sql, [':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Đếm số lượng bản ghi
     */
    public function count(string $condition = '', array $params = []): int {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        if (!empty($condition)) {
            $sql .= " WHERE " . $condition;
        }
        $row = $this->fetch($sql, $params);
        return (int)($row['total'] ?? 0);
    }
}
