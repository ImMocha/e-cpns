@props(['article', 'imageClass' => 'article-paper-cover', 'lazy' => false])

<figure {{ $attributes->class(['article-cover-figure']) }}>
    <img class="{{ $imageClass }}" src="{{ $article->coverUrl() }}" alt="Cover {{ $article->title }}" @if ($lazy) loading="lazy" @endif>
    @if ($article->cover_source || $article->cover_source_url)
        <figcaption class="article-image-credit">Sumber:
            @if ($article->cover_source_url)
                <a href="{{ $article->cover_source_url }}" target="_blank" rel="noopener noreferrer">{{ $article->cover_source ?: $article->cover_source_url }}</a>
            @else
                {{ $article->cover_source }}
            @endif
        </figcaption>
    @endif
</figure>
