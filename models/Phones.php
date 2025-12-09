<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/database.php';

class Phone {
    private PDO $conn;
    private string $table = 'phones';

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function readAll(int $limit = 0): PDOStatement {
        $sql = "SELECT p.*, b.brand_name, c.category_name
                FROM {$this->table} p
                LEFT JOIN brands b ON p.brand_id = b.brand_id
                LEFT JOIN categories c ON p.category_id = c.category_id
                ORDER BY p.created_at DESC";
        if ($limit > 0) {
            $sql .= ' LIMIT :limit';
        }
        $stmt = $this->conn->prepare($sql);
        if ($limit > 0) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt;
    }

    public function readByBrand(int $brandId): PDOStatement {
        $sql = "SELECT p.*, b.brand_name, c.category_name
                FROM {$this->table} p
                LEFT JOIN brands b ON p.brand_id = b.brand_id
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE p.brand_id = :brand_id
                ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':brand_id', $brandId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function search(string $keyword): PDOStatement {
        $sql = "SELECT p.*, b.brand_name, c.category_name
                FROM {$this->table} p
                LEFT JOIN brands b ON p.brand_id = b.brand_id
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE p.phone_name LIKE :keyword OR p.description LIKE :keyword
                ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $like = '%' . $keyword . '%';
        $stmt->bindValue(':keyword', $like, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt;
    }

    public function readPopular(int $limit = 6): array {
        $sql = "SELECT p.*, b.brand_name, c.category_name
                FROM {$this->table} p
                LEFT JOIN brands b ON p.brand_id = b.brand_id
                LEFT JOIN categories c ON p.category_id = c.category_id
                ORDER BY p.stock DESC
                LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>