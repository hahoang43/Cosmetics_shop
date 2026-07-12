<?php
/** @var PDO|null $conn */

require_once __DIR__ . '/database.php';

// Hàm lấy giá trị setting từ database
function getSetting($key, $default = null) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT setting_value FROM Settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Định nghĩa shipping constants từ database hoặc giá trị mặc định
define('SHOP_LAT', floatval(getSetting('shop_lat', 10.776889)));
define('SHOP_LNG', floatval(getSetting('shop_lng', 106.700806)));
define('SHOP_NAME', getSetting('shop_name', 'Lumina Cosmetics'));
define('SHOP_ADDRESS', getSetting('shop_address', 'Số 2, Võ Oanh, P.25, Bình Thạnh, TP.HCM'));
define('SHIPPING_BASE_FEE', intval(getSetting('shipping_base_fee', 15000)));
define('SHIPPING_PER_KM_FEE', intval(getSetting('shipping_per_km_fee', 3500)));
define('SHIPPING_MAX_FEE', intval(getSetting('shipping_max_fee', 80000)));

// Hàm tính khoảng cách Haversine
function haversineDistanceKm($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371; // Bán kính Trái Đất (km)
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earthRadius * $c, 2);
}

// Hàm tính phí vận chuyển
function calculateShippingFee($distanceKm) {
    $distanceCeil = ceil($distanceKm);
    $fee = SHIPPING_BASE_FEE + ($distanceCeil * SHIPPING_PER_KM_FEE);
    return min($fee, SHIPPING_MAX_FEE);
}
