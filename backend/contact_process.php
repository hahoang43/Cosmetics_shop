<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Nhận dữ liệu từ form
    $fullname = trim($_POST['name']); // Form chỉ có 1 ô "Họ tên"
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // 2. Xử lý tách Họ và Tên (Vì DB chia làm 2 cột)
    $name_parts = explode(" ", $fullname);
    if (count($name_parts) > 1) {
        $firstname = array_pop($name_parts); // Chữ cuối cùng là Tên
        $lastname = implode(" ", $name_parts); // Các chữ còn lại là Họ & Tên đệm
    } else {
        $firstname = $fullname;
        $lastname = "";
    }

    try {
        // 3. Chuẩn bị câu lệnh SQL theo đúng bảng FeedBack trong SQL của bạn
        // Cột: firstname, lastname, email, subject_name, note
        $sql = "INSERT INTO FeedBack (firstname, lastname, email, subject_name, note, status) 
                VALUES (?, ?, ?, ?, ?, 0)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $firstname, 
            $lastname, 
            $email, 
            $subject, 
            $message
        ]);

        // 4. Thông báo thành công
        echo "<script>
            alert('Cảm ơn " . htmlspecialchars($firstname) . "! Phản hồi của bạn đã được gửi thành công.');
            window.location.href = '../frontend/contact.php';
        </script>";

    } catch (PDOException $e) {
        // Báo lỗi nếu có vấn đề về Database
        echo "<script>
            alert('Lỗi: " . addslashes($e->getMessage()) . "');
            window.history.back();
        </script>";
    }
}
?>