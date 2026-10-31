@extends('layouts.admin.main')

@section('title', 'Riwayat Tryout Peserta — Ganesha Nusa')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a><span class="crumbs__sep">›</span>
      <a href="{{ route('admin.users.index') }}">Peserta</a><span class="crumbs__sep">›</span>
      <span class="crumbs__current">Riwayat Tryout</span>
    </nav>
    <header class="page-head page-head__row">
      <div><span class="eyebrow">Aktivitas Peserta</span><h1 style="margin-top:.5rem">Riwayat Tryout</h1>
        <p>{{ $participant ? 'Riwayat tryout '.$participant->name : 'Pantau riwayat pengerjaan tryout seluruh peserta.' }}</p>
      </div>
      <div class="btn-row">
        @if ($participant)<a class="btn btn--ghost" href="{{ route('admin.users.tryout-history') }}">Semua Riwayat</a>@endif
        <a class="btn btn--ghost" href="{{ route('admin.users.index') }}"><svg data-icon="arrow-left"></svg> Kelola Peserta</a>
      </div>
    </header>
    @include('components.flash')
    <form class="card flex items-center flex-wrap" method="GET" action="{{ route('admin.users.tryout-history') }}" style="gap:1rem;margin-bottom:1.5rem">
      @if ($participant)<input type="hidden" name="user_id" value="{{ $participant->id }}">@endif
      <div style="flex:1;min-width:200px"><label class="text-sm" for="history-search">Cari peserta atau tryout</label>
        <input class="input" id="history-search" name="q" value="{{ $search }}" maxlength="200" placeholder="Nama, email, atau nama tryout…">
      </div>
      <div><label class="text-sm" for="history-status">Status pengerjaan</label>
        <select class="input" id="history-status" name="status">
          <option value="">Semua status</option>
          <option value="completed" @selected($status === 'completed')>Selesai</option>
          <option value="in_progress" @selected($status === 'in_progress')>Belum selesai</option>
        </select>
      </div>
      <button class="btn btn--primary btn--sm" type="submit"><svg data-icon="search"></svg> Terapkan</button>
      <a class="btn btn--ghost btn--sm" href="{{ route('admin.users.tryout-history', ['user_id' => $participant?->id]) }}">Reset</a>
    </form>
    <section class="card card--pad-0">
      <div class="table-wrap" style="border:none;border-radius:0;box-shadow:none">
        <table class="table">
          <thead><tr><th>Peserta</th><th>Tryout</th><th>Mulai / Selesai</th><th>Status</th><th>Nilai Total</th><th>TWK / TIU / TKP</th><th>Durasi</th></tr></thead>
          <tbody>
            @forelse ($attempts as $attempt)
              <tr>
                <td><div class="userchip"><div><strong>{{ $attempt->user?->name ?? 'Akun tidak tersedia' }}</strong><span>{{ $attempt->user?->email }}</span>
                  @if ($attempt->user?->trashed())<span>Akun dihapus</span>@endif
                </div></div></td>
                <td><strong>{{ $attempt->title }}</strong></td>
                <td class="text-sm" style="white-space:nowrap"><x-local-time :value="$attempt->started_at" /><br><span class="text-soft"><x-local-time :value="$attempt->submitted_at" fallback="Belum selesai" /></span></td>
                <td>
                  @if ($attempt->status === 'completed')
                    <span class="badge badge--mint badge--flat"><svg data-icon="check"></svg> Selesai</span>
                  @elseif ($attempt->expires_at && $attempt->expires_at->isPast())
                    <span class="badge badge--amber badge--flat">Waktu habis</span>
                  @else
                    <span class="badge badge--primary badge--flat">Sedang dikerjakan</span>
                  @endif
                </td>
                <td><strong>{{ $attempt->status === 'completed' ? $attempt->score.' / '.$attempt->max_score : '—' }}</strong></td>
                <td class="text-sm" style="white-space:nowrap">@if ($attempt->status === 'completed'){{ $attempt->section_scores['TWK']['score'] ?? '—' }} / {{ $attempt->section_scores['TIU']['score'] ?? '—' }} / {{ $attempt->section_scores['TKP']['score'] ?? '—' }}@else — @endif</td>
                <td class="text-sm" style="white-space:nowrap">{{ $attempt->status === 'completed' ? floor($attempt->duration_seconds / 60).' mnt '.($attempt->duration_seconds % 60).' dtk' : '—' }}</td>
              </tr>
            @empty
              <tr><td colspan="7"><div class="empty" style="margin:1.5rem 0"><span class="empty__icon"><svg data-icon="clock"></svg></span><strong>Belum ada riwayat tryout</strong><p class="text-sm text-soft">Tidak ada sesi tryout yang sesuai dengan filter ini.</p></div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="flex items-center justify-between flex-wrap" style="gap:1rem;padding:1.25rem 1.5rem;border-top:2px solid var(--border)">
        <span class="text-xs text-soft">{{ number_format($attempts->total(), 0, ',', '.') }} sesi tryout ditemukan</span>
        <x-pager :paginator="$attempts" />
      </div>
    </section>
@endsection
