@extends('layouts.user.main')

@use('App\Enums\MaterialType')

@section('title', $material->title . ' — LONTAR CPNS Online')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('dashboard') }}">Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <a href="{{ route('materials.index') }}">Materi</a>
      @if ($material->section)
        <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
        <a href="{{ route('materials.index', ['section_id' => $material->section->id]) }}">{{ $material->section->name }}</a>
      @endif
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">{{ $material->title }}</span>
    </nav>

    <header class="page-head">
      <span class="eyebrow">{{ $material->section?->name ?? 'Materi' }}</span>
      @if ($material->topic)
        <p class="text-sm text-soft" style="margin-top:.5rem">{{ $material->topic->name }}</p>
      @endif
      <h1 style="margin-top:.5rem">{{ $material->title }}</h1>
      <div class="flex items-center gap-2 flex-wrap" style="margin-top:.85rem">
        <span class="badge {{ $material->type->badgeClass() }} badge--flat">
          <svg data-icon="{{ $material->type->icon() }}"></svg> {{ $material->type->label() }}
        </span>
        @if ($material->page_count)
          <span class="badge badge--ink badge--flat">{{ number_format($material->page_count, 0, ',', '.') }} halaman</span>
        @endif
        @if ($material->humanDuration())
          <span class="badge badge--ink badge--flat"><svg data-icon="clock"></svg> {{ $material->humanDuration() }}</span>
        @endif
        @if ($material->published_at)
          <span class="badge badge--mint badge--flat"><svg data-icon="check"></svg> Terbit {{ $material->published_at->format('d M Y') }}</span>
        @endif
      </div>
    </header>

    @if ($material->summary)
      <p class="text-soft" style="max-width:78ch;margin:0 0 1.5rem">{{ $material->summary }}</p>
    @endif

    @if ($progress)
      <section class="card card--tight card--dashed" style="margin-bottom:1.5rem">
        <div class="flex items-center justify-between flex-wrap" style="gap:1rem">
          <div>
            <strong style="display:block;color:var(--ink)">{{ $progress->completed_at ? 'Materi selesai dipelajari' : 'Sedang dipelajari' }}</strong>
            <p class="text-xs text-soft" style="margin-top:.3rem">{{ $progress->completed_at ? 'Selesai pada '.$progress->completed_at->translatedFormat('d M Y') : 'Tandai selesai setelah kamu membaca atau menonton materi ini.' }}</p>
          </div>
          <form method="POST" action="{{ route($progress->completed_at ? 'materials.progress.destroy' : 'materials.progress.store', $material) }}">
            @csrf
            @if ($progress->completed_at) @method('DELETE') @endif
            <button type="submit" class="btn {{ $progress->completed_at ? 'btn--ghost' : 'btn--primary' }} btn--sm">
              <svg data-icon="{{ $progress->completed_at ? 'rotate-ccw' : 'check-circle' }}"></svg>
              {{ $progress->completed_at ? 'Tandai belum selesai' : 'Tandai selesai' }}
            </button>
          </form>
        </div>
      </section>
    @endif

    {{-- ============ ISI MATERI ============ --}}
    @if ($material->type === MaterialType::Pdf)
      {{-- `fileExists()` bukan sekadar `file_path !== null`: path yang menunjuk
           ke file yang hilang akan menghasilkan frame 404 tanpa penjelasan. --}}
      @php($hasFile = $material->fileExists())

      @if ($hasFile)
        {{--
            Viewer milik LONTAR, bukan `<object>` / `<embed>` / `<iframe>`.

            Viewer bawaan browser selalu punya tombol Unduh dan Cetak. Selama
            PDF-nya diserahkan sebagai dokumen, mengunduhnya cuma satu klik.
            Di sini PDF-nya dirender pdf.js ke <canvas> dan toolbar-nya kita
            yang menentukan — sehingga tombol tersebut tidak pernah muncul.

            Watermark memakai nama + email peserta supaya salinan yang bocor
            masih bisa ditelusuri.
        --}}
        <div class="pdfview"
             data-pdf-reader
             data-src="{{ $material->fileUrl() }}"
             data-watermark="{{ auth()->user()?->name }} — {{ auth()->user()?->email }}"
             data-start-page="1">
          <div class="pdfview__bar">
            <div class="pdfview__file">
              <svg data-icon="file"></svg>
              <span>{{ $material->slug }}.pdf</span>
            </div>

            <div class="pdfview__tools">
              <button class="pdfview__btn" id="pdfview-prev" type="button" aria-label="Halaman sebelumnya" title="Halaman sebelumnya">
                <svg data-icon="chevron-left"></svg>
              </button>

              <span class="pdfview__page-nav">
                <label class="sr-only" for="pdfview-page-input">Nomor halaman</label>
                <input id="pdfview-page-input" type="number" min="1" value="1" inputmode="numeric">
                <span>/ <span id="pdfview-total">—</span></span>
              </span>

              <button class="pdfview__btn" id="pdfview-next" type="button" aria-label="Halaman berikutnya" title="Halaman berikutnya">
                <svg data-icon="chevron-right"></svg>
              </button>

              <button class="pdfview__btn" id="pdfview-zoom-out" type="button" aria-label="Perkecil" title="Perkecil">
                <svg data-icon="zoom-out"></svg>
              </button>

              <button class="pdfview__btn is-active" id="pdfview-fit" type="button"
                      aria-label="Sesuaikan lebar halaman" title="Sesuaikan lebar halaman">
                <svg data-icon="maximize"></svg>
              </button>

              <button class="pdfview__btn" id="pdfview-zoom-in" type="button" aria-label="Perbesar" title="Perbesar">
                <svg data-icon="zoom-in"></svg>
              </button>

              <span class="badge badge--amber badge--flat" title="Situs ini tidak menyediakan tombol unduh">
                <svg data-icon="lock"></svg> Tanpa unduh &amp; cetak
              </span>
            </div>
          </div>

          {{-- `tabindex="0"` supaya area baca bisa menerima fokus; tanpa itu panah
             kiri/kanan tidak pernah sampai ke penampil PDF. --}}
          <div class="pdfview__pages" id="pdfview-pages" tabindex="0" role="region"
               aria-label="Area baca materi. Gunakan panah kiri dan kanan untuk pindah halaman.">
            <div class="pdfview__state">
              <span class="pdfview__spinner" aria-hidden="true"></span>
              <p class="text-sm text-soft" style="margin:.9rem 0 0">Menyiapkan dokumen...</p>
            </div>
          </div>
        </div>

        <div class="pdfview__note" style="margin-top:.9rem">
          <svg data-icon="info"></svg>
          <div>
            Setiap halaman diberi watermark nama dan email kamu. Menyalin, mencetak, atau
            "screenshot" masih tidak bisa dicegah sepenuhnya secara teknis, tapi file yang
            bocor bisa ditelusuri balik ke pemiliknya.
          </div>
        </div>
      @else
        <div class="alert alert--warn" style="margin-bottom:1.5rem">
          <span class="alert__icon"><svg data-icon="alert"></svg></span>
          <div>
            <strong>Berkas PDF belum tersedia</strong>
            Materi ini sudah terbit tapi berkasnya belum ada di server. Hubungi admin Learning LONTAR.
          </div>
        </div>
      @endif
    @else
      @php($videoUrl = $material->video_url)

      @if ($videoUrl)
        {{-- Sumber video masih TBD (§44 TBD-11), jadi dipakai <video> apa adanya.
               Kalau nanti pindah ke YouTube atau Vimeo, hanya blok ini yang berubah. --}}
        <div style="max-width:900px">
          <div class="video-frame" style="border-radius:var(--r-md);overflow:hidden">
            <video controls preload="metadata" style="display:block;width:100%;aspect-ratio:16/9;background:#000"
                   @if ($material->duration_minutes) data-duration="{{ $material->duration_minutes }}" @endif>
              <source src="{{ $videoUrl }}">
              Browser kamu tidak mendukung pemutaran video.
            </video>
          </div>
          <p class="text-xs text-soft" style="margin-top:.6rem">
            Durasi catatan: {{ $material->humanDuration() ?? 'tidak diisi' }}. Sumber video masih menunggu keputusan client.
          </p>
        </div>
      @else
        <div class="alert alert--warn" style="margin-bottom:1.5rem">
          <span class="alert__icon"><svg data-icon="alert"></svg></span>
          <div>
            <strong>URL video belum diisi</strong>
            Materi ini sudah terbit tapi belum punya sumber video. Hubungi admin Learning LONTAR.
          </div>
        </div>
      @endif
    @endif

    {{-- ============ CATATAN AKSES ============ --}}
