<?php
require_once __DIR__ . '/../config/settings.php';
?>
<footer class="main-footer">
    <div class="container footer-grid">
        <div class="footer-info">
            <a href="/Cosmetics_shop/frontend/index.php" class="footer-logo" style="display:inline-block;margin-bottom:12px;"><img src="/Cosmetics_shop/assets/images/logo.png" alt="Lumina Cosmetics" style="height:48px;border-radius:6px;object-fit:cover;"></a>
            <p style="color: #d7d7d7; margin-bottom: 12px;">Nâng niu vẻ đẹp tự nhiên của bạn với sản phẩm chính hãng, an toàn và được tuyển chọn kỹ càng.</p>
            <div class="socials" style="display:flex;gap:10px;">
                <a href="#" aria-label="facebook"><i class="fa-brands fa-facebook"></i></a>
                <a href="#" aria-label="instagram"><i class="fa-brands fa-instagram"></i></a>
            </div>
        </div>

        <div class="footer-links">
            <h4>Về chúng tôi</h4>
            <ul>
                <li><a href="/Cosmetics_shop/frontend/products.php">Sản phẩm</a></li>
                <li><a href="/Cosmetics_shop/policies/warranty.php">Chính sách bảo hành</a></li>
                <li><a href="/Cosmetics_shop/policies/return.php">Đổi trả</a></li>
            </ul>
        </div>

        <div class="footer-contact">
            <h4>Liên hệ</h4>
            <p style="margin:6px 0;"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars(SHOP_ADDRESS) ?></p>
            <p style="margin:6px 0;"><i class="fa-solid fa-envelope"></i> support@lumina.vn</p>
            <p style="margin:6px 0;"><i class="fa-solid fa-phone"></i> 090x xxx xxx</p>
        </div>
    </div>

    <div class="container" style="display:flex;justify-content:space-between;align-items:center;gap:20px;padding-top:18px;padding-bottom:28px;border-top:1px solid rgba(255,255,255,0.06);">
        <div style="color:#bfbfbf;font-size:14px;">&copy; <?= date('Y') ?> <?= htmlspecialchars(SHOP_NAME) ?>. Bảo lưu mọi quyền.</div>
        <div style="display:flex;gap:10px;align-items:center;color:#bfbfbf;flex-wrap:wrap;justify-content:flex-end;">
            <span style="font-size:13px;color:#ddd;">Thanh toán an toàn</span>
            <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border:1px solid rgba(255,255,255,0.12);border-radius:999px;font-size:12px;color:#f2f2f2;"><i class="fa-brands fa-cc-visa"></i> Visa</span>
            <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border:1px solid rgba(255,255,255,0.12);border-radius:999px;font-size:12px;color:#f2f2f2;"><i class="fa-solid fa-wallet"></i> MoMo</span>
        </div>
    </div>
</footer>

<?php include_once __DIR__ . '/chatbot.php'; ?>

</body>
</html>