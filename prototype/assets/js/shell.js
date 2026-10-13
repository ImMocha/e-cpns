/* ==========================================================================
   LONTAR · shell.js
   Renders the shared app chrome (appbar + sidebar) from one nav config so the
   prototype doesn't repeat ~80 lines of markup on every page.
   Pages opt in with:
     <body data-shell="member" data-base="" data-active="dashboard">
     <div data-shell-appbar></div>
     <div data-shell-side></div>
   ========================================================================== */
(function () {
  'use strict';

  const I = (n) => '<svg data-icon="' + n + '"></svg>';

  const NAV = {
    member: {
      role: 'Peserta',
      user: { name: 'Rani Kusuma', email: 'rani.kusuma@mail.com', initials: 'RK', tone: 'pink' },
      groups: [
        {
          title: 'Beranda',
          items: [{ label: 'Dashboard', href: 'dashboard.html', icon: 'dashboard', key: 'dashboard' }]
        },
        {
          title: 'Belajar',
          items: [
            { label: 'Materi', href: 'materi.html', icon: 'book', key: 'materi' },
            { label: 'Bank Soal', href: 'bank-soal.html', icon: 'layers', key: 'bank-soal' },
            { label: 'Latihan (Quiz)', href: 'quiz.html', icon: 'pen-line', key: 'quiz', pill: '3' }
          ]
        },
        {
          title: 'Simulasi CAT',
          items: [
            { label: 'Tryout CAT', href: 'tryout.html', icon: 'timer', key: 'tryout', pill: 'Baru' },
            { label: 'Riwayat', href: 'riwayat.html', icon: 'history', key: 'riwayat' },
            { label: 'Statistik', href: 'statistik.html', icon: 'chart', key: 'statistik' },
            { label: 'Leaderboard', href: 'leaderboard.html', icon: 'trophy', key: 'leaderboard' }
          ]
        },
        {
          title: 'Premium',
          items: [
            { label: 'Paket Belajar', href: 'paket.html', icon: 'package', key: 'paket' },
            { label: 'Sertifikat', href: 'sertifikat.html', icon: 'award', key: 'sertifikat' }
          ]
        },
        {
          title: 'Akun',
          items: [
            { label: 'Profil Saya', href: 'profil.html', icon: 'user', key: 'profil' },
            { label: 'Pengaturan', href: 'profil.html#pengaturan', icon: 'settings', key: 'pengaturan' }
          ]
        }
      ]
    },

    admin: {
      role: 'Administrator',
      user: { name: 'Bagas Pratama', email: 'admin@lontar.asia', initials: 'BP', tone: 'mint' },
      backLink: { label: 'Kembali ke situs', href: '../index.html' },
      groups: [
        {
          title: 'Ringkasan',
          items: [{ label: 'Admin Dashboard', href: 'index.html', icon: 'dashboard', key: 'dashboard' }]
        },
        {
          title: 'Konten',
          items: [
            { label: 'Materi & Kategori', href: 'materi.html', icon: 'folder', key: 'materi' },
            { label: 'Soal & Pembahasan', href: 'soal.html', icon: 'layers', key: 'soal', pill: '12' },
            { label: 'Artikel', href: 'artikel.html', icon: 'quote', key: 'artikel' }
          ]
        },
        {
          title: 'Pengguna',
          items: [{ label: 'Peserta', href: 'peserta.html', icon: 'users', key: 'peserta' }]
        },
        {
          title: 'Bisnis',
          items: [
            { label: 'Paket Belajar', href: 'paket.html', icon: 'package', key: 'paket' },
            { label: 'Transaksi', href: 'transaksi.html', icon: 'credit-card', key: 'transaksi' },
            { label: 'Voucher', href: 'voucher.html', icon: 'tag', key: 'voucher' }
          ]
        }
      ]
    }
  };

  function buildAppbar(cfg, base, active) {
    const u = cfg.user;
    return (
      '<header class="appbar">' +
        '<div class="container container--wide appbar__inner">' +
          '<button class="appbar__icon drawer-toggle" data-drawer-toggle aria-label="Buka menu">' + I('menu') + '</button>' +
          '<a class="logo" href="' + base + 'index.html">' +
            '<span class="logo__mark">' + I('book') + '</span>' +
            '<span>LONTAR<span class="logo__sub">' + (cfg.role === 'Admin' ? 'Admin Panel' : 'CPNS Online') + '</span></span>' +
          '</a>' +

          '<div class="appbar__search">' +
            I('search') +
            '<input class="input" type="search" placeholder="' + (cfg.role === 'Admin' ? 'Cari soal, materi, peserta…' : 'Cari materi, soal, tryout…') + '" aria-label="Pencarian">' +
          '</div>' +

          '<div class="appbar__right">' +
            (cfg.backLink
              ? '<a class="btn btn--ghost btn--sm" href="' + base + cfg.backLink.href + '">' + I('external') + '<span class="text-sm">Situs</span></a>'
              : '') +
            '<a class="appbar__icon" href="' + base + 'dashboard.html" aria-label="Notifikasi">' + I('bell') + '<span class="appbar__dot">3</span></a>' +

            '<div class="dropdown" data-dropdown>' +
              '<button class="appbar__icon" data-dropdown-toggle aria-expanded="false" aria-label="Menu akun" style="box-shadow:none;border-color:transparent;background:transparent">' +
                '<span class="avatar avatar--sm avatar--' + u.tone + '" style="box-shadow:2px 2px 0 var(--ink)">' + u.initials + '</span>' +
              '</button>' +
              '<div class="dropdown__menu">' +
                '<div class="dropdown__head">' +
                  '<strong>' + u.name + '</strong>' +
                  '<span>' + u.email + '</span>' +
                '</div>' +
                '<a class="side__link" href="' + base + 'profil.html">' + I('user') + 'Profil Saya</a>' +
                '<a class="side__link" href="' + base + 'profil.html#pengaturan">' + I('settings') + 'Pengaturan</a>' +
                '<a class="side__link" href="' + base + 'index.html">' + I('logout') + 'Keluar</a>' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</header>'
    );
  }

  function buildSide(cfg, base, active) {
    const groups = cfg.groups.map((g) =>
      '<div class="side__group">' +
        '<div class="side__title">' + g.title + '</div>' +
        g.items.map((it) =>
          '<a class="side__link' + (it.key === active ? ' is-active' : '') + '" href="' + base + it.href + '"' +
            (it.key === active ? ' aria-current="page"' : '') + '>' +
            I(it.icon) + '<span>' + it.label + '</span>' +
            (it.pill ? '<span class="side__pill">' + it.pill + '</span>' : '') +
          '</a>'
        ).join('') +
      '</div>'
    ).join('');

    return (
      '<div class="side__backdrop"></div>' +
      '<aside class="side" aria-label="Menu utama">' +
        '<div class="flex items-center justify-between" style="margin-bottom:1.25rem">' +
          '<span class="badge badge--ink badge--flat">' + cfg.role + '</span>' +
          '<button class="drawer-close appbar__icon" data-drawer-close aria-label="Tutup menu" style="width:38px;height:38px">' + I('close') + '</button>' +
        '</div>' +
        groups +
        '<div class="side__group">' +
          '<div class="card card--dashed" style="padding:1rem">' +
            '<div class="flex items-center gap-2" style="margin-bottom:.4rem">' +
              '<span class="tile__glyph" style="width:34px;height:34px;border-radius:10px"><svg data-icon="megaphone" width="17" height="17"></svg></span>' +
              '<strong style="font-family:var(--font-display);font-size:var(--fs-sm);color:var(--ink)">Butuh bantuan?</strong>' +
            '</div>' +
            '<p class="text-xs text-soft">Tim kami siap bantu meatballs soal materi atau paket.</p>' +
          '</div>' +
        '</div>' +
      '</aside>'
    );
  }

  function boot() {
    const kind = document.body.dataset.shell;
    if (!kind) return;
    const cfg = NAV[kind];
    if (!cfg) return;

    const base = document.body.dataset.base || '';
    const active = document.body.dataset.active || '';

    const bar = document.querySelector('[data-shell-appbar]');
    if (bar) bar.innerHTML = buildAppbar(cfg, base, active);

    const side = document.querySelector('[data-shell-side]');
    if (side) side.innerHTML = buildSide(cfg, base, active);

    /* Ikon pada appbar dan sidebar dibuat setelah icons.js berjalan,
       jadi harus di-hydrate ulang di sini. */
    if (window.LIcons && window.LIcons.hydrate) window.LIcons.hydrate(document);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();

  window.LShell = { NAV: NAV };
})();
