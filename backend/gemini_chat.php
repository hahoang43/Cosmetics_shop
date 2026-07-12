<?php
// Backend API handler for Gemini Chatbot
header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';
require_once '../config/gemini_config.php';
require_once __DIR__ . '/../includes/image_helper.php';

$conn = getDatabase();

const CHATBOT_TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

function extractChatKeywords(string $message): array
{
    $message = mb_strtolower($message, 'UTF-8');
    $message = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $message);
    $parts = preg_split('/\s+/u', trim($message), -1, PREG_SPLIT_NO_EMPTY);
    $stopwords = [
        'và', 'hoac', 'hoặc', 'cho', 'mình', 'toi', 'tôi', 'ban', 'bạn', 'muốn', 'can', 'cần', 'shop', 'luu', 'lulu', 'giup', 'giúp',
        'xin', 'hãy', 'hay', 'co', 'có', 'khong', 'không', 'loai', 'loại', 'san', 'sản', 'pham', 'phẩm', 'nao', 'nào', 'de', 'để', 'tu', 'từ'
    ];

    $keywords = [];
    foreach ($parts as $part) {
        if (mb_strlen($part, 'UTF-8') < 3) {
            continue;
        }
        if (in_array($part, $stopwords, true)) {
            continue;
        }
        $keywords[] = $part;
    }

    $topicMap = [
        'skincare' => ['skincare', 'skin care', 'chăm sóc da', 'cham soc da', 'dưỡng da', 'duong da', 'da dầu', 'da mun', 'da mụn', 'mụn', 'mun', 'serum', 'toner', 'kem dưỡng', 'kem dưỡng ẩm', 'kem duong', 'sữa rửa mặt', 'sua rua mat'],
        'makeup' => ['makeup', 'trang điểm', 'trang diem', 'son', 'son môi', 'phấn', 'phan', 'nền', 'nen', 'mascara', 'eyeliner', 'má hồng', 'ma hong', 'concealer'],
        'bodycare' => ['bodycare', 'body care', 'body', 'dưỡng thể', 'duong the', 'sữa tắm', 'sua tam'],
        'fragrance' => ['nước hoa', 'nuoc hoa', 'perfume', 'hương', 'huong'],
    ];

    foreach ($topicMap as $aliases) {
        foreach ($aliases as $alias) {
            if (mb_stripos($message, $alias, 0, 'UTF-8') !== false) {
                $keywords = array_merge($keywords, $aliases);
                break;
            }
        }
    }

    $keywords = array_values(array_unique(array_filter($keywords, static function ($keyword) {
        return mb_strlen($keyword, 'UTF-8') >= 3;
    })));

    return array_slice($keywords, 0, 12);
}

