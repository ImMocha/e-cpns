@props(['package'])
@if ($package->hasDiscount())
    <div {{ $attributes->class(['text-sm']) }}><span class="sr-only">Harga sebelum diskon:</span> <s>{{ $package->formattedOriginalPrice() }}</s></div>
@endif
