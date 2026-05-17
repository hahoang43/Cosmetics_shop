<?php 
require_once '../config/database.php';
include '../includes/header.php'; 

// === 1. CHẶN KHÁCH VÃNG LAI YÊU CẦU ĐĂNG NHẬP ===
if (!isset($_SESSION['user'])) {
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<style>body { background: #fdfaf6; } .main-footer { display: none; }</style>"; 
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Cần đăng nhập để thanh toán!',
                text: 'Vui lòng đăng nhập nếu bạn đã có tài khoản, hoặc tạo tài khoản mới để Lumina tiện chăm sóc và theo dõi đơn hàng cho bạn nhé.',
                icon: 'info',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: '#D4A373',
                denyButtonColor: '#2c3e50',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: 'Đăng nhập',
                denyButtonText: 'Đăng ký mới',
                cancelButtonText: 'Quay lại giỏ hàng',
                allowOutsideClick: false, 
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                } else if (result.isDenied) {
                    window.location.href = 'register.php';
                } else {
                    window.location.href = 'cart.php';
                }
            });
        });
    </script>";
    exit; 
}

// 2. Kiểm tra giỏ hàng
if (empty($_SESSION['cart'])) {
    echo "<script>alert('Giỏ hàng của bạn đang trống!'); window.location.href='index.php';</script>";
    exit;
}

// 3. Tính toán dữ liệu giỏ hàng (CẬP NHẬT THEO BẢNG BIẾN THỂ)
$total_price = 0;
$cart_items = [];
$ids = array_keys($_SESSION['cart']);
$placeholders = str_repeat('?,', count($ids) - 1) . '?';

$sql = "
    SELECT pv.id AS pv_id, p.title, v.name AS variant_name, pv.price 
    FROM Product_Variant pv 
    JOIN Product p ON pv.product_id = p.id 
    JOIN Variant v ON pv.variant_id = v.id 
    WHERE pv.id IN ($placeholders)
";
$stmt = $conn->prepare($sql);
$stmt->execute($ids);
$products = $stmt->fetchAll();

foreach ($products as $p) {
    $qty = $_SESSION['cart'][$p['pv_id']];
    $subtotal = $p['price'] * $qty;
    $total_price += $subtotal;
    $cart_items[] = [
        'pv_id' => $p['pv_id'],
        'title' => $p['title'],
        'variant_name' => $p['variant_name'],
        'price' => $p['price'],
        'qty' => $qty,
        'subtotal' => $subtotal
    ];
}
?>

<main class="container checkout-container" style="margin-top: 40px; margin-bottom: 60px;">
    <h2 style="font-family: 'Playfair Display', serif; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 30px;">
        Thanh toán đơn hàng
    </h2>

    <form class="checkout-form" id="checkout-form" style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 40px; align-items: start;">
        
        <div class="billing-details">
    <h3 style="margin-bottom: 20px; font-size: 20px;">Thông tin giao hàng</h3>
    
    <input type="text" id="fullname" placeholder="Họ và tên" required value="<?= htmlspecialchars($_SESSION['user']['fullname'] ?? '') ?>" style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px;">
    
    <input type="tel" id="phone" placeholder="Số điện thoại" required value="<?= htmlspecialchars($_SESSION['user']['phone_number'] ?? '') ?>" style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px;">
    
    <input type="email" id="email" placeholder="Email nhận hóa đơn" value="<?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?>" style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px;">

    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 15px;">
        <select id="province" required style="padding: 12px; border: 1px solid #ddd; border-radius: 5px;"><option value="">Chọn Tỉnh/Thành</option></select>
        <select id="district" required style="padding: 12px; border: 1px solid #ddd; border-radius: 5px;"><option value="">Chọn Quận/Huyện</option></select>
        <select id="ward" required style="padding: 12px; border: 1px solid #ddd; border-radius: 5px;"><option value="">Chọn Phường/Xã</option></select>
    </div>

    <input type="text" id="street" placeholder="Số nhà, tên đường (Hoặc địa chỉ chi tiết)" required style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px;" value="<?= htmlspecialchars($_SESSION['user']['address'] ?? '') ?>">
    
    <textarea id="note" placeholder="Ghi chú đơn hàng (ví dụ: giao giờ hành chính)" style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px; height: 60px;"></textarea>

            <h3 class="checkout-title">Phương thức thanh toán</h3>
            <div class="payment-wrapper">
                <label class="payment-item">
                    <input type="radio" name="payment_method" value="cod" checked>
                    <div class="payment-content">
                        <div class="payment-icon"><i class="fa-solid fa-truck-fast"></i></div>
                        <div class="payment-text">
                            <strong>Thanh toán khi nhận hàng (COD)</strong>
                            <span>Bạn sẽ thanh toán bằng tiền mặt khi shipper giao hàng.</span>
                        </div>
                    </div>
                </label>

                <label class="payment-item">
                    <input type="radio" name="payment_method" value="banking">
                    <div class="payment-content">
                        <div class="payment-icon"><i class="fa-solid fa-building-columns"></i></div>
                        <div class="payment-text">
                            <strong>Chuyển khoản ngân hàng</strong>
                            <span>Chuyển khoản qua QR Code hoặc STK để được xử lý nhanh hơn.</span>
                        </div>
                    </div>
                </label>

                <div id="bank-info" class="bank-details" style="display: none;">
                    <div class="bank-info-header"><i class="fa-solid fa-circle-info"></i> THÔNG TIN CHUYỂN KHOẢN</div>
                    <div class="bank-info-body">
                        <p><strong>Chủ tài khoản:</strong> VÕ HỒ HOÀNG HÀ</p>
                        <p><strong>Số tài khoản:</strong> 123456789 - MB Bank</p>
                        <p><strong>Nội dung:</strong> LUMINA [Số điện thoại của bạn]</p>
                    </div>
                </div>
            </div> 
        </div> 

        <div class="order-review">
            <h3>Đơn hàng của bạn</h3>
            
            <div class="order-items-list">
                <?php foreach ($cart_items as $item): ?>
                    <div class="review-item" id="item-<?= $item['pv_id'] ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px dashed #eee;">
                        <div class="item-main">
                            <span class="item-title" style="display: block; font-weight: 500; color: #333; margin-bottom: 5px;">
                                <?= htmlspecialchars($item['title']); ?>
                            </span>
                            <span style="font-size: 13px; color: #777; background: #f9f9f9; padding: 2px 6px; border-radius: 4px; border: 1px solid #eee;">
                                Phân loại: <?= htmlspecialchars($item['variant_name']); ?>
                            </span>
                            <div class="item-actions" style="margin-top: 8px;">
                                <div class="quantity-selector">
                                    <button type="button" class="qty-btn" onclick="changeQty(<?= $item['pv_id'] ?>, -1)">-</button>
                                    <input type="text" id="qty-<?= $item['pv_id'] ?>" class="qty-input" value="<?= $item['qty'] ?>" readonly>
                                    <button type="button" class="qty-btn" onclick="changeQty(<?= $item['pv_id'] ?>, 1)">+</button>
                                </div>
                            </div>
                        </div>
                        <span class="item-price" id="price-<?= $item['pv_id'] ?>" style="font-weight: bold; color: #D4A373;">
                            <?= number_format($item['subtotal'], 0, ',', '.'); ?>đ
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="total-row" style="display: flex; justify-content: space-between; margin-top: 20px; font-size: 18px;">
                <span>Tổng cộng:</span>
                <span class="total-price" style="color: #e74c3c; font-weight: bold; font-size: 24px;">
                    <?= number_format($total_price, 0, ',', '.'); ?>đ
                </span>
            </div>

            <button type="submit" class="btn-order">XÁC NHẬN ĐẶT HÀNG</button>
        </div>
    </form>
