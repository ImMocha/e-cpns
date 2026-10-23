@extends('layouts.admin.main')

@section('title', 'Kelola Peserta — Ganesha Nusa')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">Peserta</span>
    </nav>

    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow">Pengguna</span>
        <h1 style="margin-top:.5rem">Kelola Peserta</h1>
        <p>Lihat data peserta, ubah informasinya, dan aktifkan atau nonaktifkan akun.</p>
      </div>
      <div class="btn-row">
        <a class="btn btn--primary" href="{{ route('admin.users.create') }}">
          <svg data-icon="user-plus"></svg> Tambah Peserta
        </a>
      </div>
    </header>

    <section class="grid grid-4" style="--gap:1.15rem;margin-bottom:1.75rem">
      <article class="stat">
        <span class="stat__icon"><svg data-icon="users"></svg></span>
        <div>
          <div class="stat__label">Total Pengguna</div>
          <div class="stat__value">{{ number_format($stats['total'], 0, ',', '.') }}</div>
          <div class="stat__delta text-soft">Seluruh akun terdaftar</div>
        </div>
      </article>
      <article class="stat">
        <span class="stat__icon ico--mint"><svg data-icon="check"></svg></span>
        <div>
          <div class="stat__label">Akun Aktif</div>
          <div class="stat__value">{{ number_format($stats['active'], 0, ',', '.') }}</div>
          <div class="stat__delta text-soft">
            {{ $stats['total'] > 0 ? number_format($stats['active'] / $stats['total'] * 100, 1, ',', '.') : '0,0' }} persen
          </div>
        </div>
      </article>
      <article class="stat">
        <span class="stat__icon ico--amber"><svg data-icon="shield"></svg></span>
        <div>
          <div class="stat__label">Administrator</div>
          <div class="stat__value">{{ number_format($stats['admins'], 0, ',', '.') }}</div>
          <div class="stat__delta text-soft">Bisa akses Admin Panel</div>
        </div>
      </article>
      <article class="stat">
        <span class="stat__icon ico--pink"><svg data-icon="x-circle"></svg></span>
        <div>
          <div class="stat__label">Akun Nonaktif</div>
          <div class="stat__value">{{ number_format($stats['inactive'], 0, ',', '.') }}</div>
          <div class="stat__delta text-soft">Perlu tinjauan</div>
        </div>
      </article>
    </section>

    {{-- ============ FILTER (server-side) ============ --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="card card--tight" style="margin-bottom:1.25rem">
      <div class="flex items-center justify-between flex-wrap" style="gap:1rem">
        <div class="pills">
          @foreach ($tabs as $tab)
            <a class="pill {{ $tab['is_active'] ? 'is-active' : '' }}" href="{{ $tab['url'] }}"
               @if ($tab['is_active']) aria-current="page" @endif>{{ $tab['label'] }}</a>
          @endforeach
        </div>

        <div class="input-group" style="min-width:260px;flex:1;max-width:340px">
          <span class="input-group__icon"><svg data-icon="search"></svg></span>
          <input class="input" type="search" name="q" value="{{ $filters['q'] }}"
                 placeholder="Cari nama, email, telepon..." aria-label="Cari peserta">
          @if ($filters['role'])
            <input type="hidden" name="role" value="{{ $filters['role'] }}">
          @endif
          @if ($filters['status'])
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
          @endif
        </div>

        <div class="btn-row" style="--gap:.5rem">
          <button class="btn btn--ghost btn--sm" type="submit"><svg data-icon="search"></svg> Terapkan</button>
          @if ($filters['q'] || $filters['role'] || $filters['status'])
            <a class="btn btn--ghost btn--sm" href="{{ route('admin.users.index') }}"><svg data-icon="refresh"></svg> Reset</a>
          @endif
        </div>
      </div>
    </form>

    {{-- ============ TABEL ============ --}}
    <section class="card card--pad-0">
      <div class="table-wrap" style="border:none;border-radius:0;box-shadow:none">
        <table class="table" id="tabelPeserta">
          <thead>
            <tr>
              <th>Peserta</th>
              <th class="is-sortable" aria-sort="none">Role<span class="sort-ind">&uarr;&darr;</span></th>
              <th>Telepon</th>
              <th class="is-sortable" aria-sort="descending">Tanggal Registrasi<span class="sort-ind">&uarr;&darr;</span></th>
              <th>Login Terakhir</th>
              <th>Status Akun</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($users as $user)
                <tr>
                  <td>
                    <div class="userchip">
                      <span class="avatar avatar--sm avatar--{{ $user->avatarTone() }}">{{ $user->initials() }}</span>
                      <div>
                        <strong>{{ $user->name }}</strong>
                        <span>{{ $user->email }}</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    @if ($user->isAdmin())
                      <span class="badge badge--ink badge--flat"><svg data-icon="shield"></svg> Admin</span>
                    @else
                      <span class="badge badge--primary badge--flat">Peserta</span>
                    @endif
                  </td>
                  <td class="text-mono text-sm" style="white-space:nowrap">{{ $user->phone ?? '—' }}</td>
                  <td data-sort="{{ $user->created_at->timestamp }}" style="white-space:nowrap">{{ $user->created_at->format('d M Y') }}</td>
                  <td class="text-sm text-soft" style="white-space:nowrap">
                    {{ $user->last_login_at ? $user->last_login_at->format('d M Y H:i') : 'Belum pernah' }}
                  </td>
                  <td>
                    @if ($user->isActive())
                      <span class="badge badge--mint badge--flat"><svg data-icon="check"></svg> Aktif</span>
                    @else
                      <span class="badge badge--danger badge--flat"><svg data-icon="x-circle"></svg> Nonaktif</span>
                    @endif
                  </td>
                  <td>
                    <div class="flex" style="--gap:.35rem">
                      <a class="btn btn--ghost btn--square btn--sm" href="{{ route('admin.users.edit', $user) }}" aria-label="Ubah {{ $user->name }}">
                        <svg data-icon="pencil"></svg>
                      </a>

                      <form method="POST" action="{{ route('admin.users.status', $user) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn--ghost btn--square btn--sm" type="submit"
                                aria-label="{{ $user->isActive() ? 'Nonaktifkan' : 'Aktifkan' }} {{ $user->name }}"
                                title="{{ $user->isActive() ? 'Nonaktifkan akun' : 'Aktifkan akun' }}">
                          <svg data-icon="{{ $user->isActive() ? 'x-circle' : 'check-circle' }}"></svg>
                        </button>
                      </form>
                      @unless (auth()->user()->is($user))
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                              data-content-confirm data-confirm-title="Hapus akun peserta?"
                              data-confirm-body="Akun {{ $user->name }} akan dihapus dari daftar dan tidak dapat masuk. Riwayat tryout dan transaksi tetap tersimpan."
                              data-confirm-ok="Ya, hapus">
                          @csrf
                          @method('DELETE')
                          <button class="btn btn--ghost btn--sm" style="color:#B3282C" type="submit" aria-label="Hapus akun {{ $user->name }}">
                            <svg data-icon="trash"></svg> Hapus
                          </button>
                        </form>
                      @endunless
                    </div>
                  </td>
                </tr>
            @empty
                <tr>
                  <td colspan="7">
                    <div class="empty" style="margin:1.5rem 0">
                      <span class="empty__icon"><svg data-icon="search"></svg></span>
                      <strong style="color:var(--ink);font-family:var(--font-display)">Peserta tidak ditemukan</strong>
                      <p class="text-sm text-soft" style="margin-top:.35rem">
                        @if ($filters['q'] || $filters['role'] || $filters['status'])
                          Tidak ada akun yang cocok dengan filter ini. Coba reset atau ubah kata kunci.
                        @else
                          Belum ada peserta terdaftar. Mulai dengan membuat akun pertama.
                        @endif
                      </p>
                      @if ($filters['q'] || $filters['role'] || $filters['status'])
                        <a class="btn btn--ghost btn--sm" href="{{ route('admin.users.index') }}" style="margin-top:1rem">
                          <svg data-icon="refresh"></svg> Reset filter
                        </a>
                      @else
                        <a class="btn btn--primary btn--sm" href="{{ route('admin.users.create') }}" style="margin-top:1rem">
                          <svg data-icon="user-plus"></svg> Tambah Peserta
                        </a>
                      @endif
                    </div>
                  </td>
                </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if ($users->hasPages() || $users->total() > 0)
        <div class="flex items-center justify-between flex-wrap" style="gap:1rem;padding:1.25rem 1.5rem;border-top:2px solid var(--border)">
          <span class="text-xs text-soft">
            Menampilkan {{ $users->firstItem() }}&ndash;{{ $users->lastItem() }} dari {{ number_format($users->total(), 0, ',', '.') }} pengguna
          </span>
          <x-pager :paginator="$users" />
        </div>
      @endif
    </section>
@endsection
