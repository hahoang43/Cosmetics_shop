<?php
require_once __DIR__ . '/../config/settings.php';
include '../includes/header.php';
?>
<main class="container contact-page">
    <div class="contact-header" style="text-align: center; margin-bottom: 50px;">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 36px; color: #333;">Liên hệ với chúng tôi</h1>
        <p style="color: #777;">Lumina luôn sẵn sàng lắng nghe và hỗ trợ bạn 24/7</p>
    </div>

    <div class="contact-grid">
        <div class="contact-info-form">
            <div class="info-details" style="display: flex; gap: 20px; margin-bottom: 40px; flex-wrap: wrap;">
                    <div class="info-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <div>
                            <strong>Địa chỉ:</strong>
                            <p><?= htmlspecialchars(SHOP_ADDRESS) ?></p>
                        </div>
                    </div>
                <div class="info-item">
                    <i class="fa-solid fa-phone"></i>
                    <div>
                        <strong>Hotline:</strong>
                        <p>090x xxx xxx</p>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fa-solid fa-envelope"></i>
                    <div>
                        <strong>Email:</strong>
                        <p>support@lumina.vn</p>
                    </div>
                </div>
            </div>

            <div class="contact-form-box">
                <h3 style="margin-bottom: 20px;">Gửi tin nhắn cho chúng tôi</h3>
                <form action="../backend/contact_process.php" method="POST">
                    <div class="form-row">
                        <input type="text" name="name" placeholder="Họ tên của bạn" required>
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <input type="text" name="subject" placeholder="Chủ đề">
                    <textarea name="message" placeholder="Lời nhắn của bạn..." rows="5" required></textarea>
                    <button type="submit" class="btn-auth-submit">GỬI PHẢN HỒI</button>
                </form>
            </div>
        </div>

        <div class="contact-map">
            <h3>Vị trí cửa hàng</h3>
            <div class="map-container" style="margin-top:12px;">
                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                <div id="contact-map" style="width:100%;height:420px;border-radius:10px;border:1px solid #eee;"></div>
                <script>
                    (function(){
                        const lat = <?= json_encode(SHOP_LAT) ?>;
                        const lng = <?= json_encode(SHOP_LNG) ?>;
                        const name = <?= json_encode(SHOP_NAME) ?>;
                        const address = <?= json_encode(SHOP_ADDRESS) ?>;

                        const map = L.map('contact-map', {scrollWheelZoom: false}).setView([lat, lng], 15);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
                        const marker = L.marker([lat, lng]).addTo(map);
                        marker.bindPopup('<strong>'+name+'</strong><br/>'+address).openPopup();
                    })();
                </script>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>