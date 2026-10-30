@extends('layouts.admin.main')
@section('title', 'Pengaturan Integrasi & Tampilan — Admin Ganesha Nusa')
@section('content')
@php
    $editingMidtrans = old('integration') === 'midtrans';
    $editingMail = old('integration') === 'mail';
    $paymentConfigured = $midtrans['is_production'] ? $productionConfigured : $sandboxConfigured;
    $fieldLabels = [
        'heading_font' => 'Font judul', 'body_font' => 'Font teks',
        'landing' => 'Font landing page', 'member' => 'Font dashboard peserta', 'admin' => 'Font dashboard admin',
        'enabled' => 'Status layanan', 'is_production' => 'Mode pembayaran',
        'sandbox_server_key' => 'Server key sandbox', 'production_server_key' => 'Server key production',
        'smtp_host' => 'Server SMTP', 'smtp_port' => 'Port SMTP', 'smtp_username' => 'Username SMTP',
        'smtp_password' => 'App password SMTP', 'from_address' => 'Email pengirim', 'from_name' => 'Nama pengirim',
    ];
    $actionLabels = ['updated' => 'Pengaturan disimpan', 'test_succeeded' => 'Uji koneksi berhasil', 'test_failed' => 'Uji koneksi gagal'];
@endphp
<header class="page-head">
    <span class="eyebrow">Administrasi</span>
    <h1 class="mt-2!">Pengaturan Integrasi &amp; Tampilan</h1>
    <p>Kelola font website, maintenance, pembayaran, dan pengiriman email. Setiap perubahan membutuhkan password akun admin kamu.</p>
</header>

@if ($errors->any())
    <div class="card mb-6!" role="alert">
        <strong>Pengaturan belum dapat diproses</strong>
        <ul class="mt-2! list-disc pl-5! text-sm">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

@include('Dashboard.Admin.settings._typography')

@include('Dashboard.Admin.settings._maintenance')

