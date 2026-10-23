<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
@isset($metaDescription)
<meta name="description" content="{{ $metaDescription }}">
@endisset
<title>@yield('title', 'Ganesha Nusa — Belajar & Tryout CPNS Online')</title>
<link rel="icon" type="image/png" href="{{ asset('images/ganesha-nusa-logo.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/ganesha-nusa-logo.png') }}">
<script>
    (function () {
        try {
            var theme = localStorage.getItem('theme');
            if (theme === 'light' || theme === 'dark') {
                document.documentElement.dataset.theme = theme;
            }
        } catch (e) {}
    })();
</script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
@fonts
@stack('head')
</head>
<body @yield('body-attributes')>

@yield('body')

<x-whatsapp-button />
<x-legal-dialogs />

@auth
    <x-feature-help :audience="request()->routeIs('admin.*') ? 'admin' : 'member'" />
@endauth

@stack('scripts')
<x-flash />

</body>
</html>
