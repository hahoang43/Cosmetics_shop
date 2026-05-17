<?php
session_start();
require_once '../config/database.php';

// Kiểm tra xem có yêu cầu gửi đến không
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- CHỨC NĂNG ĐĂNG KÝ ---
    if ($action == 'register') {
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // 1. Kiểm tra email đã tồn tại chưa
        $checkEmail = $conn->prepare("SELECT id FROM User WHERE email = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email này đã được sử dụng!']);
            exit;
        }

        // 2. Hash mật khẩu để bảo mật
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // 3. Lưu vào database (Mặc định role_id = 2 là Khách hàng)
            $stmt = $conn->prepare("INSERT INTO User (fullname, email, password, role_id) VALUES (?, ?, ?, 2)");
            $stmt->execute([$fullname, $email, $hashed_password]);
            echo json_encode(['status' => 'success', 'message' => 'Đăng ký thành công! Đang chuyển hướng...']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    // --- CHỨC NĂNG ĐĂNG NHẬP ---
    if ($action == 'login') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // 1. Tìm user theo email
        $stmt = $conn->prepare("SELECT * FROM User WHERE email = ? AND deleted = 0");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 2. Kiểm tra user và mật khẩu
        if ($user && password_verify($password, $user['password'])) {
            // Đăng nhập đúng: Lưu thông tin vào Session
            $_SESSION['user'] = [
                'id' => $user['id'],
                'fullname' => $user['fullname'],
                'role_id' => $user['role_id']
            ];
            echo json_encode(['status' => 'success', 'message' => 'Đăng nhập thành công!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Email hoặc mật khẩu không chính xác!']);
        }
    }
}