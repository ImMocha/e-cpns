<footer class="footer">
  <div class="container container--wide">
    <div class="footer__grid">
      <div>
        <a class="logo" href="{{ route('home') }}" style="color:#fff">
          <x-logo />
        </a>
        <p class="text-sm" style="margin-top:1rem;max-width:34ch">Platform belajar dan simulasi tryout CPNS untuk peserta yang mempersiapkan diri dengan cara yang lebih terukur.</p>
        <div class="socials" style="margin-top:1.25rem">
          <a href="{{ config('app.contact_whatsapp') }}" target="_blank" rel="noopener noreferrer" aria-label="Hubungi Kami"><svg data-icon="send"></svg></a>
          <a href="mailto:{{ config('app.contact_email') }}" aria-label="Email {{ config('app.contact_email') }}"><svg data-icon="mail"></svg></a>
        </div>
      </div>
      <div>
        <h4>Belajar</h4>
        <div class="footer__links">
          <a href="{{ route('materials.index') }}">Materi Pembelajaran</a>
          <a href="{{ route('articles.index') }}">Artikel</a>
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
          <a href="mailto:{{ config('app.contact_email') }}">{{ config('app.contact_email') }}</a>
          <a href="{{ config('app.contact_whatsapp') }}" target="_blank" rel="noopener noreferrer">Hubungi Kami</a>
          <a href="{{ route('terms') }}" data-legal-open="terms" aria-haspopup="dialog" aria-controls="legal-terms">Syarat &amp; Ketentuan</a>
          <a href="{{ route('privacy') }}" data-legal-open="privacy" aria-haspopup="dialog" aria-controls="legal-privacy">Kebijakan Privasi</a>
          <a href="{{ route('admin.dashboard') }}">Admin Panel</a>
        </div>
      </div>
    </div>
    <div class="footer__bottom">
      <span>© <span data-year>2026</span> Ganesha Nusa. Hak cipta dilindungi.</span>
      <span class="text-sm">Propositum &amp; Excellentia</span>
    </div>
  </div>
</footer>
