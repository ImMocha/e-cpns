@extends('layouts.admin.main')
@section('title', $question->title.' — Pratinjau Soal')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.questions.index') }}">Soal &amp; Pembahasan</a><span class="crumbs__sep">›</span><span class="crumbs__current">Pratinjau Soal</span></nav>
<header class="page-head page-head__row"><div><span class="eyebrow">Pratinjau Admin</span><h1 style="margin-top:.5rem">{{ $question->title }}</h1><p>{{ $question->section?->name ?? 'Bidang dihapus' }} · {{ $question->difficulty->label() }} · {{ $question->status->label() }}</p></div><a class="btn btn--primary" href="{{ route('admin.questions.edit', $question) }}"><svg data-icon="pencil"></svg> Edit soal</a></header>
<div class="question-reader">
    <section class="card question-answer-card">
        @if ($question->isWeighted())<p class="exam-scoring-notice">TKP berbobot · setiap pilihan bernilai 1–5, tanpa penilaian benar atau salah.</p>@endif
        <div class="article-prose question-stem">{!! $question->safeBody() !!}</div>
        @if ($question->image_path)<img class="question-image {{ ! $question->shuffle_options ? 'exam-question-figure' : '' }}" src="{{ route('admin.questions.image', $question) }}" alt="Gambar pendukung soal">@endif
        @if ($question->video_path)<video class="question-video" src="{{ route('admin.questions.video', $question) }}" controls playsinline preload="metadata" aria-label="Video pendukung soal">Browser Anda tidak mendukung pemutar video.</video>@endif
        <div class="question-answer-options">
            @foreach ($question->options as $index => $option)
                <div class="question-answer-option {{ ! $question->isWeighted() && $index === $question->correct_option ? 'question-answer-option--correct' : '' }}">
                    <span class="question-answer-letter">{{ chr(65 + $index) }}</span><span class="question-answer-text">{{ $option }}</span>
                    @if ($question->isWeighted())<span class="badge badge--primary badge--flat">Skor {{ $question->option_scores[$index] }}/5</span>@elseif ($index === $question->correct_option)<span class="badge badge--mint badge--flat">Kunci</span>@endif
                </div>
            @endforeach
        </div>
    </section>
    <section class="card question-feedback">
        <h2 class="card__title">{{ $question->isWeighted() ? 'Skor & Pembahasan' : 'Kunci & Pembahasan' }}</h2>
        @unless ($question->isWeighted())<p class="text-sm text-soft question-section">Jawaban {{ chr(65 + $question->correct_option) }}</p>@endunless
        <div class="article-prose question-section">{!! $question->safeExplanation() !!}</div>
    </section>
</div>
@endsection
