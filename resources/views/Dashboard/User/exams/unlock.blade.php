@extends('layouts.user.main')
@section('title', 'Unlock Gratis — Ganesha Nusa')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route($catalogRoute) }}">{{ \App\Enums\ExamKind::from($exam->kind)->label() }}</a><span class="crumbs__sep">›</span><span class="crumbs__current">Unlock gratis</span></nav>
<header class="page-head"><span class="eyebrow">Gratis Bersyarat</span><h1 style="margin-top:.5rem">{{ $exam->title }}</h1><p>Penuhi syarat dari admin untuk membuka sesi ini secara gratis.</p></header>
@if ($errors->any())<div class="article-errors question-section" role="alert"><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
<div class="exam-admin-layout">
    <section class="card question-form-stack">
        <h2 class="card__title">Cara unlock sesi</h2>
        <p class="text-sm text-soft">1. Follow akun Instagram berikut.</p>
        <a class="btn btn--ghost" href="{{ $exam->free_claim_instagram_url }}" target="_blank" rel="noopener noreferrer">Buka akun Instagram <svg data-icon="arrow-right"></svg></a>
        <p class="text-sm text-soft">2. Penuhi syarat upload dan ketentuan dari admin.</p>
        <p style="white-space:pre-line;overflow-wrap:anywhere">{{ $exam->free_claim_terms }}</p>
        <p class="text-sm text-soft">3. Kirim screenshot bukti follow dan upload. Tunggu persetujuan admin, lalu kembali ke halaman {{ \App\Enums\ExamKind::from($exam->kind)->label() }} untuk mulai mengerjakan.</p>
        <div class="exam-metrics"><div><span>Jumlah soal</span><strong>{{ $exam->question_count }}</strong></div><div><span>Durasi</span><strong>{{ $exam->duration_minutes }} menit</strong></div><div><span>Biaya</span><strong>Gratis</strong></div></div>
        <p class="text-sm text-soft">Persetujuan berlaku untuk sesi ini dan tidak memotong kuota Paket Belajar.</p>
    </section>
    <section class="card question-form-stack">
        <h2 class="card__title">Bukti untuk unlock</h2>
        @if (auth()->user()->isAdmin())
            <p>Ini pratinjau admin. Peserta mengirim bukti melalui formulir pada halaman ini.</p><a class="btn btn--ghost" href="{{ route($catalogRoute) }}">Kembali ke daftar sesi</a>
        @elseif ($claims->contains('status', 'approved'))
            <span class="badge badge--mint badge--flat" role="status">Sesi sudah terbuka</span><p>Bukti kamu sudah disetujui. Kamu bisa mulai mengerjakan sesi ini.</p><a class="btn btn--primary" href="{{ route($catalogRoute) }}">Buka {{ strtolower(\App\Enums\ExamKind::from($exam->kind)->label()) }}</a>
        @elseif ($claims->contains('status', 'pending'))
            <span class="badge badge--amber badge--flat" role="status">Menunggu verifikasi admin</span><p class="text-sm text-soft">Bukti sudah terkirim. Status juga tampil pada kartu sesi di halaman {{ \App\Enums\ExamKind::from($exam->kind)->label() }}.</p><a class="btn btn--ghost" href="{{ route($catalogRoute) }}">Kembali ke daftar sesi</a>
        @elseif (! $contentAvailable)
            <p>Soal sesi sedang belum tersedia. Silakan cek kembali nanti.</p>
        @else
            @if ($claims->isNotEmpty())<p class="text-sm text-soft">Perbaiki bukti sesuai catatan admin di riwayat pengajuan, lalu kirim kembali.</p>@endif
            <form method="POST" action="{{ route('exam-claims.store', $exam) }}" enctype="multipart/form-data" class="question-form-stack">
                @csrf
                <input type="hidden" name="terms_version" value="{{ $exam->freeClaimTermsVersion() }}">
                <div class="field"><label class="label" for="instagram-username">Username Instagram kamu</label><input class="input" id="instagram-username" name="instagram_username" value="{{ old('instagram_username') }}" maxlength="30" placeholder="username tanpa @" required></div>
                <div class="field"><label class="label" for="claim-proofs">Screenshot bukti follow dan upload</label><input class="input" id="claim-proofs" type="file" name="proofs[]" accept="image/jpeg,image/png,image/webp" multiple required aria-describedby="proof-hint"><p class="field__hint" id="proof-hint">Pilih 1–5 gambar JPG, PNG, atau WebP, maksimal 4 MB per gambar. Pastikan username dan bukti terlihat jelas. Bukti hanya dapat dilihat oleh kamu dan admin.</p></div>
                <div class="field"><label class="label" for="customer-note">Catatan untuk admin (opsional)</label><textarea class="textarea" id="customer-note" name="customer_note" rows="3" maxlength="1000">{{ old('customer_note') }}</textarea></div>
                <label class="check"><input type="checkbox" name="accept_terms" value="1" required @checked(old('accept_terms'))> Saya sudah membaca dan memenuhi syarat di atas serta mengirim bukti yang benar.</label>
                <button class="btn btn--primary">Kirim bukti untuk unlock</button>
            </form>
        @endif
    </section>
</div>
@if ($claims->isNotEmpty())
<section class="card question-form-stack" style="margin-top:1.5rem"><h2 class="card__title">Riwayat pengajuan</h2>
@foreach ($claims as $claim)
<article class="card card--tight question-form-stack"><div class="btn-row"><strong>Pengajuan #{{ $claim->id }}</strong><span class="badge badge--{{ $claim->status === 'approved' ? 'mint' : 'amber' }} badge--flat">{{ $claim->statusLabel() }}</span><x-local-time :value="$claim->created_at" format="d M Y H:i" /></div>
@if ($claim->review_note)<p style="white-space:pre-line;overflow-wrap:anywhere">Catatan admin: {{ $claim->review_note }}</p>@endif
<div class="btn-row">@foreach ($claim->proof_paths as $path)<a class="btn btn--ghost btn--sm" href="{{ route('exam-claims.proof', [$claim, $loop->index]) }}" target="_blank" rel="noopener">Bukti {{ $loop->iteration }}</a>@endforeach</div>
<details><summary>Syarat saat pengajuan</summary><p style="white-space:pre-line;overflow-wrap:anywhere">{{ $claim->terms }}</p><p class="text-sm text-soft">{{ $claim->instagram_url }}</p></details></article>
@endforeach
</section>
@endif
@endsection
