<?php 
require_once '../config/database.php';
$conn = getDatabase();
require_once '../includes/admin_header.php'; 

// 1. Lấy ID đơn hàng từ URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    echo "<script>Swal.fire({ icon: 'warning', title: 'Mã đơn hàng không hợp lệ', text: 'Mã đơn hàng không hợp lệ!' }).then(function() { window.location.href='orders.php'; });</script>";
    exit;
}

// 2. Lấy thông tin chung của đơn hàng
$stmt_order = $conn->prepare("SELECT * FROM Orders WHERE id = ?");
$stmt_order->execute([$order_id]);
$order = $stmt_order->fetch();

if (!$order) {
    echo "<script>Swal.fire({ icon: 'warning', title: 'Không tìm thấy đơn hàng', text: 'Không tìm thấy đơn hàng!' }).then(function() { window.location.href='orders.php'; });</script>";
    exit;
}

// 3. Lấy chi tiết các sản phẩm (ĐÃ JOIN THÊM BẢNG VARIANT)
$stmt_detail = $conn->prepare("
    SELECT od.*, p.title, p.thumbnail, v.name AS variant_name 
    FROM Order_Details od 
    JOIN Product p ON od.product_id = p.id 
    JOIN Product_Variant pv ON od.product_variant_id = pv.id
    JOIN Variant v ON pv.variant_id = v.id
    WHERE od.order_id = ?
");
$stmt_detail->execute([$order_id]);
$order_details = $stmt_detail->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 class="page-title" style="margin: 0; border: none;">Chi tiết Đơn hàng #<?= htmlspecialchars(!empty($order['order_code']) ? $order['order_code'] : $order['id']) ?></h1>
    <a href="orders.php" style="background: #95a5a6; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px;">
        <i class="fa-solid fa-arrow-left"></i> Quay lại danh sách
    </a>
</div>

<div style="display: grid; grid-template-columns: 1fr 2.5fr; gap: 20px;">
    
    <div class="customer-info" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); height: fit-content;">
        <h3 style="margin-bottom: 20px; color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px;">
            <i class="fa-solid fa-address-card"></i> Thông tin giao hàng
        </h3>
        
        <p style="margin-bottom: 12px;"><strong>Họ tên:</strong> <?= htmlspecialchars($order['fullname']) ?></p>
        <p style="margin-bottom: 12px;"><strong>Điện thoại:</strong> <?= htmlspecialchars($order['phone_number']) ?></p>
        <p style="margin-bottom: 12px;"><strong>Email:</strong> <?= !empty($order['email']) ? htmlspecialchars($order['email']) : '<span style="color:#999;">Không có</span>' ?></p>
        <p style="margin-bottom: 12px;"><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['address']) ?></p>
        <p style="margin-bottom: 12px;"><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></p>
        
        <div style="background: #fcf8e3; padding: 15px; border-radius: 5px; margin-top: 20px; border: 1px solid #faebcc;">
            <strong>Ghi chú:</strong><br>
            <span style="color: #8a6d3b;">
                <?= !empty($order['note']) ? nl2br(htmlspecialchars($order['note'])) : 'Không có ghi chú.' ?>
            </span>
        </div>
    </div>

    <div class="order-items">
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th style="text-align: center;">Đơn giá</th>
                        <th style="text-align: center;">Số lượng</th>
                        <th style="text-align: right;">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_details as $item): 
                        $subtotal = $item['price'] * $item['num'];
                    ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <img src="<?= htmlspecialchars(imageSrc($item['thumbnail'] ?? '', 'products')) ?>" alt="img" width="60" style="border-radius: 5px; border: 1px solid #eee;">
                                    <div>
                                        <strong style="display: block; color: #333; margin-bottom: 5px;"><?= htmlspecialchars($item['title']) ?></strong>
                                        <span style="font-size: 12px; color: #777; background: #eee; padding: 3px 8px; border-radius: 4px;">
                                            Phân loại: <strong><?= htmlspecialchars($item['variant_name']) ?></strong>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align: center; color: #555;">
                                <?= number_format($item['price'], 0, ',', '.') ?>đ
                            </td>
                            <td style="text-align: center; font-weight: bold;">
                                <?= $item['num'] ?>
                            </td>
                            <td style="text-align: right; color: #D4A373; font-weight: bold;">
                                <?= number_format($subtotal, 0, ',', '.') ?>đ
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="padding: 20px; text-align: right; border-top: 2px solid #eee; background: #fafafa;">
                <span style="font-size: 16px; color: #555;">Tổng giá trị đơn hàng:</span>
                <span style="font-size: 24px; font-weight: bold; color: #e74c3c; margin-left: 15px;">
                    <?= number_format($order['total_money'], 0, ',', '.') ?>đ
                </span>
            </div>
        </div>
    </div>

</div>

<?php require_once '../includes/admin_footer.php'; ?>