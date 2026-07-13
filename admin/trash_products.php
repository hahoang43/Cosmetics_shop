<?php
/** @var PDO|null $conn */
require_once '../config/database.php';
$conn = $conn ?? null;
require_once '../includes/admin_header.php';

if (!($conn instanceof PDO)) {
    throw new UnexpectedValueException('Kết nối cơ sở dữ liệu không hợp lệ.');
}

function deleteProductsPermanently(PDO $conn, array $productIds, bool $force = false): array {
    $productIds = array_values(array_filter(array_map('intval', $productIds)));
    if (empty($productIds)) {
        return ['deleted_products' => 0, 'skipped_products' => 0];
    }

    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $lockedIds = [];
    if (!$force) {
        // Ignore delivered orders (status = 2) when checking locks — products
        // that only appear in delivered orders should be deletable.
        $stmtCheck = $conn->prepare(
            "SELECT DISTINCT od.product_id FROM Order_Details od JOIN Orders o ON od.order_id = o.id WHERE od.product_id IN ($placeholders) AND o.status != 2"
        );
        $stmtCheck->execute($productIds);
        $lockedIds = array_map('intval', $stmtCheck->fetchAll(PDO::FETCH_COLUMN));
    }
    $deletableIds = array_values(array_diff($productIds, $lockedIds));

    if (empty($deletableIds)) {
        return ['deleted_products' => 0, 'skipped_products' => count($lockedIds)];
    }

    $deletePlaceholders = implode(',', array_fill(0, count($deletableIds), '?'));

    $conn->beginTransaction();
    try {
        // Remove any Order_Details for deletable products first. This is safe
        // because deletable products either have no orders, or only orders with
        // status = 2 (delivered), which we allow to be removed when deleting
        // the product itself.
        $stmt = $conn->prepare("DELETE FROM Order_Details WHERE product_id IN ($deletePlaceholders)");
        $stmt->execute($deletableIds);
        $deletedOrderDetails = $stmt->rowCount();

        $stmt = $conn->prepare("DELETE FROM Product_Review WHERE product_id IN ($deletePlaceholders)");
        $stmt->execute($deletableIds);
        $deletedReviews = $stmt->rowCount();

        $stmt = $conn->prepare("DELETE FROM Product_QA WHERE product_id IN ($deletePlaceholders)");
        $stmt->execute($deletableIds);
        $deletedQa = $stmt->rowCount();

        $stmt = $conn->prepare("DELETE FROM Galery WHERE product_id IN ($deletePlaceholders)");
        $stmt->execute($deletableIds);
        $deletedGallery = $stmt->rowCount();

        $stmt = $conn->prepare("DELETE FROM Product_Variant WHERE product_id IN ($deletePlaceholders)");
        $stmt->execute($deletableIds);
        $deletedVariants = $stmt->rowCount();

        $stmt = $conn->prepare("DELETE FROM Product WHERE id IN ($deletePlaceholders)");
        $stmt->execute($deletableIds);
        $deletedProducts = $stmt->rowCount();

        $stmt = $conn->query("DELETE FROM Category WHERE id NOT IN (SELECT DISTINCT category_id FROM Product WHERE category_id IS NOT NULL)");
        $deletedCategories = $stmt->rowCount();

        $stmt = $conn->query("DELETE FROM Variant WHERE id NOT IN (SELECT DISTINCT variant_id FROM Product_Variant WHERE variant_id IS NOT NULL)");
        $deletedVariantNames = $stmt->rowCount();

        $conn->commit();

        return [
            'deleted_products' => $deletedProducts,
            'deleted_reviews' => $deletedReviews,
            'deleted_qa' => $deletedQa,
            'deleted_gallery' => $deletedGallery,
            'deleted_variants' => $deletedVariants,
            'deleted_categories' => $deletedCategories,
            'deleted_variant_names' => $deletedVariantNames,
              'skipped_products' => count($lockedIds),
              'skipped_ids' => $lockedIds,
              'deleted_order_details' => $deletedOrderDetails ?? 0,
        ];
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw $e;
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_restore'])) {
    $selectedIds = array_values(array_filter(array_map('intval', (array)($_POST['selected_ids'] ?? []))));
    if (!empty($selectedIds)) {
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
        $stmt = $conn->prepare("UPDATE Product SET deleted = 0 WHERE id IN ($placeholders)");
        $stmt->execute($selectedIds);
        $message = 'Đã khôi phục ' . $stmt->rowCount() . ' sản phẩm.';
    } else {
        $message = 'Vui lòng chọn ít nhất một sản phẩm để khôi phục.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete_permanent'])) {
    $selectedIds = array_values(array_filter(array_map('intval', (array)($_POST['selected_ids'] ?? []))));
    if (!empty($selectedIds)) {
        try {
            $force = isset($_POST['force_delete']) && $_POST['force_delete'] == '1';
            $result = deleteProductsPermanently($conn, $selectedIds, $force);
            $message = 'Đã xóa vĩnh viễn ' . $result['deleted_products'] . ' sản phẩm.';
            if (!empty($result['skipped_products']) && !$force) {
                $skipped_ids = $result['skipped_ids'] ?? [];
                $message .= ' Bỏ qua ' . $result['skipped_products'] . ' sản phẩm đã phát sinh đơn hàng.';
            }
            if (!empty($result['skipped_products']) && $force) {
                $message .= ' (Đã buộc xóa cả dữ liệu đơn hàng liên quan.)';
            }
        } catch (Throwable $e) {
            $message = 'Lỗi xóa vĩnh viễn: ' . $e->getMessage();
        }
    } else {
        $message = 'Vui lòng chọn ít nhất một sản phẩm để xóa vĩnh viễn.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_id'])) {
    $restoreId = (int)($_POST['restore_id'] ?? 0);
    if ($restoreId > 0) {
        $stmt = $conn->prepare('UPDATE Product SET deleted = 0 WHERE id = ?');
        $stmt->execute([$restoreId]);
        $message = 'Đã khôi phục sản phẩm.';
    } else {
        $message = 'ID sản phẩm không hợp lệ.';
    }
} elseif (isset($_GET['restore_id'])) {
    $restoreId = (int)$_GET['restore_id'];
    $stmt = $conn->prepare('UPDATE Product SET deleted = 0 WHERE id = ?');
    $stmt->execute([$restoreId]);
    $message = 'Đã khôi phục sản phẩm.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)($_POST['delete_id'] ?? 0);
    if ($deleteId > 0) {
        try {
            $force = isset($_POST['force_delete']) && $_POST['force_delete'] == '1';
            $result = deleteProductsPermanently($conn, [$deleteId], $force);
            $message = $result['deleted_products'] > 0 ? 'Đã xóa vĩnh viễn sản phẩm.' : 'Không thể xóa vĩnh viễn sản phẩm vì đã phát sinh đơn hàng.';
            if (!empty($result['skipped_products']) && !$force) {
                $skipped_ids = $result['skipped_ids'] ?? [];
            }
            if (!empty($result['skipped_products']) && $force) {
                $message .= ' (Đã buộc xóa cả dữ liệu đơn hàng liên quan.)';
            }
        } catch (Throwable $e) {
            $message = 'Lỗi xóa vĩnh viễn: ' . $e->getMessage();
        }
    } else {
        $message = 'ID sản phẩm không hợp lệ.';
    }
}

