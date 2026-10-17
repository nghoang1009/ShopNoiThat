/**
 * Thiết kế phòng 2D / 3D - kéo thả nội thất để xem kích thước thực tế.
 * Không phụ thuộc thư viện ngoài: 2D vẽ bằng canvas, 3D là bộ dựng phối cảnh tự viết (painter's algorithm).
 * Hệ tọa độ (cm): x sang phải, z hướng xuống trên mặt bằng 2D (cũng là chiều sâu trong 3D), y là chiều cao.
 */
(function () {
  'use strict';

  const dataEl = document.getElementById('planner-data');
  if (!dataEl) return;

  const DATA = JSON.parse(dataEl.textContent);
  const P = {};
  DATA.products.forEach(p => { P[p.id] = p; });

  const $ = id => document.getElementById(id);
  const canvas = $('pl-canvas');
  const ctx = canvas.getContext('2d');
  const wrap = $('pl-canvas-wrap');

  // ------------------------------------------------------------------ trạng thái
  const DEFAULT_ROOM = { w: 400, l: 500, h: 270 };
  const MAX_ITEMS = 60;
  const STORAGE_KEY = 'planner_v1';
  let state = { room: Object.assign({}, DEFAULT_ROOM), items: [] };
  let uidSeq = 1;
  let selected = null;
  let mode = '2d';
  let issues = new Map();
  const undoStack = [];
  const redoStack = [];

  const view2 = { zoom: 1, panX: 0, panY: 0 };
  const cam3 = { yaw: 0.55, pitch: 0.8, dist: 800 };
  let cssW = 800, cssH = 500, dpr = 1;

  // ------------------------------------------------------------------ tiện ích
  const clamp = (v, a, b) => Math.max(a, Math.min(b, v));
  const rad = d => d * Math.PI / 180;
  const fmtVnd = n => new Intl.NumberFormat('vi-VN').format(Math.round(n)) + ' ₫';
  const round1 = n => Math.round(n * 10) / 10;
  const norm360 = d => ((d % 360) + 360) % 360;

  function el(tag, props, children) {
    const e = document.createElement(tag);
    if (props) Object.keys(props).forEach(k => {
      if (k === 'text') e.textContent = props[k];
      else if (k === 'class') e.className = props[k];
      else if (k.startsWith('on')) e.addEventListener(k.slice(2), props[k]);
      else e.setAttribute(k, props[k]);
    });
    (children || []).forEach(c => e.appendChild(c));
    return e;
  }

  function toast(msg, type) {
    if (typeof window.showToast === 'function') window.showToast(msg, type || 'success');
  }

  // ------------------------------------------------------------------ màu sắc theo mô tả màu
  const COLOR_RULES = [
    ['óc chó', '#5a3e2b'], ['socola', '#4e342e'], ['nâu da bò', '#8b5a3c'], ['nâu', '#8b5e3c'], ['cánh gián', '#a0522d'],
    ['xanh lam', '#2c4a8a'], ['xanh cổ vịt', '#1f7a7a'], ['xanh rêu', '#5c6b3c'], ['xanh pastel', '#7fb3d5'], ['xanh', '#3f7fa8'],
    ['đen', '#33363b'], ['xám', '#8d949c'], ['bạc', '#b9bec4'], ['trắng', '#e8e8e4'],
    ['cam', '#e07b2f'], ['vàng đồng', '#b08d3c'], ['vàng kem', '#e3cf9a'], ['vàng', '#c9a24a'], ['gold', '#c9a24a'],
    ['sồi', '#c19a6b'], ['gỗ', '#b88a5a']
  ];
  const CAT_COLORS = ['#a9764f', '#b08968', '#8e7dbe', '#6a8fa8', '#7aa17a', '#c98f5b', '#9a8c98'];
  function colorOf(p) {
    const m = (p.mau || '').toLowerCase();
    for (const [k, c] of COLOR_RULES) if (m.indexOf(k) !== -1) return c;
    return CAT_COLORS[p.dm % CAT_COLORS.length];
  }
  function shade(hex, f) {
    const n = parseInt(hex.slice(1), 16);
    const r = clamp(Math.round(((n >> 16) & 255) * f), 0, 255);
    const g = clamp(Math.round(((n >> 8) & 255) * f), 0, 255);
    const b = clamp(Math.round((n & 255) * f), 0, 255);
    return 'rgb(' + r + ',' + g + ',' + b + ')';
  }

  // ------------------------------------------------------------------ hình học
  const pieceCache = {};
  function rect(x0, z0, x1, z1) { return [[x0, z0], [x1, z0], [x1, z1], [x0, z1]]; }
  function armOf(p) { return Math.min(90, p.r * 0.6, p.d * 0.5); }

  /** Các đa giác lồi (tọa độ cục bộ, gốc ở tâm) tạo nên chân đế của sản phẩm */
  function localPieces(p) {
    if (pieceCache[p.id]) return pieceCache[p.id];
    const hx = p.d / 2, hz = p.r / 2;
    let pieces;
    if (p.shape === 'tron') {
      const pts = [];
      for (let i = 0; i < 24; i++) {
        const a = (i / 24) * Math.PI * 2;
        pts.push([hx * Math.cos(a), hz * Math.sin(a)]);
      }
      pieces = [pts];
    } else if (p.shape === 'chu_l') {
      const a = armOf(p);
      pieces = [rect(-hx, -hz, hx, -hz + a), rect(-hx, -hz, -hx + a, hz)];
    } else {
      pieces = [rect(-hx, -hz, hx, hz)];
    }
    pieceCache[p.id] = pieces;
    return pieces;
  }

  function toWorld(it, pt) {
    const c = Math.cos(rad(it.rot)), s = Math.sin(rad(it.rot));
    return [it.x + pt[0] * c - pt[1] * s, it.z + pt[0] * s + pt[1] * c];
  }
  function worldPolys(it) {
    return localPieces(P[it.pid]).map(poly => poly.map(pt => toWorld(it, pt)));
  }
  function bboxOf(it) {
    let minx = Infinity, maxx = -Infinity, minz = Infinity, maxz = -Infinity;
    worldPolys(it).forEach(poly => poly.forEach(pt => {
      minx = Math.min(minx, pt[0]); maxx = Math.max(maxx, pt[0]);
      minz = Math.min(minz, pt[1]); maxz = Math.max(maxz, pt[1]);
    }));
    return { minx, maxx, minz, maxz };
  }
  function footprintArea(p) {
    if (p.shape === 'tron') return Math.PI * (p.d / 2) * (p.r / 2);
    if (p.shape === 'chu_l') { const a = armOf(p); return p.d * a + (p.r - a) * a; }
    return p.d * p.r;
  }

  function pointInPoly(pt, poly) {
    let inside = false;
    for (let i = 0, j = poly.length - 1; i < poly.length; j = i++) {
      const xi = poly[i][0], zi = poly[i][1], xj = poly[j][0], zj = poly[j][1];
      if (((zi > pt[1]) !== (zj > pt[1])) && (pt[0] < (xj - xi) * (pt[1] - zi) / (zj - zi) + xi)) inside = !inside;
    }
    return inside;
  }
  function hitItem(it, pt) {
    return worldPolys(it).some(poly => pointInPoly(pt, poly));
  }

  /** Va chạm giữa hai đa giác lồi (SAT); chạm mép dưới 0.5cm không tính */
  function polysOverlap(A, B) {
    for (const poly of [A, B]) {
      for (let i = 0; i < poly.length; i++) {
        const a = poly[i], b = poly[(i + 1) % poly.length];
        const nx = b[1] - a[1], nz = a[0] - b[0];
        const len = Math.hypot(nx, nz) || 1;
        let minA = Infinity, maxA = -Infinity, minB = Infinity, maxB = -Infinity;
        A.forEach(p => { const d = (p[0] * nx + p[1] * nz) / len; minA = Math.min(minA, d); maxA = Math.max(maxA, d); });
        B.forEach(p => { const d = (p[0] * nx + p[1] * nz) / len; minB = Math.min(minB, d); maxB = Math.max(maxB, d); });
        if (maxA - minB < 0.5 || maxB - minA < 0.5) return false;
      }
    }
    return true;
  }

  function analyze() {
    issues = new Map();
    const W = state.room.w, L = state.room.l;
    const polys = state.items.map(worldPolys);
    state.items.forEach((it, i) => {
      const bb = bboxOf(it);
      const info = { out: bb.minx < -0.5 || bb.minz < -0.5 || bb.maxx > W + 0.5 || bb.maxz > L + 0.5, overlaps: [] };
      issues.set(it.uid, info);
    });
    for (let i = 0; i < state.items.length; i++) {
      for (let j = i + 1; j < state.items.length; j++) {
        const a = state.items[i], b = state.items[j], pa = P[a.pid], pb = P[b.pid];
        // chỉ coi là va chạm nếu hai món cùng chiếm một khoảng chiều cao
        if (!(a_below(pa, pb))) continue;
        if (polys[i].some(A => polys[j].some(B => polysOverlap(A, B)))) {
          issues.get(a.uid).overlaps.push(b.uid);
          issues.get(b.uid).overlaps.push(a.uid);
        }
      }
    }
  }
  function a_below(pa, pb) {
    return pa.elev < pb.elev + pb.c && pb.elev < pa.elev + pa.c;
  }

  // ------------------------------------------------------------------ lưu / hoàn tác / chia sẻ
  function snapshot() { return JSON.stringify({ room: state.room, items: state.items }); }
  function restore(json) {
    const s = JSON.parse(json);
    state.room = s.room;
    state.items = s.items;
    uidSeq = state.items.reduce((m, i) => Math.max(m, i.uid), 0) + 1;
    if (!state.items.some(i => i.uid === selected)) selected = null;
  }
  function pushHistory() {
    const snap = snapshot();
    if (undoStack.length && undoStack[undoStack.length - 1] === snap) return;
    undoStack.push(snap);
    if (undoStack.length > 100) undoStack.shift();
    redoStack.length = 0;
    persist();
  }
  function undo() {
    if (undoStack.length < 2) return;
    redoStack.push(undoStack.pop());
    restore(undoStack[undoStack.length - 1]);
    persist(); refresh(true);
  }
  function redo() {
    if (!redoStack.length) return;
    const snap = redoStack.pop();
    undoStack.push(snap);
    restore(snap);
    persist(); refresh(true);
  }
  function persist() {
    try { localStorage.setItem(STORAGE_KEY, snapshot()); } catch (e) { /* bỏ qua */ }
  }

  function sanitizeLoaded(obj) {
    if (!obj || typeof obj !== 'object') return null;
    const r = obj.room || {};
    const room = {
      w: clamp(Math.round(+r.w) || DEFAULT_ROOM.w, 150, 2000),
      l: clamp(Math.round(+r.l) || DEFAULT_ROOM.l, 150, 2000),
      h: clamp(Math.round(+r.h) || DEFAULT_ROOM.h, 200, 500)
    };
    const items = [];
    (Array.isArray(obj.items) ? obj.items : []).slice(0, MAX_ITEMS).forEach(i => {
      if (!P[i.pid]) return;
      const x = +i.x, z = +i.z, rot = +i.rot;
      if (!isFinite(x) || !isFinite(z)) return;
      items.push({ uid: items.length + 1, pid: +i.pid, x: clamp(x, -500, 2500), z: clamp(z, -500, 2500), rot: isFinite(rot) ? norm360(rot) : 0 });
    });
    return { room, items };
  }

  function encodeShare() {
    const compact = { r: [state.room.w, state.room.l, state.room.h], i: state.items.map(i => [i.pid, Math.round(i.x), Math.round(i.z), Math.round(i.rot)]) };
    return btoa(encodeURIComponent(JSON.stringify(compact)));
  }
  function decodeShare(hash) {
    try {
      const c = JSON.parse(decodeURIComponent(atob(hash)));
      return sanitizeLoaded({
        room: { w: c.r[0], l: c.r[1], h: c.r[2] },
        items: c.i.map(a => ({ pid: a[0], x: a[1], z: a[2], rot: a[3] }))
      });
    } catch (e) { return null; }
  }

  // ------------------------------------------------------------------ thao tác trên món
  function snapMove(it) {
    if (!$('pl-snap').checked) return;
    it.x = Math.round(it.x / 5) * 5;
    it.z = Math.round(it.z / 5) * 5;
    const bb = bboxOf(it), W = state.room.w, L = state.room.l, T = 10;
    if (Math.abs(bb.minx) < T) it.x -= bb.minx; else if (Math.abs(W - bb.maxx) < T) it.x += W - bb.maxx;
    if (Math.abs(bb.minz) < T) it.z -= bb.minz; else if (Math.abs(L - bb.maxz) < T) it.z += L - bb.maxz;
  }
  function clampInside(it) {
    const bb = bboxOf(it), W = state.room.w, L = state.room.l;
    if (bb.maxx - bb.minx <= W) { if (bb.minx < 0) it.x -= bb.minx; if (bb.maxx > W) it.x -= bb.maxx - W; }
    if (bb.maxz - bb.minz <= L) { if (bb.minz < 0) it.z -= bb.minz; if (bb.maxz > L) it.z -= bb.maxz - L; }
  }

  function findFreeSpot(it) {
    const W = state.room.w, L = state.room.l;
    const tryAt = (x, z) => {
      it.x = x; it.z = z;
      analyzeOne(it);
      return !issues.get(it.uid).out && issues.get(it.uid).overlaps.length === 0;
    };
    const tmpItems = state.items;
    state.items = tmpItems.concat([it]);
    let found = false;
    for (let ring = 0; ring < 12 && !found; ring++) {
      const step = 40 * ring;
      const cand = ring === 0 ? [[W / 2, L / 2]] : [];
      if (ring > 0) {
        for (let a = 0; a < 360; a += 30) cand.push([W / 2 + step * Math.cos(rad(a)), L / 2 + step * Math.sin(rad(a))]);
      }
      for (const [x, z] of cand) { if (tryAt(Math.round(x / 5) * 5, Math.round(z / 5) * 5)) { found = true; break; } }
    }
    state.items = tmpItems;
    if (!found) { it.x = W / 2; it.z = L / 2; }
  }
  function analyzeOne() { analyze(); }

  function addItem(pid, x, z) {
    const p = P[pid];
    if (!p) return null;
    if (state.items.length >= MAX_ITEMS) { toast('Tối đa ' + MAX_ITEMS + ' món trong một phòng.', 'warning'); return null; }
    const it = { uid: uidSeq++, pid: pid, x: 0, z: 0, rot: 0 };
    if (typeof x === 'number' && typeof z === 'number') {
      it.x = x; it.z = z;
      snapMove(it);
      clampInside(it);
    } else {
      findFreeSpot(it);
    }
    state.items.push(it);
    selected = it.uid;
    pushHistory();
    refresh(true);
    return it;
  }
  function removeItem(uid) {
    state.items = state.items.filter(i => i.uid !== uid);
    if (selected === uid) selected = null;
    pushHistory();
    refresh(true);
  }
  function duplicateItem(uid) {
    const src = state.items.find(i => i.uid === uid);
    if (!src || state.items.length >= MAX_ITEMS) return;
    const it = { uid: uidSeq++, pid: src.pid, x: src.x + 30, z: src.z + 30, rot: src.rot };
    state.items.push(it);
    selected = it.uid;
    pushHistory();
    refresh(true);
  }
  function rotateItem(uid, deg, absolute) {
    const it = state.items.find(i => i.uid === uid);
    if (!it) return;
    it.rot = norm360(absolute ? deg : it.rot + deg);
    if ($('pl-snap').checked) snapMove(it);
  }

  // ------------------------------------------------------------------ kích thước canvas
  function resize() {
    dpr = window.devicePixelRatio || 1;
    cssW = Math.max(300, wrap.clientWidth);
    cssH = Math.max(300, wrap.clientHeight);
    canvas.width = Math.round(cssW * dpr);
    canvas.height = Math.round(cssH * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    render();
  }

  // ------------------------------------------------------------------ 2D
  function layout2d() {
    const m = 56;
    const base = Math.min((cssW - 2 * m) / state.room.w, (cssH - 2 * m) / state.room.l);
    const s = base * view2.zoom;
    const ox = (cssW - state.room.w * s) / 2 + view2.panX;
    const oy = (cssH - state.room.l * s) / 2 + view2.panY;
    return { s, ox, oy };
  }
  const w2s = (L2, x, z) => [L2.ox + x * L2.s, L2.oy + z * L2.s];
  const s2w = (L2, sx, sy) => [(sx - L2.ox) / L2.s, (sy - L2.oy) / L2.s];

  function pathPoly(L2, poly) {
    ctx.beginPath();
    poly.forEach((pt, i) => {
      const [sx, sy] = w2s(L2, pt[0], pt[1]);
      if (i === 0) ctx.moveTo(sx, sy); else ctx.lineTo(sx, sy);
    });
    ctx.closePath();
  }
  function shorten(text, maxPx) {
    if (ctx.measureText(text).width <= maxPx) return text;
    while (text.length > 3 && ctx.measureText(text + '…').width > maxPx) text = text.slice(0, -1);
    return text + '…';
  }

  function rotateHandlePos(it, L2) {
    const p = P[it.pid];
    return toWorld(it, [0, -(p.r / 2) - 30 / L2.s]);
  }

  function draw2D() {
    const L2 = layout2d(), W = state.room.w, L = state.room.l;
    ctx.fillStyle = '#e9eef4';
    ctx.fillRect(0, 0, cssW, cssH);

    // sàn phòng
    const [x0, y0] = w2s(L2, 0, 0), [x1, y1] = w2s(L2, W, L);
    ctx.fillStyle = '#fbf8f1';
    ctx.fillRect(x0, y0, x1 - x0, y1 - y0);

    // lưới 50cm / 100cm
    ctx.lineWidth = 1;
    for (let x = 0; x <= W; x += 50) {
      ctx.strokeStyle = x % 100 === 0 ? 'rgba(100,116,139,.28)' : 'rgba(100,116,139,.12)';
      const [sx] = w2s(L2, x, 0);
      ctx.beginPath(); ctx.moveTo(sx, y0); ctx.lineTo(sx, y1); ctx.stroke();
    }
    for (let z = 0; z <= L; z += 50) {
      ctx.strokeStyle = z % 100 === 0 ? 'rgba(100,116,139,.28)' : 'rgba(100,116,139,.12)';
      const [, sy] = w2s(L2, 0, z);
      ctx.beginPath(); ctx.moveTo(x0, sy); ctx.lineTo(x1, sy); ctx.stroke();
    }

    // tường
    ctx.strokeStyle = '#334155'; ctx.lineWidth = 4;
    ctx.strokeRect(x0, y0, x1 - x0, y1 - y0);

    // kích thước phòng
    ctx.fillStyle = '#475569'; ctx.font = '12px sans-serif'; ctx.textAlign = 'center'; ctx.textBaseline = 'bottom';
    ctx.fillText(W + ' cm', (x0 + x1) / 2, y0 - 8);
    ctx.save(); ctx.translate(x0 - 10, (y0 + y1) / 2); ctx.rotate(-Math.PI / 2); ctx.textBaseline = 'bottom';
    ctx.fillText(L + ' cm', 0, 0); ctx.restore();
    ctx.font = '10px sans-serif'; ctx.fillStyle = '#94a3b8'; ctx.textBaseline = 'top';
    for (let x = 100; x < W; x += 100) { const [sx] = w2s(L2, x, 0); ctx.fillText(x, sx, y1 + 4); }
    ctx.textAlign = 'right'; ctx.textBaseline = 'middle';
    for (let z = 100; z < L; z += 100) { const [, sy] = w2s(L2, 0, z); ctx.fillText(z, x0 - 6, sy); }

    // món đồ
    state.items.forEach(it => {
      const p = P[it.pid], info = issues.get(it.uid) || { out: false, overlaps: [] };
      const bad = info.out || info.overlaps.length > 0;
      const color = colorOf(p);
      const polys = worldPolys(it);
      polys.forEach(poly => {
        pathPoly(L2, poly);
        ctx.globalAlpha = p.elev > 0 ? 0.55 : 0.92;
        ctx.fillStyle = bad ? '#fca5a5' : color;
        ctx.fill();
        ctx.globalAlpha = 1;
      });
      polys.forEach(poly => {
        pathPoly(L2, poly);
        ctx.setLineDash(p.elev > 0 ? [6, 4] : []);
        ctx.lineWidth = it.uid === selected ? 3 : 1.5;
        ctx.strokeStyle = bad ? '#dc2626' : (it.uid === selected ? '#2563eb' : 'rgba(0,0,0,.45)');
        ctx.stroke();
        ctx.setLineDash([]);
      });

      // nhãn
      const bb = bboxOf(it);
      const pw = (bb.maxx - bb.minx) * L2.s, ph = (bb.maxz - bb.minz) * L2.s;
      if (pw > 54 && ph > 26) {
        const [cx, cy] = w2s(L2, it.x, it.z);
        ctx.fillStyle = '#fff'; ctx.strokeStyle = 'rgba(0,0,0,.55)'; ctx.lineWidth = 3;
        ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.font = '600 11px sans-serif';
        const name = shorten(p.ten, pw - 8);
        ctx.strokeText(name, cx, cy - 6); ctx.fillText(name, cx, cy - 6);
        ctx.font = '10px sans-serif';
        const dim = p.d + '×' + p.r + (p.elev > 0 ? ' ↑' + p.elev : '');
        ctx.strokeText(dim, cx, cy + 8); ctx.fillText(dim, cx, cy + 8);
      }
    });

    // trang trí món đang chọn
    const it = state.items.find(i => i.uid === selected);
    if (it) {
      const p = P[it.pid], bb = bboxOf(it);
      // khoảng cách tới tường
      ctx.setLineDash([4, 4]); ctx.strokeStyle = '#2563eb'; ctx.lineWidth = 1;
      ctx.fillStyle = '#1d4ed8'; ctx.font = '11px sans-serif'; ctx.textBaseline = 'bottom'; ctx.textAlign = 'center';
      const cz = (bb.minz + bb.maxz) / 2, cx = (bb.minx + bb.maxx) / 2;
      const dists = [
        [bb.minx, cz, 0, cz, bb.minx], [bb.maxx, cz, W, cz, W - bb.maxx],
        [cx, bb.minz, cx, 0, bb.minz], [cx, bb.maxz, cx, L, L - bb.maxz]
      ];
      dists.forEach(([ax, az, bx, bz, d]) => {
        if (d < 3) return;
        const [sa, sb] = w2s(L2, ax, az), [ea, eb] = w2s(L2, bx, bz);
        ctx.beginPath(); ctx.moveTo(sa, sb); ctx.lineTo(ea, eb); ctx.stroke();
        ctx.fillText(Math.round(d) + '', (sa + ea) / 2, (sb + eb) / 2 - 2);
      });
      ctx.setLineDash([]);

      // tay cầm xoay
      const hp = rotateHandlePos(it, L2), top = toWorld(it, [0, -p.r / 2]);
      const [hx, hy] = w2s(L2, hp[0], hp[1]), [tx, ty] = w2s(L2, top[0], top[1]);
      ctx.strokeStyle = '#2563eb'; ctx.lineWidth = 2;
      ctx.beginPath(); ctx.moveTo(tx, ty); ctx.lineTo(hx, hy); ctx.stroke();
      ctx.fillStyle = '#2563eb'; ctx.beginPath(); ctx.arc(hx, hy, 9, 0, Math.PI * 2); ctx.fill();
      ctx.fillStyle = '#fff'; ctx.font = '12px sans-serif'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
      ctx.fillText('↻', hx, hy + 1);
    }
  }

  // ------------------------------------------------------------------ 3D
  const v3 = {
    sub: (a, b) => [a[0] - b[0], a[1] - b[1], a[2] - b[2]],
    add: (a, b) => [a[0] + b[0], a[1] + b[1], a[2] + b[2]],
    mul: (a, k) => [a[0] * k, a[1] * k, a[2] * k],
    dot: (a, b) => a[0] * b[0] + a[1] * b[1] + a[2] * b[2],
    cross: (a, b) => [a[1] * b[2] - a[2] * b[1], a[2] * b[0] - a[0] * b[2], a[0] * b[1] - a[1] * b[0]],
    norm: a => { const l = Math.hypot(a[0], a[1], a[2]) || 1; return [a[0] / l, a[1] / l, a[2] / l]; }
  };
  const LIGHT = v3.norm([-0.35, 0.85, 0.45]);

  function camera() {
    const W = state.room.w, L = state.room.l, H = state.room.h;
    const t = [W / 2, H * 0.25, L / 2];
    const cp = Math.cos(cam3.pitch);
    const pos = [t[0] + cam3.dist * Math.sin(cam3.yaw) * cp, t[1] + cam3.dist * Math.sin(cam3.pitch), t[2] + cam3.dist * Math.cos(cam3.yaw) * cp];
    const fwd = v3.norm(v3.sub(t, pos));
    const right = v3.norm(v3.cross(fwd, [0, 1, 0]));
    const up = v3.cross(right, fwd);
    const f = (cssH / 2) / Math.tan(rad(24));
    return { pos, fwd, right, up, f };
  }
  function project(c, p) {
    const v = v3.sub(p, c.pos);
    const zc = v3.dot(v, c.fwd);
    if (zc < 5) return null;
    return [cssW / 2 + c.f * v3.dot(v, c.right) / zc, cssH / 2 - c.f * v3.dot(v, c.up) / zc, zc];
  }
  function rayFrom(c, sx, sy) {
    const d = v3.add(v3.add(c.fwd, v3.mul(c.right, (sx - cssW / 2) / c.f)), v3.mul(c.up, -(sy - cssH / 2) / c.f));
    return { o: c.pos, d: v3.norm(d) };
  }
  function floorPoint(sx, sy) {
    const c = camera(), r = rayFrom(c, sx, sy);
    if (r.d[1] > -1e-4) return null;
    const t = -r.o[1] / r.d[1];
    return [r.o[0] + r.d[0] * t, r.o[2] + r.d[2] * t];
  }
  /** Chọn món bằng tia từ camera (giao với hộp bao đã xoay) */
  function pick3D(sx, sy) {
    const c = camera(), r = rayFrom(c, sx, sy);
    let best = null, bestT = Infinity;
    state.items.forEach(it => {
      const p = P[it.pid];
      const cs = Math.cos(rad(it.rot)), sn = Math.sin(rad(it.rot));
      const ox = r.o[0] - it.x, oz = r.o[2] - it.z;
      const lo = [ox * cs + oz * sn, r.o[1], -ox * sn + oz * cs];
      const ld = [r.d[0] * cs + r.d[2] * sn, r.d[1], -r.d[0] * sn + r.d[2] * cs];
      const mn = [-p.d / 2, p.elev, -p.r / 2], mx = [p.d / 2, p.elev + p.c, p.r / 2];
      let t0 = 0, t1 = Infinity, ok = true;
      for (let k = 0; k < 3 && ok; k++) {
        if (Math.abs(ld[k]) < 1e-9) { if (lo[k] < mn[k] || lo[k] > mx[k]) ok = false; }
        else {
          let a = (mn[k] - lo[k]) / ld[k], b = (mx[k] - lo[k]) / ld[k];
          if (a > b) { const tmp = a; a = b; b = tmp; }
          t0 = Math.max(t0, a); t1 = Math.min(t1, b);
          if (t0 > t1) ok = false;
        }
      }
      if (ok && t0 < bestT) { bestT = t0; best = it; }
    });
    return best;
  }

  /** Lăng trụ đứng từ đa giác đáy (tọa độ thế giới) -> danh sách mặt */
  function prismFaces(poly, y0, y1, color, uid) {
    const faces = [];
    const cx = poly.reduce((s, p) => s + p[0], 0) / poly.length;
    const cz = poly.reduce((s, p) => s + p[1], 0) / poly.length;
    faces.push({ pts: poly.map(p => [p[0], y1, p[1]]), n: [0, 1, 0], color, uid });
    faces.push({ pts: poly.slice().reverse().map(p => [p[0], y0, p[1]]), n: [0, -1, 0], color, uid });
    for (let i = 0; i < poly.length; i++) {
      const a = poly[i], b = poly[(i + 1) % poly.length];
      let nx = b[1] - a[1], nz = a[0] - b[0];
      const len = Math.hypot(nx, nz) || 1; nx /= len; nz /= len;
      if (nx * ((a[0] + b[0]) / 2 - cx) + nz * ((a[1] + b[1]) / 2 - cz) < 0) { nx = -nx; nz = -nz; }
      faces.push({ pts: [[a[0], y0, a[1]], [b[0], y0, b[1]], [b[0], y1, b[1]], [a[0], y1, a[1]]], n: [nx, 0, nz], color, uid });
    }
    return faces;
  }

  function draw3D() {
    const W = state.room.w, L = state.room.l, H = state.room.h;
    const c = camera();
    const g = ctx.createLinearGradient(0, 0, 0, cssH);
    g.addColorStop(0, '#dbe5ef'); g.addColorStop(1, '#eef2f6');
    ctx.fillStyle = g; ctx.fillRect(0, 0, cssW, cssH);

    const poly2 = pts => {
      const out = pts.map(p => project(c, p));
      if (out.some(o => !o)) return null;
      return out;
    };
    const fillPoly = (pts, fill, stroke, lw) => {
      const o = poly2(pts); if (!o) return;
      ctx.beginPath(); o.forEach((p, i) => i ? ctx.lineTo(p[0], p[1]) : ctx.moveTo(p[0], p[1])); ctx.closePath();
      if (fill) { ctx.fillStyle = fill; ctx.fill(); }
      if (stroke) { ctx.strokeStyle = stroke; ctx.lineWidth = lw || 1; ctx.stroke(); }
    };

    // sàn + lưới
    fillPoly([[0, 0, 0], [W, 0, 0], [W, 0, L], [0, 0, L]], '#e8dcc6', '#8b7d65', 2);
    ctx.lineWidth = 1;
    for (let x = 50; x < W; x += 50) {
      const a = project(c, [x, 0, 0]), b = project(c, [x, 0, L]);
      if (a && b) { ctx.strokeStyle = x % 100 === 0 ? 'rgba(0,0,0,.16)' : 'rgba(0,0,0,.07)'; ctx.beginPath(); ctx.moveTo(a[0], a[1]); ctx.lineTo(b[0], b[1]); ctx.stroke(); }
    }
    for (let z = 50; z < L; z += 50) {
      const a = project(c, [0, 0, z]), b = project(c, [W, 0, z]);
      if (a && b) { ctx.strokeStyle = z % 100 === 0 ? 'rgba(0,0,0,.16)' : 'rgba(0,0,0,.07)'; ctx.beginPath(); ctx.moveTo(a[0], a[1]); ctx.lineTo(b[0], b[1]); ctx.stroke(); }
    }

    // tường phía sau (mặt hướng ra ngoài quay lưng lại camera)
    const walls = [
      { n: [0, 0, -1], pts: [[0, 0, 0], [W, 0, 0], [W, H, 0], [0, H, 0]], c: [W / 2, 0, 0] },
      { n: [0, 0, 1], pts: [[0, 0, L], [W, 0, L], [W, H, L], [0, H, L]], c: [W / 2, 0, L] },
      { n: [-1, 0, 0], pts: [[0, 0, 0], [0, 0, L], [0, H, L], [0, H, 0]], c: [0, 0, L / 2] },
      { n: [1, 0, 0], pts: [[W, 0, 0], [W, 0, L], [W, H, L], [W, H, 0]], c: [W, 0, L / 2] }
    ];
    walls.forEach(w => {
      if (v3.dot(w.n, v3.sub(c.pos, w.c)) < 0) fillPoly(w.pts, 'rgba(203,213,225,.72)', 'rgba(71,85,105,.7)', 2);
    });

    // kích thước phòng trên mép sàn
    ctx.font = '12px sans-serif'; ctx.fillStyle = '#334155'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
    const lab = (p, text) => { const o = project(c, p); if (o) ctx.fillText(text, o[0], o[1]); };
    lab([W / 2, 0, L + 28], W + ' cm'); lab([-30, 0, L / 2], L + ' cm'); lab([W + 32, 0, L / 2], L + ' cm');

    // các mặt của món đồ
    let faces = [];
    state.items.forEach(it => {
      const p = P[it.pid], info = issues.get(it.uid) || { out: false, overlaps: [] };
      const color = (info.out || info.overlaps.length) ? '#e05a5a' : colorOf(p);
      worldPolys(it).forEach(poly => { faces = faces.concat(prismFaces(poly, p.elev, p.elev + p.c, color, it.uid)); });
    });
    const drawn = [];
    faces.forEach(f => {
      const cen = f.pts.reduce((s, q) => [s[0] + q[0], s[1] + q[1], s[2] + q[2]], [0, 0, 0]).map(k => k / f.pts.length);
      if (v3.dot(f.n, v3.sub(c.pos, cen)) <= 0) return;
      const o = poly2(f.pts); if (!o) return;
      drawn.push({ f, o, dist: Math.hypot(c.pos[0] - cen[0], c.pos[1] - cen[1], c.pos[2] - cen[2]) });
    });
    drawn.sort((a, b) => b.dist - a.dist);
    drawn.forEach(({ f, o }) => {
      const light = 0.5 + 0.5 * Math.max(0, v3.dot(f.n, LIGHT));
      ctx.beginPath(); o.forEach((p, i) => i ? ctx.lineTo(p[0], p[1]) : ctx.moveTo(p[0], p[1])); ctx.closePath();
      ctx.fillStyle = shade(f.color, light); ctx.fill();
      ctx.strokeStyle = f.uid === selected ? '#2563eb' : 'rgba(0,0,0,.28)';
      ctx.lineWidth = f.uid === selected ? 2.5 : 0.8; ctx.stroke();
    });

    // dây treo đèn
    state.items.forEach(it => {
      const p = P[it.pid];
      if (p.elev >= 100 && p.elev + p.c < H) {
        const a = project(c, [it.x, p.elev + p.c, it.z]), b = project(c, [it.x, H, it.z]);
        if (a && b) { ctx.strokeStyle = 'rgba(30,41,59,.7)'; ctx.lineWidth = 1.5; ctx.beginPath(); ctx.moveTo(a[0], a[1]); ctx.lineTo(b[0], b[1]); ctx.stroke(); }
      }
    });

    // nhãn món đang chọn
    const it = state.items.find(i => i.uid === selected);
    if (it) {
      const p = P[it.pid];
      const o = project(c, [it.x, p.elev + p.c + 12, it.z]);
      if (o) {
        const text = p.ten, dim = p.d + ' × ' + p.r + ' × ' + p.c + ' cm';
        ctx.font = '600 12px sans-serif';
        const w = Math.max(ctx.measureText(text).width, ctx.measureText(dim).width) + 16;
        ctx.fillStyle = 'rgba(15,23,42,.85)';
        ctx.fillRect(o[0] - w / 2, o[1] - 38, w, 34);
        ctx.fillStyle = '#fff'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText(text, o[0], o[1] - 28);
        ctx.font = '11px sans-serif'; ctx.fillStyle = '#93c5fd';
        ctx.fillText(dim, o[0], o[1] - 13);
      }
    }
  }

  // ------------------------------------------------------------------ vẽ + giao diện
  function render() {
    if (mode === '2d') draw2D(); else draw3D();
  }

  const ui = {
    list: $('pl-list'), chips: $('pl-chips'), search: $('pl-search'),
    inspector: $('pl-inspector-body'), summary: $('pl-summary'), warnings: $('pl-warnings'),
    empty: $('pl-empty'), cartBtn: $('pl-add-cart'), legend: $('pl-legend'), help: $('pl-help')
  };
  let catFilter = 0;

  function buildCatalog() {
    ui.chips.textContent = '';
    const mk = (id, name) => el('button', {
      type: 'button', class: 'pl-chip' + (catFilter === id ? ' active' : ''), text: name,
      onclick: () => { catFilter = id; buildCatalog(); }
    });
    ui.chips.appendChild(mk(0, 'Tất cả'));
    DATA.categories.forEach(c => ui.chips.appendChild(mk(c.id, c.ten)));

    const q = ui.search.value.trim().toLowerCase();
    ui.list.textContent = '';
    const shown = DATA.products.filter(p => (!catFilter || p.dm === catFilter) && (!q || p.ten.toLowerCase().indexOf(q) !== -1));
    if (!shown.length) {
      ui.list.appendChild(el('div', { class: 'pl-none', text: DATA.products.length ? 'Không có sản phẩm phù hợp.' : 'Chưa có sản phẩm nào có số đo.' }));
    }
    shown.forEach(p => {
      const card = el('div', { class: 'pl-card', 'data-pid': p.id }, [
        el('img', { src: p.anh, alt: '', loading: 'lazy', onerror: function () { this.style.visibility = 'hidden'; } }),
        el('div', null, [
          el('div', { class: 'pl-card-name', text: p.ten }),
          el('div', { class: 'pl-card-meta', text: p.d + ' × ' + p.r + ' × ' + p.c + ' cm' + (p.elev > 0 ? ' · treo cao ' + p.elev : '') }),
          el('div', { class: 'pl-card-price', text: fmtVnd(p.gia) })
        ]),
        el('button', { type: 'button', class: 'pl-add', title: 'Thêm vào phòng', text: '+', onclick: ev => { ev.stopPropagation(); addItem(p.id); } })
      ]);
      card.addEventListener('pointerdown', ev => startCatalogDrag(ev, p, card));
      ui.list.appendChild(card);
    });
  }

  function rotationInputs(it) {
    const range = el('input', { type: 'range', min: 0, max: 359, step: 1, value: Math.round(it.rot) });
    const num = el('input', { type: 'number', min: 0, max: 359, step: 1, value: Math.round(it.rot) });
    const apply = v => { rotateItem(it.uid, +v || 0, true); range.value = num.value = Math.round(state.items.find(i => i.uid === it.uid).rot); analyze(); render(); updateSummary(); };
    range.addEventListener('input', () => apply(range.value));
    num.addEventListener('input', () => apply(num.value));
    range.addEventListener('change', pushHistory);
    num.addEventListener('change', pushHistory);
    return el('div', { class: 'pl-rot' }, [el('span', { text: 'Xoay' }), range, num, el('span', { text: '°' })]);
  }

  function updateInspector() {
    ui.inspector.textContent = '';
    const it = state.items.find(i => i.uid === selected);
    if (!it) {
      ui.inspector.className = 'pl-muted';
      ui.inspector.textContent = 'Chọn một món trong phòng để xem kích thước và xoay.';
      return;
    }
    ui.inspector.className = '';
    const p = P[it.pid];
    ui.inspector.appendChild(el('div', { class: 'pl-insp-name', text: p.ten }));
    const dimBox = (label, v) => el('div', null, [el('strong', { text: v }), document.createTextNode(label)]);
    ui.inspector.appendChild(el('div', { class: 'pl-insp-dims' }, [
      dimBox('Dài (cm)', String(p.d)), dimBox(p.shape === 'tron' ? 'Rộng (cm)' : 'Sâu (cm)', String(p.r)), dimBox('Cao (cm)', String(p.c))
    ]));
    if (p.elev > 0) ui.inspector.appendChild(el('div', { class: 'pl-row' }, [el('span', { text: 'Đáy cách sàn' }), el('b', { text: p.elev + ' cm' })]));
    if (p.kt) ui.inspector.appendChild(el('div', { class: 'pl-muted', text: 'Thông số: ' + p.kt }));
    ui.inspector.appendChild(rotationInputs(it));
    ui.inspector.appendChild(el('div', { class: 'pl-insp-actions' }, [
      el('button', { type: 'button', class: 'pl-btn', text: '↻ 90°', onclick: () => { rotateItem(it.uid, 90); pushHistory(); refresh(true); } }),
      el('button', { type: 'button', class: 'pl-btn', text: '⧉ Nhân đôi', onclick: () => duplicateItem(it.uid) }),
      el('button', { type: 'button', class: 'pl-btn pl-btn-danger', text: '🗑 Xóa', onclick: () => removeItem(it.uid) }),
      el('a', { class: 'pl-btn', href: DATA.productUrl + encodeURIComponent(p.slug), target: '_blank', rel: 'noopener', text: 'Xem sản phẩm' })
    ]));
  }

  function updateSummary() {
    ui.summary.textContent = '';
    ui.warnings.textContent = '';
    const W = state.room.w, L = state.room.l;
    const roomArea = W * L;
    const counts = new Map();
    let total = 0, used = 0;
    state.items.forEach(it => {
      const p = P[it.pid];
      counts.set(p.id, (counts.get(p.id) || 0) + 1);
      total += p.gia;
      if (p.elev === 0) used += footprintArea(p);
    });
    const pct = roomArea ? Math.min(100, used / roomArea * 100) : 0;

    ui.summary.appendChild(el('div', { class: 'pl-row' }, [el('span', { text: 'Diện tích phòng' }), el('b', { text: round1(roomArea / 10000) + ' m² (' + round1(W / 100) + ' × ' + round1(L / 100) + ' m)' })]));
    ui.summary.appendChild(el('div', { class: 'pl-row' }, [el('span', { text: 'Nội thất chiếm sàn' }), el('b', { text: round1(pct) + '%' })]));
    ui.summary.appendChild(el('div', { class: 'pl-bar' }, [el('span', { class: pct > 60 ? 'bad' : (pct > 45 ? 'warn' : ''), style: 'width:' + pct + '%' })]));

    if (counts.size) {
      const ul = el('ul', { class: 'pl-sum-list' });
      counts.forEach((n, pid) => {
        const p = P[pid];
        ul.appendChild(el('li', null, [el('span', { text: n + ' × ' + p.ten }), el('b', { text: fmtVnd(p.gia * n) })]));
      });
      ui.summary.appendChild(ul);
      ui.summary.appendChild(el('div', { class: 'pl-row' }, [el('span', { text: 'Tạm tính' }), el('span', { class: 'pl-sum-total', text: fmtVnd(total) })]));
    } else {
      ui.summary.appendChild(el('div', { class: 'pl-muted', text: 'Chưa có món nào trong phòng.' }));
    }
    ui.cartBtn.disabled = counts.size === 0;

    // cảnh báo
    const warns = [];
    const seen = new Set();
    state.items.forEach(it => {
      const info = issues.get(it.uid);
      if (!info) return;
      const p = P[it.pid];
      if (info.out) warns.push('“' + p.ten + '” nằm ngoài phòng.');
      info.overlaps.forEach(uid => {
        const key = [it.uid, uid].sort().join('-');
        if (seen.has(key)) return;
        seen.add(key);
        const other = state.items.find(i => i.uid === uid);
        warns.push('“' + p.ten + '” chồng lên “' + P[other.pid].ten + '”.');
      });
    });
    counts.forEach((n, pid) => { if (n > P[pid].ton) warns.push('“' + P[pid].ten + '”: cần ' + n + ' nhưng chỉ còn ' + P[pid].ton + ' trong kho.'); });
    if (pct > 60) warns.push('Nội thất chiếm hơn 60% diện tích sàn, phòng sẽ khá chật.');
    warns.slice(0, 6).forEach(w => ui.warnings.appendChild(el('div', { class: 'pl-warn', text: '⚠ ' + w })));
    if (warns.length > 6) ui.warnings.appendChild(el('div', { class: 'pl-muted', text: '… và ' + (warns.length - 6) + ' cảnh báo khác.' }));
    if (!warns.length && state.items.length) ui.warnings.appendChild(el('div', { class: 'pl-ok', text: '✓ Bố cục hợp lệ: không chồng lấn, nằm gọn trong phòng.' }));
  }

  function updateHelp() {
    ui.help.textContent = '';
    const add = (t) => ui.help.appendChild(document.createTextNode(t));
    const k = t => el('kbd', { text: t });
    if (mode === '2d') {
      add('Kéo món để di chuyển · kéo nút ↻ để xoay · kéo nền để dịch khung nhìn · lăn chuột để phóng to/thu nhỏ · ');
    } else {
      add('Kéo nền để xoay góc nhìn · kéo món để di chuyển trên sàn · lăn chuột để zoom · ');
    }
    ui.help.appendChild(k('R')); add(' xoay 90° · ');
    ui.help.appendChild(k('Del')); add(' xóa · ');
    ui.help.appendChild(k('Ctrl+D')); add(' nhân đôi · ');
    ui.help.appendChild(k('Ctrl+Z')); add(' hoàn tác · phím mũi tên: nhích 1cm (Shift = 10cm).');
    ui.legend.textContent = mode === '2d' ? 'Lưới 50 cm · số trên đường đứt là khoảng cách tới tường (cm)' : 'Phối cảnh 3D · kích thước theo cm thực tế';
  }

  function refresh(rebuildInspector) {
    analyze();
    ui.empty.classList.toggle('hidden', state.items.length > 0);
    $('pl-room-w').value = state.room.w; $('pl-room-l').value = state.room.l; $('pl-room-h').value = state.room.h;
    $('pl-undo').disabled = undoStack.length < 2;
    $('pl-redo').disabled = redoStack.length === 0;
    if (rebuildInspector) updateInspector();
    updateSummary();
    render();
  }

  function fitView() {
    view2.zoom = 1; view2.panX = 0; view2.panY = 0;
    cam3.yaw = 0.55; cam3.pitch = 0.8;
    cam3.dist = Math.hypot(state.room.w, state.room.l) * 1.45 + state.room.h * 0.7;
    render();
  }

  // ------------------------------------------------------------------ kéo thả từ danh mục
  let ghost = null;
  function startCatalogDrag(ev, p, card) {
    if (ev.button !== undefined && ev.button !== 0) return;
    if (ev.target.closest('.pl-add')) return;
    const sx = ev.clientX, sy = ev.clientY;
    let started = false;
    const pid = ev.pointerId;
    if (ev.pointerType !== 'mouse') {
      // cảm ứng: chỉ kéo thả khi bấm giữ rồi kéo (tránh chặn cuộn danh sách) -> dùng nút + để thêm nhanh
      return;
    }
    const move = e => {
      if (!started && Math.hypot(e.clientX - sx, e.clientY - sy) > 6) {
        started = true;
        ghost = el('div', { class: 'pl-ghost', text: p.ten + ' (' + p.d + '×' + p.r + ' cm)' });
        document.body.appendChild(ghost);
      }
      if (started && ghost) { ghost.style.left = e.clientX + 'px'; ghost.style.top = e.clientY + 'px'; }
    };
    const up = e => {
      document.removeEventListener('pointermove', move);
      document.removeEventListener('pointerup', up);
      document.removeEventListener('pointercancel', up);
      if (ghost) { ghost.remove(); ghost = null; }
      // thả rất nhanh có thể không phát sinh pointermove: vẫn tính là kéo nếu đã di chuyển đủ xa
      if (!started && Math.hypot(e.clientX - sx, e.clientY - sy) > 6) started = true;
      if (!started) return;
      const r = canvas.getBoundingClientRect();
      if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) return;
      const px = e.clientX - r.left, py = e.clientY - r.top;
      let pt;
      if (mode === '2d') pt = s2w(layout2d(), px, py);
      else pt = floorPoint(px, py);
      if (!pt) { addItem(p.id); return; }
      addItem(p.id, pt[0], pt[1]);
    };
    document.addEventListener('pointermove', move);
    document.addEventListener('pointerup', up);
    document.addEventListener('pointercancel', up);
    void pid; void card;
  }

  // ------------------------------------------------------------------ tương tác trên canvas
  const pointers = new Map();
  let drag = null;
  let pinch = null;

  function canvasXY(e) {
    const r = canvas.getBoundingClientRect();
    return [e.clientX - r.left, e.clientY - r.top];
  }

  function onDown(e) {
    if (e.button !== undefined && e.button !== 0 && e.button !== 1 && e.button !== 2) return;
    canvas.setPointerCapture(e.pointerId);
    const [x, y] = canvasXY(e);
    pointers.set(e.pointerId, [x, y]);
    if (pointers.size === 2) {
      const [a, b] = Array.from(pointers.values());
      pinch = { d: Math.hypot(a[0] - b[0], a[1] - b[1]), zoom: view2.zoom, dist: cam3.dist };
      drag = null;
      return;
    }
    if (mode === '2d') down2D(x, y, e); else down3D(x, y, e);
  }

  function down2D(x, y, e) {
    const L2 = layout2d(), pt = s2w(L2, x, y);
    const cur = state.items.find(i => i.uid === selected);
    if (cur) {
      const hp = rotateHandlePos(cur, L2), [hx, hy] = w2s(L2, hp[0], hp[1]);
      if (Math.hypot(hx - x, hy - y) <= 14) { drag = { type: 'rotate', uid: cur.uid, changed: false }; return; }
    }
    for (let i = state.items.length - 1; i >= 0; i--) {
      const it = state.items[i];
      if (hitItem(it, pt)) {
        selected = it.uid;
        drag = { type: 'item', uid: it.uid, ox: it.x - pt[0], oz: it.z - pt[1], changed: false };
        updateInspector(); render();
        return;
      }
    }
    selected = null; updateInspector();
    drag = { type: 'pan', sx: x, sy: y, px: view2.panX, py: view2.panY };
    render();
    void e;
  }

  function down3D(x, y, e) {
    const it = pick3D(x, y);
    if (it && e.button !== 2) {
      selected = it.uid;
      const fp = floorPoint(x, y);
      drag = fp ? { type: 'item', uid: it.uid, ox: it.x - fp[0], oz: it.z - fp[1], changed: false } : null;
      updateInspector(); render();
      return;
    }
    selected = null; updateInspector();
    drag = { type: 'orbit', sx: x, sy: y, yaw: cam3.yaw, pitch: cam3.pitch };
    render();
  }

  function onMove(e) {
    const [x, y] = canvasXY(e);
    if (pointers.has(e.pointerId)) pointers.set(e.pointerId, [x, y]);

    if (pinch && pointers.size >= 2) {
      const [a, b] = Array.from(pointers.values());
      const d = Math.hypot(a[0] - b[0], a[1] - b[1]);
      if (pinch.d > 0) {
        view2.zoom = clamp(pinch.zoom * d / pinch.d, 0.4, 6);
        cam3.dist = clamp(pinch.dist * pinch.d / d, 150, 3500);
      }
      render();
      return;
    }
    if (!drag) {
      updateCursor(x, y);
      return;
    }

    if (drag.type === 'item') {
      const it = state.items.find(i => i.uid === drag.uid);
      if (!it) return;
      const pt = mode === '2d' ? s2w(layout2d(), x, y) : floorPoint(x, y);
      if (!pt) return;
      it.x = pt[0] + drag.ox; it.z = pt[1] + drag.oz;
      snapMove(it);
      drag.changed = true;
      analyze(); updateSummary(); render();
    } else if (drag.type === 'rotate') {
      const it = state.items.find(i => i.uid === drag.uid);
      if (!it) return;
      const pt = s2w(layout2d(), x, y);
      let ang = Math.atan2(pt[1] - it.z, pt[0] - it.x) * 180 / Math.PI + 90;
      if (!e.shiftKey) ang = Math.round(ang / 5) * 5;
      it.rot = norm360(ang);
      drag.changed = true;
      analyze(); updateSummary(); updateInspector(); render();
    } else if (drag.type === 'pan') {
      view2.panX = drag.px + (x - drag.sx); view2.panY = drag.py + (y - drag.sy);
      render();
    } else if (drag.type === 'orbit') {
      cam3.yaw = drag.yaw - (x - drag.sx) * 0.008;
      cam3.pitch = clamp(drag.pitch + (y - drag.sy) * 0.006, 0.12, 1.45);
      render();
    }
  }

  function onUp(e) {
    pointers.delete(e.pointerId);
    if (pointers.size < 2) pinch = null;
    if (drag && drag.changed) { pushHistory(); refresh(true); }
    if (pointers.size === 0) drag = null;
    try { canvas.releasePointerCapture(e.pointerId); } catch (err) { /* đã giải phóng */ }
  }

  function updateCursor(x, y) {
    let cur = 'default';
    if (mode === '2d') {
      const L2 = layout2d(), pt = s2w(L2, x, y);
      const sel = state.items.find(i => i.uid === selected);
      if (sel) {
        const hp = rotateHandlePos(sel, L2), [hx, hy] = w2s(L2, hp[0], hp[1]);
        if (Math.hypot(hx - x, hy - y) <= 14) cur = 'grab';
      }
      if (cur === 'default' && state.items.some(i => hitItem(i, pt))) cur = 'move';
      else if (cur === 'default') cur = 'grab';
    } else {
      cur = pick3D(x, y) ? 'move' : 'grab';
    }
    canvas.style.cursor = cur;
  }

  function onWheel(e) {
    e.preventDefault();
    const f = Math.exp(-e.deltaY * 0.0012);
    if (mode === '2d') {
      const [x, y] = canvasXY(e);
      const L2 = layout2d(), before = s2w(L2, x, y);
      view2.zoom = clamp(view2.zoom * f, 0.4, 6);
      const L3 = layout2d();
      // giữ nguyên điểm dưới con trỏ
      view2.panX += x - (L3.ox + before[0] * L3.s);
      view2.panY += y - (L3.oy + before[1] * L3.s);
    } else {
      cam3.dist = clamp(cam3.dist / f, 150, 3500);
    }
    render();
  }

  // ------------------------------------------------------------------ phím tắt
  document.addEventListener('keydown', e => {
    const tag = (e.target.tagName || '').toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
    const key = e.key.toLowerCase();
    if ((e.ctrlKey || e.metaKey) && key === 'z') { e.preventDefault(); e.shiftKey ? redo() : undo(); return; }
    if ((e.ctrlKey || e.metaKey) && key === 'y') { e.preventDefault(); redo(); return; }
    const it = state.items.find(i => i.uid === selected);
    if (!it) return;
    if ((e.ctrlKey || e.metaKey) && key === 'd') { e.preventDefault(); duplicateItem(it.uid); return; }
    if (key === 'delete' || key === 'backspace') { e.preventDefault(); removeItem(it.uid); return; }
    if (key === 'r') { rotateItem(it.uid, e.shiftKey ? -90 : 90); pushHistory(); refresh(true); return; }
    const step = e.shiftKey ? 10 : 1;
    const moves = { arrowleft: [-step, 0], arrowright: [step, 0], arrowup: [0, -step], arrowdown: [0, step] };
    if (moves[key]) {
      e.preventDefault();
      it.x += moves[key][0]; it.z += moves[key][1];
      pushHistory(); refresh(false);
    }
  });

  // ------------------------------------------------------------------ thanh công cụ
  function setMode(m) {
    mode = m;
    $('pl-view-2d').classList.toggle('active', m === '2d');
    $('pl-view-3d').classList.toggle('active', m === '3d');
    updateHelp();
    canvas.style.cursor = 'default';
    render();
  }
  $('pl-view-2d').addEventListener('click', () => setMode('2d'));
  $('pl-view-3d').addEventListener('click', () => setMode('3d'));

  function applyRoomInputs() {
    const w = clamp(Math.round(+$('pl-room-w').value) || state.room.w, 150, 2000);
    const l = clamp(Math.round(+$('pl-room-l').value) || state.room.l, 150, 2000);
    const h = clamp(Math.round(+$('pl-room-h').value) || state.room.h, 200, 500);
    state.room = { w, l, h };
    pushHistory(); fitView(); refresh(true);
  }
  ['pl-room-w', 'pl-room-l', 'pl-room-h'].forEach(id => $(id).addEventListener('change', applyRoomInputs));
  $('pl-preset').addEventListener('change', e => {
    if (!e.target.value) return;
    const [w, l, h] = e.target.value.split('x').map(Number);
    state.room = { w, l, h };
    e.target.value = '';
    pushHistory(); fitView(); refresh(true);
  });
  $('pl-undo').addEventListener('click', undo);
  $('pl-redo').addEventListener('click', redo);
  $('pl-fit').addEventListener('click', fitView);
  $('pl-clear').addEventListener('click', () => {
    if (!state.items.length) return;
    if (!confirm('Xóa toàn bộ nội thất trong phòng?')) return;
    state.items = []; selected = null;
    pushHistory(); refresh(true);
  });
  $('pl-share').addEventListener('click', () => {
    const url = location.origin + location.pathname + '#p=' + encodeShare();
    const done = () => toast('Đã sao chép liên kết chia sẻ bố cục!', 'success');
    if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(url).then(done, () => window.prompt('Sao chép liên kết:', url));
    else window.prompt('Sao chép liên kết:', url);
  });
  $('pl-png').addEventListener('click', () => {
    render();
    canvas.toBlob(blob => {
      if (!blob) return;
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'thiet-ke-phong-' + mode + '.png';
      document.body.appendChild(a); a.click(); a.remove();
      setTimeout(() => URL.revokeObjectURL(a.href), 2000);
    });
  });

  ui.cartBtn.addEventListener('click', async () => {
    const counts = new Map();
    state.items.forEach(it => counts.set(it.pid, (counts.get(it.pid) || 0) + 1));
    if (!counts.size) return;
    ui.cartBtn.disabled = true;
    ui.cartBtn.textContent = '⏳ Đang thêm vào giỏ...';
    let ok = 0; const fails = [];
    for (const [pid, n] of counts) {
      try {
        const res = await fetch(window.BASE_URL + '/api/v1/gio-hang', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ san_pham_id: pid, so_luong: n })
        }).then(r => r.json());
        if (res.success) { ok++; if (res.data && typeof window.updateCartBadge === 'function') window.updateCartBadge(res.data.total_quantity); }
        else fails.push(P[pid].ten + ': ' + (res.message || 'lỗi'));
      } catch (err) { fails.push(P[pid].ten + ': lỗi kết nối'); }
    }
    ui.cartBtn.textContent = '🛒 Thêm tất cả vào giỏ hàng';
    ui.cartBtn.disabled = false;
    if (ok) toast('Đã thêm ' + ok + ' sản phẩm vào giỏ hàng!', 'success');
    fails.forEach(f => toast(f, 'danger'));
    if (ok && !fails.length) window.location.href = window.BASE_URL + '/gio-hang';
  });

  ui.search.addEventListener('input', buildCatalog);

  canvas.addEventListener('pointerdown', onDown);
  canvas.addEventListener('pointermove', onMove);
  canvas.addEventListener('pointerup', onUp);
  canvas.addEventListener('pointercancel', onUp);
  canvas.addEventListener('wheel', onWheel, { passive: false });
  canvas.addEventListener('contextmenu', e => e.preventDefault());
  if (window.ResizeObserver) new ResizeObserver(resize).observe(wrap); else window.addEventListener('resize', resize);

  // ------------------------------------------------------------------ khởi tạo
  (function init() {
    let loaded = null;
    const m = /^#p=(.+)$/.exec(location.hash || '');
    if (m) loaded = decodeShare(m[1]);
    if (!loaded) {
      try { loaded = sanitizeLoaded(JSON.parse(localStorage.getItem(STORAGE_KEY))); } catch (e) { loaded = null; }
    }
    if (loaded) {
      state = loaded;
      uidSeq = state.items.length + 1;
    }
    undoStack.push(snapshot());
    buildCatalog();
    updateHelp();
    fitView();
    resize();
    refresh(true);

    // /thiet-ke-phong?add=ID  -> thêm sẵn sản phẩm từ trang chi tiết
    const add = new URLSearchParams(location.search).get('add');
    if (add && P[+add]) {
      addItem(+add);
      const clean = location.pathname + (location.hash || '');
      history.replaceState(null, '', clean);
    }
    if (m) history.replaceState(null, '', location.pathname);
  })();

  // Cho phép kiểm thử / mở rộng từ console
  window.RoomPlanner = {
    get state() { return state; }, addItem, setMode, refresh, analyze,
    get issues() { return issues; }, pick3D, floorPoint, layout2d, bboxOf, worldPolys,
    undo, redo, rotateItem, encodeShare, decodeShare, footprintArea,
    projectPoint: p => project(camera(), p), fitView
  };
})();
