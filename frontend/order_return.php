<?php 
require_once '../config/database.php';
require_once '../includes/popup_notify.php';
$conn = getDatabase();
require_once '../includes/header.php'; 
echo popup_assets();

if (!isset($_SESSION['user'])) { 
    header("Location: login.php"); 
    exit; 
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user']['id'];
$fullname = $_SESSION['user']['fullname'] ?? '';

// Kiểm tra đơn hàng có đúng điều kiện hoàn trả không
$stmt = $conn->prepare("SELECT * FROM Orders WHERE id = ? AND user_id = ? AND status = 2");
$stmt->execute([$id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    echo "<div class='container' style='margin-top:50px;'><h3>Đơn hàng không hợp lệ để thực hiện hoàn trả.</h3></div>";
    include '../includes/footer.php'; 
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reason = trim($_POST['return_reason']);
    if (!empty($reason)) {
        $stmt_update = $conn->prepare("UPDATE Orders SET status = 4, return_reason = ? WHERE id = ?");
        $stmt_update->execute([$reason, $id]);
        popup_success('Đã gửi yêu cầu', 'Yêu cầu trả hàng của bạn đã được gửi thành công. Vui lòng chờ hệ thống kiểm duyệt.', 'order_history.php');
        exit;
    }
}
?>

<link rel="stylesheet" href="/Cosmetics_shop/assets/css/profile.css">

<div class="container" style="margin-top: 30px; margin-bottom: 60px;">
    <div class="profile-container">
        
        <aside class="profile-sidebar">
            <div class="user-brief">
                <i class="fa-solid fa-circle-user"></i>
                <div>
                    <div class="username"><?= htmlspecialchars($fullname) ?></div>
                    <a href="profile.php" style="font-size: 12px; color: #999; text-decoration: none;">Sửa hồ sơ</a>
                </div>
            </div>
            <ul class="profile-menu">
                <li><a href="profile.php"><i class="fa-solid fa-user"></i> Thông tin tài khoản</a></li>
                <li><a href="order_history.php" class="active"><i class="fa-solid fa-clipboard-list"></i> Đơn hàng của tôi</a></li>
                <li><a href="change_password.php"><i class="fa-solid fa-key"></i> Đổi mật khẩu</a></li>
                <li><a href="logout.php" style="color: #e74c3c;"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></li>
            </ul>
        </aside>

        <main class="profile-main" style="flex: 1;">
            <div style="border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 25px;">
                <h2 style="font-family: 'Playfair Display', serif; font-size: 24px; color: #2c3e50;">
                    <i class="fa-solid fa-arrow-rotate-left"></i> Yêu cầu hoàn trả đơn hàng #<?= htmlspecialchars(!empty($order['order_code']) ? $order['order_code'] : $order['id']) ?>
                </h2>
                <p style="color: #777; font-size: 14px; margin-top: 5px;">Chính sách hỗ trợ đổi trả hàng lỗi, sai phân loại trong vòng 7 ngày kể từ khi nhận hàng.</p>
            </div>
            
            <form action="" method="POST" style="max-width: 650px;">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 10px; font-size: 15px; color: #333;">
                        Lý do hoàn trả hàng *
                    </label>
                    <textarea name="return_reason" rows="6" required 
                              placeholder="Vui lòng mô tả chi tiết lý do đổi trả để Lumina xử lý nhanh nhất cho bạn nhé (Ví dụ: Giao sai phân loại dung tích, son bị trầy xước vỏ, móp méo do vận chuyển...)" 
                              style="width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; font-size: 14px; line-height: 1.5; outline: none; transition: 0.3s; box-shadow: inset 0 1px 3px rgba(0,0,0,0.02);"
                              onfocus="this.style.borderColor='#D4A373'" onblur="this.style.borderColor='#ddd'"></textarea>
                </div>
                
                <button type="submit" style="background: #D4A373; color: white; border: none; padding: 12px 30px; font-weight: bold; border-radius: 5px; cursor: pointer; font-size: 15px; transition: 0.3s;" onmouseover="this.style.background='#c2905f'" onmouseout="this.style.background='#D4A373'">
                    GỬI YÊU CẦU HOÀN TRẢ
                </button>
            </form>
        </main>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>