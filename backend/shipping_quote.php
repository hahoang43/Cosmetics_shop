<?php
session_start();
header('Content-Type: application/json');

require_once '../config/shipping.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập để tính phí giao hàng.']);
    exit;
}

$lat = isset($_POST['lat']) ? (float) $_POST['lat'] : null;
$lng = isset($_POST['lng']) ? (float) $_POST['lng'] : null;

if ($lat === null || $lng === null) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu tọa độ giao hàng.']);
    exit;
}

if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode(['status' => 'error', 'message' => 'Tọa độ giao hàng không hợp lệ.']);
    exit;
}

$distanceKm = haversine_distance_km(SHOP_LAT, SHOP_LNG, $lat, $lng);
$shippingFee = calculate_shipping_fee($distanceKm);

echo json_encode([
    'status' => 'success',
    'distance_km' => round($distanceKm, 2),
    'shipping_fee' => $shippingFee,
    'shop' => [
        'name' => SHOP_NAME,
        'lat' => SHOP_LAT,
        'lng' => SHOP_LNG,
    ],
]);
