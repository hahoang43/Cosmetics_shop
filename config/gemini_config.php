<?php
// Gemini API Configuration
// Thay YOUR_GEMINI_API_KEY bằng API key thực của bạn từ Google AI Studio
// Lấy API key tại: https://aistudio.google.com/app/apikey

define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('GEMINI_MODEL', 'gemini-2.5-flash');
define('GEMINI_MODEL_LABEL', 'Gemini 2.5 Flash');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/');

// Chatbot Settings
define('CHATBOT_NAME', 'LuLu');
define('CHATBOT_WELCOME_MSG', 'Xin chào! Tôi là ' . CHATBOT_NAME . ', hãy hỏi tôi bất cứ điều gì về Lumina Cosmetics 😊');
define('CHATBOT_MAX_HISTORY', 10); // Lưu tối đa 10 tin nhắn gần nhất

