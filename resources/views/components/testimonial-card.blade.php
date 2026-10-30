@props(['testimonial'])

@php
    $displayName = $testimonial->name;
    $displayInstitution = $testimonial->institution;

    if (app()->environment('local')) {
        $displayName = preg_replace('/ \(Demo\)$/u', '', $displayName);
        $displayInstitution = $displayInstitution === null ? null : preg_replace('/ · Data Demo$/u', '', $displayInstitution);
    }
@endphp

<figure {{ $attributes->merge(['class' => 'card flex min-w-0 flex-col gap-4']) }}>
    <span class="badge badge--mint badge--flat self-start"><svg data-icon="quote" aria-hidden="true"></svg> Cerita Peserta</span>
    <blockquote class="whitespace-pre-wrap wrap-anywhere flex-1">{{ $testimonial->content }}</blockquote>
    <figcaption class="wrap-anywhere">
        <strong>{{ $displayName }}</strong>
        @if ($displayInstitution)<p class="text-sm text-soft">{{ $displayInstitution }}</p>@endif
    </figcaption>
</figure>
