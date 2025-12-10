<?php
declare(strict_types=1);

class Cart {
    private PDO $conn;
    private string $table = 'cart_items';

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function addItem(int $userId, int $phoneId, int $quantity): bool {
        // Kiểm tra item đã tồn tại
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id AND phone_id = :phone_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':phone_id' => $phoneId]);
        
        if ($stmt->rowCount() > 0) {
            // Update quantity
            $sql = "UPDATE {$this->table} SET quantity = quantity + :quantity WHERE user_id = :user_id AND phone_id = :phone_id";
        } else {
            // Insert new
            $sql = "INSERT INTO {$this->table} (user_id, phone_id, quantity) VALUES (:user_id, :phone_id, :quantity)";
        }
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':phone_id' => $phoneId,
            ':quantity' => $quantity
        ]);
    }

    public function getCartItems(int $userId): array {
        $sql = "SELECT ci.*, p.phone_name, p.price, p.stock_quantity, p.image_url 
                FROM {$this->table} ci
                LEFT JOIN phones p ON ci.phone_id = p.phone_id
                WHERE ci.user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function removeItem(int $userId, int $phoneId): bool {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id AND phone_id = :phone_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':user_id' => $userId, ':phone_id' => $phoneId]);
    }

    public function updateQuantity(int $userId, int $phoneId, int $quantity): bool {
        if ($quantity <= 0) {
            return $this->removeItem($userId, $phoneId);
        }
        
        $sql = "UPDATE {$this->table} SET quantity = :quantity WHERE user_id = :user_id AND phone_id = :phone_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':quantity' => $quantity,
            ':user_id' => $userId,
            ':phone_id' => $phoneId
        ]);
    }

    public function getCartTotal(int $userId): float {
        $sql = "SELECT COALESCE(SUM(ci.quantity * p.price), 0) as total 
                FROM {$this->table} ci
                LEFT JOIN phones p ON ci.phone_id = p.phone_id
                WHERE ci.user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return (float)$stmt->fetchColumn();
    }

    public function getCartCount(int $userId): int {
        $sql = "SELECT COALESCE(SUM(quantity), 0) FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function clearCart(int $userId): bool {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }
}
