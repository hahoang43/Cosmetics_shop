<?php 
require_once '../config/database.php';
require_once '../includes/admin_header.php';

$stmt_cat = $conn->query("SELECT * FROM Category");
$categories = $stmt_cat->fetchAll();

$stmt_var = $conn->query("SELECT * FROM Variant ORDER BY name ASC");
$all_variants = $stmt_var->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $brand = trim($_POST['brand']);
    $category_id = (int)$_POST['category_id'];
    $price = (float)$_POST['price']; 
    $old_price = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : 0;
    $description = trim($_POST['description']);
    $thumbnail = ''; 
    
    // Xử lý Ảnh đại diện chính (Thumbnail)
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $file_name = $_FILES['thumbnail']['name'];
        $file_tmp = $_FILES['thumbnail']['tmp_name'];
        $new_file_name = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $file_name);
        if (move_uploaded_file($file_tmp, '../assets/uploads/products/' . $new_file_name)) {
            $thumbnail = $new_file_name; 
        }
    }

    if (!empty($title) && $price > 0 && $category_id > 0) {
        try {
            $conn->beginTransaction();

            // 1. Thêm sản phẩm cơ bản
            $sql = "INSERT INTO Product (category_id, title, price, old_price, thumbnail, description, brand) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$category_id, $title, $price, $old_price, $thumbnail, $description, $brand]);
            $product_id = $conn->lastInsertId();

            // 2. Xử lý Phân loại hàng thông minh & Ảnh riêng từng biến thể
            $variant_names = $_POST['variant_names'] ?? []; 
            $var_prices = $_POST['var_prices'] ?? [];
            $var_old_prices = $_POST['var_old_prices'] ?? [];
            $var_qtys = $_POST['var_qtys'] ?? [];

            for ($i = 0; $i < count($variant_names); $i++) {
                $vname = trim($variant_names[$i]);
                if (!empty($vname)) {
                    $vprice = (float)$var_prices[$i];
                    $vold = (float)$var_old_prices[$i];
                    $vqty = (int)$var_qtys[$i];
                    $var_img_name = null;

                    // Xử lý upload ảnh riêng cho phân loại này (nếu có)
                    if (isset($_FILES['var_thumbnails']['error'][$i]) && $_FILES['var_thumbnails']['error'][$i] == 0) {
                        $vt_name = $_FILES['var_thumbnails']['name'][$i];
                        $vt_tmp = $_FILES['var_thumbnails']['tmp_name'][$i];
                        $vt_ext = strtolower(pathinfo($vt_name, PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                        
                        if (in_array($vt_ext, $allowed)) {
                            $var_img_name = 'var_' . time() . '_' . rand(1000, 9999) . '.' . $vt_ext;
                            move_uploaded_file($vt_tmp, '../assets/uploads/products/' . $var_img_name);
                        }
                    }

                    $stmt_check = $conn->prepare("SELECT id FROM Variant WHERE name = ?");
                    $stmt_check->execute([$vname]);
                    $v_row = $stmt_check->fetch();

                    if ($v_row) {
                        $vid = $v_row['id'];
                    } else {
                        $stmt_in_v = $conn->prepare("INSERT INTO Variant (name) VALUES (?)");
                        $stmt_in_v->execute([$vname]);
                        $vid = $conn->lastInsertId();
                    }

                    // ĐÃ SỬA: Lưu thêm biến $var_img_name vào cột thumbnail
                    $stmt_pv = $conn->prepare("INSERT INTO Product_Variant (product_id, variant_id, price, old_price, quantity, thumbnail) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt_pv->execute([$product_id, $vid, $vprice, $vold, $vqty, $var_img_name]);
                }
            }

            // 3. Xử lý Upload nhiều ảnh vào Thư viện ảnh phụ (Galery)
            if (isset($_FILES['gallery_images']) && count($_FILES['gallery_images']['name']) > 0) {
                $gallery_files = $_FILES['gallery_images'];
                $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
                
                for ($i = 0; $i < count($gallery_files['name']); $i++) {
                    if ($gallery_files['error'][$i] == 0) {
                        $g_name = $gallery_files['name'][$i];
                        $g_tmp = $gallery_files['tmp_name'][$i];
                        $g_ext = strtolower(pathinfo($g_name, PATHINFO_EXTENSION));
                        
                        if (in_array($g_ext, $allowed_ext)) {
                            $new_g_name = 'gal_' . time() . '_' . rand(1000, 9999) . '.' . $g_ext;
                            if (move_uploaded_file($g_tmp, '../assets/uploads/products/' . $new_g_name)) {
                                $stmt_gal = $conn->prepare("INSERT INTO Galery (product_id, thumbnail) VALUES (?, ?)");
                                $stmt_gal->execute([$product_id, $new_g_name]);
                            }
                        }
                    }
                }
            }

            $conn->commit();
            echo "<script>alert('Thêm sản phẩm thành công!'); window.location.href='products.php';</script>";
            exit;
        } catch(Exception $e) {
            $conn->rollBack();
            echo "<script>alert('Lỗi Hệ thống: " . addslashes($e->getMessage()) . "');</script>";
        }
    } else {
        echo "<script>alert('Vui lòng nhập đầy đủ Tên, Giá và chọn Danh mục!');</script>";
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 class="page-title" style="margin: 0; border: none;">Thêm Sản phẩm mới</h1>
    <a href="products.php" style="background: #95a5a6; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px;">Quay lại</a>
</div>

<div class="admin-form-container" style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <form action="" method="POST" enctype="multipart/form-data">
        <h3 style="margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;">1. Thông tin chung</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div><label style="font-weight: bold;">Tên sản phẩm *</label><input type="text" name="title" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-top:5px;"></div>
            <div><label style="font-weight: bold;">Thương hiệu</label><input type="text" name="brand" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-top:5px;"></div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div><label style="font-weight: bold;">Danh mục *</label>
                <select name="category_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-top:5px;">
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div><label style="font-weight: bold;">Giá hiển thị (Giá từ) *</label><input type="number" name="price" required min="0" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-top:5px;"></div>
            <div><label style="font-weight: bold;">Giá cũ hiển thị</label><input type="number" name="old_price" min="0" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-top:5px;"></div>
        </div>

        <datalist id="variant_list">
            <?php foreach($all_variants as $v): ?><option value="<?= htmlspecialchars($v['name']) ?>"><?php endforeach; ?></option></datalist>

        <h3 style="margin-top: 30px; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;">2. Phân loại hàng (Màu sắc / Dung tích)</h3>
        <table style="width: 100%; margin-bottom: 10px; border-collapse: collapse;" id="variant-table">
            <thead>
                <tr style="background: #f9f9f9;">
                    <th style="padding: 10px; border: 1px solid #ddd;">Chọn hoặc Gõ tên Phân loại mới</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Giá bán riêng</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Giá cũ riêng</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Tồn kho</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Hình ảnh Swatch màu riêng</th>
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Xóa</th>
                </tr>
            </thead>
            <tbody id="variant-body">
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd;">
                        <input type="text" name="variant_names[]" list="variant_list" required placeholder="VD: Đỏ Cherry hoặc 150ml" autocomplete="off" style="width:100%; padding:8px; border: 1px solid #ddd; border-radius: 4px;">
                    </td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><input type="number" name="var_prices[]" required min="0" style="width:100%; padding:8px; border: 1px solid #ddd; border-radius: 4px;"></td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><input type="number" name="var_old_prices[]" value="0" min="0" style="width:100%; padding:8px; border: 1px solid #ddd; border-radius: 4px;"></td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><input type="number" name="var_qtys[]" required min="0" style="width:100%; padding:8px; border: 1px solid #ddd; border-radius: 4px;"></td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><input type="file" name="var_thumbnails[]" accept="image/*" style="width:100%; padding:5px;"></td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">-</td>
                </tr>
            </tbody>
        </table>
        <button type="button" id="btn-add-variant" style="background: #3498db; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-bottom: 30px;"><i class="fa-solid fa-plus"></i> Thêm phân loại nữa</button>

        <h3 style="margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;">3. Hình ảnh & Mô tả</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <label style="font-weight: bold;">Hình ảnh đại diện chính (Thumbnail)</label>
                <input type="file" name="thumbnail" accept="image/*" style="width: 100%; padding: 8px; border: 1px dashed #D4A373; border-radius: 4px; background: #fdfaf6; margin-top:5px;">
            </div>
            <div>
                <label style="font-weight: bold;">Thư viện ảnh phụ mô tả (Chọn nhiều ảnh cùng lúc)</label>
                <input type="file" name="gallery_images[]" accept="image/*" multiple style="width: 100%; padding: 8px; border: 1px dashed #3498db; border-radius: 4px; background: #f0f7fc; margin-top:5px;">
            </div>
        </div>
        <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: bold; margin-bottom: 8px;">Mô tả sản phẩm</label>
            <textarea name="description" rows="6" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-top:5px;"></textarea>
        </div>

        <button type="submit" style="background: #2ecc71; color: white; border: none; padding: 12px 30px; font-weight: bold; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%;">
            LƯU TOÀN BỘ SẢN PHẨM
        </button>
    </form>
</div>

<script>
document.getElementById('btn-add-variant').addEventListener('click', function() {
    var tbody = document.getElementById('variant-body');
    var row = document.createElement('tr');
    row.innerHTML = `
        <td style="padding: 10px; border: 1px solid #ddd;">
            <input type="text" name="variant_names[]" list="variant_list" required placeholder="VD: Đỏ Cherry hoặc 150ml" autocomplete="off" style="width:100%; padding:8px; border: 1px solid #ddd; border-radius: 4px;">
        </td>
        <td style="padding: 10px; border: 1px solid #ddd;"><input type="number" name="var_prices[]" required min="0" style="width:100%; padding:8px; border: 1px solid #ddd; border-radius: 4px;"></td>
        <td style="padding: 10px; border: 1px solid #ddd;"><input type="number" name="var_old_prices[]" value="0" min="0" style="width:100%; padding:8px; border: 1px solid #ddd; border-radius: 4px;"></td>
        <td style="padding: 10px; border: 1px solid #ddd;"><input type="number" name="var_qtys[]" required min="0" style="width:100%; padding:8px; border: 1px solid #ddd; border-radius: 4px;"></td>
        <td style="padding: 10px; border: 1px solid #ddd;"><input type="file" name="var_thumbnails[]" accept="image/*" style="width:100%; padding:5px;"></td>
        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;"><button type="button" class="btn-remove-row" style="background: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Xóa</button></td>
    `;
    tbody.appendChild(row);
});

document.getElementById('variant-body').addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('btn-remove-row')) {
        e.target.closest('tr').remove();
    }
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>