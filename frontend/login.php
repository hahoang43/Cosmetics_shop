<?php 

include '../includes/header.php'; ?>
<div class="auth-bg">
    <div class="container auth-container">
        <div class="auth-left">
            <img src="/Cosmetics_shop/assets/images/logo.png" alt="Lumina Cosmetics" style="width: 100%;">
            <p style="font-size: 1.2rem; margin-top: 20px; font-weight: 500; color: #555;">
                Đánh thức vẻ đẹp tự nhiên của bạn cùng Lumina.
            </p>
        </div>

        <div class="auth-right">
            
            <form class="auth-form" id="login-form">
                <h2 class="auth-title">Đăng Nhập</h2>
                <div class="input-group">
                    <input id="login-email" type="email" placeholder="Email của bạn" required>
                </div>
                <div class="input-group">
                    <input id="login-password" type="password" placeholder="Mật khẩu" required>
                </div>
                <button type="submit" class="btn-auth-submit">ĐĂNG NHẬP</button>
                <div class="auth-links">
                    <a href="javascript:void(0)" id="show-forgot" class="link">Quên mật khẩu?</a>
                </div>
                <hr>
                <button type="button" class="btn-switch-form" id="show-register">Tạo tài khoản mới</button>
            </form>

            <form class="auth-form" id="register-form" style="display:none;">
                <h2 class="auth-title">Đăng Ký</h2>
                <div class="input-group">
                    <input id="register-fullname" type="text" placeholder="Họ và tên" required>
                </div>
                <div class="input-group">
                    <input id="register-email" type="email" placeholder="Email nhận tin" required>
                </div>
                <div class="input-group">
                    <input id="register-password" type="password" placeholder="Mật khẩu" required>
                </div>
                <div class="input-group">
                    <input id="register-password2" type="password" placeholder="Nhập lại mật khẩu" required>
                </div>
                <button type="submit" class="btn-auth-submit">ĐĂNG KÝ NGAY</button>
                <div class="auth-links">
                    <a href="javascript:void(0)" id="back-login1" class="link">Đã có tài khoản? Đăng nhập</a>
                </div>
            </form>

            <form class="auth-form" id="forgot-form" style="display:none;">
                <h2 class="auth-title">Quên Mật Khẩu</h2>
                <p style="margin-bottom: 15px; font-size: 0.9rem; color: #666;">
                    Nhập email bạn đã đăng ký, chúng tôi sẽ gửi hướng dẫn khôi phục.
                </p>
                <div class="input-group">
                    <input id="forgot-email" type="email" placeholder="Email của bạn" required>
                </div>
                <button type="submit" class="btn-auth-submit">GỬI YÊU CẦU</button>
                <div class="auth-links">
                    <a href="javascript:void(0)" id="back-login2" class="link">Quay lại đăng nhập</a>
                </div>
            </form>

        </div>
    </div>
</div>
<script src="/Cosmetics_shop/assets/js/jquery-3.7.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/Cosmetics_shop/assets/js/login.js"></script>

<?php 
include '../includes/footer.php'; 
?>