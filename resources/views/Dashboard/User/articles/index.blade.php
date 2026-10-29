@extends(auth()->check() ? 'layouts.user.main' : 'layouts.public')
@section('title', 'Artikel — Ganesha Nusa')
@section('content')
<div class="article-page">
    <header class="page-head"><span class="eyebrow">Wawasan & pembelajaran</span><h1 style="margin-top:.5rem">Artikel</h1><p>Baca rangkuman, panduan, dan tips untuk menemani persiapan CPNS.</p></header>
    <form method="GET" class="card flex flex-wrap items-end gap-4 mb-6">
        <div class="field flex-1 min-w-48"><label class="label" for="article-search">Cari artikel</label><input class="input" id="article-search" name="q" value="{{ request('q') }}" placeholder="Judul atau ringkasan"></div>
        <div class="field"><label class="label" for="article-field">Bidang</label><select class="select" id="article-field" name="section_id"><option value="">Semua bidang</option>@foreach ($sections as $section)<option value="{{ $section->id }}" @selected((string) request('section_id') === (string) $section->id)>{{ $section->name }}</option>@endforeach</select></div>
        <button class="btn btn--primary">Cari</button><a class="btn btn--ghost" href="{{ route('articles.index') }}">Reset</a>
    </form>
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($articles as $article)
            <article class="card article-card">
                @if ($article->coverUrl())<x-article-cover :article="$article" image-class="article-card-cover" :lazy="true" />@else<div class="article-card-cover article-card-cover--empty"><svg data-icon="quote"></svg></div>@endif
                <span class="badge {{ $article->section->resolvedTone()->badgeClass() }}">{{ $article->section->name }}</span>
                <h2><a href="{{ route('articles.show', $article) }}">{{ $article->title }}</a></h2>
                <p class="text-soft">{{ $article->summary() }}</p>
                <div class="article-card-meta"><span><x-local-time :value="$article->published_at" format="d M Y" /></span><span>{{ $article->readingMinutes() }} menit baca</span></div>
                <a class="btn btn--outline" href="{{ route('articles.show', $article) }}">Baca artikel <svg data-icon="arrow-right"></svg></a>
            </article>
        @empty
            <div class="card md:col-span-2 xl:col-span-3"><h2>Belum ada artikel</h2><p class="text-soft">Artikel yang sesuai pencarian akan muncul di sini setelah diterbitkan.</p></div>
        @endforelse
    </div>
    <x-pager :paginator="$articles" />
</div>
@endsection
