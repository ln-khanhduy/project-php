<?php
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../models/Phones.php';
require_once '../includes/header.php';

$database = new Database();
$db = $database->getConnection();
$phone = new Phone($db);

// Phân trang & lọc sản phẩm
$perPage = 8;
$page = max(1, (int)($_GET['page'] ?? 1));
$brandFilter = filter_input(INPUT_GET, 'brand', FILTER_VALIDATE_INT);
$searchKeyword = trim((string)($_GET['q'] ?? ''));
$minPriceInput = filter_input(INPUT_GET, 'min_price', FILTER_VALIDATE_FLOAT);
$maxPriceInput = filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT);
$minPrice = $minPriceInput !== false ? $minPriceInput : null;
$maxPrice = $maxPriceInput !== false ? $maxPriceInput : null;

$filterParts = [];
$filterParams = [];
if ($brandFilter) {
    $filterParts[] = 'p.brand_id = :brand_id';
    $filterParams[':brand_id'] = $brandFilter;
}
if ($searchKeyword !== '') {
    $filterParts[] = '(p.phone_name LIKE :search OR p.description LIKE :search OR b.brand_name LIKE :search)';
    $filterParams[':search'] = '%' . $searchKeyword . '%';
}
if ($minPrice !== null) {
    $filterParts[] = 'p.price >= :min_price';
    $filterParams[':min_price'] = $minPrice;
}
if ($maxPrice !== null) {
    $filterParts[] = 'p.price <= :max_price';
    $filterParams[':max_price'] = $maxPrice;
}

$filterClause = $filterParts ? 'WHERE ' . implode(' AND ', $filterParts) : '';

$countQuery = 'SELECT COUNT(*) FROM phones p
               LEFT JOIN brands b ON p.brand_id = b.brand_id
               LEFT JOIN categories c ON p.category_id = c.category_id
               ' . $filterClause;
$countStmt = $db->prepare($countQuery);
$countStmt->execute($filterParams);
$totalPhones = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil(max(0, $totalPhones) / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$new_query = "SELECT p.*, b.brand_name, c.category_name
              FROM phones p
              LEFT JOIN brands b ON p.brand_id = b.brand_id
              LEFT JOIN categories c ON p.category_id = c.category_id
              $filterClause
              ORDER BY p.created_at DESC
              LIMIT :limit OFFSET :offset";
$new_stmt = $db->prepare($new_query);
$new_stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$new_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($filterParams as $key => $value) {
    $type = PDO::PARAM_STR;
    if (is_int($value)) {
        $type = PDO::PARAM_INT;
    }
    $new_stmt->bindValue($key, $value, $type);
}
$new_stmt->execute();
$phones = $new_stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách thương hiệu
$brands_query = "SELECT * FROM brands";
$brands_stmt = $db->prepare($brands_query);
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);

$currentBrand = null;
if ($brandFilter) {
    $brandDetailStmt = $db->prepare('SELECT * FROM brands WHERE brand_id = :brand_id LIMIT 1');
    $brandDetailStmt->execute([':brand_id' => $brandFilter]);
    $currentBrand = $brandDetailStmt->fetch(PDO::FETCH_ASSOC);
}

$bannerSlides = [
    SITE_URL . '/uploads/s24ultra.jpg',
    SITE_URL . '/uploads/xiaomi14.jpg',
    SITE_URL . '/uploads/iphone15pro.jpg'
];

$searchValue = htmlspecialchars($searchKeyword, ENT_QUOTES);    
$minPriceValue = $minPrice !== null ? (string)$minPrice : '';
$maxPriceValue = $maxPrice !== null ? (string)$maxPrice : '';

if (!function_exists('buildSearchQuery')) {
    function buildSearchQuery(array $overrides = []): string
    {
        $params = array_merge($_GET, $overrides);
        $filtered = array_filter($params, static function ($value) {
            return $value !== '' && $value !== null;
        });

        return $filtered ? '?' . http_build_query($filtered) : '';
    }
}
?>
<style>
    .hero-carousel {
        max-height: 280px;
        overflow: hidden;
    }
    .hero-carousel .carousel-inner {
        height: 280px;
    }
    .hero-carousel .carousel-item {
        height: 280px;
    }
    .hero-carousel .carousel-item img {
        height: 100%;
        width: 100%;
        object-fit: contain;
        object-position: center;
        background-color: #111;
    }
    .hero-carousel .carousel-caption {
        background: rgba(0, 0, 0, 0.65);
        right: auto;
        left: 1rem;
        bottom: 1.5rem;
        padding: 1.2rem;
    }
    .hero-carousel .carousel-caption h1 {
        font-size: 2rem;
    }
    .hero-carousel .carousel-caption .lead {
        font-size: 1rem;
    }
    .hero-carousel .carousel-control-prev-icon,
    .hero-carousel .carousel-control-next-icon {
        background-color: black;
        border-radius: 50%;
    }
</style>
<!-- Hero slider -->
<div id="homepageCarousel" class="carousel slide hero-carousel mb-5" data-bs-ride="carousel" data-bs-interval="2500">
    <div class="carousel-inner">
        <?php foreach ($bannerSlides as $index => $imageUrl): ?>
            <div class="carousel-item <?= $index === 0 ? 'active' : ''; ?>">
                <img src="<?= $imageUrl; ?>" class="d-block w-100" alt="Banner <?= $index + 1; ?>">
            </div>
        <?php endforeach; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#homepageCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#homepageCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>

