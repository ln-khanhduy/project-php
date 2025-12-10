<?php
declare(strict_types=1);

class Brand {
    private PDO $conn;
    private string $table = 'brands';

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function create(array $data): int|false {
        $sql = "INSERT INTO {$this->table} (brand_name, brand_logo, description) 
                VALUES (:brand_name, :brand_logo, :description)";
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':brand_name' => $data['brand_name'],
            ':brand_logo' => $data['brand_logo'] ?? null,
            ':description' => $data['description'] ?? null
        ]);
        return $result ? (int)$this->conn->lastInsertId() : false;
    }

    public function findById(int $id): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE brand_id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getAll(): array {
        $sql = "SELECT b.*, COUNT(p.phone_id) as product_count 
                FROM {$this->table} b
                LEFT JOIN phones p ON b.brand_id = p.brand_id
                GROUP BY b.brand_id
                ORDER BY b.brand_name";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['brand_name'])) {
            $fields[] = 'brand_name = :brand_name';
            $params[':brand_name'] = $data['brand_name'];
        }
        if (isset($data['brand_logo'])) {
            $fields[] = 'brand_logo = :brand_logo';
            $params[':brand_logo'] = $data['brand_logo'];
        }
        if (isset($data['description'])) {
            $fields[] = 'description = :description';
            $params[':description'] = $data['description'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE brand_id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool {
        $sql = "DELETE FROM {$this->table} WHERE brand_id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function hasProducts(int $id): bool {
        $sql = "SELECT COUNT(*) FROM phones WHERE brand_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
