<?php
// Chatbot HTML Component
// Include this file in footer.php to display chatbot on all pages
?>

<!-- LuLu Chatbot Widget -->
<div id="chatbot-container" class="chatbot-container">
    <div class="chatbot-header">
        <div>
            <h3 class="chatbot-header-title">
                <i class="fa-solid fa-message"></i> LuLu
            </h3>
            <p class="chatbot-header-subtitle">Trợ lý AI của Lumina</p>
        </div>
        <button class="chatbot-close" id="chatbot-close" title="Đóng chat">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    
    <div class="chatbot-messages" id="chatbot-messages"></div>
    
    <div class="chatbot-input-area">
        <input 
            type="text" 
            id="chatbot-input" 
            class="chatbot-input" 
            placeholder="Nhập câu hỏi của bạn..."
            autocomplete="off"
        >
        <button id="chatbot-send" class="chatbot-send" title="Gửi tin nhắn">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </div>
</div>

<!-- Floating Button -->
<button id="chatbot-toggle" class="chatbot-toggle" title="Mở chat với LuLu">
    <i class="fa-solid fa-message"></i>
</button>

<!-- CSS & JS -->
<link rel="stylesheet" href="/Cosmetics_shop/assets/css/chatbot.css?v=<?php echo time(); ?>">
<script src="/Cosmetics_shop/assets/js/chatbot.js?v=<?php echo time(); ?>"></script>
