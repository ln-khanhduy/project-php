<?php
declare(strict_types=1);

require_once '../../config/config.php';
require_once '../../includes/database.php';
require_once '../../includes/admin_auth.php';
require_once '../../models/Phones.php';

require_admin();

$database = new Database();
$db = $database->getConnection();
$phoneModel = new Phone($db);

$message = '';
$alertType = 'success';

// Hàm xử lý upload ảnh
function handleImageUpload($fileInput) {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // Không có file upload
    }
    
    if ($_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Lỗi upload ảnh: ' . $_FILES[$fileInput]['error']);
    }
    
    $file = $_FILES[$fileInput];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Định dạng ảnh không hợp lệ. Vui lòng dùng JPG, PNG, GIF hoặc WebP.');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('Ảnh quá lớn. Tối đa 5MB.');
    }
    
    // Sử dụng đường dẫn tuyệt đối
    $uploadsDir = dirname(dirname(__DIR__)) . '/uploads/';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
    $filepath = $uploadsDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Không thể lưu ảnh. Vui lòng kiểm tra quyền folder.');
    }
    
    return '/project-php/uploads/' . $filename;
}

// Xử lý form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Thêm sản phẩm
        if (isset($_POST['add_product'])) {
            $imageUrl = handleImageUpload('product_image');
            
            // Nếu không upload file, dùng URL nếu có
            if (!$imageUrl && !empty($_POST['image_url'])) {
                $imageUrl = trim($_POST['image_url']);
            }
            
            $stmt = $db->prepare("INSERT INTO phones (brand_id, category_id, phone_name, price, stock_quantity, description, specifications, image_url, screen, os, cpu, ram, storage, camera, battery, sim, weight) 
                                   VALUES (:brand_id, :category_id, :name, :price, :stock, :desc, :specs, :image, :screen, :os, :cpu, :ram, :storage, :camera, :battery, :sim, :weight)");
            $stmt->execute([
                ':brand_id' => (int)$_POST['brand_id'],
                ':category_id' => (int)$_POST['category_id'],
                ':name' => trim($_POST['phone_name']),
                ':price' => (float)$_POST['price'],
                ':stock' => (int)$_POST['stock_quantity'],
                ':desc' => trim($_POST['description'] ?? ''),
                ':specs' => trim($_POST['specifications'] ?? ''),
                ':image' => $imageUrl ?? '',
                ':screen' => trim($_POST['screen'] ?? ''),
                ':os' => trim($_POST['os'] ?? ''),
                ':cpu' => trim($_POST['cpu'] ?? ''),
                ':ram' => trim($_POST['ram'] ?? ''),
                ':storage' => trim($_POST['storage'] ?? ''),
                ':camera' => trim($_POST['camera'] ?? ''),
                ':battery' => trim($_POST['battery'] ?? ''),
                ':sim' => trim($_POST['sim'] ?? ''),
                ':weight' => trim($_POST['weight'] ?? '')
            ]);
            $message = 'Thêm sản phẩm thành công!';
        }
        
        // Cập nhật sản phẩm
        elseif (isset($_POST['edit_product'])) {
            $imageUrl = handleImageUpload('product_image');
            
            // Nếu không upload file mới, dùng URL mới nếu có hoặc giữ ảnh cũ
            if (!$imageUrl) {
                if (!empty($_POST['image_url'])) {
                    $imageUrl = trim($_POST['image_url']);
                } else {
                    // Giữ lại ảnh cũ
                    $stmt = $db->prepare("SELECT image_url FROM phones WHERE phone_id = :phone_id");
                    $stmt->execute([':phone_id' => (int)$_POST['phone_id']]);
                    $oldPhone = $stmt->fetch(PDO::FETCH_ASSOC);
                    $imageUrl = $oldPhone['image_url'] ?? '';
                }
            }
            
            $stmt = $db->prepare("UPDATE phones SET brand_id=:brand_id, category_id=:category_id, phone_name=:name, 
                                   price=:price, stock_quantity=:stock, description=:desc, specifications=:specs, image_url=:image,
                                   screen=:screen, os=:os, cpu=:cpu, ram=:ram, storage=:storage, camera=:camera, battery=:battery, sim=:sim, weight=:weight
                                   WHERE phone_id=:phone_id");
            $stmt->execute([
                ':phone_id' => (int)$_POST['phone_id'],
                ':brand_id' => (int)$_POST['brand_id'],
                ':category_id' => (int)$_POST['category_id'],
                ':name' => trim($_POST['phone_name']),
                ':price' => (float)$_POST['price'],
                ':stock' => (int)$_POST['stock_quantity'],
                ':desc' => trim($_POST['description'] ?? ''),
                ':specs' => trim($_POST['specifications'] ?? ''),
                ':image' => $imageUrl,
                ':screen' => trim($_POST['screen'] ?? ''),
                ':os' => trim($_POST['os'] ?? ''),
                ':cpu' => trim($_POST['cpu'] ?? ''),
                ':ram' => trim($_POST['ram'] ?? ''),
                ':storage' => trim($_POST['storage'] ?? ''),
                ':camera' => trim($_POST['camera'] ?? ''),
                ':battery' => trim($_POST['battery'] ?? ''),
                ':sim' => trim($_POST['sim'] ?? ''),
                ':weight' => trim($_POST['weight'] ?? '')
            ]);
            $message = 'Cập nhật sản phẩm thành công!';
        }
        
        // Xóa sản phẩm
        elseif (isset($_POST['delete_product'])) {
            $stmt = $db->prepare("DELETE FROM phones WHERE phone_id = :phone_id");
            $stmt->execute([':phone_id' => (int)$_POST['phone_id']]);
            $message = 'Xóa sản phẩm thành công!';
        }
    } catch (Exception $e) {
        $message = 'Lỗi: ' . $e->getMessage();
        $alertType = 'danger';
    }
}

