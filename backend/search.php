<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (isset($_GET['keyword'])) {
    $keyword = trim($_GET['keyword']);
    
    if (strlen($keyword) < 2) {
        echo json_encode([]);
        exit;
    }

    $search_term = "%" . $keyword . "%";
    
    // Tìm tối đa 5 sản phẩm khớp tên, chưa bị xóa
    $stmt = $conn->prepare("SELECT id, title, price, thumbnail FROM Product WHERE title LIKE ? AND deleted = 0 LIMIT 5");
    $stmt->execute([$search_term]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
} else {
    echo json_encode([]);
}
?>