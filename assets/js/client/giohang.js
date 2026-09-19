/**
 * Script xử lý Giỏ hàng AJAX (RESTful API v1)
 */

document.addEventListener('DOMContentLoaded', () => {
  // Thêm vào giỏ hàng từ trang chi tiết sản phẩm
  const btnDetailAddToCart = document.getElementById('btn-detail-add-cart');
  if (btnDetailAddToCart) {
    btnDetailAddToCart.addEventListener('click', () => {
      const productId = btnDetailAddToCart.dataset.productId;
      const qtyInput = document.getElementById('detail-quantity');
      const quantity = qtyInput ? parseInt(qtyInput.value) : 1;

      fetch(`${window.BASE_URL}/api/v1/gio-hang`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          san_pham_id: productId,
          so_luong: quantity
        })
      })
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            showToast(res.message, 'success');
            if (res.data && res.data.total_quantity !== undefined) {
              updateCartBadge(res.data.total_quantity);
            }
          } else {
            showToast(res.message || 'Lỗi thêm sản phẩm.', 'danger');
          }
        })
        .catch(err => {
          console.error(err);
          showToast('Lỗi kết nối máy chủ.', 'danger');
        });
    });
  }
});

// Hàm cập nhật số lượng trong bảng giỏ hàng (PATCH /api/v1/gio-hang/{id})
window.updateCartQty = function(cartId, newQty) {
  if (newQty < 1) {
    if (confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) {
      window.removeCartItem(cartId);
    }
    return;
  }

  fetch(`${window.BASE_URL}/api/v1/gio-hang/${cartId}`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      so_luong: newQty
    })
  })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        showToast('Đã cập nhật giỏ hàng.', 'success');
        location.reload(); // Tải lại để đồng bộ bảng giỏ hàng & tổng tiền
      } else {
        showToast(res.message || 'Cập nhật thất bại.', 'danger');
      }
    })
    .catch(err => {
      console.error(err);
      showToast('Lỗi kết nối máy chủ.', 'danger');
    });
};

// Hàm xóa 1 item khỏi giỏ hàng (DELETE /api/v1/gio-hang/{id})
window.removeCartItem = function(cartId) {
  if (!confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) return;

  fetch(`${window.BASE_URL}/api/v1/gio-hang/${cartId}`, {
    method: 'DELETE',
    headers: {
      'Accept': 'application/json'
    }
  })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        showToast('Đã xóa sản phẩm khỏi giỏ hàng.', 'info');
        location.reload();
      } else {
        showToast(res.message || 'Xóa thất bại.', 'danger');
      }
    })
    .catch(err => {
      console.error(err);
      showToast('Lỗi kết nối máy chủ.', 'danger');
    });
};
