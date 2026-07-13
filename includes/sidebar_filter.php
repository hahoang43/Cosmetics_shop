<?php
// Lấy danh sách danh mục từ DB để hiển thị
$stmt_cat = $conn->query("SELECT * FROM Category ORDER BY name ASC");
$categories = $stmt_cat->fetchAll();

$current_page = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$current_path = '/Cosmetics_shop/frontend/' . $current_page;

$selected_categories = [];
if (isset($_GET['categories'])) {
    if (is_array($_GET['categories'])) {
        $selected_categories = array_values(array_filter(array_map('intval', $_GET['categories'])));
    } elseif ((int)$_GET['categories'] > 0) {
        $selected_categories = [(int)$_GET['categories']];
    }
}

// Giữ tương thích với các URL cũ dùng category đơn lẻ ở menu trên
$current_cat = isset($_GET['category']) ? (int)$_GET['category'] : 0;
if (empty($selected_categories) && $current_cat > 0 && !in_array($current_cat, [1, 2], true)) {
    $selected_categories = [$current_cat];
}

$current_min_price = isset($_GET['min_price']) ? max(0, (int)$_GET['min_price']) : 0;
$current_max_price = isset($_GET['max_price']) ? min(100000000, (int)$_GET['max_price']) : 0;

$clear_params = $_GET;
unset($clear_params['categories'], $clear_params['category'], $clear_params['price_range'], $clear_params['min_price'], $clear_params['max_price'], $clear_params['page']);
?>

<button type="button" class="sidebar-filter-toggle" id="sidebar-filter-toggle" aria-controls="sidebar-filter-panel" aria-expanded="false" title="Mở bộ lọc" aria-label="Mở bộ lọc">
    <i class="fa-solid fa-filter"></i>
    Bộ lọc
</button>

<div class="sidebar-filter-backdrop" id="sidebar-filter-backdrop" aria-hidden="true"></div>

<aside class="sidebar-filter" id="sidebar-filter-panel" aria-hidden="true">
    <div class="filter-group">
        <h4>Danh mục sản phẩm</h4>
        <a class="filter-clear-link <?= empty($selected_categories) ? 'active' : '' ?>" href="<?= $current_path . (!empty($clear_params) ? '?' . http_build_query($clear_params) : '') ?>">Tất cả sản phẩm</a>

        <form class="filter-form" action="<?= $current_path ?>" method="GET">
            <?php foreach ($clear_params as $paramKey => $paramValue): ?>
                <?php if (is_array($paramValue)): ?>
                    <?php foreach ($paramValue as $arrayValue): ?>
                        <input type="hidden" name="<?= htmlspecialchars($paramKey) ?>[]" value="<?= htmlspecialchars($arrayValue) ?>">
                    <?php endforeach; ?>
                <?php elseif ($paramValue !== null && $paramValue !== ''): ?>
                    <input type="hidden" name="<?= htmlspecialchars($paramKey) ?>" value="<?= htmlspecialchars($paramValue) ?>">
                <?php endif; ?>
            <?php endforeach; ?>

            <div class="filter-checkbox-list">
                <?php foreach ($categories as $cat): ?>
                    <?php $isChecked = in_array((int)$cat['id'], $selected_categories, true); ?>
                    <label class="filter-checkbox-item">
                        <input type="checkbox" name="categories[]" value="<?= (int)$cat['id'] ?>" <?= $isChecked ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($cat['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="price-custom-form">
                <div class="price-custom-title">Khoảng giá</div>
                <div class="price-custom-row">
                    <input type="number" name="min_price" min="0" max="100000000" step="1000" placeholder="Từ 0" value="<?= $current_min_price > 0 ? $current_min_price : '' ?>">
                    <input type="number" name="max_price" min="0" max="100000000" step="1000" placeholder="Đến 100.000.000" value="<?= $current_max_price > 0 ? $current_max_price : '' ?>">
                </div>
                <button type="submit" class="price-custom-submit">Áp dụng</button>
            </div>
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
