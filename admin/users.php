<?php
require_once '../config/database.php';
$conn = getDatabase();
require_once '../includes/admin_header.php';

// --- XỬ LÝ XÓA TÀI KHOẢN ---
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    
    // Kiểm tra ràng buộc: Khách hàng này đã từng đặt hàng chưa?
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM Orders WHERE user_id = ?");
    $stmt_check->execute([$del_id]);
    $count = $stmt_check->fetchColumn();
    
    if ($count > 0) {
        // Nếu đã có đơn hàng thì không được xóa để giữ lại lịch sử đối soát
        echo "<script>Swal.fire({ icon: 'warning', title: 'Không thể xóa', text: 'KHÔNG THỂ XÓA! Khách hàng này đã có lịch sử mua $count đơn hàng trên hệ thống.' }).then(function() { window.location.href='users.php'; });</script>";
        exit;
    } else {
        try {
            $stmt_del = $conn->prepare("DELETE FROM User WHERE id = ?");
            $stmt_del->execute([$del_id]);
            echo "<script>Swal.fire({ icon: 'success', title: 'Thành công', text: 'Đã xóa tài khoản khách hàng thành công!' }).then(function() { window.location.href='users.php'; });</script>";
            exit;
        } catch(PDOException $e) {
            echo "<script>Swal.fire({ icon: 'error', title: 'Lỗi', text: '" . addslashes($e->getMessage()) . "' });</script>";
        }
    }
}

// --- TÌM KIẾM KHÁCH HÀNG ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = [];
$where_clause = 'WHERE role = 0 AND deleted = 0';

if (!empty($search)) {
    $where_clause .= " AND (fullname LIKE ? OR email LIKE ? OR phone_number LIKE ?)";
    $search_param = ["%$search%", "%$search%", "%$search%"];
}

// --- LẤY DANH SÁCH KHÁCH HÀNG ---
// Giả sử bảng User của bạn có các cột: id, fullname, email, phone_number, created_at
$stmt = $conn->prepare("SELECT * FROM User $where_clause ORDER BY id DESC");
$stmt->execute($search_param);
$users = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 class="page-title" style="margin: 0; border: none;">Quản lý Khách hàng</h1>
    <span style="background: #2ecc71; color: #fff; padding: 5px 15px; border-radius: 20px; font-size: 14px;">
        Tổng thành viên: <strong><?= count($users) ?></strong>
    </span>
</div>

<!-- Search Box -->
<div style="margin-bottom: 20px;">
    <form method="GET" style="display: flex; gap: 10px; align-items: center;">
        <label for="admin-users-search" style="position: absolute; left: -9999px;">Tìm kiếm khách hàng</label>
        <input type="text" id="admin-users-search" name="search" placeholder="Tìm kiếm theo tên, email hoặc số điện thoại..." value="<?= htmlspecialchars($search) ?>"
               style="flex: 1; padding: 10px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
        <button type="submit" style="background: #2ecc71; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
            <i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm
        </button>
        <?php if(!empty($search)): ?>
            <a href="users.php" style="background: #95a5a6; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-weight: bold;">
                <i class="fa-solid fa-xmark"></i> Xóa lọc
            </a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Họ và Tên</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Ngày đăng ký</th>
                <th style="text-align: center;">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($users) > 0): ?>
                <?php foreach($users as $row): ?>
                    <tr>
                        <td><strong>#<?= $row['id'] ?></strong></td>
                        <td style="font-weight: 500; color: #2c3e50;">
                            <i class="fa-solid fa-user-circle" style="color: #bdc3c7; margin-right: 5px; font-size: 18px; vertical-align: middle;"></i>
                            <?= htmlspecialchars($row['fullname']) ?>
                        </td>
                        <td><?= !empty($row['email']) ? htmlspecialchars($row['email']) : '<span style="color:#999; font-style:italic;">Chưa cập nhật</span>' ?></td>
                        <td><?= !empty($row['phone_number']) ? htmlspecialchars($row['phone_number']) : '<span style="color:#999; font-style:italic;">Chưa cập nhật</span>' ?></td>
                        
                        <td style="color: #7f8c8d; font-size: 14px;">
                            <?= isset($row['created_at']) ? date('d/m/Y', strtotime($row['created_at'])) : '--/--/----' ?>
                        </td>

                        <td style="text-align: center;">
                            <button type="button" class="btn-action" style="background: #e74c3c; color: white;" title="Xóa tài khoản" onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['fullname'])) ?>')">
                                <i class="fa-solid fa-user-xmark"></i> Xóa
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #777;">
                        <i class="fa-solid fa-users-slash" style="font-size: 30px; color: #ddd; margin-bottom: 10px; display: block;"></i>
                        Chưa có khách hàng nào đăng ký trên hệ thống.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function confirmDelete(id, name) {
    confirmAdminAction('Bạn có chắc chắn muốn xóa tài khoản của khách hàng "' + name + '" không? Hành động này không thể hoàn tác!', function() {
        window.location.href = 'users.php?delete_id=' + id;
    });
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>
