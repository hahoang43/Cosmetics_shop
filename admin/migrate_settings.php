<?php
/** @var PDO|null $conn */
require_once '../config/database.php';

try {
    // Tạo bảng Settings
    $sql = "CREATE TABLE IF NOT EXISTS Settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value LONGTEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    
    // Insert giá trị mặc định
    $defaults = [
        'shop_lat' => '10.776889',
        'shop_lng' => '106.700806',
        'shop_name' => 'Lumina Cosmetics',
        'shipping_base_fee' => '15000',
        'shipping_per_km_fee' => '3500',
        'shipping_max_fee' => '80000'
    ];
    
    foreach ($defaults as $key => $value) {
        $stmt = $conn->prepare("INSERT IGNORE INTO Settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
    
    echo "✓ Bảng Settings đã được tạo thành công!<br>";
    echo "✓ Giá trị mặc định đã được khởi tạo!<br><br>";
    echo '<a href="settings.php" style="background: #D4A373; color: #fff; padding: 10px 20px; border-radius: 5px; text-decoration: none;">Vào Cài đặt →</a>';
    
} catch (PDOException $e) {
    echo "❌ Lỗi: " . htmlspecialchars($e->getMessage());
}
?>
