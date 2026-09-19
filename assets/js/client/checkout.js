/**
 * Checkout JS - Tính phí vận chuyển động & cập nhật tổng tiền qua RESTful API v1
 */

document.addEventListener('DOMContentLoaded', () => {
  const shippingRadios = document.querySelectorAll('input[name="phuong_thuc_van_chuyen_id"]');
  const paymentRadios = document.querySelectorAll('input[name="phuong_thuc_thanh_toan_id"]');
  const provinceInput = document.getElementById('tinh_thanh');
  const subtotalVal = parseFloat(window.CART_SUBTOTAL || 0);

  const shippingFeeDisplay = document.getElementById('shipping-fee-display');
  const grandTotalDisplay = document.getElementById('grand-total-display');

  // Xử lý đổi phương thức vận chuyển
  shippingRadios.forEach(radio => {
    radio.addEventListener('change', function() {
      document.querySelectorAll('.shipping-method-card').forEach(card => card.classList.remove('active'));
      this.closest('.shipping-method-card').classList.add('active');
      fetchShippingMethodsAndFee();
    });
  });

  // Xử lý đổi phương thức thanh toán
  paymentRadios.forEach(radio => {
    radio.addEventListener('change', function() {
      document.querySelectorAll('.payment-method-item').forEach(card => card.classList.remove('active'));
      this.closest('.payment-method-item').classList.add('active');
    });
  });

  // Lắng nghe thay đổi tỉnh thành để cập nhật phí giao
  if (provinceInput) {
    let timeout = null;
    provinceInput.addEventListener('input', () => {
      clearTimeout(timeout);
      timeout = setTimeout(fetchShippingMethodsAndFee, 400);
    });
  }

  function fetchShippingMethodsAndFee() {
    const selectedShipping = document.querySelector('input[name="phuong_thuc_van_chuyen_id"]:checked');
    const selectedId = selectedShipping ? parseInt(selectedShipping.value) : 1;
    const province = provinceInput ? provinceInput.value.trim() : '';

    const apiUrl = `${window.BASE_URL}/api/v1/phuong-thuc-van-chuyen?khu_vuc=${encodeURIComponent(province)}&subtotal=${subtotalVal}`;

    fetch(apiUrl)
      .then(res => res.json())
      .then(res => {
        if (res.success && Array.isArray(res.data)) {
          let currentFee = 0;
          let currentFeeFormatted = 'Miễn phí';

          res.data.forEach(m => {
            const card = document.querySelector(`input[name="phuong_thuc_van_chuyen_id"][value="${m.id}"]`)?.closest('.shipping-method-card');
            if (card) {
              const feeBox = card.querySelector('.shipping-method-fee');
              if (feeBox) feeBox.textContent = m.phi_dinh_dang;
            }

            if (m.id === selectedId) {
              currentFee = parseFloat(m.phi_tinh_toan) || 0;
              currentFeeFormatted = m.phi_dinh_dang;
            }
          });

          const grandTotal = subtotalVal + currentFee;

          if (shippingFeeDisplay) {
            shippingFeeDisplay.textContent = currentFeeFormatted;
            shippingFeeDisplay.style.color = currentFee === 0 ? 'var(--success-color)' : 'var(--primary-color)';
          }

          if (grandTotalDisplay) {
            grandTotalDisplay.textContent = formatCurrency(grandTotal);
          }
        }
      })
      .catch(err => console.error('Lỗi tính phí ship qua API:', err));
  }

  // Khởi động tính phí lần đầu
  fetchShippingMethodsAndFee();
});
