<?php
// Database Configuration - Cấu hình Cơ sở dữ liệu
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_mypham');

// Kết nối PDO luôn được khởi tạo dưới dạng object
/** @var PDO $conn */

try {
    // Chuỗi DSN (Data Source Name)
    // Sử dụng utf8mb4 để hỗ trợ hiển thị tiếng Việt chuẩn và cả Emoji (nếu khách bình luận bằng icon)
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    // Khởi tạo kết nối PDO
    $conn = new PDO($dsn, DB_USER, DB_PASS);

    // CẤU HÌNH BẢO MẬT & TỐI ƯU CHO PDO
    // 1. Kích hoạt chế độ báo lỗi ngoại lệ (Exception) để dễ debug và ẩn lỗi cơ sở dữ liệu thật với người dùng
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. Trả về dữ liệu dạng Mảng kết hợp (Associative Array) cho dễ gọi tên cột ($row['title'])
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // 3. Tắt tính năng Emulate Prepares để tận dụng tối đa bảo mật của Database engine thật
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    die("Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage());
}

// Ensure $conn is always available in global scope
$GLOBALS['conn'] = $conn;

// Helper function to get database connection
function getDatabase(): PDO {
    global $conn;
    return $conn;
}

// Hàm tạo mã đơn hàng gồm 7 ký tự (chữ hoa/thường + số)
function generateOrderCode(): string {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $code = '';
    for ($i = 0; $i < 7; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $code;
}
