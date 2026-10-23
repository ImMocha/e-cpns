@foreach (['terms' => 'Syarat & Ketentuan', 'privacy' => 'Kebijakan Privasi'] as $document => $title)
<dialog class="legal-dialog no-print" id="legal-{{ $document }}" data-legal-document="{{ $document }}" aria-labelledby="legal-{{ $document }}-title" aria-describedby="legal-{{ $document }}-summary">
    <header class="legal-dialog__head">
        <div><span class="eyebrow">Ganesha Nusa</span><h2 id="legal-{{ $document }}-title">{{ $title }}</h2><p id="legal-{{ $document }}-summary">Diperbarui 22 Oktober 2026 · Gulir untuk membaca selengkapnya.</p></div>
        <button class="legal-dialog__close" type="button" data-legal-close aria-label="Tutup {{ $title }}" autofocus><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg></button>
    </header>
    <article class="legal-dialog__body" tabindex="0" aria-label="Isi {{ $title }}">
        @include('LandingPage.'.$document)
    </article>
    <footer class="legal-dialog__foot"><span>Bantuan: <a href="{{ config('app.contact_whatsapp') }}" target="_blank" rel="noopener noreferrer">Hubungi Kami</a></span><button class="btn btn--primary btn--sm" type="button" data-legal-close>Tutup</button></footer>
</dialog>
@endforeach
