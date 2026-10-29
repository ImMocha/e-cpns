@extends('layouts.admin.main')
@section('title', 'Periksa Klaim Gratis — Admin Ganesha Nusa')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.exam-claims.index') }}">Unlock Sesi Gratis</a><span class="crumbs__sep">›</span><span class="crumbs__current">Pengajuan #{{ $claim->id }}</span></nav>
<header class="page-head"><span class="eyebrow">Verifikasi Bukti</span><h1 style="margin-top:.5rem">{{ $claim->exam_title }}</h1><p>{{ $claim->user?->name ?? 'Akun dihapus' }} · {{ $claim->user?->email }}</p></header>
@if ($errors->any())<div class="article-errors question-section" role="alert"><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
<div class="exam-admin-layout">
    <section class="card question-form-stack">
        <h2 class="card__title">Bukti peserta</h2><p>Username Instagram: <strong>{{ '@'.$claim->instagram_username }}</strong></p>
        <p class="text-sm text-soft">Diajukan <x-local-time :value="$claim->created_at" format="d M Y H:i" /></p>
        @if ($claim->customer_note)<p style="white-space:pre-line;overflow-wrap:anywhere">{{ $claim->customer_note }}</p>@endif
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">@foreach ($claim->proof_paths as $path)<a href="{{ route('exam-claims.proof', [$claim, $loop->index]) }}" target="_blank" rel="noopener"><img src="{{ route('exam-claims.proof', [$claim, $loop->index]) }}" alt="Bukti {{ $loop->iteration }} dari {{ $claim->instagram_username }}" style="width:100%;max-height:420px;object-fit:contain" loading="lazy"><span class="text-sm">Buka bukti {{ $loop->iteration }}</span></a>@endforeach</div>
    </section>
    <div class="question-form-stack">
        <section class="card question-form-stack"><h2 class="card__title">Syarat saat pengajuan</h2><a href="{{ $claim->instagram_url }}" target="_blank" rel="noopener noreferrer">{{ $claim->instagram_url }}</a><p style="white-space:pre-line;overflow-wrap:anywhere">{{ $claim->terms }}</p></section>
        <section class="card question-form-stack"><h2 class="card__title">Keputusan admin</h2><span class="badge badge--{{ $claim->status === 'approved' ? 'mint' : ($claim->status === 'rejected' ? 'coral' : 'amber') }} badge--flat">{{ match ($claim->status) { 'approved' => 'Diterima', 'rejected' => 'Ditolak', default => 'Butuh verifikasi' } }}</span>
        @if ($claim->status === 'pending')
            <p class="text-sm text-soft">Persetujuan membuka sesi ini untuk peserta secara gratis, tanpa mengaktifkan Paket Belajar.</p>
            <a class="btn btn--ghost btn--sm" href="{{ route(\App\Enums\ExamKind::from($claim->kind)->adminRoutePrefix().'.edit', $claim->exam_id) }}">Periksa pengaturan sesi</a>
            <form method="POST" action="{{ route('admin.exam-claims.update', $claim) }}" class="question-form-stack">
                @csrf @method('PUT')
                <div class="field"><label class="label" for="review-note">Catatan / alasan penolakan</label><textarea class="textarea" id="review-note" name="review_note" rows="4" maxlength="1000">{{ old('review_note') }}</textarea><p class="field__hint">Wajib diisi saat menolak. Catatan dapat dilihat peserta.</p></div>
                <div class="btn-row"><button class="btn btn--primary" name="status" value="approved">Setujui &amp; unlock sesi</button><button class="btn btn--ghost" name="status" value="rejected">Tolak pengajuan</button></div>
            </form>
        @else
            <p class="text-sm text-soft">Diproses oleh {{ $claim->reviewer?->name ?? 'Admin' }} pada <x-local-time :value="$claim->reviewed_at" format="d M Y H:i" />.</p><p style="white-space:pre-line;overflow-wrap:anywhere">{{ $claim->review_note }}</p>
        @endif
        </section>
    </div>
</div>
<section class="card question-form-stack" style="margin-top:1.5rem" aria-labelledby="claim-history-title">
    <div><h2 class="card__title" id="claim-history-title">Riwayat pengajuan peserta</h2><p class="card__sub">Pengajuan sebelumnya dari peserta yang sama, termasuk untuk sesi lain. {{ $history->total() }} pengajuan tercatat sebelum pengajuan ini.</p></div>
    @forelse ($history as $previous)
    <article class="card card--tight question-form-stack">
        <div class="btn-row flex-wrap"><strong>Pengajuan #{{ $previous->id }} · {{ $previous->exam_title }}</strong><span class="badge badge--{{ $previous->status === 'approved' ? 'mint' : ($previous->status === 'rejected' ? 'coral' : 'amber') }} badge--flat">{{ match ($previous->status) { 'approved' => 'Diterima', 'rejected' => 'Ditolak', default => 'Butuh verifikasi' } }}</span></div>
        <p class="text-sm text-soft">{{ \App\Enums\ExamKind::from($previous->kind)->label() }} · Instagram: {{ '@'.$previous->instagram_username }} · Diajukan <x-local-time :value="$previous->created_at" format="d M Y H:i" /></p>
        @if ($previous->customer_note)<p class="text-sm" style="white-space:pre-line;overflow-wrap:anywhere">Catatan peserta: {{ $previous->customer_note }}</p>@endif
        @if ($previous->reviewed_at)<p class="text-sm text-soft">Diproses oleh {{ $previous->reviewer?->name ?? 'Admin' }} pada <x-local-time :value="$previous->reviewed_at" format="d M Y H:i" />.</p>@endif
        @if ($previous->review_note)<p class="text-sm" style="white-space:pre-line;overflow-wrap:anywhere">Catatan admin: {{ $previous->review_note }}</p>@endif
        <div class="btn-row flex-wrap"><a class="btn btn--ghost btn--sm" href="{{ route('admin.exam-claims.show', $previous) }}">Detail pengajuan #{{ $previous->id }}</a>@foreach ($previous->proof_paths as $path)<a class="btn btn--ghost btn--sm" href="{{ route('exam-claims.proof', [$previous, $loop->index]) }}" target="_blank" rel="noopener">Bukti {{ $loop->iteration }}</a>@endforeach</div>
    </article>
    @empty
    <p class="text-sm text-soft">Peserta ini belum memiliki pengajuan sebelumnya.</p>
    @endforelse
    @if ($history->hasPages())<div class="exam-pager"><x-pager :paginator="$history" /></div>@endif
</section>
@endsection
