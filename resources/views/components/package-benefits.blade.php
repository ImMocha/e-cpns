@props(['package'])
<div {{ $attributes->class(['grid content-start gap-4']) }}>
<ul class="price__list">
    @foreach ($package->tier->benefits() as $benefit)
        <li><span class="price__check"><svg data-icon="check" aria-hidden="true"></svg></span> {{ $benefit }}</li>
    @endforeach
</ul>
@if ($package->tier !== \App\Enums\PackageTier::Premium)
    <p class="text-sm text-soft">Tidak termasuk akses materi dan latihan soal.</p>
@endif
</div>
