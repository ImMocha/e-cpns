@props(['testimonial'])

<figure {{ $attributes->merge(['class' => 'card flex min-w-0 flex-col gap-4']) }}>
    <span class="badge badge--mint badge--flat self-start"><svg data-icon="quote" aria-hidden="true"></svg> Cerita Peserta</span>
    <blockquote class="whitespace-pre-wrap wrap-anywhere flex-1">{{ $testimonial->content }}</blockquote>
    <figcaption class="wrap-anywhere">
        <strong>{{ $testimonial->name }}</strong>
        @if ($testimonial->institution)<p class="text-sm text-soft">{{ $testimonial->institution }}</p>@endif
    </figcaption>
</figure>