</main>

<script>
$(document).ready(function() {
    fetch('https://provinces.open-api.vn/api/?depth=1').then(res => res.json()).then(data => {
        data.forEach(item => $('#province').append(`<option value="${item.code}">${item.name}</option>`));
    });

    $('#province').change(function() {
        const code = $(this).val();
        $('#district, #ward').html('<option value="">Chọn...</option>');
        if(code) {
            fetch(`https://provinces.open-api.vn/api/p/${code}?depth=2`).then(res => res.json())
                .then(data => data.districts.forEach(item => $('#district').append(`<option value="${item.code}">${item.name}</option>`)));
        }
    });

    $('#district').change(function() {
        const code = $(this).val();
        $('#ward').html('<option value="">Chọn...</option>');
        if(code) {
            fetch(`https://provinces.open-api.vn/api/d/${code}?depth=2`).then(res => res.json())
                .then(data => data.wards.forEach(item => $('#ward').append(`<option value="${item.code}">${item.name}</option>`)));
        }
    });

    $('input[name="payment_method"]').change(function() {
        $(this).val() === 'banking' ? $('#bank-info').slideDown() : $('#bank-info').slideUp();
    });

    $('#checkout-form').submit(function(e) {
        e.preventDefault();
        const fullAddress = `${$('#street').val()}, ${$('#ward option:selected').text()}, ${$('#district option:selected').text()}, ${$('#province option:selected').text()}`;
        
        const orderData = {
            fullname: $('#fullname').val(),
            phone: $('#phone').val(),      
            email: $('#email').val(),
            address: fullAddress,
            note: $('#note').val(),
            payment_method: $('input[name="payment_method"]:checked').val(),
            total_price: <?= $total_price ?> 
        };

        $('.btn-order').text('ĐANG XỬ LÝ...').prop('disabled', true);

        $.ajax({
            url: '../backend/checkout_process.php',
            type: 'POST',
            data: orderData,
            success: function(response) {
                let res;
                try { res = (typeof response === 'object') ? response : JSON.parse(response); } 
                catch(e) { alert('Lỗi dữ liệu trả về!'); $('.btn-order').text('XÁC NHẬN ĐẶT HÀNG').prop('disabled', false); return; }

                if(res.status === 'success') {
                    alert('Đặt hàng thành công! Mã đơn: #' + res.order_id);
                    window.location.href = 'order_history.php'; 
                } else {
                    alert('Lỗi: ' + res.message);
                    $('.btn-order').text('XÁC NHẬN ĐẶT HÀNG').prop('disabled', false);
                }
            },
            error: function() {
                alert('Lỗi kết nối máy chủ!');
                $('.btn-order').text('XÁC NHẬN ĐẶT HÀNG').prop('disabled', false);
            }
        });
    });
});

function changeQty(variantId, delta) {
    let qtyInput = $('#qty-' + variantId);
    let newQty = parseInt(qtyInput.val()) + delta;
    if (newQty < 1) return;

    $.post('../backend/cart_process.php', { action: 'update', id: variantId, qty: newQty }, function(response) {
        let res;
        try { res = (typeof response === 'object') ? response : JSON.parse(response); } 
        catch(e) { location.reload(); return; }

        if (res.status === 'success') { location.reload(); } 
        else { alert('Lỗi: ' + res.message); location.reload(); }
    });
}
</script>

<?php include '../includes/footer.php'; ?>