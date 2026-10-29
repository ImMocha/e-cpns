@props(['value', 'format' => 'd M Y H:i', 'fallback' => '—'])
@php
    $date = $value === null ? null : \Carbon\CarbonImmutable::parse($value)->setTimezone('Asia/Jakarta')->locale('id');
@endphp
@if ($date)
<time {{ $attributes }} datetime="{{ $date->toIso8601String() }}" data-local-time data-time-format="{{ $format }}">{{ $date->translatedFormat($format) }}{{ str_contains($format, 'H') ? ' WIB' : '' }}</time>
@else
{{ $fallback }}
@endif
