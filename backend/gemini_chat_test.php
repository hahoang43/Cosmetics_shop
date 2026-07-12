<?php
/**
 * Gemini Chat Test Handler
 * Used by test_gemini.php to validate API keys
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../config/gemini_config.php';

$api_key = isset($_POST['api_key']) ? trim($_POST['api_key']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : 'Hello';

if (empty($api_key)) {
    http_response_code(400);
    echo json_encode(['error' => 'API key không được trống']);
    exit;
}

if (strlen($api_key) < 20) {
    http_response_code(400);
    echo json_encode(['error' => 'API key không hợp lệ (quá ngắn)']);
    exit;
}

try {
    $curl = curl_init();
    
    $request_body = json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => $message]
                ]
            ]
        ],
        'generationConfig' => [
            'maxOutputTokens' => 200,
            'temperature' => 0.7
        ]
    ]);

    $url = GEMINI_API_URL . GEMINI_MODEL . ':generateContent?key=' . $api_key;

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $request_body,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'User-Agent: GeminiTester/1.0'
        ],
    ]);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);

    if ($curl_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi kết nối: ' . $curl_error]);
        exit;
    }

    if ($http_code === 401) {
        http_response_code(401);
        echo json_encode(['error' => 'API key không hợp lệ (401 Unauthorized)']);
        exit;
    }

    if ($http_code !== 200) {
        $error_data = json_decode($response, true);
        http_response_code($http_code);
        $error_msg = $error_data['error']['message'] ?? 'Lỗi từ Gemini API (HTTP ' . $http_code . ')';
        echo json_encode(['error' => $error_msg]);
        exit;
    }

    $gemini_response = json_decode($response, true);

    if (isset($gemini_response['candidates'][0]['content']['parts'][0]['text'])) {
        $bot_message = $gemini_response['candidates'][0]['content']['parts'][0]['text'];
        echo json_encode([
            'success' => true,
            'message' => htmlspecialchars($bot_message, ENT_QUOTES, 'UTF-8')
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Không thể xử lý phản hồi từ Gemini']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi server: ' . $e->getMessage()]);
}

