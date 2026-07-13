<?php
/** @var PDO|null $conn */
require_once '../config/database.php';
$conn = $conn ?? null;
require_once '../includes/admin_header.php';

function ensureProductDiscountPercentColumn(PDO $conn): void {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Product' AND COLUMN_NAME = 'discount_percent'");
    $stmt->execute();
    if ((int)$stmt->fetchColumn() === 0) {
        $conn->exec("ALTER TABLE Product ADD COLUMN discount_percent INT NOT NULL DEFAULT 0 AFTER old_price");
    }
}

ensureProductDiscountPercentColumn($conn);

// --- XỬ LÝ XÓA SẢN PHẨM (XÓA MỀM) ---
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    try {
        $stmt_del = $conn->prepare("UPDATE Product SET deleted = 1 WHERE id = ?");
        $stmt_del->execute([$del_id]);
        echo "<script>Swal.fire({ icon: 'success', title: 'Thành công', text: 'Đã chuyển sản phẩm vào thùng rác!' }).then(function() { window.location.href='products.php'; });</script>";
        exit;
    } catch(PDOException $e) {
        echo "<script>Swal.fire({ icon: 'error', title: 'Lỗi', text: '" . addslashes($e->getMessage()) . "' });</script>";
    }
}

// --- XỬ LÝ XÓA HÀNG LOẠT (XÓA MỀM) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete'])) {
    $selectedIds = $_POST['selected_ids'] ?? [];
    $selectedIds = array_values(array_filter(array_map('intval', (array)$selectedIds)));

    if (!empty($selectedIds)) {
        try {
            $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
            $stmt_del = $conn->prepare("UPDATE Product SET deleted = 1 WHERE id IN ($placeholders)");
            $stmt_del->execute($selectedIds);
            echo "<script>Swal.fire({ icon: 'success', title: 'Thành công', text: 'Đã chuyển " . count($selectedIds) . " sản phẩm vào thùng rác!' }).then(function() { window.location.href='products.php'; });</script>";
            exit;
        } catch(PDOException $e) {
            echo "<script>Swal.fire({ icon: 'error', title: 'Lỗi', text: '" . addslashes($e->getMessage()) . "' });</script>";
        }
    } else {
        echo "<script>Swal.fire({ icon: 'warning', title: 'Thiếu dữ liệu', text: 'Vui lòng chọn ít nhất một sản phẩm để xóa.' });</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_promote'])) {
    $selectedIds = $_POST['selected_ids'] ?? [];
    $selectedIds = array_values(array_filter(array_map('intval', (array)$selectedIds)));
    $discountPercent = isset($_POST['discount_percent']) ? max(0, min(100, (int)$_POST['discount_percent'])) : 0;

    if (empty($selectedIds)) {
        echo "<script>Swal.fire({ icon: 'warning', title: 'Thiếu dữ liệu', text: 'Vui lòng chọn ít nhất một sản phẩm để áp dụng khuyến mãi.' });</script>";
    } elseif ($discountPercent <= 0) {
        echo "<script>Swal.fire({ icon: 'warning', title: 'Thiếu dữ liệu', text: 'Vui lòng nhập % khuyến mãi lớn hơn 0.' });</script>";
    } else {
        try {
            $stmtProduct = $conn->prepare("SELECT id, price, old_price FROM Product WHERE id = ? AND deleted = 0");
            $stmtVariants = $conn->prepare("SELECT id, price, old_price FROM Product_Variant WHERE product_id = ?");
            $stmtUpdateProduct = $conn->prepare("UPDATE Product SET price = ?, old_price = ?, discount_percent = ? WHERE id = ?");
            $stmtUpdateVariant = $conn->prepare("UPDATE Product_Variant SET price = ?, old_price = ? WHERE id = ?");

            foreach ($selectedIds as $productId) {
                $stmtProduct->execute([$productId]);
                $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);
                if (!$product) {
                    continue;
                }

                $basePrice = ((float)$product['old_price'] > (float)$product['price'] && (float)$product['old_price'] > 0)
                    ? (float)$product['old_price']
                    : (float)$product['price'];
                $salePrice = (float) round($basePrice * (100 - $discountPercent) / 100);
                $stmtUpdateProduct->execute([$salePrice, $basePrice, $discountPercent, $productId]);

                $stmtVariants->execute([$productId]);
                while ($variant = $stmtVariants->fetch(PDO::FETCH_ASSOC)) {
                    $baseVariantPrice = ((float)$variant['old_price'] > (float)$variant['price'] && (float)$variant['old_price'] > 0)
                        ? (float)$variant['old_price']
                        : (float)$variant['price'];
                    $saleVariantPrice = (float) round($baseVariantPrice * (100 - $discountPercent) / 100);
                    $stmtUpdateVariant->execute([$saleVariantPrice, $baseVariantPrice, $variant['id']]);
                }
            }

            echo "<script>Swal.fire({ icon: 'success', title: 'Thành công', text: 'Đã áp dụng khuyến mãi {$discountPercent}% cho " . count($selectedIds) . " sản phẩm đã chọn.' }).then(function() { window.location.href='products.php'; });</script>";
            exit;
        } catch(PDOException $e) {
            echo "<script>Swal.fire({ icon: 'error', title: 'Lỗi', text: '" . addslashes($e->getMessage()) . "' });</script>";
        }
    }
}

