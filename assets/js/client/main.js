/**
 * Main Client JavaScript
 */

// Hàm hiển thị Toast thông báo đẹp
function showToast(message, type = 'success') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `<span>${message}</span>`;

  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    toast.style.transition = 'all 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

// Cập nhật số lượng hiển thị trên icon giỏ hàng ở header
function updateCartBadge(count) {
  const badge = document.querySelector('.cart-badge');
  if (badge) {
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline-block' : 'none';
  }
}

// Format tiền tệ VNĐ
function formatCurrency(number) {
  return new Intl.NumberFormat('vi-VN').format(number) + ' ₫';
}

// Tự động load số lượng giỏ hàng khi vào trang
document.addEventListener('DOMContentLoaded', () => {
  fetch(window.BASE_URL + '/api/v1/gio-hang')
    .then(res => res.json())
    .then(res => {
      if (res.success && res.data) {
        updateCartBadge(res.data.total_quantity || 0);
      }
    })
    .catch(err => console.error('Lỗi lấy giỏ hàng:', err));
});
