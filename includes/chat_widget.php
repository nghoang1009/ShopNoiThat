<?php
/**
 * AI Assistant Chat Widget Component
 * Nhúng tự động trên mọi trang Client
 */
?>
<!-- Nút Chat Nổi Góc Phải -->
<button type="button" id="ai-chat-toggle-btn" class="ai-chat-btn" title="Trò chuyện với AI Chuyên Gia Nội Thất">
    <span>🛋️</span>
    <span class="badge-ai">AI</span>
</button>

<!-- Khung Popup Hội Thoại AI -->
<div id="ai-chat-popup" class="ai-chat-popup">
    <!-- Header -->
    <div class="ai-chat-header">
        <div class="ai-chat-header-info">
            <div class="ai-avatar">✨</div>
            <div>
                <div class="ai-header-title">Trợ Lý Thiết Kế AI</div>
                <div class="ai-header-status">Đang trực tuyến 24/7</div>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <button type="button" id="ai-chat-reset-btn" class="ai-btn-close" style="font-size:14px; background:rgba(255,255,255,0.15); padding:4px 8px; border-radius:6px;" title="Tạo cuộc hội thoại mới">
                🔄 Mới
            </button>
            <button type="button" id="ai-chat-close-btn" class="ai-btn-close" title="Đóng">&times;</button>
        </div>
    </div>

    <!-- Vùng Tin Nhắn -->
    <div id="ai-chat-messages" class="ai-chat-body">
        <!-- Tin nhắn sẽ được render tự động qua JS -->
    </div>

    <!-- Gợi ý nhanh câu hỏi (Quick Suggestion Chips) -->
    <div class="ai-quick-chips">
        <div class="ai-chip" data-query="Tư vấn sofa da phòng khách sang trọng">🛋️ Sofa phòng khách</div>
        <div class="ai-chip" data-query="Gợi ý bộ bàn ăn mặt đá 6 ghế">🍽️ Bàn ăn 6 ghế</div>
        <div class="ai-chip" data-query="Giường ngủ gỗ sồi tự nhiên 1m8">🛏️ Giường ngủ 1m8</div>
        <div class="ai-chip" data-query="Ghế công thái học chống đau lưng">💺 Ghế Ergonomic</div>
    </div>

    <!-- Khung nhập liệu & Gửi -->
    <div class="ai-chat-footer">
        <form id="ai-chat-form" class="ai-chat-input-form">
            <input type="text" id="ai-chat-input" class="ai-chat-input" placeholder="Hỏi AI về mẫu mã, chất liệu, kích thước..." autocomplete="off" required>
            <button type="submit" class="ai-btn-send" title="Gửi tin nhắn">
                ➤
            </button>
        </form>
    </div>
</div>
