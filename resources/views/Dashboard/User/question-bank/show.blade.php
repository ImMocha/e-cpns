@extends('layouts.user.main')
@section('title', $question->title.' — Ganesha Nusa')
@section('content')
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><a href="{{ route('question-bank.index', $filters) }}">Bank Soal</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><span class="crumbs__current">Jawab Soal</span></nav>
    <header class="page-head page-head__row"><div><span class="eyebrow">Latihan Mandiri</span><h1 style="margin-top:.5rem">{{ $question->title }}</h1><p>{{ $question->section?->name ?? 'Bidang dihapus' }} · {{ $question->difficulty->label() }}</p></div><a class="btn btn--ghost" href="{{ route('question-bank.index', $filters) }}"><svg data-icon="arrow-left"></svg> Bank Soal</a></header>
    <div class="question-reader">
        @if ($isPreview)
            <div class="alert alert--info question-section"><svg data-icon="eye"></svg><div>Preview admin · {{ $question->status->label() }} <a href="{{ route('admin.questions.edit', $question) }}" class="font-bold">Edit soal</a></div></div>
        @endif
        @if ($navigation['position'] !== null)
            <nav class="question-bank-navigation" aria-label="Navigasi bank soal">
                <div class="question-bank-navigation__progress"><strong>Soal {{ $navigation['position'] }} dari {{ $navigation['total'] }}</strong><span class="text-xs text-soft">{{ $navigation['next'] ? 'Lanjut langsung ke soal berikutnya.' : 'Ini soal terakhir dalam daftar pilihanmu.' }}</span></div>
                <div class="question-bank-navigation__actions">
                    @if ($navigation['previous'])
                        <a class="btn btn--ghost btn--sm" rel="prev" href="{{ route('question-bank.show', ['question' => $navigation['previous'], ...$filters]) }}"><svg data-icon="arrow-left"></svg> Sebelumnya</a>
                    @else
                        <button class="btn btn--ghost btn--sm" type="button" disabled><svg data-icon="arrow-left"></svg> Sebelumnya</button>
                    @endif
                    @if ($navigation['next'])
                        <a class="btn btn--primary btn--sm" rel="next" href="{{ route('question-bank.show', ['question' => $navigation['next'], ...$filters]) }}">Berikutnya <svg data-icon="arrow-right"></svg></a>
                    @else
                        <a class="btn btn--primary btn--sm" href="{{ route('question-bank.index', $filters) }}">Kembali ke daftar <svg data-icon="check"></svg></a>
                    @endif
                </div>
            </nav>
        @endif
        <section class="card question-answer-card">
            @if ($question->isWeighted())<p class="exam-scoring-notice">TKP berbobot · setiap pilihan bernilai 1–5, tanpa penilaian benar atau salah.</p>@endif
            <div class="article-prose question-stem">{!! $question->safeBody() !!}</div>
            @if ($question->image_path)<img class="question-image {{ !$question->shuffle_options ? 'exam-question-figure' : '' }}" src="{{ route('question-bank.image', $question) }}" alt="Gambar pendukung soal">@endif
            @if ($question->video_path)<video class="question-video" src="{{ route('question-bank.video', $question) }}" controls playsinline preload="metadata" aria-label="Video pendukung soal">Browser Anda tidak mendukung pemutar video.</video>@endif
            <form method="POST" action="{{ route('question-bank.answer', ['question' => $question, ...$filters]) }}">
                @csrf
                <input type="hidden" name="revision" value="{{ $question->revision() }}">
                <fieldset class="question-answer-options">
                    <legend class="label">Pilih jawabanmu</legend>
                    @foreach ($question->options as $index => $option)
                        <label class="question-answer-option {{ ! $question->isWeighted() && ($selectedOption !== null || $isPreview) && $index === $question->correct_option ? 'question-answer-option--correct' : '' }} {{ ! $question->isWeighted() && $selectedOption === $index && $index !== $question->correct_option ? 'question-answer-option--wrong' : '' }}">
                            <input type="radio" name="option" value="{{ $index }}" @checked((string) old('option', $selectedOption) !== '' && (string) old('option', $selectedOption) === (string) $index) required>
                            <span class="question-answer-letter">{{ chr(65 + $index) }}</span><span class="question-answer-text">{{ $option }}</span>
                            @if ($question->isWeighted() && ($selectedOption !== null || $isPreview))<span class="badge badge--primary badge--flat">Skor {{ $question->option_scores[$index] }}/5</span>@elseif (($selectedOption !== null || $isPreview) && $index === $question->correct_option)<span class="badge badge--mint badge--flat">Kunci</span>@endif
                        </label>
                    @endforeach
                </fieldset>
                @error('option')<p class="question-error" role="alert">{{ $message }}</p>@enderror
                @error('revision')<p class="question-error" role="alert">{{ $message }}</p>@enderror
                <div class="question-answer-footer"><p class="text-sm text-soft">{{ $canViewExplanations ? 'Kunci dan pembahasan muncul setelah jawaban dikirim.' : 'Kunci jawaban muncul setelah jawaban dikirim. Pembahasan khusus akun berbayar.' }}</p><button class="btn btn--primary"><svg data-icon="check"></svg> {{ $selectedOption !== null ? 'Coba jawaban lagi' : 'Periksa jawaban' }}</button></div>
            </form>
        </section>
        @if ($selectedOption !== null || $isPreview)
            <section class="card question-feedback" aria-live="polite">
                <div class="card__head"><div class="card__title">{{ $question->isWeighted() ? ($isPreview ? 'Skor & Pembahasan' : 'Skor jawabanmu') : ($isPreview ? 'Kunci & Pembahasan' : ($selectedOption === $question->correct_option ? 'Jawabanmu benar!' : 'Jawabanmu belum tepat')) }}</div><span class="badge badge--mint badge--flat">{{ $question->isWeighted() ? ($selectedOption !== null ? 'Skor '.$question->option_scores[$selectedOption].'/5' : 'Skor 1–5') : 'Jawaban '.chr(65 + $question->correct_option) }}</span></div>
                @if ($selectedOption !== null)<p class="text-sm text-soft question-section">Kamu memilih {{ chr(65 + $selectedOption) }}. {{ $question->isWeighted() ? 'Pilihan ini bernilai '.$question->option_scores[$selectedOption].' dari 5.' : 'Kunci jawaban: '.chr(65 + $question->correct_option).'.' }}</p>@endif
                @if ($canViewExplanations)
                    <div class="article-prose">{!! $explanationHtml ?: '<p>Pembahasan belum diisi.</p>' !!}</div>
                @else
                    <x-explanation-lock />
                @endif
                @if ($navigation['next'])
                    <div class="question-answer-footer"><p class="text-sm text-soft">{{ $canViewExplanations ? 'Sudah memahami pembahasannya?' : 'Sudah memeriksa kunci jawabannya?' }}</p><a class="btn btn--primary" href="{{ route('question-bank.show', ['question' => $navigation['next'], ...$filters]) }}">Lanjut ke soal berikutnya <svg data-icon="arrow-right"></svg></a></div>
                @elseif ($navigation['position'] !== null)
                    <div class="question-answer-footer"><p class="text-sm text-soft">Kamu sudah sampai di soal terakhir.</p><a class="btn btn--ghost" href="{{ route('question-bank.index', $filters) }}">Pilih soal lainnya <svg data-icon="arrow-right"></svg></a></div>
                @endif
            </section>
        @endif
    </div>
@endsection

