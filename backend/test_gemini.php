<?php
/**
 * Gemini API Key Validator & Tester
 * 
 * Sử dụng: 
 * 1. Truy cập: http://yoursite.com/backend/test_gemini.php
 * 2. Kiểm tra xem API key có hoạt động không
 * 
 * ⚠️ LƯU Ý: Nên xóa file này sau khi setup xong!
 */

header('Content-Type: text/html; charset=utf-8');

// Load config
require_once '../config/gemini_config.php';

// Simple HTML for testing
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gemini API Key Tester</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-weight: 500;
        }
        .status.error {
            background: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }
        .status.success {
            background: #efe;
            color: #3c3;
            border-left: 4px solid #3c3;
        }
        .status.warning {
            background: #ffe;
            color: #c90;
            border-left: 4px solid #c90;
        }
        .info-box {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }
        .info-box strong {
            color: #333;
        }
        .info-box code {
            background: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            color: #d63384;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 15px;
            font-family: monospace;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-test {
            background: #667eea;
            color: white;
        }
        .btn-test:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .btn-test:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        .btn-close {
            background: #e0e0e0;
            color: #333;
        }
        .btn-close:hover {
            background: #d0d0d0;
        }
        .response {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
            line-height: 1.6;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            display: none;
        }
        .response.show {
            display: block;
        }
        .response pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .footer-text {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
            text-align: center;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 12px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔑 Gemini API Key Tester</h1>
        <p class="subtitle">Kiểm tra xem API key của bạn có hoạt động không</p>

        <?php
        $api_key_status = 'not_set';
        $api_key_preview = '';

        if (defined('GEMINI_API_KEY')) {
            $key = GEMINI_API_KEY;
            if ($key === 'YOUR_GEMINI_API_KEY') {
                $api_key_status = 'not_set';
                echo '<div class="status error">❌ API Key chưa được thiết lập! Vui lòng cập nhật trong config/gemini_config.php</div>';
            } else {
                $api_key_status = 'set';
                $api_key_preview = substr($key, 0, 10) . '...' . substr($key, -10);
                echo '<div class="status warning">⚠️ API Key đã được thiết lập: ' . htmlspecialchars($api_key_preview) . '</div>';
            }
        } else {
            echo '<div class="status error">❌ Config file không được load đúng!</div>';
        }
        ?>

        <div class="info-box">
            <strong>📝 Hướng dẫn:</strong>
            <ol style="margin-left: 20px; margin-top: 10px;">
                <li>Nhập API key của bạn bên dưới (hoặc để nguyên nếu đã set trong config)</li>
                <li>Click "Test API" để kiểm tra kết nối</li>
                <li>Nếu thành công, chatbot sẽ hoạt động trên website</li>
            </ol>
        </div>

        <?php if ($api_key_status === 'not_set'): ?>
        <input 
            type="text" 
            id="api-key-input" 
            placeholder="Nhập Gemini API key của bạn (sk-...)"
            value=""
        >
        <?php else: ?>
        <input 
            type="text" 
            id="api-key-input" 
            placeholder="Hoặc nhập API key mới để thay thế"
            value=""
        >
        <p style="font-size: 12px; color: #666; margin-top: -10px; margin-bottom: 15px;">
            💡 Để thay đổi API key vĩnh viễn, sửa trong <code>config/gemini_config.php</code>
        </p>
        <?php endif; ?>

        <div class="btn-group">
            <button class="btn-test" onclick="testAPI()">Test API</button>
            <button class="btn-close" onclick="window.location.href='../'">Đóng</button>
        </div>

        <div id="response" class="response">
            <pre id="response-content"></pre>
        </div>

        <div class="warning-box">
            ⚠️ <strong>LƯU Ý BẢsecurity:</strong> Đừng share hoặc commit API key này lên Git công khai! Sau khi test xong, xóa file này hoặc đặt password để bảo vệ.
        </div>

        <div class="footer-text">
            LuLu Chatbot Gemini Integration - Made with ❤️
        </div>
    </div>

    <script>
    function testAPI() {
        const apiKeyInput = document.getElementById('api-key-input').value.trim();
        const apiKey = apiKeyInput || '<?php echo GEMINI_API_KEY; ?>';
        const responseDiv = document.getElementById('response');
        const responseContent = document.getElementById('response-content');
        const testBtn = event.target;

        if (!apiKey || apiKey === 'YOUR_GEMINI_API_KEY') {
            responseContent.textContent = '❌ API Key không hợp lệ!';
            responseDiv.classList.add('show');
            return;
        }

        testBtn.disabled = true;
        testBtn.textContent = '⏳ Đang test...';
        responseContent.textContent = 'Đang kết nối đến Gemini API...';
        responseDiv.classList.add('show');

        const testData = {
            message: 'Lumina Cosmetics là cửa hàng gì? (Trả lời bằng tiếng Việt, dưới 50 từ)'
        };

        // Thay tạm API key
        const originalKey = '<?php echo GEMINI_API_KEY; ?>';
        const tempFormData = new FormData();
        tempFormData.append('message', testData.message);
        tempFormData.append('api_key', apiKey);

        fetch('gemini_chat_test.php', {
            method: 'POST',
            body: tempFormData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                responseContent.textContent = '✅ SUCCESS!\n\nAPI Key hợp lệ ✓\n\nPhản hồi từ Gemini:\n' + data.message;
                responseDiv.classList.add('show');
                setTimeout(() => {
                    alert('✅ API Key hoạt động bình thường!\n\nChatbot sẽ được kích hoạt trên website.');
                }, 500);
            } else {
                responseContent.textContent = '❌ ERROR\n\n' + (data.error || 'Không xác định lỗi');
            }
        })
        .catch(err => {
            responseContent.textContent = '❌ ERROR: ' + err.message;
        })
        .finally(() => {
            testBtn.disabled = false;
            testBtn.textContent = 'Test API';
        });
    }

    // Test on Enter key
    document.getElementById('api-key-input').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            document.querySelector('.btn-test').click();
        }
    });
    </script>
</body>
</html>
