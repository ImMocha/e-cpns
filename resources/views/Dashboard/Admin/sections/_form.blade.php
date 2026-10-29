{{--
    Formulir bersama untuk tambah / edit bidang.

    $section  App\Models\Section  (pada create: model kosong)
    $icons    array<int, string> nama ikon yang tersedia
    $tones    App\Enums\ColorTone[]
    $mode     'create' | 'edit'
--}}
@php
    $isEdit = $mode === 'edit';
    $action = $isEdit ? route('admin.sections.update', $section) : route('admin.sections.store');
    $err = fn (string $key) => $errors->first($key);
    $currentTone = $isEdit ? $section->resolvedTone() : $tones[$section->position % count($tones)];
@endphp

<form method="POST" action="{{ $action }}" class="panes panes--wide" style="--gap:1.5rem" novalidate>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    {{-- ============ KOLOM KIRI ============ --}}
    <div class="stack" style="--stack:1.5rem">

        <section class="card">
            <div class="card__head">
                <div>
                    <div class="card__title">Identitas Bidang</div>
                    <div class="card__sub">Nama dan kode dipakai di materi, soal, dan tryout</div>
                </div>
                <span class="badge badge--ink badge--flat"><svg data-icon="clipboard"></svg> Wajib</span>
            </div>

            <div class="stack" style="--stack:1.15rem">
                <div class="field{{ $errors->has('name') ? ' is-invalid' : '' }}">
                    <label class="label" for="name">Nama Bidang</label>
                    <div class="input-group">
                        <span class="input-group__icon"><svg data-icon="folder"></svg></span>
                        <input class="input" type="text" id="name" name="name"
                               value="{{ old('name', $section->name) }}"
                               placeholder="Contoh: Bahasa Indonesia" required autofocus autocomplete="off">
                    </div>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('name') ?? 'Wajib diisi.' }}</span></span>
                </div>

                <div class="grid grid-2" style="--gap:1.15rem">
                    <div class="field{{ $errors->has('code') ? ' is-invalid' : '' }}">
                        <label class="label" for="code">Kode Bidang</label>
                        <div class="input-group">
                            <span class="input-group__icon"><svg data-icon="key"></svg></span>
                            <input class="input text-mono" type="text" id="code" name="code"
                                   value="{{ old('code', $section->code) }}"
                                   placeholder="Contoh: TWK" required
                                   data-suggest-code data-touched="{{ old('code', $section->code) ? '1' : '0' }}"
                                   style="text-transform:uppercase" autocomplete="off">
                        </div>
                        <span class="field__hint">Terisi otomatis dari nama bila dikosongkan.</span>
                        <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('code') ?? 'Hanya huruf kapital, angka, dan tanda hubung.' }}</span></span>
                    </div>

                    <div class="field{{ $errors->has('position') ? ' is-invalid' : '' }}">
                        <label class="label" for="position">Urutan Tampil</label>
                        <div class="input-group">
                            <span class="input-group__icon"><svg data-icon="filter"></svg></span>
                            <input class="input" type="number" id="position" name="position" min="0" max="999"
                                   value="{{ old('position', $section->position ?? 0) }}">
                        </div>
                        <span class="field__hint">Angka kecil tampil lebih dulu.</span>
                        <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('position') ?? 'Nilai 0 sampai 999.' }}</span></span>
                    </div>
                </div>

                <div class="field{{ $errors->has('slug') ? ' is-invalid' : '' }}">
                    <label class="label" for="slug">
                        Slug
                        <span class="label__opt">— otomatis dari nama, diberi akhiran jika sudah dipakai</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group__icon"><svg data-icon="external"></svg></span>
                        <input class="input text-mono" type="text" id="slug" maxlength="140" name="slug" data-auto-slug="sections" data-slug-url="{{ route('admin.slugs.preview') }}" data-slug-id="{{ $section->id }}"
                               value="{{ old('slug', $section->slug) }}"
                               placeholder="bahasa-indonesia" autocomplete="off">
                    </div>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('slug') ?? 'Slug ini sudah dipakai bidang lain.' }}</span></span>
                </div>

                <div class="field{{ $errors->has('description') ? ' is-invalid' : '' }}">
                    <label class="label" for="description">Deskripsi</label>
                    <textarea class="textarea" id="description" name="description" rows="3"
                              placeholder="Penjelasan singkat bidang ini.">{{ old('description', $section->description) }}</textarea>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('description') ?? 'Maksimal 1.000 karakter.' }}</span></span>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card__head">
                <div>
                    <div class="card__title">Penampilan</div>
                    <div class="card__sub">Ikon dan warna yang dipakai di daftar, badge, dan kartu</div>
                </div>
            </div>

            <div class="grid grid-2" style="--gap:1.15rem">
                <div class="field{{ $errors->has('icon') ? ' is-invalid' : '' }}">
                    <label class="label" for="icon">Ikon</label>
                    <select class="select" id="icon" name="icon">
                        <option value="">Otomatis</option>
                        @foreach ($icons as $icon)
                            <option value="{{ $icon }}" @selected(old('icon', $section->icon) === $icon)>{{ $icon }}</option>
                        @endforeach
                    </select>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('icon') ?? 'Ikon tidak dikenal.' }}</span></span>
                </div>

                <div class="field{{ $errors->has('tone') ? ' is-invalid' : '' }}">
                    <label class="label" for="tone">Warna</label>
                    <select class="select" id="tone" name="tone">
                        <option value="">Otomatis</option>
                        @foreach ($tones as $tone)
                            <option value="{{ $tone->value }}" @selected(old('tone', $section->tone) === $tone->value)>{{ $tone->label() }}</option>
                        @endforeach
                    </select>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('tone') ?? 'Warna tidak dikenal.' }}</span></span>
                </div>
            </div>

            <div class="flex items-center gap-3" style="margin-top:1.25rem">
                <span class="text-sm text-soft">Pratinjau:</span>
                @php($previewTone = \App\Enums\ColorTone::tryFrom((string) old('tone')) ?? $currentTone)
                <span class="stat__icon {{ $previewTone->iconModifier() }}" style="width:44px;height:44px;flex-basis:44px">
                    <svg data-icon="{{ $section->icon ?: $section->resolvedIcon() }}" id="iconPreview"></svg>
                </span>
                <span class="badge {{ $previewTone->badgeClass() }} badge--flat" id="tonePreview">{{ $previewTone->label() }}</span>
            </div>
        </section>
    </div>

    {{-- ============ KOLOM KANAN ============ --}}
    <aside class="stack" style="--stack:1.25rem">

        <section class="card card--tight">
            <label class="switch">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $section->is_active ?? true))>
                <span>Bidang aktif</span>
            </label>
            <p class="text-xs text-soft" style="margin-top:.5rem">Bidang nonaktif disembunyikan dari pilihan di halaman peserta, tapi isinya tetap ada.</p>
        </section>

        <section class="card card--dashed">
            <div class="card__title" style="font-size:var(--fs-base)">Ringkasan</div>
            <div class="kv" style="margin-top:.85rem">
                <div class="kv__row"><span class="kv__key">ID bidang</span><span class="kv__val">{{ $isEdit ? '#'.$section->id : 'otomatis' }}</span></div>
                <div class="kv__row"><span class="kv__key">Slug</span><span class="kv__val">{{ $section->slug ?: '—' }}</span></div>
                <div class="kv__row"><span class="kv__key">Dibuat</span><span class="kv__val"><x-local-time :value="$isEdit ? $section->created_at : null" format="d M Y" /></span></div>
                <div class="kv__row"><span class="kv__key">Diubah terakhir</span><span class="kv__val"><x-local-time :value="$isEdit ? $section->updated_at : null" /></span></div>
            </div>
        </section>

        <div class="alert alert--info">
          <span class="alert__icon"><svg data-icon="info"></svg></span>
          <div>Materi, soal, dan artikel belum punya tabel. Setelah dibuat, kolom <span class="text-mono text-xs">section_id</span> akan menunjuk ke bidang ini.</div>
        </div>

        <div class="btn-row" style="--gap:.65rem">
            <button class="btn btn--primary btn--block" type="submit">
                {{ $isEdit ? 'Simpan Perubahan' : 'Buat Bidang' }}
                <span class="btn__icon"><svg data-icon="check"></svg></span>
            </button>
            <a class="btn btn--ghost btn--block" href="{{ route('admin.sections.index') }}">Batal</a>
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