<form action="index.php#phones" method="get" class="row g-3 mb-5 align-items-end bg-white border rounded p-3 shadow-sm">
    <div class="col-md-4">
        <label class="form-label fw-semibold">Từ khóa</label>
        <input type="search" name="q" value="<?= $searchValue; ?>" class="form-control" placeholder="Tên hoặc thương hiệu">
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Thương hiệu</label>
        <select name="brand" class="form-select">
            <option value="">Tất cả thương hiệu</option>
            <?php foreach ($brands as $brand): ?>
                <option value="<?= $brand['brand_id']; ?>" <?= $brandFilter === (int)$brand['brand_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($brand['brand_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label fw-semibold">Giá từ (₫)</label>
        <input type="number" name="min_price" min="0" step="100000" class="form-control" value="<?= $minPriceValue; ?>">
    </div>
    <div class="col-md-2">
        <label class="form-label fw-semibold">Giá đến (₫)</label>
        <input type="number" name="max_price" min="0" step="100000" class="form-control" value="<?= $maxPriceValue; ?>">
    </div>
    <div class="col-md-1 d-grid">
        <button class="btn btn-primary w-100">Áp dụng</button>
    </div>
</form>

<!-- Thương hiệu nổi bật -->
<div class="row mb-5">
    <div class="col-12">
        <h2 class="text-center mb-4">Thương hiệu nổi bật</h2>
        <div class="row text-center align-items-center">
            <div class="col-md-3 col-6 mb-3 d-flex align-items-center justify-content-center">
                <a href="index.php<?= buildSearchQuery(['brand' => null, 'q' => null, 'min_price' => null, 'max_price' => null, 'page' => 1]); ?>#phones" class="btn btn-outline-primary w-100 py-3">Xem tất cả</a>
            </div>
            <?php foreach ($brands as $brand): ?>
            <div class="col-md-3 col-6 mb-3">
                <a href="index.php<?= buildSearchQuery(['brand' => $brand['brand_id'], 'page' => 1]); ?>#phones" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title text-dark"><?= htmlspecialchars($brand['brand_name']); ?></h5>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Điện thoại -->
<div class="row mb-5" id="phones">
    <div class="col-12">
        <h2 class="text-center mb-4">
            <?= $currentBrand ? 'Điện thoại ' . htmlspecialchars($currentBrand['brand_name']) : 'Danh sách điện thoại'; ?>
        </h2>
    </div>
    
    <?php if (!empty($phones)): ?>
        <?php foreach ($phones as $product): ?>
            <div class="col-lg-3 col-md-4 col-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="image-container" style="height: 225px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                        <img src="<?= SITE_URL; ?>/uploads/<?= htmlspecialchars($product['image_url']); ?>" 
                             class="card-img-top product-image" 
                             alt="<?= htmlspecialchars($product['phone_name']); ?>"
                             style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain;"
                             onerror="this.src='../images/default.jpg'">
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?= htmlspecialchars($product['phone_name']); ?></h6>
                        <p class="card-text">
                            <small class="text-muted"><?= htmlspecialchars($product['brand_name']); ?></small>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="price text-danger fw-bold"><?= formatPrice($product['price']); ?></span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent p-2">
                        <div class="d-grid gap-1">
                            <a href="product_detail.php?id=<?= $product['phone_id']; ?>" 
                               class="btn btn-outline-primary btn-sm">Chi tiết</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="col-12">
            <nav aria-label="Trang sản phẩm">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="index.php<?= buildSearchQuery(['page' => $i]); ?>#phones"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php else: ?>
        <div class="col-12 text-center">
            <p class="text-muted">Không có điện thoại nào phù hợp.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Features Section -->
<div class="row mb-5">
    <div class="col-md-3 col-6 text-center mb-3">
        <div class="border rounded p-3 h-100">
            <i class="fas fa-shipping-fast fa-2x text-primary mb-2"></i>
            <h6>Miễn phí vận chuyển</h6>
            <small class="text-muted">Cho đơn hàng từ 5 triệu</small>
        </div>
    </div>
    <div class="col-md-3 col-6 text-center mb-3">
        <div class="border rounded p-3 h-100">
            <i class="fas fa-shield-alt fa-2x text-primary mb-2"></i>
            <h6>Bảo hành chính hãng</h6>
            <small class="text-muted">12-24 tháng</small>
        </div>
    </div>
    <div class="col-md-3 col-6 text-center mb-3">
        <div class="border rounded p-3 h-100">
            <i class="fas fa-undo-alt fa-2x text-primary mb-2"></i>
            <h6>Đổi trả dễ dàng</h6>
            <small class="text-muted">Trong 7 ngày</small>
        </div>
    </div>
    <div class="col-md-3 col-6 text-center mb-3">
        <div class="border rounded p-3 h-100">
            <i class="fas fa-headset fa-2x text-primary mb-2"></i>
            <h6>Hỗ trợ 24/7</h6>
            <small class="text-muted">Hotline: 1800-1234</small>
        </div>
    </div>
</div>

<script defer src="js/home.js"></script>

<?php require_once '../includes/footer.php'; ?>