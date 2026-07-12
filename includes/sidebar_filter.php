<?php
// Lấy danh sách danh mục từ DB để hiển thị
$stmt_cat = $conn->query("SELECT * FROM Category");
$categories = $stmt_cat->fetchAll();

// Lấy category_id hiện tại từ URL để highlight
$current_cat = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$current_price = isset($_GET['price_range']) ? $_GET['price_range'] : '';
$current_min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$current_max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 0;

// Define group keywords to highlight matching categories when grouped category selected
$groupKeywords = [
    1 => ['chăm sóc da', 'skin care', 'skincare', 'bodycare'],
    2 => ['trang điểm', 'makeup'],
];
$current_group = null;
if (in_array($current_cat, [1,2])) {
    $current_group = $current_cat;
}
?>

<button type="button" class="sidebar-filter-toggle" id="sidebar-filter-toggle" aria-controls="sidebar-filter-panel" aria-expanded="false" title="Mở bộ lọc" aria-label="Mở bộ lọc">
    <i class="fa-solid fa-filter"></i>
    Bộ lọc
</button>

<div class="sidebar-filter-backdrop" id="sidebar-filter-backdrop" aria-hidden="true"></div>

<aside class="sidebar-filter" id="sidebar-filter-panel" aria-hidden="true">
    <div class="filter-group">
        <h4>Danh mục sản phẩm</h4>
        <ul>
            <li>
                <a href="/Cosmetics_shop/frontend/products.php" class="<?= $current_cat == 0 ? 'active' : '' ?>">Tất cả sản phẩm</a>
            </li>
            <?php foreach ($categories as $cat): ?>
                <li>
                          <?php
                              $isActive = $current_cat == $cat['id'];
                              if ($current_group !== null && !$isActive) {
                                  $keywords = $groupKeywords[$current_group] ?? [];
                                  $lname = mb_strtolower($cat['name'], 'UTF-8');
                                  foreach ($keywords as $kw) {
                                      if (mb_stripos($lname, $kw) !== false) {
                                          $isActive = true;
                                          break;
                                      }
                                  }
                              }
                          ?>
                                                    <a href="/Cosmetics_shop/frontend/products.php?category=<?= $cat['id'] ?>" class="<?= $isActive ? 'active' : '' ?>" aria-label="<?= htmlspecialchars($cat['name']) ?>">
                                                        <span><?= htmlspecialchars($cat['name']) ?></span>
                        </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="filter-group">
        <h4>Khoảng giá</h4>
        <ul>
            <li><a href="/Cosmetics_shop/frontend/products.php?<?= http_build_query(array_merge($_GET, ['price_range' => 'under-300', 'min_price' => null, 'max_price' => null])) ?>" class="<?= $current_price == 'under-300' ? 'active' : '' ?>">Dưới 300.000đ</a></li>
            <li><a href="/Cosmetics_shop/frontend/products.php?<?= http_build_query(array_merge($_GET, ['price_range' => '300-700', 'min_price' => null, 'max_price' => null])) ?>" class="<?= $current_price == '300-700' ? 'active' : '' ?>">300.000đ - 700.000đ</a></li>
            <li><a href="/Cosmetics_shop/frontend/products.php?<?= http_build_query(array_merge($_GET, ['price_range' => '700-1500', 'min_price' => null, 'max_price' => null])) ?>" class="<?= $current_price == '700-1500' ? 'active' : '' ?>">700.000đ - 1.500.000đ</a></li>
            <li><a href="/Cosmetics_shop/frontend/products.php?<?= http_build_query(array_merge($_GET, ['price_range' => 'over-1500', 'min_price' => null, 'max_price' => null])) ?>" class="<?= $current_price == 'over-1500' ? 'active' : '' ?>">Trên 1.500.000đ</a></li>
        </ul>

        <form class="price-custom-form" action="/Cosmetics_shop/frontend/products.php" method="GET">
            <?php if ($current_cat > 0): ?>
                <input type="hidden" name="category" value="<?= $current_cat ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['search'])): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['sort'])): ?>
                <input type="hidden" name="sort" value="<?= htmlspecialchars($_GET['sort']) ?>">
            <?php endif; ?>

            <div class="price-custom-title">Tự chọn khoảng giá</div>
            <div class="price-custom-row">
                <input type="number" name="min_price" min="0" step="1000" placeholder="Từ" value="<?= $current_min_price > 0 ? $current_min_price : '' ?>">
                <input type="number" name="max_price" min="0" step="1000" placeholder="Đến" value="<?= $current_max_price > 0 ? $current_max_price : '' ?>">
            </div>
            <button type="submit" class="price-custom-submit">Áp dụng</button>
        </form>
    </div>
</aside>

<script>
(function () {
    const toggle = document.getElementById('sidebar-filter-toggle');
    const panel = document.getElementById('sidebar-filter-panel');
    const backdrop = document.getElementById('sidebar-filter-backdrop');
    const header = document.querySelector('.main-header');
    const footer = document.querySelector('.main-footer');

    if (!toggle || !panel || !backdrop) return;

    function getHeaderHeight() {
        return header ? header.getBoundingClientRect().height : 0;
    }

    function updatePanelPosition() {
        if (window.innerWidth < 992) {
            panel.style.top = '90px';
            panel.style.left = '12px';
            panel.style.right = '12px';
            panel.style.maxHeight = 'calc(100vh - 120px)';
            return;
        }

        const headerHeight = getHeaderHeight();
        let top = headerHeight + 20;

        if (footer) {
            const footerRect = footer.getBoundingClientRect();
            const panelHeight = panel.offsetHeight;
            const limitTop = footerRect.top - panelHeight - 20;
            if (limitTop < top) {
                top = Math.max(20, limitTop);
            }
        }

        panel.style.top = top + 'px';
        panel.style.left = '20px';
        panel.style.right = 'auto';
        panel.style.maxHeight = 'calc(100vh - ' + (top + 20) + 'px)';
    }

    function openPanel() {
        panel.classList.add('open');
        backdrop.classList.add('open');
        toggle.classList.add('active');
        toggle.setAttribute('aria-expanded', 'true');
        panel.setAttribute('aria-hidden', 'false');
        updatePanelPosition();
    }

    function closePanel() {
        panel.classList.remove('open');
        backdrop.classList.remove('open');
        toggle.classList.remove('active');
        toggle.setAttribute('aria-expanded', 'false');
        panel.setAttribute('aria-hidden', 'true');
    }

    toggle.addEventListener('click', function () {
        if (panel.classList.contains('open')) {
            closePanel();
        } else {
            openPanel();
        }
    });

    backdrop.addEventListener('click', closePanel);
    window.addEventListener('resize', function () {
        if (panel.classList.contains('open')) {
            updatePanelPosition();
        }
    });
    window.addEventListener('scroll', function () {
        if (panel.classList.contains('open')) {
            updatePanelPosition();
        }
    }, { passive: true });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closePanel();
        }
    });
})();
</script>
