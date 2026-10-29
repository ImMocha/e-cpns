<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light dark">
    <title>@yield('code') — @yield('title') | Ganesha Nusa</title>
    <link rel="icon" type="image/png" href="{{ asset('images/ganesha-nusa-logo.png') }}">
    <script>
        try {
            var theme = localStorage.getItem('theme');
            if (theme === 'light' || theme === 'dark') document.documentElement.dataset.theme = theme;
        } catch (e) {}
    </script>
    <style>
        :root { color-scheme: light; --paper: #fffdf7; --surface: #fff; --ink: #16324f; --text: #2a4257; --muted: #64809a; --line: #dce8f2; --blue: #0177bc; --tint: #eff9fe; --amber: #ffc53d; }
        :root[data-theme="dark"] { color-scheme: dark; --paper: #0b1622; --surface: #13253a; --ink: #f2f8fd; --text: #dce9f4; --muted: #a9c3d9; --line: #2e4a68; --blue: #35a9f2; --tint: #122b42; }
        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) { color-scheme: dark; --paper: #0b1622; --surface: #13253a; --ink: #f2f8fd; --text: #dce9f4; --muted: #a9c3d9; --line: #2e4a68; --blue: #35a9f2; --tint: #122b42; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100svh; display: flex; flex-direction: column; background: var(--paper); color: var(--text); font: 16px/1.7 ui-sans-serif, system-ui, -apple-system, sans-serif; }
        a { color: inherit; text-decoration: none; }
        a:focus-visible { outline: 3px solid var(--blue); outline-offset: 6px; }
        .wrap { width: min(1100px, calc(100% - 48px)); margin-inline: auto; }
        .header { padding-block: 28px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .brand { display: inline-flex; align-items: center; gap: 12px; color: var(--ink); font-size: 20px; font-weight: 850; line-height: 1.3; }
        .brand img { object-fit: contain; width: 56px; height: 44px; }
        .brand small { display: block; color: var(--muted); font-size: 10px; letter-spacing: .09em; font-weight: 600; margin-top: 4px; }
        .header-label { color: var(--muted); font-size: 12px; letter-spacing: .08em; text-transform: uppercase; }
        main { flex: 1; display: grid; grid-template-columns: 1.1fr 1fr; align-items: center; gap: clamp(32px, 7vw, 100px); padding-block: 64px 90px; }
        .eyebrow { display: inline-flex; align-items: center; gap: 9px; padding: 7px 13px; background: var(--tint); border: 1px solid var(--line); border-radius: 100px; color: var(--blue); font-size: 12px; font-weight: 750; }
        .eyebrow::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
        h1 { margin: 22px 0 18px; color: var(--ink); font-size: clamp(36px, 4.5vw, 58px); font-weight: 850; line-height: 1.12; letter-spacing: -.045em; text-wrap: balance; }
        .description { max-width: 470px; margin: 0; color: var(--muted); font-size: 16px; line-height: 1.85; }
        .actions { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; margin-top: 30px; }
        .button { display: inline-flex; justify-content: center; align-items: center; gap: 12px; min-height: 48px; border: 2px solid var(--ink); padding: 10px 20px; border-radius: 12px; font-size: 14px; font-weight: 750; background: var(--surface); box-shadow: 3px 3px 0 var(--ink); }
        .button-primary { background: var(--blue); color: #fff; border-color: #16324f; box-shadow: 3px 3px 0 #16324f; }
        .button:hover { transform: translateY(-2px); }
        .note { margin-top: 24px; color: var(--muted); font-size: 12px; }
        .note a { color: var(--blue); font-weight: 700; text-decoration: underline; }
        .visual { position: relative; padding: 38px 14px; }
        .halo { position: absolute; inset: 0; background: radial-gradient(circle, var(--line) 1.5px, transparent 1.5px); background-size: 18px 18px; border-radius: 50%; }
        .window { position: relative; border: 2px solid var(--ink); border-radius: 20px; background: var(--surface); box-shadow: 8px 8px 0 var(--ink); transform: rotate(-4deg); overflow: hidden; }
        .window-top { display: flex; align-items: center; gap: 6px; border-bottom: 2px solid var(--ink); padding: 16px 18px; background: var(--tint); }
        .dot { width: 9px; height: 9px; border: 1.5px solid var(--ink); border-radius: 50%; background: #ff6b9d; }
        .dot:nth-child(2) { background: var(--amber); }
        .dot:nth-child(3) { background: #2dd4a7; }
        .window-path { margin-left: auto; color: var(--muted); font: 10px ui-monospace, monospace; }
        .window-body { display: grid; justify-items: center; gap: 10px; padding: 25px 20px 38px; }
        .code { color: var(--ink); font-size: clamp(90px, 12vw, 148px); font-weight: 900; letter-spacing: -.07em; line-height: 1.25; }
        .code-line { width: 50%; height: 8px; border-radius: 20px; background: var(--line); }
        .code-line-short { width: 32%; height: 6px; }
        .sticker { position: absolute; right: -2px; bottom: 12px; padding: 10px 18px; border: 2px solid #16324f; border-radius: 10px; background: var(--amber); color: #16324f; box-shadow: 3px 3px 0 #16324f; font-size: 12px; font-weight: 800; transform: rotate(6deg); }
        footer { border-top: 1px solid var(--line); }
        .footer-inner { padding-block: 22px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; color: var(--muted); font-size: 12px; }
        .footer-inner a { color: var(--blue); font-weight: 700; }
        @media (max-width: 700px) {
            .wrap { width: calc(100% - 40px); }
            .header { padding-block: 20px; }
            .header-label { display: none; }
            main { grid-template-columns: 1fr; gap: 32px; padding-block: 26px 56px; }
            .visual { grid-row: 1; width: min(310px, 90%); margin-inline: auto; padding-block: 26px; }
            .code { font-size: 90px; }
            .window-body { padding-block: 15px 25px; gap: 7px; }
            .window-top { padding: 12px 16px; }
            .sticker { bottom: 5px; }
            h1 { font-size: 36px; }
            .actions { gap: 12px; }
            .button { padding-inline: 16px; }
        }
    </style>
</head>
<body>
    <header class="header wrap">
        <a class="brand" href="{{ route('home') }}" aria-label="Beranda Ganesha Nusa">
            <img src="{{ asset('images/ganesha-nusa-logo-no-circle.png') }}" alt="" width="56" height="44">
            <span>Ganesha Nusa<small>PROPOSITUM &amp; EXCELLENTIA</small></span>
        </a>
        <span class="header-label">Teman perjalanan belajarmu</span>
    </header>
    <main class="wrap" id="main">
        <section aria-labelledby="page-title">
            <span class="eyebrow">@yield('label', 'Sejenak, ada kendala')</span>
            <h1 id="page-title">@yield('title')</h1>
            <p class="description">@yield('description')</p>
            <div class="actions">
                <a class="button button-primary" href="@yield('action-url', route('home'))">@yield('action', 'Kembali ke beranda') <span aria-hidden="true">&rarr;</span></a>
                <a class="button" href="{{ config('app.contact_whatsapp') }}" target="_blank" rel="noopener noreferrer">Hubungi Kami</a>
            </div>
            <p class="note">@yield('note', 'Butuh bantuan? Tim Ganesha Nusa siap membantu kamu.')</p>
        </section>
        <div class="visual" aria-hidden="true">
            <div class="halo"></div>
            <div class="window">
                <div class="window-top"><span class="dot"></span><span class="dot"></span><span class="dot"></span><span class="window-path">ganesha nusa / @yield('code')</span></div>
                <div class="window-body"><span class="code">@yield('code')</span><span class="code-line"></span><span class="code-line code-line-short"></span></div>
            </div>
            <span class="sticker">@yield('sticker', 'Semangat belajarnya tetap!')</span>
        </div>
    </main>
    <footer><div class="footer-inner wrap"><span>Ganesha Nusa &middot; Belajar &amp; Tryout CPNS Online</span><a href="mailto:{{ config('app.contact_email') }}">{{ config('app.contact_email') }}</a></div></footer>
</body>
</html>
