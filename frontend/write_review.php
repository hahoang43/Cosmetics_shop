<?php 
require_once '../config/database.php';
require_once '../includes/popup_notify.php';
$conn = getDatabase();
require_once '../includes/header.php'; 
echo popup_assets();

if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$user_id = $_SESSION['user']['id'];

// Kiểm tra xem khách đã thực sự mua mặt hàng này chưa
$stmt_check = $conn->prepare("
    SELECT COUNT(*) FROM Orders o 
    JOIN Order_Details od ON o.id = od.order_id 
    WHERE o.user_id = ? AND od.product_id = ? AND o.status = 2
");
$stmt_check->execute([$user_id, $product_id]);
if ($stmt_check->fetchColumn() == 0) {
    echo "<div class='container' style='margin-top:50px;'><h3>Bạn chỉ được đánh giá những sản phẩm đã được giao thành công!</h3></div>";
    include '../includes/footer.php'; exit;
}

$stmt_p = $conn->prepare("SELECT title FROM Product WHERE id = ?");
$stmt_p->execute([$product_id]);
$product_title = $stmt_p->fetchColumn();

// Xử lý gửi Form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    $review_image = null;
    $review_video = null;

    $upload_dir = '../assets/uploads/reviews/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

    // Xử lý upload ảnh
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $img_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($img_ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $review_image = 'rev_img_' . time() . '_' . rand(100, 999) . '.' . $img_ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $review_image);
        }
    }

    // Xử lý upload video
    if (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
        $vid_ext = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
        if (in_array($vid_ext, ['mp4', 'mov', 'avi', 'mkv'])) {
            $review_video = 'rev_vid_' . time() . '_' . rand(100, 999) . '.' . $vid_ext;
            move_uploaded_file($_FILES['video']['tmp_name'], $upload_dir . $review_video);
        }
    }

    $stmt_insert = $conn->prepare("INSERT INTO Product_Review (product_id, user_id, rating, comment, image, video, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt_insert->execute([$product_id, $user_id, $rating, $comment, $review_image, $review_video]);
    
    popup_success('Cảm ơn bạn!', 'Đánh giá của bạn đã được gửi kèm hình ảnh/video thực tế.', 'order_history.php');
    exit;
}
?>

<div class="container" style="margin-top: 40px; margin-bottom: 60px; max-width: 700px;">
    <h2 style="font-family: 'Playfair Display', serif; margin-bottom: 10px;">Đánh giá sản phẩm</h2>
    <p style="color: #777; margin-bottom: 30px;">Sản phẩm: <strong><?= htmlspecialchars($product_title) ?></strong></p>

    <form action="" method="POST" enctype="multipart/form-data" style="background: #fff; padding: 30px; border-radius: 8px; border: 1px solid #eee; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <div style="margin-bottom: 20px;">
            <label style="display:block; font-weight: bold; margin-bottom: 8px;">Số sao chấm điểm:</label>
            <select name="rating" style="padding: 10px; width: 100%; border-radius: 5px; border: 1px solid #ddd; outline: none; color: #D4A373; font-weight: bold;">
                <option value="5">⭐⭐⭐⭐⭐ (5 Sao - Tuyệt vời)</option>
                <option value="4">⭐⭐⭐⭐ (4 Sao - Tốt)</option>
                <option value="3">⭐⭐⭐ (3 Sao - Bình thường)</option>
                <option value="2">⭐⭐ (2 Sao - Kém)</option>
                <option value="1">⭐ (1 Sao - Quá tệ)</option>
            </select>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display:block; font-weight: bold; margin-bottom: 8px;">Nội dung bình luận:</label>
            <textarea name="comment" rows="5" required placeholder="Sản phẩm dùng mịn da không, tông màu son lên môi có chuẩn như mô tả không bạn nhỉ?..." style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-family: inherit; outline: none;"></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div>
                <label style="display:block; font-weight: bold; margin-bottom: 8px;"><i class="fa-solid fa-image"></i> Tải ảnh Swatch thực tế:</label>
                <input type="file" name="image" accept="image/*" style="width:100%; padding:8px; border: 1px dashed #D4A373; background:#fdfaf6; border-radius:4px;">
            </div>
            <div>
                <label style="display:block; font-weight: bold; margin-bottom: 8px;"><i class="fa-solid fa-video"></i> Tải Video đập hộp (Unboxing):</label>
                <input type="file" name="video" accept="video/*" style="width:100%; padding:8px; border: 1px dashed #3498db; background:#f0f7fc; border-radius:4px;">
            </div>
        </div>

        <button type="submit" style="background: #D4A373; color: white; border: none; padding: 12px 30px; font-weight: bold; border-radius: 5px; width: 100%; cursor: pointer; font-size: 16px;">GỬI ĐÁNH GIÁ SẢN PHẨM</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>