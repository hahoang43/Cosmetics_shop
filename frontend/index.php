<?php
require_once '../config/database.php';
$conn = getDatabase();

if (function_exists('opcache_reset')) {
    opcache_reset();
}

require_once '../includes/header.php';
include_once '../includes/chatbot.php';

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
<main class="home-page">
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
                        <img src="/Cosmetics_shop/assets/uploads/banners/<?= htmlspecialchars($b['image']) ?>" alt="Banner">
                    </div>
                <?php endforeach; ?>
                
                <button type="button" class="prev" onclick="plusSlides(-1)" aria-label="Ảnh trước">&#10094;</button>
                <button type="button" class="next" onclick="plusSlides(1)" aria-label="Ảnh tiếp theo">&#10095;</button>
                
                <div class="hero-text">
                    <div style="display: inline-block; padding: 8px 16px; border-radius: 999px; background: rgba(212, 163, 115, 0.12); color: #a96e3a; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 18px;">Lumina Cosmetics</div>
                    <h2 style="font-family: 'Playfair Display', serif; font-size: clamp(22px, 7vw, 56px); line-height: 1.1; color: #2f241d; margin: 0 0 14px;">Rạng rỡ mỗi ngày</h2>
                    <p style="font-size: clamp(14px, 3.8vw, 18px); color: #6f5b4d; margin: 0 0 28px;">Khám phá bộ sưu tập mỹ phẩm cao cấp mới nhất từ Lumina</p>
                    <a href="products.php" class="btn-shop">Mua ngay</a>
                </div>
            </div>
        <?php else: ?>
            <div style="min-height: 420px; border-radius: 18px; overflow: hidden; background: linear-gradient(135deg, #f7e7d7 0%, #fdfaf6 45%, #f1d4bc 100%); display: flex; align-items: center; justify-content: center; position: relative; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);">
                <div style="text-align: center; padding: 40px 20px; max-width: 720px;">
                    <div style="display: inline-block; padding: 8px 16px; border-radius: 999px; background: rgba(212, 163, 115, 0.12); color: #a96e3a; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 18px;">Lumina Cosmetics</div>
                    <h2 style="font-family: 'Playfair Display', serif; font-size: clamp(22px, 7vw, 56px); line-height: 1.1; color: #2f241d; margin: 0 0 14px;">Rạng rỡ mỗi ngày</h2>
                    <p style="font-size: clamp(14px, 3.8vw, 18px); color: #6f5b4d; margin: 0 0 28px;">Khám phá bộ sưu tập mỹ phẩm cao cấp mới nhất từ Lumina</p>
                    <a href="products.php" class="btn-shop">Mua ngay</a>
                </div>
            </div>
        <?php endif; ?>
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

        // Initialize slideshow on page load
        document.addEventListener('DOMContentLoaded', function() {
            showSlides(slideIndex);
            slideInterval = setInterval(function() { showSlides(slideIndex += 1); }, 4000);
        });
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
                            <img src="<?php echo htmlspecialchars(imageSrc($item['thumbnail'] ?? '', 'products')); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
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
                            <img src="<?php echo htmlspecialchars(imageSrc($item['thumbnail'] ?? '', 'products')); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
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
