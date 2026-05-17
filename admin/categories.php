<?php 
require_once '../config/database.php';
require_once '../includes/admin_header.php'; 

// --- 1. XỬ LÝ THÊM DANH MỤC MỚI ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    
    if (!empty($name)) {
        try {
            $stmt = $conn->prepare("INSERT INTO Category (name) VALUES (?)");
            $stmt->execute([$name]);
            echo "<script>alert('Thêm danh mục thành công!'); window.location.href='categories.php';</script>";
            exit;
        } catch(PDOException $e) {
            echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "');</script>";
        }
    } else {
        echo "<script>alert('Vui lòng nhập tên danh mục!');</script>";
    }
}

// --- 2. XỬ LÝ XÓA DANH MỤC ---
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    
    // Rất quan trọng: Kiểm tra xem danh mục này có đang chứa sản phẩm nào không
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM Product WHERE category_id = ?");
    $stmt_check->execute([$del_id]);
    $count = $stmt_check->fetchColumn();
    
    if ($count > 0) {
        // Nếu có sản phẩm thì KHÔNG cho xóa để bảo vệ dữ liệu (Ràng buộc toàn vẹn)
        echo "<script>alert('KHÔNG THỂ XÓA! Danh mục này đang chứa $count sản phẩm. Vui lòng xóa hoặc chuyển các sản phẩm đó sang danh mục khác trước.'); window.location.href='categories.php';</script>";
        exit;
    } else {
        try {
            // Nếu trống thì cho phép xóa vĩnh viễn
            $stmt_del = $conn->prepare("DELETE FROM Category WHERE id = ?");
            $stmt_del->execute([$del_id]);
            echo "<script>alert('Đã xóa danh mục!'); window.location.href='categories.php';</script>";
            exit;
        } catch(PDOException $e) {
            echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}

// --- 3. LẤY DANH SÁCH DANH MỤC HIỆN CÓ ---
$stmt = $conn->prepare("SELECT * FROM Category ORDER BY id ASC");
$stmt->execute();
$categories = $stmt->fetchAll();
?>

<h1 class="page-title">Quản lý Danh mục</h1>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start;">
    
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 20px; color: #2c3e50; font-size: 18px;">
            <i class="fa-solid fa-folder-plus"></i> Thêm danh mục
        </h3>
        
        <form action="" method="POST">
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #555;">Tên danh mục <span style="color: red;">*</span></label>
                <input type="text" name="name" required placeholder="VD: Nước hoa nữ..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; outline: none;">
            </div>
            
            <button type="submit" name="add_category" style="width: 100%; background: #D4A373; color: white; border: none; padding: 10px; font-weight: bold; border-radius: 5px; cursor: pointer; transition: 0.3s;" onmouseover="this.style.background='#c2905f'" onmouseout="this.style.background='#D4A373'">
                LƯU DANH MỤC
            </button>
        </form>
    </div>

    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Tên danh mục</th>
                    <th style="text-align: right; width: 150px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($categories) > 0): ?>
                    <?php foreach($categories as $row): ?>
                        <tr>
                            <td><strong>#<?= $row['id'] ?></strong></td>
                            <td style="font-weight: 500; font-size: 15px;"><?= htmlspecialchars($row['name']) ?></td>
                            
                            <td style="text-align: right;">
                                <button type="button" class="btn-action" style="background: #e74c3c; color: white;" title="Xóa danh mục" onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['name'])) ?>')">
                                    <i class="fa-solid fa-trash-can"></i> Xóa
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 20px; color: #777;">Chưa có danh mục nào.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
function confirmDelete(id, name) {
    if(confirm('Bạn có chắc chắn muốn xóa danh mục "' + name + '" không?')) {
        window.location.href = 'categories.php?delete_id=' + id;
    }
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>