<?php 
// 1. Nạp file kết nối database
require_once '../config/database.php';
require_once '../includes/popup_notify.php';
$conn = getDatabase();

// 2. Lấy ID sản phẩm từ URL (vd: ?id=1). Nếu không có thì mặc định là 0
$id_san_pham = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 3. Truy vấn lấy thông tin sản phẩm chung
$stmt = $conn->prepare("SELECT * FROM Product WHERE id = ? AND deleted = 0");
$stmt->execute([$id_san_pham]);
$product = $stmt->fetch();

// Nếu không tìm thấy sản phẩm, chuyển hướng về trang chủ
if (!$product) {
    header("Location: /Cosmetics_shop/frontend/index.php");
    exit;
}

// LẤY DANH SÁCH ẢNH PHỤ (GALLERY) TỪ DATABASE
$stmt_gallery = $conn->prepare("SELECT * FROM Galery WHERE product_id = ?");
$stmt_gallery->execute([$id_san_pham]);
$gallery = $stmt_gallery->fetchAll();

// 4. LẤY DANH SÁCH BIẾN THỂ KÈM ẢNH RIÊNG CỦA TỪNG MÀU/SIZE
$stmt_variants = $conn->prepare("
    SELECT pv.id AS pv_id, v.name AS variant_name, pv.price, pv.old_price, pv.quantity, pv.thumbnail AS variant_img
    FROM Product_Variant pv
    JOIN Variant v ON pv.variant_id = v.id
    WHERE pv.product_id = ? AND pv.quantity > 0
");
$stmt_variants->execute([$id_san_pham]);
$variants = $stmt_variants->fetchAll();

// 5. LẤY DANH SÁCH BÌNH LUẬN (REVIEWS) TỪ DATABASE
$stmt_reviews = $conn->prepare("
    SELECT pr.*, u.fullname 
    FROM Product_Review pr 
    JOIN User u ON pr.user_id = u.id 
    WHERE pr.product_id = ? 
    ORDER BY pr.created_at DESC
");
$stmt_reviews->execute([$id_san_pham]);
$reviews = $stmt_reviews->fetchAll();

// 6. LẤY DANH SÁCH HỎI ĐÁP (Q&A) TỪ DATABASE
$stmt_qa = $conn->prepare("
    SELECT qa.*, u.fullname 
    FROM Product_QA qa 
    LEFT JOIN User u ON qa.user_id = u.id 
    WHERE qa.product_id = ? 
    ORDER BY qa.created_at DESC
");
$stmt_qa->execute([$id_san_pham]);
$list_qa = $stmt_qa->fetchAll();

// 7. LẤY SẢN PHẨM LIÊN QUAN (Cùng danh mục)
$stmt_related = $conn->prepare("
    SELECT * FROM Product 
    WHERE category_id = ? AND id != ? AND deleted = 0 
    LIMIT 4
");
$stmt_related->execute([$product['category_id'], $id_san_pham]);
$related_products = $stmt_related->fetchAll();

// Gọi giao diện Header
include '../includes/header.php'; 
?>

<?php echo popup_assets(); ?>

<style>
    .variant-selection { margin-bottom: 25px; }
    .variant-grid { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 8px; }
    .variant-item { cursor: pointer; }
    .variant-item input[type="radio"] { display: none; }
    .variant-item span { display: inline-block; padding: 8px 18px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; transition: 0.3s; background: #fff; }
    .variant-item input[type="radio"]:checked + span { border-color: #D4A373; background-color: #fdfaf6; color: #D4A373; font-weight: 600; box-shadow: 0 2px 5px rgba(212,163,115,0.15); }
    .variant-item span:hover { border-color: #D4A373; }

    /* CSS hàng ảnh nhỏ Thư viện ảnh */
    .gallery-thumbnails { display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap; }
    .thumb-img { width: 70px; height: 70px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; transition: 0.2s; }
    .thumb-img:hover { border-color: #D4A373; }
    .thumb-img.active { border: 2px solid #D4A373; box-shadow: 0 2px 4px rgba(212,163,115,0.2); }
</style>

<main class="container product-detail-container">
    <div class="product-main">
        <div class="product-images">
            <div class="main-img" style="aspect-ratio: 1; overflow: hidden; margin-bottom: 15px;">
                <img src="<?php echo htmlspecialchars(imageSrc($product['thumbnail'] ?? '', 'products')); ?>" id="big-img" alt="<?php echo htmlspecialchars($product['title']); ?>" style="width: 100%; height: 100%; object-fit: contain; border: 1px solid #eee; border-radius: 8px; display: block;">
            </div>

            <div class="gallery-thumbnails">
                <img src="<?php echo htmlspecialchars(imageSrc($product['thumbnail'] ?? '', 'products')); ?>" class="thumb-img active" alt="main-thumb">
                
                <?php foreach ($variants as $v): ?>
                    <?php if(!empty($v['variant_img'])): ?>
                        <img src="<?= htmlspecialchars(imageSrc($v['variant_img'] ?? '', 'products')) ?>" class="thumb-img" data-variant-id="<?= $v['pv_id'] ?>" alt="variant-thumb">
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php foreach ($gallery as $gal): ?>
                    <img src="<?php echo htmlspecialchars(imageSrc($gal['thumbnail'] ?? '', 'products')); ?>" class="thumb-img" alt="gallery-thumb">
                <?php endforeach; ?>
            </div>
        </div>

        <div class="product-info-order">
            <p class="brand-name"><?php echo htmlspecialchars($product['brand']); ?></p>
            <h1><?php echo htmlspecialchars($product['title']); ?></h1>
            
            <div class="variant-selection">
                <label style="font-weight: bold; color: #333;">Chọn phân loại:</label>
                <div class="variant-grid">
                    <?php if (count($variants) > 0): ?>
                        <?php foreach ($variants as $index => $v): 
                            $v_img = !empty($v['variant_img']) ? $v['variant_img'] : $product['thumbnail'];
                        ?>
                            <label class="variant-item">
                                <input type="radio" name="product_variant" value="<?= $v['pv_id'] ?>" 
                                       data-price="<?= $v['price'] ?>" 
                                       data-old-price="<?= $v['old_price'] ?>"
                                       data-image="<?= htmlspecialchars(imageSrc($v_img, 'products')) ?>"
                                       <?= $index === 0 ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($v['variant_name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span style="color: #e74c3c; font-style: italic;">Sản phẩm này hiện đang tạm hết hàng.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="price-box" style="margin-bottom: 20px;">
                <span class="old-price" style="text-decoration: line-through; color: #999; margin-right: 12px; font-size: 18px; display: none;"></span>
                <span class="current-price" style="color: #D4A373; font-size: 26px; font-weight: bold;"></span>
            </div>
            
            <div class="order-controls">
                <input type="number" id="qty" value="1" min="1" class="qty-input">
                <?php if (count($variants) > 0): ?>
                    <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                        THÊM VÀO GIỎ HÀNG
                    </button>
                    <button class="btn-buy-now" style="background:#2c3e50;color:#fff;border:none;padding:0 24px;border-radius:6px;cursor:pointer;font-weight:700;margin-left:8px;" onclick="buyNow(<?php echo $product['id']; ?>)">
                        MUA NGAY
                    </button>
                <?php else: ?>
                    <button class="btn-add-cart" disabled style="background: #ccc; cursor: not-allowed;">
                        HẾT HÀNG
                    </button>
                <?php endif; ?>
            </div>

            <div class="short-policy" style="margin-top: 25px; color: #555; line-height: 2;">
                <p><i class="fa-solid fa-truck-fast"></i> Giao hàng toàn quốc trong 2-3 ngày</p>
                <p><i class="fa-solid fa-shield-halved"></i> Cam kết chính hãng 100% - Đền 1000% nếu phát hiện hàng giả</p>
            </div>
        </div>
    </div>

    <div class="product-tabs" style="margin-top: 50px;">
        <div class="tabs-header" style="border-bottom: 2px solid #eee;">
            <button class="tab-btn active" style="padding: 10px 20px; font-weight: bold; background: none; border: none; color: #D4A373; border-bottom: 2px solid #D4A373;">
                Mô tả chi tiết
            </button>
        </div>
        <div class="tab-content active" style="padding: 20px 0; line-height: 1.8; color: #444;">
            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
        </div>
        
        <div class="product-qa" style="margin-top: 50px; border-top: 2px solid #eee; padding-top: 30px;">

            <h3 style="font-family: 'Playfair Display', serif; font-size: 24px; margin-bottom: 20px;">Hỏi đáp về sản phẩm</h3>

            <?php if (isset($_SESSION['user'])): ?>
                <form action="/Cosmetics_shop/backend/submit_qa.php" method="POST" class="qa-form" style="margin-bottom: 30px; display: flex; gap: 10px;">
                    <input type="hidden" name="product_id" value="<?= $id_san_pham ?>">
                    <input type="text" name="question" required placeholder="Bạn có thắc mắc gì về sản phẩm này? Đặt câu hỏi ngay..." style="flex: 1; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; outline: none;">
                    <button type="submit" style="background: #333; color: #fff; border: none; padding: 0 25px; border-radius: 5px; font-weight: bold; cursor: pointer;">
                        GỬI CÂU HỎI
                    </button>
                </form>
            <?php else: ?>
                <div style="margin-bottom: 30px;">
                    <a href="/Cosmetics_shop/frontend/login.php" style="display: inline-block; background: #D4A373; color: #fff; padding: 12px 30px; border-radius: 5px; font-weight: bold; text-decoration: none; font-size: 16px;">Đăng nhập/Đăng ký để hỏi đáp về sản phẩm</a>
                </div>
            <?php endif; ?>

            <div class="qa-list" style="display: flex; flex-direction: column; gap: 25px;">
                <?php if (count($list_qa) > 0): ?>
                    <?php foreach($list_qa as $qa): ?>
                        <div class="qa-item" style="border-bottom: 1px dashed #eee; padding-bottom: 20px;">
                            <div class="question" style="display: flex; gap: 10px; margin-bottom: 12px;">
                                <div style="min-width: 28px; height: 28px; background: #eee; color: #555; text-align: center; line-height: 28px; border-radius: 50%; font-weight: bold; font-size: 13px;">Q</div>
                                <div>
                                    <strong style="color: #333;"><?= htmlspecialchars($qa['fullname'] ?? 'Khách vãng lai') ?>:</strong> 
                                    <span style="color: #333;"><?= htmlspecialchars($qa['question']) ?></span>
                                    <div style="font-size: 12px; color: #999; margin-top: 4px;"><?= date('d/m/Y H:i', strtotime($qa['created_at'])) ?></div>
                                </div>
                            </div>
                            
                            <?php if(!empty($qa['answer'])): ?>
                                <div class="answer" style="display: flex; gap: 10px; margin-left: 38px; background: #f9f6f0; padding: 15px; border-radius: 8px; position: relative;">
                                    <div style="min-width: 28px; height: 28px; background: #D4A373; color: #fff; text-align: center; line-height: 28px; border-radius: 50%; font-weight: bold; font-size: 13px; position: absolute; left: -14px; top: -14px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">A</div>
                                    <div>
                                        <strong style="color: #D4A373;">Lumina Shop:</strong> 
                                        <span style="color: #555;"><?= nl2br(htmlspecialchars($qa['answer'])) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #777; font-style: italic;">Chưa có câu hỏi nào cho sản phẩm này.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="product-reviews" class="product-reviews" style="margin-top: 50px; border-top: 2px solid #eee; padding-top: 30px;">
            <h3 style="font-family: 'Playfair Display', serif; font-size: 24px; margin-bottom: 20px;">Đánh giá từ khách hàng</h3>
            
            <?php if (count($reviews) > 0): ?>
                <div class="reviews-list" style="display: flex; flex-direction: column; gap: 20px;">
                    <?php foreach ($reviews as $rv): ?>
                        <div id="review-id-<?= (int)$rv['id'] ?>" class="review-item" style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
                            <div class="review-header" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <strong style="color: #333;"><?= htmlspecialchars($rv['fullname']) ?></strong>
                                <span class="review-date" style="color: #999; font-size: 13px;"><?= date('d/m/Y', strtotime($rv['created_at'])) ?></span>
                            </div>
                            <div class="review-stars" style="color: #ffc107; font-size: 14px; margin-bottom: 10px;">
                                <?php 
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $rv['rating'] ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
                                    }
                                ?>
                            </div>
                            <p style="color: #555; line-height: 1.5; margin-bottom: 10px;"><?= nl2br(htmlspecialchars($rv['comment'])) ?></p>
                            
                            <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                                <?php if (!empty($rv['image'])): ?>
                                    <img src="/Cosmetics_shop/assets/uploads/reviews/<?= htmlspecialchars($rv['image']) ?>" style="max-width: 150px; max-height: 150px; border-radius: 6px; object-fit: cover; border: 1px solid #ddd;">
                                <?php endif; ?>

                                <?php if (!empty($rv['video'])): ?>
                                    <video src="/Cosmetics_shop/assets/uploads/reviews/<?= htmlspecialchars($rv['video']) ?>" controls style="max-width: 240px; max-height: 150px; border-radius: 6px; border: 1px solid #ddd; background: #000;"></video>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #777; font-style: italic;">Chưa có đánh giá nào cho sản phẩm này.</p>
            <?php endif; ?>
        </div>

        <?php if (count($related_products) > 0): ?>
        <div class="related-products" style="margin-top: 60px; margin-bottom: 60px;">
            <h3 style="font-family: 'Playfair Display', serif; font-size: 28px; text-align: center; margin-bottom: 30px;">Có thể bạn sẽ thích</h3>
            <div class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;">
                <?php foreach ($related_products as $item): ?>
                    <div class="product-card" style="border: 1px solid #eee; padding: 15px; border-radius: 8px; text-align: center;">
                        <a href="product_detail.php?id=<?= $item['id'] ?>" style="text-decoration: none; color: inherit;">
                            <img src="<?= htmlspecialchars(imageSrc($item['thumbnail'] ?? '', 'products')) ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy" style="width: 100%; aspect-ratio: 1; object-fit: contain; border-radius: 5px; margin-bottom: 15px;">
                            <h4 style="font-size: 14px; margin-bottom: 10px; height: 40px; overflow: hidden;"><?= htmlspecialchars($item['title']) ?></h4>
                            <div class="price">
                                <span style="color: #D4A373; font-weight: bold; font-size: 16px;"><?= number_format($item['price'], 0, ',', '.') ?>đ</span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
$(document).ready(function() {
    // Khi đổi radio phân loại -> đổi giá, ảnh lớn, đồng bộ active cho ảnh nhỏ
    $('input[name="product_variant"]').change(function() {
        let price = parseInt($(this).data('price'));
        let oldPrice = parseInt($(this).data('old-price'));
        let imgUrl = $(this).data('image');
        let variantId = $(this).val();

        // Cập nhật giá bán hiện tại
        $('.current-price').text(new Intl.NumberFormat('vi-VN').format(price) + 'đ');

        // Cập nhật giá cũ nếu có khuyến mãi
        if (oldPrice > price) {
            $('.old-price').text(new Intl.NumberFormat('vi-VN').format(oldPrice) + 'đ').show();
        } else {
            $('.old-price').hide();
        }

        // Đổi ảnh lớn sang ảnh của biến thể
        if (imgUrl) {
            $('#big-img').attr('src', imgUrl);
        }

        // Đồng bộ active cho ảnh nhỏ đúng biến thể
        $('.thumb-img').removeClass('active');
        let thumb = $(`.thumb-img[data-variant-id="${variantId}"]`);
        if (thumb.length) {
            thumb.addClass('active');
        } else {
            $('.thumb-img[alt="main-thumb"]').addClass('active');
        }
    });

    // Tự động kích hoạt biến thể đầu tiên khi mới tải trang
    $('input[name="product_variant"]:first').trigger('change');

    // Click vào ảnh nhỏ -> đổi ảnh lớn, đồng bộ radio nếu là ảnh biến thể
    $('.thumb-img').click(function() {
        $('.thumb-img').removeClass('active');
        $(this).addClass('active');

        let newSrc = $(this).attr('src');
        $('#big-img').attr('src', newSrc);

        // Nếu là ảnh biến thể thì chọn radio tương ứng
        let variantId = $(this).data('variant-id');
        if (variantId) {
            $(`input[name="product_variant"][value="${variantId}"]`).prop('checked', true).trigger('change');
        }
    });
});

function addToCart(productId) {
    var variantId = $('input[name="product_variant"]:checked').val();
    if (!variantId) {
        Swal.fire({ icon: 'warning', title: 'Thiếu phân loại', text: 'Vui lòng chọn phân loại sản phẩm trước khi thêm vào giỏ.' });
        return;
    }
    
    var quantity = parseInt($('#qty').val());

    $.ajax({
        url: '../backend/cart_process.php',
        type: 'POST',
        data: {
            action: 'add',
            product_id: productId,
            product_variant_id: variantId, 
            qty: quantity
        },
        success: function(response) {
            let res;
            try {
                res = (typeof response === 'object') ? response : JSON.parse(response);
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Lỗi hệ thống', text: 'Đã xảy ra lỗi hệ thống khi xử lý giỏ hàng.' });
                return;
            }

            if (res.status === 'success') {
                $('.cart-count').text(res.total_items);
                Swal.fire({ icon: 'success', title: 'Đã thêm vào giỏ', text: 'Sản phẩm đã được thêm vào giỏ hàng thành công!', timer: 1500, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Không thể thêm', text: 'Có lỗi xảy ra: ' + (res.message ? res.message : 'Vui lòng thử lại.') });
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'Mất kết nối', text: 'Không thể kết nối tới máy chủ!' });
        }
    });
}

function buyNow(productId) {
    var variantId = $('input[name="product_variant"]:checked').val();
    if (!variantId) {
        Swal.fire({ icon: 'warning', title: 'Thiếu phân loại', text: 'Vui lòng chọn phân loại sản phẩm trước khi mua.' });
        return;
    }
    var quantity = parseInt($('#qty').val());

    $.ajax({
        url: '../backend/cart_process.php',
        type: 'POST',
        data: {
            action: 'buy_now',
            product_variant_id: variantId,
            qty: quantity
        },
        success: function(response) {
            let res;
            try {
                res = (typeof response === 'object') ? response : JSON.parse(response);
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Lỗi hệ thống', text: 'Đã xảy ra lỗi hệ thống khi xử lý giỏ hàng.' });
                return;
            }

            if (res.status === 'success') {
                window.location.href = '/Cosmetics_shop/frontend/checkout.php';
            } else {
                Swal.fire({ icon: 'error', title: 'Không thể mua ngay', text: 'Có lỗi xảy ra: ' + (res.message ? res.message : 'Vui lòng thử lại.') });
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'Mất kết nối', text: 'Không thể kết nối tới máy chủ!' });
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>