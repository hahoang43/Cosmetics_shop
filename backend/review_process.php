<?php
session_start();
require_once '../config/database.php';
$conn = getDatabase();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Kiểm tra đăng nhập (Chỉ User mới được đánh giá)
    if (!isset($_SESSION['user'])) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>Swal.fire({ icon: 'warning', title: 'Cần đăng nhập', text: 'Bạn cần đăng nhập để gửi đánh giá!' }).then(function() { window.history.back(); });</script>";
        exit;
    }

    // 2. Nhận dữ liệu từ Form
    $user_id = $_SESSION['user']['id'];
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5; // Mặc định 5 sao
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    // 3. Xử lý lưu vào Database
    if ($product_id > 0 && !empty($comment)) {
        try {
            $sql = "INSERT INTO Product_Review (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$product_id, $user_id, $rating, $comment]);

            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>Swal.fire({ icon: 'success', title: 'Thành công', text: 'Cảm ơn bạn đã đánh giá sản phẩm!' }).then(function() { window.location.href = '../frontend/product_detail.php?id=$product_id'; });</script>";
        } catch (PDOException $e) {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>Swal.fire({ icon: 'error', title: 'Lỗi Database', text: '" . addslashes($e->getMessage()) . "' }).then(function() { window.history.back(); });</script>";
        }
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>Swal.fire({ icon: 'warning', title: 'Thiếu dữ liệu', text: 'Vui lòng nhập đầy đủ nội dung đánh giá!' }).then(function() { window.history.back(); });</script>";
    }
}
?>