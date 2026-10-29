@extends('errors.layout')
@section('code', '503')
@section('label', 'Layanan sementara tidak tersedia')
@section('title', 'Kami sedang berbenah.')
@section('description', 'Ganesha Nusa sedang dalam pemeliharaan atau mengalami gangguan sementara. Silakan kembali beberapa saat lagi. Terima kasih sudah menunggu.')
@section('action', 'Periksa kembali')
@section('note')
    Pengelola website? <a href="{{ route('login') }}">Masuk sebagai admin</a> untuk mengelola pemeliharaan.
@endsection
@section('sticker', 'Sampai ketemu sebentar lagi!')
