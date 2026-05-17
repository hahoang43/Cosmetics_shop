<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user']['id'];
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];
    $re_new_pass = $_POST['re_new_password'];

    // 1. Kiểm tra mật khẩu mới và xác nhận mật khẩu
    if ($new_pass !== $re_new_pass) {
        $_SESSION['flash_error'] = "Mật khẩu xác nhận không trùng khớp!";
        header("Location: ../frontend/change_password.php");
        exit;
    }

    if (strlen($new_pass) < 6) {
        $_SESSION['flash_error'] = "Mật khẩu mới phải có ít nhất 6 ký tự!";
        header("Location: ../frontend/change_password.php");
        exit;
    }

    try {
        // 2. Lấy mật khẩu hiện tại trong DB để đối chiếu
        $stmt = $conn->prepare("SELECT password FROM User WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        // 3. Kiểm tra mật khẩu cũ (Dùng password_verify nếu bạn có dùng Bcrypt)
        if ($user && password_verify($old_pass, $user['password'])) {
            
            // 4. Mã hóa mật khẩu mới và cập nhật
            $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE User SET password = ? WHERE id = ?");
            $update->execute([$hashed_password, $user_id]);

            $_SESSION['flash_success'] = "Đổi mật khẩu thành công!";
        } else {
            $_SESSION['flash_error'] = "Mật khẩu hiện tại không chính xác!";
        }
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = "Lỗi hệ thống: " . $e->getMessage();
    }
}

header("Location: ../frontend/change_password.php");
exit;