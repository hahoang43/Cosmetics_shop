<?php
session_start();
require_once '../config/database.php';

// Báo cho trình duyệt biết đây là dữ liệu JSON
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// Khởi tạo giỏ hàng nếu chưa có
// Cấu trúc $_SESSION['cart'] bây giờ sẽ là: [ product_variant_id => quantity ]
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 1. XỬ LÝ THÊM VÀO GIỎ HÀNG (Từ trang Chi tiết sản phẩm)
if ($action == 'add') {
    $variant_id = isset($_POST['product_variant_id']) ? (int)$_POST['product_variant_id'] : 0;
    $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;

    if ($variant_id <= 0 || $qty <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ!']);
        exit;
    }

    // Kiểm tra xem phân loại này có tồn tại và tồn kho còn bao nhiêu
    $stmt = $conn->prepare("SELECT quantity FROM Product_Variant WHERE id = ?");
    $stmt->execute([$variant_id]);
    $variant = $stmt->fetch();

    if (!$variant) {
        echo json_encode(['status' => 'error', 'message' => 'Phân loại sản phẩm không tồn tại!']);
        exit;
    }

    // Tính toán số lượng dự kiến sau khi thêm vào giỏ
    $current_qty_in_cart = isset($_SESSION['cart'][$variant_id]) ? $_SESSION['cart'][$variant_id] : 0;
    $new_qty = $current_qty_in_cart + $qty;

    // Chặn nếu khách đặt lố số lượng đang có trong kho
    if ($new_qty > $variant['quantity']) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Vượt quá số lượng tồn kho! (Kho chỉ còn ' . $variant['quantity'] . ' sản phẩm)'
        ]);
        exit;
    }

    // Lưu vào session
    $_SESSION['cart'][$variant_id] = $new_qty;
    
    // Đếm tổng số lượng sản phẩm để update icon giỏ hàng
    $total_items = array_sum($_SESSION['cart']);
    
    echo json_encode(['status' => 'success', 'total_items' => $total_items]);
    exit;
}

// 2. XỬ LÝ CẬP NHẬT TĂNG/GIẢM SỐ LƯỢNG (Từ trang Giỏ hàng & Thanh toán)
if ($action == 'update') {
    // Lưu ý: Ở trang giỏ hàng cũ, biến gửi lên tên là 'id', nhưng giá trị thực chất 
    // giờ đây chính là product_variant_id
    $variant_id = isset($_POST['id']) ? (int)$_POST['id'] : 0; 
    $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 0;

    if ($variant_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Sản phẩm không hợp lệ!']);
        exit;
    }

    if ($qty > 0) {
        // Vẫn phải kiểm tra tồn kho khi khách bấm dấu "+"
        $stmt = $conn->prepare("SELECT quantity FROM Product_Variant WHERE id = ?");
        $stmt->execute([$variant_id]);
        $variant = $stmt->fetch();

        if ($variant && $qty > $variant['quantity']) {
            echo json_encode(['status' => 'error', 'message' => 'Không đủ số lượng trong kho!']);
            exit;
        }

        $_SESSION['cart'][$variant_id] = $qty;
    } else {
        // Nếu số lượng <= 0 (khách bấm dấu "-" về 0) thì xóa luôn khỏi giỏ
        unset($_SESSION['cart'][$variant_id]);
    }
    
    echo json_encode(['status' => 'success']);
    exit;
}

// 3. XỬ LÝ XÓA HẲN SẢN PHẨM KHỎI GIỎ
if ($action == 'remove') {
    $variant_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if (isset($_SESSION['cart'][$variant_id])) {
        unset($_SESSION['cart'][$variant_id]);
    }
    
    echo json_encode(['status' => 'success']);
    exit;
}

// Nếu gửi action linh tinh
echo json_encode(['status' => 'error', 'message' => 'Hành động không hợp lệ!']);
exit;