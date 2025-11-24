# Dự án PHP thuần - Backend & Frontend

Cấu trúc dự án:

```
DoAnPHP/
├── backend/               # Backend (PHP, Controllers, Models, Database)
│   ├── app/
│   │   ├── controllers/   # Xử lý logic
│   │   ├── models/        # Database & ORM
│   │   └── views/         # Template PHP
│   ├── sql/               # Database schema
│   ├── config.php         # Cấu hình DB
│   ├── bootstrap.php      # Khởi tạo ứng dụng
│   └── .env.example       # Biến môi trường mẫu
│
├── frontend/              # Frontend (HTML, Bootstrap, JS, CSS)
│   └── public/            # Document root (entry point)
│       ├── index.php      # Entry point
│       ├── .htaccess      # Apache routing
│       └── assets/        # CSS, JS, images
│           ├── css/
│           └── js/
│
├── routes.php             # Router (tùy chọn)
├── .gitignore
└── README.md
```

## Chạy nhanh (PHP built-in server)

```powershell
cd d:/DoAnPHP
php -S localhost:8000 -t frontend/public
```

Mở trình duyệt: `http://localhost:8000`

## Import database

Import file `backend/sql/schema.sql` vào MySQL:
```sql
-- Dùng phpMyAdmin hoặc MySQL CLI
mysql -u root < backend/sql/schema.sql
```

## Cấu hình

Chỉnh `backend/config.php`:
```php
'host' => '127.0.0.1',      // Server MySQL
'dbname' => 'doanphp',      // Tên database
'user' => 'root',           // User MySQL
'pass' => '',               // Password
```

## Cây thư mục chi tiết

- **backend/app/controllers/** : Chứa logic xử lý (HomeController, v.v.)
- **backend/app/models/** : Chứa Database wrapper, model khác
- **backend/app/views/** : Chứa template view (home.php, v.v.)
- **backend/sql/** : Chứa schema & migrate
- **frontend/public/assets/css/** : CSS (Bootstrap + custom)
- **frontend/public/assets/js/** : JavaScript
