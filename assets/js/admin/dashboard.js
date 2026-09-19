/**
 * Admin Dashboard Chart & Analytics JS (RESTful API v1)
 */

document.addEventListener('DOMContentLoaded', () => {
  renderMainRevenueChart(window.DASHBOARD_CHART_DATA || []);
});

window.renderMainRevenueChart = function(dataList) {
  const canvas = document.getElementById('dashboardRevenueChart');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  const width = canvas.width;
  const height = canvas.height;
  const padding = 50;

  ctx.clearRect(0, 0, width, height);

  if (!dataList || dataList.length === 0) {
    ctx.font = '14px sans-serif';
    ctx.fillStyle = '#94a3b8';
    ctx.textAlign = 'center';
    ctx.fillText('Không có dữ liệu giao dịch trong khoảng thời gian này', width / 2, height / 2);
    return;
  }

  const labels = dataList.map(d => d.ngay);
  const revenues = dataList.map(d => parseFloat(d.doanh_thu) || 0);
  const orders = dataList.map(d => parseInt(d.tong_so_don) || 0);

  const maxRevenue = Math.max(...revenues, 5000000);
  const minRevenue = 0;

  // Lưới ngang
  const gridRows = 4;
  for (let i = 0; i <= gridRows; i++) {
    const y = padding + ((height - 2 * padding) / gridRows) * i;
    ctx.strokeStyle = '#f1f5f9';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(padding, y);
    ctx.lineTo(width - padding, y);
    ctx.stroke();

    const val = maxRevenue - (maxRevenue / gridRows) * i;
    ctx.fillStyle = '#64748b';
    ctx.font = '11px sans-serif';
    ctx.textAlign = 'right';
    ctx.fillText((val / 1000000).toFixed(1) + 'M', padding - 10, y + 4);
  }

  // Vẽ trục toạ độ
  ctx.strokeStyle = '#cbd5e1';
  ctx.lineWidth = 1.5;
  ctx.beginPath();
  ctx.moveTo(padding, padding);
  ctx.lineTo(padding, height - padding);
  ctx.lineTo(width - padding, height - padding);
  ctx.stroke();

  // Vẽ đường doanh thu
  const stepX = (width - 2 * padding) / (revenues.length - 1 || 1);

  // Gradient area
  const gradient = ctx.createLinearGradient(0, padding, 0, height - padding);
  gradient.addColorStop(0, 'rgba(147, 88, 50, 0.25)');
  gradient.addColorStop(1, 'rgba(147, 88, 50, 0.0)');

  ctx.beginPath();
  revenues.forEach((rev, idx) => {
    const x = padding + idx * stepX;
    const y = (height - padding) - ((rev - minRevenue) / (maxRevenue - minRevenue || 1)) * (height - 2 * padding);
    if (idx === 0) {
      ctx.moveTo(x, y);
    } else {
      ctx.lineTo(x, y);
    }
  });

  const lastX = padding + (revenues.length - 1) * stepX;
  ctx.lineTo(lastX, height - padding);
  ctx.lineTo(padding, height - padding);
  ctx.closePath();
  ctx.fillStyle = gradient;
  ctx.fill();

  // Vẽ nét viền
  ctx.beginPath();
  ctx.strokeStyle = '#935832';
  ctx.lineWidth = 3;
  revenues.forEach((rev, idx) => {
    const x = padding + idx * stepX;
    const y = (height - padding) - ((rev - minRevenue) / (maxRevenue - minRevenue || 1)) * (height - 2 * padding);
    if (idx === 0) ctx.moveTo(x, y);
    else ctx.lineTo(x, y);
  });
  ctx.stroke();

  // Vẽ điểm tròn và nhãn ngày/tháng
  revenues.forEach((rev, idx) => {
    const x = padding + idx * stepX;
    const y = (height - padding) - ((rev - minRevenue) / (maxRevenue - minRevenue || 1)) * (height - 2 * padding);

    ctx.fillStyle = '#fb923c';
    ctx.beginPath();
    ctx.arc(x, y, 5, 0, Math.PI * 2);
    ctx.fill();
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 2;
    ctx.stroke();

    ctx.fillStyle = '#475569';
    ctx.font = '11px sans-serif';
    ctx.textAlign = 'center';
    let shortLabel = labels[idx];
    if (shortLabel.includes('-')) {
      shortLabel = shortLabel.split('-').slice(1).join('/');
    }
    ctx.fillText(shortLabel, x, height - padding + 20);

    if (orders[idx] > 0) {
      ctx.fillStyle = '#10b981';
      ctx.font = 'bold 10px sans-serif';
      ctx.fillText(`${orders[idx]} đơn`, x, y - 10);
    }
  });
};
