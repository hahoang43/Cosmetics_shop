<?php
header('Content-type: text/html; charset=utf-8');

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

// Lưu đơn hàng vào database
require_once '../backend/database.php';
$db = new Database();
$conn = $db->link;
$sql = "INSERT INTO orders (user_id, total_money, status) VALUES ($user_id, $amount, '$status')";
$conn->query($sql);

// Tạo dữ liệu MoMo
$partnerCode = 'MOMOBKUN20180529';
$accessKey = 'klm05TvNBzhg7h7j';
$secretKey = 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa';
$orderInfo = "Thanh toán qua MoMo";
$orderId = time() . "";
$redirectUrl = "http://localhost/Website-Sneaker/page/thanhtoan.php";
$ipnUrl = "http://localhost/Website-Sneaker/page/thanhtoan.php";
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
    header('Location: ' . $jsonResult['payUrl']);
    exit();
} else {
    echo '<h2 style="color:red;text-align:center;">Lỗi khi tạo thanh toán MoMo!</h2>';
    if (isset($jsonResult['message'])) {
        echo '<p style="text-align:center;">' . htmlspecialchars($jsonResult['message']) . '</p>';
    }
}
?>