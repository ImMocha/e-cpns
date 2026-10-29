@php($metaDescription = $article->summary())
@php($articleUrl = route('articles.show', $article))
@extends(auth()->check() ? 'layouts.user.main' : 'layouts.public')
@section('title', $article->title.' — Ganesha Nusa')
@section('content')
<div class="article-page" data-article-reader>
    <progress class="article-reading-progress" data-reading-progress max="100" value="0" aria-label="Progres membaca artikel" hidden></progress>
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('articles.index') }}">Artikel</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><span class="crumbs__current">{{ $article->title }}</span></nav>
    @if ($isPreview)
        <div class="card article-admin-preview"><span><strong>Pratinjau admin</strong> · {{ $article->status->label() }}{{ $article->section?->is_active ? '' : ' · bidang nonaktif atau dihapus' }}@if ($article->published_at?->isFuture()) · Dijadwalkan <x-local-time :value="$article->published_at" format="d M Y, H:i" />@endif</span><a class="btn btn--outline btn--sm" href="{{ route('admin.articles.edit', $article) }}">Edit artikel</a></div>
    @endif
    <div class="article-reading-layout">
        <aside class="article-share" aria-label="Bagikan artikel" data-article-share>
            <span class="article-side-label">Bagikan</span>
            <a class="article-share-action" href="mailto:?subject={{ rawurlencode($article->title) }}&body={{ rawurlencode($articleUrl) }}" aria-label="Bagikan artikel lewat email">
                <svg data-icon="mail"></svg><span>Email</span>
            </a>
            <button class="article-share-action" type="button" data-article-copy="{{ $articleUrl }}" aria-label="Salin tautan artikel">
                <svg data-icon="link"></svg><span>Salin link</span>
            </button>
            <span class="article-copy-status" role="status" data-article-copy-status></span>
        </aside>

        <div class="article-reading-main">
            <div class="article-reader-controls" data-reader-controls hidden>
                <label for="article-reader-size">Ukuran teks</label><select id="article-reader-size" class="select" data-reader-size><option value="16">Normal</option><option value="18">Besar</option><option value="20">Sangat besar</option></select>
                <button class="btn btn--outline btn--sm" type="button" data-article-print>Cetak / Simpan PDF</button>
            </div>
            <article class="card article-paper article-reader">
                <span class="badge badge--sky">{{ $article->section?->name ?? 'Bidang dihapus' }}</span>
                <h1 class="article-paper-title">{{ $article->title }}</h1>
                @if ($article->excerpt)<p class="article-paper-excerpt">{{ $article->excerpt }}</p>@endif
                <div class="article-paper-meta"><span>{{ $article->author?->name ?? 'Tim Ganesha Nusa' }}</span><span>·</span><span><x-local-time :value="$article->published_at" format="d M Y" fallback="Belum terbit" /></span><span>·</span><span>{{ $article->readingMinutes() }} menit baca</span></div>
                @if ($article->coverUrl())<x-article-cover :article="$article" />@endif
                <nav class="article-toc" aria-label="Daftar isi artikel" data-article-toc hidden></nav>
                <div class="article-prose" data-article-body>{!! $article->safeBody() !!}</div>
            </article>
            <div class="article-reader-back"><a class="btn btn--ghost" href="{{ route('articles.index') }}"><svg data-icon="arrow-left"></svg> Kembali ke semua artikel</a></div>
        </div>

        <aside class="article-related" aria-label="Artikel lainnya">
            <div class="card article-related-card">
                <span class="article-side-label">Lanjut membaca</span>
                <h2>Artikel lainnya</h2>
                @forelse ($relatedArticles as $relatedArticle)
                    <a class="article-related-link" href="{{ route('articles.show', $relatedArticle) }}">
                        <span class="article-related-section">{{ $relatedArticle->section->name }}</span>
                        <strong>{{ $relatedArticle->title }}</strong>
                        <span class="article-related-date"><x-local-time :value="$relatedArticle->published_at" format="d M Y" /> <svg data-icon="arrow-right"></svg></span>
                    </a>
                @empty
                    <p class="article-related-empty">Artikel berikutnya akan muncul setelah diterbitkan.</p>
                @endforelse
                <a class="article-related-more" href="{{ route('articles.index') }}">Lihat semua artikel <svg data-icon="arrow-right"></svg></a>
            </div>
        </aside>
    </div>
</div>
@endsection
