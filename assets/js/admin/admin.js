/**
 * Admin Panel JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
  // Preview ảnh chính khi chọn file
  const imageInputs = document.querySelectorAll('input[type="file"]');
  imageInputs.forEach(input => {
    input.addEventListener('change', function() {
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          let preview = input.closest('.form-group')?.querySelector('.preview-img');
          if (preview) {
            preview.src = e.target.result;
            preview.style.display = 'block';
          }
        };
        reader.readAsDataURL(this.files[0]);
      }
    });
  });

  // Vẽ biểu đồ doanh thu nếu có canvas
  initRevenueChart();
});

// Hàm xác nhận xóa
window.confirmDelete = function(url, message = 'Bạn có chắc chắn muốn xóa bản ghi này không? Hành động này không thể hoàn tác!') {
  if (confirm(message)) {
    window.location.href = url;
  }
};

// Vẽ biểu đồ Canvas doanh thu 7 ngày
function initRevenueChart() {
  const canvas = document.getElementById('revenueChart');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  const chartData = window.REVENUE_CHART_DATA || [];

  if (chartData.length === 0) {
    ctx.font = '14px sans-serif';
    ctx.fillStyle = '#94a3b8';
    ctx.textAlign = 'center';
    ctx.fillText('Chưa có dữ liệu doanh thu gần đây', canvas.width / 2, canvas.height / 2);
    return;
  }

  const padding = 40;
  const width = canvas.width;
  const height = canvas.height;

  const labels = chartData.map(d => d.ngay);
  const values = chartData.map(d => parseFloat(d.doanh_thu) || 0);

  const maxVal = Math.max(...values, 1000000);
  const minVal = 0;

  // Xóa canvas
  ctx.clearRect(0, 0, width, height);

  // Vẽ trục toạ độ
  ctx.strokeStyle = '#e2e8f0';
  ctx.lineWidth = 1;
  ctx.beginPath();
  ctx.moveTo(padding, padding);
  ctx.lineTo(padding, height - padding);
  ctx.lineTo(width - padding, height - padding);
  ctx.stroke();

  // Vẽ lưới ngang
  const gridLines = 4;
  for (let i = 0; i <= gridLines; i++) {
    const y = padding + ((height - 2 * padding) / gridLines) * i;
    ctx.strokeStyle = '#f1f5f9';
    ctx.beginPath();
    ctx.moveTo(padding, y);
    ctx.lineTo(width - padding, y);
    ctx.stroke();

    // Nhãn giá trị
    const val = Math.round(maxVal - (maxVal / gridLines) * i);
    ctx.fillStyle = '#64748b';
    ctx.font = '10px sans-serif';
    ctx.textAlign = 'right';
    ctx.fillText((val / 1000000).toFixed(1) + 'M', padding - 8, y + 3);
  }

  // Vẽ đường biểu đồ
  const stepX = (width - 2 * padding) / (values.length - 1 || 1);
  ctx.strokeStyle = '#935832';
  ctx.lineWidth = 3;
  ctx.beginPath();

  const points = [];
  values.forEach((val, idx) => {
    const x = padding + idx * stepX;
    const y = (height - padding) - ((val - minVal) / (maxVal - minVal)) * (height - 2 * padding);
    points.push({ x, y, val, label: labels[idx] });

    if (idx === 0) {
      ctx.moveTo(x, y);
    } else {
      ctx.lineTo(x, y);
    }
  });
  ctx.stroke();

  // Vẽ điểm tròn và nhãn ngày
  points.forEach(p => {
    ctx.fillStyle = '#fb923c';
    ctx.beginPath();
    ctx.arc(p.x, p.y, 5, 0, Math.PI * 2);
    ctx.fill();
    ctx.strokeStyle = '#fff';
    ctx.lineWidth = 2;
    ctx.stroke();

    // Nhãn ngày
    ctx.fillStyle = '#475569';
    ctx.font = '11px sans-serif';
    ctx.textAlign = 'center';
    const dateLabel = p.label.split('-').slice(1).join('/');
    ctx.fillText(dateLabel, p.x, height - padding + 18);
  });
}
