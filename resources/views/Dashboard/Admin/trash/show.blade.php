@extends('layouts.admin.main')
@section('title', 'Dampak Penghapusan — Ganesha Nusa')
@section('content')
@include('components.flash')
<header class="page-head page-head__row w-full"><div><span class="eyebrow">Tinjau Data Terkait</span><h1>{{ $title }}</h1><p>Periksa seluruh dampak sebelum melanjutkan.</p></div><a class="btn btn--ghost" href="{{ route('admin.trash.index', ['type' => $type]) }}">Kembali ke Sampah</a></header>
@if($errors->any())<div role="alert" class="mb-4 rounded-xl bg-[var(--danger-050)] p-4 text-[var(--danger)]">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<div class="grid w-full items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(17rem,0.55fr)]">
  <section class="card grid min-w-0 gap-5" aria-labelledby="impact-title">
    <div>
      <h2 id="impact-title" class="text-xl! font-semibold">Dampak hapus permanen</h2>
      <p class="text-sm text-soft">Rincian ini mencakup data aktif dan data di Sampah.</p>
    </div>

    <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-2)] p-4">
      <p class="text-sm text-soft">Data yang dipilih &middot; {{ $rootLabel }}</p>
      <strong class="block break-words">{{ $title ?: $rootLabel.' tanpa nama' }}</strong>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2">
      <h3 class="text-lg! font-semibold">Data terkait yang ikut terhapus</h3>
      <span class="badge badge--flat">{{ $relatedCount }} data terkait</span>
    </div>

    @forelse($groups as $label => $nodes)
      <details open class="min-w-0 overflow-hidden rounded-xl border border-[var(--border)]">
        <summary class="cursor-pointer bg-[var(--surface-2)] px-4 py-3 font-semibold">
          {{ $label }} <span class="badge badge--flat">{{ $nodes->count() }}</span>
        </summary>
        <ul class="m-0! list-none divide-y divide-[var(--border)] border-t border-[var(--border)] p-0! text-sm">
          @foreach($nodes->take(20) as $node)
            <li class="m-0! break-words px-4 py-2">
              @php
                $row = $node['row'];
                $material = $names['material_id']->get($row['material_id'] ?? 0) ?? 'Judul materi tidak tersedia';
                $exam = $names['exam_id']->get($row['exam_id'] ?? 0) ?? 'Judul sesi tidak tersedia';
                $participant = $names['user_id']->get($row['user_id'] ?? 0) ?? 'Nama peserta tidak tersedia';
                $package = $names['package_id']->get($row['package_id'] ?? 0) ?? 'Nama paket tidak tersedia';
                $ownership = $ownerships->get($row['user_package_id'] ?? 0);
              @endphp
              @switch($node['table'])
                @case('material_progress')
                  <strong>{{ $material }}</strong>
                  <span class="block text-soft">Progres belajar &middot; {{ $participant }}</span>
                  @break
                @case('material_user_package')
                @case('exam_user_package')
                  <strong>{{ $node['table'] === 'material_user_package' ? $material : $exam }}</strong>
                  <span class="block text-soft">Akses paket {{ $ownership?->package_name ?? '(nama tidak tersedia)' }} &middot; {{ $ownership?->participant_name ?? 'Nama peserta tidak tersedia' }}</span>
                  @break
                @case('exam_question')
                  <strong>{{ $names['question_id']->get($row['question_id']) ?? 'Judul soal tidak tersedia' }}</strong>
                  <span class="block text-soft">Tautan ke sesi {{ $exam }}</span>
                  @break
                @case('material_package')
                @case('exam_package')
                  <strong>{{ $node['table'] === 'material_package' ? $material : $exam }}</strong>
                  <span class="block text-soft">Tautan ke paket {{ $package }}</span>
                  @break
                @default
                  <strong>{{ $row['title'] ?? $row['name'] ?? $row['code'] ?? $row['exam_title'] ?? $row['package_name'] ?? $label }}</strong>
                  @if(isset($row['user_id']))
                    <span class="block text-soft">{{ $participant }}</span>
                  @endif
              @endswitch
            </li>
          @endforeach
        </ul>
        @if($nodes->count() > 20)
          <p class="px-4 py-2 text-sm text-soft">Dan {{ $nodes->count() - 20 }} data lainnya.</p>
        @endif
      </details>
    @empty
      <p class="rounded-xl border border-[var(--border)] p-4 text-sm text-soft">Tidak ada data terkait yang ikut terhapus. Hanya data yang dipilih yang akan dihapus.</p>
    @endforelse

    @if($groups->has('Relasi soal dalam sesi'))
      <p class="text-sm text-soft">Relasi soal dalam sesi adalah tautannya. Soal yang ikut dihapus dari bank soal tercantum pada kelompok &ldquo;Soal di bank soal&rdquo;.</p>
    @endif
    @if($type === 'users')
      <p class="text-sm text-soft">Referensi pembuat dan pengubah pada konten akan dikosongkan.</p>
    @endif
  </section>
  <section class="card grid min-w-0 gap-4">
    @if($record->trashed())
      <h2 class="text-xl! font-semibold">Pulihkan atau hapus permanen</h2>
      <p class="text-soft">Recovery mengembalikan data yang diarsipkan bersama. Data yang sudah berada di Sampah sebelumnya tetap diarsipkan.</p>
      <form method="POST" action="{{ route('admin.trash.restore', [$type, $record->id]) }}" class="m-0">@csrf<button class="btn btn--primary w-full" type="submit">Pulihkan Data</button></form>
      <div class="border-t border-[var(--border)]"></div>
      <p class="font-semibold text-[var(--danger)]">Hapus permanen tidak dapat dibatalkan.</p>
      <p class="mt-2 text-sm">Seluruh data pada rincian dampak akan hilang, termasuk riwayat dan hak akses yang tercantum.</p>
      <form method="POST" action="{{ route('admin.trash.destroy', [$type, $record->id]) }}" class="grid gap-3">
        @csrf @method('DELETE')<input type="hidden" name="fingerprint" value="{{ $fingerprint }}">
        <label for="confirmation">Ketik <strong>HAPUS PERMANEN</strong></label><input class="input w-full" id="confirmation" name="confirmation" required autocomplete="off" placeholder="HAPUS PERMANEN">
        <button class="btn w-full bg-red-700 text-white hover:bg-red-800" type="submit">Hapus Permanen</button>
      </form>
    @else
      <h2 class="text-xl! font-semibold">Pindahkan ke Sampah</h2>
      <p class="text-soft">Data bisa dipulihkan sebelum dihapus permanen.</p>
      @if(in_array($type, ['topics', 'sections']))
        <p class="text-soft">Materi, sesi, dan konten terkait yang memenuhi syarat ikut diarsipkan. Progres belajar dan relasi akses baru dihapus saat penghapusan permanen.</p>
      @else
        <p class="text-soft">Tahap ini mengarsipkan data yang dipilih. Rincian di panel dampak berlaku saat penghapusan permanen.</p>
      @endif
      <form method="POST" action="{{ route('admin.trash.archive', [$type, $record->id]) }}" class="mt-5">@csrf<button class="btn btn--primary w-full" type="submit">Pindahkan ke Sampah</button></form>
    @endif
  </section>
</div>
@endsection
