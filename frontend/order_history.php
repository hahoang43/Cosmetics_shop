<?php 
require_once '../config/database.php';
$conn = getDatabase();
require_once '../includes/header.php'; 

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$fullname = $_SESSION['user']['fullname'];

// 2. XỬ LÝ ĐIỀU KIỆN LỌC KÉP (TRẠNG THÁI & TÌM KIẾM TỪ KHÓA)
$status_filter = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : -1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT * FROM Orders WHERE user_id = ?";
$params = [$user_id];

// Nếu lọc theo trạng thái
if ($status_filter >= 0) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

// Nếu nhập từ khóa tìm kiếm (Mã đơn, Tên người nhận, SĐT, hoặc TÊN SẢN PHẨM)
if ($search !== '') {
    $clean_search = ltrim($search, '#');
    
    // Kiểm tra xem cột order_code có tồn tại không
    $stmt_check = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Orders' AND COLUMN_NAME='order_code' AND TABLE_SCHEMA=?");
    $stmt_check->execute(['db_mypham']);
    $has_order_code = $stmt_check->rowCount() > 0;
    
    if ($has_order_code) {
        $sql .= " AND (order_code LIKE ? OR id LIKE ? OR fullname LIKE ? OR phone_number LIKE ? OR id IN (
            SELECT order_id FROM Order_Details od 
            JOIN Product p ON od.product_id = p.id 
            WHERE p.title LIKE ?
        ))";
        $params[] = "%$clean_search%";
        $params[] = "%$clean_search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    } else {
        $sql .= " AND (id LIKE ? OR fullname LIKE ? OR phone_number LIKE ? OR id IN (
            SELECT order_id FROM Order_Details od 
            JOIN Product p ON od.product_id = p.id 
            WHERE p.title LIKE ?
        ))";
        $params[] = "%$clean_search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>
