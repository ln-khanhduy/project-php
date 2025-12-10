<?php
declare(strict_types=1);

class Payment {
    private PDO $conn;
    private string $table = 'payments';

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function create(array $data): int|false {
        $sql = "INSERT INTO {$this->table} (order_id, user_id, amount, payment_method, status, transaction_id) 
                VALUES (:order_id, :user_id, :amount, :payment_method, :status, :transaction_id)";
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':order_id' => $data['order_id'],
            ':user_id' => $data['user_id'],
            ':amount' => $data['amount'],
            ':payment_method' => $data['payment_method'],
            ':status' => $data['status'] ?? 'pending',
            ':transaction_id' => $data['transaction_id'] ?? null
        ]);
        return $result ? (int)$this->conn->lastInsertId() : false;
    }

    public function findById(int $id): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE payment_id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getByOrder(int $orderId): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE order_id = :order_id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);
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

    public function updateStatus(int $id, string $status): bool {
        $sql = "UPDATE {$this->table} SET status = :status WHERE payment_id = :id";
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
        if (isset($data['amount'])) {
            $fields[] = 'amount = :amount';
            $params[':amount'] = $data['amount'];
        }
        if (isset($data['transaction_id'])) {
            $fields[] = 'transaction_id = :transaction_id';
            $params[':transaction_id'] = $data['transaction_id'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE payment_id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function getTotalByStatus(string $status): float {
        $sql = "SELECT COALESCE(SUM(amount), 0) FROM {$this->table} WHERE status = :status";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':status' => $status]);
        return (float)$stmt->fetchColumn();
    }

    public function getTotalRevenue(): float {
        $sql = "SELECT COALESCE(SUM(amount), 0) FROM {$this->table} WHERE status = 'completed'";
        $stmt = $this->conn->query($sql);
        return (float)$stmt->fetchColumn();
    }
}
