<?php 
require_once '../config/database.php';
$conn = getDatabase();
require_once '../includes/admin_header.php'; 

// --- XỬ LÝ ĐÁNH DẤU ĐÃ ĐỌC (Đổi status thành 1) ---
if (isset($_GET['read_id'])) {
    $read_id = (int)$_GET['read_id'];
    $conn->prepare("UPDATE FeedBack SET status = 1 WHERE id = ?")->execute([$read_id]);
    header("Location: feedbacks.php");
    exit;
}

// --- XỬ LÝ XÓA PHẢN HỒI ---
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $conn->prepare("DELETE FROM FeedBack WHERE id = ?")->execute([$del_id]);
    echo "<script>Swal.fire({ icon: 'success', title: 'Thành công', text: 'Đã xóa phản hồi!' }).then(function() { window.location.href='feedbacks.php'; });</script>";
    exit;
}

// --- LẤY DANH SÁCH PHẢN HỒI ---
$stmt = $conn->prepare("SELECT * FROM FeedBack ORDER BY id DESC");
$stmt->execute();
$feedbacks = $stmt->fetchAll();
?>

<h1 class="page-title">Hộp thư Phản hồi</h1>

<div class="admin-table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Người gửi</th>
                <th>Thông tin liên hệ</th>
                <th style="width: 40%;">Nội dung tin nhắn</th>
                <th style="text-align: center;">Trạng thái</th>
                <th style="text-align: center;">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($feedbacks) > 0): ?>
                <?php foreach($feedbacks as $row): 
                    // Nối firstname và lastname
                    $fullname = trim($row['firstname'] . ' ' . $row['lastname']);
                    // Trạng thái 0 là Mới, 1 là Đã đọc
                    $is_new = ($row['status'] == 0);
                ?>
                    <tr style="<?= $is_new ? 'background-color: #f0f8ff; font-weight: bold;' : '' ?>">
                        <td><?= htmlspecialchars($fullname) ?></td>
                        <td style="font-size: 13px;">
                            <i class="fa-solid fa-envelope" style="color: #7f8c8d;"></i> <?= htmlspecialchars($row['email']) ?><br>
                            <i class="fa-solid fa-phone" style="color: #7f8c8d; margin-top: 5px;"></i> <?= htmlspecialchars($row['phone_number']) ?>
                        </td>
                        
                        <td style="line-height: 1.5; font-size: 14px;">
                            <strong style="color: #D4A373;"><?= htmlspecialchars($row['subject_name']) ?></strong><br>
                            <span style="<?= !$is_new ? 'color: #555;' : 'color: #333;' ?>">
                                <?= nl2br(htmlspecialchars($row['note'])) ?>
                            </span><br>
                            <small style="color: #999;"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></small>
                        </td>
                        
                        <td style="text-align: center;">
                            <?php if($is_new): ?>
                                <span class="badge" style="background: #e74c3c; font-size: 11px;">MỚI</span>
                            <?php else: ?>
                                <span class="badge" style="background: #95a5a6; font-size: 11px;">Đã đọc</span>
                            <?php endif; ?>
                        </td>

                        <td style="text-align: center;">
                            <div style="display: flex; justify-content: center; gap: 8px;">
                                <?php if($is_new): ?>
                                    <a href="feedbacks.php?read_id=<?= $row['id'] ?>" class="btn-action btn-update" title="Đánh dấu đã đọc">
                                        <i class="fa-solid fa-check"></i>
                                    </a>
                                <?php endif; ?>
                                <button type="button" class="btn-action" style="background: #e74c3c; color: white;" title="Xóa" onclick="confirmDelete(<?= $row['id'] ?>)">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 30px; color: #777;">Hộp thư trống.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function confirmDelete(id) {
    confirmAdminAction('Bạn có chắc chắn muốn xóa tin nhắn này?', function() {
        window.location.href = 'feedbacks.php?delete_id=' + id;
    });
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>