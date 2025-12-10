<?php
declare(strict_types=1);

require_once '../../config/config.php';
require_once '../../includes/database.php';
require_once '../../includes/admin_auth.php';
require_once '../../models/Brand.php';
require_once '../../models/Category.php';

require_admin();

$database = new Database();
$db = $database->getConnection();
$brandModel = new Brand($db);
$categoryModel = new Category($db);

$message = '';
$alertType = 'success';

// Xử lý form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Thêm thương hiệu
        if (isset($_POST['add_brand'])) {
            $brandModel->create(['brand_name' => trim($_POST['brand_name'])]);
            $message = 'Thêm thương hiệu thành công!';
        }
        
        // Sửa thương hiệu
        elseif (isset($_POST['edit_brand'])) {
            $brandModel->update((int)$_POST['brand_id'], ['brand_name' => trim($_POST['brand_name'])]);
            $message = 'Cập nhật thương hiệu thành công!';
        }
        
        // Xóa thương hiệu
        elseif (isset($_POST['delete_brand'])) {
            $brandId = (int)$_POST['brand_id'];
            if ($brandModel->hasProducts($brandId)) {
                $message = 'Không thể xóa! Thương hiệu này đang có sản phẩm.';
                $alertType = 'danger';
            } else {
                $brandModel->delete($brandId);
                $message = 'Xóa thương hiệu thành công!';
            }
        }
        
        // Thêm danh mục
        elseif (isset($_POST['add_category'])) {
            $categoryModel->create(['category_name' => trim($_POST['category_name'])]);
            $message = 'Thêm danh mục thành công!';
        }
        
        // Sửa danh mục
        elseif (isset($_POST['edit_category'])) {
            $categoryModel->update((int)$_POST['category_id'], ['category_name' => trim($_POST['category_name'])]);
            $message = 'Cập nhật danh mục thành công!';
        }
        
        // Xóa danh mục
        elseif (isset($_POST['delete_category'])) {
            $categoryId = (int)$_POST['category_id'];
            if ($categoryModel->hasProducts($categoryId)) {
                $message = 'Không thể xóa! Danh mục này đang có sản phẩm.';
                $alertType = 'danger';
            } else {
                $categoryModel->delete($categoryId);
                $message = 'Xóa danh mục thành công!';
            }
        }
    } catch (Exception $e) {
        $message = 'Lỗi: ' . $e->getMessage();
        $alertType = 'danger';
    }
}

// Lấy danh sách
$brands = $brandModel->getAll();
$categories = $categoryModel->getAll();

require_once '../../includes/header.php';

$activePage = 'brands';
$pageTitle = 'Thương hiệu & Danh mục';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../../includes/admin_sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php include '../../includes/admin_header.php'; ?>
            <?php include '../../includes/admin_alert.php'; ?>

            <div class="row">
                <!-- Brands -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Thương hiệu</h5>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                                + Thêm mới
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Tên thương hiệu</th>
                                            <th>Sản phẩm</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($brands)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Chưa có thương hiệu</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($brands as $brand): ?>
                                            <tr>
                                                <td><?= $brand['brand_id']; ?></td>
                                                <td><strong><?= htmlspecialchars($brand['brand_name']); ?></strong></td>
                                                <td><?= $brand['product_count']; ?></td>
                                                <td>
                                                    <button class="btn btn-xs btn-warning" onclick="editBrand(<?= $brand['brand_id']; ?>, '<?= htmlspecialchars($brand['brand_name'], ENT_QUOTES); ?>')">
                                                        ✏️
                                                    </button>
                                                    <form method="post" style="display:inline;" onsubmit="return confirm('Xác nhận xóa?');">
                                                        <input type="hidden" name="brand_id" value="<?= $brand['brand_id']; ?>">
                                                        <button type="submit" name="delete_brand" class="btn btn-xs btn-danger" <?= $brand['product_count'] > 0 ? 'disabled' : ''; ?>>
                                                            🗑️
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Categories -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Danh mục</h5>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                + Thêm mới
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Tên danh mục</th>
                                            <th>Sản phẩm</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($categories)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Chưa có danh mục</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($categories as $cat): ?>
                                            <tr>
                                                <td><?= $cat['category_id']; ?></td>
                                                <td><strong><?= htmlspecialchars($cat['category_name']); ?></strong></td>
                                                <td><?= $cat['product_count']; ?></td>
                                                <td>
                                                    <button class="btn btn-xs btn-warning" onclick="editCategory(<?= $cat['category_id']; ?>, '<?= htmlspecialchars($cat['category_name'], ENT_QUOTES); ?>')">
                                                    </button>
                                                    <form method="post" style="display:inline;" onsubmit="return confirm('Xác nhận xóa?');">
                                                        <input type="hidden" name="category_id" value="<?= $cat['category_id']; ?>">
                                                        <button type="submit" name="delete_category" class="btn btn-xs btn-danger" <?= $cat['product_count'] > 0 ? 'disabled' : ''; ?>>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Brand Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm thương hiệu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Tên thương hiệu</label>
                    <input type="text" name="brand_name" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="add_brand" class="btn btn-primary">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Brand Modal -->
<div class="modal fade" id="editBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="brand_id" id="edit_brand_id">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa thương hiệu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Tên thương hiệu</label>
                    <input type="text" name="brand_name" id="edit_brand_name" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="edit_brand" class="btn btn-warning">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm danh mục</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Tên danh mục</label>
                    <input type="text" name="category_name" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="add_category" class="btn btn-primary">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa danh mục</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Tên danh mục</label>
                    <input type="text" name="category_name" id="edit_category_name" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="edit_category" class="btn btn-warning">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/admin_styles.php'; ?>

<script>
function editBrand(id, name) {
    document.getElementById('edit_brand_id').value = id;
    document.getElementById('edit_brand_name').value = name;
    new bootstrap.Modal(document.getElementById('editBrandModal')).show();
}

function editCategory(id, name) {
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_category_name').value = name;
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>
