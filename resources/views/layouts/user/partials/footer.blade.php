{{--
    Footer tipis area peserta — penanda batas halaman, bukan footer marketing.
    `@media print` di base.css menyembunyikan `footer`, jadi blok ini tidak ikut
    tercetak saat sertifikat diekspor ke PDF.
--}}
<footer class="no-print" style="border-top:2px solid var(--border);margin-top:2.5rem;padding:1.25rem 0 2rem">
  <div class="flex items-center justify-between flex-wrap" style="gap:.75rem">
    <span class="text-xs text-soft">
      &copy; <span data-year>2026</span> LONTAR &middot; CPNS Online
      @if (auth()->user()) &middot; masuk sebagai <strong style="color:var(--ink)">{{ auth()->user()->name }}</strong> @endif
    </span>

    <div class="flex items-center flex-wrap" style="gap:1.25rem">
      <a class="text-xs fw-700 text-primary" href="{{ route('leaderboard.index') }}">Leaderboard</a>
      <a class="text-xs fw-700 text-primary" href="{{ route('profile.edit') }}">Profil Saya</a>
      <a class="text-xs fw-700 text-primary" href="{{ route('home') }}">Situs Publik</a>
      <span class="badge badge--flat text-xs">v0.1</span>
    </div>
  </div>
</footer>