// --- LẤY DANH SÁCH DANH MỤC CHO DROPDOWN LỌC ---
$stmt_cat = $conn->query("SELECT * FROM Category");
$categories = $stmt_cat->fetchAll();

// --- XỬ LÝ ĐIỀU KIỆN TÌM KIẾM & LỌC ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$whereClause = "WHERE p.deleted = 0";
$params = [];

if ($search !== '') {
    $whereClause .= " AND (p.title LIKE ? OR p.brand LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_id > 0) {
    $whereClause .= " AND p.category_id = ?";
    $params[] = $category_id;
}

// LẤY DANH SÁCH SẢN PHẨM DỰA TRÊN ĐIỀU KIỆN LỌC
$sql = "
    SELECT p.*, c.name as category_name, COALESCE(SUM(pv.quantity), 0) as total_stock
    FROM Product p
    LEFT JOIN Category c ON p.category_id = c.id
    LEFT JOIN Product_Variant pv ON p.id = pv.product_id
    $whereClause
    GROUP BY p.id
    ORDER BY p.id DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 class="page-title" style="margin: 0; border: none;">Quản lý Sản phẩm</h1>
    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
        <a href="#promotion-panel" style="background: #f39c12; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px;">
            <i class="fa-solid fa-tag"></i> Khuyến mãi
        </a>
        <a href="product_add.php" style="background: #2ecc71; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px;">
            <i class="fa-solid fa-plus"></i> Thêm sản phẩm mới
        </a>
    </div>
</div>

<div style="background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px;">
    <form action="" method="GET" style="display: flex; gap: 15px; align-items: center;">
        <div style="flex: 1;">
            <label for="admin-product-search" style="display: block; font-size: 12px; font-weight: bold; color: #666; margin-bottom: 6px;">Tìm kiếm</label>
            <input type="text" id="admin-product-search" name="search" placeholder="Nhập tên sản phẩm hoặc thương hiệu..." value="<?= htmlspecialchars($search) ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; outline: none;">
        </div>
        <div style="width: 250px;">
            <label for="admin-category-filter" style="display: block; font-size: 12px; font-weight: bold; color: #666; margin-bottom: 6px;">Danh mục</label>
            <select id="admin-category-filter" name="category_id" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; outline: none;">
                <option value="0">-- Tất cả danh mục --</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" style="background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer;">
                <i class="fa-solid fa-filter"></i> Lọc
            </button>
            <?php if($search !== '' || $category_id > 0): ?>
                <a href="products.php" style="display: inline-block; background: #95a5a6; color: white; padding: 10px 15px; border-radius: 4px; text-decoration: none; font-weight: bold; margin-left: 5px;">
                    Hủy lọc
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<form method="POST" id="bulk-promote-form" style="margin-bottom: 20px;">
    <div id="promotion-panel" style="background: #fffaf2; border: 1px solid #f1d0a2; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.04);">
        <div style="display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div>
                <div style="font-weight: bold; margin-bottom: 6px; color: #8a5a24;">Áp dụng khuyến mãi cho các sản phẩm đã chọn</div>
                <div style="color: #8f6a42; font-size: 13px;">Chọn sản phẩm trong bảng bên dưới, nhập % khuyến mãi rồi bấm áp dụng.</div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <label for="bulk-discount-percent" style="font-size: 12px; font-weight: bold; color: #8a5a24;">% khuyến mãi</label>
                <input type="number" id="bulk-discount-percent" name="discount_percent" min="1" max="100" placeholder="% KM" style="width: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; outline: none;">
                <button type="submit" name="bulk_promote" value="1" style="background: #f39c12; color: white; border: none; padding: 10px 16px; border-radius: 5px; font-weight: bold; cursor: pointer;">
                    <i class="fa-solid fa-tag"></i> Áp dụng khuyến mãi
                </button>
            </div>
        </div>
    </div>
    <div id="bulk-promote-selected-inputs"></div>
