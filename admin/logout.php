<?php
session_start();
// Xóa riêng session của admin, giữ lại session giỏ hàng của khách (nếu quản trị viên đang tự mua hàng)
if (isset($_SESSION['admin'])) {
    unset($_SESSION['admin']);
}
// Chuyển hướng về lại trang đăng nhập
header("Location: login.php");
exit;
?>