$stmt = $conn->query("SELECT p.*, c.name AS category_name, COALESCE(SUM(pv.quantity), 0) AS total_stock FROM Product p LEFT JOIN Category c ON p.category_id = c.id LEFT JOIN Product_Variant pv ON p.id = pv.product_id WHERE p.deleted = 1 GROUP BY p.id ORDER BY p.id DESC");
$products = $stmt->fetchAll();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; gap: 12px; flex-wrap: wrap;">
    <div>
        <h1 class="page-title" style="margin:0; border:none;">Thùng rác sản phẩm</h1>
        <div style="color:#666; font-size:14px; margin-top:4px;">Các sản phẩm đã chuyển sang thùng rác</div>
    </div>
    <a href="products.php" style="background:#3498db; color:white; padding:10px 16px; text-decoration:none; border-radius:5px; font-weight:bold; font-size:14px;">
        <i class="fa-solid fa-box-open"></i> Quay lại danh sách sản phẩm
    </a>
</div>

<?php if ($message): ?>
    <div style="padding:12px 14px; background:#f3f4f6; border-radius:8px; margin-bottom:16px; color:#374151; border:1px solid #e5e7eb;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>
<?php if (!empty($skipped_ids) && is_array($skipped_ids)): ?>
    <div style="padding:12px 14px; background:#fff7f7; border-radius:8px; margin-bottom:16px; color:#7f1d1d; border:1px solid #fecaca;">
        <p><strong>Thông báo:</strong> Một số sản phẩm không thể xóa vĩnh viễn vì đã có lịch sử đơn hàng. Nếu bạn chắc chắn muốn xóa mọi dữ liệu liên quan (bao gồm chi tiết đơn hàng), hãy sử dụng tùy chọn buộc xóa bên dưới. Hành động này sẽ làm mất lịch sử giao dịch.</p>
        <form method="POST" style="margin-top:8px;">
            <?php foreach ($skipped_ids as $sid): ?>
                <input type="hidden" name="selected_ids[]" value="<?= (int)$sid ?>">
            <?php endforeach; ?>
            <input type="hidden" name="bulk_delete_permanent" value="1">
            <input type="hidden" name="force_delete" value="1">
            <button type="submit" style="background:#b91c1c; color:white; border:none; padding:8px 12px; border-radius:6px; font-weight:bold;" onclick="event.preventDefault(); confirmAdminAction('Xác nhận buộc xóa mọi dữ liệu liên quan? Hành động này không thể khôi phục.', function() { this.form.submit(); }.bind(this)); return false;">Buộc xóa các sản phẩm bị khóa</button>
        </form>
    </div>
