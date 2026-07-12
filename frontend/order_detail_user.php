<?php 
require_once '../config/database.php';
$conn = getDatabase();
require_once '../includes/header.php'; 

// Chặn nếu chưa đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user']['id'];
$fullname = $_SESSION['user']['fullname'] ?? '';

// Lấy thông tin đơn hàng
$stmt_order = $conn->prepare("SELECT * FROM Orders WHERE id = ? AND user_id = ?");
$stmt_order->execute([$order_id, $user_id]);
$order = $stmt_order->fetch();

if (!$order) {
    echo "<div class='container' style='margin-top:50px;'><h3>Đơn hàng không tồn tại hoặc bạn không có quyền xem.</h3></div>";
    include '../includes/footer.php';
    exit;
}

$s = $order['status']; // Lấy trạng thái đơn hàng để xử lý mạch timeline

// Kiểm tra điều kiện đổi trả hàng (trong vòng 7 ngày)
$order_time = strtotime($order['order_date']);
$current_time = time();
$days_passed = ($current_time - $order_time) / (60 * 60 * 24);
$can_return = ($s == 2 && $days_passed <= 7);

// Lấy chi tiết các mặt hàng trong đơn
$sql_details = "
    SELECT od.*, p.title, p.thumbnail, v.name AS variant_name, p.id AS real_product_id
    FROM Order_Details od 
    JOIN Product p ON od.product_id = p.id 
    JOIN Product_Variant pv ON od.product_variant_id = pv.id
    JOIN Variant v ON pv.variant_id = v.id
    WHERE od.order_id = ?
";
$stmt_details = $conn->prepare($sql_details);
$stmt_details->execute([$order_id]);
$details = $stmt_details->fetchAll();

// Map sản phẩm -> review_id mà chính user này đã đánh giá để tạo link xem đúng vị trí.
$reviewed_product_map = [];
if (!empty($details)) {
    $product_ids = [];
    foreach ($details as $d) {
        $product_ids[] = (int)$d['real_product_id'];
    }
    $product_ids = array_values(array_unique($product_ids));

    if (!empty($product_ids)) {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $sql_reviewed = "SELECT product_id, MAX(id) AS review_id FROM Product_Review WHERE user_id = ? AND product_id IN ($placeholders) GROUP BY product_id";
        $stmt_reviewed = $conn->prepare($sql_reviewed);
        $stmt_reviewed->execute(array_merge([$user_id], $product_ids));
        $reviewed_rows = $stmt_reviewed->fetchAll();

        foreach ($reviewed_rows as $r) {
            $reviewed_product_map[(int)$r['product_id']] = (int)$r['review_id'];
        }
    }
}
?>

<link rel="stylesheet" href="/Cosmetics_shop/assets/css/profile.css">
<style>
    /* BỘ CSS THÀNH TIMELINE TIẾN TRÌNH ĐỘNG KIỂU SHOPEE */
    .order-timeline {
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        margin-bottom: 30px;
        padding: 25px 20px;
        background: #fff;
        border: 1px solid #eee;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    }
    .timeline-line {
        position: absolute;
        top: 47px;
        left: 55px;
        right: 55px;
        height: 4px;
        background: #e0e0e0;
        z-index: 1;
    }
    .timeline-progress {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        background: #D4A373; /* Màu cam nude của Lumina Shop */
        transition: width 0.4s ease;
    }
    .timeline-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
        flex: 1;
    }
    .step-icon {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #f9f9f9;
        border: 3px solid #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #aaa;
        font-size: 16px;
        transition: all 0.3s ease;
    }
    .step-label {
        margin-top: 10px;
        font-size: 13px;
        font-weight: 500;
        color: #888;
        text-align: center;
    }
    /* Các trạng thái kích hoạt của mốc tròn */
    .timeline-step.active .step-icon {
        background: #fff;
        border-color: #D4A373;
        color: #D4A373;
        box-shadow: 0 0 10px rgba(212,163,115,0.4);
    }
    .timeline-step.completed .step-icon {
        background: #D4A373;
        border-color: #D4A373;
        color: #fff;
    }
    .timeline-step.active .step-label, .timeline-step.completed .step-label {
        color: #333;
        font-weight: 600;
    }
    /* Trạng thái khi đơn hàng bị hủy hoặc từ chối trả */
    .timeline-step.canceled .step-icon {
        background: #e74c3c;
        border-color: #e74c3c;
        color: #fff;
    }
    .timeline-step.canceled .step-label {
        color: #e74c3c;
        font-weight: 600;
    }
</style>

