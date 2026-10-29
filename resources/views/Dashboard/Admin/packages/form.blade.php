@extends('layouts.admin.main')
@section('title', ($package->exists ? 'Kelola' : 'Buat').' Paket — Admin Ganesha Nusa')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.packages.index') }}">Paket Belajar</a><span class="crumbs__sep">›</span><span class="crumbs__current">{{ $package->exists ? $package->name : 'Buat Paket' }}</span></nav>
<header class="page-head page-head__row"><div><span class="eyebrow">Pengaturan Paket</span><h1 style="margin-top:.5rem">{{ $package->exists ? 'Kelola Paket' : 'Buat Paket' }}</h1><p>Perubahan ketentuan berlaku untuk aktivasi berikutnya. Paket peserta yang sudah aktif tetap mengikuti isi saat aktivasi.</p></div><a class="btn btn--ghost" href="{{ route('admin.packages.index') }}">Kembali</a></header>
@if ($errors->any())<div class="article-errors question-section" role="alert"><strong>Periksa data berikut:</strong><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
@if ($package->trashed())<div class="card question-section"><span class="badge badge--coral badge--flat">Paket diarsipkan</span><p class="text-soft">Ketentuan tidak dapat diubah. Akses peserta tetap bisa dicabut melalui daftar di bawah.</p></div>@endif
@if ($package->exists && ! $package->trashed() && $package->is_active && ! $contentAvailable)
<div class="card question-section" role="status"><strong>Konten paket belum tersedia</strong><p class="text-sm text-soft" style="margin-top:.5rem">Paket ini belum bisa dibeli karena kosong atau masih memuat konten yang sudah dihapus, belum terbit, atau tidak aktif. Pilih kembali materi dan sesi yang tersedia, sesuaikan deskripsinya, lalu simpan paket.</p></div>
@endif
<form data-package-tier-form method="POST" action="{{ $package->exists ? route('admin.packages.update', $package) : route('admin.packages.store') }}">
@csrf @if ($package->exists) @method('PUT') @endif
<fieldset @disabled($package->trashed()) style="border:0;padding:0;margin:0">
<div class="exam-admin-layout">
    <section class="card question-form-stack">
        <div class="card__title">Ketentuan paket</div>
        <div class="field">
            <label class="label" for="package-tier">Jenis paket dan benefit</label>
            <select class="input" id="package-tier" name="tier" data-package-tier>
                <option value="">Paket khusus (ketentuan manual)</option>
                @foreach (\App\Enums\PackageTier::cases() as $tier)
                    <option value="{{ $tier->value }}" @selected(old('tier', $package->tier?->value) === $tier->value)>{{ $tier->label() }}</option>
                @endforeach
            </select>
            <p class="field__hint">Benefit, kuota percobaan, token, dan perangkingan otomatis mengikuti jenis paket. Harga serta masa aktif dapat diatur sendiri.</p>
        </div>
        @foreach (\App\Enums\PackageTier::cases() as $tier)
            <div data-tier-benefits="{{ $tier->value }}" @if (old('tier', $package->tier?->value) !== $tier->value) hidden @endif>
                <strong>{{ $tier->label() }}</strong>
                <ul>@foreach ($tier->benefits() as $benefit)<li>{{ $benefit }}</li>@endforeach</ul>
                <p class="field__hint">{{ $tier === \App\Enums\PackageTier::Premium ? 'Seluruh materi, latihan soal, dan tryout terbit otomatis terbuka, termasuk konten baru. Tidak perlu memilih konten satu per satu.' : 'Tanpa akses materi dan latihan soal. Tidak perlu memilih konten. Peserta memilih tryout setelah paket aktif menggunakan 1 token Reguler atau 3 token Bundling.' }}</p>
            </div>
        @endforeach
        <div class="field"><label class="label" for="package-name">Nama paket</label><input class="input" id="package-name" name="name" maxlength="180" value="{{ old('name', $package->name) }}" required></div>
        <div class="field"><label class="label" for="package-description">Deskripsi</label><textarea class="textarea" id="package-description" name="description" rows="4" maxlength="2000">{{ old('description', $package->description) }}</textarea></div>
        <div class="question-fields">
            <div class="field"><label class="label" for="package-original-price">Harga diskon/coret (Rp)</label><input class="input" id="package-original-price" type="number" name="original_price" min="0" max="1000000000" step="1" value="{{ old('original_price', $package->original_price) }}"><p class="field__hint">Opsional. Harga lebih tinggi yang dicoret, misalnya 100000. Kosongkan jika tanpa diskon.</p></div>
            <div class="field"><label class="label" for="package-price">Harga sebenarnya (Rp)</label><input class="input" id="package-price" type="number" name="price" min="0" max="1000000000" step="1" value="{{ old('price', $package->price) }}" required><p class="field__hint">Harga yang dibayar peserta, misalnya 80000. Isi rupiah tanpa titik pemisah.</p></div>
            <div class="field"><label class="label" for="package-duration">Masa aktif (hari)</label><input class="input" id="package-duration" type="number" name="duration_days" min="1" max="36500" value="{{ old('duration_days', $package->duration_days) }}"><p class="field__hint">Dihitung sejak aktivasi. Kosong = tanpa batas waktu.</p></div>
            <div class="field"><label class="label" for="package-quota">Total kuota percobaan tryout</label><input class="input" id="package-quota" type="number" name="tryout_quota" min="0" max="100000" value="{{ old('tryout_quota', $package->tryout_quota) }}"><p class="field__hint">Kosong = tanpa batas. 0 = tanpa tryout. Latihan Soal tidak memotong kuota.</p></div>
            <div class="field"><label class="label" for="package-tokens">Token pilihan tryout</label><input class="input" id="package-tokens" type="number" name="tryout_tokens" min="0" max="100000" value="{{ old('tryout_tokens', $package->tryout_tokens) }}" required><p class="field__hint">1 token membuka 1 tryout premium pilihan peserta. Untuk Reguler: 1 token dan total kuota 2 percobaan. Isi 0 jika tanpa token. Sesi yang dipilih di konten paket tetap langsung terbuka tanpa token.</p></div>
            <div class="field"><label class="label" for="package-position">Urutan katalog</label><input class="input" id="package-position" type="number" name="position" min="0" max="65535" value="{{ old('position', $package->position) }}" required></div>
        </div>
        <div class="field">
            <label class="label" for="package-leaderboard">Akses leaderboard</label>
            <select class="input" id="package-leaderboard" name="leaderboard_access" required>
                @foreach (\App\Enums\LeaderboardAccess::cases() as $leaderboardOption)
                    <option value="{{ $leaderboardOption->value }}" @selected(old('leaderboard_access', $package->leaderboard_access->value) === $leaderboardOption->value)>{{ $leaderboardOption->label() }}</option>
                @endforeach
            </select>
            <p class="field__hint">Lokal mengikuti provinsi peserta. Akses nasional mencakup lokal juga. Berlaku selama paket aktif, sesuai ketentuan saat aktivasi.</p>
        </div>
        <input type="hidden" name="is_active" value="0"><label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $package->is_active))> Aktif dan tampil di katalog peserta</label>
        <p class="field__hint">Reguler dan Bundling dapat diaktifkan tanpa konten. Paket khusus mengikuti konten yang dipilih. Menonaktifkan paket menghentikan aktivasi baru; hak peserta yang sudah aktif tetap berlaku.</p>
        @unless ($package->trashed())<button class="btn btn--primary">Simpan paket</button>@endunless
    </section>
    <div class="question-form-stack">
        @include('Dashboard.Admin.packages._content-picker', ['pickerType' => 'materials', 'items' => $materials, 'selectedIds' => $selectedMaterials, 'availableIds' => $availableMaterialIds])
        @include('Dashboard.Admin.packages._content-picker', ['pickerType' => 'exams', 'items' => $exams, 'selectedIds' => $selectedExams, 'availableIds' => $availableExamIds])
    </div>
