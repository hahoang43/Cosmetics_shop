<?php 
require_once '../config/database.php';
$conn = getDatabase();
require_once '../includes/admin_header.php'; 

// --- XỬ LÝ XÓA BÌNH LUẬN (SPAM) ---
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    try {
        $stmt_del = $conn->prepare("DELETE FROM Product_Review WHERE id = ?");
        $stmt_del->execute([$del_id]);
        echo "<script>Swal.fire({ icon: 'success', title: 'Thành công', text: 'Đã xóa bình luận thành công!' }).then(function() { window.location.href='reviews.php'; });</script>";
        exit;
    } catch(PDOException $e) {
        echo "<script>Swal.fire({ icon: 'error', title: 'Lỗi', text: '" . addslashes($e->getMessage()) . "' });</script>";
    }
}

// --- LẤY DANH SÁCH BÌNH LUẬN ---
// Nối 3 bảng để lấy Tên khách hàng và Tên sản phẩm
$sql = "
    SELECT r.*, u.fullname as user_name, p.title as product_title 
    FROM Product_Review r 
    JOIN User u ON r.user_id = u.id 
    JOIN Product p ON r.product_id = p.id 
    ORDER BY r.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute();
$reviews = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 class="page-title" style="margin: 0; border: none;">Quản lý Đánh giá & Bình luận</h1>
    <span style="background: #3498db; color: #fff; padding: 5px 15px; border-radius: 20px; font-size: 14px;">
        Tổng số: <strong><?= count($reviews) ?></strong> đánh giá
    </span>
</div>

<div class="admin-table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Khách hàng</th>
                <th>Sản phẩm</th>
                <th style="text-align: center;">Đánh giá</th>
                <th style="width: 35%;">Nội dung</th>
                <th>Ngày đăng</th>
                <th style="text-align: center;">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($reviews) > 0): ?>
                <?php foreach($reviews as $row): ?>
                    <tr>
                        <td style="font-weight: bold; color: #2c3e50;"><?= htmlspecialchars($row['user_name']) ?></td>
                        <td style="font-size: 13px; color: #555;"><?= htmlspecialchars($row['product_title']) ?></td>
                        
                        <td style="text-align: center; color: #f1c40f; font-size: 14px;">
                            <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $row['rating'] ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
                                }
                            ?>
                        </td>
                        
                        <td style="line-height: 1.5; font-size: 14px;"><?= nl2br(htmlspecialchars($row['comment'])) ?></td>
                        
                        <td style="color: #7f8c8d; font-size: 13px;">
                            <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                        </td>

                        <td style="text-align: center;">
                            <div style="display: flex; flex-direction: column; gap: 8px; align-items: center;">
                                <a href="../frontend/product_detail.php?id=<?= (int)$row['product_id'] ?>#review-id-<?= (int)$row['id'] ?>" class="btn-action" style="background: #1f7a4c; color: white; text-decoration: none;" title="Xem đánh giá của khách hàng">
                                    <i class="fa-solid fa-eye"></i> Xem
                                </a>
                                <button type="button" class="btn-action" style="background: #e74c3c; color: white;" title="Xóa Spam" onclick="confirmDelete(<?= (int)$row['id'] ?>)">
                                    <i class="fa-solid fa-trash-can"></i> Xóa
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: #777;">
                        Chưa có đánh giá nào trên hệ thống.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function confirmDelete(id) {
    confirmAdminAction('Bạn có chắc chắn muốn xóa bình luận này không?', function() {
        window.location.href = 'reviews.php?delete_id=' + id;
    });
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>