function analyzeUserIntent(string $message): array
{
    $message_lower = mb_strtolower($message, 'UTF-8');
    
    // Phát hiện loại da
    $skinTypes = [
        'da dầu' => ['da dầu', 'da dau', 'dầu', 'dau', 'nhờn'],
        'da khô' => ['da khô', 'da kho', 'khô', 'kho'],
        'da hỗn hợp' => ['da hỗn hợp', 'da hon hop', 'hỗn hợp', 'hon hop'],
        'da nhạy cảm' => ['da nhạy cảm', 'da nhay cam', 'nhạy cảm', 'nhay cam', 'kích ứng'],
        'da bình thường' => ['da bình thường', 'da binh thuong', 'bình thường', 'binh thuong']
    ];
    
    // Phát hiện vấn đề da
    $skinConcerns = [
        'mụn' => ['mụn', 'mun', 'mụn ẩn', 'mun an', 'mụn đầu đen', 'mun dau den'],
        'nám' => ['nám', 'nam', 'nám tàn nhang', 'nam tan nhang'],
        'lão hóa' => ['lão hóa', 'lao hoa', 'chống lão hóa', 'chong lao hoa', 'chồng chéo', 'chong cheo'],
        'nhăn' => ['nhăn', 'nhan', 'chống nhăn', 'chong nhan'],
        'thâm quầng' => ['thâm quầng', 'tham quang', 'quầng thâm', 'quang tham'],
        'mất nước' => ['mất nước', 'mat nuoc', 'cấp ẩm', 'cap am'],
        'bóng nhờn' => ['bóng nhờn', 'bong nhon', 'kiểm dầu', 'kiem dau']
    ];
    
    // Phát hiện loại sản phẩm
    $productTypes = [
        'serum' => ['serum'],
        'toner' => ['toner', 'nước cân bằng', 'nuoc can bang'],
        'kem dưỡng' => ['kem dưỡng', 'kem duong', 'kem', 'cream'],
        'mặt nạ' => ['mặt nạ', 'mat na', 'mask'],
        'sữa rửa mặt' => ['sữa rửa mặt', 'sua rua mat', 'sữa rửa', 'sua rua'],
        'tẩy tế bào chết' => ['tẩy tế bào chết', 'tay te bao chet', 'scrub', 'exfoliate'],
        'son môi' => ['son môi', 'son moi', 'son'],
        'mascara' => ['mascara'],
        'nền' => ['nền', 'nen', 'foundation', 'primer'],
        'phấn' => ['phấn', 'phan', 'powder', 'blush'],
        'eyeliner' => ['eyeliner'],
        'nước hoa' => ['nước hoa', 'nuoc hoa', 'perfume']
    ];
    
    $detected = [
        'skinTypes' => [],
        'concerns' => [],
        'productTypes' => []
    ];
    
    foreach ($skinTypes as $type => $keywords) {
        foreach ($keywords as $keyword) {
            if (mb_stripos($message_lower, $keyword, 0, 'UTF-8') !== false) {
                $detected['skinTypes'][] = $type;
                break;
            }
        }
    }
    
    foreach ($skinConcerns as $concern => $keywords) {
        foreach ($keywords as $keyword) {
            if (mb_stripos($message_lower, $keyword, 0, 'UTF-8') !== false) {
                $detected['concerns'][] = $concern;
                break;
            }
        }
    }
    
    foreach ($productTypes as $type => $keywords) {
        foreach ($keywords as $keyword) {
            if (mb_stripos($message_lower, $keyword, 0, 'UTF-8') !== false) {
                $detected['productTypes'][] = $type;
                break;
            }
        }
    }
    
    return $detected;
}

