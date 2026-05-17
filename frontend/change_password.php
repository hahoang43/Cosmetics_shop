<?php 
require_once '../config/database.php';
require_once '../includes/header.php'; 

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$fullname = $_SESSION['user']['fullname'];
?>
<link rel="stylesheet" href="../assets/css/profile.css">
<div class="container">
    <div class="profile-container">
        
        <aside class="profile-sidebar">
            <div class="user-brief">
                <i class="fa-solid fa-circle-user"></i>
                <div>
                    <div class="username"><?= htmlspecialchars($fullname) ?></div>
                    <span style="font-size: 12px; color: #999;">Cập nhật mật khẩu</span>
                </div>
            </div>
            
            <ul class="profile-menu">
                <li><a href="profile.php"><i class="fa-solid fa-user"></i> Thông tin tài khoản</a></li>
                <li><a href="order_history.php"><i class="fa-solid fa-clipboard-list"></i> Đơn hàng của tôi</a></li>
                <li><a href="change_password.php" class="active"><i class="fa-solid fa-key"></i> Đổi mật khẩu</a></li>
                <li><a href="logout.php" style="color: #e74c3c;"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></li>
            </ul>
        </aside>

        <main class="profile-main">
            <h2>Đổi mật khẩu</h2>
            <p style="color: #777; margin-bottom: 30px;">Để bảo mật tài khoản, vui lòng không chia sẻ mật khẩu cho người khác</p>

            <form action="../backend/change_password_process.php" method="POST">
                <div class="form-info-group">
                    <label>Mật khẩu hiện tại</label>
                    <input type="password" name="old_password" required placeholder="Nhập mật khẩu cũ">
                </div>

                <div class="form-info-group">
                    <label>Mật khẩu mới</label>
                    <input type="password" name="new_password" required placeholder="Tối thiểu 6 ký tự">
                </div>

                <div class="form-info-group">
                    <label>Xác nhận mật khẩu mới</label>
                    <input type="password" name="re_new_password" required placeholder="Nhập lại mật khẩu mới">
                </div>

                <button type="submit" class="btn-update-profile" style="background: #2c3e50;">Cập nhật mật khẩu</button>
            </form>
        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($_SESSION['flash_success'])): ?>
    <script>
        Swal.fire({ icon: 'success', title: 'Thành công!', text: '<?= $_SESSION['flash_success'] ?>', timer: 2000, showConfirmButton: false });
    </script>
<?php unset($_SESSION['flash_success']); endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <script>
        Swal.fire({ icon: 'error', title: 'Lỗi!', text: '<?= $_SESSION['flash_error'] ?>' });
    </script>
<?php unset($_SESSION['flash_error']); endif; ?>

<?php require_once '../includes/footer.php'; ?>