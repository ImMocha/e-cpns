@extends('layouts.base')

@section('body')
    <a class="skip-link" href="#main">Lompat ke konten utama</a>

    @include('layouts.admin.partials.navbar')

    <div class="app-shell">
      @include('layouts.admin.partials.sidebar')

      <main class="app-main" id="main">
        @yield('content')

        @include('layouts.admin.partials.footer')
      </main>
    </div>

    @yield('modals')
@endsection
