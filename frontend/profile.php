<?php 
require_once '../config/database.php';
require_once '../includes/popup_notify.php';
$conn = getDatabase();
require_once '../includes/header.php'; 

// Kiểm tra nếu chưa đăng nhập thì đá về trang login
if (!isset($_SESSION['user'])) {
    popup_warning('Cần đăng nhập', 'Vui lòng đăng nhập để xem hồ sơ cá nhân.', 'login.php');
    exit;
}

// Giả sử lấy dữ liệu từ Session hoặc truy vấn lại từ DB
$user = $_SESSION['user']; 
$fullname = $user['fullname'] ?? '';
$email = $user['email'] ?? '';
$phone = $user['phone_number'] ?? '';
$address = $user['address'] ?? '';
?>
<div class="container">
    <div class="profile-container">
        
        <aside class="profile-sidebar">
            <div class="user-brief">
                <i class="fa-solid fa-circle-user"></i>
                <div>
                    <div class="username"><?= htmlspecialchars($fullname) ?></div>
                    <a href="#" style="font-size: 12px; color: #999; text-decoration: none;">Sửa hồ sơ</a>
                </div>
            </div>
            
            <ul class="profile-menu">
                <li><a href="profile.php" class="active"><i class="fa-solid fa-user"></i> Thông tin tài khoản</a></li>
                <li><a href="order_history.php"><i class="fa-solid fa-clipboard-list"></i> Đơn hàng của tôi</a></li>
                <li><a href="change_password.php"><i class="fa-solid fa-key"></i> Đổi mật khẩu</a></li>
                <li><a href="logout.php" style="color: #e74c3c;"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></li>
            </ul>
        </aside>

        <main class="profile-main">
            <h2>Hồ sơ của tôi</h2>
            <p style="color: #777; margin-bottom: 30px;">Quản lý thông tin hồ sơ để bảo mật tài khoản</p>

            <form action="update_profile_process.php" method="POST">
                <div class="form-info-group">
                    <label>Họ và tên</label>
                    <input type="text" name="fullname" value="<?= htmlspecialchars($fullname) ?>" required>
                </div>

                <div class="form-info-group">
                    <label>Email (Không thể thay đổi)</label>
                    <input type="email" value="<?= htmlspecialchars($email) ?>" disabled>
                </div>

                <div class="form-info-group">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone_number" value="<?= htmlspecialchars($phone) ?>">
                </div>

                <div class="form-info-group">
                    <label>Địa chỉ nhận hàng mặc định</label>
                    <textarea name="address" rows="3"><?= htmlspecialchars($address) ?></textarea>
                </div>

                <button type="submit" class="btn-update-profile">Lưu thay đổi</button>
            </form>
        </main>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (isset($_SESSION['flash_success'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Thành công!',
            text: '<?= $_SESSION['flash_success'] ?>',
            showConfirmButton: false,
            timer: 2000, // Tự động tắt sau 2 giây
            backdrop: `rgba(0,0,0,0.4)`
        });
    </script>
<?php unset($_SESSION['flash_success']); endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Ối, có lỗi rồi!',
            text: '<?= $_SESSION['flash_error'] ?>',
            confirmButtonColor: '#d33'
        });
    </script>
<?php unset($_SESSION['flash_error']); endif; ?>
<?php require_once '../includes/footer.php'; ?>