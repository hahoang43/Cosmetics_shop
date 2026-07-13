<?php 
require_once '../config/database.php';
$conn = getDatabase();
require_once '../includes/admin_header.php'; 

// --- XỬ LÝ TẢI BANNER MỚI LÊN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['banner_image'])) {
    if ($_FILES['banner_image']['error'] == 0) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $file_name = $_FILES['banner_image']['name'];
        $file_tmp = $_FILES['banner_image']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = 'banner_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
            
            // Đảm bảo bạn đã tạo thư mục này trong dự án nhé
            $upload_dir = '../assets/uploads/banners/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                $stmt = $conn->prepare("INSERT INTO Banner (image) VALUES (?)");
                $stmt->execute([$new_file_name]);
                echo "<script>Swal.fire({ icon: 'success', title: 'Thành công', text: 'Thêm Banner thành công!' }).then(function() { window.location.href='banners.php'; });</script>";
                exit;
            } else {
                echo "<script>Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Không thể lưu file!' });</script>";
            }
        } else {
            echo "<script>Swal.fire({ icon: 'warning', title: 'Định dạng ảnh không hợp lệ', text: 'Vui lòng chọn đúng định dạng ảnh.' });</script>";
        }
    }
}

// --- XỬ LÝ XÓA BANNER ---
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    
    // Xóa file ảnh vật lý trong thư mục trước
    $stmt_img = $conn->prepare("SELECT image FROM Banner WHERE id = ?");
    $stmt_img->execute([$del_id]);
    $img_to_delete = $stmt_img->fetchColumn();
    if ($img_to_delete && file_exists('../assets/uploads/banners/' . $img_to_delete)) {
        unlink('../assets/uploads/banners/' . $img_to_delete);
    }

    // Xóa khỏi Database
    $stmt_del = $conn->prepare("DELETE FROM Banner WHERE id = ?");
    $stmt_del->execute([$del_id]);
    echo "<script>Swal.fire({ icon: 'success', title: 'Thành công', text: 'Đã xóa Banner!' }).then(function() { window.location.href='banners.php'; });</script>";
    exit;
}

// LẤY DANH SÁCH BANNER
$stmt_banners = $conn->query("SELECT * FROM Banner ORDER BY id DESC");
$banners = $stmt_banners->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 class="page-title" style="margin: 0; border: none;">Quản lý Banner Trang chủ</h1>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
    
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); align-self: start;">
        <h3 style="margin-bottom: 15px;">Tải Banner mới</h3>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="file" name="banner_image" accept="image/*" required style="width: 100%; padding: 10px; border: 1px dashed #D4A373; border-radius: 4px; margin-bottom: 15px; background: #fdfaf6;">
            <p style="font-size: 13px; color: #777; margin-bottom: 15px;">* Khuyên dùng ảnh kích thước ngang (VD: 1200x450 px) để hiển thị đẹp nhất.</p>
            <button type="submit" style="background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; width: 100%;">
                <i class="fa-solid fa-cloud-arrow-up"></i> Tải lên ngay
            </button>
        </form>
    </div>

    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 15px;">Danh sách Banner hiện tại</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Hình ảnh</th>
                    <th style="text-align: center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($banners) > 0): ?>
                    <?php foreach($banners as $b): ?>
                        <tr>
                            <td>
                                <img src="/Cosmetics_shop/assets/uploads/banners/<?= htmlspecialchars($b['image']) ?>" style="max-width: 300px; max-height: 120px; border-radius: 5px; border: 1px solid #ddd; object-fit: cover;" onerror="this.onerror=null;this.src='<?= htmlspecialchars(noImageSrc('No Image')) ?>';">
                            </td>
                            <td style="text-align: center; vertical-align: middle;">
                                <button type="button" class="btn-action" style="background: #e74c3c; color: white;" onclick="confirmAdminAction('Bạn có chắc muốn xóa Banner này?', function() { window.location.href='banners.php?delete_id=<?= $b['id'] ?>'; }); return false;">
                                    <i class="fa-solid fa-trash-can"></i> Xóa
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2" style="text-align: center; padding: 20px; color: #999;">Chưa có Banner nào. Hãy tải lên nhé!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require_once '../includes/admin_footer.php'; ?>