// Lấy danh sách sản phẩm
$products = [];
$brands = [];
$categories = [];

try {
    $stmt = $db->query("SELECT p.phone_id, p.brand_id, p.category_id, p.phone_name, p.price, p.stock, p.description, p.image_url, p.created_at, b.brand_name, c.category_name 
                        FROM phones p 
                        LEFT JOIN brands b ON p.brand_id = b.brand_id 
                        LEFT JOIN categories c ON p.category_id = c.category_id 
                        ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->query("SELECT * FROM brands ORDER BY brand_name");
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->query("SELECT * FROM categories ORDER BY category_name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Products error: ' . $e->getMessage());
}

require_once '../../includes/header.php';

$activePage = 'products';
$pageTitle = 'Quản lý sản phẩm';
$buttonText = '+ Thêm sản phẩm mới';
$buttonModal = 'addProductModal';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../../includes/admin_sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php include '../../includes/admin_header.php'; ?>
            <?php include '../../includes/admin_alert.php'; ?>

            <!-- Products Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hình ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Thương hiệu</th>
                            <th>Danh mục</th>
                            <th>Giá</th>
                            <th>Tồn kho</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" class="text-center">Chưa có sản phẩm nào</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= $product['phone_id']; ?></td>
                                <td>
                                    <?php if (!empty($product['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($product['image_url']); ?>" alt="" style="width:50px;height:50px;object-fit:cover;">
                                    <?php else: ?>
                                    <div style="width:50px;height:50px;background:#ddd;"></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($product['phone_name']); ?></td>
                                <td><?= htmlspecialchars($product['brand_name'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                <td><?= number_format((float)$product['price']); ?>đ</td>
                                <td>
                                    <span class="badge bg-<?= ($product['stock_quantity'] ?? 0) > 0 ? 'success' : 'danger'; ?>">
                                        <?= $product['stock_quantity'] ?? 0; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editProduct(<?= htmlspecialchars(json_encode($product)); ?>)">
                                        ✏️ Sửa
                                    </button>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Xác nhận xóa sản phẩm này?');">
                                        <input type="hidden" name="phone_id" value="<?= $product['phone_id']; ?>">
                                        <button type="submit" name="delete_product" class="btn btn-sm btn-danger">🗑️ Xóa</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm sản phẩm mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tên sản phẩm *</label>
                            <input type="text" name="phone_name" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Thương hiệu *</label>
                            <select name="brand_id" class="form-select" required>
                                <option value="">Chọn...</option>
                                <?php foreach ($brands as $brand): ?>
                                <option value="<?= $brand['brand_id']; ?>"><?= htmlspecialchars($brand['brand_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Danh mục *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Chọn...</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id']; ?>"><?= htmlspecialchars($cat['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Giá (VNĐ) *</label>
                            <input type="number" name="price" class="form-control" min="0" step="1000" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tồn kho *</label>
                            <input type="number" name="stock_quantity" class="form-control" min="0" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Hình ảnh</label>
                            <input type="file" name="product_image" class="form-control" accept="image/*">
                            <small class="text-muted">Hoặc nhập URL nếu không upload file</small>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">URL Hình ảnh (nếu không có file)</label>
                            <input type="text" name="image_url" class="form-control" placeholder="https://...">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Thông số kỹ thuật (miêu tả chi tiết)</label>
                            <textarea name="specifications" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <!-- Thông số kỹ thuật cụ thể -->
                        <h6 class="col-12 mt-3 mb-3">Thông số kỹ thuật chi tiết</h6>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Màn hình</label>
                            <input type="text" name="screen" class="form-control" placeholder="VD: 6.1 inch OLED">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hệ điều hành</label>
                            <input type="text" name="os" class="form-control" placeholder="VD: iOS 17">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bộ xử lý</label>
                            <input type="text" name="cpu" class="form-control" placeholder="VD: Apple A17 Pro">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">RAM</label>
                            <input type="text" name="ram" class="form-control" placeholder="VD: 8GB">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bộ nhớ trong</label>
                            <input type="text" name="storage" class="form-control" placeholder="VD: 256GB">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Camera</label>
                            <input type="text" name="camera" class="form-control" placeholder="VD: 48MP + 12MP">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pin</label>
                            <input type="text" name="battery" class="form-control" placeholder="VD: 3274 mAh">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SIM</label>
                            <input type="text" name="sim" class="form-control" placeholder="VD: 2 SIM (nano + eSIM)">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Trọng lượng</label>
                            <input type="text" name="weight" class="form-control" placeholder="VD: 187g">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="add_product" class="btn btn-primary">Thêm sản phẩm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="phone_id" id="edit_phone_id">
                <div class="modal-header">
                    <h5 class="modal-title">Chỉnh sửa sản phẩm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tên sản phẩm *</label>
                            <input type="text" name="phone_name" id="edit_phone_name" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Thương hiệu *</label>
                            <select name="brand_id" id="edit_brand_id" class="form-select" required>
                                <?php foreach ($brands as $brand): ?>
                                <option value="<?= $brand['brand_id']; ?>"><?= htmlspecialchars($brand['brand_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Danh mục *</label>
                            <select name="category_id" id="edit_category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id']; ?>"><?= htmlspecialchars($cat['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Giá (VNĐ) *</label>
                            <input type="number" name="price" id="edit_price" class="form-control" min="0" step="1000" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tồn kho *</label>
                            <input type="number" name="stock_quantity" id="edit_stock" class="form-control" min="0" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Hình ảnh</label>
                            <input type="file" name="product_image" class="form-control" accept="image/*">
                            <small class="text-muted">Để trống nếu không thay đổi ảnh</small>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">URL Hình ảnh (nếu không có file)</label>
                            <input type="text" name="image_url" id="edit_image" class="form-control">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Thông số kỹ thuật (miêu tả chi tiết)</label>
                            <textarea name="specifications" id="edit_specs" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <!-- Thông số kỹ thuật cụ thể -->
                        <h6 class="col-12 mt-3 mb-3">Thông số kỹ thuật chi tiết</h6>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Màn hình</label>
                            <input type="text" name="screen" id="edit_screen" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hệ điều hành</label>
                            <input type="text" name="os" id="edit_os" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bộ xử lý</label>
                            <input type="text" name="cpu" id="edit_cpu" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">RAM</label>
                            <input type="text" name="ram" id="edit_ram" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bộ nhớ trong</label>
                            <input type="text" name="storage" id="edit_storage" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Camera</label>
                            <input type="text" name="camera" id="edit_camera" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pin</label>
                            <input type="text" name="battery" id="edit_battery" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SIM</label>
                            <input type="text" name="sim" id="edit_sim" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Trọng lượng</label>
                            <input type="text" name="weight" id="edit_weight" class="form-control">
                        </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="edit_product" class="btn btn-warning">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/admin_styles.php'; ?>

<script>
function editProduct(product) {
    document.getElementById('edit_phone_id').value = product.phone_id;
    document.getElementById('edit_phone_name').value = product.phone_name;
    document.getElementById('edit_brand_id').value = product.brand_id;
    document.getElementById('edit_category_id').value = product.category_id;
    document.getElementById('edit_price').value = product.price;
    document.getElementById('edit_stock').value = product.stock_quantity;
    document.getElementById('edit_image').value = product.image_url || '';
    document.getElementById('edit_description').value = product.description || '';
    document.getElementById('edit_specs').value = product.specifications || '';
    document.getElementById('edit_screen').value = product.screen || '';
    document.getElementById('edit_os').value = product.os || '';
    document.getElementById('edit_cpu').value = product.cpu || '';
    document.getElementById('edit_ram').value = product.ram || '';
    document.getElementById('edit_storage').value = product.storage || '';
    document.getElementById('edit_camera').value = product.camera || '';
    document.getElementById('edit_battery').value = product.battery || '';
    document.getElementById('edit_sim').value = product.sim || '';
    document.getElementById('edit_weight').value = product.weight || '';
    
    new bootstrap.Modal(document.getElementById('editProductModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>
