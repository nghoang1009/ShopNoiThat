/**
 * Script xử lý Lọc và Sắp xếp sản phẩm bằng API (AJAX)
 */

document.addEventListener('DOMContentLoaded', () => {
  const filterForm = document.getElementById('filter-form');
  const productContainer = document.getElementById('product-list-container');
  const loadingOverlay = document.getElementById('loading-spinner');
  const sortSelect = document.getElementById('sort-select');
  const resultCount = document.getElementById('result-count');

  if (!filterForm || !productContainer) return;

  // Lắng nghe sự kiện thay đổi trên các input lọc
  const filterInputs = filterForm.querySelectorAll('input, select');
  filterInputs.forEach(input => {
    input.addEventListener('change', () => {
      fetchFilteredProducts();
    });
  });

  if (sortSelect) {
    sortSelect.addEventListener('change', () => {
      fetchFilteredProducts();
    });
  }

  // Reset bộ lọc
  const btnReset = document.getElementById('btn-reset-filter');
  if (btnReset) {
    btnReset.addEventListener('click', (e) => {
      e.preventDefault();
      filterForm.reset();
      if (sortSelect) sortSelect.value = 'moi_nhat';
      fetchFilteredProducts();
    });
  }

  function fetchFilteredProducts() {
    if (loadingOverlay) loadingOverlay.style.display = 'block';
    productContainer.style.opacity = '0.4';

    const formData = new FormData(filterForm);
    const params = new URLSearchParams();

    for (let [key, value] of formData.entries()) {
      if (value && value.trim() !== '') {
        params.append(key, value.trim());
      }
    }

    if (sortSelect && sortSelect.value) {
      params.append('sort', sortSelect.value);
    }

    const apiUrl = `${window.BASE_URL}/api/v1/san-pham?${params.toString()}`;

    fetch(apiUrl)
      .then(res => res.json())
      .then(res => {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
        productContainer.style.opacity = '1';

        if (res.success && res.data) {
          const productList = res.data.items || res.data.products || [];
          const totalCount = res.data.total !== undefined ? res.data.total : productList.length;
          renderProductList(productList);
          if (resultCount) {
            resultCount.textContent = `Hiển thị ${productList.length}/${totalCount} sản phẩm nội thất`;
          }
        }
      })
      .catch(err => {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
        productContainer.style.opacity = '1';
        console.error('Lỗi khi lọc sản phẩm:', err);
      });
  }

  function renderProductList(products) {
    if (!products || products.length === 0) {
      productContainer.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: #fff; border-radius: 8px; border: 1px solid #e2e8f0;">
          <h3 style="color: #64748b; margin-bottom: 10px;">Không tìm thấy sản phẩm phù hợp!</h3>
          <p style="color: #94a3b8; font-size: 14px;">Hãy thử điều chỉnh lại bộ lọc hoặc khoảng giá.</p>
        </div>
      `;
      return;
    }

    let html = '';
    products.forEach(p => {
      const saleBadge = p.phan_tram_giam > 0 ? `<div class="product-badge-sale">-${p.phan_tram_giam}%</div>` : '';
      const oldPrice = p.gia_km_dinh_dang ? `<span class="price-old">${p.gia_dinh_dang}</span>` : '';
      const currentPrice = p.gia_km_dinh_dang ? p.gia_km_dinh_dang : p.gia_dinh_dang;

      html += `
        <div class="product-card">
          ${saleBadge}
          <div class="product-img-box">
            <a href="${p.detail_url}">
              <img src="${p.hinh_anh_url}" alt="${p.ten_san_pham}" loading="lazy">
            </a>
          </div>
          <div class="product-body">
            <div class="product-cat">${p.ten_danh_muc || 'Nội thất'}</div>
            <h3 class="product-title">
              <a href="${p.detail_url}">${p.ten_san_pham}</a>
            </h3>
            <div class="product-meta">
              ${p.chat_lieu ? `<span>Chất liệu: ${p.chat_lieu}</span>` : ''}
            </div>
            <div class="product-price-box">
              <span class="price-current">${currentPrice}</span>
              ${oldPrice}
            </div>
            <button class="btn-add-cart" onclick="quickAddToCart(${p.id})">
              Thêm vào giỏ hàng
            </button>
          </div>
        </div>
      `;
    });

    productContainer.innerHTML = html;
  }
});

// Hàm thêm giỏ hàng nhanh gọi RESTful API v1
window.quickAddToCart = function(productId) {
  fetch(`${window.BASE_URL}/api/v1/gio-hang`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      san_pham_id: productId,
      so_luong: 1
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
        showToast(res.message || 'Thêm vào giỏ hàng thất bại!', 'danger');
      }
    })
    .catch(err => {
      console.error(err);
      showToast('Lỗi kết nối máy chủ!', 'danger');
    });
};
