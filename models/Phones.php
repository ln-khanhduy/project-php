<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/database.php';

class Phone {
    private PDO $conn;
    private string $table = 'phones';

    // Thuộc tính để lưu thông tin sản phẩm khi đọc chi tiết
    public int $phone_id;
    public string $phone_name = '';
    public int $brand_id = 0;
    public string $brand_name = '';
    public ?int $category_id = null;
    public string $category_name = '';
    public float $price = 0.0;
    public string $image_url = '';
    public ?string $description = null;
    public int $stock = 0;
    public ?string $screen = null;
    public ?string $os = null;
    public ?string $cpu = null;
    public ?string $ram = null;
    public ?string $storage = null;
    public ?string $camera = null;
    public ?string $battery = null;
    public ?string $weight = null;
    public string $created_at = '';

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
                WHERE p.phone_name LIKE :keyword OR p.description LIKE :keyword OR b.brand_name LIKE :keyword
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

    public function readOne(int $phoneId): bool {
        $sql = "SELECT p.*, b.brand_name, c.category_name
                FROM {$this->table} p
                LEFT JOIN brands b ON p.brand_id = b.brand_id
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE p.phone_id = :phone_id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':phone_id', $phoneId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false;
        }

        // Gán dữ liệu vào thuộc tính của object
        $this->phone_id       = (int)$row['phone_id'];
        $this->phone_name     = $row['phone_name'];
        $this->brand_id       = (int)$row['brand_id'];
        $this->brand_name     = $row['brand_name'] ?? '';
        $this->category_id    = $row['category_id'] !== null ? (int)$row['category_id'] : null;
        $this->category_name  = $row['category_name'] ?? '';
        $this->price          = (float)$row['price'];
        $this->image_url      = $row['image_url'];
        $this->description    = $row['description'];
        $this->stock          = (int)$row['stock'];
        $this->screen         = $row['screen'];
        $this->os             = $row['os'];
        $this->cpu            = $row['cpu'];
        $this->ram            = $row['ram'];
        $this->storage        = $row['storage'];
        $this->camera         = $row['camera'];
        $this->battery        = $row['battery'];
        $this->weight         = $row['weight'];
        $this->created_at     = $row['created_at'];

        return true;
    }
}
?>