<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<main class="container contact-page" style="margin-top: 50px; margin-bottom: 80px;">
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
                        <p>Số 2, Võ Oanh, P.25, Bình Thạnh, TP.HCM</p>
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
            <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.126582153123!2d106.71189917573618!3d10.801615658727181!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x317528a45951a301%3A0xc338902099395460!2zVHLGsOG7nW5nIMSQ4bqhaSBo4buNYyBHaWFvIHRow7RuZyB24bqtbiB04bqjaSBUUC5IQ00!5e0!3m2!1svi!2svn!4v1715670000000!5m2!1svi!2svn" width="100%" height="450" style="border:0; border-radius: 10px;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>