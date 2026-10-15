@extends('layouts.base')

@section('title', 'Sertifikat '.$attempt->certificateNumber().' — '.$participant->name)
@section('body-attributes')class="certificate-page"@endsection

@section('body')
    <main class="certificate-workspace">
      <div class="certificate-toolbar no-print">
        <a class="btn btn--ghost btn--sm" href="{{ route('certificates.index') }}"><svg data-icon="arrow-left"></svg> Daftar Sertifikat</a>
        <button class="btn btn--primary" type="button" data-print><svg data-icon="printer"></svg> Cetak / Simpan PDF</button>
        <p class="text-sm text-soft">Pilih kertas A4, orientasi landscape, dan nonaktifkan header/footer browser. Untuk PDF, pilih tujuan “Simpan sebagai PDF”.</p>
      </div>

      <article class="cert certificate-document" aria-label="Sertifikat kelulusan tryout {{ $participant->name }}">
        <div class="cert__frame" aria-hidden="true"></div>
        <div class="certificate-brand">
          <div class="logo"><span class="logo__mark"><svg data-icon="book"></svg></span><span>Ganesha Nusa<span class="logo__sub">Lembaga Belajar CPNS</span></span></div>
          <span class="certificate-number">Nomor: {{ $attempt->certificateNumber() }}</span>
        </div>
        <div class="cert__seal"><svg data-icon="award"></svg></div>
        <p class="certificate-kicker">Sertifikat Kelulusan Tryout</p>
        <p class="certificate-caption">Diberikan kepada</p>
        <h1 class="cert__name certificate-name">{{ $participant->name }}</h1>
        <p class="certificate-description">Telah menyelesaikan dan mencapai passing grade simulasi Tryout CAT</p>
        <h2 class="certificate-exam">{{ $attempt->title }}</h2>
        <p class="certificate-description">yang diselenggarakan oleh Ganesha Nusa pada {{ $attempt->submitted_at->locale('id')->translatedFormat('d F Y') }}.</p>

        <div class="certificate-scores">
          <div class="certificate-score certificate-score--total"><span>Nilai Total</span><strong>{{ $attempt->score }} <small>/ {{ $attempt->max_score }}</small></strong></div>
          <div class="certificate-score"><span>Passing Grade</span><strong>{{ $attempt->passing_score }}</strong></div>
          @foreach ($sections as $section)
            <div class="certificate-score"><span>{{ $section['section'] }}</span><strong>{{ $section['score'] }} <small>/ {{ $section['max'] }}</small></strong></div>
          @endforeach
        </div>

        <div class="certificate-footer">
          <div><span class="certificate-kicker">Tanggal Penyelesaian</span><p>{{ $attempt->submitted_at->locale('id')->translatedFormat('d F Y') }}</p></div>
          <div class="certificate-issuer"><strong>Ganesha Nusa</strong><p>Lembaga Belajar CPNS</p></div>
        </div>
        <p class="certificate-note">Sertifikat ini merupakan bukti pencapaian passing grade simulasi tryout, bukan bukti kelulusan seleksi CPNS.</p>
      </article>
    </main>
@endsection
