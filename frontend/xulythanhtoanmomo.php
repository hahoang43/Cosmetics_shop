<?php
header('Content-type: text/html; charset=utf-8');

require_once __DIR__ . '/../includes/popup_notify.php';
require_once __DIR__ . '/../config/momo_config.php';
echo popup_assets();

// Hàm gửi request MoMo
function execPostRequest($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data))
    );
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

// Lấy thông tin đơn hàng
session_start();
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$amount = isset($_POST['total_amount']) ? (int)$_POST['total_amount'] : 10000;
$status = 'pending';

function getSiteBaseUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '127.0.0.1');
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/Cosmetics_shop/frontend/xulythanhtoanmomo.php';
    $basePath = rtrim(str_replace('\\', '/', dirname(dirname($scriptName))), '/');
    if ($basePath === '') {
        $basePath = '/Cosmetics_shop';
    }

    return $scheme . '://' . $host . $basePath;
}

// Lưu đơn hàng vào database
require_once __DIR__ . '/../backend/database.php';
if (!class_exists('Database')) {
    die('Database class not found');
}
$db = new Database();
$conn = $db->link;
$sql = "INSERT INTO orders (user_id, total_money, status) VALUES ($user_id, $amount, '$status')";
$conn->query($sql);

// Tạo dữ liệu MoMo
$partnerCode = defined('MOMO_PARTNER_CODE') ? MOMO_PARTNER_CODE : '';
$accessKey = defined('MOMO_ACCESS_KEY') ? MOMO_ACCESS_KEY : '';
$secretKey = defined('MOMO_SECRET_KEY') ? MOMO_SECRET_KEY : '';
$orderInfo = "Thanh toán qua MoMo";
$orderId = time() . "";
$redirectUrl = getSiteBaseUrl() . "/frontend/order_history.php";
$ipnUrl = getSiteBaseUrl() . "/frontend/order_history.php";
$extraData = "";
$requestId = time() . "";
$requestType = "captureWallet";
$rawHash = "accessKey=$accessKey&amount=$amount&extraData=$extraData&ipnUrl=$ipnUrl&orderId=$orderId&orderInfo=$orderInfo&partnerCode=$partnerCode&redirectUrl=$redirectUrl&requestId=$requestId&requestType=$requestType";
$signature = hash_hmac("sha256", $rawHash, $secretKey);

$data = array(
    'partnerCode' => $partnerCode,
    'partnerName' => "Test",
    "storeId" => "MomoTestStore",
    'requestId' => $requestId,
    'amount' => $amount,
    'orderId' => $orderId,
    'orderInfo' => $orderInfo,
    'redirectUrl' => $redirectUrl,
    'ipnUrl' => $ipnUrl,
    'lang' => 'vi',
    'extraData' => $extraData,
    'requestType' => $requestType,
    'signature' => $signature
);

$result = execPostRequest("https://test-payment.momo.vn/v2/gateway/api/create", json_encode($data));
$jsonResult = json_decode($result, true);

if (isset($jsonResult['payUrl'])) {
    popup_success('Đang chuyển sang MoMo', 'Hệ thống sẽ chuyển bạn đến cổng thanh toán MoMo để hoàn tất giao dịch.', $jsonResult['payUrl'], 1200);
    exit();
} else {
    popup_error('Lỗi thanh toán MoMo', 'Không thể tạo liên kết thanh toán. Vui lòng thử lại sau hoặc chọn phương thức khác.');
    if (isset($jsonResult['message'])) {
        echo '<p style="text-align:center; color:#64748b;">' . htmlspecialchars($jsonResult['message']) . '</p>';
    }
}
