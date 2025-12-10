<?php
declare(strict_types=1);

class Category {
    private PDO $conn;
    private string $table = 'categories';

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function create(array $data): int|false {
        $sql = "INSERT INTO {$this->table} (category_name, description) 
                VALUES (:category_name, :description)";
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':category_name' => $data['category_name'],
            ':description' => $data['description'] ?? null
        ]);
        return $result ? (int)$this->conn->lastInsertId() : false;
    }

    public function findById(int $id): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE category_id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getAll(): array {
        $sql = "SELECT c.*, COUNT(p.phone_id) as product_count 
                FROM {$this->table} c
                LEFT JOIN phones p ON c.category_id = p.category_id
                GROUP BY c.category_id
                ORDER BY c.category_name";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['category_name'])) {
            $fields[] = 'category_name = :category_name';
            $params[':category_name'] = $data['category_name'];
        }
        if (isset($data['description'])) {
            $fields[] = 'description = :description';
            $params[':description'] = $data['description'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE category_id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool {
        $sql = "DELETE FROM {$this->table} WHERE category_id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function hasProducts(int $id): bool {
        $sql = "SELECT COUNT(*) FROM phones WHERE category_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
