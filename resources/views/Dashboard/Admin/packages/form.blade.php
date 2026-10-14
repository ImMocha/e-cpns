@extends('layouts.admin.main')
@section('title', ($package->exists ? 'Kelola' : 'Buat').' Paket — Admin Ganesha Nusa')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.packages.index') }}">Paket Belajar</a><span class="crumbs__sep">›</span><span class="crumbs__current">{{ $package->exists ? $package->name : 'Buat Paket' }}</span></nav>
<header class="page-head page-head__row"><div><span class="eyebrow">Pengaturan Paket</span><h1 style="margin-top:.5rem">{{ $package->exists ? 'Kelola Paket' : 'Buat Paket' }}</h1><p>Perubahan ketentuan berlaku untuk aktivasi berikutnya. Paket peserta yang sudah aktif tetap mengikuti isi saat aktivasi.</p></div><a class="btn btn--ghost" href="{{ route('admin.packages.index') }}">Kembali</a></header>
@if ($errors->any())<div class="article-errors question-section" role="alert"><strong>Periksa data berikut:</strong><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
@if ($package->trashed())<div class="card question-section"><span class="badge badge--coral badge--flat">Paket diarsipkan</span><p class="text-soft">Ketentuan tidak dapat diubah. Akses peserta tetap bisa dicabut melalui daftar di bawah.</p></div>@endif
<form method="POST" action="{{ $package->exists ? route('admin.packages.update', $package) : route('admin.packages.store') }}">
@csrf @if ($package->exists) @method('PUT') @endif
<fieldset @disabled($package->trashed()) style="border:0;padding:0;margin:0">
<div class="exam-admin-layout">
    <section class="card question-form-stack">
        <div class="card__title">Ketentuan paket</div>
        <div class="field"><label class="label" for="package-name">Nama paket</label><input class="input" id="package-name" name="name" maxlength="180" value="{{ old('name', $package->name) }}" required></div>
        <div class="field"><label class="label" for="package-description">Deskripsi</label><textarea class="textarea" id="package-description" name="description" rows="4" maxlength="2000">{{ old('description', $package->description) }}</textarea></div>
        <div class="question-fields">
            <div class="field"><label class="label" for="package-price">Harga (Rp)</label><input class="input" id="package-price" type="number" name="price" min="0" max="1000000000" step="1" value="{{ old('price', $package->price) }}" required><p class="field__hint">Isi harga final dalam rupiah, tanpa titik pemisah.</p></div>
            <div class="field"><label class="label" for="package-duration">Masa aktif (hari)</label><input class="input" id="package-duration" type="number" name="duration_days" min="1" max="36500" value="{{ old('duration_days', $package->duration_days) }}"><p class="field__hint">Dihitung sejak aktivasi. Kosong = tanpa batas waktu.</p></div>
            <div class="field"><label class="label" for="package-quota">Total kuota tryout</label><input class="input" id="package-quota" type="number" name="tryout_quota" min="0" max="100000" value="{{ old('tryout_quota', $package->tryout_quota) }}"><p class="field__hint">Kosong = tanpa batas. 0 = tanpa tryout. Latihan tidak memotong kuota.</p></div>
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
        <p class="field__hint">Paket aktif perlu konten yang sudah terbit. Menonaktifkan paket menghentikan aktivasi baru; hak peserta yang sudah aktif tetap berlaku.</p>
        @unless ($package->trashed())<button class="btn btn--primary">Simpan paket</button>@endunless
    </section>
    <div class="question-form-stack">
        <section class="card question-form-stack">
            <div><div class="card__title">Materi &amp; video</div><p class="card__sub">Paket membuka materi premium yang dipilih di sini.</p></div>
            <div class="exam-question-pool">
            @forelse ($materials as $material)
            <label class="exam-pool-item"><input type="checkbox" name="material_ids[]" value="{{ $material->id }}" @checked(in_array($material->id, is_array(old('material_ids', $selectedMaterials)) ? old('material_ids', $selectedMaterials) : []))><span><strong>{{ $material->title }}</strong><span class="text-xs text-soft">{{ $material->section?->name ?? 'Bidang dihapus' }} · {{ $material->type->label() }} · {{ $material->access_level->label() }} · {{ $material->status->label() }}</span></span></label>
            @empty<p class="text-soft">Belum ada materi. <a href="{{ route('admin.materials.create') }}">Tambahkan materi</a>.</p>@endforelse
            </div>
        </section>
        <section class="card question-form-stack">
            <div><div class="card__title">Latihan &amp; tryout</div><p class="card__sub">Sesi premium hanya dapat dimulai lewat paket yang memuatnya.</p></div>
            <div class="exam-question-pool">
            @forelse ($exams as $exam)
            <label class="exam-pool-item"><input type="checkbox" name="exam_ids[]" value="{{ $exam->id }}" @checked(in_array($exam->id, is_array(old('exam_ids', $selectedExams)) ? old('exam_ids', $selectedExams) : []))><span><strong>{{ $exam->title }}</strong><span class="text-xs text-soft">{{ $exam->kind === 'quiz' ? 'Latihan' : 'Tryout' }} · {{ $exam->access_level->label() }} · {{ $exam->status->label() }}</span></span></label>
            @empty<p class="text-soft">Belum ada sesi. <a href="{{ route('admin.exams.create') }}">Buat sesi</a>.</p>@endforelse
            </div>
            <p class="field__hint">Sesi gratis tetap bebas diakses dan tidak memotong kuota paket. Ubah level akses melalui pengaturan sesi bila ingin menjadikannya premium.</p>
        </section>
    </div>
