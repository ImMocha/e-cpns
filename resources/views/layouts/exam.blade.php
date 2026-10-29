@extends('layouts.base')
@section('typography-scope', 'member')

@section('body-attributes')
    data-attempt="{{ $attempt->id }}"
@endsection

@section('body')
    <a class="skip-link" href="#main">Lompat ke konten utama</a>

    @yield('exam-bar')

    @yield('content')
@endsection
