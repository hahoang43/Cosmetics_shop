<?php 
require_once '../config/database.php'; 
require_once '../includes/header.php'; 

// --- 1. CẤU HÌNH PHÂN TRANG ---
$limit = 8; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- 2. KHỞI TẠO BIẾN ĐIỀU KIỆN ---
$whereClause = " WHERE deleted = 0";
$params = [];

// --- 3. LẤY THÔNG SỐ LỌC & TÌM KIẾM ---
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$price_range = isset($_GET['price_range']) ? $_GET['price_range'] : '';

// Lọc theo Danh mục
if ($category_id > 0) {
    $whereClause .= " AND category_id = ?";
    $params[] = $category_id;
}

// Lọc theo Tìm kiếm
if (!empty($search)) {
    $whereClause .= " AND title LIKE ?";
    $params[] = "%$search%";
}

// Lọc theo Khoảng giá
if ($price_range == 'under-500') {
    $whereClause .= " AND price < 500000";
} elseif ($price_range == '500-1000') {
    $whereClause .= " AND price BETWEEN 500000 AND 1000000";
} elseif ($price_range == 'over-1000') {
    $whereClause .= " AND price > 1000000";
}

// --- 4. TÍNH TỔNG SỐ TRANG ---
$countSql = "SELECT COUNT(*) FROM Product" . $whereClause;
$stmtCount = $conn->prepare($countSql);
$stmtCount->execute($params);
$totalRows = $stmtCount->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// --- 5. LẤY DỮ LIỆU SẢN PHẨM ---
$sql = "SELECT * FROM Product" . $whereClause;

// Thêm sắp xếp
switch ($sort) {
    case 'price_asc': $sql .= " ORDER BY price ASC"; break;
    case 'price_desc': $sql .= " ORDER BY price DESC"; break;
    default: $sql .= " ORDER BY created_at DESC"; break;
}

$sql .= " LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="container shop-container" style="display: flex; gap: 30px; margin-top: 30px;">
    <?php require_once '../includes/sidebar_filter.php'; ?>

    <section class="shop-content" style="flex: 1;">
        <div class="shop-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 class="category-title" style="margin: 0; font-family: 'Playfair Display', serif;">Tất cả sản phẩm</h2>
            
            <form action="" method="GET">
                <?php if($category_id > 0) echo '<input type="hidden" name="category" value="'.$category_id.'">'; ?>
                <?php if(!empty($search)) echo '<input type="hidden" name="search" value="'.htmlspecialchars($search).'">'; ?>
                <?php if(!empty($price_range)) echo '<input type="hidden" name="price_range" value="'.htmlspecialchars($price_range).'">'; ?>
                
                <select name="sort" onchange="this.form.submit()" style="padding: 8px 15px; border-radius: 5px; border: 1px solid #ddd; outline: none;">
                    <option value="latest" <?= $sort == 'latest' ? 'selected' : '' ?>>Mới nhất</option>
                    <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Giá: Thấp đến Cao</option>
                    <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Giá: Cao đến Thấp</option>
                </select>
            </form>
        </div>

        <div class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $item): ?>
                    <div class="product-card" style="border: 1px solid #eee; padding: 15px; border-radius: 8px; background: #fff; display: flex; flex-direction: column; justify-content: space-between; transition: 0.3s; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
                        <a href="product_detail.php?id=<?= $item['id'] ?>" style="text-decoration: none; color: inherit; display: block; flex: 1;">
                            <div class="product-img" style="text-align: center; margin-bottom: 10px;">
                                <img src="../assets/uploads/products/<?= htmlspecialchars($item['thumbnail']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" style="width: 100%; border-radius: 8px;">
                            </div>
                            <h3 style="font-size: 15px; margin: 10px 0; height: 40px; overflow: hidden; line-height: 1.4;"><?= htmlspecialchars($item['title']) ?></h3>
                            <div class="price" style="margin-bottom: 15px;">
                                <span style="font-size: 13px; color: #999;">Giá từ:</span>
                                <span class="current-price" style="color: #D4A373; font-weight: bold; font-size: 16px; margin-left: 5px;">
                                    <?= number_format($item['price'], 0, ',', '.') ?>đ
                                </span>
                            </div>
                        </a>
                        
                        <a href="product_detail.php?id=<?= $item['id'] ?>" class="btn-add-cart-grid" style="display: block; text-align: center; background: #333; color: #fff; padding: 10px; border-radius: 5px; text-decoration: none; font-weight: 500; transition: 0.3s;" onmouseover="this.style.background='#D4A373'" onmouseout="this.style.background='#333'">
                            <i class="fa-solid fa-list"></i> Chọn phân loại
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px 0; color: #777;">
                    <i class="fa-solid fa-box-open" style="font-size: 40px; color: #ddd; margin-bottom: 15px;"></i>
                    <p>Không có sản phẩm nào phù hợp với bộ lọc hiện tại.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 50px; margin-bottom: 30px;">
            <?php 
                function getPageUrl($p) {
                    $params = $_GET;
                    $params['page'] = $p;
                    return "?" . http_build_query($params);
                }
            ?>
            <?php if ($page > 1): ?>
                <a href="<?= getPageUrl($page - 1) ?>" class="page-node" style="padding: 8px 15px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 4px;">&laquo; Trước</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?= getPageUrl($i) ?>" class="page-node <?= $i == $page ? 'active' : '' ?>" style="padding: 8px 15px; border: 1px solid <?= $i == $page ? '#D4A373' : '#ddd' ?>; text-decoration: none; color: <?= $i == $page ? '#fff' : '#333' ?>; background: <?= $i == $page ? '#D4A373' : 'transparent' ?>; border-radius: 4px; font-weight: bold;">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= getPageUrl($page + 1) ?>" class="page-node" style="padding: 8px 15px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 4px;">Sau &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>