@if ($material->type === MaterialType::Pdf)
        <div class="alert alert--info" style="margin-top:1.5rem">
          <span class="alert__icon"><svg data-icon="info"></svg></span>
          <div>
            <strong>Dokumen hanya untuk dibaca</strong>
            Situs ini tidak menyediakan tombol unduh, dan pintasan Ctrl+S / Ctrl+P
            dinonaktifkan. Apakah materi boleh diunduh tetap menunggu keputusan
            client (§44 TBD-12).
          </div>
        </div>
      @endif

    <div class="btn-row" style="margin-top:1.5rem">
      <a class="btn btn--ghost" href="{{ route('materials.index') }}"><svg data-icon="arrow-left"></svg> Kembali ke daftar materi</a>
      <a class="btn btn--primary" href="{{ route('question-bank.index') }}">
        <svg data-icon="list-checks"></svg> Latihan Soal Materi Ini
      </a>
    </div>

    {{-- ============ MATERI LAIN ============ --}}
    @if ($related->isNotEmpty())
      <section style="margin-top:2.5rem">
        <div class="section-head section-head--split">
          <div>
            <h2 style="font-size:var(--fs-lg)">Materi lain di {{ $material->section?->name ?? 'bidang ini' }}</h2>
            <p>Materi lain yang masih bisa kamu akses dari bidang yang sama.</p>
          </div>
          @if ($material->section)
            <a class="btn btn--ghost btn--sm" href="{{ route('materials.index', ['section_id' => $material->section->id]) }}">
              Lihat semua
            </a>
          @endif
        </div>

        <div class="grid-auto grid-auto--sm related-material-grid">
          @foreach ($related as $item)
            <article class="card card--tight card--pop">
              <div class="flex items-center gap-3 related-material-card__row">
                <span class="tile__glyph" style="width:44px;height:44px;flex:0 0 44px;border-radius:14px">
                  <svg data-icon="{{ $item->type->icon() }}" style="width:22px;height:22px"></svg>
                </span>
                <div class="related-material-card__content">
                  <strong class="related-material-card__title">{{ $item->title }}</strong>
                  <span class="text-xs text-soft related-material-card__meta">
                    {{ $item->type->shortLabel() }}
                    @if ($item->humanDuration()) &middot; {{ $item->humanDuration() }} @endif
                    @if ($item->page_count) &middot; {{ number_format($item->page_count, 0, ',', '.') }} halaman @endif
                  </span>
                </div>
                <a class="btn btn--ghost btn--square btn--sm related-material-card__open" href="{{ route('materials.show', $item) }}"
                   aria-label="Buka {{ $item->title }}" title="Buka">
                  <svg data-icon="arrow-right"></svg>
                </a>
              </div>
            </article>
          @endforeach
        </div>
      </section>
    @endif
@endsection
