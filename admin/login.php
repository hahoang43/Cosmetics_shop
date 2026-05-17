<?php
session_start();
require_once '../config/database.php';

// Nếu người dùng đã đăng nhập từ trước rồi thì tự động chuyển hướng vào Dashboard
if (isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

$error = ''; // Biến lưu trữ thông báo lỗi

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ Email và Mật khẩu!";
    } else {
        try {
            // Truy vấn kiểm tra tài khoản. 
            // Giả sử trong bảng User, tài khoản Admin sẽ có cột role = 1 (0 là khách hàng)
            $stmt = $conn->prepare("SELECT * FROM User WHERE email = ? AND role = 1 AND deleted = 0");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            // Kiểm tra mật khẩu
            // Lưu ý: Đang so sánh trực tiếp. Nếu đồ án của bạn dùng MD5 để mã hóa mật khẩu, 
            // hãy đổi thành: if ($admin && $admin['password'] === md5($password))
            if ($admin && password_verify($password, $admin['password'])) { 
                
                // Khởi tạo Session lưu phiên đăng nhập của Admin
                $_SESSION['admin'] = [
                    'id' => $admin['id'],
                    'fullname' => $admin['fullname'],
                    'email' => $admin['email']
                ];
                
                // Chuyển hướng vào trang quản trị
                header("Location: index.php");
                exit;
            } else {
                $error = "Sai thông tin đăng nhập hoặc bạn không có quyền Quản trị viên!";
            }
        } catch (PDOException $e) {
            $error = "Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Quản trị - Lumina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { 
            background-color: #2c3e50; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
        }
        .login-container {
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: #2c3e50;
            font-size: 28px;
            letter-spacing: 2px;
        }
        .login-header h2 span {
            color: #D4A373;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            outline: none;
            transition: 0.3s;
        }
        .form-group input:focus {
            border-color: #D4A373;
            box-shadow: 0 0 5px rgba(212, 163, 115, 0.3);
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #D4A373;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-login:hover {
            background: #c2905f;
        }
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <h2>LUMINA<span>.</span></h2>
            <p style="color: #777; margin-top: 5px;">Hệ thống Quản trị</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-msg">
                <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Email đăng nhập</label>
                <input type="email" name="email" required placeholder="admin@lumina.com">
            </div>
            
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn-login">ĐĂNG NHẬP</button>
        </form>
    </div>

</body>
</html>