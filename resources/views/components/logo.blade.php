@props(['sub' => 'Propositum & Excellentia', 'compact' => false])

<span class="logo__mark"><img src="{{ asset('images/ganesha-nusa-logo.png') }}" alt="Logo Ganesha Nusa" width="52" height="52" decoding="async"></span>
@unless ($compact)
<span class="logo__text">Ganesha Nusa<span class="logo__sub">{{ $sub }}</span></span>
@endunless
