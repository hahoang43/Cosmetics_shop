<?php
session_start();
require_once '../config/database.php';
require_once '../includes/popup_notify.php';
$conn = getDatabase();

// 1. Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// 2. Chỉ xử lý khi có dữ liệu POST gửi lên
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy ID của người dùng đang đăng nhập từ Session
    $user_id = (int)$_SESSION['user']['id'];
    
    // Nhận dữ liệu từ Form gửi lên và dọn dẹp khoảng trắng
    $fullname = trim($_POST['fullname']);
    $phone_number = trim($_POST['phone_number']);
    $address = trim($_POST['address']);

    // Validate cơ bản: Không được để trống Họ và Tên
    if (empty($fullname)) {
        popup_error('Thiếu thông tin', 'Vui lòng nhập Họ và tên!', true);
        exit;
    }

    try {
        // 3. Thực thi câu lệnh UPDATE vào Database
        $sql = "UPDATE User SET fullname = ?, phone_number = ?, address = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fullname, $phone_number, $address, $user_id]);

        // 4. QUAN TRỌNG: Cập nhật lại Session ngay lập tức!
        // Nếu không làm bước này, giao diện profile.php sẽ vẫn hiện thông tin cũ cho đến khi đăng nhập lại
        $_SESSION['user']['fullname'] = $fullname;
        $_SESSION['user']['phone_number'] = $phone_number;
        $_SESSION['user']['address'] = $address;

        // Báo thành công bằng flash session và chuyển hướng
        $_SESSION['flash_success'] = 'Cập nhật thông tin thành công!';
        popup_success('Thành công', 'Cập nhật thông tin thành công!', 'profile.php');
        exit;

    } catch (PDOException $e) {
        // Báo lỗi bằng flash session và chuyển hướng
        popup_error('Lỗi Database', $e->getMessage(), true);
        exit;
    }
} else {
    // Nếu ai đó cố tình gõ trực tiếp file này lên thanh URL thì đẩy về lại profile
    header("Location: profile.php");
    exit;
}
?>