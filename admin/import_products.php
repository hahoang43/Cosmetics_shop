<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

function parsePrice($s) {
    $s = trim($s);
    if ($s === '' || strtoupper($s) === '----') {
        return 0;
    }
    $num = preg_replace('/\D/', '', $s);
    return $num === '' ? 0 : intval($num);
}

function parseStock($s) {
    $s = trim((string)$s);
    if ($s === '') {
        return 0;
    }
    return (int)round((float)str_replace(',', '.', preg_replace('/[^0-9\.,]/', '', $s)));
}

function normalizeCsvHeader($value) {
    $value = trim((string)$value);
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
    $value = mb_strtolower($value, 'UTF-8');
    $value = str_replace([' ', '-', '_', '.'], '', $value);
    return $value;
}

function buildHeaderMap(array $header) {
    $map = [];
    foreach ($header as $index => $name) {
        $map[normalizeCsvHeader($name)] = $index;
    }
    return $map;
}

function getCsvValue(array $row, array $headerMap, array $aliases, $fallbackIndex = null, $default = '') {
    foreach ($aliases as $alias) {
        $key = normalizeCsvHeader($alias);
        if (isset($headerMap[$key])) {
            return $row[$headerMap[$key]] ?? $default;
        }
    }

    if ($fallbackIndex !== null) {
        return $row[$fallbackIndex] ?? $default;
    }

    return $default;
}

// reuse helpers similar to tools/import_products.php
function getOrCreateCategory(PDO $conn, $name) {
    $name = trim($name);
    if ($name === '') {
        return null;
    }
    $stmt = $conn->prepare('SELECT id FROM Category WHERE name = ? LIMIT 1');
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    if ($row) {
        return (int)$row['id'];
    }
    $stmt = $conn->prepare('INSERT INTO Category (name) VALUES (?)');
    $stmt->execute([$name]);
    return (int)$conn->lastInsertId();
}

function getOrCreateVariant(PDO $conn, $name) {
    $name = trim($name);
    if ($name === '') {
        return null;
    }
    $stmt = $conn->prepare('SELECT id FROM Variant WHERE name = ? LIMIT 1');
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    if ($row) {
        return (int)$row['id'];
    }
    $stmt = $conn->prepare('INSERT INTO Variant (name) VALUES (?)');
    $stmt->execute([$name]);
    return (int)$conn->lastInsertId();
}