<div class="container" style="margin-top: 30px; margin-bottom: 60px;">
    <div class="profile-container">
        
        <aside class="profile-sidebar">
            <div class="user-brief">
                <i class="fa-solid fa-circle-user"></i>
                <div>
                    <div class="username"><?= htmlspecialchars($fullname) ?></div>
                    <a href="profile.php" style="font-size: 12px; color: #999; text-decoration: none;">Sửa hồ sơ</a>
                </div>
            </div>
            <ul class="profile-menu">
                <li><a href="profile.php"><i class="fa-solid fa-user"></i> Thông tin tài khoản</a></li>
                <li><a href="order_history.php" class="active"><i class="fa-solid fa-clipboard-list"></i> Đơn hàng của tôi</a></li>
                <li><a href="change_password.php"><i class="fa-solid fa-key"></i> Đổi mật khẩu</a></li>
                <li><a href="logout.php" style="color: #e74c3c;"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></li>
            </ul>
        </aside>

        <main class="profile-main" style="flex: 1;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 15px;">
                <h2 style="margin: 0; font-family: 'Playfair Display', serif; font-size: 24px;">Chi tiết đơn hàng #<?= htmlspecialchars(!empty($order['order_code']) ? $order['order_code'] : $order['id']) ?></h2>
                <div style="display: flex; gap: 10px;">
                    <?php if ($can_return): ?>
                        <a href="order_return.php?id=<?= $order['id'] ?>" style="background: #e67e22; color: white; padding: 8px 14px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 13px;">
                            <i class="fa-solid fa-arrow-rotate-left"></i> Yêu cầu trả hàng
                        </a>
                    <?php endif; ?>
                    <a href="order_history.php" style="background: #95a5a6; color: white; padding: 8px 14px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 13px;">
                        <i class="fa-solid fa-arrow-left"></i> Quay lại
                    </a>
                </div>
            </div>

            <div class="order-timeline">
                <?php if ($s == 3): ?>
                    <div class="timeline-line"><div class="timeline-progress" style="width: 100%; background: #e74c3c;"></div></div>
                    <div class="timeline-step completed">
                        <div class="step-icon"><i class="fa-solid fa-file-invoice"></i></div>
                        <div class="step-label">Đặt hàng thành công</div>
                    </div>
                    <div class="timeline-step canceled">
                        <div class="step-icon"><i class="fa-solid fa-xmark"></i></div>
                        <div class="step-label">Đã hủy đơn hàng</div>
                    </div>
                <?php elseif ($s == 4 || $s == 5 || $s == 6): ?>
                    <?php 
                        $p_width = '50%'; // Đang chờ duyệt
                        if ($s == 5 || $s == 6) $p_width = '100%'; // Đã xử lý xong
                    ?>
                    <div class="timeline-line"><div class="timeline-progress" style="width: <?= $p_width ?>; background: <?= $s == 6 ? '#e74c3c' : '#D4A373' ?>;"></div></div>
                    <div class="timeline-step completed">
                        <div class="step-icon"><i class="fa-solid fa-box-open"></i></div>
                        <div class="step-label">Đã giao hàng</div>
                    </div>
                    <div class="timeline-step completed">
                        <div class="step-icon"><i class="fa-solid fa-arrow-rotate-left"></i></div>
                        <div class="step-label">Yêu cầu hoàn trả</div>
                    </div>
                    <div class="timeline-step <?= $s == 4 ? 'active' : 'completed' ?>">
                        <div class="step-icon"><i class="fa-solid fa-user-shield"></i></div>
                        <div class="step-label">Shop đang duyệt</div>
                    </div>
                    <?php if ($s == 6): ?>
                        <div class="timeline-step canceled">
                            <div class="step-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
                            <div class="step-label">Từ chối hoàn trả</div>
                        </div>
                    <?php else: ?>
                        <div class="timeline-step <?= $s == 5 ? 'completed' : '' ?>">
                            <div class="step-icon"><i class="fa-solid fa-circle-check"></i></div>
                            <div class="step-label">Hoàn trả thành công</div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php 
                        $p_width = '0%';
                        if ($s == 1) $p_width = '50%';
                        if ($s == 2) $p_width = '100%';
                    ?>
                    <div class="timeline-line"><div class="timeline-progress" style="width: <?= $p_width ?>;"></div></div>
                    <div class="timeline-step completed">
                        <div class="step-icon"><i class="fa-solid fa-file-invoice"></i></div>
                        <div class="step-label">Đặt đơn thành công</div>
                    </div>
                    <div class="timeline-step <?= $s == 1 ? 'active' : ($s == 2 ? 'completed' : '') ?>">
                        <div class="step-icon"><i class="fa-solid fa-truck-ramp-box"></i></div>
                        <div class="step-label">Đang giao hàng</div>
                    </div>
                    <div class="timeline-step <?= $s == 2 ? 'completed' : '' ?>">
                        <div class="step-icon"><i class="fa-solid fa-circle-check"></i></div>
                        <div class="step-label">Giao thành công</div>
                    </div>
                <?php endif; ?>
            </div>

            <div style="background: #fdfaf6; padding: 20px; border-radius: 6px; margin-bottom: 25px; border: 1px solid #f5ebe0; line-height: 1.8; font-size: 14px;">
                <p><strong>Ngày đặt hàng:</strong> <?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></p>
                <p><strong>Người nhận:</strong> <?= htmlspecialchars($order['fullname']) ?> - <?= htmlspecialchars($order['phone_number']) ?></p>
                <p><strong>Địa chỉ giao:</strong> <?= htmlspecialchars($order['address']) ?></p>
                <?php if(!empty($order['note'])): ?>
                    <p><strong>Ghi chú từ khách:</strong> <span style="color:#666; font-style:italic;">"<?= htmlspecialchars($order['note']) ?>"</span></p>
                <?php endif; ?>
                <?php if($s >= 4 && !empty($order['return_reason'])): ?>
                    <p style="color: #e67e22; background: #fff5e6; padding: 10px; border-radius: 4px; border: 1px dashed #e67e22; margin-top: 10px;">
                        <strong>Lý do gửi hoàn trả:</strong> <?= htmlspecialchars($order['return_reason']) ?>
                    </p>
                <?php endif; ?>
            </div>

            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="background: #2c3e50; color: white; text-align: left;">
                        <th style="padding: 12px 15px; border-top-left-radius: 4px; border-bottom-left-radius: 4px;">Sản phẩm</th>
                        <th style="padding: 12px 15px; text-align: center;">Đơn giá</th>
                        <th style="padding: 12px 15px; text-align: center;">Số lượng</th>
                        <th style="padding: 12px 15px; text-align: right; border-top-right-radius: 4px; border-bottom-right-radius: 4px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($details as $item): ?>
                        <tr>
                            <td style="padding: 15px 10px; border-bottom: 1px solid #eee;">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <img src="<?= htmlspecialchars(imageSrc($item['thumbnail'] ?? '', 'products')) ?>" width="55" height="55" style="border-radius: 4px; border: 1px solid #eee; object-fit: cover;" onerror="this.onerror=null;this.src='<?= htmlspecialchars(noImageSrc('No Image')) ?>';">
                                    <div>
                                        <strong style="display: block; color: #333; margin-bottom: 4px;"><?= htmlspecialchars($item['title']) ?></strong>
                                        <span style="font-size: 12px; color: #666; background: #f5f5f5; padding: 2px 6px; border-radius: 3px; border: 1px solid #e8e8e8;">
                                            Phân loại: <?= htmlspecialchars($item['variant_name']) ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 15px; border-bottom: 1px solid #eee; color: #D4A373; font-weight: bold; text-align: center;">
                                <?= number_format($item['price'], 0, ',', '.') ?>đ
                            </td>
                            <td style="padding: 15px; border-bottom: 1px solid #eee; text-align: center; font-weight: bold;">
                                <?= $item['num'] ?>
                            </td>
                            <td style="padding: 15px; border-bottom: 1px solid #eee; text-align: right;">
                                <?php if($order['status'] == 2): ?>
                                    <?php $review_id = $reviewed_product_map[(int)$item['real_product_id']] ?? 0; ?>
                                    <?php $has_reviewed = $review_id > 0; ?>
                                    <?php if ($has_reviewed): ?>
                                        <a href="product_detail.php?id=<?= (int)$item['real_product_id'] ?>#review-id-<?= $review_id ?>" style="display: inline-block; background: #1f7a4c; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 500; transition: 0.3s;" onmouseover="this.style.background='#21935b'" onmouseout="this.style.background='#1f7a4c'">
                                            <i class="fa-solid fa-eye"></i> Xem đánh giá
                                        </a>
                                    <?php else: ?>
                                        <a href="write_review.php?product_id=<?= (int)$item['real_product_id'] ?>" style="display: inline-block; background: #333; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 500; transition: 0.3s;" onmouseover="this.style.background='#D4A373'" onmouseout="this.style.background='#333'">
                                            <i class="fa-solid fa-pen-clip"></i> Viết đánh giá
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:#aaa; font-size:12px; font-style: italic;">Chưa khả dụng</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="text-align: right; margin-top: 25px; padding-top: 15px; border-top: 1px solid #eee;">
                <p style="font-size: 15px; color: #555;">Tổng tiền đơn hàng: <strong style="color: #e74c3c; font-size: 22px; margin-left: 10px;"><?= number_format($order['total_money'], 0, ',', '.') ?>đ</strong></p>
            </div>
        </main>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>