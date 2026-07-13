<?php
require_once '../config/database.php';
$conn = getDatabase();
require_once '../includes/header.php';

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$selected_categories = [];
if (isset($_GET['categories'])) {
    if (is_array($_GET['categories'])) {
        $selected_categories = array_values(array_filter(array_map('intval', $_GET['categories'])));
    } elseif ((int)$_GET['categories'] > 0) {
        $selected_categories = [(int)$_GET['categories']];
    }
}
$min_price = isset($_GET['min_price']) ? max(0, (int)$_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? min(100000000, (int)$_GET['max_price']) : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$search_results = [];

if ($keyword !== '') {
    $whereClause = " WHERE deleted = 0 AND (title LIKE ? OR brand LIKE ?)";
    $params = ["%" . $keyword . "%", "%" . $keyword . "%"];

    if (!empty($selected_categories)) {
        $inPlace = implode(',', array_fill(0, count($selected_categories), '?'));
        $whereClause .= " AND category_id IN ($inPlace)";
        foreach ($selected_categories as $cid) {
            $params[] = (int)$cid;
        }
    }

    if ($min_price > 0) {
        $whereClause .= " AND price >= ?";
        $params[] = $min_price;
    }

    if ($max_price > 0) {
        $whereClause .= " AND price <= ?";
        $params[] = $max_price;
    }

    $sql = "SELECT * FROM Product" . $whereClause;
    switch ($sort) {
        case 'price_asc': $sql .= " ORDER BY price ASC"; break;
        case 'price_desc': $sql .= " ORDER BY price DESC"; break;
        default: $sql .= " ORDER BY id DESC"; break;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $search_results = $stmt->fetchAll();
}
?>

<main class="container shop-container" style="margin-top: 40px; margin-bottom: 60px; min-height: 50vh;">
    <div class="shop-content">
        <div class="shop-header" style="margin-bottom: 24px;">
            <h2 class="category-title" style="font-family: 'Playfair Display', serif; margin: 0;">
                Kết quả tìm kiếm cho: <span style="color: #D4A373;">"<?= htmlspecialchars($keyword) ?>"</span>
            </h2>
            <form action="" method="GET">
                <input type="hidden" name="keyword" value="<?= htmlspecialchars($keyword) ?>">
                <?php if (!empty($selected_categories)): ?>
                    <?php foreach ($selected_categories as $selectedCategory): ?>
                        <input type="hidden" name="categories[]" value="<?= (int)$selectedCategory ?>">
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if ($min_price > 0): ?><input type="hidden" name="min_price" value="<?= $min_price ?>"><?php endif; ?>
                <?php if ($max_price > 0): ?><input type="hidden" name="max_price" value="<?= $max_price ?>"><?php endif; ?>
                <select name="sort" onchange="this.form.submit()" class="shop-sort">
                    <option value="latest" <?= $sort == 'latest' ? 'selected' : '' ?>>Mới nhất</option>
                    <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Giá: Thấp đến Cao</option>
                    <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Giá: Cao đến Thấp</option>
                </select>
            </form>
        </div>

        <?php if (count($search_results) > 0): ?>
            <p style="margin-bottom: 20px; color: #666;">Tìm thấy <strong><?= count($search_results) ?></strong> sản phẩm phù hợp.</p>
            
            <div class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;">
                <?php foreach ($search_results as $item): ?>
                    <div class="product-card" style="border: 1px solid #eee; border-radius: 8px; overflow: hidden; transition: 0.3s;">
                        <a href="product_detail.php?id=<?= $item['id'] ?>" class="product-link" style="text-decoration: none; color: inherit;">
                            <div class="product-img" style="position: relative;">
                                <?php if ($item['old_price'] > $item['price']): ?>
                                    <div class="product-badge sale-badge" style="position: absolute; top: 10px; left: 10px; background: #e67e22; color: white; padding: 4px 8px; border-radius: 999px; font-size: 12px; font-weight: 700;">Sale</div>
                                <?php endif; ?>
                                <img src="<?= htmlspecialchars(imageSrc($item['thumbnail'] ?? '', 'products')) ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy" style="width: 100%; aspect-ratio: 1/1; object-fit: cover;">
                            </div>
                            <div style="padding: 15px;">
                                <h3 style="font-size: 15px; margin-bottom: 10px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 42px;"><?= htmlspecialchars($item['title']) ?></h3>
                                <div class="price" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <?php if ($item['old_price'] > $item['price']): ?>
                                        <span class="old-price" style="color: #999; text-decoration: line-through; font-size: 13px;"><?= number_format($item['old_price'], 0, ',', '.') ?>đ</span>
                                    <?php endif; ?>
                                    <span class="current-price" style="color: #D4A373; font-weight: bold; font-size: 16px;"><?= number_format($item['price'], 0, ',', '.') ?>đ</span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 50px 0;">
                <i class="fa-solid fa-box-open" style="font-size: 50px; color: #ddd; margin-bottom: 15px;"></i>
                <h3 style="color: #777;">Rất tiếc, không tìm thấy sản phẩm nào!</h3>
                <p style="color: #999;">Vui lòng thử lại với từ khóa khác (Ví dụ: Son, Serum, MAC...)</p>
                <a href="index.php" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: #2c3e50; color: white; text-decoration: none; border-radius: 5px;">Quay lại Trang chủ</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once '../includes/sidebar_filter.php'; ?>

<?php require_once '../includes/footer.php'; ?>
