<?php 
require_once '../config/database.php';
require_once '../includes/admin_header.php'; 

// --- XỬ LÝ XÓA SẢN PHẨM (XÓA MỀM) ---
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    try {
        $stmt_del = $conn->prepare("UPDATE Product SET deleted = 1 WHERE id = ?");
        $stmt_del->execute([$del_id]);
        echo "<script>alert('Đã chuyển sản phẩm vào thùng rác!'); window.location.href='products.php';</script>";
        exit;
    } catch(PDOException $e) {
        echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "');</script>";
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
    <a href="product_add.php" style="background: #2ecc71; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px;">
        <i class="fa-solid fa-plus"></i> Thêm sản phẩm mới
    </a>
</div>

<div style="background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px;">
    <form action="" method="GET" style="display: flex; gap: 15px; align-items: center;">
        <div style="flex: 1;">
            <input type="text" name="search" placeholder="Nhập tên sản phẩm hoặc thương hiệu..." value="<?= htmlspecialchars($search) ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; outline: none;">
        </div>
        <div style="width: 250px;">
            <select name="category_id" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; outline: none;">
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

<div class="admin-table-container">
    <table class="admin-table">
        <thead>
            <tr>
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
                        <td><strong>#<?= $row['id'] ?></strong></td>
                        <td>
                            <img src="../assets/uploads/products/<?= htmlspecialchars($row['thumbnail']) ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px; border: 1px solid #eee;" onerror="this.src='https://via.placeholder.com/60?text=No+Image';">
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
                            <?= number_format($row['price'], 0, ',', '.') ?>đ
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

<script>
function confirmDelete(id) {
    if(confirm('Bạn có chắc chắn muốn xóa sản phẩm này? Nó sẽ không hiển thị trên website nữa.')) {
        window.location.href = 'products.php?delete_id=' + id;
    }
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>