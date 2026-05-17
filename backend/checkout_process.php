<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || empty($_SESSION['cart'])) {
    echo json_encode(['status' => 'error', 'message' => 'Giỏ hàng trống hoặc chưa đăng nhập!']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$fullname = $_POST['fullname'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$address = $_POST['address'] ?? '';
$note = $_POST['note'] ?? '';
$payment_method = $_POST['payment_method'] ?? 'cod';
$total_price = (int)$_POST['total_price'];

try {
    // Bắt đầu Transaction (Đảm bảo an toàn dữ liệu: Nếu lỗi giữa chừng thì hủy hết lệnh)
    $conn->beginTransaction();

    // 1. Lưu thông tin chung vào bảng Orders
    $stmt = $conn->prepare("INSERT INTO Orders (user_id, fullname, email, phone_number, address, note, payment_method, total_money, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
    $stmt->execute([$user_id, $fullname, $email, $phone, $address, $note, $payment_method, $total_price]);
    $order_id = $conn->lastInsertId();

    // 2. Lưu chi tiết đơn hàng và Trừ tồn kho
    // $pv_id chính là product_variant_id (ID của biến thể)
    foreach ($_SESSION['cart'] as $pv_id => $qty) {
        
        // Lấy thông tin biến thể để chèn vào Order_Details
        $stmt_variant = $conn->prepare("SELECT product_id, price, quantity FROM Product_Variant WHERE id = ?");
        $stmt_variant->execute([$pv_id]);
        $variant = $stmt_variant->fetch();

        if (!$variant) {
            throw new Exception("Sản phẩm không tồn tại!");
        }

        // Kiểm tra tồn kho lần cuối trước khi chốt đơn
        if ($variant['quantity'] < $qty) {
            throw new Exception("Sản phẩm có ID phân loại $pv_id không đủ số lượng trong kho!");
        }

        $price = $variant['price'];
        $subtotal = $price * $qty;
        $product_id = $variant['product_id'];

        // Chèn vào Order_Details (CÓ THÊM product_variant_id)
        $stmt_detail = $conn->prepare("INSERT INTO Order_Details (order_id, product_id, product_variant_id, price, num, total_money) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_detail->execute([$order_id, $product_id, $pv_id, $price, $qty, $subtotal]);

        // TRỪ KHO trong bảng Product_Variant
        $stmt_update_stock = $conn->prepare("UPDATE Product_Variant SET quantity = quantity - ? WHERE id = ?");
        $stmt_update_stock->execute([$qty, $pv_id]);
    }

    // Nếu mọi thứ trơn tru, Commit lưu vào CSDL
    $conn->commit();
    
    // Xóa giỏ hàng sau khi đặt thành công
    unset($_SESSION['cart']);

    echo json_encode(['status' => 'success', 'order_id' => $order_id]);

} catch (Exception $e) {
    // Nếu có lỗi (ví dụ hết kho), Rollback hủy bỏ toàn bộ lệnh vừa chạy
    $conn->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>