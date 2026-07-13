<?php
session_start();
require_once '../config/database.php';
$conn = getDatabase();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $question = trim($_POST['question']);
    $redirectUrl = '../frontend/product_detail.php?id=' . $product_id;
    
    // Nếu khách đã đăng nhập thì lấy ID, chưa đăng nhập thì để NULL
    $user_id = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;

    if (!empty($question) && $product_id > 0) {
        try {
            $stmt = $conn->prepare("INSERT INTO Product_QA (product_id, user_id, question) VALUES (?, ?, ?)");
            $stmt->execute([$product_id, $user_id, $question]);

            $_SESSION['flash_success'] = 'Gửi câu hỏi thành công! Shop sẽ phản hồi bạn sớm nhất.';
            header('Location: ' . $redirectUrl);
            exit;
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = 'Lỗi hệ thống: ' . $e->getMessage();
            header('Location: ' . $redirectUrl);
            exit;
        }
    } else {
        $_SESSION['flash_error'] = 'Vui lòng nhập nội dung câu hỏi!';
        header('Location: ' . $redirectUrl);
        exit;
    }
} else {
    header("Location: ../frontend/index.php");
    exit;
}
?>