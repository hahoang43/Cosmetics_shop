<?php
session_start();
require_once '../config/database.php';
require_once '../config/shipping.php';

header('Content-Type: application/json');

$conn = getDatabase();
if (!($conn instanceof PDO)) {
    echo json_encode(['status' => 'error', 'message' => 'Kết nối cơ sở dữ liệu không khả dụng.']);
    exit;
}

class CheckoutProcessException extends Exception {}

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Giỏ hàng trống hoặc chưa đăng nhập!']);
    exit;
}

$checkout_cart = !empty($_SESSION['checkout_cart']) ? $_SESSION['checkout_cart'] : [];
$source_cart = !empty($checkout_cart) ? $checkout_cart : ($_SESSION['cart'] ?? []);

if (empty($source_cart)) {
    echo json_encode(['status' => 'error', 'message' => 'Giỏ hàng trống hoặc chưa đăng nhập!']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$user_id = (int)$user_id;
$fullname = $_POST['fullname'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$address = $_POST['address'] ?? '';
$note = $_POST['note'] ?? '';
$payment_method = $_POST['payment_method'] ?? 'cod';
$customerLat = isset($_POST['customer_lat']) ? (float) $_POST['customer_lat'] : null;
$customerLng = isset($_POST['customer_lng']) ? (float) $_POST['customer_lng'] : null;

if ($fullname === '' || $phone === '' || $address === '') {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ thông tin giao hàng.']);
    exit;
}

if ($customerLat === null || $customerLng === null || $customerLat < -90 || $customerLat > 90 || $customerLng < -180 || $customerLng > 180) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng chọn vị trí giao hàng hợp lệ trên bản đồ.']);
    exit;
}

if ($user_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Tài khoản đăng nhập không hợp lệ. Vui lòng đăng nhập lại.']);
    exit;
}

try {
    // Bắt đầu Transaction (Đảm bảo an toàn dữ liệu: Nếu lỗi giữa chừng thì hủy hết lệnh)
    $conn->beginTransaction();

    // Kiểm tra xem cột order_code có tồn tại không
    $stmt_check = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Orders' AND COLUMN_NAME='order_code' AND TABLE_SCHEMA=?");
    $stmt_check->execute(['db_mypham']);
    $has_order_code = $stmt_check->rowCount() > 0;

    // Nếu cột chưa tồn tại, tạo nó
    if (!$has_order_code) {
        try {
            $conn->exec("ALTER TABLE Orders ADD COLUMN order_code VARCHAR(7) DEFAULT NULL UNIQUE");
            $has_order_code = true;
        } catch(Exception $e) {
            // Cột có thể đã được tạo bởi request khác
            $has_order_code = false;
        }
    }

    $subtotalPrice = 0;
    $distanceKm = haversine_distance_km(SHOP_LAT, SHOP_LNG, $customerLat, $customerLng);
    $shippingFee = calculate_shipping_fee($distanceKm);

    // 1. Lưu thông tin chung vào bảng Orders
    if ($has_order_code) {
        $orderCode = generateOrderCode();
        $stmt = $conn->prepare("INSERT INTO Orders (user_id, fullname, email, phone_number, address, note, payment_method, total_money, status, order_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?)");
        $stmt->execute([$user_id, $fullname, $email, $phone, $address, $note, $payment_method, 0, $orderCode]);
    } else {
        $stmt = $conn->prepare("INSERT INTO Orders (user_id, fullname, email, phone_number, address, note, payment_method, total_money, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$user_id, $fullname, $email, $phone, $address, $note, $payment_method, 0]);
    }
    $order_id = $conn->lastInsertId();

    // 2. Lưu chi tiết đơn hàng và Trừ tồn kho
    // $pv_id chính là product_variant_id (ID của biến thể)
    foreach ($source_cart as $pv_id => $qty) {
        
        // Lấy thông tin biến thể để chèn vào Order_Details
        $stmt_variant = $conn->prepare("SELECT product_id, price, quantity FROM Product_Variant WHERE id = ?");
        $stmt_variant->execute([$pv_id]);
        $variant = $stmt_variant->fetch();

        if (!$variant) {
            throw new CheckoutProcessException("Sản phẩm không tồn tại!");
        }

        // Kiểm tra tồn kho lần cuối trước khi chốt đơn
        if ($variant['quantity'] < $qty) {
            throw new CheckoutProcessException("Sản phẩm có ID phân loại $pv_id không đủ số lượng trong kho!");
        }

        $price = $variant['price'];
        $subtotal = $price * $qty;
        $product_id = $variant['product_id'];
        $subtotalPrice += $subtotal;

        // Chèn vào Order_Details (CÓ THÊM product_variant_id)
        $stmt_detail = $conn->prepare("INSERT INTO Order_Details (order_id, product_id, product_variant_id, price, num, total_money) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_detail->execute([$order_id, $product_id, $pv_id, $price, $qty, $subtotal]);

        // TRỪ KHO trong bảng Product_Variant
        $stmt_update_stock = $conn->prepare("UPDATE Product_Variant SET quantity = quantity - ? WHERE id = ?");
        $stmt_update_stock->execute([$qty, $pv_id]);
    }

    $totalPrice = $subtotalPrice + $shippingFee;
    $shippingMeta = sprintf('[Ship %.2f km | Phi %sđ]', $distanceKm, number_format($shippingFee, 0, ',', '.'));
    $orderNote = trim($note) !== '' ? trim($note) . ' ' . $shippingMeta : $shippingMeta;

    // 3. Cập nhật tổng tiền cuối cùng và ghi chú ship
    $stmt_order_update = $conn->prepare("UPDATE Orders SET note = ?, total_money = ? WHERE id = ?");
    $stmt_order_update->execute([$orderNote, $totalPrice, $order_id]);

    // Lưu địa chỉ và số điện thoại cho các lần mua tiếp theo
    $stmt_user_address = $conn->prepare("UPDATE User SET address = ? WHERE id = ?");
    $stmt_user_address->execute([$address, $user_id]);
    $stmt_user_phone = $conn->prepare("UPDATE User SET phone_number = ? WHERE id = ?");
    $stmt_user_phone->execute([$phone, $user_id]);
    $_SESSION['user']['address'] = $address;
    $_SESSION['user']['phone_number'] = $phone;

    // Nếu mọi thứ trơn tru, Commit lưu vào CSDL
    $conn->commit();
    
    // Xóa giỏ hàng sau khi đặt thành công
    if (!empty($checkout_cart)) {
        foreach ($checkout_cart as $pv_id => $_qty) {
            unset($_SESSION['cart'][$pv_id]);
        }
        $_SESSION['checkout_cart'] = [];
    } else {
        unset($_SESSION['cart']);
    }

    // Lấy order_code từ database
    $stmt_get_code = $conn->prepare("SELECT order_code FROM Orders WHERE id = ?");
    $stmt_get_code->execute([$order_id]);
    $order_code = $stmt_get_code->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'order_id' => $order_id,
        'order_code' => $order_code,
        'distance_km' => round($distanceKm, 2),
        'shipping_fee' => $shippingFee,
        'total_price' => $totalPrice,
    ]);

} catch (Exception $e) {
    // Nếu có lỗi (ví dụ hết kho), Rollback hủy bỏ toàn bộ lệnh vừa chạy
    $conn->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
