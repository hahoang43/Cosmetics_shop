<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để lưu địa chỉ.']);
    exit;
}

$address = trim($_POST['address'] ?? '');

if ($address === '') {
    echo json_encode(['status' => 'error', 'message' => 'Địa chỉ không được để trống.']);
    exit;
}

try {
    $conn = getDatabase();
    $userId = (int)$_SESSION['user']['id'];

    $stmt = $conn->prepare('UPDATE User SET address = ? WHERE id = ?');
    $stmt->execute([$address, $userId]);

    $_SESSION['user']['address'] = $address;

    echo json_encode(['status' => 'success', 'message' => 'Đã lưu địa chỉ.']);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Không thể lưu địa chỉ.']);
}