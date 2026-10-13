@php
    $links = [
        ['label' => 'Fitur', 'href' => route('home').'#fitur'],
        ['label' => 'Materi', 'href' => route('home').'#materi'],
        ['label' => 'Artikel', 'href' => route('home').'#artikel'],
        ['label' => 'Tryout', 'href' => route('home').'#tryout'],
        ['label' => 'Paket', 'href' => route('home').'#paket'],
        ['label' => 'FAQ', 'href' => route('home').'#faq'],
    ];
@endphp

<header class="nav">
  <div class="container container--wide nav__inner">
    <a class="logo" href="{{ route('home') }}">
      <x-logo />
    </a>

    <nav class="nav__links" aria-label="Navigasi utama">
      @foreach ($links as $link)
        <a class="nav__link" href="{{ $link['href'] }}">{{ $link['label'] }}</a>
      @endforeach
    </nav>

    <div class="nav__actions">
      <a class="btn btn--ghost btn--sm" href="{{ route('login') }}">Masuk</a>
      <a class="btn btn--primary btn--sm" href="{{ route('register') }}">Daftar Gratis</a>
    </div>

    <button class="nav__toggle" data-nav-toggle="navMobile" aria-expanded="false" aria-controls="navMobile" aria-label="Buka menu">
      <svg data-icon="menu"></svg>
    </button>
  </div>

  <div class="nav__mobile" id="navMobile">
    @foreach ($links as $link)
      <a class="nav__link" href="{{ $link['href'] }}">{{ $link['label'] === 'Paket' ? 'Paket Belajar' : $link['label'] }}</a>
    @endforeach
    <a class="btn btn--ghost" href="{{ route('login') }}">Masuk</a>
    <a class="btn btn--primary" href="{{ route('register') }}">Daftar Gratis</a>
  </div>
</header>
