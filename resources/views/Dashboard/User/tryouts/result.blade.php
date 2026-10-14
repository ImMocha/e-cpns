@extends('layouts.user.main')

@section('title', 'Hasil '.($kind === 'quiz' ? 'Latihan' : 'Tryout').' — Ganesha Nusa CPNS Online')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('dashboard') }}">Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <a href="{{ route($kind === 'quiz' ? 'quizzes.index' : 'tryouts.index') }}">{{ $kind === 'quiz' ? 'Latihan' : 'Tryout CAT' }}</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">{{ $canViewExplanations ? 'Hasil & Pembahasan' : 'Hasil & Kunci Jawaban' }}</span>
    </nav>

    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow">{{ $attempt ? 'Selesai Dikerjakan' : 'Belum Ada Hasil' }}</span>
        <h1 style="margin-top:.5rem">{{ $attempt?->title ?? 'Hasil Belum Tersedia' }}</h1>
        <p>{{ $attempt ? 'Rincian jawaban yang tersimpan pada sesi ini.' : 'Kerjakan sesi terlebih dahulu untuk melihat hasil dan kunci jawaban.' }}</p>
      </div>
      @if ($exam?->access_level === \App\Enums\MaterialAccess::Premium)
        <a class="btn btn--primary" href="{{ route($kind === 'quiz' ? 'quizzes.index' : 'tryouts.index') }}">Lihat sesi &amp; kuota</a>
      @elseif ($exam)
        <form method="POST" action="{{ route($kind === 'quiz' ? 'quizzes.start' : 'tryouts.start', $exam->slug) }}">@csrf
          <button class="btn btn--primary" type="submit">{{ $attempt ? 'Kerjakan Lagi' : 'Mulai Sekarang' }}</button>
        </form>
      @else
        <a class="btn btn--primary" href="{{ route($kind === 'quiz' ? 'quizzes.index' : 'tryouts.index') }}">Pilih sesi lain</a>
      @endif
    </header>

    @if ($attempt === null)
      <section class="card card--pop" style="text-align:center;padding:2.5rem">
        <span class="card__icon ico--primary" style="position:static;margin:0 auto 1rem"><svg data-icon="clipboard"></svg></span>
        <h2 class="card__title">Belum ada sesi selesai</h2>
        <p class="text-sm text-soft" style="margin-top:.5rem">Nilai baru akan muncul setelah jawabanmu berhasil dikumpulkan.</p>
      </section>
    @else
      <section class="card card--pop" style="margin-bottom:1.5rem">
        <div class="result-hero">
          <div class="ring" data-pct="{{ $attempt->max_score > 0 ? round($attempt->score / $attempt->max_score * 100) : 0 }}">
            <div class="ring__label">
              <div class="ring__value">{{ $attempt->score }}</div>
              <div class="ring__unit">dari {{ $attempt->max_score }}</div>
            </div>
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap" style="margin-bottom:.75rem">
              <span class="badge badge--{{ $attempt->passing_score !== null && $attempt->score >= $attempt->passing_score ? 'mint' : 'amber' }} badge--flat">
                {{ $attempt->passing_score === null ? 'Selesai' : ($attempt->score >= $attempt->passing_score ? 'Di atas ambang' : 'Di bawah ambang') }}
              </span>
              <span class="badge badge--primary badge--flat">{{ $attempt->submitted_at->translatedFormat('d M Y H:i') }}</span>
              @if ($attempt->auto_submitted)
                <span class="badge badge--amber badge--flat">Terkumpul otomatis</span>
              @endif
            </div>
            <h2>Hasil {{ $kind === 'quiz' ? 'Latihan' : 'Tryout' }} Kamu</h2>
            <p class="text-soft" style="margin-top:.4rem;max-width:56ch">Nilai dihitung dari jawaban yang tersimpan. Soal biasa: benar +{{ $attempt->question_snapshot['pointsPerCorrect'] }}, salah −{{ $attempt->question_snapshot['penaltyWrong'] }}. @if ($weightedQuestionCount)TKP berbobot: skor pilihan 1–5, tanpa penalti salah.@endif Jawaban kosong bernilai 0.
              @if (($attempt->question_snapshot['scoreMode'] ?? 'points') === 'percent') Hasil dikonversi ke skala 0–100.@endif
              @if ($attempt->passing_score !== null) Nilai ambang sesi: {{ $attempt->passing_score }}.@endif
            </p>
            <div class="grid grid-4" style="--gap:.75rem;margin-top:1.5rem">
              <div class="card card--tight card--dashed" style="text-align:center"><div class="text-xs text-soft fw-700">{{ $weightedQuestionCount ? 'Benar (soal biasa)' : 'Benar' }}</div><div class="fw-800" style="font-family:var(--font-display);font-size:1.5rem;color:var(--success)">{{ $attempt->correct_count }}</div></div>
              <div class="card card--tight card--dashed" style="text-align:center"><div class="text-xs text-soft fw-700">{{ $weightedQuestionCount ? 'Salah (soal biasa)' : 'Salah' }}</div><div class="fw-800" style="font-family:var(--font-display);font-size:1.5rem;color:var(--danger)">{{ $attempt->wrong_count }}</div></div>
              <div class="card card--tight card--dashed" style="text-align:center"><div class="text-xs text-soft fw-700">Kosong</div><div class="fw-800" style="font-family:var(--font-display);font-size:1.5rem;color:var(--text-soft)">{{ $attempt->blank_count }}</div></div>
              <div class="card card--tight card--dashed" style="text-align:center"><div class="text-xs text-soft fw-700">{{ $weightedQuestionCount ? 'TKP berbobot dijawab' : 'Akurasi' }}</div><div class="fw-800" style="font-family:var(--font-display);font-size:1.5rem;color:var(--primary-700)">{{ $weightedQuestionCount ? $attempt->weighted_answered_count : ($attempt->correct_count + $attempt->wrong_count > 0 ? round($attempt->correct_count / ($attempt->correct_count + $attempt->wrong_count) * 100).'%' : '0%') }}</div></div>
            </div>
          </div>
        </div>
      </section>

      <div class="panes" style="--gap:1.5rem;margin-bottom:1.5rem">
        <section class="card">
          <div class="card__head"><div><div class="card__title">Nilai per Bagian</div><div class="card__sub">Berdasarkan jawaban pada sesi ini</div></div></div>
          <div class="grid" style="--gap:1rem">
            @foreach ($sections as $section)
              <div><div class="scorebar"><strong>{{ $section['section'] }}</strong><div class="scorebar__track"><div class="scorebar__fill" data-pct="{{ $section['max'] > 0 ? round($section['score'] / $section['max'] * 100) : 0 }}"></div></div><span class="scorebar__val">{{ $section['score'] }} / {{ $section['max'] }}</span></div><p class="text-xs text-soft" style="margin-top:.35rem">@if ($section['weighted_total'] < $section['total']){{ $section['correct'] }} benar, {{ $section['wrong'] }} salah, @endif @if ($section['weighted_total']){{ $section['weighted'] }} soal berbobot dijawab, @endif{{ $section['blank'] }} kosong</p></div>
            @endforeach
          </div>
        </section>
        <section class="card">
          <div class="card__head"><div><div class="card__title">Komposisi Jawaban</div><div class="card__sub">Benar (hijau), salah (oranye), berbobot dijawab (biru), kosong (abu-abu)</div></div></div>
          <div style="width:180px;height:180px;margin:0 auto 1rem" data-chart='@json($compositionChart)'></div>
          <p class="text-sm text-soft" style="text-align:center">Durasi pengerjaan: {{ floor($attempt->duration_seconds / 60) }} menit {{ $attempt->duration_seconds % 60 }} detik</p>
        </section>
      </div>

      @if (count($trendChart['values']) > 1)
        <section class="card" style="margin-bottom:1.5rem">
          <div class="card__head"><div><div class="card__title">Perkembangan Nilai</div><div class="card__sub">{{ count($trendChart['values']) }} sesi terakhir dari bank soal ini</div></div></div>
          <div style="height:230px" data-chart='@json($trendChart)'></div>
        </section>
      @endif

      <section class="card">
        <div class="card__head"><div><div class="card__title">{{ $canViewExplanations ? 'Pembahasan Soal' : 'Kunci Jawaban' }}</div><div class="card__sub">{{ $canViewExplanations ? 'Jawaban dan penjelasan dari sesi yang tersimpan' : 'Jawaban benar dan pilihanmu dari sesi yang tersimpan' }}</div></div></div>
        <div class="stack" style="--stack:.8rem">
          @unless ($canViewExplanations)
            <x-explanation-lock />
          @endunless
          @foreach ($reviewQuestions as $index => $question)
            @php($chosen = $attempt->answers[(string) $question['id']] ?? null)
            @php($weighted = ($question['scoringType'] ?? 'binary') === 'weighted')
            <details class="card card--tight card--dashed">
              <summary style="cursor:pointer;font-weight:700;color:var(--ink)">
                {{ $index + 1 }}. {{ $question['topic'] ?? 'Soal' }}
                <span class="badge badge--{{ $chosen === null ? 'amber' : ($weighted ? 'primary' : ($chosen === $question['answer'] ? 'mint' : 'coral')) }} badge--flat" style="margin-left:.5rem">{{ $chosen === null ? 'Kosong' : ($weighted ? 'Skor '.$question['optionScores'][$chosen].'/5' : ($chosen === $question['answer'] ? 'Benar' : 'Salah')) }}</span>
              </summary>
              <div class="text-sm" style="margin-top:1rem">
                <div class="article-prose">@if (!empty($question['stemHtml'])){!! \App\ArticleContent::sanitize($question['stemHtml']) !!}@else{{ $question['stem'] }}@endif</div>
                @foreach (['image', 'video'] as $media)
                  @if (!empty($question[$media.'_path']))
                    @php($mediaUrl = route('exam-attempts.media', ['examAttempt' => $attempt->id, 'question' => $question['id'], 'media' => $media]))
                    @if ($media === 'image')<img class="exam-question-media {{ !empty($question['fixedOptionOrder']) ? 'exam-question-figure' : '' }}" src="{{ $mediaUrl }}" alt="Ilustrasi soal">
                    @else<video class="exam-question-media" src="{{ $mediaUrl }}" controls playsinline preload="metadata"></video>@endif
                  @endif
                @endforeach
                <ol class="exam-review-options" type="A">
                  @foreach ($question['options'] as $optionIndex => $option)
                    <li class="exam-review-choice {{ $weighted ? ($chosen === $optionIndex ? 'is-selected' : '') : ($optionIndex === $question['answer'] ? 'is-correct' : ($chosen === $optionIndex ? 'is-wrong' : '')) }}">{{ $option }}
                      @if ($weighted)<strong> · Skor {{ $question['optionScores'][$optionIndex] }}/5</strong>@elseif ($optionIndex === $question['answer'])<strong> · Jawaban benar</strong>@endif
                      @if ($chosen === $optionIndex)<span> · Pilihan kamu</span>@endif
                    </li>
                  @endforeach
                </ol>
                <p><strong>Jawaban kamu:</strong> {{ $chosen === null ? 'Tidak dijawab' : ($question['options'][$chosen] ?? 'Pilihan tidak tersedia') }}</p>
                @if ($weighted)<p style="margin-top:.4rem"><strong>Skor yang diperoleh:</strong> {{ $chosen === null ? 0 : $question['optionScores'][$chosen] }} dari 5. Setiap pilihan memiliki skor; tidak ada jawaban benar atau salah.</p>
                @else<p style="margin-top:.4rem"><strong>Jawaban benar:</strong> {{ $question['options'][$question['answer']] }}</p>@endif
                @if ($canViewExplanations)
                  <div class="article-prose text-soft" style="margin-top:.65rem">@if (!empty($question['explainHtml'])){!! \App\ArticleContent::sanitize($question['explainHtml']) !!}@else{{ $question['explain'] ?? '' }}@endif</div>
                @endif
              </div>
            </details>
          @endforeach
        </div>
      </section>
    @endif
@endsection
