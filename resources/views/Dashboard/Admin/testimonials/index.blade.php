@extends('layouts.admin.main')
@section('title', 'Kelola Testimoni — Ganesha Nusa')
@section('content')
    @include('components.flash')
    <header class="page-head">
        <div><span class="eyebrow">Cerita Peserta</span><h1>Kelola Testimoni</h1><p>Tampilkan atau sembunyikan testimoni tanpa menghapus data peserta.</p></div>
        <div class="btn-row"><a class="btn btn--ghost" href="{{ route('home') }}#testimoni">Lihat Halaman Publik <svg data-icon="external"></svg></a><a class="btn btn--primary" href="{{ route('admin.testimonials.create') }}"><svg data-icon="plus"></svg> Tambah Testimoni</a></div>
    </header>
    <p class="text-sm text-soft mb-6">{{ $counts['total'] }} testimoni · {{ $counts['visible'] }} ditampilkan · {{ $counts['hidden'] }} tersembunyi</p>
    <form method="GET" action="{{ route('admin.testimonials.index') }}" class="card flex flex-wrap items-end gap-4 mb-6">
        <div class="field flex-1 min-w-48"><label class="label" for="testimonial-search">Cari testimoni</label><input class="input" type="search" id="testimonial-search" name="q" value="{{ $filters['q'] }}" maxlength="120" placeholder="Nama, instansi, atau isi testimoni"></div>
        <div class="field"><label class="label" for="testimonial-status">Status</label><select class="select" id="testimonial-status" name="status"><option value="">Semua status</option><option value="visible" @selected($filters['status'] === 'visible')>Ditampilkan (visible)</option><option value="hidden" @selected($filters['status'] === 'hidden')>Tersembunyi (hidden)</option></select></div>
        <button class="btn btn--primary" type="submit">Cari</button><a class="btn btn--ghost" href="{{ route('admin.testimonials.index') }}">Reset</a>
    </form>
    @if ($errors->any())<div class="card mb-6" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        @forelse ($testimonials as $testimonial)
            <article class="card flex min-w-0 flex-col gap-4">
                <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="wrap-anywhere">{{ $testimonial->name }}</h2><span class="badge {{ $testimonial->is_visible ? 'badge--mint' : 'badge--amber' }}">{{ $testimonial->is_visible ? 'Ditampilkan (visible)' : 'Tersembunyi (hidden)' }}</span></div>
                @if ($testimonial->institution)<p class="text-sm text-soft wrap-anywhere">{{ $testimonial->institution }}</p>@endif
                <p class="whitespace-pre-wrap wrap-anywhere flex-1">{{ $testimonial->content }}</p>
                <p class="text-xs text-soft">Dikirim <x-local-time :value="$testimonial->created_at" format="d M Y H:i" /></p>
                <div class="btn-row">
                    <a class="btn btn--ghost btn--sm" href="{{ route('admin.testimonials.edit', $testimonial) }}"><svg data-icon="pencil"></svg> Edit</a>
                    <form method="POST" action="{{ route('admin.testimonials.status', $testimonial) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="is_visible" value="{{ $testimonial->is_visible ? '0' : '1' }}">
                        <button class="btn btn--outline btn--sm" type="submit" aria-label="{{ $testimonial->is_visible ? 'Sembunyikan' : 'Tampilkan' }} testimoni {{ $testimonial->name }}">{{ $testimonial->is_visible ? 'Sembunyikan' : 'Tampilkan' }}</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="card lg:col-span-2"><h2>{{ $filters['q'] || $filters['status'] ? 'Testimoni tidak ditemukan' : 'Belum ada testimoni' }}</h2><p class="text-soft">Testimoni dari halaman publik akan muncul di sini untuk ditinjau. Kamu juga dapat menambahkannya lewat tombol Tambah Testimoni.</p></div>
        @endforelse
    </div>
    <x-pager :paginator="$testimonials" />
@endsection
