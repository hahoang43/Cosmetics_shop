<?php
session_start();
require_once '../config/database.php';

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
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            LUMINA<span>.</span>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="active"><i class="fa-solid fa-gauge"></i> Tổng quan</a></li>
            <li><a href="banners.php"><i class="fa-solid fa-images"></i> Banner</a></li>
            <li><a href="orders.php"><i class="fa-solid fa-cart-flatbed"></i> Đơn hàng</a></li>
            <li><a href="products.php"><i class="fa-solid fa-box-open"></i> Sản phẩm</a></li>
            <li><a href="categories.php"><i class="fa-solid fa-layer-group"></i> Danh mục</a></li>
            <li><a href="users.php"><i class="fa-solid fa-users"></i> Khách hàng</a></li>
            <li><a href="reviews.php"><i class="fa-solid fa-star"></i> Đánh giá</a></li>
            <li><a href="feedbacks.php"><i class="fa-solid fa-message"></i> Phản hồi</a></li>
            <li><a href="qa.php"><i class="fa-solid fa-question"></i> Hỏi đáp</a></li>
            <li><a href="logout.php" style="color: #e74c3c; margin-top: 20px;"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></li>
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