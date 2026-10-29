{{--
    Formulir bersama untuk tambah / edit pengguna.

    $user    App\Models\User   ( pada create: model kosong )
    $roles   App\Enums\UserRole[]
    $statuses App\Enums\UserStatus[]
    $mode    'create' | 'edit'

    Autentikasi(csrf) ditangani layouts/base, jadi form tidak perlu @csrf manual.
--}}
@php
    $isEdit = $mode === 'edit';
    $action = $isEdit ? route('admin.users.update', $user) : route('admin.users.store');
    $err = fn (string $key) => $errors->first($key);
@endphp

<form method="POST" action="{{ $action }}" class="panes panes--wide" style="--gap:1.5rem" novalidate>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    {{-- ============ KOLOM KIRI ============ --}}
    <div class="stack" style="--stack:1.5rem">

        {{-- Identitas --}}
        <section class="card">
            <div class="card__head">
                <div>
                    <div class="card__title">Identitas Akun</div>
                    <div class="card__sub">Data dasar yang dipakai untuk masuk ke platform</div>
                </div>
                <span class="badge badge--ink badge--flat"><svg data-icon="user"></svg> Wajib</span>
            </div>

            <div class="stack" style="--stack:1.15rem">
                <div class="field{{ $errors->has('name') ? ' is-invalid' : '' }}">
                    <label class="label" for="name">Nama Lengkap</label>
                    <div class="input-group">
                        <span class="input-group__icon"><svg data-icon="user"></svg></span>
                        <input class="input" type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
                               placeholder="Contoh: Rani Kusuma" required autocomplete="name" autofocus>
                    </div>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('name') ?? 'Wajib diisi.' }}</span></span>
                </div>

                <div class="field{{ $errors->has('email') ? ' is-invalid' : '' }}">
                    <label class="label" for="email">Email</label>
                    <div class="input-group">
                        <span class="input-group__icon"><svg data-icon="mail"></svg></span>
                        <input class="input" type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                               placeholder="nama@email.com" required autocomplete="off">
                    </div>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('email') ?? 'Format email belum benar.' }}</span></span>
                </div>

                <div class="grid grid-2" style="--gap:1.15rem">
                    <div class="field{{ $errors->has('password') ? ' is-invalid' : '' }}">
                        <label class="label" for="password">
                            Password
                            @if ($isEdit)<span class="label__opt">— kosongkan bila tidak diubah</span>@endif
                        </label>
                        <div class="input-group">
                            <span class="input-group__icon"><svg data-icon="lock"></svg></span>
                            <input class="input" type="password" id="password" name="password"
                                   placeholder="{{ $isEdit ? 'Biarkan kosong untuk tidak mengubah' : 'Minimal 8 karakter' }}"
                                   {{ $isEdit ? '' : 'required' }} minlength="8" autocomplete="new-password"
                                   style="padding-right:3.25rem">
                            <button class="input-group__action" type="button" data-toggle-password="#password" aria-label="Tampilkan sandi">
                                <svg data-icon="eye"></svg>
                            </button>
                        </div>
                        <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('password') ?? 'Minimal 8 karakter.' }}</span></span>
                    </div>

                    <div class="field{{ $errors->has('password_confirmation') ? ' is-invalid' : '' }}">
                        <label class="label" for="password_confirmation">Konfirmasi Password</label>
                        <div class="input-group">
                            <span class="input-group__icon"><svg data-icon="key"></svg></span>
                            <input class="input" type="password" id="password_confirmation" name="password_confirmation"
                                   placeholder="Ulangi password" {{ $isEdit ? '' : 'required' }} minlength="8" autocomplete="new-password">
                        </div>
                        <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('password_confirmation') ?? 'Tidak sama dengan password.' }}</span></span>
                    </div>
                </div>

                <label class="check">
                    <input type="checkbox" name="email_verified" value="1"
                           @checked(old('email_verified', $user->email_verified_at !== null))>
                    <span>Tandai email sebagai terverifikasi</span>
                </label>
            </div>
        </section>

        {{-- Kontak & instansi --}}
        <section class="card">
            <div class="card__head">
                <div>
                    <div class="card__title">Kontak &amp; Instansi</div>
                    <div class="card__sub">Opsional, dipakai untuk mengenali peserta dan mengirim pengingat</div>
                </div>
                <span class="badge badge--flat">Opsional</span>
            </div>

            <div class="stack" style="--stack:1.15rem">
                <div class="field{{ $errors->has('phone') ? ' is-invalid' : '' }}">
                    <label class="label" for="phone">Nomor Telepon</label>
                    <div class="input-group">
                        <span class="input-group__icon"><svg data-icon="smartphone"></svg></span>
                        <input class="input" type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                               placeholder="08xx xxxx xxxx" inputmode="tel">
                    </div>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('phone') ?? 'Format nomor telepon tidak dikenali.' }}</span></span>
                </div>

                <div class="field{{ $errors->has('institution') ? ' is-invalid' : '' }}">
                    <label class="label" for="institution">Instansi / Asal Lengkap</label>
                    <div class="input-group">
                        <span class="input-group__icon"><svg data-icon="building"></svg></span>
                        <input class="input" type="text" id="institution" name="institution" value="{{ old('institution', $user->institution) }}"
                               placeholder="Contoh: Universitas Indonesia">
                    </div>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('institution') ?? 'Maksimal 120 karakter.' }}</span></span>
                </div>

                <div class="field{{ $errors->has('address') ? ' is-invalid' : '' }}">
                    <label class="label" for="address">Alamat Lengkap</label>
                    <textarea class="textarea" id="address" name="address" rows="3"
                              placeholder="Jalan, kota, provinsi">{{ old('address', $user->address) }}</textarea>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('address') ?? 'Maksimal 180 karakter.' }}</span></span>
                </div>
            </div>
        </section>
    </div>

    {{-- ============ KOLOM KANAN ============ --}}
    <aside class="stack" style="--stack:1.25rem">

        <section class="card card--pop">
            <span class="card__icon ico--primary"><svg data-icon="shield"></svg></span>
            <h2 class="card__title" style="margin-top:.75rem;font-size:var(--fs-md)">Hak Akses</h2>
            <p class="text-sm text-soft" style="margin-top:.4rem">Menentukan halaman mana yang bisa dibuka akun ini.</p>

            <div class="stack" style="--stack:1rem;margin-top:1.25rem">
                @foreach ($roles as $role)
                    <label class="card card--tight card--dashed flex items-center gap-3" style="cursor:pointer">
                        <span class="check check--radio" style="flex:0 0 auto">
                            <input type="radio" name="role" value="{{ $role->value }}"
                                   @checked(old('role', $user->role?->value ?? 'user') === $role->value)>
                        </span>
                        <span class="tile__glyph {{ $role->value === 'admin' ? 'ico--amber' : 'ico--primary' }}"
                              style="width:42px;height:42px;border-radius:13px;flex:0 0 42px">
                            <svg data-icon="{{ $role->value === 'admin' ? 'shield' : 'user' }}"></svg>
                        </span>
                        <div style="flex:1;min-width:0">
                            <strong style="display:block;color:var(--ink);font-size:var(--fs-sm)">{{ $role->label() }}</strong>
                            <span class="text-xs text-soft">
                                {{ $role->value === 'admin' ? 'Akses penuh Admin Panel' : 'Hanya halaman belajar dan tryout' }}
                            </span>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('role')
                <span class="field__error" style="margin-top:.6rem"><svg data-icon="alert"></svg><span>{{ $message }}</span></span>
            @enderror

            <div class="field" style="margin-top:1.25rem">
                <label class="label" for="status">Status Akun</label>
                <select class="select" id="status" name="status">
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $user->status?->value ?? 'active') === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                <span class="field__hint">Akun nonaktif tidak bisa masuk sampai diaktifkan kembali.</span>
            </div>
            @error('status')
                <span class="field__error" style="margin-top:.5rem"><svg data-icon="alert"></svg><span>{{ $message }}</span></span>
            @enderror
        </section>

        <section class="card card--dashed">
            <div class="card__title" style="font-size:var(--fs-base)">Ringkasan</div>
            <div class="kv" style="margin-top:.85rem">
                <div class="kv__row"><span class="kv__key">ID akun</span><span class="kv__val">{{ $isEdit ? '#'.$user->id : 'otomatis' }}</span></div>
                <div class="kv__row"><span class="kv__key">Inisial avatar</span><span class="kv__val">{{ $user->exists ? $user->initials() : '—' }}</span></div>
                <div class="kv__row"><span class="kv__key">Login terakhir</span><span class="kv__val"><x-local-time :value="$isEdit ? $user->last_login_at : null" :fallback="$isEdit ? 'Belum pernah' : '—'" /></span></div>
                <div class="kv__row"><span class="kv__key">Terdaftar</span><span class="kv__val"><x-local-time :value="$isEdit ? $user->created_at : null" format="d M Y" /></span></div>
            </div>
        </section>

        <div class="btn-row" style="--gap:.65rem">
            <button class="btn btn--primary btn--block" type="submit">
                {{ $isEdit ? 'Simpan Perubahan' : 'Buat Akun' }}
                <span class="btn__icon"><svg data-icon="check"></svg></span>
            </button>
            <a class="btn btn--ghost btn--block" href="{{ route('admin.users.index') }}">Batal</a>
        </div>
    </aside>
</form>

@if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.toast === 'function') {
                window.toast('Ada {{ $errors->count() }} isian yang perlu diperbaiki.', 'error', 4200);
            }
        });
    </script>
@endif
