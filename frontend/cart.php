<?php 
require_once '../config/database.php';
require_once '../includes/popup_notify.php';
$conn = getDatabase();
include_once '../includes/header.php'; 

echo popup_assets();

$total_price = 0;
$cart_items = [];

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    // ĐÃ SỬA: Lấy dữ liệu kết hợp từ 3 bảng: Product, Product_Variant, Variant
    $sql = "
        SELECT pv.id AS pv_id, p.title, p.thumbnail, v.name AS variant_name, pv.price 
        FROM Product_Variant pv 
        JOIN Product p ON pv.product_id = p.id 
        JOIN Variant v ON pv.variant_id = v.id 
        WHERE pv.id IN ($placeholders)
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($ids);
    $cart_items = $stmt->fetchAll();
}
?>

<main class="container cart-page">
    <h2 style="margin-bottom: 20px; font-family: 'Playfair Display', serif;">Giỏ hàng của bạn</h2>

    <?php if (empty($cart_items)): ?>
        <div style="text-align: center; padding: 50px 0;">
            <p style="font-size: 18px; color: #666; margin-bottom: 20px;">Giỏ hàng của bạn đang trống.</p>
            <a href="index.php" class="btn-shop" style="background: #D4A373; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Tiếp tục mua sắm</a>
        </div>
    <?php else: ?>
        <div style="display:flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 14px; flex-wrap: wrap;">
            <label style="display:inline-flex; align-items:center; gap:8px; font-size:14px; color:#555; cursor:pointer;">
                <input type="checkbox" id="select-all-cart" style="width: 16px; height: 16px; accent-color: #D4A373;">
                Chọn tất cả sản phẩm
            </label>
        </div>

        <table class="cart-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #fdfaf6; text-align: left;">
                    <th style="padding: 15px; border-bottom: 2px solid #eee; width: 56px; text-align:center;">Chọn</th>
                    <th style="padding: 15px; border-bottom: 2px solid #eee;">Sản phẩm</th>
                    <th style="padding: 15px; border-bottom: 2px solid #eee;">Đơn giá</th>
                    <th style="padding: 15px; border-bottom: 2px solid #eee; text-align: center;">Số lượng</th>
                    <th style="padding: 15px; border-bottom: 2px solid #eee;">Thành tiền</th>
                    <th style="padding: 15px; border-bottom: 2px solid #eee; text-align: center;">Xóa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): 
                    $pv_id = $item['pv_id'];
                    // Kiểm tra kỹ tránh lỗi Undefined array key
                    $qty = isset($_SESSION['cart'][$pv_id]) ? $_SESSION['cart'][$pv_id] : 0; 
                    
                    if ($qty > 0): // Chỉ hiển thị nếu số lượng > 0
                        $subtotal = $item['price'] * $qty; 
                        $total_price += $subtotal; 
                ?>
                    <tr id="cart-item-<?php echo $pv_id; ?>">
                        <td style="padding: 15px; border-bottom: 1px solid #eee; text-align: center;">
                            <input type="checkbox" class="cart-item-check" data-pv-id="<?php echo $pv_id; ?>" checked style="width:16px; height:16px; accent-color:#D4A373;">
                        </td>
                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <img src="<?php echo htmlspecialchars(imageSrc($item['thumbnail'] ?? '', 'products')); ?>" width="80" style="border-radius: 5px; border: 1px solid #eee;">
                                <div>
                                    <strong style="display: block; margin-bottom: 5px; color: #333;"><?php echo htmlspecialchars($item['title']); ?></strong>
                                    <span style="font-size: 13px; color: #777; background: #f9f9f9; padding: 3px 8px; border: 1px solid #ddd; border-radius: 4px;">
                                        Phân loại: <strong><?php echo htmlspecialchars($item['variant_name']); ?></strong>
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 15px; border-bottom: 1px solid #eee; color: #D4A373; font-weight: bold;">
                            <?php echo number_format($item['price'], 0, ',', '.'); ?>đ
                        </td>
                        <td style="padding: 15px; border-bottom: 1px solid #eee; text-align: center;">
                            <input type="number" value="<?php echo $qty; ?>" min="1" class="qty-input" 
                                onchange="updateCart(<?php echo $pv_id; ?>, this.value)"
                                style="width: 60px; padding: 8px; text-align: center; border: 1px solid #ddd; border-radius: 4px; outline: none;">
                        </td>
                        <td style="padding: 15px; border-bottom: 1px solid #eee; font-weight: bold;" id="subtotal-<?php echo $pv_id; ?>" data-subtotal="<?php echo $subtotal; ?>">
                            <?php echo number_format($subtotal, 0, ',', '.'); ?>đ
                        </td>
                        <td style="padding: 15px; border-bottom: 1px solid #eee; text-align: center;">
                            <button onclick="removeCart(<?php echo $pv_id; ?>)" style="background: #ff4d4d; color: white; border: none; width: 35px; height: 35px; border-radius: 5px; cursor: pointer; transition: 0.3s;" onmouseover="this.style.background='#e60000'" onmouseout="this.style.background='#ff4d4d'">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endif; endforeach; ?>
            </tbody>
        </table>

        <div class="cart-summary" style="margin-top: 30px; text-align: right; background: #fdfaf6; padding: 25px; border-radius: 8px; border: 1px solid #eaddcc;">
            <p style="font-size: 18px; margin-bottom: 8px;">Tổng thanh toán sản phẩm đã chọn: 
                <strong style="color: #e74c3c; font-size: 28px;" id="total-price">
                    <?php echo number_format($total_price, 0, ',', '.'); ?>đ
                </strong>
            </p>
            <button type="button" id="btn-checkout-selected" style="display: inline-block; background: #2c3e50; color: #fff; padding: 15px 40px; border: none; border-radius: 5px; font-weight: bold; font-size: 16px; transition: 0.3s; cursor: pointer; margin-right: 10px;" onmouseover="this.style.background='#1f2d3a'" onmouseout="this.style.background='#2c3e50'">
                TIẾN HÀNH THANH TOÁN
            </button>
       </div>
    <?php endif; ?>
