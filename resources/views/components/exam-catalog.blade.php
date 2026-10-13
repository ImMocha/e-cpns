@props(['banks', 'kind', 'completedCount'])
<header class="page-head page-head__row"><div><span class="eyebrow">{{ $kind === 'quiz' ? 'Latihan Soal' : 'Simulasi Ujian' }}</span><h1 style="margin-top:.5rem">{{ $kind === 'quiz' ? 'Daftar Latihan' : 'Tryout CAT' }}</h1><p>Sesi dari bank soal admin. Jawaban tersimpan di server dan pembahasan tersedia setelah selesai.</p></div><span class="badge badge--primary badge--flat">{{ $completedCount }} sesi selesai</span></header>
@if ($errors->any())<div class="article-errors" role="alert">@foreach ($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>@endif
<div class="btn-row question-section"><a class="btn btn--ghost btn--sm" href="{{ route('question-bank.index') }}">Bank Soal</a><a class="btn btn--ghost btn--sm" href="{{ route('history.index') }}">Riwayat &amp; sesi berjalan</a></div>
<section class="grid grid-cols-1 lg:grid-cols-2 exam-catalog">
@forelse ($banks as $item)
@php($exam = $item['exam'])
<article class="card question-form-stack">
<div><span class="badge badge--{{ $kind === 'quiz' ? 'primary' : 'amber' }} badge--flat">{{ $kind === 'quiz' ? 'Latihan' : 'Simulasi CAT' }}</span><h2 class="card__title exam-card-title">{{ $exam->title }}</h2><p class="text-sm text-soft">{{ $exam->description }}</p></div>
<div class="exam-metrics"><div><span>Jumlah soal</span><strong>{{ $exam->question_count }}</strong></div><div><span>Durasi</span><strong>{{ $exam->duration_minutes ? $exam->duration_minutes.' menit' : 'Tanpa timer' }}</strong></div><div><span>Skor maksimal</span><strong>{{ $exam->maximumScore() }}</strong></div></div>
<p class="text-xs text-soft">{{ $exam->random_questions ? 'Soal diacak' : 'Urutan soal tetap' }} · {{ $exam->random_options ? 'Pilihan diacak' : 'Urutan pilihan tetap' }} · Autosave</p>
@if ($exam->passing_score !== null)<p class="text-sm text-soft">Nilai ambang: {{ $exam->passing_score }}</p>@endif
@if ($item['last'])<p class="text-sm text-soft">Nilai terakhir: <strong>{{ $item['last']->score }} / {{ $item['last']->max_score }}</strong></p>@endif
@if ($item['open'])<p class="text-sm text-soft">Sesi berjalan: {{ count($item['open']->answers ?? []) }} soal sudah dijawab.{{ $item['open']->expires_at ? ' Timer tetap berjalan saat keluar.' : '' }}</p>@endif
<div class="btn-row">
@if ($item['open'])<a class="btn btn--primary" href="{{ $item['open']->attemptUrl() }}">Lanjutkan</a>
@elseif ($exam->available_count >= $exam->question_count)<form method="POST" action="{{ route($kind === 'quiz' ? 'quizzes.start' : 'tryouts.start', $exam->slug) }}">@csrf<button class="btn btn--primary">Mulai {{ $kind === 'quiz' ? 'latihan' : 'tryout' }} <svg data-icon="arrow-right"></svg></button></form>
@else<span class="badge badge--amber badge--flat">Belum cukup soal terbit</span>@endif
@if ($item['last'])<a class="btn btn--ghost" href="{{ $item['last']->resultUrl() }}">Hasil terakhir</a>@endif
</div>
</article>
@empty
<section class="card exam-empty"><h2 class="card__title">Belum ada sesi terbit</h2><p class="text-soft exam-card-title">Sambil menunggu sesi tersedia, kamu bisa belajar melalui bank soal.</p><a class="btn btn--primary exam-card-title" href="{{ route('question-bank.index') }}">Buka bank soal</a></section>
@endforelse
</section>