<div class="grid gap-6 xl:grid-cols-2">
    <section class="card card--static">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4!">
        <h2 class="text-xl!">Pembayaran Midtrans</h2>
            <span class="badge badge--{{ $midtrans['enabled'] && $paymentConfigured ? 'mint' : 'amber' }} badge--flat">{{ ! $paymentConfigured ? 'Belum dikonfigurasi' : ($midtrans['enabled'] ? 'Pembelian diaktifkan' : 'Pembelian dijeda') }}</span>
        </div>
        <p class="text-sm text-soft mb-5!">Key dapat diganti meskipun masih ada transaksi menunggu pembayaran. Key baru berlaku untuk pesanan baru. Transaksi lama tetap memakai key sebelumnya yang disimpan terenkripsi. Sebelum menghapus key mode aktif, jeda pembelian baru terlebih dahulu.</p>
        <form method="POST" action="{{ route('admin.settings.update', 'midtrans') }}" class="grid gap-5" autocomplete="off">
            @csrf
            @method('PATCH')
            <input type="hidden" name="integration" value="midtrans">
            <input type="hidden" name="version" value="{{ $editingMidtrans ? old('version') : $versions['midtrans'] }}">
            <input type="hidden" name="enabled" value="0">
            <label class="switch"><input type="checkbox" name="enabled" value="1" @checked($editingMidtrans ? old('enabled') : $midtrans['enabled'])><span>Terima pembelian baru melalui Midtrans</span></label>
            <div class="field">
                <label class="label" for="is_production">Mode untuk pembelian baru</label>
                <select class="select" id="is_production" name="is_production">
                    <option value="0" @selected(! ($editingMidtrans ? old('is_production') : $midtrans['is_production']))>Sandbox — simulasi pembayaran</option>
                    <option value="1" @selected($editingMidtrans ? old('is_production') : $midtrans['is_production'])>Production — pembayaran uang sungguhan</option>
                </select>
            </div>
            @foreach (['sandbox' => $sandboxConfigured, 'production' => $productionConfigured] as $mode => $configured)
                <div class="field">
                    <label class="label" for="{{ $mode }}_server_key">Server key {{ ucfirst($mode) }}</label>
                    <p class="text-xs text-soft mb-2!">{{ $configured ? 'Sudah terisi. Kosongkan untuk mempertahankan key yang tersimpan.' : 'Belum terisi.' }}</p>
                    <input class="input" type="password" id="{{ $mode }}_server_key" name="{{ $mode }}_server_key" maxlength="255" autocomplete="new-password" spellcheck="false" placeholder="Masukkan key baru">
                    @if ($configured)
                        <label class="flex items-center gap-2 text-sm mt-2!"><input type="checkbox" name="remove_{{ $mode }}_server_key" value="1"><span>Hapus key {{ $mode }}</span></label>
                    @endif
                </div>
            @endforeach
            <div class="field">
                <label class="label" for="midtrans_password">Password akun admin</label>
                <input class="input" type="password" id="midtrans_password" name="current_password" autocomplete="current-password" required>
            </div>
            <button class="btn btn--primary justify-self-start" type="submit">Simpan pembayaran</button>
        </form>
        <details class="mt-6!">
            <summary class="cursor-pointer text-sm font-semibold">Uji kredensial Midtrans yang tersimpan</summary>
            <form method="POST" action="{{ route('admin.settings.test', 'midtrans') }}" class="grid gap-4 mt-4!">
                @csrf
                <p class="text-sm text-soft">Uji ini memeriksa autentikasi tanpa membuat tagihan. Simpan perubahan sebelum melakukan pengujian.</p>
                <div class="field"><label class="label" for="test_mode">Mode yang diuji</label><select class="select" name="is_production" id="test_mode"><option value="0">Sandbox</option><option value="1">Production</option></select></div>
                <div class="field"><label class="label" for="midtrans_test_password">Password akun admin</label><input class="input" type="password" id="midtrans_test_password" name="current_password" autocomplete="current-password" required></div>
                <button class="btn btn--ghost justify-self-start" type="submit">Uji koneksi Midtrans</button>
            </form>
        </details>
    </section>

    <section class="card card--static">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4!">
            <h2 class="text-xl!">Email &amp; SMTP</h2>
            <span class="badge badge--{{ $mailManaged && ! $mail['enabled'] ? 'amber' : 'mint' }} badge--flat">{{ ! $mailManaged ? 'Konfigurasi server' : ($mail['enabled'] ? 'Aktif' : 'Nonaktif') }}</span>
        </div>
        <p class="text-sm text-soft mb-5!">App password dipakai untuk mengirim email. Jika email dinonaktifkan, verifikasi akun, reset password, dan notifikasi email tidak dapat dikirim. Pengiriman akan menggunakan pengaturan ini setelah disimpan.</p>
        <form method="POST" action="{{ route('admin.settings.update', 'mail') }}" class="grid gap-5" autocomplete="off">
            @csrf
            @method('PATCH')
            <input type="hidden" name="integration" value="mail">
            <input type="hidden" name="version" value="{{ $editingMail ? old('version') : $versions['mail'] }}">
            <input type="hidden" name="enabled" value="0">
            <label class="switch"><input type="checkbox" name="enabled" value="1" @checked($editingMail ? old('enabled') : $mail['enabled'])><span>Aktifkan pengiriman email</span></label>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="field">
                    <label class="label" for="smtp_host">Server SMTP</label>
                    <select class="select" name="smtp_host" id="smtp_host">
                        <option value="">Pilih server SMTP</option>
                        @foreach ($smtpHosts as $host)<option value="{{ $host }}" @selected(($editingMail ? old('smtp_host') : $mail['smtp_host']) === $host)>{{ $host }}</option>@endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="label" for="smtp_port">Koneksi terenkripsi</label>
                    <select class="select" name="smtp_port" id="smtp_port"><option value="465" @selected((int) ($editingMail ? old('smtp_port') : $mail['smtp_port']) === 465)>465 — TLS</option><option value="587" @selected((int) ($editingMail ? old('smtp_port') : $mail['smtp_port']) !== 465)>587 — STARTTLS</option></select>
                </div>
            </div>
            <div class="field"><label class="label" for="smtp_username">Username SMTP</label><input class="input" id="smtp_username" name="smtp_username" value="{{ $editingMail ? old('smtp_username') : $mail['smtp_username'] }}" maxlength="255" autocomplete="off" spellcheck="false"></div>
            <div class="field">
                <label class="label" for="smtp_password">App password SMTP</label>
                <p class="text-xs text-soft mb-2!">{{ $smtpConfigured ? 'Sudah terisi. Kosongkan untuk mempertahankan password yang tersimpan.' : 'Belum terisi. Gunakan app password atau kredensial SMTP dari penyedia email.' }}</p>
                <input class="input" type="password" id="smtp_password" name="smtp_password" maxlength="1024" autocomplete="new-password" spellcheck="false" placeholder="Masukkan app password baru">
                @if ($smtpConfigured)<label class="flex items-center gap-2 text-sm mt-2!"><input type="checkbox" name="remove_smtp_password" value="1"><span>Hapus app password tersimpan</span></label>@endif
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="field"><label class="label" for="from_address">Email pengirim</label><input class="input" type="email" id="from_address" name="from_address" value="{{ $editingMail ? old('from_address') : $mail['from_address'] }}" maxlength="255"></div>
                <div class="field"><label class="label" for="from_name">Nama pengirim</label><input class="input" id="from_name" name="from_name" value="{{ $editingMail ? old('from_name') : $mail['from_name'] }}" maxlength="160"></div>
            </div>
            <div class="field"><label class="label" for="mail_password">Password akun admin</label><input class="input" type="password" id="mail_password" name="current_password" autocomplete="current-password" required></div>
            <button class="btn btn--primary justify-self-start" type="submit">Simpan email</button>
        </form>
        <details class="mt-6!">
            <summary class="cursor-pointer text-sm font-semibold">Kirim email uji ke akun admin</summary>
            <form method="POST" action="{{ route('admin.settings.test', 'mail') }}" class="grid gap-4 mt-4!">
                @csrf
                <p class="text-sm text-soft">Simpan konfigurasi SMTP terlebih dahulu. Email uji hanya dikirim ke alamat email akun admin yang sedang masuk.</p>
                <div class="field"><label class="label" for="mail_test_password">Password akun admin</label><input class="input" type="password" id="mail_test_password" name="current_password" autocomplete="current-password" required></div>
                <button class="btn btn--ghost justify-self-start" type="submit">Kirim email uji</button>
            </form>
        </details>
    </section>
