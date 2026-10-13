@extends('layouts.base')

@section('body')
    <a class="skip-link" href="#main">Lompat ke konten utama</a>

    <x-public-nav />

    <main id="main">
        @yield('content')
    </main>

    <x-footer />
@endsection
