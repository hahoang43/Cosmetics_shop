<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once __DIR__ . '/image_helper.php';

// Determine current admin page filename for active menu highlighting
$currentAdminPage = basename($_SERVER['PHP_SELF']);

// Tạm thời comment đoạn kiểm tra Admin lại để bạn test giao diện trước
// if (!isset($_SESSION['admin'])) {
//     header("Location: login.php");
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lumina - Quản trị hệ thống</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.showAdminAlert = function(options) {
            if (window.Swal) {
                return Swal.fire(options);
            }

            const fallbackMessage = options.text || options.title || 'Thông báo';
            window.alert(fallbackMessage);
            return Promise.resolve({ isConfirmed: true });
        };

        window.showAdminRedirectAlert = function(options, redirectUrl) {
            return window.showAdminAlert(options).then(function() {
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                }
            });
        };

        window.confirmAdminAction = function(message, onConfirm) {
            if (window.Swal) {
                return Swal.fire({
                    icon: 'question',
                    title: 'Xác nhận',
                    text: message,
                    showCancelButton: true,
                    confirmButtonText: 'Đồng ý',
                    cancelButtonText: 'Hủy'
                }).then(function(result) {
                    if (result.isConfirmed && typeof onConfirm === 'function') {
                        onConfirm();
                    }
                });
            }

            if (window.confirm(message) && typeof onConfirm === 'function') {
                onConfirm();
            }
        };
    </script>
    <link rel="stylesheet" href="/Cosmetics_shop/assets/css/admin.css">
</head>
<body>

<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            LUMINA<span>.</span>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="<?= $currentAdminPage === 'index.php' ? 'active' : '' ?>"><i class="fa-solid fa-gauge"></i> Tổng quan</a></li>
            <li><a href="banners.php" class="<?= preg_match('/banner/i', $currentAdminPage) ? 'active' : '' ?>"><i class="fa-solid fa-images"></i> Banner</a></li>
            <li><a href="orders.php" class="<?= preg_match('/order/i', $currentAdminPage) ? 'active' : '' ?>"><i class="fa-solid fa-cart-flatbed"></i> Đơn hàng</a></li>
            <li><a href="products.php" class="<?= preg_match('/product/i', $currentAdminPage) ? 'active' : '' ?>"><i class="fa-solid fa-box-open"></i> Sản phẩm</a></li>
            <li><a href="trash_products.php" class="<?= $currentAdminPage === 'trash_products.php' ? 'active' : '' ?>"><i class="fa-solid fa-trash-can"></i> Thùng rác</a></li>
            <li><a href="categories.php" class="<?= preg_match('/category/i', $currentAdminPage) ? 'active' : '' ?>"><i class="fa-solid fa-layer-group"></i> Danh mục</a></li>
            <li><a href="users.php" class="<?= preg_match('/user/i', $currentAdminPage) ? 'active' : '' ?>"><i class="fa-solid fa-users"></i> Khách hàng</a></li>
            <li><a href="reviews.php" class="<?= preg_match('/review/i', $currentAdminPage) ? 'active' : '' ?>"><i class="fa-solid fa-star"></i> Đánh giá</a></li>
            <li><a href="feedbacks.php" class="<?= preg_match('/feedback/i', $currentAdminPage) ? 'active' : '' ?>"><i class="fa-solid fa-message"></i> Phản hồi</a></li>
            <li><a href="qa.php" class="<?= preg_match('/\bqa\b/i', $currentAdminPage) ? 'active' : '' ?>"><i class="fa-solid fa-question"></i> Hỏi đáp</a></li>
            <li><a href="settings.php" class="<?= $currentAdminPage === 'settings.php' ? 'active' : '' ?>"><i class="fa-solid fa-gear"></i> Cài đặt</a></li>
            <li><a href="logout.php" style="color: #e74c3c; margin-top: 20px;" class="<?= $currentAdminPage === 'logout.php' ? 'active' : '' ?>"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></li>
        </ul>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <div class="admin-profile">
                <i class="fa-solid fa-user-shield"></i>
                Xin chào, Quản trị viên
            </div>
        </header>
        
        <div class="admin-content">
