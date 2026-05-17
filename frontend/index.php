<?php require_once '../includes/header.php'; ?>
<?php
require_once '../config/database.php';

try {
    $stmt_hot = $conn->prepare("SELECT * FROM Product WHERE deleted = 0 ORDER BY id DESC LIMIT 4");
    $stmt_hot->execute();
    $hot_products = $stmt_hot->fetchAll();

    $stmt_new = $conn->prepare("SELECT * FROM Product WHERE deleted = 0 ORDER BY created_at DESC LIMIT 4");
    $stmt_new->execute();
    $new_products = $stmt_new->fetchAll();

} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}
?>
<main>
<?php
        // Truy vấn lấy danh sách banner từ Database
        $stmt_banners = $conn->prepare("SELECT * FROM Banner ORDER BY id DESC");
        $stmt_banners->execute();
        $banners = $stmt_banners->fetchAll();
    ?>

    <section class="hero-banner">
        <?php if (count($banners) > 0): ?>
            <div class="slideshow-container">
                <?php foreach ($banners as $index => $b): ?>
                    <div class="mySlides fade" style="<?= $index == 0 ? 'display: block;' : 'display: none;' ?>">
                        <img src="../assets/uploads/banners/<?= htmlspecialchars($b['image']) ?>" alt="Banner">
                    </div>
                <?php endforeach; ?>
                
                <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
                <a class="next" onclick="plusSlides(1)">&#10095;</a>
            </div>
        <?php else: ?>
            <img src="../assets/images/banner_skincare_sale.jpg" alt="Default Banner">
        <?php endif; ?>

        <div class="hero-text">
            <h2>Rạng rỡ mỗi ngày</h2>
            <p>Khám phá bộ sưu tập mỹ phẩm cao cấp mới nhất từ Lumina</p>
            <a href="products.php" class="btn-shop">Mua ngay</a>
        </div>
    </section>

    <script>
        let slideIndex = 1;
        let slideInterval;

        function showSlides(n) {
            let slides = document.getElementsByClassName("mySlides");
            if (slides.length === 0) return;
            
            if (n > slides.length) {slideIndex = 1}
            if (n < 1) {slideIndex = slides.length}
            
            for (let i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";
            }
            slides[slideIndex-1].style.display = "block";
        }

        function plusSlides(n) {
            clearInterval(slideInterval); // Dừng auto khi người dùng tự bấm
            showSlides(slideIndex += n);
            slideInterval = setInterval(function() { showSlides(slideIndex += 1); }, 4000); // Mở lại auto
        }

        // Tự động chuyển ảnh mỗi 4 giây
        slideInterval = setInterval(function() { showSlides(slideIndex += 1); }, 4000);
    </script>

    <section class="container product-section">
        <h2 class="section-title">Khuyến mãi cực hot</h2>
        <div class="product-grid">
            <?php foreach ($hot_products as $item): ?>
                <div class="product-card">
                    <?php if ($item['old_price'] > $item['price']): ?>
                        <div class="product-badge sale-badge">Sale</div>
                    <?php endif; ?>
                    
                    <a href="product_detail.php?id=<?php echo $item['id']; ?>" class="product-link">
                        <div class="product-img">
                            <img src="../assets/uploads/products/<?php echo htmlspecialchars($item['thumbnail']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                        </div>
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <div class="price">
                            <span class="price-label">Giá từ:</span>
                            <?php if ($item['old_price'] > $item['price']): ?>
                                <span class="old-price"><?php echo number_format($item['old_price'], 0, ',', '.'); ?>đ</span>
                            <?php endif; ?>
                            <span class="current-price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</span>
                        </div>
                    </a>
                    
                    <a href="product_detail.php?id=<?= $item['id'] ?>" class="btn-add-cart-grid">
                        <i class="fa-solid fa-list"></i> Chọn phân loại
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="container product-section">
        <h2 class="section-title">Sản phẩm mới nhất</h2>
        <div class="product-grid">
            <?php foreach ($new_products as $item): ?>
                <div class="product-card">
                    <div class="product-badge new-badge">New</div>
                    
                    <a href="product_detail.php?id=<?php echo $item['id']; ?>" class="product-link">
                        <div class="product-img">
                            <img src="../assets/uploads/products/<?php echo htmlspecialchars($item['thumbnail']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                        </div>
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <div class="price">
                            <span class="price-label">Giá từ:</span>
                            <?php if ($item['old_price'] > $item['price']): ?>
                                <span class="old-price"><?php echo number_format($item['old_price'], 0, ',', '.'); ?>đ</span>
                            <?php endif; ?>
                            <span class="current-price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</span>
                        </div>
                    </a>
                    
                    <a href="product_detail.php?id=<?= $item['id'] ?>" class="btn-add-cart-grid">
                        <i class="fa-solid fa-list"></i> Chọn phân loại
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php require_once '../includes/footer.php'; ?>