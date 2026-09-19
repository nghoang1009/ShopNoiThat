/**
 * Online Payment Gateway & Simulator JS (RESTful API v1)
 */

window.copyToClipboard = function(text, label = 'nội dung') {
  navigator.clipboard.writeText(text).then(() => {
    showToast(`Đã sao chép ${label} vào bộ nhớ tạm!`, 'success');
  }).catch(() => {
    showToast('Không thể sao chép tự động!', 'warning');
  });
};

window.simulatePayment = function(orderCode, methodCode) {
  const btn = document.getElementById('btn-simulate-payment');
  if (btn) {
    btn.disabled = true;
    btn.textContent = '⏳ Đang kết nối cổng thanh toán & xác nhận...';
  }

  // Gọi RESTful endpoint PATCH /api/v1/don-hang/{orderCode}/thanh-toan
  const transCode = (methodCode.toUpperCase()) + Date.now().toString().slice(-6);

  fetch(`${window.BASE_URL}/api/v1/don-hang/${orderCode}/thanh-toan`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      trang_thai: 'da_thanh_toan',
      ma_giao_dich: transCode,
      ghi_chu: `Thanh toán thành công qua cổng ${methodCode.toUpperCase()}`
    })
  })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        showToast('Thanh toán thành công!', 'success');
        setTimeout(() => {
          window.location.href = `${window.BASE_URL}/don-hang/thanh-cong?ma=${orderCode}`;
        }, 1000);
      } else {
        // Fallback sang endpoint giả lập
        return fetch(`${window.BASE_URL}/api/v1/thanh-toan/simulate`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            ma_don_hang: orderCode,
            phuong_thuc: methodCode
          })
        }).then(r => r.json()).then(r2 => {
          if (r2.success) {
            showToast('Thanh toán thành công!', 'success');
            setTimeout(() => {
              window.location.href = `${window.BASE_URL}/don-hang/thanh-cong?ma=${orderCode}`;
            }, 1000);
          } else {
            showToast(r2.message || 'Thanh toán thất bại!', 'danger');
            if (btn) {
              btn.disabled = false;
              btn.textContent = '⚡ Giả Lập Thanh Toán Thành Công';
            }
          }
        });
      }
    })
    .catch(err => {
      console.error(err);
      showToast('Lỗi kết nối máy chủ!', 'danger');
      if (btn) {
        btn.disabled = false;
        btn.textContent = '⚡ Giả Lập Thanh Toán Thành Công';
      }
    });
};
