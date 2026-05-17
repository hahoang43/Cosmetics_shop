<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['user']['id'];

try {
    // 1. Kiểm tra xem đơn này có đúng của user này và đang ở trạng thái "Chờ xác nhận" (0) không
    $stmt = $conn->prepare("SELECT status FROM Orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if ($order && $order['status'] == 0) {
        $conn->beginTransaction();

        // 2. Chuyển trạng thái đơn thành Đã hủy (3)
        $stmt_cancel = $conn->prepare("UPDATE Orders SET status = 3 WHERE id = ?");
        $stmt_cancel->execute([$order_id]);

        // 3. Lấy danh sách sản phẩm trong đơn để HOÀN TRẢ TỒN KHO
        $stmt_details = $conn->prepare("SELECT product_variant_id, num FROM Order_Details WHERE order_id = ?");
        $stmt_details->execute([$order_id]);
        $items = $stmt_details->fetchAll();

        foreach ($items as $item) {
            // Cộng trả lại số lượng vào bảng Product_Variant
            $stmt_restore = $conn->prepare("UPDATE Product_Variant SET quantity = quantity + ? WHERE id = ?");
            $stmt_restore->execute([$item['num'], $item['product_variant_id']]);
        }

        $conn->commit();
        $_SESSION['flash_success'] = "Hủy đơn hàng và hoàn trả kho thành công!";
    } else {
        $_SESSION['flash_error'] = "Không thể hủy đơn hàng này (đơn đã được xử lý hoặc không tồn tại).";
    }
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $_SESSION['flash_error'] = "Lỗi hệ thống khi hủy đơn: " . $e->getMessage();
}

header("Location: order_history.php");
exit;
?>