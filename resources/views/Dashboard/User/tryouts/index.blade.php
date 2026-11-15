@extends('layouts.user.main')
@section('title', 'Tryout CAT — Ganesha Nusa')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span class="crumbs__sep">›</span><span class="crumbs__current">Tryout CAT</span></nav>
<x-exam-catalog :banks="$banks" :kind="$kind" :completed-count="$completedCount" :token-count="$tokenCount" />
<dialog class="exam-dialog tryout-ready-dialog" id="tryout-ready" aria-labelledby="tryout-ready-title" aria-describedby="tryout-ready-reminder">
    <header class="exam-dialog__head">
        <div>
            <h2 class="text-xl font-bold" id="tryout-ready-title">Siap mulai tryout?</h2>
            <p class="text-sm text-soft wrap-anywhere" data-tryout-ready-name></p>
        </div>
    </header>
    <div class="exam-dialog__body grid gap-4">
        <dl class="exam-metrics tryout-ready-metrics">
            <div><dt class="text-xs text-soft">Jumlah soal</dt><dd><strong data-tryout-ready-count></strong></dd></div>
            <div><dt class="text-xs text-soft">Waktu pengerjaan</dt><dd><strong data-tryout-ready-duration></strong></dd></div>
        </dl>
        <p class="text-sm" id="tryout-ready-reminder">Kerjakan secara mandiri tanpa bantuan atau melihat kunci jawaban. Dengan begitu, hasil simulasi bisa menunjukkan kemampuanmu dan bagian yang masih perlu dilatih.</p>
        <p class="text-sm text-soft" data-tryout-ready-timer>Timer mulai berjalan setelah kamu menekan <strong>Mulai sekarang</strong> dan tetap berjalan jika kamu keluar dari sesi.</p>
        <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
            <button class="btn btn--ghost btn--sm" type="button" data-tryout-ready-cancel autofocus>Nanti dulu</button>
            <button class="btn btn--primary btn--sm" type="submit" data-tryout-ready-confirm>Mulai sekarang</button>
        </div>
    </div>
</dialog>
@endsection
