/* ==========================================================================
   LONTAR · charts.js
   Tiny dependency-free SVG chart helpers. Data comes from `data-chart` JSON
   on the element, e.g. <div data-chart='{"type":"line","labels":[…],"values":[…]}'>
   ========================================================================== */
(function () {
  'use strict';

  const NS = 'http://www.w3.org/2000/svg';
  const REDUCED = () =>
    typeof window.matchMedia === 'function' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const PALETTE = ['#0191ED', '#FF6B9D', '#FFC53D', '#2DD4A7', '#8B5CF6', '#FF7A5C'];

  /* SVG presentation attributes can't hold var(), so theme colours are
     resolved on every render — charts re-render when the theme is toggled. */
  function cssVar(name, fallback) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return value || fallback;
  }

  function el(name, attrs, children) {
    const node = document.createElementNS(NS, name);
    Object.keys(attrs || {}).forEach((k) => node.setAttribute(k, attrs[k]));
    (children || []).forEach((c) => node.appendChild(c));
    return node;
  }
  function txt(content, attrs) {
    const t = el('text', attrs);
    t.textContent = content;
    return t;
  }
  const fmt = (n) => new Intl.NumberFormat('id-ID').format(n);

  function axisPadding(min, max, ticks) {
    let longestLabel = 0;

    for (let i = 0; i <= ticks; i++) {
      const value = min + ((max - min) * i) / ticks;
      longestLabel = Math.max(longestLabel, fmt(value).length);
    }

    return Math.max(52, longestLabel * 7 + 10);
  }

  /* ---------- shared frame ---------------------------------------------- */
  function frame(w, h, pad) {
    const svg = el('svg', {
      viewBox: `0 0 ${w} ${h}`,
      width: '100%',
      height: '100%',
      preserveAspectRatio: 'none',
      role: 'img',
      style: 'display:block;overflow:hidden'
    });
    svg.dataset.padded = 'true';
    svg.__pad = pad;
    svg.__w = w;
    svg.__h = h;
    return svg;
  }

  function gridY(svg, min, max, ticks, pad, fmtTick) {
    const { w, h } = { w: svg.__w, h: svg.__h };
    const plotH = h - pad.t - pad.b;
    for (let i = 0; i <= ticks; i++) {
      const v = min + ((max - min) * i) / ticks;
      const y = pad.t + plotH - (plotH * i) / ticks;
      svg.appendChild(el('line', {
        x1: pad.l, x2: w - pad.r, y1: y, y2: y,
        stroke: i === 0 ? cssVar('--border-strong', '#B9CFE0') : cssVar('--border', '#E6EFF6'),
        'stroke-width': i === 0 ? 2 : 1,
        'stroke-dasharray': i === 0 ? 'none' : '4 5'
      }));
      svg.appendChild(txt(fmtTick ? fmtTick(v) : fmt(v), {
        x: pad.l - 10, y: y + 4, 'text-anchor': 'end',
        'font-size': 11, 'font-weight': 700, fill: cssVar('--text-soft', '#64809A'),
        'font-family': 'Plus Jakarta Sans, sans-serif'
      }));
    }
  }

  function xLabels(svg, labels, pad, every) {
    const { w } = { w: svg.__w };
    const plotW = w - pad.l - pad.r;
    const step = plotW / Math.max(1, labels.length - 1);
    labels.forEach((l, i) => {
      if (every && i % every !== 0 && i !== labels.length - 1) return;
      const anchor = labels.length === 1 || (i > 0 && i < labels.length - 1)
        ? 'middle'
        : (i === 0 ? 'start' : 'end');
      svg.appendChild(txt(l, {
        x: pad.l + step * i,
        y: svg.__h - pad.b + 20,
        'text-anchor': anchor,
        'font-size': 11, 'font-weight': 700, fill: cssVar('--text-soft', '#64809A'),
        'font-family': 'Plus Jakarta Sans, sans-serif'
      }));
    });
  }

  function animateLine(path, totalLen, animate = true) {
    if (!animate || REDUCED()) return;
    path.style.strokeDasharray = totalLen;
    path.style.strokeDashoffset = totalLen;
    path.style.transition = 'stroke-dashoffset 1100ms cubic-bezier(.34,1.56,.64,1)';
    requestAnimationFrame(() => { path.style.strokeDashoffset = '0'; });
  }

  /* ---------- LINE ------------------------------------------------------ */
  function line(host, cfg) {
    const labels = cfg.labels || [];
    const values = cfg.values || [];
    const w = cfg.width || 720;
    const h = cfg.height || 260;
    const max = cfg.max != null ? cfg.max : Math.max.apply(null, values) * 1.15;
    const min = cfg.min != null ? cfg.min : 0;
    const pad = { t: 22, r: 24, b: 34, l: axisPadding(min, max, 4) };
    const color = cfg.color || PALETTE[0];
    const svg = frame(w, h, pad);

    gridY(svg, min, max, 4, pad);

    const plotW = w - pad.l - pad.r;
    const plotH = h - pad.t - pad.b;
    const x = (i) => pad.l + (plotW * i) / Math.max(1, values.length - 1);
    const valueRange = max - min || 1;
    const y = (v) => pad.t + plotH - ((v - min) / valueRange) * plotH;

    // Target / average reference line
    if (cfg.avg != null) {
      svg.appendChild(el('line', {
        x1: pad.l, x2: w - pad.r, y1: y(cfg.avg), y2: y(cfg.avg),
        stroke: cssVar('--pop-pink', '#FF6B9D'), 'stroke-width': 2, 'stroke-dasharray': '7 6'
      }));
      svg.appendChild(txt('rata-rata ' + cfg.avg, {
        x: w - pad.r, y: y(cfg.avg) - 8, 'text-anchor': 'end',
        'font-size': 11, 'font-weight': 800, fill: cssVar('--pop-pink', '#C22E63'),
        'font-family': 'Plus Jakarta Sans, sans-serif'
      }));
    }

    const pts = values.map((v, i) => [x(i), y(v)]);
    const d = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
    const area = `${d} L ${pts[pts.length - 1][0].toFixed(1)} ${(pad.t + plotH).toFixed(1)} L ${pts[0][0].toFixed(1)} ${(pad.t + plotH).toFixed(1)} Z`;

    const gid = 'lg' + Math.random().toString(36).slice(2, 7);
    const defs = el('defs');
    const grad = el('linearGradient', { id: gid, x1: 0, y1: 0, x2: 0, y2: 1 });
    grad.appendChild(el('stop', { offset: '0%', 'stop-color': color, 'stop-opacity': 0.28 }));
    grad.appendChild(el('stop', { offset: '100%', 'stop-color': color, 'stop-opacity': 0 }));
    defs.appendChild(grad);
    svg.appendChild(defs);

    svg.appendChild(el('path', { d: area, fill: `url(#${gid})` }));

    const path = el('path', {
      d, fill: 'none', stroke: color, 'stroke-width': 3.5,
      'stroke-linecap': 'round', 'stroke-linejoin': 'round'
    });
    svg.appendChild(path);
    animateLine(path, path.getTotalLength ? path.getTotalLength() : 1200, cfg.animate !== false);

    pts.forEach((p, i) => {
      const g = el('g', { class: 'chart-dot' });
      g.appendChild(el('circle', { cx: p[0], cy: p[1], r: 6, fill: cssVar('--surface', '#fff'), stroke: color, 'stroke-width': 3 }));
      g.appendChild(el('title')).textContent = `${labels[i]}: ${values[i]}`;
      svg.appendChild(g);
    });

    xLabels(svg, labels, pad, cfg.labelEvery || 1);
    return svg;
  }

  /* ---------- BAR ------------------------------------------------------- */
  function bar(host, cfg) {
    const labels = cfg.labels || [];
    const values = cfg.values || [];
    const w = cfg.width || 720;
    const h = cfg.height || 240;
    const max = cfg.max != null ? cfg.max : Math.max.apply(null, values) * 1.2 || 1;
    const pad = { t: 20, r: 16, b: 42, l: axisPadding(0, max, 4) };
    const svg = frame(w, h, pad);
    gridY(svg, 0, max, 4, pad);

    const plotW = w - pad.l - pad.r;
    const plotH = h - pad.t - pad.b;
    const slot = plotW / values.length;
    const bw = Math.min(46, slot * 0.62);

    values.forEach((v, i) => {
      const bx = pad.l + slot * i + (slot - bw) / 2;
      const bh = v > 0 ? Math.max(3, (v / max) * plotH) : 0;
      const by = pad.t + plotH - bh;
      const color = Array.isArray(cfg.colors) ? cfg.colors[i] : (cfg.color || PALETTE[0]);
      const rect = el('rect', {
        x: bx.toFixed(1), y: by.toFixed(1), width: bw.toFixed(1), height: bh.toFixed(1),
        rx: 6, fill: color, stroke: cssVar('--ink', '#16324F'), 'stroke-width': 2
      });
      const g = el('g');
      if (bh > 0) g.appendChild(rect);
      g.appendChild(el('title')).textContent = `${labels[i]}: ${v}`;
      svg.appendChild(g);
      if (cfg.animate !== false && !REDUCED()) {
        rect.style.transformOrigin = `center ${pad.t + plotH}px`;
        rect.style.transform = 'scaleY(0)';
        rect.style.transition = `transform 700ms cubic-bezier(.34,1.56,.64,1) ${i * 45}ms`;
        requestAnimationFrame(() => { rect.style.transform = 'scaleY(1)'; });
      }
      if (cfg.showValues !== false) {
        const valueLabel = slot < 72
          ? new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 }).format(v)
          : fmt(v);
        svg.appendChild(txt(valueLabel, {
          x: bx + bw / 2, y: by - 9, 'text-anchor': 'middle',
          'font-size': 11, 'font-weight': 800, fill: cssVar('--ink', '#16324F'),
          'font-family': 'Plus Jakarta Sans, sans-serif'
        }));
      }
      svg.appendChild(txt(labels[i], {
        x: bx + bw / 2, y: h - pad.b + 20, 'text-anchor': 'middle',
        'font-size': 11, 'font-weight': 700, fill: cssVar('--text-soft', '#64809A'),
        'font-family': 'Plus Jakarta Sans, sans-serif'
      }));
    });
    return svg;
  }

  /* ---------- DONUT ----------------------------------------------------- */
  function donut(host, cfg) {
    const size = cfg.size || 200;
    const data = cfg.segments || [];
    const total = data.reduce((a, s) => a + s.value, 0) || 1;
    const cx = size / 2, cy = size / 2;
    const r = size / 2 - 14;
    const thick = cfg.thickness || 26;
    const svg = el('svg', {
      viewBox: `0 0 ${size} ${size}`, width: '100%', height: '100%',
      style: 'display:block', role: 'img'
    });

    svg.appendChild(el('circle', {
      cx, cy, r, fill: 'none', stroke: cssVar('--border', '#E6EFF6'), 'stroke-width': thick
    }));

    let angle = -Math.PI / 2;
    data.forEach((seg, i) => {
      const frac = seg.value / total;
      const sweep = frac * Math.PI * 2;
      const a0 = angle, a1 = angle + sweep;
      const large = sweep > Math.PI ? 1 : 0;
      const x0 = cx + r * Math.cos(a0), y0 = cy + r * Math.sin(a0);
      const x1 = cx + r * Math.cos(a1), y1 = cy + r * Math.sin(a1);
      const path = el('path', {
        d: `M ${x0.toFixed(2)} ${y0.toFixed(2)} A ${r} ${r} 0 ${large} 1 ${x1.toFixed(2)} ${y1.toFixed(2)}`,
        fill: 'none',
        stroke: seg.color || PALETTE[i % PALETTE.length],
        'stroke-width': thick,
        'stroke-linecap': frac > 0.02 ? 'butt' : 'round'
      });
      svg.appendChild(path);
      if (!REDUCED()) {
        const len = path.getTotalLength ? path.getTotalLength() : 300;
        path.style.strokeDasharray = len;
        path.style.strokeDashoffset = len;
        path.style.transition = `stroke-dashoffset 800ms cubic-bezier(.34,1.56,.64,1) ${i * 110}ms`;
        requestAnimationFrame(() => { path.style.strokeDashoffset = '0'; });
      }
      angle = a1;
    });
    return svg;
  }

  /* ---------- SPARKLINE ------------------------------------------------- */
  function spark(host, cfg) {
    const values = cfg.values || [];
    const w = cfg.width || 120, h = cfg.height || 34;
    const min = Math.min.apply(null, values);
    const max = Math.max.apply(null, values);
    const span = max - min || 1;
    const svg = el('svg', { viewBox: `0 0 ${w} ${h}`, width: '100%', height: h, style: 'display:block' });
    const pts = values.map((v, i) => [
      (w * i) / Math.max(1, values.length - 1),
      h - 3 - ((v - min) / span) * (h - 6)
    ]);
    const d = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
    const path = el('path', { d, fill: 'none', stroke: cfg.color || PALETTE[0], 'stroke-width': 2.5, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' });
    svg.appendChild(path);
    const last = pts[pts.length - 1];
    svg.appendChild(el('circle', { cx: last[0], cy: last[1], r: 3.5, fill: cfg.color || PALETTE[0] }));
    return svg;
  }

  /* ---------- AUTO INIT ------------------------------------------------- */
  const RENDER = { line, bar, donut, spark };
  const instances = new WeakMap();
  let resizeFallbackInstalled = false;

  function initCharts() {
    document.querySelectorAll('[data-chart]').forEach((host) => {
      if (instances.has(host)) return;

      let cfg;
      try { cfg = JSON.parse(host.dataset.chart); } catch (err) { return; }
      const fn = RENDER[cfg.type] || line;
      const isResponsive = cfg.type === 'line' || cfg.type === 'bar';
      host.dataset.chartType = cfg.type || 'line';
      let lastWidth = 0;
      let lastHeight = 0;

      const render = (width, animate = false, force = false) => {
        const measuredWidth = Math.round(width || host.clientWidth || cfg.width || 720);
        const measuredHeight = Math.round(host.clientHeight || cfg.height || (cfg.type === 'bar' ? 240 : 260));

        if (!force && isResponsive && measuredWidth === lastWidth && measuredHeight === lastHeight && host.firstElementChild) return;

        lastWidth = measuredWidth;
        lastHeight = measuredHeight;
        const chartConfig = isResponsive
          ? { ...cfg, width: measuredWidth, height: measuredHeight, animate: animate && cfg.animate !== false }
          : cfg;
        const svg = fn(host, chartConfig);
        svg.setAttribute('aria-label', cfg.aria || 'Grafik');
        host.replaceChildren(svg);

        if (cfg.legend) host.appendChild(buildLegend(cfg));
      };

      render(host.clientWidth, true);
      instances.set(host, { render });

      if (isResponsive && typeof ResizeObserver !== 'undefined') {
        const observer = new ResizeObserver((entries) => {
          const width = entries[0]?.contentRect.width || 0;
          const height = entries[0]?.contentRect.height || 0;
          if (width > 0 && (Math.abs(Math.round(width) - lastWidth) >= 2 || Math.abs(Math.round(height) - lastHeight) >= 2)) render(width);
        });

        observer.observe(host);
      }
    });

    if (!resizeFallbackInstalled && typeof ResizeObserver === 'undefined') {
      window.addEventListener('resize', () => {
        document.querySelectorAll('[data-chart]').forEach((host) => {
          instances.get(host)?.render(host.clientWidth);
        });
      }, { passive: true });
      resizeFallbackInstalled = true;
    }
  }

  function buildLegend(cfg) {
    const wrap = document.createElement('div');
    wrap.style.cssText = 'display:flex;flex-wrap:wrap;gap:.75rem;margin-top:.85rem;font-size:.8125rem;font-weight:600;color:' + cssVar('--text-soft', '#64809A');
    (cfg.legendColors || PALETTE).forEach((c, i) => {
      const item = document.createElement('span');
      item.style.cssText = 'display:inline-flex;align-items:center;gap:.4rem';
      item.innerHTML = `<span style="width:14px;height:14px;border-radius:4px;border:2px solid ${cssVar('--ink', '#16324F')};background:${c};display:inline-block"></span>`;
      item.appendChild(document.createTextNode((cfg.legendLabels || [])[i] || ''));
      wrap.appendChild(item);
    });
    return wrap;
  }

  /* Re-render every chart so theme-dependent colours are re-resolved from
     the CSS variables (called when the light/dark toggle flips). */
  function refresh() {
    document.querySelectorAll('[data-chart]').forEach((host) => {
      instances.get(host)?.render(host.clientWidth, false, true);
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initCharts);
  else initCharts();

  window.LCharts = { line, bar, donut, spark, init: initCharts, refresh };
})();