</div>
</fieldset>
</form>
@if ($package->exists)
<div class="btn-row question-section" style="margin-top:1.5rem">
    @include('Dashboard.Admin.packages._actions')
</div>
<section class="card question-section" style="margin-top:1.5rem">
    <div class="card__head"><div><h2 class="card__title">Paket Peserta</h2><p class="card__sub">Daftar akses dari pembelian atau pemberian manual oleh admin.</p></div><span class="badge badge--primary badge--flat">{{ $grants->total() }} akses</span></div>
    @if ($package->is_active && ! $package->trashed())
    <details class="question-section" @if ($errors->hasAny(['email', 'note'])) open @endif>
    <summary class="cursor-pointer text-sm font-bold">Berikan akses manual</summary>
    <p class="text-sm text-soft" style="margin-top:.75rem">Gunakan untuk bonus atau kelas khusus. Masukkan email peserta terdaftar dan alasan pemberian akses.</p>
    <form class="question-fields question-section" style="margin-top:1rem" method="POST" action="{{ route('admin.packages.members.store', $package) }}">
        @csrf
        <div class="field"><label class="label" for="grant-email">Email peserta</label><input class="input" type="email" id="grant-email" name="email" maxlength="255" value="{{ old('email') }}" required></div>
        <div class="field"><label class="label" for="grant-note">Alasan pemberian paket</label><input class="input" id="grant-note" name="note" maxlength="500" value="{{ old('note') }}" placeholder="Contoh: akses kelas yang dikonfirmasi admin" required></div>
        <div><button class="btn btn--primary">Berikan paket</button></div>
    </form>
    <p class="text-xs text-soft">Masa aktif dimulai saat akses diberikan. Paket yang sama tidak dapat diberikan dua kali selama masih aktif.</p>
    </details>
    @elseif (! $package->trashed())<p class="text-soft question-section">Aktifkan paket terlebih dahulu untuk memberikannya kepada peserta.</p>@endif
    @if ($grants->isNotEmpty())
    <div class="table-wrap"><table class="table"><thead><tr><th>Peserta</th><th>Ketentuan saat aktivasi</th><th>Masa aktif</th><th>Kuota tersisa</th><th>Status</th><th>Catatan</th><th>Aksi</th></tr></thead><tbody>
    @forelse ($grants as $grant)
    <tr><td><strong>{{ $grant->user?->name ?? 'Akun dihapus' }}</strong><p class="text-xs text-soft">{{ $grant->user?->email }}</p></td><td>{{ $grant->name }}<p class="text-xs text-soft">{{ $grant->leaderboard_access->label() }}</p></td><td><x-local-time :value="$grant->starts_at" format="d/m/Y H:i" /><br>@if ($grant->expires_at)s/d <x-local-time :value="$grant->expires_at" format="d/m/Y H:i" />@else Tanpa batas waktu @endif</td><td>{{ $grant->remainingTryouts() ?? 'Tanpa batas' }}</td><td>{{ $grant->statusLabel() }}</td><td>{{ $grant->note }}</td><td>
    @if ($grant->revoked_at === null)
    <form method="POST" action="{{ route('admin.packages.members.destroy', [$package, $grant]) }}" data-content-confirm data-confirm-title="Cabut akses paket?" data-confirm-body="Materi premium dan sesi baru akan terkunci. Riwayat ujian tetap tersimpan.">@csrf @method('DELETE')<button class="btn btn--ghost btn--sm">Cabut akses</button></form>
    @endif</td></tr>
    @empty<tr><td colspan="7" class="text-soft">Belum ada peserta yang menerima paket ini.</td></tr>@endforelse
    </tbody></table></div>
    @if ($grants->hasPages())<div class="exam-pager"><x-pager :paginator="$grants" /></div>@endif
    @else
    <p class="text-sm text-soft">Belum ada peserta yang menerima paket ini. Pembelian yang berhasil akan menambahkan akses peserta secara otomatis.</p>
    @endif
</section>
@endif
@endsection
