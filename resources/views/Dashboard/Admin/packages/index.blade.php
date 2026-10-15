@extends('layouts.admin.main')
@section('title', 'Paket Belajar — Admin Ganesha Nusa')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.dashboard') }}">Admin Dashboard</a><span class="crumbs__sep">›</span><span class="crumbs__current">Paket Belajar</span></nav>
<header class="page-head page-head__row">
    <div><span class="eyebrow">Akses Peserta</span><h1 style="margin-top:.5rem">Paket Belajar</h1><p>Atur harga, masa aktif, konten, dan total kuota tryout setiap paket.</p></div>
    <a class="btn btn--primary" href="{{ route('admin.packages.create') }}"><svg data-icon="plus"></svg> Buat paket</a>
</header>
<div class="btn-row question-section"><span class="badge badge--primary badge--flat">{{ $activeCount }} paket aktif</span><span class="badge badge--mint badge--flat">{{ $memberCount }} peserta memiliki paket aktif</span></div>
<form class="card question-filter question-section" method="GET">
    <div class="field question-filter-search"><label class="label" for="package-search">Cari paket</label><input class="input" id="package-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="180"></div>
    <div class="field"><label class="label" for="package-status">Status</label><select class="select" id="package-status" name="status"><option value="">Semua paket</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option><option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Draft / nonaktif</option><option value="archived" @selected(($filters['status'] ?? '') === 'archived')>Diarsipkan</option></select></div>
    <button class="btn btn--primary">Cari</button>
</form>
<section class="card">
<div class="table-wrap"><table class="table"><thead><tr><th>Paket</th><th>Harga</th><th>Masa aktif</th><th>Konten</th><th>Kuota tryout</th><th>Peserta aktif</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse ($packages as $package)
<tr>
    <td><strong>{{ $package->name }}</strong><p class="text-xs text-soft">{{ $package->leaderboard_access->label() }}</p></td><td>{{ $package->formattedPrice() }}</td><td>{{ $package->durationLabel() }}</td>
    <td>{{ $package->materials_count }} materi · {{ $package->exams_count }} sesi</td><td>{{ $package->tryout_quota ?? 'Tanpa batas' }}</td><td>{{ $package->active_members_count }}</td>
    <td><span class="badge badge--{{ $package->trashed() ? 'coral' : ($package->is_active ? 'mint' : 'amber') }} badge--flat">{{ $package->trashed() ? 'Diarsipkan' : ($package->is_active ? 'Aktif' : 'Draft / nonaktif') }}</span>
        @if (! $package->trashed() && $package->is_active && ! $package->hasAvailableContent())<p class="text-xs text-soft" style="margin-top:.5rem">Konten belum tersedia. Perbarui isi paket agar dapat dibeli.</p>@endif
    </td>
    <td><div class="btn-row"><a class="btn btn--ghost btn--sm" href="{{ route('admin.packages.edit', $package) }}">{{ $package->trashed() ? 'Lihat peserta' : 'Kelola' }}</a>
    @unless ($package->trashed())<form method="POST" action="{{ route('admin.packages.destroy', $package) }}" data-content-confirm data-confirm-title="Arsipkan paket?" data-confirm-body="Paket hilang dari katalog. Akses peserta yang sudah aktif tetap berlaku.">@csrf @method('DELETE')<button class="btn btn--ghost btn--sm">Arsipkan</button></form>@endunless
    </div></td>
</tr>
@empty<tr><td colspan="8" class="text-soft">Belum ada paket yang sesuai. Buat paket dan pilih konten untuk mulai.</td></tr>@endforelse
</tbody></table></div>
@if ($packages->hasPages())<div class="exam-pager"><x-pager :paginator="$packages" /></div>@endif
</section>
@endsection