<?php endif; ?>

<form method="POST" id="trash-bulk-form">
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px; text-align:center;">
                        <input type="checkbox" id="select-all-trash" style="width:16px; height:16px; cursor:pointer;">
                    </th>
                    <th>ID</th>
                    <th>Hình ảnh</th>
                    <th>Tên sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Giá từ</th>
                    <th style="text-align:center;">Kho</th>
                    <th style="text-align:center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $row): ?>
                        <tr>
                            <td style="text-align:center; vertical-align:middle;">
                                <input type="checkbox" name="selected_ids[]" value="<?= (int)$row['id'] ?>" class="trash-checkbox" style="width:16px; height:16px; cursor:pointer;">
                            </td>
                            <td><strong>#<?= (int)$row['id'] ?></strong></td>
                            <td>
                                <img src="<?= htmlspecialchars(imageSrc($row['thumbnail'] ?? '', 'products')) ?>" alt="<?= htmlspecialchars($row['title']) ?>" style="width:60px; height:60px; object-fit:cover; border-radius:5px; border:1px solid #eee;">
                            </td>
                            <td style="max-width:250px; line-height:1.4;">
                                <strong><?= htmlspecialchars($row['title']) ?></strong><br>
                                <span style="color:#999; font-size:12px;">Thương hiệu: <?= htmlspecialchars($row['brand']) ?></span>
                            </td>
                            <td>
                                <span style="background:#f0f0f0; padding:5px 10px; border-radius:4px; font-size:13px;">
                                    <?= htmlspecialchars($row['category_name'] ?? 'Chưa có') ?>
                                </span>
                            </td>
                            <td style="color:#D4A373; font-weight:bold;">
                                <?= number_format((int)$row['price'], 0, ',', '.') ?>đ
                            </td>
                            <td style="text-align:center;">
                                <span style="background:#f8fafc; color:#475569; padding:4px 8px; border-radius:4px; font-weight:bold;">
                                    <?= (int)$row['total_stock'] ?>
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <div style="display:flex; justify-content:center; gap:10px; flex-wrap:wrap;">
                                    <form method="POST" class="inline-action-form" style="display:inline-block; margin:0;">
                                        <input type="hidden" name="restore_id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn-action btn-update confirm-restore" title="Khôi phục">
                                            <i class="fa-solid fa-rotate-left"></i>
                                        </button>
                                    </form>
                                    <form method="POST" class="inline-action-form" style="display:inline-block; margin:0 0 0 6px;">
                                        <input type="hidden" name="delete_id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn-action confirm-delete-permanent" style="background:#e74c3c; color:white;" title="Xóa vĩnh viễn">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:40px; color:#777;">
                            <i class="fa-solid fa-trash-can" style="font-size:30px; margin-bottom:10px; color:#ccc;"></i><br>
                            Thùng rác đang trống.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-top:16px; flex-wrap:wrap;">
        <div style="color:#666; font-size:14px;">
            <strong id="trash-selected-count">0</strong> sản phẩm đã chọn
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button type="submit" name="bulk_restore" value="1" id="bulk-restore-btn" style="background:#2ecc71; color:white; border:none; padding:10px 16px; border-radius:5px; font-weight:bold; cursor:pointer;" disabled>
                <i class="fa-solid fa-rotate-left"></i> Khôi phục đã chọn
            </button>
            <button type="submit" name="bulk_delete_permanent" value="1" id="bulk-delete-permanent-btn" class="confirm-delete-permanent" style="background:#e74c3c; color:white; border:none; padding:10px 16px; border-radius:5px; font-weight:bold; cursor:pointer;" disabled>
                <i class="fa-solid fa-skull-crossbones"></i> Xóa vĩnh viễn đã chọn
            </button>
        </div>
    </div>
