<?php
declare(strict_types=1);

require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$perPage = max(20, (int)($_GET['per_page'] ?? 20));
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
$alertError = '';
try {
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($filterParams);
    $totalPhones = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil(max(0, $totalPhones) / $perPage));
    $page = min($page, $totalPages);
    $offset = max(0, ($page - 1) * $perPage);
} catch (Throwable $e) {
    $alertError = 'Không thể tải danh sách sản phẩm (đếm tổng).';
    $totalPhones = 0;
    $totalPages = 1;
    $page = 1;
    $offset = 0;
}

$limitInt = (int)$perPage;
$offsetInt = (int)$offset;

$listQuery = "SELECT p.*, b.brand_name, c.category_name
              FROM phones p
              LEFT JOIN brands b ON p.brand_id = b.brand_id
              LEFT JOIN categories c ON p.category_id = c.category_id
              $filterClause
              ORDER BY p.created_at DESC
              LIMIT $limitInt OFFSET $offsetInt";
try {
    $listStmt = $db->prepare($listQuery);
    foreach ($filterParams as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $listStmt->bindValue($key, $value, $type);
    }
    $listStmt->execute();
    $phones = $listStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $alertError = 'Không thể tải danh sách sản phẩm (truy vấn chính).';
    // Fallback: lấy 20 sản phẩm mới nhất không lọc để trang không trống
    try {
        $fallbackSql = "SELECT p.*, b.brand_name, c.category_name
                        FROM phones p
                        LEFT JOIN brands b ON p.brand_id = b.brand_id
                        LEFT JOIN categories c ON p.category_id = c.category_id
                        ORDER BY p.created_at DESC
                        LIMIT 20";
        $fallbackStmt = $db->query($fallbackSql);
        $phones = $fallbackStmt ? $fallbackStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Throwable $inner) {
        $alertError .= ' | Fallback lỗi.';
        $phones = [];
    }
}

$brands_stmt = $db->prepare('SELECT * FROM brands ORDER BY brand_name');
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);

$currentBrand = null;
if ($brandFilter) {
    $brandDetailStmt = $db->prepare('SELECT * FROM brands WHERE brand_id = :brand_id LIMIT 1');
    $brandDetailStmt->execute([':brand_id' => $brandFilter]);
    $currentBrand = $brandDetailStmt->fetch(PDO::FETCH_ASSOC);
}

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
<div class="row mb-4">
    <div class="col-lg-3 col-md-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Bộ lọc</h5>
                <form method="get" action="">
                    <div class="mb-3">
                        <label class="form-label">Tìm kiếm</label>
                        <input type="search" name="q" class="form-control" placeholder="Tên sản phẩm, mô tả" value="<?= $searchValue; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Thương hiệu</label>
                        <select name="brand" class="form-select">
                            <option value="">Tất cả</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= $brand['brand_id']; ?>" <?= ($brandFilter == $brand['brand_id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($brand['brand_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Giá từ</label>
                            <input type="number" step="0.01" name="min_price" class="form-control" value="<?= $minPriceValue; ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Đến</label>
                            <input type="number" step="0.01" name="max_price" class="form-control" value="<?= $maxPriceValue; ?>">
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Lọc</button>
                        <a href="products.php" class="btn btn-outline-secondary">Xóa lọc</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-9 col-md-8">
        <?php if (!empty($alertError)): ?>
            <div class="alert alert-danger mb-3"><?= htmlspecialchars($alertError); ?></div>
        <?php elseif (empty($phones)): ?>
            <div class="alert alert-warning mb-3">Không có sản phẩm để hiển thị.</div>
        <?php endif; ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">
                <?= $currentBrand ? 'Điện thoại ' . htmlspecialchars($currentBrand['brand_name']) : 'Tất cả sản phẩm'; ?>
                <span class="text-muted small ms-2">(<?= $totalPhones; ?> sản phẩm)</span>
            </h4>
        </div>

        <?php if (!empty($phones)): ?>
            <div class="row">
                <?php foreach ($phones as $product): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="image-container" style="height: 225px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                  <?php
                                $placeholder = 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400"><rect width="100%" height="100%" fill="#f8f9fa"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-size="20" fill="#999">No Image</text></svg>');
                                $img = isset($product['image_url']) && is_string($product['image_url']) && $product['image_url'] !== ''
                                    ? $product['image_url']
                                    : null;
                                $imgUrl = $img ? SITE_URL . '/uploads/' . htmlspecialchars($img) : $placeholder;
                                  ?>
                                <img src="<?= $imgUrl; ?>" 
                                      class="card-img-top product-image" 
                                      alt="<?= htmlspecialchars($product['phone_name']); ?>"
                                      style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain;"
                                     onerror="this.src='<?= $placeholder; ?>'">
                            </div>
                            <div class="card-body">
                                <h6 class="card-title mb-1"><?= htmlspecialchars($product['phone_name']); ?></h6>
                                <p class="card-text mb-1">
                                    <small class="text-muted"><?= htmlspecialchars($product['brand_name']); ?></small>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="price text-danger fw-bold"><?= formatPrice($product['price']); ?></span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent p-2">
                                <div class="d-grid gap-1">
                                    <a href="product_detail.php?id=<?= $product['phone_id']; ?>" class="btn btn-outline-primary btn-sm">Chi tiết</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <nav aria-label="Trang sản phẩm">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="products.php<?= buildSearchQuery(['page' => $i]); ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php else: ?>
            <div class="alert alert-info">Không tìm thấy sản phẩm phù hợp.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php';
