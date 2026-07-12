<?php 
require_once '../config/database.php';
$conn = getDatabase();
require_once '../includes/admin_header.php'; 

function ensureProductDiscountPercentColumn(PDO $conn): void {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Product' AND COLUMN_NAME = 'discount_percent'");
    $stmt->execute();
    if ((int)$stmt->fetchColumn() === 0) {
        $conn->exec("ALTER TABLE Product ADD COLUMN discount_percent INT NOT NULL DEFAULT 0 AFTER old_price");
    }
}

ensureProductDiscountPercentColumn($conn);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo "<script>alert('Mã sản phẩm không hợp lệ!'); window.location.href='products.php';</script>"; exit; }

$stmt_prod = $conn->prepare("SELECT * FROM Product WHERE id = ? AND deleted = 0");
$stmt_prod->execute([$id]);
$product = $stmt_prod->fetch();
if (!$product) { echo "<script>alert('Không tìm thấy sản phẩm!'); window.location.href='products.php';</script>"; exit; }

$base_price = (float)(($product['old_price'] > $product['price'] && $product['old_price'] > 0) ? $product['old_price'] : $product['price']);
$discount_percent = isset($product['discount_percent']) ? (int)$product['discount_percent'] : 0;
if ($discount_percent <= 0 && $product['old_price'] > $product['price'] && $product['old_price'] > 0) {
    $discount_percent = (int) round((1 - ($product['price'] / $product['old_price'])) * 100);
}
$discounted_price = $discount_percent > 0 ? (int) round($base_price * (100 - $discount_percent) / 100) : (int) round($base_price);

// XỚA ẢNH PHỤ TRONG THƯ VIỆN KHI ADMIN BẤM NÚT XÓA ẢNH
if (isset($_GET['delete_gal_id'])) {
    $gal_id = (int)$_GET['delete_gal_id'];
    $stmt_g = $conn->prepare("SELECT thumbnail FROM Galery WHERE id = ? AND product_id = ?");
    $stmt_g->execute([$gal_id, $id]);
    $g_thumb = $stmt_g->fetchColumn();
    
    if ($g_thumb) {
        if (file_exists('../assets/uploads/products/' . $g_thumb)) {
            unlink('../assets/uploads/products/' . $g_thumb);
        }
        $stmt_del_g = $conn->prepare("DELETE FROM Galery WHERE id = ?");
        $stmt_del_g->execute([$gal_id]);
    }
    header("Location: product_edit.php?id=" . $id);
    exit;
}

$stmt_cat = $conn->query("SELECT * FROM Category");
$categories = $stmt_cat->fetchAll();

$stmt_var = $conn->query("SELECT * FROM Variant ORDER BY name ASC");
$all_variants = $stmt_var->fetchAll();

