<?php
declare(strict_types=1);

class User {
    private PDO $conn;
    private string $table = 'users';

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function create(array $data): bool {
        $sql = "INSERT INTO {$this->table} (full_name, email, password, role, provider, oauth_id) 
                VALUES (:full_name, :email, :password, :role, :provider, :oauth_id)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':full_name' => $data['full_name'] ?? null,
            ':email' => $data['email'],
            ':password' => $data['password'] ?? null,
            ':role' => $data['role'] ?? 'customer',
            ':provider' => $data['provider'] ?? 'local',
            ':oauth_id' => $data['oauth_id'] ?? null
        ]);
    }

    public function findByEmail(string $email): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findById(int $id): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getAll(int $limit = 0, int $offset = 0): array {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        if ($limit > 0) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }
        $stmt = $this->conn->prepare($sql);
        if ($limit > 0) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['full_name'])) {
            $fields[] = 'full_name = :full_name';
            $params[':full_name'] = $data['full_name'];
        }
        if (isset($data['email'])) {
            $fields[] = 'email = :email';
            $params[':email'] = $data['email'];
        }
        if (isset($data['password'])) {
            $fields[] = 'password = :password';
            $params[':password'] = $data['password'];
        }
        if (isset($data['role'])) {
            $fields[] = 'role = :role';
            $params[':role'] = $data['role'];
        }
        if (isset($data['is_locked'])) {
            $fields[] = 'is_locked = :is_locked';
            $params[':is_locked'] = $data['is_locked'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE user_id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function getTotalSpent(int $userId): float {
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return (float)$stmt->fetchColumn();
    }

    public function getTotalOrders(int $userId): int {
        $sql = "SELECT COUNT(*) FROM orders WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function lock(int $id): bool {
        return $this->update($id, ['is_locked' => 1]);
    }

    public function unlock(int $id): bool {
        return $this->update($id, ['is_locked' => 0]);
    }
}
