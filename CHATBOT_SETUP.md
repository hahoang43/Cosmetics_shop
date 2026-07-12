# LuLu Chatbot - Hướng Dẫn Setup

## 🎯 Giới Thiệu
LuLu là một chatbot AI được tích hợp trên tất cả các trang của Lumina Cosmetics, sử dụng Gemini API để trả lời câu hỏi của khách hàng về mỹ phẩm và chính sách cửa hàng.

## 📋 Thành Phần
- **Backend**: `backend/gemini_chat.php` - API handler
- **Config**: `config/gemini_config.php` - Cấu hình API key
- **Frontend**: 
  - `includes/chatbot.php` - HTML component
  - `assets/css/chatbot.css` - Styling
  - `assets/js/chatbot.js` - Logic chatbot
- **Integration**: Đã thêm vào `includes/footer.php`

## 🔑 Bước 1: Lấy Gemini API Key

### Option 1: Sử dụng Google AI Studio (Miễn phí)
1. Truy cập: https://aistudio.google.com/app/apikey
2. Đăng nhập bằng tài khoản Google
3. Click "Create API key in new project"
4. Copy API key

### Option 2: Sử dụng Google Cloud Console
1. Truy cập: https://console.cloud.google.com/
2. Tạo project mới
3. Bật Generative Language API
4. Tạo API key
5. Copy API key

## ⚙️ Bước 2: Cập Nhật API Key

Mở file `config/gemini_config.php` và thay thế:

```php
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY');
```

bằng:

```php
define('GEMINI_API_KEY', 'sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
```

**⚠️ QUAN TRỌNG**: Không commit API key lên Git công khai!

## 📝 Bước 3: Kiểm Tra (Optional)

Để test xem API có hoạt động không:

1. Mở browser và truy cập bất kỳ trang nào trên website
2. Click button "💬" ở góc dưới phải
3. Gõ một câu hỏi (vd: "Cửa hàng mở giờ nào?")
4. Chờ phản hồi từ LuLu

## 🎨 Tùy Chỉnh

### Thay Đổi Tên Chatbot
Sửa trong `config/gemini_config.php`:
```php
define('CHATBOT_NAME', 'LuLu');  // Thay thành tên khác
```

### Thay Đổi Màu Sắc
Sửa trong `assets/css/chatbot.css`:
```css
/* Tìm và thay thế màu #D4A373 bằng màu khác */
```

### Thay Đổi Vị Trí Button
Sửa trong `assets/css/chatbot.css`:
```css
.chatbot-toggle {
    bottom: 20px;  /* Khoảng cách từ dưới */
    right: 20px;   /* Khoảng cách từ phải */
}
```

### Thay Đổi Kích Thước Popup
Sửa trong `assets/css/chatbot.css`:
```css
.chatbot-container {
    width: 380px;   /* Chiều rộng */
    height: 600px;  /* Chiều cao */
}
```

## 📱 Tính Năng

✅ Popup chat trên tất cả các trang
✅ Lưu lịch sử chat (localStorage)
✅ Response thông minh từ Gemini AI
✅ Responsive trên mobile/tablet
✅ Auto-scroll tin nhắn mới
✅ Loading indicator
✅ Xử lý lỗi thông minh

## 🔒 Bảo Mật

- API key được lưu ở backend (file PHP) - không bao giờ hiển thị client
- Tất cả requests đi qua backend server
- Input được sanitize trước khi gửi
- Có rate limiting cơ bản từ Gemini API

## 🐛 Troubleshooting

### Chatbot không xuất hiện
- Kiểm tra console (F12) có lỗi gì không
- Chắc chắn footer.php đã được include trên tất cả trang
- Clear browser cache

### Chat không hoạt động
- Kiểm tra API key trong `config/gemini_config.php`
- Kiểm tra network tab (F12 > Network) có error gì không
- Xem error message trong popup

### API key error
- Chắc chắn API key không bị sai hoặc cắt ngắn
- Kiểm tra API key còn hiệu lực không
- Thử tạo API key mới

### Bot trả lời sai
- System prompt có thể được tùy chỉnh trong `backend/gemini_chat.php`
- Tăng `temperature` để bot "sáng tạo" hơn (0.0-1.0)
- Giảm `maxOutputTokens` nếu response quá dài

## 📚 API Documentation

- Gemini API: https://ai.google.dev/
- Models: https://ai.google.dev/models
- Pricing: https://ai.google.dev/pricing

## 💡 Tips

1. **Cải thiện response**: Edit `system_context` trong `backend/gemini_chat.php` để thêm thông tin về cửa hàng
2. **Lưu chat**: Hiện tại chat được lưu trong localStorage (chỉ trên browser đó)
3. **Xóa lịch sử**: Dùng DevTools Console: `localStorage.removeItem('luluChatHistory')`

## 🚀 Phát Triển Tiếp Theo

- [ ] Thêm database để lưu chat history trên server
- [ ] Admin panel để xem tất cả chats từ users
- [ ] Thêm tính năng transfer sang nhân viên (live chat)
- [ ] Multi-language support
- [ ] Analytics & insights

---

**Hỗ Trợ**: Nếu có vấn đề gì, vui lòng liên hệ với team phát triển.
