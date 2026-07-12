<?php 
require_once '../config/database.php';
$conn = getDatabase();
require_once '../includes/admin_header.php'; 

// --- 1. XỬ LÝ DUYỆT / TRẢ LỜI CÂU HỎI ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_answer'])) {
    $qa_id = (int)$_POST['qa_id'];
    $answer = trim($_POST['answer']);

    try {
        $stmt_ans = $conn->prepare("UPDATE Product_QA SET answer = ? WHERE id = ?");
        $stmt_ans->execute([$answer, $qa_id]);
        echo "<script>alert('Đã gửi câu trả lời thành công!'); window.location.href='qa.php';</script>";
        exit;
    } catch(PDOException $e) {
        echo "<script>alert('Lỗi trả lời: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// --- 2. XỬ LÝ XÓA CÂU HỎI ---
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    try {
        $stmt_del = $conn->prepare("DELETE FROM Product_QA WHERE id = ?");
        $stmt_del->execute([$del_id]);
        echo "<script>alert('Đã xóa câu hỏi thành công!'); window.location.href='qa.php';</script>";
        exit;
    } catch(PDOException $e) {
        echo "<script>alert('Lỗi xóa: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// --- 3. LẤY DANH SÁCH CÂU HỎI (JOIN SẢN PHẨM & USER) ---
$sql = "
    SELECT qa.*, p.title AS product_title, p.thumbnail, u.fullname 
    FROM Product_QA qa
    JOIN Product p ON qa.product_id = p.id
    LEFT JOIN User u ON qa.user_id = u.id
    ORDER BY qa.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute();
$list_qa = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 class="page-title" style="margin: 0; border: none;">Quản lý Hỏi đáp Sản phẩm</h1>
    <span style="background: #2c3e50; color: #fff; padding: 5px 15px; border-radius: 20px; font-size: 14px;">
        Tổng số câu hỏi: <strong><?= count($list_qa) ?></strong>
    </span>
</div>

<div class="admin-table-container">
    <table class="admin-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #2c3e50; color: white;">
                <th style="padding: 12px; width: 25%;">Sản phẩm</th>
                <th style="padding: 12px; width: 20%;">Người hỏi / Thời gian</th>
                <th style="padding: 12px; width: 25%;">Nội dung câu hỏi</th>
                <th style="padding: 12px; width: 22%;">Trả lời của Shop</th>
                <th style="padding: 12px; width: 8%; text-align: center;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($list_qa) > 0): ?>
                <?php foreach($list_qa as $row): ?>
                    <tr style="border-bottom: 1px solid #eee; background: <?= empty($row['answer']) ? '#fffdf6' : '#fff' ?>;">
                        <td style="padding: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="<?= htmlspecialchars(imageSrc($row['thumbnail'] ?? '', 'products')) ?>" width="45" style="border-radius: 4px; border: 1px solid #ddd;" onerror="this.onerror=null;this.src='<?= htmlspecialchars(noImageSrc('No Image')) ?>';">
                                <span style="font-size: 13px; font-weight: bold; color: #333; max-width: 180px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= htmlspecialchars($row['product_title']) ?>
                                </span>
                            </div>
                        </td>

                        <td style="padding: 12px; font-size: 13px;">
                            <strong><?= htmlspecialchars($row['fullname'] ?? 'Khách vãng lai') ?></strong><br>
                            <span style="color: #999; font-size: 11px;"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></span>
                        </td>

                        <td style="padding: 12px; font-size: 13px; color: #2c3e50; line-height: 1.4;">
                            <?= htmlspecialchars($row['question']) ?>
                        </td>

                        <td style="padding: 12px;">
                            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 5px;">
                                <input type="hidden" name="qa_id" value="<?= $row['id'] ?>">
                                <textarea name="answer" required placeholder="Nhập câu trả lời tại đây..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; font-size: 13px; resize: vertical; min-height: 45px;"><?= htmlspecialchars($row['answer'] ?? '') ?></textarea>
                                <button type="submit" name="submit_answer" style="align-self: flex-end; background: <?= empty($row['answer']) ? '#e67e22' : '#3498db' ?>; color: white; border: none; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; transition: 0.2s;">
                                    <?= empty($row['answer']) ? 'Gửi trả lời' : 'Cập nhật' ?>
                                </button>
                            </form>
                        </td>

                        <td style="padding: 12px; text-align: center; vertical-align: middle;">
                            <a href="qa.php?delete_id=<?= $row['id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa câu hỏi này khỏi hệ thống?')" style="background: #e74c3c; color: white; padding: 6px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; display: inline-block;" title="Xóa câu hỏi">
                                <i class="fa-solid fa-trash-can"></i> Xóa
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px; color: #777; font-style: italic;">
                        <i class="fa-solid fa-comment-slash" style="font-size: 32px; color: #ccc; margin-bottom: 10px;"></i><br>
                        Hiện tại chưa nhận được câu hỏi nào từ khách hàng.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/admin_footer.php'; ?>