// LuLu Chatbot - Gemini Integration
class LuLuChatbot {
    constructor() {
        this.container = document.getElementById('chatbot-container');
        this.toggle = document.getElementById('chatbot-toggle');
        this.messagesDiv = document.getElementById('chatbot-messages');
        this.input = document.getElementById('chatbot-input');
        this.sendBtn = document.getElementById('chatbot-send');
        this.closeBtn = document.getElementById('chatbot-close');
        
        this.isOpen = false;
        this.isLoading = false;
        this.messageHistory = [];

        this.init();
    }

    init() {
        // Event listeners
        this.toggle.addEventListener('click', () => this.toggleChat());
        this.closeBtn.addEventListener('click', () => this.toggleChat());
        this.sendBtn.addEventListener('click', () => this.sendMessage());
        this.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Load chat history from localStorage
        this.loadHistory();

        // Display welcome message if first time
        if (this.messageHistory.length === 0) {
            this.addBotMessage('Xin chào! 👋 Tôi là LuLu, hãy hỏi tôi bất cứ điều gì về Lumina Cosmetics 😊');
        } else {
            this.displayHistory();
        }
    }

    toggleChat() {
        this.isOpen = !this.isOpen;
        
        if (this.isOpen) {
            this.container.classList.add('active');
            this.toggle.classList.add('active');
            this.input.focus();
        } else {
            this.container.classList.remove('active');
            this.toggle.classList.remove('active');
        }
    }