</main>

<script>
// Hàm AJAX cập nhật số lượng
function updateCart(variantId, qty) {
    if (qty < 1) return;
    $.post('/Cosmetics_shop/backend/cart_process.php', { id: variantId, qty: qty, action: 'update' }, function(response) {
        let res;
        try {
            res = (typeof response === 'object') ? response : JSON.parse(response);
        } catch(e) {
            location.reload(); return;
        }

        if (res.status === 'error') {
            Swal.fire({ icon: 'error', title: 'Cập nhật thất bại', text: 'Lỗi: ' + res.message });
            location.reload(); // Load lại để trả về số lượng cũ hợp lệ
        } else {
            location.reload(); // Load lại trang để cập nhật tổng tiền
        }
    });
}

function formatMoney(value) {
    return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
}

function updateSelectedTotal() {
    let selectedTotal = 0;

    $('.cart-item-check:checked').each(function() {
        const pvId = $(this).data('pv-id');
        const subtotal = parseInt($('#subtotal-' + pvId).data('subtotal') || 0);
        selectedTotal += subtotal;
    });

    $('#total-price').text(formatMoney(selectedTotal));
    $('#btn-checkout-selected').prop('disabled', selectedTotal <= 0);
}

// Hàm AJAX xóa sản phẩm
function removeCart(variantId) {
    Swal.fire({
        icon: 'warning',
        title: 'Xóa sản phẩm?',
        text: 'Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?',
        showCancelButton: true,
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (!result.isConfirmed) return;
        $.post('/Cosmetics_shop/backend/cart_process.php', { id: variantId, action: 'remove' }, function(response) {
            location.reload();
        });
    });
}

$('#select-all-cart').on('change', function() {
    $('.cart-item-check').prop('checked', $(this).is(':checked'));
    updateSelectedTotal();
});

$(document).on('change', '.cart-item-check', function() {
    const total = $('.cart-item-check').length;
    const checked = $('.cart-item-check:checked').length;
    $('#select-all-cart').prop('checked', total > 0 && checked === total);
    updateSelectedTotal();
});

$('#btn-checkout-selected').on('click', function() {
    const selectedIds = $('.cart-item-check:checked').map(function() {
        return parseInt($(this).data('pv-id'));
    }).get();

    if (!selectedIds.length) {
        Swal.fire({ icon: 'warning', title: 'Chưa chọn sản phẩm', text: 'Vui lòng chọn ít nhất một sản phẩm để thanh toán.' });
        return;
    }

    $.post('/Cosmetics_shop/backend/cart_process.php', {
        action: 'prepare_checkout',
        selected_ids: JSON.stringify(selectedIds)
    }, function(response) {
        let res;
        try {
            res = (typeof response === 'object') ? response : JSON.parse(response);
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Lỗi thanh toán', text: 'Đã xảy ra lỗi khi chuẩn bị thanh toán.' });
            return;
        }

        if (res.status === 'success') {
            window.location.href = '/Cosmetics_shop/frontend/checkout.php';
        } else {
            Swal.fire({ icon: 'error', title: 'Không thể thanh toán', text: 'Lỗi: ' + (res.message || 'Không thể thanh toán các sản phẩm đã chọn.') });
        }
    });
});

$('#btn-checkout-all').on('click', function() {
    $.post('/Cosmetics_shop/backend/cart_process.php', {
        action: 'clear_checkout'
    }, function() {
        window.location.href = '/Cosmetics_shop/frontend/checkout.php';
    });
});

updateSelectedTotal();
</script>

<?php include '../includes/footer.php'; ?>