@push('scripts')
    {{-- Saran kode: 3 huruf pertama dari nama, sama seperti Section::suggestCode(). --}}
    <script>
        (function () {
            const code = document.getElementById('code');
            const name = document.getElementById('name');

            if (!code || !name) return;

            let touched = code.dataset.touched === '1';

            const suggest = function () {
                if (touched) return;

                const letters = name.value.replace(/[^A-Za-z0-9]+/g, '').slice(0, 3).toUpperCase();
                code.value = letters || 'BID';
            };

            code.addEventListener('input', function () { touched = true; });
            name.addEventListener('input', suggest);

            suggest();
        })();
    </script>

    {{-- Pratinjau ikon & warna langsung mengikuti pilihan di form. --}}
    <script>
        (function () {
            const icon = document.getElementById('icon');
            const tone = document.getElementById('tone');
            const iconPreview = document.getElementById('iconPreview');
            const tonePreview = document.getElementById('tonePreview');
            const box = iconPreview && iconPreview.closest('.stat__icon');

            if (!icon || !tone || !iconPreview || !tonePreview || !box) return;

            const labels = @json(collect($tones)->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all());
            const modifiers = @json(collect($tones)->mapWithKeys(fn ($t) => [$t->value => $t->iconModifier()])->all());
            const badges = @json(collect($tones)->mapWithKeys(fn ($t) => [$t->value => $t->badgeClass()])->all());

            const sync = function () {
                if (icon.value) {
                    iconPreview.dataset.icon = icon.value;
                    if (window.LIcons) {
                        iconPreview.innerHTML = window.LIcons.path(icon.value);
                        iconPreview.removeAttribute('data-icon');
                        iconPreview.classList.add('icon');
                    }
                }

                if (tone.value) {
                    box.className = box.className.replace(/\bico--[\w-]+\b/g, '').trim() + ' ' + modifiers[tone.value];
                    tonePreview.className = 'badge ' + badges[tone.value] + ' badge--flat';
                    tonePreview.textContent = labels[tone.value];
                }
            };

            icon.addEventListener('change', sync);
            tone.addEventListener('change', sync);
        })();
    </script>
@endpush