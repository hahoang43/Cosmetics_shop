<?php
// tools/import_products.php
// Usage (CLI): php tools/import_products.php data/products.csv

require_once __DIR__ . '/../config/database.php';

if (!isset($conn) || !($conn instanceof PDO)) {
    echo "Database connection not found. Check config/database.php\n";
    exit(1);
}

$csvFile = $argv[1] ?? __DIR__ . '/../data/products.csv';
if (!file_exists($csvFile)) {
    echo "CSV file not found: $csvFile\n";
    exit(1);
}

function parsePrice($s) {
    $s = trim($s);
    if ($s === '' || strtoupper($s) === '----') {
        return 0;
    }
    // Remove non-digits
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

function getOrCreateCategory(PDO $conn, $name): array {
    $name = trim($name);
    if ($name === '') {
        return ['id' => null, 'created' => false];
    }
    $stmt = $conn->prepare('SELECT id FROM Category WHERE name = ? LIMIT 1');
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    if ($row) {
        return ['id' => (int)$row['id'], 'created' => false];
    }
    $stmt = $conn->prepare('INSERT INTO Category (name) VALUES (?)');
    $stmt->execute([$name]);
    return ['id' => (int)$conn->lastInsertId(), 'created' => true];
}

function getOrCreateVariant(PDO $conn, $name): array {
    $name = trim($name);
    if ($name === '') {
        return ['id' => null, 'created' => false];
    }
    $stmt = $conn->prepare('SELECT id FROM Variant WHERE name = ? LIMIT 1');
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    if ($row) {
        return ['id' => (int)$row['id'], 'created' => false];
    }
    $stmt = $conn->prepare('INSERT INTO Variant (name) VALUES (?)');
    $stmt->execute([$name]);
    return ['id' => (int)$conn->lastInsertId(), 'created' => true];
}

function getOrCreateProduct(PDO $conn, array $productData): array {
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
        return ['id' => $productId, 'created' => false];
    }
    $stmt = $conn->prepare('INSERT INTO Product (category_id, title, price, old_price, thumbnail, description, brand) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$category_id, $title, $price, $old_price, $thumbnail, $description, $brand]);
    return ['id' => (int)$conn->lastInsertId(), 'created' => true];
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

$handle = fopen($csvFile, 'r');
if (!$handle) {
    echo "Cannot open CSV file: $csvFile\n";
    exit(1);
}

$header = fgetcsv($handle);
$headerMap = $header ? buildHeaderMap($header) : [];
if (!$header) {
    echo "Empty CSV file.\n";
    exit(1);
}

$counts = ['rows' => 0, 'categories' => 0, 'products' => 0, 'products_created' => 0, 'products_existing' => 0, 'variants' => 0, 'product_variants' => 0];
$seenCategories = [];
$seenVariants = [];

$conn->beginTransaction();
try {
    while (($row = fgetcsv($handle)) !== false) {
        $counts['rows']++;
        $category = getCsvValue($row, $headerMap, ['Danh mục', 'Category'], 0, '');
        $brand = getCsvValue($row, $headerMap, ['Thương hiệu', 'Brand'], 1, '');
        $title = getCsvValue($row, $headerMap, ['Tên sản phẩm', 'Product Name', 'Title'], 2, '');
        $variantField = getCsvValue($row, $headerMap, ['Phân loại', 'Variant'], 3, '');
        $price = parsePrice(getCsvValue($row, $headerMap, ['Giá bán', 'Price'], 4, '0'));
        $old_price = parsePrice(getCsvValue($row, $headerMap, ['Giá cũ', 'Old Price'], 5, '0'));
        $stock = parseStock(getCsvValue($row, $headerMap, ['Tồn kho', 'Stock'], 6, '0'));
        $image = getCsvValue($row, $headerMap, ['Link ảnh', 'Image', 'Image Link', 'Thumbnail'], 7, null);
        $description = getCsvValue($row, $headerMap, ['Mô tả sản phẩm', 'Description'], 8, '');

        if (trim($title) === '') {
            continue;
        }

        $catKey = trim($category);
        if (!isset($seenCategories[$catKey])) {
            $categoryResult = getOrCreateCategory($conn, $catKey);
            $catId = $categoryResult['id'];
            $seenCategories[$catKey] = $catId;
            $counts['categories'] = count($seenCategories);
        } else {
            $catId = $seenCategories[$catKey];
        }

        $product_title = trim($title);
        $productResult = getOrCreateProduct($conn, [
            'title' => trim($product_title),
            'category_id' => $catId,
            'price' => $price,
            'old_price' => $old_price,
            'thumbnail' => $image,
            'description' => trim((string)$description),
            'brand' => trim((string)$brand),
        ]);
        $product_id = $productResult['id'];
        if ($productResult['created']) {
            $counts['products_created']++;
        } else {
            $counts['products_existing']++;
        }
        $counts['products'] = $counts['products_created'] + $counts['products_existing'];

        // Variants may be separated by comma, pipe or slash
        $variantField = str_replace(['|','/',';'], ',', $variantField);
        $variants = array_map('trim', explode(',', $variantField));
        if (empty($variants) || (count($variants) === 1 && $variants[0] === '')) {
            // create a default variant named "Default"
            $variants = ['Default'];
        }

        foreach ($variants as $vname) {
            if ($vname === '') {
                $vname = 'Default';
            }
            if (!isset($seenVariants[$vname])) {
                $variantResult = getOrCreateVariant($conn, $vname);
                $variantId = $variantResult['id'];
                $seenVariants[$vname] = $variantId;
                $counts['variants'] = count($seenVariants);
            } else {
                $variantId = $seenVariants[$vname];
            }

            $pvId = createOrUpdateProductVariant($conn, $product_id, $variantId, $price, $old_price, $stock, $image);
            $counts['product_variants']++;
        }
    }

    $conn->commit();
    fclose($handle);
    echo "Import completed. Rows processed: {$counts['rows']}\n";
    echo "Categories: {$counts['categories']}, Products: {$counts['products']} total ({$counts['products_created']} new, {$counts['products_existing']} existing), Variants: {$counts['variants']}, Product_Variant rows added/updated: {$counts['product_variants']}\n";
} catch (Throwable $e) {
    $conn->rollBack();
    fclose($handle);
    echo "Error during import: " . $e->getMessage() . "\n";
    exit(1);
}

// Summary suggestion
echo "Run SQL to verify inserted records, e.g.: SELECT COUNT(*) FROM Product;\n";