</form>

<form method="POST" id="bulk-delete-form">
<div class="admin-table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width: 50px; text-align: center;">
                    <input type="checkbox" id="select-all-products" style="width: 16px; height: 16px; cursor: pointer;">
                    <label for="select-all-products" style="display: inline-block; margin-left: 6px; font-size: 12px; color: #555;">Tất cả</label>
                </th>
                <th>ID</th>
                <th>Hình ảnh</th>
                <th>Tên sản phẩm</th>
                <th>Danh mục</th>
                <th>Giá từ</th>
                <th style="text-align: center;">Kho (Tổng)</th>
                <th style="text-align: center;">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($products) > 0): ?>
                <?php foreach($products as $row): ?>
                    <tr>
                        <td style="text-align: center; vertical-align: middle;">
                            <input type="checkbox" id="product-select-<?= $row['id'] ?>" name="selected_ids[]" value="<?= $row['id'] ?>" class="product-checkbox" style="width: 16px; height: 16px; cursor: pointer;">
                            <label for="product-select-<?= $row['id'] ?>" style="position: absolute; left: -9999px;">Chọn sản phẩm <?= htmlspecialchars($row['title']) ?></label>
                        </td>
                        <td><strong>#<?= $row['id'] ?></strong></td>
                        <td>
                            <img src="<?= htmlspecialchars(imageSrc($row['thumbnail'] ?? '', 'products')) ?>" alt="<?= htmlspecialchars($row['title']) ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px; border: 1px solid #eee;">
                        </td>
                        <td style="max-width: 250px; line-height: 1.4;">
                            <strong><?= htmlspecialchars($row['title']) ?></strong><br>
                            <span style="color: #999; font-size: 12px;">Thương hiệu: <?= htmlspecialchars($row['brand']) ?></span>
                        </td>
                        <td>
                            <span style="background: #f0f0f0; padding: 5px 10px; border-radius: 4px; font-size: 13px;">
                                <?= htmlspecialchars($row['category_name']) ?>
                            </span>
                        </td>
                        <td style="color: #D4A373; font-weight: bold;">
                            <?php if ((float)$row['old_price'] > (float)$row['price']): ?>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 700; color: #fff; background: #e67e22; padding: 3px 8px; border-radius: 999px; width: fit-content;">
                                        <i class="fa-solid fa-tag"></i> Sale <?= (int)round((1 - ((float)$row['price'] / (float)$row['old_price'])) * 100) ?>%
                                    </span>
                                    <span style="text-decoration: line-through; color: #888; font-size: 12px; font-weight: 500;"> <?= number_format($row['old_price'], 0, ',', '.') ?>đ</span>
                                    <span><?= number_format($row['price'], 0, ',', '.') ?>đ</span>
                                </div>
                            <?php else: ?>
                                <?= number_format($row['price'], 0, ',', '.') ?>đ
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <span style="background: <?= $row['total_stock'] > 0 ? '#e8f8f5' : '#fdedec' ?>; color: <?= $row['total_stock'] > 0 ? '#27ae60' : '#e74c3c' ?>; padding: 4px 8px; border-radius: 4px; font-weight: bold;">
                                <?= $row['total_stock'] ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; justify-content: center; gap: 10px;">
                                <a href="product_edit.php?id=<?= $row['id'] ?>" class="btn-action btn-update" title="Sửa sản phẩm & phân loại">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <button type="button" class="btn-action" style="background: #e74c3c; color: white;" title="Xóa sản phẩm" onclick="confirmDelete(<?= $row['id'] ?>)">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #777;">
                        <i class="fa-solid fa-magnifying-glass" style="font-size: 30px; margin-bottom: 10px; color: #ccc;"></i><br>
                        Không tìm thấy sản phẩm nào phù hợp với điều kiện lọc.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 16px; flex-wrap: wrap;">
    <div style="color: #666; font-size: 14px;">
        <strong id="selected-count">0</strong> sản phẩm đã chọn
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <button type="submit" name="bulk_delete" value="1" id="bulk-delete-btn" style="background: #e74c3c; color: white; border: none; padding: 10px 16px; border-radius: 5px; font-weight: bold; cursor: pointer;" disabled>
            <i class="fa-solid fa-trash-can"></i> Xóa đã chọn
        </button>
    </div>
