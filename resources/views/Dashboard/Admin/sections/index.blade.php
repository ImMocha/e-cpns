@extends('layouts.admin.main')

@section('title', 'Kelola Bidang — Ganesha Nusa')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">Bidang</span>
    </nav>

    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow">Taksonomi Konten</span>
        <h1 style="margin-top:.5rem">Kelola Bidang</h1>
        <p>Bidang mengelompokkan materi, soal, dan artikel. TWK, TIU, dan TKP hanya bidang awal &mdash; tambah bidang sendiri kapan saja.</p>
      </div>
      <div class="btn-row">
        <a class="btn btn--primary" href="{{ route('admin.sections.create') }}">
          <svg data-icon="plus"></svg> Tambah Bidang
        </a>
      </div>
    </header>

    <section class="grid grid-3" style="--gap:1.15rem;margin-bottom:1.75rem">
      <article class="stat reveal">
        <span class="stat__icon"><svg data-icon="layers"></svg></span>
        <div>
          <div class="stat__label">Total Bidang</div>
          <div class="stat__value">{{ number_format($stats['total'], 0, ',', '.') }}</div>
          <div class="stat__delta text-soft">Semua bidang terdaftar</div>
        </div>
      </article>
      <article class="stat reveal" data-delay="70">
        <span class="stat__icon ico--mint"><svg data-icon="check-circle"></svg></span>
        <div>
          <div class="stat__label">Bidang Aktif</div>
          <div class="stat__value">{{ number_format($stats['active'], 0, ',', '.') }}</div>
          <div class="stat__delta text-soft">
            {{ $stats['total'] > 0 ? number_format($stats['active'] / $stats['total'] * 100, 1, ',', '.') : '0,0' }} persen
          </div>
        </div>
      </article>
      <article class="stat reveal" data-delay="140">
        <span class="stat__icon ico--pink"><svg data-icon="x-circle"></svg></span>
        <div>
          <div class="stat__label">Bidang Nonaktif</div>
          <div class="stat__value">{{ number_format($stats['inactive'], 0, ',', '.') }}</div>
          <div class="stat__delta text-soft">Disembunyikan dari peserta</div>
        </div>
      </article>
    </section>

    {{-- ============ CARI ============ --}}
    <form method="GET" action="{{ route('admin.sections.index') }}" class="card card--tight" style="margin-bottom:1.25rem">
      <div class="flex items-center justify-between flex-wrap" style="gap:1rem">
        <p class="text-sm text-soft">
          Materi, soal, dan artikel akan ditautkan ke bidang lewat
          <span class="text-mono text-xs">section_id</span>.
        </p>

        <div class="flex items-center flex-wrap" style="--gap:.75rem">
          <div class="input-group" style="min-width:260px;flex:1;max-width:340px">
            <span class="input-group__icon"><svg data-icon="search"></svg></span>
            <input class="input" type="search" name="q" value="{{ $filters['q'] }}"
                   placeholder="Cari nama atau kode bidang..." aria-label="Cari bidang">
          </div>

          <div class="btn-row" style="--gap:.5rem">
            <button class="btn btn--ghost btn--sm" type="submit"><svg data-icon="search"></svg> Terapkan</button>
            @if ($filters['q'])
              <a class="btn btn--ghost btn--sm" href="{{ route('admin.sections.index') }}"><svg data-icon="refresh"></svg> Reset</a>
            @endif
          </div>
        </div>
      </div>
    </form>

    {{-- ============ TABEL ============ --}}
    <section class="card card--pad-0">
      <div class="table-wrap" style="border:none;border-radius:0;box-shadow:none">
        <table class="table" id="tabelBidang">
          <thead>
            <tr>
              <th>Bidang</th>
              <th>Kode</th>
              <th>Deskripsi</th>
              <th>Penampilan</th>
              <th class="is-sortable num" aria-sort="none">Urutan<span class="sort-ind">&uarr;&darr;</span></th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($sections as $section)
                @php($tone = $section->resolvedTone())
                <tr>
                  <td>
                    <div class="userchip">
                      <span class="stat__icon {{ $tone->iconModifier() }}"
                            style="width:34px;height:34px;flex-basis:34px;border-radius:12px">
                        <svg data-icon="{{ $section->resolvedIcon() }}" style="width:17px;height:17px"></svg>
                      </span>
                      <div>
                        <strong>{{ $section->name }}</strong>
                        <span class="text-mono text-xs">/{{ $section->slug }}</span>
                      </div>
                    </div>
                  </td>
                  <td><span class="badge badge--ink badge--flat text-mono">{{ $section->code }}</span></td>
                  <td class="text-sm text-soft" style="max-width:260px">
                    {{ \Illuminate\Support\Str::limit($section->description ?: '—', 56) }}
                  </td>
                  <td>
                    <span class="badge {{ $tone->badgeClass() }} badge--flat">
                      <svg data-icon="{{ $section->resolvedIcon() }}"></svg> {{ $tone->label() }}
                    </span>
                  </td>
                  <td class="num text-mono" data-sort="{{ $section->position }}">{{ $section->position }}</td>
                  <td>
                    @if ($section->is_active)
                      <span class="badge badge--mint badge--flat"><svg data-icon="check"></svg> Aktif</span>
                    @else
                      <span class="badge badge--danger badge--flat"><svg data-icon="x-circle"></svg> Nonaktif</span>
                    @endif
                  </td>
                  <td>
                    <div class="flex" style="--gap:.35rem">
                      <a class="btn btn--ghost btn--square btn--sm" href="{{ route('admin.sections.edit', $section) }}"
                         aria-label="Ubah {{ $section->name }}" title="Ubah">
                        <svg data-icon="pencil"></svg>
                      </a>

                      <form method="POST" action="{{ route('admin.sections.destroy', $section) }}"
                            onsubmit="event.preventDefault(); L.confirmAction({ title: 'Hapus bidang ini?', body: 'Bidang '{{ $section->name }}' akan dihapus. Tindakan ini tidak bisa dibatalkan.', ok: 'Ya, hapus', tone: 'danger', onOk: function () { this.closest('form').submit(); } }.bind(this));">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn--ghost btn--square btn--sm" type="submit"
                                aria-label="Hapus {{ $section->name }}" title="Hapus">
                          <svg data-icon="trash"></svg>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
            @empty
                <tr>
                  <td colspan="7">
                    <div class="empty" style="margin:1.5rem 0">
                      <span class="empty__icon"><svg data-icon="search"></svg></span>
                      <strong style="color:var(--ink);font-family:var(--font-display)">Bidang tidak ditemukan</strong>
                      <p class="text-sm text-soft" style="margin-top:.35rem">
                        @if ($filters['q'])
                          Tidak ada bidang yang cocok dengan &ldquo;{{ $filters['q'] }}&rdquo;.
                        @else
                          Belum ada bidang sama sekali.
                        @endif
                      </p>
                      @if ($filters['q'])
                        <a class="btn btn--ghost btn--sm" href="{{ route('admin.sections.index') }}" style="margin-top:1rem">
                          <svg data-icon="refresh"></svg> Reset pencarian
                        </a>
                      @else
                        <a class="btn btn--primary btn--sm" href="{{ route('admin.sections.create') }}" style="margin-top:1rem">
                          <svg data-icon="plus"></svg> Tambah Bidang
                        </a>
                      @endif
                    </div>
                  </td>
                </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if ($sections->hasPages() || $sections->total() > 0)
        <div class="flex items-center justify-between flex-wrap" style="gap:1rem;padding:1.25rem 1.5rem;border-top:2px solid var(--border)">
          <span class="text-xs text-soft">
            Menampilkan {{ $sections->firstItem() }}&ndash;{{ $sections->lastItem() }} dari {{ number_format($sections->total(), 0, ',', '.') }} bidang
          </span>
          <x-pager :paginator="$sections" />
        </div>
      @endif
    </section>
@endsection