    sendMessage() {
        const message = this.input.value.trim();
        
        if (!message || this.isLoading) {
            return;
        }

        // Add user message
        this.addUserMessage(message);
        this.input.value = '';
        this.input.style.height = 'auto';

        // Send to backend
        this.isLoading = true;
        this.showLoadingIndicator();

        fetch('/Cosmetics_shop/backend/gemini_chat.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: message
            })
        })
        .then(response => response.json())
        .then(data => {
            this.removeLoadingIndicator();
            
            if (data.success) {
                this.addBotMessage(data.message, data.suggestions || []);
            } else if (data.error) {
                let errorMsg = data.error;
                
                // Handle specific errors
                if (data.error.includes('API key')) {
                    errorMsg = 'Lỗi: API key chưa được thiết lập. Vui lòng liên hệ admin.';
                } else if (data.error.includes('kết nối')) {
                    errorMsg = 'Lỗi kết nối. Vui lòng kiểm tra internet và thử lại.';
                }
                
                this.addBotMessage('❌ ' + errorMsg);
            }
        })
        .catch(error => {
            this.removeLoadingIndicator();
            console.error('Error:', error);
            this.addBotMessage('❌ Có lỗi xảy ra. Vui lòng thử lại sau.');
        })
        .finally(() => {
            this.isLoading = false;
        });
    }

    addUserMessage(message) {
        const messageObj = {
            type: 'user',
            content: message,
            timestamp: new Date()
        };

        this.messageHistory.push(messageObj);
        this.saveHistory();
        this.renderMessage(messageObj);
    }

    addBotMessage(message, suggestions = []) {
        const messageObj = {
            type: 'bot',
            content: message,
            suggestions: suggestions,
            timestamp: new Date()
        };

        this.messageHistory.push(messageObj);
        this.saveHistory();
        this.renderMessage(messageObj);
    }

    renderMessage(messageObj) {
        const messageEl = document.createElement('div');
        messageEl.className = 'chatbot-message ' + messageObj.type;

        const contentEl = document.createElement('div');
        contentEl.className = 'chatbot-message-content';
        contentEl.innerHTML = this.escapeHtml(messageObj.content)
            .replaceAll('\n', '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/__(.*?)__/g, '<em>$1</em>');

        if (Array.isArray(messageObj.suggestions) && messageObj.suggestions.length > 0) {
            const recommendationsEl = this.renderProductSuggestions(messageObj.suggestions);
            contentEl.appendChild(recommendationsEl);
        }

        messageEl.appendChild(contentEl);

        const timeEl = document.createElement('div');
        timeEl.className = 'chatbot-message-time';
        timeEl.textContent = this.formatTime(messageObj.timestamp);
        messageEl.appendChild(timeEl);

        this.messagesDiv.appendChild(messageEl);
        this.scrollToBottom();
    }

    renderProductSuggestions(products) {
        const wrapper = document.createElement('div');
        wrapper.className = 'chatbot-recommendations';

        const title = document.createElement('div');
        title.className = 'chatbot-recommendations-title';
        title.textContent = 'Gợi ý sản phẩm';
        wrapper.appendChild(title);

        const grid = document.createElement('div');
        grid.className = 'chatbot-product-grid';

        products.forEach((product) => {
            const card = document.createElement('a');
            card.className = 'chatbot-product-card';
            card.href = product.url || ('/Cosmetics_shop/frontend/product_detail.php?id=' + encodeURIComponent(product.id));
            card.title = product.title || 'Xem chi tiết sản phẩm';

            const thumb = document.createElement('img');
            thumb.className = 'chatbot-product-thumb';
            thumb.src = product.thumbnail_src || '/Cosmetics_shop/assets/images/no-image.png';
            thumb.alt = product.title || 'Sản phẩm gợi ý';
            card.appendChild(thumb);

            const meta = document.createElement('div');
            meta.className = 'chatbot-product-meta';

            const name = document.createElement('div');
            name.className = 'chatbot-product-title';
            name.textContent = product.title || 'Không tên';
            meta.appendChild(name);

            if (product.category_name) {
                const category = document.createElement('div');
                category.className = 'chatbot-product-category';
                category.textContent = product.category_name;
                meta.appendChild(category);
            }

            const price = document.createElement('div');
            price.className = 'chatbot-product-price';
            const priceValue = Number(product.price || 0);
            price.textContent = priceValue > 0 ? ('Giá từ: ' + priceValue.toLocaleString('vi-VN') + 'đ') : 'Liên hệ';
            meta.appendChild(price);

            const action = document.createElement('div');
            action.className = 'chatbot-product-action';
            action.textContent = 'Nhấn để xem chi tiết';
            meta.appendChild(action);

            card.appendChild(meta);
            grid.appendChild(card);
        });

        wrapper.appendChild(grid);
        return wrapper;
    }

    showLoadingIndicator() {
        const loaderEl = document.createElement('div');
        loaderEl.className = 'chatbot-message bot';
        loaderEl.id = 'chatbot-loader';
        loaderEl.innerHTML = `
            <div class="chatbot-message-content">
                <div class="chatbot-loading">
                    <div class="chatbot-loading-dot"></div>
                    <div class="chatbot-loading-dot"></div>
                    <div class="chatbot-loading-dot"></div>
                </div>
            </div>
        `;
        this.messagesDiv.appendChild(loaderEl);
        this.scrollToBottom();
    }

    removeLoadingIndicator() {
        const loader = document.getElementById('chatbot-loader');
        if (loader) {
            loader.remove();
        }
    }

    scrollToBottom() {
        setTimeout(() => {
            this.messagesDiv.scrollTop = this.messagesDiv.scrollHeight;
        }, 100);
    }

    saveHistory() {
        // Limit to last 10 messages
        const limitedHistory = this.messageHistory.slice(-10);
        localStorage.setItem('luluChatHistory', JSON.stringify(limitedHistory));
    }

    loadHistory() {
        const saved = localStorage.getItem('luluChatHistory');
        if (saved) {
            try {
                this.messageHistory = JSON.parse(saved).map(msg => ({
                    ...msg,
                    timestamp: new Date(msg.timestamp)
                }));
            } catch (e) {
                console.error('Error loading chat history:', e);
                this.messageHistory = [];
            }
        }
    }

    displayHistory() {
        this.messageHistory.forEach(msg => this.renderMessage(msg));
        this.scrollToBottom();
    }

    formatTime(date) {
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${hours}:${minutes}`;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    clearHistory() {
        if (confirm('Bạn có chắc muốn xóa lịch sử chat?')) {
            this.messageHistory = [];
            localStorage.removeItem('luluChatHistory');
            this.messagesDiv.innerHTML = '';
            this.addBotMessage('Xin chào! 👋 Tôi là LuLu, hãy hỏi tôi bất cứ điều gì về Lumina Cosmetics 😊');
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if chatbot elements exist
    if (document.getElementById('chatbot-toggle')) {
        globalThis.luluChatbot = new LuLuChatbot();
    }
});