</div>

<section class="card mt-6!">
    <h2 class="mb-4! text-xl!">Riwayat perubahan &amp; pengujian</h2>
    <p class="text-sm text-soft mb-4!">Riwayat mencatat tindakan dan nama pengaturan yang berubah. Nilai kredensial tidak ditampilkan.</p>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Waktu</th><th>Admin</th><th>Integrasi</th><th>Tindakan</th><th>Pengaturan berubah</th></tr></thead>
            <tbody>
                @forelse ($audits as $audit)
                    <tr>
                        <td class="text-sm"><x-local-time :value="$audit->created_at" format="d/m/Y H:i" /></td>
                        <td>{{ $audit->actor_name ?? 'Akun telah dihapus' }}</td>
                        <td>{{ ['midtrans' => 'Midtrans', 'mail' => 'Email', 'typography' => 'Font & Tipografi'][$audit->key] ?? $audit->key }}</td>
                        <td>{{ $actionLabels[$audit->action] ?? $audit->action }}</td>
                        <td class="text-sm text-soft">{{ implode(', ', array_map(fn (string $field): string => $fieldLabels[$field] ?? $field, json_decode($audit->changed_fields, true))) ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-soft">Belum ada perubahan atau pengujian integrasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($audits->hasPages())<div class="exam-pager"><x-pager :paginator="$audits" /></div>@endif
</section>
@endsection
