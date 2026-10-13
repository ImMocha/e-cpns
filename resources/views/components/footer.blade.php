<footer class="footer">
  <div class="container container--wide">
    <div class="footer__grid">
      <div>
        <a class="logo" href="{{ route('home') }}" style="color:#fff">
          <x-logo />
        </a>
        <p class="text-sm" style="margin-top:1rem;max-width:34ch">Platform belajar dan simulasi tryout CPNS untuk peserta yang mempersiapkan diri dengan cara yang lebih terukur.</p>
        <div class="socials" style="margin-top:1.25rem">
          <a href="#" aria-label="Instagram"><svg data-icon="image"></svg></a>
          <a href="#" aria-label="Telegram"><svg data-icon="send"></svg></a>
          <a href="#" aria-label="Email"><svg data-icon="mail"></svg></a>
        </div>
      </div>
      <div>
        <h4>Belajar</h4>
        <div class="footer__links">
          <a href="{{ route('materials.index') }}">Materi Pembelajaran</a>
          <a href="{{ route('articles.index') }}">Artikel</a>
          <a href="{{ route('question-bank.index') }}">Bank Soal</a>
          <a href="{{ route('quizzes.index') }}">Latihan Soal</a>
          <a href="{{ route('tryouts.index') }}">Try Out CAT</a>
        </div>
      </div>
      <div>
        <h4>Akun</h4>
        <div class="footer__links">
          <a href="{{ route('login') }}">Masuk</a>
          <a href="{{ route('register') }}">Daftar</a>
          <a href="{{ route('packages.index') }}">Paket Belajar</a>
          <a href="{{ route('certificates.index') }}">Sertifikat</a>
        </div>
      </div>
      <div>
        <h4>Informasi</h4>
        <div class="footer__links">
          <a href="{{ route('home') }}#faq">FAQ</a>
          <a href="#">Syarat &amp; Ketentuan</a>
          <a href="#">Kebijakan Privasi</a>
          <a href="{{ route('admin.dashboard') }}">Admin Panel</a>
        </div>
      </div>
    </div>
    <div class="footer__bottom">
      <span>© <span data-year>2026</span> LONTAR · Prototipe demonstrasi, bukan situs produksi.</span>
      <span class="badge badge--flat" style="background:rgba(255,255,255,.08);color:#A9C1D6;border-color:rgba(255,255,255,.2);box-shadow:none">v0.1 Prototype</span>
    </div>
  </div>
</footer>