</form>

<script>
(function() {
    const selectAll = document.getElementById('select-all-trash');
    const checkboxes = Array.from(document.querySelectorAll('.trash-checkbox'));
    const selectedCount = document.getElementById('trash-selected-count');
    const restoreBtn = document.getElementById('bulk-restore-btn');
    const deleteBtn = document.getElementById('bulk-delete-permanent-btn');
    const form = document.getElementById('trash-bulk-form');

    function updateState() {
        const checkedCount = checkboxes.filter(cb => cb.checked).length;
        selectedCount.textContent = checkedCount;
        restoreBtn.disabled = checkedCount === 0;
        deleteBtn.disabled = checkedCount === 0;
        selectAll.checked = checkedCount > 0 && checkedCount === checkboxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
    }

    selectAll?.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
        updateState();
    });

    checkboxes.forEach(cb => cb.addEventListener('change', updateState));

    form?.addEventListener('submit', function(e) {
        const checkedCount = checkboxes.filter(cb => cb.checked).length;
        if (checkedCount === 0) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Thiếu dữ liệu', text: 'Vui lòng chọn ít nhất một sản phẩm.' });
            return;
        }

        const submitter = document.activeElement;
        const isDelete = submitter && submitter.name === 'bulk_delete_permanent';
        const message = isDelete
            ? 'Bạn có chắc chắn muốn xóa vĩnh viễn ' + checkedCount + ' sản phẩm đã chọn?'
            : 'Bạn có chắc chắn muốn khôi phục ' + checkedCount + ' sản phẩm đã chọn?';

        e.preventDefault();
        confirmAdminAction(message, function() {
            form.submit();
        });
    });

    updateState();
})();
</script>

<script>
// Unified confirmation handler for restore/delete actions on this page
(function(){
    document.addEventListener('click', function(e){
        const btn = e.target.closest && e.target.closest('button');
        if(!btn) return;

        // Permanent delete (per-row or bulk)
        if (btn.classList.contains('confirm-delete-permanent')) {
            // Determine selected count for bulk button
            const bulkForm = document.getElementById('trash-bulk-form');
            if (btn.id === 'bulk-delete-permanent-btn' && bulkForm) {
                const checked = Array.from(document.querySelectorAll('.trash-checkbox')).filter(cb=>cb.checked).length;
                if (checked === 0) {
                    e.preventDefault();
                    Swal.fire({ icon: 'warning', title: 'Thiếu dữ liệu', text: 'Vui lòng chọn ít nhất một sản phẩm.' });
                    return;
                }
                e.preventDefault();
                confirmAdminAction('Bạn có chắc chắn muốn xóa vĩnh viễn ' + checked + ' sản phẩm đã chọn? Hành động không thể hoàn tác.', function() {
                    bulkForm.submit();
                });
                return;
            }

            // per-row delete button inside its own form
            e.preventDefault();
            confirmAdminAction('Xóa vĩnh viễn sản phẩm này? Hành động không thể hoàn tác.', function() {
                btn.closest('form')?.submit();
            });
        }

        // Restore confirmation
        if (btn.classList.contains('confirm-restore')) {
            e.preventDefault();
            confirmAdminAction('Khôi phục sản phẩm này?', function() {
                btn.closest('form')?.submit();
            });
        }
    });
})();
</script>

<?php include_once '../includes/admin_footer.php'; ?>

