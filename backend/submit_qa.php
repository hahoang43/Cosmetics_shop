<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $question = trim($_POST['question']);
    
    // Nếu khách đã đăng nhập thì lấy ID, chưa đăng nhập thì để NULL
    $user_id = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;

    if (!empty($question) && $product_id > 0) {
        try {
            $stmt = $conn->prepare("INSERT INTO Product_QA (product_id, user_id, question) VALUES (?, ?, ?)");
            $stmt->execute([$product_id, $user_id, $question]);
            
            // Có thể dùng SweetAlert flash_success ở đây nếu bạn muốn, tạm thời mình dùng hàm alert() JS cho gọn
            echo "<script>alert('Gửi câu hỏi thành công! Shop sẽ phản hồi bạn sớm nhất.'); window.location.href='../frontend/product_detail.php?id=$product_id';</script>";
            exit;
        } catch (PDOException $e) {
            echo "<script>alert('Lỗi hệ thống: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Vui lòng nhập nội dung câu hỏi!'); window.history.back();</script>";
    }
} else {
    header("Location: ../frontend/index.php");
    exit;
}
?>