function getOrCreateProduct(PDO $conn, array $productData) {
    $title = $productData['title'];
    $category_id = $productData['category_id'];
    $price = $productData['price'];
    $old_price = $productData['old_price'];
    $thumbnail = $productData['thumbnail'];
    $description = $productData['description'];
    $brand = $productData['brand'];

    $stmt = $conn->prepare('SELECT id FROM Product WHERE title = ? LIMIT 1');
    $stmt->execute([$title]);
    $row = $stmt->fetch();
    if ($row) {
        $productId = (int)$row['id'];
        $stmt = $conn->prepare('UPDATE Product SET category_id = ?, price = ?, old_price = ?, thumbnail = ?, description = ?, brand = ? WHERE id = ?');
        $stmt->execute([$category_id, $price, $old_price, $thumbnail, $description, $brand, $productId]);
        return $productId;
    }
    $stmt = $conn->prepare('INSERT INTO Product (category_id, title, price, old_price, thumbnail, description, brand) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$category_id, $title, $price, $old_price, $thumbnail, $description, $brand]);
    return (int)$conn->lastInsertId();
}

function createOrUpdateProductVariant(PDO $conn, $product_id, $variant_id, $price, $old_price, $quantity, $thumbnail) {
    $stmt = $conn->prepare('SELECT id FROM Product_Variant WHERE product_id = ? AND variant_id = ? LIMIT 1');
    $stmt->execute([$product_id, $variant_id]);
    $row = $stmt->fetch();
    if ($row) {
        $id = (int)$row['id'];
        $stmt = $conn->prepare('UPDATE Product_Variant SET price = ?, old_price = ?, quantity = ?, thumbnail = ? WHERE id = ?');
        $stmt->execute([$price, $old_price, $quantity, $thumbnail, $id]);
        return $id;
    }
    $stmt = $conn->prepare('INSERT INTO Product_Variant (product_id, variant_id, price, old_price, quantity, thumbnail) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$product_id, $variant_id, $price, $old_price, $quantity, $thumbnail]);
    return (int)$conn->lastInsertId();
}

function deleteDatasetPermanently(PDO $conn) {
    $conn->beginTransaction();

    try {
        $stmt = $conn->query("SELECT p.id FROM Product p LEFT JOIN Order_Details od ON od.product_id = p.id WHERE od.id IS NULL");
        $productIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($productIds)) {
            $conn->rollBack();
            return ['deleted_products' => 0, 'deleted_variants' => 0, 'deleted_galleries' => 0, 'deleted_reviews' => 0, 'deleted_qa' => 0, 'deleted_categories' => 0, 'deleted_variant_rows' => 0];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        $stmt = $conn->prepare("DELETE FROM Product_Review WHERE product_id IN ($placeholders)");
        $stmt->execute($productIds);
        $deletedReviews = $stmt->rowCount();

        $stmt = $conn->prepare("DELETE FROM Product_QA WHERE product_id IN ($placeholders)");
        $stmt->execute($productIds);
        $deletedQa = $stmt->rowCount();

        $stmt = $conn->prepare("DELETE FROM Galery WHERE product_id IN ($placeholders)");
        $stmt->execute($productIds);
        $deletedGalleries = $stmt->rowCount();

        $stmt = $conn->prepare("DELETE FROM Product_Variant WHERE product_id IN ($placeholders)");
        $stmt->execute($productIds);
        $deletedVariantRows = $stmt->rowCount();

        $stmt = $conn->prepare("DELETE FROM Product WHERE id IN ($placeholders)");
        $stmt->execute($productIds);
        $deletedProducts = $stmt->rowCount();

        $stmt = $conn->query("DELETE FROM Category WHERE id NOT IN (SELECT DISTINCT category_id FROM Product WHERE category_id IS NOT NULL)");
        $deletedCategories = $stmt->rowCount();

        $stmt = $conn->query("DELETE FROM Variant WHERE id NOT IN (SELECT DISTINCT variant_id FROM Product_Variant WHERE variant_id IS NOT NULL)");
        $deletedVariants = $stmt->rowCount();

        $conn->commit();

        return [
            'deleted_products' => $deletedProducts,
            'deleted_variants' => $deletedVariants,
            'deleted_galleries' => $deletedGalleries,
            'deleted_reviews' => $deletedReviews,
            'deleted_qa' => $deletedQa,
            'deleted_categories' => $deletedCategories,
            'deleted_variant_rows' => $deletedVariantRows,
        ];
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw $e;
    }
}

$message = $_SESSION['import_message'] ?? '';
unset($_SESSION['import_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_dataset'])) {
    try {
        $result = deleteDatasetPermanently($conn);
        $message = 'Đã xóa vĩnh viễn dataset: ' . $result['deleted_products'] . ' sản phẩm, ' . $result['deleted_variant_rows'] . ' biến thể, ' . $result['deleted_reviews'] . ' review, ' . $result['deleted_qa'] . ' QA, ' . $result['deleted_galleries'] . ' ảnh, ' . $result['deleted_categories'] . ' danh mục, ' . $result['deleted_variants'] . ' biến thể danh mục.';
    } catch (Throwable $e) {
        $message = 'Lỗi xóa dataset: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file']) && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
    $tmp = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($tmp, 'r');
    if (!$handle) {
        $message = 'Không thể mở file CSV.';
    } else {
        $header = fgetcsv($handle);
        $headerMap = $header ? buildHeaderMap($header) : [];
        $seenCategories = [];
        $seenVariants = [];
        $counts = ['rows'=>0,'product_variants'=>0,'variants'=>0,'categories'=>0];
        $conn->beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $counts['rows']++;
                $category = getCsvValue($row, $headerMap, ['Danh mục', 'Category'], 0, '');
                $brand = getCsvValue($row, $headerMap, ['Thương hiệu', 'Brand'], 1, '');
                $title = getCsvValue($row, $headerMap, ['Tên sản phẩm', 'Product Name', 'Title'], 2, '');
                $variantField = getCsvValue($row, $headerMap, ['Phân loại', 'Variant'], 3, '');
                $price = parsePrice(getCsvValue($row, $headerMap, ['Giá bán', 'Price'], 4, '0'));
                $old = parsePrice(getCsvValue($row, $headerMap, ['Giá cũ', 'Old Price'], 5, '0'));
                $stock = parseStock(getCsvValue($row, $headerMap, ['Tồn kho', 'Stock'], 6, '0'));
                $image = getCsvValue($row, $headerMap, ['Link ảnh', 'Image', 'Image Link', 'Thumbnail'], 7, null);
                $description = getCsvValue($row, $headerMap, ['Mô tả sản phẩm', 'Description'], 8, '');

                if (trim($title) === '') {
                    continue;
                }

                $catKey = trim($category);
                if (!isset($seenCategories[$catKey])) {
                    $catId = getOrCreateCategory($conn, $catKey);
                    $seenCategories[$catKey] = $catId;
                } else {
                    $catId = $seenCategories[$catKey];
                }

                $product_id = getOrCreateProduct($conn, [
                    'title' => trim($title),
                    'category_id' => $catId,
                    'price' => $price,
                    'old_price' => $old,
                    'thumbnail' => $image,
                    'description' => trim((string)$description),
                    'brand' => trim((string)$brand),
                ]);

                $variantField = str_replace(['|','/',';'], ',', $variantField);
                $variants = array_map('trim', explode(',', $variantField));
                if (empty($variants) || (count($variants) === 1 && $variants[0] === '')) {
                    $variants = ['Default'];
                }
                foreach ($variants as $vname) {
                    if ($vname === '') {
                        $vname = 'Default';
                    }
                    if (!isset($seenVariants[$vname])) {
                        $variantId = getOrCreateVariant($conn, $vname);
                        $seenVariants[$vname] = $variantId;
                    } else {
                        $variantId = $seenVariants[$vname];
                    }
                    createOrUpdateProductVariant($conn, $product_id, $variantId, $price, $old, $stock, $image);
                    $counts['product_variants']++;
                }
            }
            $conn->commit();
            $_SESSION['import_message'] = "Import xong. Dòng: {$counts['rows']}, Product_Variant thêm/cập nhật: {$counts['product_variants']}";
            header('Location: import_products.php');
            exit;
        } catch (Throwable $e) {
            $conn->rollBack();
            $_SESSION['import_message'] = 'Lỗi import: ' . $e->getMessage();
            header('Location: import_products.php');
            exit;
        }
        fclose($handle);
    }
}
?>
<?php include_once 'admin_header.php'; ?>
<main style="max-width:900px;margin:40px auto;padding:20px;">
    <h2>Import Products CSV</h2>
    <?php if ($message): ?>
        <div style="padding:12px;background:#f3f4f6;border-radius:6px;margin-bottom:12px;"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="POST" style="margin-bottom:16px; background:#fff7ed; border:1px solid #fed7aa; padding:14px; border-radius:8px;">
        <div style="font-weight:bold; margin-bottom:8px; color:#9a3412;">Xóa dataset vĩnh viễn</div>
        <p style="margin:0 0 12px; color:#7c2d12; font-size:14px; line-height:1.5;">
            Xóa toàn bộ sản phẩm dataset chưa phát sinh đơn hàng cùng review, QA, gallery và dữ liệu liên quan. Các sản phẩm đã có trong đơn hàng sẽ được giữ lại để không lỗi khóa ngoại.
        </p>
        <button type="submit" name="delete_dataset" value="1" style="padding:10px 14px; background:#d9480f; color:#fff; border:none; border-radius:6px; font-weight:bold; cursor:pointer;" onclick="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn dataset không? Hành động này không thể hoàn tác.')">
            <i class="fa-solid fa-trash-can"></i> Xóa dataset vĩnh viễn
        </button>
    </form>
    <form method="POST" enctype="multipart/form-data">
        <div style="margin-bottom:10px;">
            <label for="csv_file">CSV file:</label>
            <input type="file" id="csv_file" name="csv_file" accept="text/csv,application/csv,text/plain">
        </div>
        <button type="submit" style="padding:8px 14px;">Upload & Import</button>
    </form>
    <p style="margin-top:16px;color:#666;">Lưu ý: file CSV phải có header tương ứng và định dạng giống `data/products.csv`.</p>
</main>
<?php include_once 'admin_footer.php'; ?>
