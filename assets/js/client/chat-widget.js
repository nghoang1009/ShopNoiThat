/**
 * AI Interior Design Assistant Chat Widget JavaScript (RESTful API v1)
 */

(function() {
  let currentSessionId = null;
  let isSending = false;

  document.addEventListener('DOMContentLoaded', () => {
    initChatWidget();
  });

  function initChatWidget() {
    const chatBtn = document.getElementById('ai-chat-toggle-btn');
    const chatPopup = document.getElementById('ai-chat-popup');
    const closeBtn = document.getElementById('ai-chat-close-btn');
    const resetBtn = document.getElementById('ai-chat-reset-btn');
    const chatForm = document.getElementById('ai-chat-form');
    const chatInput = document.getElementById('ai-chat-input');
    const quickChips = document.querySelectorAll('.ai-chip');

    if (!chatBtn || !chatPopup) return;

    // Mở / đóng widget
    chatBtn.addEventListener('click', () => {
      const isActive = chatPopup.classList.toggle('active');
      if (isActive) {
        if (!currentSessionId) {
          loadOrCreateSession();
        }
        setTimeout(() => chatInput?.focus(), 200);
      }
    });

    closeBtn?.addEventListener('click', () => {
      chatPopup.classList.remove('active');
    });

    // Nút làm mới phiên trò chuyện
    resetBtn?.addEventListener('click', () => {
      if (confirm('Bạn có muốn làm mới và bắt đầu cuộc trò chuyện mới với AI không?')) {
        loadOrCreateSession(true);
      }
    });

    // Gửi tin nhắn qua form submit
    chatForm?.addEventListener('submit', (e) => {
      e.preventDefault();
      const message = chatInput.value.trim();
      if (!message || isSending) return;
      chatInput.value = '';
      sendUserMessage(message);
    });

    // Gợi ý nhanh (Quick Chips)
    quickChips.forEach(chip => {
      chip.addEventListener('click', () => {
        const text = chip.getAttribute('data-query') || chip.textContent.trim();
        if (text && !isSending) {
          sendUserMessage(text);
        }
      });
    });
  }

  // Khởi tạo hoặc lấy phiên tư vấn từ server
  function loadOrCreateSession(forceNew = false) {
    const messagesContainer = document.getElementById('ai-chat-messages');

    fetch(`${window.BASE_URL}/api/v1/phien-tu-van`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        force_new: forceNew
      })
    })
      .then(async res => {
        const raw = await res.text();
        try {
          return JSON.parse(raw);
        } catch (e) {
          console.error('Lỗi phản hồi từ máy chủ:', raw);
          return { success: false, message: 'Lỗi máy chủ' };
        }
      })
      .then(res => {
        if (res.success && res.data && res.data.session) {
          currentSessionId = res.data.session.id;
          messagesContainer.innerHTML = '';

          if (res.data.messages && res.data.messages.length > 0 && !forceNew) {
            res.data.messages.forEach(msg => {
              renderMessage(msg.nguoi_gui, msg.noi_dung, msg.san_pham_goi_y, msg.thoi_gian);
            });
          } else {
            // Lời chào mặc định ban đầu nếu chưa có tin nhắn
            renderMessage('ai', 'Dạ chào bạn! Em là **AI Trợ lý Thiết kế & Tư vấn Nội thất**. Em có thể giúp bạn tìm kiếm mẫu bàn ghế, sofa, giường tủ phù hợp với không gian và ngân sách của gia đình mình ạ!', null, new Date().toISOString());
          }
          scrollToBottom();
        }
      })
      .catch(err => {
        console.error('Lỗi khởi tạo phiên AI:', err);
      });
  }

  // Gửi tin nhắn người dùng và nhận câu trả lời AI
  function sendUserMessage(messageText) {
    if (!currentSessionId) {
      loadOrCreateSession();
      setTimeout(() => sendUserMessage(messageText), 600);
      return;
    }

    isSending = true;
    const messagesContainer = document.getElementById('ai-chat-messages');

    // 1. Hiển thị tin nhắn người dùng ngay lập tức
    renderMessage('khach', messageText, null, new Date().toISOString());
    scrollToBottom();

    // 2. Hiển thị hiệu ứng gõ (Typing Indicator)
    showTypingIndicator();
    scrollToBottom();

    // 3. Gọi RESTful API gửi tin nhắn
    fetch(`${window.BASE_URL}/api/v1/phien-tu-van/${currentSessionId}/tin-nhan`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        noi_dung: messageText
      })
    })
      .then(async res => {
        const raw = await res.text();
        try {
          return JSON.parse(raw);
        } catch (e) {
          console.error('Lỗi phản hồi từ máy chủ khi gửi tin nhắn:', raw);
          return { success: false, message: 'Lỗi máy chủ' };
        }
      })
      .then(res => {
        hideTypingIndicator();
        isSending = false;

        if (res.success && res.data && res.data.ai_response) {
          const ai = res.data.ai_response;
          renderMessage('ai', ai.noi_dung, ai.san_pham_goi_y, ai.thoi_gian);
          scrollToBottom();
        } else {
          renderMessage('ai', res.message || 'Dạ hệ thống đang bận một chút, bạn thử nhắn lại giúp em nhé!', null, new Date().toISOString());
          scrollToBottom();
        }
      })
      .catch(err => {
        hideTypingIndicator();
        isSending = false;
        console.error('Lỗi gửi tin nhắn AI:', err);
        renderMessage('ai', 'Dạ kết nối máy chủ bị gián đoạn. Vui lòng kiểm tra lại đường truyền mạng nhé!', null, new Date().toISOString());
        scrollToBottom();
      });
  }

  // Render 1 bubble tin nhắn
  function renderMessage(sender, content, product, time) {
    const messagesContainer = document.getElementById('ai-chat-messages');
    if (!messagesContainer) return;

    const msgDiv = document.createElement('div');
    const isUser = (sender === 'khach' || sender === 'user');
    msgDiv.className = `chat-message ${isUser ? 'user' : 'ai'}`;

    // Format markdown bold **text** đơn giản
    let formattedContent = escapeHtml(content)
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
      .replace(/\n/g, '<br>');

    let productHtml = '';
    if (product && product.id) {
      productHtml = `
        <a href="${product.detail_url}" target="_blank" class="ai-product-card">
          <img src="${product.hinh_anh_url}" alt="${escapeHtml(product.ten_san_pham)}" class="ai-product-img" onerror="this.src='https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=100&q=80'">
          <div class="ai-product-info">
            <div class="ai-product-title">${escapeHtml(product.ten_san_pham)}</div>
            <div class="ai-product-meta">${escapeHtml(product.chat_lieu || 'Nội thất cao cấp')}</div>
            <div class="ai-product-price">${product.gia_dinh_dang}</div>
          </div>
          <span style="font-size: 16px; color: #935832;">&rarr;</span>
        </a>
      `;
    }

    const timeString = time ? formatTime(time) : '';

    msgDiv.innerHTML = `
      <div class="chat-bubble">
        ${formattedContent}
        ${productHtml}
      </div>
      <div class="chat-time">${timeString}</div>
    `;

    messagesContainer.appendChild(msgDiv);
  }

  function showTypingIndicator() {
    const messagesContainer = document.getElementById('ai-chat-messages');
    if (!messagesContainer || document.getElementById('ai-typing-indicator')) return;

    const typingDiv = document.createElement('div');
    typingDiv.id = 'ai-typing-indicator';
    typingDiv.className = 'chat-message ai';
    typingDiv.innerHTML = `
      <div class="typing-indicator">
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
      </div>
    `;
    messagesContainer.appendChild(typingDiv);
  }

  function hideTypingIndicator() {
    const indicator = document.getElementById('ai-typing-indicator');
    if (indicator) indicator.remove();
  }

  function scrollToBottom() {
    const messagesContainer = document.getElementById('ai-chat-messages');
    if (messagesContainer) {
      messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
  }

  function formatTime(isoString) {
    try {
      const date = new Date(isoString);
      return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    } catch {
      return '';
    }
  }

  function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
})();