// ĐÃ SỬA: Lấy thêm cả cột pv.thumbnail từ bảng Product_Variant lên để quản lý ảnh màu
$stmt_existing_pv = $conn->prepare("
    SELECT pv.*, v.name as variant_name 
    FROM Product_Variant pv 
    JOIN Variant v ON pv.variant_id = v.id 
    WHERE pv.product_id = ?
");
$stmt_existing_pv->execute([$id]);
$existing_pvs = $stmt_existing_pv->fetchAll();

// Lấy danh sách ảnh trong Thư viện của sản phẩm này hiện tại
$stmt_gal_list = $conn->prepare("SELECT * FROM Galery WHERE product_id = ?");
$stmt_gal_list->execute([$id]);
$gallery_images = $stmt_gal_list->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $brand = trim($_POST['brand']);
    $category_id = (int)$_POST['category_id'];
    $base_price = (float)$_POST['price'];
    $discount_percent = isset($_POST['discount_percent']) ? max(0, min(100, (int)$_POST['discount_percent'])) : 0;
    $price = $discount_percent > 0 ? (float) round($base_price * (100 - $discount_percent) / 100) : $base_price;
    $old_price = $discount_percent > 0 ? $base_price : 0;
    $description = trim($_POST['description']);
    $thumbnail = $product['thumbnail']; 
    
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

            $sql = "UPDATE Product SET category_id=?, title=?, price=?, old_price=?, thumbnail=?, description=?, brand=?, discount_percent=? WHERE id=?";
            $stmt_update = $conn->prepare($sql);
            $stmt_update->execute([$category_id, $title, $price, $old_price, $thumbnail, $description, $brand, $discount_percent, $id]);

            $pv_ids = $_POST['pv_ids'] ?? []; 
            $variant_names = $_POST['variant_names'] ?? []; 
            $var_prices = $_POST['var_prices'] ?? [];
            $var_old_prices = $_POST['var_old_prices'] ?? [];
            $var_qtys = $_POST['var_qtys'] ?? [];
            $existing_var_imgs = $_POST['existing_var_imgs'] ?? []; // Mảng chứa tên ảnh biến thể cũ

            $old_ids = array_column($existing_pvs, 'id');
            foreach ($old_ids as $old_id) {
                if (!in_array($old_id, $pv_ids)) {
                    // Nếu admin xóa dòng biến thể, xóa luôn ảnh của dòng đó ra khỏi thư mục máy tính
                    $stmt_find_img = $conn->prepare("SELECT thumbnail FROM Product_Variant WHERE id = ?");
                    $stmt_find_img->execute([$old_id]);
                    $old_v_thumb = $stmt_find_img->fetchColumn();
                    if ($old_v_thumb && file_exists('../assets/uploads/products/' . $old_v_thumb)) {
                        unlink('../assets/uploads/products/' . $old_v_thumb);
                    }

                    try {
                        $conn->exec("DELETE FROM Product_Variant WHERE id = $old_id");
                    } catch(Exception $e) {
                        $conn->exec("UPDATE Product_Variant SET quantity = 0 WHERE id = $old_id");
                    }
                }
            }

            for ($i = 0; $i < count($variant_names); $i++) {
                $vname = trim($variant_names[$i]);
                if (!empty($vname)) {
                    $pvid = (int)($pv_ids[$i] ?? 0);
                    $baseVariantPrice = (float)$var_prices[$i];
                    $vprice = $discount_percent > 0 ? (float) round($baseVariantPrice * (100 - $discount_percent) / 100) : $baseVariantPrice;
                    $vold = $discount_percent > 0 ? $baseVariantPrice : (float)$var_old_prices[$i];
                    $vqty = (int)$var_qtys[$i];
                    
                    // Mặc định gán lại ảnh biến thể cũ của dòng này
                    $var_img_name = $existing_var_imgs[$i] ?? null;

                    // Nếu có tệp ảnh mới được chọn riêng cho dòng này, tiến hành lưu đè ảnh mới
                    if (isset($_FILES['var_thumbnails']['error'][$i]) && $_FILES['var_thumbnails']['error'][$i] == 0) {
                        $vt_name = $_FILES['var_thumbnails']['name'][$i];
                        $vt_tmp = $_FILES['var_thumbnails']['tmp_name'][$i];
                        $vt_ext = strtolower(pathinfo($vt_name, PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                        
                        if (in_array($vt_ext, $allowed)) {
                            $new_vt_name = 'var_' . time() . '_' . rand(1000, 9999) . '.' . $vt_ext;
                            if (move_uploaded_file($vt_tmp, '../assets/uploads/products/' . $new_vt_name)) {
                                // Xóa file ảnh cũ nếu tồn tại trước đó để tránh rác host
                                if (!empty($var_img_name) && file_exists('../assets/uploads/products/' . $var_img_name)) {
                                    unlink('../assets/uploads/products/' . $var_img_name);
                                }
                                $var_img_name = $new_vt_name;
                            }
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

                    if ($pvid > 0) {
                        // Cập nhật dòng biến thể cũ (Đã thêm cập nhật cột thumbnail = ?)
                        $stmt_up_pv = $conn->prepare("UPDATE Product_Variant SET variant_id=?, price=?, old_price=?, quantity=?, thumbnail=? WHERE id=?");
                        $stmt_up_pv->execute([$vid, $vprice, $vold, $vqty, $var_img_name, $pvid]);
                    } else {
                        // Thêm dòng biến thể mới hoàn toàn (Đã thêm cột thumbnail)
                        $stmt_in_pv = $conn->prepare("INSERT INTO Product_Variant (product_id, variant_id, price, old_price, quantity, thumbnail) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt_in_pv->execute([$id, $vid, $vprice, $vold, $vqty, $var_img_name]);
                    }
                }
            }

            // Xử lý Upload thêm ảnh vào Thư viện khi Chỉnh sửa
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
                                $stmt_gal->execute([$id, $new_g_name]);
                            }
                        }
                    }
                }
            }

            $conn->commit();
            echo "<script>alert('Cập nhật thành công!'); window.location.href='products.php';</script>";
            exit;
        } catch(Exception $e) {
            $conn->rollBack();
            echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 class="page-title" style="margin: 0; border: none;">Chỉnh sửa Sản phẩm #<?= $id ?></h1>
    <a href="products.php" style="background: #95a5a6; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px;">Quay lại</a>
</div>

<div class="admin-form-container" style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <form action="" method="POST" enctype="multipart/form-data">
        
        <h3 style="margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;">1. Thông tin chung</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div><label style="font-weight: bold;">Tên sản phẩm *</label><input type="text" name="title" value="<?= htmlspecialchars($product['title']) ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-top:5px;"></div>
            <div><label style="font-weight: bold;">Thương hiệu</label><input type="text" name="brand" value="<?= htmlspecialchars($product['brand']) ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-top:5px;"></div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div><label style="font-weight: bold;">Danh mục *</label>
                <select name="category_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-top:5px;">
                    <?php foreach($categories as $cat): ?><option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $product['category_id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div><label style="font-weight: bold;">Giá gốc (VNĐ) *</label><input type="number" name="price" id="base-price" value="<?= (int)$base_price ?>" required min="0" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-top:5px;"></div>
            <div><label style="font-weight: bold;">Khuyến mãi (%)</label><input type="number" name="discount_percent" id="discount-percent" value="<?= (int)$discount_percent ?>" min="0" max="100" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-top:5px;"></div>
        </div>

        <div style="margin-bottom: 20px; background: #fdfaf6; border: 1px dashed #e7c9a4; padding: 14px 16px; border-radius: 6px; color: #7a4f2e;">
            <strong>Giá hiển thị sau khuyến mãi:</strong>
            <span id="discounted-price-preview" style="font-size: 18px; margin-left: 8px; color: #D4A373; font-weight: 700;"><?= number_format($discounted_price, 0, ',', '.') ?>đ</span>
        </div>

        <datalist id="variant_list">
            <?php foreach($all_variants as $v): ?><option value="<?= htmlspecialchars($v['name']) ?>"><?php endforeach; ?></option></datalist>

        <h3 style="margin-top: 30px; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;">2. Phân loại hàng (Màu sắc / Dung tích)</h3>
        <table style="width: 100%; margin-bottom: 10px; border-collapse: collapse;" id="variant-table">
            <thead>
                <tr style="background: #f9f9f9;">
                    <th style="padding: 10px; border: 1px solid #ddd;">Chọn hoặc Gõ tên Phân loại</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Giá bán riêng</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Giá cũ riêng</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Tồn kho</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Hình ảnh màu riêng</th>
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Xóa</th>
                </tr>
            </thead>
            <tbody id="variant-body">
                <?php foreach($existing_pvs as $pv): ?>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <input type="hidden" name="pv_ids[]" value="<?= $pv['id'] ?>">
                            <input type="hidden" name="existing_var_imgs[]" value="<?= htmlspecialchars($pv['thumbnail'] ?? '') ?>">
                            <input type="text" name="variant_names[]" list="variant_list" value="<?= htmlspecialchars($pv['variant_name']) ?>" required autocomplete="off" style="width:100%; padding:8px; border: 1px solid #ddd; border-radius: 4px;">
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><input type="number" name="var_prices[]" value="<?= $pv['price'] ?>" required min="0" style="width:100%; padding:8px; border: 1px solid #ddd; border-radius: 4px;"></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><input type="number" name="var_old_prices[]" value="<?= $pv['old_price'] ?>" min="0" style="width:100%; padding:8px; border: 1px solid #ddd; border-radius: 4px;"></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><input type="number" name="var_qtys[]" value="<?= $pv['quantity'] ?>" required min="0" style="width:100%; padding:8px; border: 1px solid #ddd; border-radius: 4px;"></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                    <?php if(!empty($pv['thumbnail'])): ?>
                                    <img src="/Cosmetics_shop/assets/uploads/products/<?= htmlspecialchars($pv['thumbnail']) ?>" style="width:35px; height:35px; object-fit:cover; border-radius:3px; border:1px solid #eee;">
                                <?php endif; ?>
                                <input type="file" name="var_thumbnails[]" accept="image/*" style="width:100%; padding:3px;">
                            </div>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;"><button type="button" class="btn-remove-row" style="background: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Xóa</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="button" id="btn-add-variant" style="background: #3498db; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-bottom: 30px;"><i class="fa-solid fa-plus"></i> Thêm phân loại nữa</button>

        <h3 style="margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;">3. Hình ảnh & Mô tả</h3>
        <div style="margin-bottom: 20px; display: flex; gap: 40px; align-items: flex-start; border-bottom: 1px dashed #eee; padding-bottom: 20px;">
                <div style="width: 120px; text-align: center;">
                <p style="margin-bottom: 5px; font-weight: bold; font-size: 13px; color: #555;">Ảnh đại diện chính</p>
                <img src="<?= htmlspecialchars(imageSrc($product['thumbnail'] ?? '', 'products')) ?>" style="width: 100%; border-radius: 5px; border: 1px solid #eee;" onerror="this.onerror=null;this.src='<?= htmlspecialchars(noImageSrc('No Image')) ?>';">
            </div>
            <div style="flex: 1;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px;">Thay đổi ảnh đại diện (Thumbnail)</label>
                <input type="file" name="thumbnail" accept="image/*" style="width: 100%; padding: 8px; border: 1px dashed #D4A373; border-radius: 4px; background: #fdfaf6;">
            </div>
        </div>

        <div style="margin-bottom: 25px;">
            <label style="display: block; font-weight: bold; margin-bottom: 10px;">Thư viện ảnh mô tả phụ hiện tại</label>
            <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px;">
                <?php if (count($gallery_images) > 0): ?>
                        <?php foreach ($gallery_images as $img): ?>
                        <div style="position: relative; width: 100px; border: 1px solid #ddd; padding: 5px; border-radius: 4px; background: #fff;">
                            <img src="/Cosmetics_shop/assets/uploads/products/<?= htmlspecialchars($img['thumbnail']) ?>" style="width: 100%; height: 80px; object-fit: cover; border-radius: 3px;">
                            <a href="product_edit.php?id=<?= $id ?>&delete_gal_id=<?= $img['id'] ?>" onclick="return confirm('Xóa ảnh mô tả này?')" style="position: absolute; top: -5px; right: -5px; background: #e74c3c; color: white; width: 20px; height: 20px; border-radius: 50%; text-align: center; line-height: 18px; text-decoration: none; font-size: 11px; font-weight: bold; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">x</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #999; font-style: italic; font-size: 14px;">Chưa có ảnh mô tả phụ nào.</p>
                <?php endif; ?>
            </div>
            <label style="display: block; font-weight: bold; margin-bottom: 8px;">Tải thêm ảnh phụ vào Thư viện ảnh (Chọn nhiều ảnh cùng lúc)</label>
            <input type="file" name="gallery_images[]" accept="image/*" multiple style="width: 100%; padding: 8px; border: 1px dashed #3498db; border-radius: 4px; background: #f0f7fc;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: bold; margin-bottom: 8px;">Mô tả sản phẩm</label>
            <textarea name="description" rows="6" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; resize: vertical;"><?= htmlspecialchars($product['description']) ?></textarea>
        </div>

        <button type="submit" style="background: #3498db; color: white; border: none; padding: 12px 30px; font-weight: bold; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%;">
            CẬP NHẬT SẢN PHẨM & KHO
        </button>
    </form>
</div>

<script>
document.getElementById('btn-add-variant').addEventListener('click', function() {
    var tbody = document.getElementById('variant-body');
    var row = document.createElement('tr');
    row.innerHTML = `
        <td style="padding: 10px; border: 1px solid #ddd;">
            <input type="hidden" name="pv_ids[]" value="0">
            <input type="hidden" name="existing_var_imgs[]" value="">
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

function updateDiscountPreview() {
    const basePrice = parseFloat(document.getElementById('base-price').value || '0');
    const discountPercent = Math.min(100, Math.max(0, parseFloat(document.getElementById('discount-percent').value || '0')));
    const discountedPrice = discountPercent > 0 ? Math.round(basePrice * (100 - discountPercent) / 100) : Math.round(basePrice);
    document.getElementById('discounted-price-preview').textContent = new Intl.NumberFormat('vi-VN').format(discountedPrice) + 'đ';
}

document.getElementById('base-price').addEventListener('input', updateDiscountPreview);
document.getElementById('discount-percent').addEventListener('input', updateDiscountPreview);
updateDiscountPreview();

document.getElementById('variant-body').addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('btn-remove-row')) {
        e.target.closest('tr').remove();
    }
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>