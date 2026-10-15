{{ config('app.name') }}

{{ $headline }}

{{ $intro }}

@foreach ($details as $label => $value)
{{ $label }}: {{ $value }}
@endforeach

{{ $actionText }}: {!! $actionUrl !!}

{{ $outro }}
@if ($preferencesUrl)

Atur preferensi email: {!! $preferencesUrl !!}
@endif
