@extends('layouts.user.main')
@section('title', 'Bank Soal — Ganesha Nusa')
@section('content')
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><span class="crumbs__current">Bank Soal</span></nav>
    <header class="page-head page-head__row"><div><span class="eyebrow">Latihan Mandiri</span><h1 style="margin-top:.5rem">Bank Soal</h1><p>Pilih soal, lalu lanjutkan dengan tombol Berikutnya tanpa kembali ke daftar.</p></div><span class="badge badge--primary badge--flat">{{ number_format($questionCount, 0, ',', '.') }} soal tersedia</span></header>
    <div class="btn-row question-section"><a class="btn btn--primary" href="{{ route('quizzes.index') }}">Latihan dengan nilai &amp; riwayat</a><a class="btn btn--ghost" href="{{ route('tryouts.index') }}">Simulasi CAT</a></div>
    <div class="question-field-pills question-section">
        <a class="pill {{ !request('section_id') ? 'is-active' : '' }}" href="{{ route('question-bank.index', request()->except('section_id', 'page')) }}">Semua bidang</a>
        @foreach ($sections as $section)
            @if ($section->available_questions_count > 0)
                <a class="pill {{ (string) request('section_id') === (string) $section->id ? 'is-active' : '' }}" href="{{ route('question-bank.index', array_merge(request()->except('page'), ['section_id' => $section->id])) }}">{{ $section->name }} <span class="text-xs">{{ $section->available_questions_count }}</span></a>
            @endif
        @endforeach
    </div>
    <form method="GET" class="card question-filter question-section">
        @if (request('section_id'))<input type="hidden" name="section_id" value="{{ request('section_id') }}">@endif
        <div class="field question-filter-search"><label class="label" for="bank-search">Cari soal</label><input class="input" id="bank-search" name="q" value="{{ request('q') }}" placeholder="Cari judul atau pertanyaan"></div>
        <div class="field"><label class="label" for="bank-level">Kesulitan</label><select class="select" id="bank-level" name="difficulty"><option value="">Semua level</option>@foreach ($difficulties as $difficulty)<option value="{{ $difficulty->value }}" @selected(request('difficulty') === $difficulty->value)>{{ $difficulty->label() }}</option>@endforeach</select></div>
        <div class="btn-row"><button class="btn btn--primary">Cari</button><a class="btn btn--ghost" href="{{ route('question-bank.index') }}">Reset</a></div>
    </form>
    @if ($questions->isNotEmpty())
        <div class="flex flex-wrap items-center justify-between gap-4 question-section">
            <p class="text-sm text-soft">{{ number_format($questions->total(), 0, ',', '.') }} soal sesuai pilihanmu · bisa dikerjakan berurutan.</p>
            <a class="btn btn--primary btn--sm" href="{{ route('question-bank.show', ['question' => $questions->first(), ...$filters]) }}">Mulai dari sini <svg data-icon="arrow-right"></svg></a>
        </div>
    @endif
    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 question-bank-grid" aria-label="Daftar soal">
        @forelse ($questions as $question)
            <article class="card question-bank-card">
                <div class="question-preview-meta"><span class="badge badge--primary badge--flat">{{ $question->section->name }}</span><span class="text-sm text-soft">{{ $question->difficulty->label() }}</span></div>
                <h2 class="card__title"><a href="{{ route('question-bank.show', ['question' => $question, ...$filters]) }}">{{ $question->title }}</a></h2>
                <p class="text-sm text-soft">{{ \Illuminate\Support\Str::limit(\App\ArticleContent::text($question->body), 160) }}</p>
                <a class="btn btn--ghost btn--sm" href="{{ route('question-bank.show', ['question' => $question, ...$filters]) }}">Jawab soal <svg data-icon="arrow-right"></svg></a>
            </article>
        @empty
            <div class="card question-bank-empty"><div class="question-empty"><svg data-icon="clipboard"></svg><strong>Belum ada soal yang sesuai</strong><p class="text-soft">Coba kata kunci atau bidang lain. Soal baru akan muncul setelah diterbitkan.</p></div></div>
        @endforelse
    </section>
    <x-pager :paginator="$questions" />
@endsection

