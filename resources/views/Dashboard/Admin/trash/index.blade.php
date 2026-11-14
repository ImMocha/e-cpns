@extends('layouts.admin.main')

@section('title', 'Sampah & Recovery — Ganesha Nusa')

@section('content')
@include('components.flash')

<header class="page-head w-full">
  <span class="eyebrow">Pengelolaan Data</span>
  <h1>Sampah &amp; Recovery</h1>
  <p>Pulihkan data yang diarsipkan atau tinjau dampaknya sebelum menghapus permanen.</p>
</header>

<div class="grid w-full gap-6">
  @if($errors->any())
    <div role="alert" class="rounded-xl bg-[var(--danger-050)] p-4 text-[var(--danger)]">
      @foreach($errors->all() as $error)
        <p>{{ $error }}</p>
      @endforeach
    </div>
  @endif

  <section class="card grid gap-4" aria-labelledby="trash-filter-title">
    <h2 id="trash-filter-title" class="text-lg font-semibold">Filter data</h2>
    <form method="GET" action="{{ route('admin.trash.index') }}" class="grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.35fr)_auto]">
      <label class="grid min-w-0 gap-1">
        <span class="label">Jenis data</span>
        <select class="select w-full" name="type">
          <option value="all" @selected($type === 'all')>Semua jenis data</option>
          @foreach($resources as $key => $resource)
            <option value="{{ $key }}" @selected($type === $key)>{{ $resource[1] }}</option>
          @endforeach
        </select>
      </label>
      <label class="grid min-w-0 gap-1">
        <span class="label">Status</span>
        <select class="select w-full" name="state">
          <option value="trash" @selected($state === 'trash')>Di Sampah</option>
          <option value="active" @selected($state === 'active')>Belum dihapus</option>
        </select>
      </label>
      <label class="grid min-w-0 gap-1">
        <span class="label">Cari</span>
        <input class="input w-full" type="search" name="q" value="{{ request('q') }}" maxlength="120" placeholder="Nama atau judul...">
      </label>
      <button class="btn btn--primary min-h-[3.25rem]" type="submit">Tampilkan</button>
    </form>
  </section>

  <section class="card grid gap-4" aria-labelledby="trash-results-title">
    <header class="flex flex-wrap items-center justify-between gap-2">
      <h2 id="trash-results-title" class="text-lg font-semibold">{{ $state === 'trash' ? 'Data di Sampah' : 'Data belum dihapus' }}</h2>
      <span class="badge badge--flat">{{ $total }} data</span>
    </header>

    @forelse($categories as $category => $result)
      <section class="grid gap-3" aria-labelledby="trash-category-{{ $category }}">
        <header class="flex items-center justify-between gap-2 rounded-xl bg-[var(--surface-2)] px-4 py-3">
          <h3 id="trash-category-{{ $category }}" class="text-base! font-semibold">{{ $result['label'] }}</h3>
          <span class="badge badge--flat">{{ $result['records']->total() }} data</span>
        </header>
        <ul class="divide-y divide-[var(--border)] overflow-hidden rounded-xl border border-[var(--border)]">
          @foreach($result['records'] as $record)
            <li class="grid gap-3 p-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
              <div class="flex min-w-0 items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-[var(--surface-2)]" aria-hidden="true">
                  <svg data-icon="{{ $record->trashed() ? 'trash' : 'folder' }}"></svg>
                </span>
                <div class="min-w-0">
                  <strong class="break-words">{{ $record->getAttribute($result['column']) ?: $result['label'].' tanpa nama' }}</strong>
                  <p class="text-sm text-soft">
                    {{ $category === 'exams' ? (\App\Enums\ExamKind::tryFrom($record->getRawOriginal('kind') ?? '')?->label() ?? $result['label']) : $result['label'] }}
                    @if($record->trashed())
                      · Dihapus <time datetime="{{ $record->deleted_at->toIso8601String() }}">{{ $record->deleted_at->format('d/m/Y H:i') }}</time>
                    @endif
                  </p>
                </div>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                @if($record->trashed())
                  <form method="POST" action="{{ route('admin.trash.restore', [$category, $record->id]) }}">
                    @csrf
                    <button class="btn btn--primary btn--sm" type="submit">Pulihkan</button>
                  </form>
                @endif
                <a class="btn btn--ghost btn--sm" href="{{ route('admin.trash.show', [$category, $record->id]) }}">
                  {{ $record->trashed() ? 'Tinjau Hapus Permanen' : 'Tinjau Penghapusan' }}
                </a>
              </div>
            </li>
          @endforeach
        </ul>
        <x-pager :paginator="$result['records']" />
      </section>
    @empty
      <div class="empty py-10">
        <strong>{{ $state === 'trash' ? 'Sampah kosong untuk filter ini' : 'Data tidak ditemukan' }}</strong>
        <p>Pilih jenis data lain atau ubah pencarian.</p>
      </div>
    @endforelse
  </section>
</div>
@endsection
