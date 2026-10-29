@props(['title', 'description'])
<section {{ $attributes->class(['card grid gap-4 content-start']) }} aria-label="Konten terkunci">
    <div><span class="badge badge--amber badge--flat"><svg data-icon="lock" aria-hidden="true"></svg> Khusus Premium</span></div>
    <div><h2 class="card__title">{{ $title }}</h2><p class="text-sm text-soft" style="margin-top:.5rem">{{ $description }}</p></div>
    {{ $slot }}
    <div><a class="btn btn--primary btn--sm" href="{{ route('packages.index') }}">Lihat paket Premium <svg data-icon="arrow-right" aria-hidden="true"></svg></a></div>
</section>