</div>

</form>

<script>
function confirmDelete(id) {
    confirmAdminAction('Bạn có chắc chắn muốn xóa sản phẩm này? Nó sẽ không hiển thị trên website nữa.', function() {
        window.location.href = 'products.php?delete_id=' + id;
    });
}

(function() {
    const selectAll = document.getElementById('select-all-products');
    const checkboxes = Array.from(document.querySelectorAll('.product-checkbox'));
    const selectedCount = document.getElementById('selected-count');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const bulkDeleteForm = document.getElementById('bulk-delete-form');
    const bulkPromoteForm = document.getElementById('bulk-promote-form');
    const bulkPromoteInputs = document.getElementById('bulk-promote-selected-inputs');
    const bulkDiscountPercent = document.getElementById('bulk-discount-percent');

    function updateSelectionState() {
        const checkedCount = checkboxes.filter(cb => cb.checked).length;
        selectedCount.textContent = checkedCount;
        bulkDeleteBtn.disabled = checkedCount === 0;
        selectAll.checked = checkedCount > 0 && checkedCount === checkboxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
    }

    selectAll?.addEventListener('change', function() {
        checkboxes.forEach(cb => {
            cb.checked = selectAll.checked;
        });
        updateSelectionState();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectionState);
    });

    bulkDeleteForm?.addEventListener('submit', function(e) {
        const checkedCount = checkboxes.filter(cb => cb.checked).length;
        if (checkedCount === 0) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Thiếu dữ liệu', text: 'Vui lòng chọn ít nhất một sản phẩm để xóa.' });
            return;
        }

        e.preventDefault();
        confirmAdminAction('Bạn có chắc chắn muốn xóa ' + checkedCount + ' sản phẩm đã chọn?', function() {
            bulkDeleteForm.submit();
        });
    });

    bulkPromoteForm?.addEventListener('submit', function(e) {
        const checkedIds = checkboxes.filter(cb => cb.checked).map(cb => cb.value);
        const discountPercent = parseInt(bulkDiscountPercent?.value || '0', 10);

        if (checkedIds.length === 0) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Thiếu dữ liệu', text: 'Vui lòng chọn ít nhất một sản phẩm để áp dụng khuyến mãi.' });
            return;
        }

        if (!discountPercent || discountPercent <= 0) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Thiếu dữ liệu', text: 'Vui lòng nhập % khuyến mãi lớn hơn 0.' });
            return;
        }

        e.preventDefault();
        bulkPromoteInputs.innerHTML = '';
        
        // Thêm các ID sản phẩm được chọn
        checkedIds.forEach(function(id) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'selected_ids[]';
            hidden.value = id;
            bulkPromoteInputs.appendChild(hidden);
        });

        // Thêm % giảm giá[cite: 1]
        const discountHidden = document.createElement('input');
        discountHidden.type = 'hidden';
        discountHidden.name = 'discount_percent';
        discountHidden.value = String(discountPercent);
        bulkPromoteInputs.appendChild(discountHidden);

        // THÊM ĐOẠN MÃ NÀY: Bổ sung input để PHP nhận diện được hành động bulk_promote
        const actionHidden = document.createElement('input');
        actionHidden.type = 'hidden';
        actionHidden.name = 'bulk_promote';
        actionHidden.value = '1';
        bulkPromoteInputs.appendChild(actionHidden);

        // Gửi form[cite: 1]
        bulkPromoteForm.submit();
    });

    updateSelectionState();
})();
</script>

<?php require_once '../includes/admin_footer.php'; ?>
