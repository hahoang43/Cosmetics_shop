<?php
require_once '../config/database.php';
$conn = getDatabase();
require_once '../includes/header.php';

// --- 1. CẤU HÌNH PHÂN TRANG ---
$limit = 27;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $limit;

// --- 2. KHỞI TẠO BIẾN ĐIỀU KIỆN ---
$whereClause = " WHERE deleted = 0";
$params = [];

// --- 3. LẤY THÔNG SỐ LỌC & TÌM KIẾM ---
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$selected_categories = [];
if (isset($_GET['categories'])) {
    if (is_array($_GET['categories'])) {
        $selected_categories = array_values(array_filter(array_map('intval', $_GET['categories'])));
    } elseif ((int)$_GET['categories'] > 0) {
        $selected_categories = [(int)$_GET['categories']];
    }
}
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$price_range = isset($_GET['price_range']) ? $_GET['price_range'] : '';
$min_price = isset($_GET['min_price']) ? max(0, (int)$_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? min(100000000, (int)$_GET['max_price']) : 0;

$selectedCategoryTitle = 'Tất cả sản phẩm';
if ($category_id > 0) {
    $stmtSelectedCat = $conn->prepare('SELECT name FROM Category WHERE id = ? LIMIT 1');
    $stmtSelectedCat->execute([$category_id]);
    $selectedCategoryName = $stmtSelectedCat->fetchColumn();
    if ($selectedCategoryName) {
        $selectedCategoryTitle = $selectedCategoryName;
    }
}

// Lọc theo Danh mục (checkbox đa chọn)
if (!empty($selected_categories)) {
    $inPlace = implode(',', array_fill(0, count($selected_categories), '?'));
    $whereClause .= " AND category_id IN ($inPlace)";
    foreach ($selected_categories as $cid) {
        $params[] = (int)$cid;
    }
} elseif ($category_id > 0) {
    // Map logical category ids to groups by name keywords.
    // 1 => chăm sóc da (skin care / bodycare), 2 => trang điểm (makeup)
    $categoryGroups = [
        1 => ['chăm sóc da', 'skin care', 'skincare', 'bodycare'],
        2 => ['trang điểm', 'makeup'],
    ];

    if (isset($categoryGroups[$category_id])) {
        $patterns = $categoryGroups[$category_id];
        $likeClauses = [];
        $likeParams = [];
        foreach ($patterns as $pat) {
            $likeClauses[] = 'LOWER(name) LIKE ?';
            $likeParams[] = '%' . mb_strtolower($pat, 'UTF-8') . '%';
        }
        $stmtCat = $conn->prepare('SELECT id FROM Category WHERE ' . implode(' OR ', $likeClauses));
        $stmtCat->execute($likeParams);
        $catIds = $stmtCat->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($catIds)) {
            $inPlace = implode(',', array_fill(0, count($catIds), '?'));
            $whereClause .= " AND category_id IN ($inPlace)";
            foreach ($catIds as $cid) {
                $params[] = (int)$cid;
            }
        } else {
            // Fallback to exact id if no matching names found
            $whereClause .= " AND category_id = ?";
            $params[] = $category_id;
        }
    } else {
        $whereClause .= " AND category_id = ?";
        $params[] = $category_id;
    }
}

// Lọc theo Tìm kiếm
if (!empty($search)) {
    $whereClause .= " AND title LIKE ?";
    $params[] = "%$search%";
}

// Lọc theo Khoảng giá preset hoặc nhập tay
if ($price_range == 'under-300') {
    $whereClause .= " AND price < 300000";
} elseif ($price_range == '300-700') {
    $whereClause .= " AND price BETWEEN 300000 AND 700000";
} elseif ($price_range == '700-1500') {
    $whereClause .= " AND price BETWEEN 700000 AND 1500000";
} elseif ($price_range == 'over-1500') {
    $whereClause .= " AND price > 1500000";
}

if ($min_price > 0) {
    $whereClause .= " AND price >= ?";
    $params[] = $min_price;
}

if ($max_price > 0) {
    $whereClause .= " AND price <= ?";
    $params[] = $max_price;
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

<div class="container shop-container">
    <section class="shop-content">
        <div class="shop-header">
            <h2 class="category-title"><?= htmlspecialchars($selectedCategoryTitle) ?></h2>
            
            <form action="" method="GET">
                <?php if ($category_id > 0) { echo '<input type="hidden" name="category" value="'.$category_id.'">'; } ?>
                <?php if (!empty($selected_categories)): ?>
                    <?php foreach ($selected_categories as $selectedCategory): ?>
                        <input type="hidden" name="categories[]" value="<?= (int)$selectedCategory ?>">
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($search)) { echo '<input type="hidden" name="search" value="'.htmlspecialchars($search).'">'; } ?>
                <?php if (!empty($price_range)) { echo '<input type="hidden" name="price_range" value="'.htmlspecialchars($price_range).'">'; } ?>
                <?php if ($min_price > 0) { echo '<input type="hidden" name="min_price" value="'.$min_price.'">'; } ?>
                <?php if ($max_price > 0) { echo '<input type="hidden" name="max_price" value="'.$max_price.'">'; } ?>
                
                <label for="shop-sort" class="sr-only" style="position: absolute; left: -9999px;">Sắp xếp sản phẩm</label>
                <select id="shop-sort" name="sort" onchange="this.form.submit()" class="shop-sort">
                    <option value="latest" <?= $sort == 'latest' ? 'selected' : '' ?>>Mới nhất</option>
                    <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Giá: Thấp đến Cao</option>
                    <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Giá: Cao đến Thấp</option>
                </select>
            </form>
        </div>

        <div class="product-grid">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $item): ?>
                    <div class="product-card">
                        <?php if ($item['old_price'] > $item['price']): ?>
                            <div class="product-badge sale-badge">Sale</div>
                        <?php endif; ?>
                        <a href="product_detail.php?id=<?= $item['id'] ?>" class="product-card-link">
                            <div class="product-img">
                                <img src="<?= htmlspecialchars(imageSrc($item['thumbnail'] ?? '', 'products')) ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
                            </div>
                            <h3 class="product-title"><?= htmlspecialchars($item['title']) ?></h3>
                            <div class="price">
                                <span class="price-label">Giá từ:</span>
                                <?php if ($item['old_price'] > $item['price']): ?>
                                    <span class="old-price"><?= number_format($item['old_price'], 0, ',', '.') ?>đ</span>
                                <?php endif; ?>
                                <span class="current-price">
                                    <?= number_format($item['price'], 0, ',', '.') ?>đ
                                </span>
                            </div>
                        </a>
                        
                        <a href="product_detail.php?id=<?= $item['id'] ?>" class="btn-add-cart-grid">
                            <i class="fa-solid fa-list"></i> Mua ngay
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-products">
                    <i class="fa-solid fa-box-open"></i>
                    <p>Không có sản phẩm nào phù hợp với bộ lọc hiện tại.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
                function getPageUrl($p) {
                    $params = $_GET;
                    $params['page'] = $p;
                    return "?" . http_build_query($params);
                }
            ?>
            <?php if ($page > 1): ?>
                <a href="<?= getPageUrl($page - 1) ?>" class="page-node">&laquo; Trang trước</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?= getPageUrl($i) ?>" class="page-node <?= $i == $page ? 'active' : '' ?>" aria-label="Trang <?= $i ?>">
                    <span><?= $i ?></span>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= getPageUrl($page + 1) ?>" class="page-node">Trang sau &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once '../includes/sidebar_filter.php'; ?>

<?php require_once '../includes/footer.php'; ?>