<link rel="stylesheet" href="/Cosmetics_shop/assets/css/profile.css">
<div class="container">
    <div class="profile-container">
        
        <aside class="profile-sidebar">
            <div class="user-brief">
                <i class="fa-solid fa-circle-user"></i>
                <div>
                    <div class="username"><?= htmlspecialchars($fullname) ?></div>
                    <span style="font-size: 12px; color: #999;">Khách hàng thành viên</span>
                </div>
            </div>
            
            <ul class="profile-menu">
                <li><a href="profile.php"><i class="fa-solid fa-user"></i> Thông tin tài khoản</a></li>
                <li><a href="order_history.php" class="active"><i class="fa-solid fa-clipboard-list"></i> Đơn hàng của tôi</a></li>
                <li><a href="change_password.php"><i class="fa-solid fa-key"></i> Đổi mật khẩu</a></li>
                <li><a href="logout.php" style="color: #e74c3c;"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></li>
            </ul>
        </aside>

        <main class="profile-main">
            <h2>Đơn hàng của tôi</h2>
            <p style="color: #777; margin-bottom: 20px;">Theo dõi và quản lý các đơn hàng bạn đã đặt tại Lumina</p>

            <div style="background: #fff; padding: 15px; border-radius: 6px; border: 1px solid #eee; margin-bottom: 25px;">
                <form action="" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                    
                    <div style="flex: 1; min-width: 220px;">
                        <input type="text" name="search" placeholder="Tìm theo tên sản phẩm, mã đơn, SĐT..." value="<?= htmlspecialchars($search) ?>" style="width: 100%; padding: 9px 12px; border: 1px solid #ddd; border-radius: 4px; outline: none; font-size: 14px;">
                    </div>
                    
                    <div style="width: 180px;">
                        <select name="status" style="width: 100%; padding: 9px 12px; border: 1px solid #ddd; border-radius: 4px; outline: none; background: #fff; font-size: 14px; cursor: pointer; color: #555;">
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="0" <?= $status_filter === 0 ? 'selected' : '' ?>>Chờ xác nhận</option>
                            <option value="1" <?= $status_filter === 1 ? 'selected' : '' ?>>Đang giao</option>
                            <option value="2" <?= $status_filter === 2 ? 'selected' : '' ?>>Đã giao thành công</option>
                            <option value="3" <?= $status_filter === 3 ? 'selected' : '' ?>>Đã hủy đơn</option>
                            <option value="4" <?= $status_filter === 4 ? 'selected' : '' ?>>Chờ duyệt đổi trả</option>
                            <option value="5" <?= $status_filter === 5 ? 'selected' : '' ?>>Đã hoàn trả thành công</option>
                            <option value="6" <?= $status_filter === 6 ? 'selected' : '' ?>>Từ chối đổi trả</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 5px;">
                        <button type="submit" style="background: #D4A373; color: white; border: none; padding: 9px 18px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-magnifying-glass"></i> Tìm
                        </button>
                        
                        <?php if($search !== '' || $status_filter >= 0): ?>
                            <a href="order_history.php" style="display: inline-flex; align-items: center; background: #95a5a6; color: white; padding: 9px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 14px;">
                                Hủy lọc
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if (count($orders) > 0): ?>
                <div class="order-list-wrapper">
                    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                        <thead>
                            <tr style="background: #fdfaf6; text-align: left;">
                                <th style="padding: 15px; border-bottom: 2px solid #eee; width: 12%;">Mã đơn</th>
                                <th style="padding: 15px; border-bottom: 2px solid #eee; width: 48%;">Sản phẩm đã đặt</th>
                                <th style="padding: 15px; border-bottom: 2px solid #eee; width: 15%;">Tổng tiền</th>
                                <th style="padding: 15px; border-bottom: 2px solid #eee; width: 13%;">Trạng thái</th>
                                <th style="padding: 15px; border-bottom: 2px solid #eee; text-align: center; width: 12%;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $row): 
                                // TRUY VẤN LẤY TOÀN BỘ SẢN PHẨM TRONG ĐƠN HÀNG NÀY
                                $stmt_items = $conn->prepare("
                                    SELECT od.*, p.title, p.thumbnail, v.name AS variant_name 
                                    FROM Order_Details od
                                    JOIN Product p ON od.product_id = p.id
                                    LEFT JOIN Product_Variant pv ON od.product_variant_id = pv.id
                                    LEFT JOIN Variant v ON pv.variant_id = v.id
                                    WHERE od.order_id = ?
                                ");
                                $stmt_items->execute([$row['id']]);
                                $items = $stmt_items->fetchAll();
                            ?>
                                <tr>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee; vertical-align: top;">
                                        <strong>#<?= htmlspecialchars(!empty($row['order_code']) ? $row['order_code'] : $row['id']) ?></strong>
                                        <div style="font-size: 11px; color: #999; margin-top: 6px; white-space: nowrap;">
                                            <?= date('d/m/Y', strtotime($row['order_date'])) ?>
                                        </div>
                                    </td>
                                    
                                    <td style="padding: 15px; border-bottom: 1px solid #eee; vertical-align: top;">
                                        <?php if (count($items) > 0): ?>
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <a href="product_detail.php?id=<?= (int)$items[0]['product_id'] ?>" style="display: inline-flex;">
                                                    <img src="<?= htmlspecialchars(imageSrc($items[0]['thumbnail'] ?? '', 'products')) ?>" width="45" height="45" style="object-fit: cover; border-radius: 4px; border: 1px solid #eee;" onerror="this.onerror=null;this.src='<?= htmlspecialchars(noImageSrc('No Image')) ?>';">
                                                </a>
                                                <div style="line-height: 1.4;">
                                                    <a href="product_detail.php?id=<?= (int)$items[0]['product_id'] ?>" style="font-weight: 500; font-size: 13.5px; color: #333; display: block; max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-decoration: none;" title="<?= htmlspecialchars($items[0]['title']) ?>">
                                                        <?= htmlspecialchars($items[0]['title']) ?>
                                                    </a>
                                                    <span style="font-size: 12px; color: #777;">
                                                        Phân loại: <?= htmlspecialchars($items[0]['variant_name']) ?> <strong style="color:#444; margin-left:5px;">x<?= $items[0]['num'] ?></strong>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <?php if (count($items) > 1): ?>
                                                <div id="more-products-<?= $row['id'] ?>" style="display: none; margin-top: 12px; border-top: 1px dashed #eee; padding-top: 12px;">
                                                    <?php for ($i = 1; $i < count($items); $i++): ?>
                                                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                                            <a href="product_detail.php?id=<?= (int)$items[$i]['product_id'] ?>" style="display: inline-flex;">
                                                                <img src="<?= htmlspecialchars(imageSrc($items[$i]['thumbnail'] ?? '', 'products')) ?>" width="45" height="45" style="object-fit: cover; border-radius: 4px; border: 1px solid #eee;" onerror="this.onerror=null;this.src='<?= htmlspecialchars(noImageSrc('No Image')) ?>';">
                                                            </a>
                                                            <div style="line-height: 1.4;">
                                                                <a href="product_detail.php?id=<?= (int)$items[$i]['product_id'] ?>" style="font-weight: 500; font-size: 13.5px; color: #333; display: block; max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-decoration: none;" title="<?= htmlspecialchars($items[$i]['title']) ?>">
                                                                    <?= htmlspecialchars($items[$i]['title']) ?>
                                                                </a>
                                                                <span style="font-size: 12px; color: #777;">
                                                                    Phân loại: <?= htmlspecialchars($items[$i]['variant_name']) ?> <strong style="color:#444; margin-left:5px;">x<?= $items[$i]['num'] ?></strong>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    <?php endfor; ?>
                                                </div>
                                                
                                                <a href="javascript:void(0);" class="toggle-products-btn" data-order-id="<?= $row['id'] ?>" data-count="<?= count($items) - 1 ?>" style="color: #e67e22; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block; margin-top: 8px;">
                                                    <i class="fa-solid fa-chevron-down"></i> Xem thêm (<?= count($items) - 1 ?> sản phẩm khác)
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>

                                    <td style="padding: 15px; border-bottom: 1px solid #eee; color: #D4A373; font-weight: bold; vertical-align: top;">
                                        <?= number_format($row['total_money'], 0, ',', '.') ?>đ
                                    </td>

                                    <td style="padding: 15px; border-bottom: 1px solid #eee; vertical-align: top;">
                                        <?php 
                                            $s = $row['status'];
                                            if($s == 0) echo '<span style="background:#f39c12; color:white; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:bold; display:inline-block; text-align:center; min-width:105px;">Chờ xác nhận</span>';
                                            elseif($s == 1) echo '<span style="background:#3498db; color:white; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:bold; display:inline-block; text-align:center; min-width:105px;">Đang giao</span>';
                                            elseif($s == 2) echo '<span style="background:#2ecc71; color:white; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:bold; display:inline-block; text-align:center; min-width:110px;">Đã giao</span>';
                                            elseif($s == 3) echo '<span style="background:#e74c3c; color:white; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:bold; display:inline-block; text-align:center; min-width:105px;">Đã hủy</span>';
                                            elseif($s == 4) echo '<span style="background:#e67e22; color:white; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:bold; display:inline-block; text-align:center; min-width:105px;">Chờ duyệt trả</span>';
                                            elseif($s == 5) echo '<span style="background:#27ae60; color:white; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:bold; display:inline-block; text-align:center; min-width:105px;">Đã hoàn trả</span>';
                                            elseif($s == 6) echo '<span style="background:#c0392b; color:white; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:bold; display:inline-block; text-align:center; min-width:105px;">Từ chối trả</span>';
                                        ?>
                                    </td>
                                    
                                    <td style="padding: 15px; border-bottom: 1px solid #eee; text-align: center; vertical-align: top;">
                                        <div style="display: flex; flex-direction: column; gap: 8px; align-items: center;">
                                            <a href="order_detail_user.php?id=<?= $row['id'] ?>" style="color: #3498db; text-decoration: none; font-weight: 600; font-size: 13.5px; padding: 6px 0; display: inline-block;">
                                                Chi tiết
                                            </a>
                                            
                                            <?php if ($row['status'] == 0): ?>
                                                <a href="cancel_order.php?id=<?= $row['id'] ?>" 
                                                    onclick="return confirmCancelOrder(event, 'cancel_order.php?id=<?= $row['id'] ?>')" 
                                                    style="color: #e74c3c; font-size: 12.5px; text-decoration: none; font-weight: 600; padding: 6px 0; display: inline-block;">
                                                    Hủy đơn
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 50px 0;">
                    <i class="fa-solid fa-magnifying-glass" style="font-size: 45px; color: #ddd; margin-bottom: 15px;"></i>
                    <p style="color: #999;">Không tìm thấy đơn hàng nào phù hợp.</p>
                    <a href="order_history.php" style="display: inline-block; margin-top: 15px; background: #95a5a6; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 14px;">Xem tất cả đơn</a>
                </div>
            <?php endif; ?>
        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    $('.toggle-products-btn').click(function() {
        let orderId = $(this).data('order-id');
        let count = $(this).data('count');
        let targetDiv = $('#more-products-' + orderId);
        
        if (targetDiv.is(':visible')) {
            targetDiv.slideUp();
            $(this).html(`<i class="fa-solid fa-chevron-down"></i> Xem thêm (${count} sản phẩm khác)`);
        } else {
            targetDiv.slideDown();
            $(this).html(`<i class="fa-solid fa-chevron-up"></i> Thu gọn`);
        }
    });
});

function confirmCancelOrder(event, url) {
    event.preventDefault();

    Swal.fire({
        icon: 'question',
        title: 'Hủy đơn hàng',
        text: 'Bạn có chắc muốn hủy đơn hàng này?',
        showCancelButton: true,
        confirmButtonText: 'Hủy đơn',
        cancelButtonText: 'Không',
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#95a5a6'
    }).then(function(result) {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });

    return false;
}
</script>

<?php if (isset($_SESSION['flash_success'])): ?>
    <script>Swal.fire({ icon: 'success', title: 'Thành công!', text: '<?= $_SESSION['flash_success'] ?>', timer: 2500, showConfirmButton: false });</script>
<?php unset($_SESSION['flash_success']); endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <script>Swal.fire({ icon: 'error', title: 'Lỗi!', text: '<?= $_SESSION['flash_error'] ?>' });</script>
<?php unset($_SESSION['flash_error']); endif; ?>

<?php require_once '../includes/footer.php'; ?>