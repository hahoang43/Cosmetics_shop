<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lumina Cosmetics</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <header class="main-header">
        <div class="container header-top">
            <div class="logo">
                <a href="../frontend/index.php"><img src="../assets/images/logo.jpg" alt="Lumina Logo"></a>
            </div>
            
            <div class="search-bar">
                <input type="text" name="search" placeholder="Tìm kiếm mỹ phẩm..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button><i class="fa fa-search"></i></button>
            </div>
            <?php session_start(); ?>
            <div class="header-icons">
                <?php if (isset($_SESSION['user'])): ?>
                    <span class="user-info">
                        Xin chào, 
                        <a href="../frontend/profile.php" style="text-decoration: none; color: #D4A373;">
                            <strong><?php echo htmlspecialchars($_SESSION['user']['fullname']); ?></strong>
                        </a> | 
                        <a href="../frontend/logout.php" style="text-decoration: none; color: #555;">Đăng xuất</a>
                    </span>
                <?php else: ?>
                    <a href="../frontend/login.php" title="Tài khoản"><i class="fa-regular fa-user"></i></a>
                <?php endif; ?>
                
                <a href="../frontend/cart.php" title="Giỏ hàng" class="cart-icon">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <?php 
                        // Tính tổng số lượng sản phẩm đang có trong Session
                        $count = 0;
                        if (isset($_SESSION['cart'])) {
                            $count = array_sum($_SESSION['cart']);
                        }
                    ?>
                    <span class="cart-count"><?php echo $count; ?></span>
                </a>
            </div>
        </div>

        <nav class="main-nav">
            <div class="container">
                <ul>
                    <li><a href="../frontend/index.php">Trang chủ</a></li>
                    <li><a href="../frontend/products.php">Cửa hàng</a></li>
                    <li><a href="../frontend/products.php?category=1">Chăm sóc da</a></li>
                    <li><a href="../frontend/products.php?category=2">Trang điểm</a></li>
                    <li><a href="../frontend/contact.php">Liên hệ</a></li>
                </ul>
            </div>
        </nav>
    </header>