</div>
</fieldset>
</form>
@if ($package->exists)
<section class="card question-section" style="margin-top:1.5rem">
    <div class="card__head"><div><div class="card__title">Paket Peserta</div><div class="card__sub">Aktivasi dan pencabutan akses oleh admin</div></div></div>
    @if ($package->is_active && ! $package->trashed())
    <form class="question-fields question-section" method="POST" action="{{ route('admin.packages.members.store', $package) }}">
        @csrf
        <div class="field"><label class="label" for="grant-email">Email peserta</label><input class="input" type="email" id="grant-email" name="email" maxlength="255" value="{{ old('email') }}" required></div>
        <div class="field"><label class="label" for="grant-note">Alasan pemberian paket</label><input class="input" id="grant-note" name="note" maxlength="500" value="{{ old('note') }}" placeholder="Contoh: akses kelas yang dikonfirmasi admin" required></div>
        <div><button class="btn btn--primary">Berikan paket</button></div>
    </form>
    <p class="text-xs text-soft question-section">Masa aktif dimulai sekarang. Peserta dengan paket yang sama masih aktif tidak bisa menerima aktivasi ganda.</p>
    @elseif (! $package->trashed())<p class="text-soft question-section">Aktifkan paket terlebih dahulu untuk memberikannya kepada peserta.</p>@endif
    <div class="table-wrap"><table class="table"><thead><tr><th>Peserta</th><th>Ketentuan saat aktivasi</th><th>Masa aktif</th><th>Kuota tersisa</th><th>Status</th><th>Catatan</th><th>Aksi</th></tr></thead><tbody>
    @forelse ($grants as $grant)
    <tr><td><strong>{{ $grant->user?->name ?? 'Akun dihapus' }}</strong><p class="text-xs text-soft">{{ $grant->user?->email }}</p></td><td>{{ $grant->name }}<p class="text-xs text-soft">{{ $grant->leaderboard_access->label() }}</p></td><td>{{ $grant->starts_at->format('d/m/Y H:i') }}<br>{{ $grant->expires_at ? 's/d '.$grant->expires_at->format('d/m/Y H:i') : 'Tanpa batas waktu' }}</td><td>{{ $grant->remainingTryouts() ?? 'Tanpa batas' }}</td><td>{{ $grant->statusLabel() }}</td><td>{{ $grant->note }}</td><td>
    @if ($grant->revoked_at === null)
    <form method="POST" action="{{ route('admin.packages.members.destroy', [$package, $grant]) }}" data-content-confirm data-confirm-title="Cabut akses paket?" data-confirm-body="Materi premium dan sesi baru akan terkunci. Riwayat ujian tetap tersimpan.">@csrf @method('DELETE')<button class="btn btn--ghost btn--sm">Cabut akses</button></form>
    @endif</td></tr>
    @empty<tr><td colspan="7" class="text-soft">Belum ada peserta yang menerima paket ini.</td></tr>@endforelse
    </tbody></table></div>
    @if ($grants->hasPages())<div class="exam-pager"><x-pager :paginator="$grants" /></div>@endif
</section>
@endif
@endsection
