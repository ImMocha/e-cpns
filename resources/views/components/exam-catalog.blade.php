@props(['banks', 'kind', 'completedCount', 'tokenCount' => 0, 'learningLocked' => false])
@php($examKind = \App\Enums\ExamKind::from($kind))
<header class="page-head page-head__row"><div><span class="eyebrow">{{ $examKind->label() }}</span><h1 style="margin-top:.5rem">{{ $kind === 'tryout' ? 'Tryout CAT' : 'Daftar '.$examKind->label() }}</h1><p>Pilih sesi latihan atau tryout yang tersedia. Kunci jawaban tersedia setelah selesai. Pembahasan lengkap tersedia dengan paket berbayar aktif.</p></div><span class="badge badge--primary badge--flat">{{ $completedCount }} sesi selesai</span></header>
@if ($learningLocked)
<x-feature-lock title="Isi latihan soal terkunci" description="Kamu tetap bisa melihat daftar, topik, jumlah soal, dan durasi latihan. Aktifkan Premium untuk membuka soal dan mulai mengerjakan." style="margin-bottom:1.5rem" />
@endif
@if ($kind === 'tryout')<x-passing-grades style="margin-bottom:1.5rem" />@endif
<div class="exam-catalog-notices">
@if ($kind === 'tryout' && $tokenCount > 0)
<aside class="card card--tight" aria-label="Token pilihan tryout">
    <span class="badge badge--mint badge--flat">{{ $tokenCount }} token tersedia</span>
    <h2 class="card__title exam-card-title">Pilih tryout yang ingin kamu buka</h2>
    <p class="text-sm text-soft">1 token membuka 1 paket soal tryout premium pilihanmu. Jika percobaan tryout lama habis, gunakan token dari pembelian lain untuk mengisi ulang percobaannya. Setelah dikonfirmasi, pilihan tersimpan dan tidak bisa ditukar. Kuota dari pembelian yang dipilih baru terpakai saat mulai mengerjakan.</p>
</aside>
@endif
@unless ($learningLocked)
<aside class="card card--tight" aria-label="Informasi akses sesi">
    <h2 class="card__title">Perhatikan jenis akses sesi</h2>
    <p class="text-sm text-soft" style="margin-top:.5rem">Sesi <strong>Premium</strong> mengikuti akses paket peserta. Buka tryout pilihanmu dengan token Reguler/Bundling, atau gunakan paket Premium. Untuk sesi <strong>Gratis Bersyarat</strong>, ikuti syarat dan unggah bukti untuk diperiksa admin.</p>
</aside>
@endunless
</div>
@if ($errors->any())<div class="article-errors" role="alert">@foreach ($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>@endif
<div class="btn-row question-section"><a class="btn btn--ghost btn--sm" href="{{ route('history.index') }}">Riwayat &amp; sesi berjalan</a></div>
<section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 exam-catalog">
@forelse ($banks as $item)
@php($exam = $item['exam'])
<article class="card question-form-stack">
@if(in_array($kind, ['practice', 'tryout'], true))
<img class="exam-card-cover" src="{{ $exam->coverUrl() }}" alt="" width="960" height="540" loading="lazy" decoding="async">
@endif
<div>
    <div class="exam-card-badges">
        <span class="badge badge--{{ $kind === 'tryout' ? 'amber' : 'primary' }} badge--flat">{{ $examKind->label() }}</span>
        <span class="badge badge--{{ ($item['learningLocked'] ?? false) || $exam->free_claim_enabled ? 'amber' : ($exam->access_level === \App\Enums\MaterialAccess::Premium ? 'violet' : 'mint') }} badge--flat">{{ ($item['learningLocked'] ?? false) ? 'Terkunci · Premium' : $exam->accessLabel() }}</span>
        @if (! ($item['learningLocked'] ?? false) && $exam->free_claim_enabled && ($item['claim'] ?? null)?->status === 'approved')<span class="badge badge--mint badge--flat">Sudah terbuka</span>@endif
    </div>
    <h2 class="card__title exam-card-title">{{ $exam->title }}</h2>
    <p class="text-sm text-soft">{{ $exam->description }}</p>
</div>
<div class="exam-metrics"><div><span>Jumlah soal</span><strong>{{ $exam->question_count }}</strong></div><div><span>Durasi</span><strong>{{ $exam->duration_minutes ? $exam->duration_minutes.' menit' : 'Tanpa timer' }}</strong></div><div><span>Skor maksimal</span><strong>{{ $exam->maximumScore() }}</strong></div></div>
<p class="text-xs text-soft">{{ $exam->random_questions ? 'Soal diacak' : 'Urutan soal tetap' }} · {{ $exam->random_options ? 'Pengacakan pilihan mengikuti pengaturan tiap soal' : 'Urutan pilihan tetap' }} · Autosave</p>
@if ($kind !== 'tryout' && $exam->passing_score !== null)<p class="text-sm text-soft">Nilai ambang: {{ $exam->passing_score }}</p>@endif
@if ($kind === 'tryout' && ! $exam->free_claim_enabled && $exam->access_level === \App\Enums\MaterialAccess::Premium && $item['hasAccess'])<p class="text-sm text-soft">Kuota tersisa: {{ $item['remainingTryouts'] ?? 'Tanpa batas' }}</p>@endif
@if ($exam->free_claim_enabled && ! ($item['learningLocked'] ?? false))
<div class="card card--tight card--dashed">
    <strong class="text-sm">{{ ($item['claim'] ?? null)?->status === 'approved' ? 'Syarat sudah diverifikasi' : 'Gratis dengan syarat dari admin' }}</strong>
    <p class="text-sm text-soft" style="margin-top:.5rem">@if ($item['isAdminPreview'])Pratinjau admin. Peserta wajib memenuhi syarat, upload bukti, dan mendapatkan persetujuan admin sebelum sesi terbuka.@elseif (($item['claim'] ?? null)?->status === 'approved')Bukti kamu sudah disetujui. Sesi ini sudah terbuka dan bisa dikerjakan tanpa biaya.@else Baca syarat unlock, follow akun Instagram yang ditentukan, dan upload bukti sesuai instruksi. Kamu baru bisa mengerjakan sesi ini setelah admin menyetujui bukti.@endif</p>
</div>
@endif
@if ($item['last'])<p class="text-sm text-soft">Nilai terakhir: <strong>{{ $item['last']->score }} / {{ $item['last']->max_score }}</strong></p>@endif
@if ($item['open'])<p class="text-sm text-soft">Sesi berjalan: {{ count($item['open']->answers ?? []) }} soal sudah dijawab.{{ $item['open']->expires_at ? ' Timer tetap berjalan saat keluar.' : '' }}</p>@endif
@if ($exam->topic)<p class="text-sm text-soft">Topik: {{ $exam->topic->name }} · {{ $item['isAdminPreview'] ? 'Pratinjau admin: bisa dibuka tanpa menyelesaikan materi.' : ($exam->requires_topic_completion ? 'Setelah semua materi topik selesai' : 'Bisa dikerjakan kapan saja') }}</p>@endif
<x-exam-actions :item="$item" :kind="$kind" />
</article>
@empty
<section class="card exam-empty"><h2 class="card__title">Belum ada sesi terbit</h2><p class="text-soft exam-card-title">Sambil menunggu sesi tersedia, pelajari materi PDF dan video.</p><a class="btn btn--primary exam-card-title" href="{{ route('materials.index') }}">Buka materi</a></section>
@endforelse
</section>
