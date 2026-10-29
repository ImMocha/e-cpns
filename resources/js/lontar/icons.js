/* ==========================================================================
   LONTAR · icons.js
   Lucide-style 24×24 stroke icons. Write <svg data-icon="name"></svg> anywhere
   and this fills it in — keeps markup tiny and the icon set in one place.
   ========================================================================== */
(function () {
  'use strict';

  const P = {
    'menu': '<path d="M4 6h16M4 12h16M4 18h16"/>',
    'close': '<path d="M18 6 6 18M6 6l12 12"/>',
    'check': '<path d="M20 6 9 17l-5-5"/>',
    'check-circle': '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
    'x-circle': '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/>',
    'alert': '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
    'info': '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
    'help': '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3M12 17h.01"/>',
    'search': '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
    'bell': '<path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/><path d="M21 15.5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3.6a7 2 0 0 1 1-2.7l1.2-1.2a2 2 0 0 0 .8-1.7V7a7 7 0 0 1 14 0v1.3a2 2 0 0 0 .8 1.7l1.3 1.2a2 2 0 0 1 1 2.7Z"/>',
    'chevron-down': '<path d="m6 9 6 6 6-6"/>',
    'chevron-up': '<path d="m18 15-6-6-6 6"/>',
    'chevron-right': '<path d="m9 18 6-6-6-6"/>',
    'chevron-left': '<path d="m15 18-6-6 6-6"/>',
    'arrow-right': '<path d="M5 12h14M12 5l7 7-7 7"/>',
    'arrow-left': '<path d="M19 12H5M12 19l-7-7 7-7"/>',
    'arrow-up': '<path d="M12 19V5M5 12l7-7 7 7"/>',
    'external': '<path d="M7 17 17 7M8 7h9v9"/>',
    'link': '<path d="M10 13a5 5 0 0 0 7 .4l3-3a5 5 0 0 0-7-7l-2 2M14 11a5 5 0 0 0-7-.4l-3 3a5 5 0 0 0 7 7l2-2"/>',
    'align-left': '<path d="M3 5h18M3 10h12M3 15h18M3 20h12"/>',
    'align-center': '<path d="M3 5h18M6 10h12M3 15h18M6 20h12"/>',
    'align-right': '<path d="M3 5h18M9 10h12M3 15h18M9 20h12"/>',
    'align-justify': '<path d="M3 5h18M3 10h18M3 15h18M3 20h18"/>',
    'dashboard': '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
    'book': '<path d="M12 7v14M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3Z"/>',
    'layers': '<path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/>',
    'pencil': '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
    'timer': '<circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2.5"/><path d="M9 2h6"/>',
    'clock': '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'video': '<path d="m22 8-6 4 6 4V8Z"/><rect x="2" y="6" width="14" height="12" rx="2"/>',
    'history': '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>',
    'chart': '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M7.5 17v-5M12 17V7M16.5 17v-3"/>',
    'trending-up': '<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
    'trending-down': '<path d="m3 7 6 6 4-4 8 8"/><path d="M15 17h6v-6"/>',
    'trophy': '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.7V17c0 .6-.4 1-1 1H6v2h12v-2h-3c-.6 0-1-.4-1-1v-2.3"/><path d="M8 4h8v5a4 4 0 0 1-8 0Z"/>',
    'package': '<path d="m7.5 4.3 9 5.2"/><path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="M3.3 7 12 12l8.7-5M12 22V12"/>',
    'award': '<circle cx="12" cy="8" r="6"/><path d="M15.5 13.5 17 22l-5-3-5 3 1.5-8.5"/>',
    'user': '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    'users': '<circle cx="9" cy="8" r="3.5"/><path d="M2 21a7 7 0 0 1 14 0"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 21a6 6 0 0 0-3-5.2"/>',
    'logout': '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
    'settings': '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.2 2.2M16.9 16.9l2.2 2.2M19.1 4.9l-2.2 2.2M7.1 16.9l-2.2 2.2"/>',
    'file': '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
    'folder': '<path d="M4 20a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4.5l2 3H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2Z"/>',
    'play-circle': '<circle cx="12" cy="12" r="10"/><path d="m10 8.5 6 3.5-6 3.5Z"/>',
    'play': '<path d="M6.5 4.5v15l13-7.5Z"/>',
    'pause': '<rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/>',
    'image': '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
    'plus': '<path d="M12 5v14M5 12h14"/>',
    'minus': '<path d="M5 12h14"/>',
    'zoom-in': '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3M11 8v6M8 11h6"/>',
    'zoom-out': '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3M8 11h6"/>',
    'trash': '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/>',
    'eye': '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
    'download': '<path d="M12 3v12M7 11l5 5 5-5"/><path d="M4 20h16"/>',
    'star': '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9Z"/>',
    'zap': '<path d="M13 2 4 14h7l-1 8 9-12h-7Z"/>',
    'target': '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/>',
    'flame': '<path d="M12 22a7 7 0 0 0 7-7c0-5-4-6-4-10 0 0-3 1.5-3 5 0-2-1.5-3-1.5-3S5 9 5 15a7 7 0 0 0 7 7Z"/>',
    'sparkles': '<path d="m12 3 1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8Z"/><path d="M18.5 15.5 19.4 18l2.5.9-2.5.9-.9 2.5-.9-2.5-2.5-.9 2.5-.9Z"/>',
    'calendar': '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
    'mail': '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 7 10-7"/>',
    'shield': '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>',
    'smartphone': '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/>',
    'monitor': '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>',
    'volume': '<path d="M11 5 6 9H2v6h4l5 4Z"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/><path d="M18.5 5.5a9 9 0 0 1 0 13"/>',
    'maximize': '<path d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/>',
    'fit-width': '<path d="M3 3v18M21 3v18M4 12h16M8 8l-4 4 4 4M16 8l4 4-4 4"/>',
    'skip-forward': '<path d="m5 4 10 8-10 8Z"/><path d="M19 5v14"/>',
    'list-checks': '<path d="m3 6 2 2 3-3M3 13l2 2 3-3M3 20l2 2 3-3M12 6h9M12 13h9M12 20h9"/>',
    'gauge': '<path d="M12 14 8 9"/><path d="M20.5 17a9 9 0 1 0-17 0"/>',
    'credit-card': '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
    'tag': '<path d="M20.6 13.4 12 22l-9-9V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1.2"/>',
    'refresh': '<path d="M21 12a9 9 0 1 1-2.6-6.4"/><path d="M21 3v6h-6"/>',
    'printer': '<path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 15h12v6H6Z"/>',
    'share': '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>',
    'filter': '<path d="M3 5h18l-7 8v6l-4 2v-8Z"/>',
    'more': '<circle cx="12" cy="5" r="1.4" fill="currentColor"/><circle cx="12" cy="12" r="1.4" fill="currentColor"/><circle cx="12" cy="19" r="1.4" fill="currentColor"/>',
    'bookmark': '<path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4-7 4V4a1 1 0 0 1 1-1Z"/>',
    'quote': '<path d="M9 7H5a2 2 0 0 0-2 2v4h4v4H3"/><path d="M20 7h-4a2 2 0 0 0-2 2v4h4v4h-4"/>',
    'clipboard': '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M8 11h8M8 15h5"/>',
    'grid': '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx=".5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    'rocket': '<path d="M5 13c-1.5 1.5-2 5-2 5s3.5-.5 5-2c.9-.9.9-2.3 0-3.2a2.2 2.2 0 0 0-3 .2Z"/><path d="M14.5 4.5C17 2 21 2 21 2s0 4-2.5 6.5l-4 4-4-4Z"/><path d="m10.5 9.5-3 3"/><path d="M8 12 4.5 13 3 16l3.5-.5L8 19l1-3.5 3.5-1L9 13Z"/>',
    'lock': '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
    'unlock': '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 7.5-2"/>',
    'percent': '<path d="M19 5 5 19"/><circle cx="7.5" cy="7.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/>',
    'crown': '<path d="M3 7l4 4 5-7 5 7 4-4-2 12H5Z"/>',
    'send': '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
    'megaphone': '<path d="m3 11 15-6v14L3 13Z"/><path d="M3 11v2a2 2 0 0 0 2 2h1v3a2 2 0 0 0 4 0v-3"/><path d="M18 8a3 3 0 0 1 0 8"/>',
    'timer-off': '<circle cx="12" cy="13" r="8"/><path d="m5 5 14 14"/><path d="M9 2h6"/>',
    'coffee': '<path d="M4 8h13v6a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5Z"/><path d="M17 10h1.5a2.5 2.5 0 0 1 0 5H17"/><path d="M6 2v2M10 2v2M14 2v2"/>',
    'pen-line': '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/><path d="m15 5 4 4"/>',
    'key': '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.5 12.5 8-8M17 6l2 2M14.5 8.5l2 2"/>',
    'building': '<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4M10 10h4M10 14h4M10 18h4"/>',
    'user-plus': '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>',
    'rotate-ccw': '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
    'moon': '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/>',
    'sun': '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>'
  };

  function svg(name) {
    const body = P[name] || P['help'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" ' +
      'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + body + '</svg>';
  }

  function hydrate(root) {
    (root || document).querySelectorAll('svg[data-icon]').forEach((node) => {
      const name = node.dataset.icon;
      if (!P[name]) return;
      node.removeAttribute('data-icon');
      node.setAttribute('aria-hidden', 'true');
      node.innerHTML = P[name];
      /* SVGs are replaced elements with no intrinsic size: without an
         explicit width they fall back to the 300x150 default box and blow up
         whatever they sit inside. Give every icon a scalable default and let
         component rules (larger selector specificity) override it. */
      node.classList.add('icon');
      if (!node.getAttribute('viewBox')) node.setAttribute('viewBox', '0 0 24 24');
      if (!node.getAttribute('fill')) node.setAttribute('fill', 'none');
      if (!node.getAttribute('stroke')) node.setAttribute('stroke', 'currentColor');
      if (!node.getAttribute('stroke-width')) node.setAttribute('stroke-width', '2.5');
      if (!node.getAttribute('stroke-linecap')) node.setAttribute('stroke-linecap', 'round');
      if (!node.getAttribute('stroke-linejoin')) node.setAttribute('stroke-linejoin', 'round');
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => hydrate());
  else hydrate();

  window.LIcons = { hydrate: hydrate, path: (n) => P[n] || '', svg: svg, names: Object.keys(P) };
})();
