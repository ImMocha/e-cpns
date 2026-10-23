{{ config('app.name') }}

{{ $headline }}

{{ $intro }}

@foreach ($details as $label => $value)
{{ $label }}: {{ $value }}
@endforeach

{{ $actionText }}: {!! $actionUrl !!}

{{ $outro }}

Butuh bantuan? Hubungi Kami: {!! config('app.contact_whatsapp') !!}
Email: {{ config('app.contact_email') }}
@if ($preferencesUrl)

Atur preferensi email: {!! $preferencesUrl !!}
@endif
