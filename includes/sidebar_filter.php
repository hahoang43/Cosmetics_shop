<?php
// Lấy danh sách danh mục từ DB để hiển thị
$stmt_cat = $conn->query("SELECT * FROM Category");
$categories = $stmt_cat->fetchAll();

// Lấy category_id hiện tại từ URL để highlight
$current_cat = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$current_price = isset($_GET['price_range']) ? $_GET['price_range'] : '';
?>

<aside class="sidebar-filter">
    <div class="filter-group">
        <h4>Danh mục sản phẩm</h4>
        <ul>
            <li>
                <a href="../frontend/products.php" class="<?= $current_cat == 0 ? 'active' : '' ?>">Tất cả sản phẩm</a>
            </li>
            <?php foreach ($categories as $cat): ?>
                <li>
                    <a href="../frontend/products.php?category=<?= $cat['id'] ?>" 
                       class="<?= $current_cat == $cat['id'] ? 'active' : '' ?>">
                        <?= $cat['name'] ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="filter-group">
        <h4>Khoảng giá</h4>
        <ul>
            <li><a href="../frontend/products.php?<?= http_build_query(array_merge($_GET, ['price_range' => 'under-500'])) ?>" class="<?= $current_price == 'under-500' ? 'active' : '' ?>">Dưới 500.000đ</a></li>
            <li><a href="../frontend/products.php?<?= http_build_query(array_merge($_GET, ['price_range' => '500-1000'])) ?>" class="<?= $current_price == '500-1000' ? 'active' : '' ?>">500.000đ - 1.000.000đ</a></li>
            <li><a href="../frontend/products.php?<?= http_build_query(array_merge($_GET, ['price_range' => 'over-1000'])) ?>" class="<?= $current_price == 'over-1000' ? 'active' : '' ?>">Trên 1.000.000đ</a></li>
        </ul>
    </div>
</aside>