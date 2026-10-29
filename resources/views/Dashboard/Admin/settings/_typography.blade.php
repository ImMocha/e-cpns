@php
    $editingTypography = old('integration') === 'typography';
    $fontSettings = $typography['settings'];
    $selectedHeading = $editingTypography ? old('heading_font') : $fontSettings['heading_font'];
    $selectedBody = $editingTypography ? old('body_font') : $fontSettings['body_font'];
@endphp
<section class="card card--static mb-6!" id="typography" data-typography-settings>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3!">
        <div>
            <span class="eyebrow">Tampilan website</span>
            <h2 class="text-xl! mt-2!">Font &amp; Tipografi</h2>
        </div>
        <span class="badge badge--mint badge--flat">{{ count($fontOptions) }} pilihan font</span>
    </div>
    <p class="text-sm text-soft mb-6!">Padukan font judul dan teks, lalu pilih area penerapannya. Preview berubah langsung; pengunjung baru melihat perubahan setelah kamu menyimpan.</p>
    <form method="POST" action="{{ route('admin.settings.typography.update') }}" class="grid gap-6">
        @csrf
        @method('PATCH')
        <input type="hidden" name="integration" value="typography">
        <input type="hidden" name="version" value="{{ $editingTypography ? old('version') : $typography['version'] }}">
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="grid content-start gap-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach (['heading_font' => 'Font judul', 'body_font' => 'Font teks'] as $field => $label)
                        <div class="field">
                            <label class="label" for="{{ $field }}">{{ $label }}</label>
                            <select class="select" id="{{ $field }}" name="{{ $field }}" data-font-select required>
                                @foreach (['Sans serif', 'Serif'] as $category)
                                    <optgroup label="{{ $category }}">
                                        @foreach ($fontOptions as $key => $font)
                                            @if ($font['category'] === $category)
                                                <option value="{{ $key }}" data-family="{{ $font['label'] }}" data-category="{{ $category }}" @selected(($field === 'heading_font' ? $selectedHeading : $selectedBody) === $key)>{{ $font['label'] }}</option>
                                            @endif
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
                <fieldset class="grid gap-3">
                    <legend class="label mb-3!">Terapkan font pada</legend>
                    @foreach (['landing' => 'Landing page', 'member' => 'Dashboard peserta', 'admin' => 'Dashboard admin'] as $scope => $label)
                        <input type="hidden" name="{{ $scope }}" value="0">
                        <label class="switch">
                            <input type="checkbox" name="{{ $scope }}" value="1" data-font-scope="{{ $scope }}" @checked($editingTypography ? old($scope) : $fontSettings[$scope])>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                    <p class="text-xs text-soft">Area yang tidak dicentang memakai font bawaan: Outfit &amp; Plus Jakarta Sans. Dashboard peserta mencakup halaman belajar dan ujian. Halaman login dan artikel publik tetap memakai font bawaan.</p>
                </fieldset>
                <div class="field">
                    <label class="label" for="font_preview_text">Coba teks sendiri</label>
                    <textarea class="input" id="font_preview_text" rows="3" maxlength="300" data-font-sample>Persiapkan langkahmu menuju ASN. Belajar terarah, latihan soal, dan raih target SKD 2026.</textarea>
                </div>
                <button type="button" class="btn btn--ghost justify-self-start" data-font-reset>Gunakan font bawaan</button>
                <p class="text-xs text-soft">Font tambahan dimuat melalui Bunny Fonts dan membutuhkan koneksi internet. Jika font gagal dimuat, tampilan memakai font cadangan perangkat.</p>
            </div>
            <div class="grid content-start gap-4">
                <div class="flex items-center justify-between gap-3">
                    <strong>Preview langsung</strong>
                    <span class="text-xs text-soft">Belum disimpan</span>
                </div>
                @foreach (['landing' => 'Landing page', 'member' => 'Dashboard peserta', 'admin' => 'Dashboard admin'] as $scope => $label)
                    <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-2)] p-5!" data-font-preview="{{ $scope }}">
                        <div class="flex flex-wrap justify-between items-center gap-2 mb-3!">
                            <span class="text-xs text-soft">{{ $label }}</span>
                            <span class="text-xs text-soft" data-font-status></span>
                        </div>
                        <h3 class="text-xl! mb-2!">{{ $scope === 'landing' ? 'Langkah besar dimulai dari sini.' : ($scope === 'member' ? 'Selamat datang, pejuang ASN!' : 'Kelola pembelajaran dengan mudah.') }}</h3>
                        <p class="text-sm" data-font-preview-text>Persiapkan langkahmu menuju ASN. Belajar terarah, latihan soal, dan raih target SKD 2026.</p>
                        <div class="flex flex-wrap gap-3 mt-4! items-center">
                            <span class="btn btn--primary btn--sm">{{ $scope === 'landing' ? 'Mulai belajar' : ($scope === 'member' ? 'Lanjutkan latihan' : 'Kelola materi') }}</span>
                            <span class="text-xs">TWK 85 · TIU 95 · TKP 180</span>
                        </div>
                    </div>
                @endforeach
                <p class="text-xs text-soft" role="status" aria-live="polite" data-font-loading></p>
            </div>
        </div>
        <div class="flex flex-wrap items-end gap-4 border-t border-[var(--border)] pt-5!">
            <div class="field grow max-w-sm">
                <label class="label" for="typography_password">Password akun admin</label>
                <input class="input" type="password" id="typography_password" name="current_password" autocomplete="current-password" required>
            </div>
            <button class="btn btn--primary" type="submit">Simpan pengaturan font</button>
        </div>
    </form>
</section>
