<?php
// includes/database.php
// Cấu hình db
$host    = 'localhost';    
$db      = 'phone_store'; 
$user    = 'root';         
$pass    = '612004tra';    

// DSN (Thử thêm cổng nếu lỗi, ví dụ: port=3307)
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4"; 

// Tùy chọn cho PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);
    echo "✅ Kết nối thành công!"; 
} catch (\PDOException $e) { 
    // Bắt lỗi PDOException để có thông báo chi tiết
    die("LỖI KẾT NỐI CSDL: " . $e->getMessage()); 
}
?>