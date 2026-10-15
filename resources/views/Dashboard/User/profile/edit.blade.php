@extends('layouts.user.main')

@section('title', 'Profil Saya — Ganesha Nusa CPNS Online')

@section('content')
    @include('components.flash')

    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('dashboard') }}">Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">Profil Saya</span>
    </nav>

    <header class="page-head">
      <h1>Profil Saya</h1>
      <p>Kelola informasi akun, keamanan, dan preferensi notifikasi kamu.</p>
    </header>

    <section class="card card--pop pattern-dots-primary" style="margin-bottom:1.5rem;overflow:hidden">
      <div class="flex items-center gap-4 flex-wrap" style="position:relative">
        @if ($avatarUrl)
          <img src="{{ $avatarUrl }}" alt="Foto profil {{ $user->name }}" width="104" height="104"
               style="width:104px;height:104px;flex:0 0 104px;border:2px solid var(--ink);border-radius:50%;object-fit:cover">
        @else
          <span class="avatar avatar--lg avatar--{{ $user->avatarTone() }}" style="width:104px;height:104px;flex-basis:104px;font-size:2.5rem">{{ $user->initials() }}</span>
        @endif
        <div style="flex:1;min-width:220px">
          <h2 style="font-size:var(--fs-xl)">{{ $user->name }}</h2>
          <p class="text-soft">{{ $user->email }}</p>
          <div class="flex items-center gap-2 flex-wrap" style="margin-top:.75rem">
            <span class="badge badge--ink badge--flat">{{ $user->role->label() }}</span>
            @if ($user->hasVerifiedEmail())
              <span class="badge badge--mint badge--flat"><svg data-icon="check"></svg> Email terverifikasi</span>
            @else
              <a class="badge badge--amber badge--flat" href="{{ route('verification.notice') }}">Verifikasi email</a>
            @endif
            <span class="badge badge--primary badge--flat">Bergabung {{ $user->created_at->translatedFormat('M Y') }}</span>
          </div>
        </div>
        <div class="kv" style="min-width:200px">
          <div class="kv__row"><span class="kv__key">Status akun</span><span class="kv__val">{{ $user->status->label() }}</span></div>
          <div class="kv__row"><span class="kv__key">Terdaftar</span><span class="kv__val">{{ $user->created_at->format('d/m/Y') }}</span></div>
        </div>
      </div>
    </section>

    <div class="panes" style="--gap:1.5rem" id="pengaturan">
      <div class="stack" style="--stack:1.5rem">
        <form class="card card--static" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
          @csrf
          @method('PATCH')
          <div class="card__head">
            <div><div class="card__title">Informasi Profil</div><div class="card__sub">Data akun dan profil belajarmu</div></div>
            <span class="stat__icon" style="width:42px;height:42px;flex-basis:42px;border-radius:12px"><svg data-icon="user"></svg></span>
          </div>

          <div class="field{{ $errors->has('avatar') ? ' is-invalid' : '' }}" style="margin-bottom:1rem">
            <label class="label" for="avatar">Foto Profil <span class="label__opt">(JPG, PNG, atau WebP; maks. 2 MB)</span></label>
            <input class="input" type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp">
            <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('avatar') }}</span></span>
          </div>

          <div class="form-grid">
            <div class="field{{ $errors->has('name') ? ' is-invalid' : '' }}">
              <label class="label" for="name">Nama Lengkap</label>
              <input class="input" type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="120" autocomplete="name">
              <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('name') }}</span></span>
            </div>
            <div class="field">
              <label class="label" for="email">Email</label>
              <input class="input" type="email" id="email" value="{{ $user->email }}" readonly aria-describedby="email-hint">
              <span class="field__hint" id="email-hint">Email tidak bisa diubah sendiri. Hubungi admin bila perlu.</span>
            </div>
            <x-province-select :value="$user->province?->value" />
            <div class="field{{ $errors->has('phone') ? ' is-invalid' : '' }}">
              <label class="label" for="phone">Nomor Telepon</label>
              <input class="input" type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="30" autocomplete="tel">
              <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('phone') }}</span></span>
            </div>
            <div class="field{{ $errors->has('institution') ? ' is-invalid' : '' }}">
              <label class="label" for="institution">Instansi / Asal</label>
              <input class="input" type="text" id="institution" name="institution" value="{{ old('institution', $user->institution) }}" maxlength="120">
              <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('institution') }}</span></span>
            </div>
            <div class="field span-2{{ $errors->has('address') ? ' is-invalid' : '' }}">
              <label class="label" for="address">Alamat Domisili</label>
              <textarea class="textarea" id="address" name="address" rows="2" maxlength="180">{{ old('address', $user->address) }}</textarea>
              <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('address') }}</span></span>
            </div>
            <div class="field span-2{{ $errors->has('bio') ? ' is-invalid' : '' }}">
              <label class="label" for="bio">Tentang Saya <span class="label__opt">(opsional)</span></label>
              <textarea class="textarea" id="bio" name="bio" rows="3" maxlength="500" placeholder="Ceritakan singkat target dan motivasimu belajar.">{{ old('bio', $user->bio) }}</textarea>
              <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('bio') }}</span></span>
            </div>
          </div>

          <div class="btn-row" style="margin-top:1.5rem">
            <button class="btn btn--primary" type="submit">Simpan Profil</button>
          </div>
        </form>

        <form class="card" method="POST" action="{{ route('profile.password.update') }}">
          @csrf
          @method('PUT')
          <div class="card__head">
            <div><div class="card__title">Ubah Kata Sandi</div><div class="card__sub">Masukkan kata sandi saat ini untuk melanjutkan</div></div>
            <span class="stat__icon ico--violet" style="width:42px;height:42px;flex-basis:42px;border-radius:12px"><svg data-icon="shield"></svg></span>
          </div>
          <div class="stack" style="--stack:1rem">
            <div class="field{{ $errors->has('current_password') ? ' is-invalid' : '' }}">
              <label class="label" for="current_password">Kata Sandi Saat Ini</label>
              <input class="input" type="password" id="current_password" name="current_password" required autocomplete="current-password">
              <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('current_password') }}</span></span>
            </div>
            <div class="field{{ $errors->has('password') ? ' is-invalid' : '' }}">
              <label class="label" for="password">Kata Sandi Baru</label>
              <input class="input" type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
              <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('password') }}</span></span>
            </div>
            <div class="field">
              <label class="label" for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
              <input class="input" type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password">
            </div>
          </div>
          <div class="btn-row" style="margin-top:1.25rem">
            <button class="btn btn--primary" type="submit">Perbarui Kata Sandi</button>
          </div>
        </form>
      </div>

      <aside class="stack" style="--stack:1.5rem">
        <form class="card" method="POST" action="{{ route('profile.notifications.update') }}">
          @csrf
          @method('PATCH')
          <div class="card__head">
            <div><div class="card__title">Notifikasi Email</div><div class="card__sub">Pilih informasi yang dikirim ke emailmu. Email keamanan akun tetap dikirim.</div></div>
          </div>
          <div class="stack" style="--stack:1rem">
            <label class="switch"><input type="checkbox" name="notifications[new_tryout]" value="1" @checked(old('notifications.new_tryout', $notificationPreferences['new_tryout']))><span>Pengingat tryout baru</span></label>
            <label class="switch"><input type="checkbox" name="notifications[updated_materials]" value="1" @checked(old('notifications.updated_materials', $notificationPreferences['updated_materials']))><span>Materi diperbarui</span></label>
            <label class="switch"><input type="checkbox" name="notifications[payment_updates]" value="1" @checked(old('notifications.payment_updates', $notificationPreferences['payment_updates']))><span>Status pembayaran dan invoice</span></label>
            <label class="switch"><input type="checkbox" name="notifications[package_recommendations]" value="1" @checked(old('notifications.package_recommendations', $notificationPreferences['package_recommendations']))><span>Rekomendasi paket belajar</span></label>
            <label class="switch"><input type="checkbox" name="notifications[platform_announcements]" value="1" @checked(old('notifications.platform_announcements', $notificationPreferences['platform_announcements']))><span>Berita dan pengumuman platform</span></label>
          </div>
          <button class="btn btn--ghost btn--block" type="submit" style="margin-top:1.25rem">Simpan Preferensi</button>
        </form>

        <section class="card card--tight" style="border-color:var(--danger);box-shadow:4px 4px 0 #F5C6C7">
          <div class="flex items-center gap-3">
            <svg data-icon="alert" style="width:22px;height:22px;color:var(--danger)"></svg>
            <div><strong style="display:block;color:var(--ink);font-family:var(--font-display)">Hapus akun</strong><span class="text-xs text-soft">Akun akan dinonaktifkan dan sesi kamu diakhiri.</span></div>
          </div>
          <form method="POST" action="{{ route('profile.destroy') }}" style="margin-top:1rem">
            @csrf
            @method('DELETE')
            <div class="field{{ $errors->has('delete_password') ? ' is-invalid' : '' }}" style="margin-bottom:.8rem">
              <label class="label" for="delete_current_password">Konfirmasi Kata Sandi</label>
              <input class="input" type="password" id="delete_current_password" name="delete_password" required autocomplete="current-password">
              <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('delete_password') }}</span></span>
            </div>
            <label class="check{{ $errors->has('confirm_delete') ? ' is-invalid' : '' }}">
              <input type="checkbox" name="confirm_delete" value="1" required>
              <span>Saya paham akun saya akan dinonaktifkan.</span>
            </label>
            @error('confirm_delete')<span class="field__error" style="display:flex"><svg data-icon="alert"></svg><span>{{ $message }}</span></span>@enderror
            <button class="btn btn--ghost btn--block" type="submit" style="margin-top:1rem;border-color:var(--danger);color:var(--danger)">Hapus Akun Saya</button>
          </form>
        </section>
      </aside>
    </div>
@endsection
