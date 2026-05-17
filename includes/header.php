<?php
// BẮT BUỘC: session_start() phải nằm ở dòng 1, cột 1, không có dấu cách hay enter phía trước
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>osmetics</title> 
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <header class="main-header">
        <div class="container header-top">
            <div class="logo">
                <a href="../frontend/index.php"><img src="../assets/images/logo.jpg"></a>
            </div>
            
            <div class="search-box" style="position: relative; width: 100%; max-width: 400px;">
                <form action="search.php" method="GET" style="display: flex; width: 100%;">
                    <input type="text" name="keyword" id="live-search-input" placeholder="Tìm kiếm sản phẩm..." autocomplete="off" required style="width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 20px 0 0 20px; outline: none;">
                    <button type="submit" style="padding: 10px 20px; background: #D4A373; color: white; border: none; border-radius: 0 20px 20px 0; cursor: pointer;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>
                
                <ul id="search-results-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ddd; border-radius: 5px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); list-style: none; padding: 0; margin-top: 5px; z-index: 9999; max-height: 350px; overflow-y: auto;">
                </ul>
            </div>

            <script>
            $(document).ready(function() {
                let searchTimer;
                
                $('#live-search-input').on('input', function() {
                    clearTimeout(searchTimer);
                    let keyword = $(this).val().trim();
                    let dropdown = $('#search-results-dropdown');
                    
                    if (keyword.length >= 2) {
                        searchTimer = setTimeout(function() {
                            $.ajax({
                                // Mình đã trỏ URL về search_live.php như hướng dẫn tạo file backend nãy nhé
                                url: '../backend/search.php', 
                                type: 'GET',
                                data: { keyword: keyword },
                                dataType: 'json',
                                success: function(data) {
                                    dropdown.empty();
                                    if (data.length > 0) {
                                        $.each(data, function(index, product) {
                                            let formattedPrice = new Intl.NumberFormat('vi-VN').format(product.price) + 'đ';
                                            
                                            dropdown.append(`
                                                <li style="border-bottom: 1px solid #f0f0f0;">
                                                    <a href="product_detail.php?id=${product.id}" style="display: flex; align-items: center; padding: 10px; text-decoration: none; color: #333; transition: background 0.2s;">
                                                        <img src="../assets/uploads/products/${product.thumbnail}" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; margin-right: 10px; border: 1px solid #eee;">
                                                        <div>
                                                            <div style="font-size: 14px; font-weight: 500; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;">${product.title}</div>
                                                            <div style="color: #D4A373; font-weight: bold; font-size: 13px; margin-top: 3px;">${formattedPrice}</div>
                                                        </div>
                                                    </a>
                                                </li>
                                            `);
                                        });
                                        dropdown.append(`
                                            <li>
                                                <a href="search.php?keyword=${encodeURIComponent(keyword)}" style="display: block; text-align: center; padding: 10px; font-size: 13px; color: #D4A373; font-weight: bold; background: #fdfaf6; text-decoration: none;">
                                                    Xem tất cả kết quả cho "${keyword}" <i class="fa-solid fa-arrow-right"></i>
                                                </a>
                                            </li>
                                        `);
                                        dropdown.slideDown('fast');
                                    } else {
                                        dropdown.html('<li style="padding: 15px; text-align: center; color: #777; font-size: 14px;">Không tìm thấy sản phẩm nào!</li>');
                                        dropdown.slideDown('fast');
                                    }
                                }
                            });
                        }, 300);
                    } else {
                        dropdown.slideUp('fast');
                    }
                });

                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.search-box').length) {
                        $('#search-results-dropdown').hide();
                    }
                });
                
                $(document).on('mouseenter', '#search-results-dropdown li a', function() {
                    $(this).css('background', '#f9f9f9');
                }).on('mouseleave', '#search-results-dropdown li a', function() {
                    $(this).css('background', 'transparent');
                });
            });
            </script>
            
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