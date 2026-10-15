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
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='9' fill='%230191ED'/%3E%3Ctext x='16' y='23' font-size='19' font-family='sans-serif' font-weight='bold' fill='white' text-anchor='middle'%3EG%3C/text%3E%3C/svg%3E">
@vite(['resources/css/app.css', 'resources/js/app.js'])
@fonts
@stack('head')
</head>
<body @yield('body-attributes')>

@yield('body')

@auth
    <x-feature-help :audience="request()->routeIs('admin.*') ? 'admin' : 'member'" />
@endauth

@stack('scripts')
<x-flash />

</body>
</html>
