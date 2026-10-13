@extends('layouts.base')

@section('body')
    <a class="skip-link" href="#main">Lompat ke konten utama</a>

    @include('layouts.user.partials.navbar')

    <div class="app-shell">
      @include('layouts.user.partials.sidebar')

      <main class="app-main" id="main">
        @yield('content')

        @include('layouts.user.partials.footer')
      </main>
    </div>
@endsection
