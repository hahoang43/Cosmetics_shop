<?php
// Safe-ish image proxy for external product thumbnails.
// Usage: /backend/image_proxy.php?url=https%3A%2F%2F...

$url = isset($_GET['url']) ? trim($_GET['url']) : '';
if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    exit('Invalid image URL');
}

$parts = parse_url($url);
$host = strtolower($parts['host'] ?? '');
$allowedHosts = [
    'down-vn.img.susercontent.com',
    'img.susercontent.com',
    'cf.shopee.vn',
    'cf.shopee.vn',
];

if (!in_array($host, $allowedHosts, true)) {
    http_response_code(403);
    exit('Host not allowed');
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; CosmeticsShopImageProxy/1.0)');
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$body = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($body === false || $httpCode !== 200) {
    http_response_code(404);
    exit('Image not found');
}

if ($contentType) {
    header('Content-Type: ' . $contentType);
} else {
    header('Content-Type: image/jpeg');
}
header('Cache-Control: public, max-age=86400');
echo $body;
