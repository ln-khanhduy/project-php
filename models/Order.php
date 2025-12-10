<?php
declare(strict_types=1);

class Order {
    private PDO $conn;
    private string $table = 'orders';

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function create(array $data): int|false {
        $sql = "INSERT INTO {$this->table} (user_id, total_amount, status, payment_method, shipping_address) 
                VALUES (:user_id, :total_amount, :status, :payment_method, :shipping_address)";
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':user_id' => $data['user_id'],
            ':total_amount' => $data['total_amount'],
            ':status' => $data['status'] ?? 'pending',
            ':payment_method' => $data['payment_method'] ?? 'cod',
            ':shipping_address' => $data['shipping_address'] ?? null
        ]);
        return $result ? (int)$this->conn->lastInsertId() : false;
    }

    public function findById(int $id): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE order_id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getByUser(int $userId, int $limit = 0, int $offset = 0): array {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC";
        if ($limit > 0) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        if ($limit > 0) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll(int $limit = 0, int $offset = 0): array {
        $sql = "SELECT o.*, u.full_name, u.email FROM {$this->table} o
                LEFT JOIN users u ON o.user_id = u.user_id
                ORDER BY o.created_at DESC";
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

    public function getByStatus(string $status, int $limit = 0, int $offset = 0): array {
        $sql = "SELECT o.*, u.full_name, u.email FROM {$this->table} o
                LEFT JOIN users u ON o.user_id = u.user_id
                WHERE o.status = :status
                ORDER BY o.created_at DESC";
        if ($limit > 0) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':status', $status);
        if ($limit > 0) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $id, string $status): bool {
        $sql = "UPDATE {$this->table} SET status = :status WHERE order_id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['status'])) {
            $fields[] = 'status = :status';
            $params[':status'] = $data['status'];
        }
        if (isset($data['total_amount'])) {
            $fields[] = 'total_amount = :total_amount';
            $params[':total_amount'] = $data['total_amount'];
        }
        if (isset($data['payment_method'])) {
            $fields[] = 'payment_method = :payment_method';
            $params[':payment_method'] = $data['payment_method'];
        }
        if (isset($data['shipping_address'])) {
            $fields[] = 'shipping_address = :shipping_address';
            $params[':shipping_address'] = $data['shipping_address'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE order_id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool {
        $sql = "DELETE FROM {$this->table} WHERE order_id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function getTotal(): int {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $stmt = $this->conn->query($sql);
        return (int)$stmt->fetchColumn();
    }

    public function getTotalRevenue(): float {
        $sql = "SELECT COALESCE(SUM(total_amount), 0) FROM {$this->table}";
        $stmt = $this->conn->query($sql);
        return (float)$stmt->fetchColumn();
    }
}
