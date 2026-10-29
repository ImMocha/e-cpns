    <section class="card card--static mb-6!" id="maintenance">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4!">
            <h2 class="text-xl!">Mode maintenance</h2>
            <span class="badge badge--{{ $maintenanceEnabled ? 'amber' : 'mint' }} badge--flat">{{ $maintenanceEnabled ? 'Maintenance aktif' : 'Website online' }}</span>
        </div>
        <p class="text-sm text-soft mb-4!">Saat aktif, halaman publik dan area peserta menampilkan halaman pemeliharaan. Login, panel admin, dan notifikasi pembayaran tetap dapat diakses.</p>
        <p class="text-sm text-soft mb-5!">Aktifkan saat tidak ada ujian berlangsung karena peserta tidak dapat membuka atau mengirim jawaban selama maintenance. Pekerjaan yang diproses lewat antrean ikut dijeda selama pemeliharaan.</p>
        <form method="POST" action="{{ route('admin.settings.maintenance.update') }}" class="grid gap-5">
            @csrf
            @method('PATCH')
            <input type="hidden" name="integration" value="maintenance">
            <input type="hidden" name="enabled" value="0">
            <label class="switch"><input type="checkbox" name="enabled" value="1" @checked(old('integration') === 'maintenance' ? old('enabled') : $maintenanceEnabled)><span>Aktifkan mode maintenance</span></label>
            @error('enabled')<p class="text-sm" role="alert">{{ $message }}</p>@enderror
            <div class="field">
                <label class="label" for="maintenance-password">Password akun admin</label>
                <input class="input" type="password" id="maintenance-password" name="current_password" autocomplete="current-password" required @error('current_password') aria-invalid="true" aria-describedby="maintenance-password-error" @enderror>
                @error('current_password')<p id="maintenance-password-error" class="text-sm" role="alert">{{ $message }}</p>@enderror
            </div>
            <button class="btn btn--primary justify-self-start" type="submit">Simpan status maintenance</button>
        </form>
    </section>