function fetchSuggestedProducts(PDO $conn, string $message, int $limit = 4): array
{
    $keywords = extractChatKeywords($message);
    $userIntent = analyzeUserIntent($message);

    if (empty($keywords) && empty($userIntent['concerns']) && empty($userIntent['productTypes'])) {
        $stmt = $conn->prepare(
            "SELECT p.id, p.title, p.price, p.old_price, p.thumbnail, p.brand, c.name AS category_name, LEFT(COALESCE(p.description, ''), 140) AS excerpt
             FROM Product p
             LEFT JOIN Category c ON p.category_id = c.id
             WHERE p.deleted = 0
             ORDER BY p.created_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Xây dựng query với điểm số cao hơn cho sản phẩm khớp với intent
        $conditions = [];
        $scoreParts = ['0'];
        $params = [];

        // Đặt điểm cao cho match với product type (đây là yêu cầu chính của user)
        if (!empty($userIntent['productTypes'])) {
            foreach ($userIntent['productTypes'] as $productType) {
                $like = '%' . $productType . '%';
                $conditions[] = '(p.title LIKE ? OR p.description LIKE ? OR c.name LIKE ?)';
                $scoreParts[] = '((CASE WHEN p.title LIKE ? THEN 15 ELSE 0 END) + (CASE WHEN p.description LIKE ? THEN 8 ELSE 0 END) + (CASE WHEN c.name LIKE ? THEN 12 ELSE 0 END))';
                array_push($params, $like, $like, $like, $like, $like, $like);
            }
        }

        // Đặt điểm trung bình cho match với concerns
        if (!empty($userIntent['concerns'])) {
            foreach ($userIntent['concerns'] as $concern) {
                $like = '%' . $concern . '%';
                $conditions[] = '(p.description LIKE ? OR p.title LIKE ?)';
                $scoreParts[] = '((CASE WHEN p.description LIKE ? THEN 10 ELSE 0 END) + (CASE WHEN p.title LIKE ? THEN 8 ELSE 0 END))';
                array_push($params, $like, $like, $like, $like);
            }
        }

        // Đặt điểm thấp cho match với keywords chung
        if (!empty($keywords)) {
            foreach ($keywords as $keyword) {
                $like = '%' . $keyword . '%';
                $conditions[] = '(p.title LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)';
                $scoreParts[] = '((CASE WHEN p.title LIKE ? THEN 5 ELSE 0 END) + (CASE WHEN p.description LIKE ? THEN 2 ELSE 0 END) + (CASE WHEN p.brand LIKE ? THEN 3 ELSE 0 END))';
                array_push($params, $like, $like, $like, $like, $like, $like);
            }
        }

        if (!empty($conditions)) {
            $sql = "SELECT p.id, p.title, p.price, p.old_price, p.thumbnail, p.brand, c.name AS category_name, LEFT(COALESCE(p.description, ''), 140) AS excerpt, (" . implode(' + ', $scoreParts) . ") AS score
                    FROM Product p
                    LEFT JOIN Category c ON p.category_id = c.id
                    WHERE p.deleted = 0 AND (" . implode(' OR ', $conditions) . ")
                    ORDER BY score DESC, p.created_at DESC
                    LIMIT " . (int)$limit;

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $results = [];
        }

        // Nếu không tìm được sản phẩm khớp, lấy sản phẩm mới nhất
        if (empty($results)) {
            $stmt = $conn->prepare(
                "SELECT p.id, p.title, p.price, p.old_price, p.thumbnail, p.brand, c.name AS category_name, LEFT(COALESCE(p.description, ''), 140) AS excerpt
                 FROM Product p
                 LEFT JOIN Category c ON p.category_id = c.id
                 WHERE p.deleted = 0
                 ORDER BY p.created_at DESC
                 LIMIT ?"
            );
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    foreach ($results as &$product) {
        $product['thumbnail_src'] = imageSrc($product['thumbnail'] ?? '', 'products');
        $product['url'] = '/Cosmetics_shop/frontend/product_detail.php?id=' . (int)$product['id'];
        $product['excerpt'] = trim((string)($product['excerpt'] ?? ''));
    }
    unset($product);

    return $results;
}

function buildFallbackReply(array $products): string
{
    if (empty($products)) {
        return 'Mình chưa tìm được sản phẩm phù hợp ngay lúc này. Bạn có thể nói rõ hơn về loại da, nhu cầu hoặc loại sản phẩm bạn đang muốn tìm.';
    }

    $lines = [
        'Mình đã tìm được một vài sản phẩm phù hợp từ dữ liệu cửa hàng:',
    ];

    foreach (array_slice($products, 0, 3) as $product) {
        $lines[] = '- ' . $product['title'] . ' (' . number_format((int)$product['price'], 0, ',', '.') . 'đ)';
    }

    $lines[] = 'Bạn có thể bấm vào từng sản phẩm bên dưới để xem chi tiết.';

    return implode("\n", $lines);
}

$input = json_decode(file_get_contents('php://input'), true);
$user_message = $input['message'] ?? '';

if (empty($user_message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tin nhắn không được trống']);
    exit;
}

// Sanitize input
$user_message_raw = trim($user_message);
$user_message = htmlspecialchars($user_message_raw, ENT_QUOTES, 'UTF-8');
$suggested_products = fetchSuggestedProducts($conn, $user_message_raw, 4);

try {
    $dataset_context_lines = [];
    foreach ($suggested_products as $index => $product) {
        $dataset_context_lines[] = sprintf(
            '%d. %s | Danh mục: %s | Giá: %s | Thương hiệu: %s | Mô tả ngắn: %s',
            $index + 1,
            $product['title'] ?: 'Không tên',
            $product['category_name'] ?: 'Chưa phân loại',
            number_format((int)$product['price'], 0, ',', '.') . 'đ',
            $product['brand'] ?: 'Chưa rõ',
            $product['excerpt'] ?: 'Không có mô tả ngắn'
        );
    }

    $dataset_context = empty($dataset_context_lines)
        ? "- Hiện chưa tìm thấy sản phẩm khớp rõ ràng trong dataset."
        : implode("\n", $dataset_context_lines);

    // Chuẩn bị context cho chatbot - chỉ trả lời về Lumina Cosmetics
    $system_context = "Bạn là LuLu, một trợ lý chatbot cho Lumina Cosmetics - một cửa hàng mỹ phẩm cao cấp.
    
Thông tin về Lumina Cosmetics:
- Địa chỉ: Số 2, Võ Oanh, P.25, Bình Thạnh, TP.HCM
- Hotline: 0123.456.789
- Email: support@lumina.vn
- Các loại sản phẩm: Son môi, Serum, Kem dưỡng, Toner, Mặt nạ, v.v...
- Chính sách: Đổi trả trong 7 ngày, bảo hành chất lượng, vận chuyển tận nơi
- Giờ làm việc: 8:00 - 22:00 (hàng ngày)

DANH SÁCH SẢN PHẨM KHUYẾN NGHỊ DÀNH CHO KHÁCH (ưu tiên gợi ý những sản phẩm này trước):
{$dataset_context}

YÊU CẦU QUAN TRỌNG VỀ TƯ VẤN SẢN PHẨM:
1. ⭐ LUÔN ưu tiên và gợi ý các sản phẩm trong danh sách KHUYẾN NGHỊ trên trước tiên
2. Chỉ gợi ý sản phẩm từ danh sách khuyến nghị - KHÔNG bịa ra hoặc gợi ý các sản phẩm khác
3. Khi khách hỏi về sản phẩm cụ thể (loại da, vấn đề da, loại sản phẩm), hãy trả lời bằng cách gợi ý sản phẩm phù hợp nhất từ danh sách
4. Giải thích tại sao sản phẩm đó phù hợp với yêu cầu của khách (ví dụ: \"Serum này chứa hyaluronic acid giúp cấp ẩm cho da khô\")
5. Nếu không có sản phẩm phù hợp trong danh sách, hãy liên hệ khách để hiểu rõ hơn yêu cầu
6. Trả lời ngắn gọn (dưới 150 từ), lịch sự và tư vấn chuyên nghiệp

Nếu câu hỏi không liên quan đến Lumina Cosmetics hoặc mỹ phẩm, hãy từ chối lịch sự và hướng dẫn về mỹ phẩm của Lumina.";

    $geminiAvailable = defined('GEMINI_API_KEY') && GEMINI_API_KEY !== 'YOUR_GEMINI_API_KEY';

    if (!$geminiAvailable) {
        echo json_encode([
            'success' => true,
            'message' => htmlspecialchars(buildFallbackReply($suggested_products), ENT_QUOTES, 'UTF-8'),
            'suggestions' => $suggested_products,
            'timestamp' => date(CHATBOT_TIMESTAMP_FORMAT)
        ]);
        exit;
    }

    // Gọi Gemini API
    $curl = curl_init();
    
    $request_body = json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => $system_context],
                    ['text' => $user_message]
                ]
            ]
        ],
        'generationConfig' => [
            'maxOutputTokens' => 500,
            'temperature' => 0.7
        ],
        'safetySettings' => [
            [
                'category' => 'HARM_CATEGORY_HARASSMENT',
                'threshold' => 'BLOCK_ONLY_HIGH'
            ]
        ]
    ]);

    $url = GEMINI_API_URL . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY;

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
            'User-Agent: LuLuChatbot/1.0'
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

    if ($http_code !== 200) {
        $error_data = json_decode($response, true);
        $fallback_message = buildFallbackReply($suggested_products);
        echo json_encode([
            'success' => true,
            'message' => htmlspecialchars($fallback_message, ENT_QUOTES, 'UTF-8'),
            'suggestions' => $suggested_products,
            'timestamp' => date(CHATBOT_TIMESTAMP_FORMAT),
            'warning' => $error_data['error']['message'] ?? 'Lỗi từ Gemini API'
        ]);
        exit;
    }

    $gemini_response = json_decode($response, true);

    // Xử lý response
    if (isset($gemini_response['candidates'][0]['content']['parts'][0]['text'])) {
        $bot_message = $gemini_response['candidates'][0]['content']['parts'][0]['text'];
        echo json_encode([
            'success' => true,
            'message' => htmlspecialchars($bot_message, ENT_QUOTES, 'UTF-8'),
            'suggestions' => $suggested_products,
            'timestamp' => date(CHATBOT_TIMESTAMP_FORMAT)
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => htmlspecialchars(buildFallbackReply($suggested_products), ENT_QUOTES, 'UTF-8'),
            'suggestions' => $suggested_products,
            'timestamp' => date(CHATBOT_TIMESTAMP_FORMAT)
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi server: ' . $e->getMessage()]);
}

