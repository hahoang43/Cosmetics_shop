<?php
require_once '../config/database.php';
$conn = getDatabase();
require_once '../includes/admin_header.php';

// --- XỬ LÝ CẬP NHẬT TRẠNG THÁI VÀ TỒN KHO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = (int)$_POST['status'];

    try {
        // Lấy trạng thái hiện tại của đơn hàng trước khi cập nhật
        $stmt_old = $conn->prepare("SELECT status FROM Orders WHERE id = ?");
        $stmt_old->execute([$order_id]);
        $old_status = (int)$stmt_old->fetchColumn();

        if ($old_status !== $new_status) {
            $conn->beginTransaction();

            // 1. Cập nhật trạng thái đơn hàng
            $stmt_update = $conn->prepare("UPDATE Orders SET status = ? WHERE id = ?");
            $stmt_update->execute([$new_status, $order_id]);

            // 2. Xử lý tồn kho khi thay đổi trạng thái
            $stmt_details = $conn->prepare("SELECT product_variant_id, num FROM Order_Details WHERE order_id = ?");
            $stmt_details->execute([$order_id]);
            $items = $stmt_details->fetchAll();

            // Hủy đơn (3) hoặc Chấp nhận hoàn trả (5) => CỘNG LẠI KHO
            if (($new_status == 3 || $new_status == 5) && $old_status != 3 && $old_status != 5) {
                foreach ($items as $item) {
                    $stmt_restore = $conn->prepare("UPDATE Product_Variant SET quantity = quantity + ? WHERE id = ?");
                    $stmt_restore->execute([$item['num'], $item['product_variant_id']]);
                }
            }
            // Khôi phục từ Hủy/Hoàn trả về giao bình thường => TRỪ LẠI KHO
            elseif (($old_status == 3 || $old_status == 5) && $new_status != 3 && $new_status != 5) {
                foreach ($items as $item) {
                    $stmt_deduct = $conn->prepare("UPDATE Product_Variant SET quantity = quantity - ? WHERE id = ?");
                    $stmt_deduct->execute([$item['num'], $item['product_variant_id']]);
                }
            }

            $conn->commit();
            echo "<script>alert('Cập nhật trạng thái đơn hàng #$order_id thành công!'); window.location.href='orders.php';</script>";
            exit;
        }
    } catch(Exception $e) {
        if ($conn->inTransaction()) { $conn->rollBack(); }
        echo "<script>alert('Lỗi cập nhật: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// --- TÌM KIẾM ĐƠN HÀNG ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = [];
$where_clause = '';

// Kiểm tra xem cột order_code có tồn tại không
$stmt_check_column = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Orders' AND COLUMN_NAME='order_code' AND TABLE_SCHEMA=?");
$stmt_check_column->execute(['db_mypham']);
$column_exists = $stmt_check_column->rowCount() > 0;

// Nếu cột chưa tồn tại, tạo nó
if (!$column_exists) {
    try {
        $conn->exec("ALTER TABLE Orders ADD COLUMN order_code VARCHAR(7) DEFAULT NULL UNIQUE");
        $column_exists = true;
    } catch(Exception $e) {
        // Cột có thể đã được tạo bởi request khác, bỏ qua lỗi
    }
}

if (!empty($search)) {
    if ($column_exists) {
        $where_clause = "WHERE (order_code LIKE ? OR fullname LIKE ?)";
        $search_param = ["%$search%", "%$search%"];
    } else {
        $where_clause = "WHERE fullname LIKE ?";
        $search_param = ["%$search%"];
    }
}

// --- LẤY DANH SÁCH ĐƠN HÀNG MỚI NHẤT ---
$stmt = $conn->prepare("SELECT * FROM Orders $where_clause ORDER BY id DESC");
$stmt->execute($search_param);
$orders = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 class="page-title" style="margin: 0; border: none;">Quản lý Đơn hàng</h1>
    <span style="background: #2c3e50; color: #fff; padding: 5px 15px; border-radius: 20px; font-size: 14px;">
        Tổng số: <strong><?= count($orders) ?></strong> đơn
    </span>
</div>

<!-- Search Box -->
<div style="margin-bottom: 20px;">
    <form method="GET" style="display: flex; gap: 10px; align-items: center;">
        <input type="text" name="search" placeholder="Tìm kiếm theo mã đơn hàng hoặc tên khách hàng..." value="<?= htmlspecialchars($search) ?>" 
               style="flex: 1; padding: 10px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
        <button type="submit" style="background: #D4A373; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
            <i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm
        </button>
        <?php if(!empty($search)): ?>
            <a href="orders.php" style="background: #95a5a6; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-weight: bold;">
                <i class="fa-solid fa-xmark"></i> Xóa lọc
            </a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Mã ĐH</th>
                <th>Khách hàng</th>
                <th>Điện thoại</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th style="text-align: center;">Cập nhật trạng thái</th>
                <th>Chi tiết</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($orders) > 0): ?>
                <?php foreach($orders as $row): ?>
                    <tr>
                        <td><strong>#<?= htmlspecialchars(!empty($row['order_code']) ? $row['order_code'] : $row['id']) ?></strong></td>
                        <td><?= htmlspecialchars($row['fullname']) ?></td>
                        <td><?= htmlspecialchars($row['phone_number']) ?></td>
                        <td style="color: #D4A373; font-weight: bold;">
                            <?= number_format($row['total_money'], 0, ',', '.') ?>đ
                        </td>
                        
                        <td>
                            <?php
                                $status = $row['status'];
                                $badge_style = '';
                                $status_text = '';
                                
                                if($status == 0) { $badge_style = 'background: #f39c12; color: white;'; $status_text = 'Chờ xác nhận'; }
                                elseif($status == 1) { $badge_style = 'background: #3498db; color: white;'; $status_text = 'Đang giao'; }
                                elseif($status == 2) { $badge_style = 'background: #2ecc71; color: white;'; $status_text = 'Đã giao'; }
                                elseif($status == 3) { $badge_style = 'background: #95a5a6; color: white;'; $status_text = 'Đã hủy'; }
                                elseif($status == 4) { $badge_style = 'background: #e67e22; color: white;'; $status_text = 'Chờ duyệt trả'; }
                                elseif($status == 5) { $badge_style = 'background: #27ae60; color: white;'; $status_text = 'Đã hoàn trả'; }
                                elseif($status == 6) { $badge_style = 'background: #c0392b; color: white;'; $status_text = 'Từ chối trả'; }
                            ?>
                            <span style="display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-align: center; white-space: nowrap; <?= $badge_style ?>">
                                <?= $status_text ?>
                            </span>
                        </td>

                        <td style="text-align: center;">
                            <form action="" method="POST" style="display: flex; gap: 5px; justify-content: center;">
                                <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                <select name="status" style="padding: 5px; border: 1px solid #ddd; border-radius: 4px; outline: none; background: #fff;">
                                    <option value="0" <?= $status == 0 ? 'selected' : '' ?>>Chờ xác nhận</option>
                                    <option value="1" <?= $status == 1 ? 'selected' : '' ?>>Đang giao</option>
                                    <option value="2" <?= $status == 2 ? 'selected' : '' ?>>Đã giao</option>
                                    <option value="3" <?= $status == 3 ? 'selected' : '' ?>>Đã hủy</option>
                                    <option value="4" <?= $status == 4 ? 'selected' : '' ?>>Chờ duyệt đổi trả</option>
                                    <option value="5" <?= $status == 5 ? 'selected' : '' ?>>Chấp nhận hoàn trả (Hoàn kho)</option>
                                    <option value="6" <?= $status == 6 ? 'selected' : '' ?>>Từ chối hoàn trả</option>
                                </select>
                                <button type="submit" name="update_status" style="background: #D4A373; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-weight: bold;">Lưu</button>
                            </form>
                        </td>

                        <td>
                            <a href="order_detail.php?id=<?= $row['id'] ?>" class="btn-view" style="display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; background: #34495e; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px;">
                                <i class="fa-solid fa-eye"></i> Xem
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px; color: #777;">
                        Chưa có đơn hàng nào trên hệ thống.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
