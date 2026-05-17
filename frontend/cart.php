<?php 
require_once '../config/database.php';
include '../includes/header.php'; 

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

<main class="container cart-page" style="margin-top: 40px; min-height: 50vh;">
    <h2 style="margin-bottom: 20px; font-family: 'Playfair Display', serif;">Giỏ hàng của bạn</h2>

    <?php if (empty($cart_items)): ?>
        <div style="text-align: center; padding: 50px 0;">
            <p style="font-size: 18px; color: #666; margin-bottom: 20px;">Giỏ hàng của bạn đang trống.</p>
            <a href="index.php" class="btn-shop" style="background: #D4A373; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Tiếp tục mua sắm</a>
        </div>
    <?php else: ?>
        <table class="cart-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #fdfaf6; text-align: left;">
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
                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <img src="../assets/uploads/products/<?php echo htmlspecialchars($item['thumbnail']); ?>" width="80" style="border-radius: 5px; border: 1px solid #eee;">
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
                        <td style="padding: 15px; border-bottom: 1px solid #eee; font-weight: bold;" id="subtotal-<?php echo $pv_id; ?>">
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
            <p style="font-size: 18px;">Tổng thanh toán: 
                <strong style="color: #e74c3c; font-size: 28px;" id="total-price">
                    <?php echo number_format($total_price, 0, ',', '.'); ?>đ
                </strong>
            </p>
            <a href="../frontend/checkout.php" style="display: inline-block; background: #D4A373; color: white; padding: 15px 40px; text-decoration: none; margin-top: 20px; border-radius: 5px; font-weight: bold; font-size: 16px; transition: 0.3s;" onmouseover="this.style.background='#c2905f'" onmouseout="this.style.background='#D4A373'">
                TIẾN HÀNH THANH TOÁN
            </a>
        </div>
    <?php endif; ?>
</main>

<script>
// Hàm AJAX cập nhật số lượng
function updateCart(variantId, qty) {
    if (qty < 1) return;
    $.post('../backend/cart_process.php', { id: variantId, qty: qty, action: 'update' }, function(response) {
        let res;
        try {
            res = (typeof response === 'object') ? response : JSON.parse(response);
        } catch(e) {
            location.reload(); return;
        }

        if (res.status === 'error') {
            alert('Lỗi: ' + res.message);
            location.reload(); // Load lại để trả về số lượng cũ hợp lệ
        } else {
            location.reload(); // Load lại trang để cập nhật tổng tiền
        }
    });
}

// Hàm AJAX xóa sản phẩm
function removeCart(variantId) {
    if(confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) {
        $.post('../backend/cart_process.php', { id: variantId, action: 'remove' }, function(response) {
            location.reload();
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>