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
        echo "<script>Swal.fire({ icon: 'success', title: 'Thành công', text: 'Đã gửi câu trả lời thành công!' }).then(function() { window.location.href='qa.php'; });</script>";
        exit;
    } catch(PDOException $e) {
        echo "<script>Swal.fire({ icon: 'error', title: 'Lỗi trả lời', text: '" . addslashes($e->getMessage()) . "' });</script>";
    }
}

// --- 2. XỬ LÝ XÓA CÂU HỎI ---
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    try {
        $stmt_del = $conn->prepare("DELETE FROM Product_QA WHERE id = ?");
        $stmt_del->execute([$del_id]);
        echo "<script>Swal.fire({ icon: 'success', title: 'Thành công', text: 'Đã xóa câu hỏi thành công!' }).then(function() { window.location.href='qa.php'; });</script>";
        exit;
    } catch(PDOException $e) {
        echo "<script>Swal.fire({ icon: 'error', title: 'Lỗi xóa', text: '" . addslashes($e->getMessage()) . "' });</script>";
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

<style>
    .qa-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .qa-title-block {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .qa-subtitle {
        color: #6b7280;
        font-size: 14px;
    }

    .qa-count {
        background: linear-gradient(135deg, #2c3e50, #34495e);
        color: #fff;
        padding: 10px 16px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 700;
        box-shadow: 0 8px 20px rgba(44, 62, 80, 0.16);
        white-space: nowrap;
    }

    .qa-list {
        display: grid;
        gap: 16px;
    }

    .qa-card {
        background: #fff;
        border: 1px solid #edf0f4;
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .qa-card.unanswered {
        border-color: #f3dfc5;
        background: linear-gradient(180deg, #fffdf8 0%, #fff 18%);
    }

    .qa-card-header {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        padding: 16px 18px;
        border-bottom: 1px solid #eef2f7;
        background: linear-gradient(180deg, rgba(44, 62, 80, 0.03), rgba(44, 62, 80, 0));
    }

    .qa-product {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .qa-product img {
        width: 56px;
        height: 56px;
        object-fit: cover;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        background: #fff;
        flex: 0 0 auto;
    }

    .qa-product-info {
        min-width: 0;
    }

    .qa-product-name {
        font-size: 16px;
        font-weight: 800;
        color: #1f2937;
        line-height: 1.35;
        margin-bottom: 4px;
    }

    .qa-meta {
        color: #6b7280;
        font-size: 13px;
    }

    .qa-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .qa-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }

    .qa-badge.pending {
        background: #fff3e6;
        color: #c76a0a;
    }

    .qa-badge.done {
        background: #e9f8f0;
        color: #1f7a4c;
    }

    .qa-card-body {
        display: grid;
        grid-template-columns: 1.1fr 1.2fr;
        gap: 16px;
        padding: 18px;
    }

    .qa-panel {
        border: 1px solid #eef2f7;
        border-radius: 14px;
        padding: 14px;
        background: #f8fafc;
    }

    .qa-panel-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #334155;
        margin-bottom: 10px;
    }

    .qa-question {
        color: #111827;
        font-size: 15px;
        line-height: 1.7;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .qa-answer-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .qa-answer-form textarea {
        width: 100%;
        min-height: 128px;
        padding: 14px 15px;
        border: 1px solid #dbe3ea;
        border-radius: 12px;
        background: #fff;
        font-family: inherit;
        font-size: 14px;
        line-height: 1.6;
        resize: vertical;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
    }

    .qa-answer-form textarea:focus {
        border-color: #D4A373;
        box-shadow: 0 0 0 4px rgba(212, 163, 115, 0.15);
    }

    .qa-answer-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .qa-hint {
        color: #6b7280;
        font-size: 12px;
    }

    .qa-submit {
        border: none;
        color: #fff;
        padding: 10px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        transition: transform .15s ease, box-shadow .2s ease, opacity .2s ease;
        box-shadow: 0 8px 18px rgba(230, 126, 34, 0.18);
    }

    .qa-submit:hover {
        transform: translateY(-1px);
        opacity: 0.96;
    }

    .qa-delete {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #e74c3c;
        color: #fff;
        padding: 10px 14px;
        border-radius: 10px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 800;
        box-shadow: 0 8px 18px rgba(231, 76, 60, 0.18);
        white-space: nowrap;
        transition: transform .15s ease, opacity .2s ease;
    }

    .qa-delete:hover {
        transform: translateY(-1px);
        opacity: 0.96;
    }

    .qa-empty {
        padding: 56px 20px;
        text-align: center;
        color: #6b7280;
        background: #fff;
        border: 1px dashed #d6dde6;
        border-radius: 18px;
    }

    .qa-empty i {
        font-size: 34px;
        color: #cbd5e1;
        margin-bottom: 14px;
    }

    @media (max-width: 1100px) {
        .qa-card-body {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 720px) {
        .qa-card-header,
        .qa-answer-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .qa-badges {
            justify-content: flex-start;
        }

        .qa-delete,
        .qa-submit {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="qa-toolbar">
    <div class="qa-title-block">
        <h1 class="page-title" style="margin: 0; border: none; padding-bottom: 0;">Quản lý Hỏi đáp Sản phẩm</h1>
        <div class="qa-subtitle">Trả lời câu hỏi của khách và xử lý nhanh các câu hỏi cần xóa.</div>
    </div>
    <span class="qa-count">Tổng số câu hỏi: <strong><?= count($list_qa) ?></strong></span>
</div>

<div class="qa-list">
    <?php if(count($list_qa) > 0): ?>
        <?php foreach($list_qa as $row): ?>
            <div class="qa-card <?= empty($row['answer']) ? 'unanswered' : '' ?>">
                <div class="qa-card-header">
                    <div class="qa-product">
                        <img src="<?= htmlspecialchars(imageSrc($row['thumbnail'] ?? '', 'products')) ?>" alt="<?= htmlspecialchars($row['product_title']) ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars(noImageSrc('No Image')) ?>';">
                        <div class="qa-product-info">
                            <div class="qa-product-name"><?= htmlspecialchars($row['product_title']) ?></div>
                            <div class="qa-meta">ID câu hỏi #<?= (int)$row['id'] ?> · <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></div>
                        </div>
                    </div>

                    <div class="qa-badges">
                        <?php if (empty($row['answer'])): ?>
                            <span class="qa-badge pending"><i class="fa-solid fa-clock"></i> Chưa trả lời</span>
                        <?php else: ?>
                            <span class="qa-badge done"><i class="fa-solid fa-circle-check"></i> Đã trả lời</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="qa-card-body">
                    <div class="qa-panel">
                        <div class="qa-panel-title"><i class="fa-solid fa-user-pen"></i> Thông tin câu hỏi</div>
                        <div class="qa-meta" style="margin-bottom: 10px; font-weight: 700; color: #334155;">
                            <?= htmlspecialchars($row['fullname'] ?? 'Khách vãng lai') ?>
                        </div>
                        <div class="qa-question"><?= htmlspecialchars($row['question']) ?></div>
                    </div>

                    <div class="qa-panel" style="background: #fff;">
                        <div class="qa-panel-title"><i class="fa-solid fa-reply"></i> Trả lời của shop</div>
                        <form action="" method="POST" class="qa-answer-form">
                            <input type="hidden" name="qa_id" value="<?= (int)$row['id'] ?>">
                            <textarea name="answer" required placeholder="Nhập câu trả lời tại đây..."><?= htmlspecialchars($row['answer'] ?? '') ?></textarea>
                            <div class="qa-answer-actions">
                                <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
                                    <button type="submit" name="submit_answer" class="qa-submit" style="background: <?= empty($row['answer']) ? '#e67e22' : '#3498db' ?>;">
                                        <?= empty($row['answer']) ? 'Gửi trả lời' : 'Cập nhật' ?>
                                    </button>
                                    <a href="#" onclick="confirmAdminAction('Bạn có chắc chắn muốn xóa câu hỏi này khỏi hệ thống?', function() { window.location.href='qa.php?delete_id=<?= (int)$row['id'] ?>'; }); return false;" class="qa-delete" title="Xóa câu hỏi">
                                        <i class="fa-solid fa-trash-can"></i> Xóa
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="qa-empty">
            <i class="fa-solid fa-comment-slash"></i>
            <div style="font-size: 18px; font-weight: 800; color: #334155; margin-bottom: 6px;">Hiện chưa có câu hỏi nào</div>
            <div>Khách hàng sẽ xuất hiện tại đây khi họ gửi câu hỏi về sản phẩm.</div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/admin_footer.php'; ?>