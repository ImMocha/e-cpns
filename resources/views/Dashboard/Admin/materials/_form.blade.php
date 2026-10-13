{{--
    Formulir bersama untuk tambah / edit materi.

    $material  App\Models\Material  (pada create: model kosong)
    $sections  Illuminate\Support\Collection<int, App\Models\Section>
    $mode      'create' | 'edit'

    Bagian "sumber" (berkas PDF atau URL video) hanya satu yang tampil,
    mengikuti pilihan format. Field yang disembunyikan tetap ikut terkirim
    supaya nilainya tidak dianggap sengaja dikosongkan.
--}}
@php
    $isEdit = $mode === 'edit';
    $action = $isEdit ? route('admin.materials.update', $material) : route('admin.materials.store');
    $err = fn (string $key) => $errors->first($key);
    $currentSection = old('section_id', $material->section_id);
@endphp

<form method="POST" action="{{ $action }}" class="panes panes--wide" style="--gap:1.5rem"
      enctype="multipart/form-data" novalidate>
    @csrf
    <input type="hidden" name="workspace" value="1">
    @if ($isEdit)
        @method('PUT')
    @endif

    {{-- ============ KOLOM KIRI ============ --}}
    <div class="stack" style="--stack:1.5rem">

        <section class="card">
            <div class="card__head">
                <div>
                    <div class="card__title">Identitas Materi</div>
                    <div class="card__sub">Judul yang tampil di daftar materi dalam topik</div>
                </div>
                <span class="badge badge--ink badge--flat"><svg data-icon="clipboard"></svg> Wajib</span>
            </div>

            <div class="stack" style="--stack:1.15rem">
                <div class="field{{ $errors->has('title') ? ' is-invalid' : '' }}">
                    <label class="label" for="title">Judul Materi</label>
                    <div class="input-group">
                        <span class="input-group__icon"><svg data-icon="book"></svg></span>
                        <input class="input" type="text" id="title" name="title"
                               value="{{ old('title', $material->title) }}"
                               placeholder="Contoh: Rangkuman Penalaran Verbal" required autofocus autocomplete="off">
                    </div>
                    <span class="field__hint">Minimal 5 karakter. Unik di dalam bidang yang sama.</span>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('title') ?? 'Judul materi wajib diisi.' }}</span></span>
                </div>

                <div class="field{{ $errors->has('summary') ? ' is-invalid' : '' }}">
                    <label class="label" for="summary">
                        Ringkasan
                        <span class="label__opt">— opsional, tampil di kartu materi</span>
                    </label>
                    <textarea class="textarea" id="summary" name="summary" rows="3"
                              placeholder="Deskripsi singkat yang muncul di daftar materi.">{{ old('summary', $material->summary) }}</textarea>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('summary') ?? 'Maksimal 1.000 karakter.' }}</span></span>
                </div>

                <div class="field{{ $errors->has('slug') ? ' is-invalid' : '' }}">
                    <label class="label" for="slug">
                        Slug
                        <span class="label__opt">— opsional, dibuat otomatis dari judul bila kosong</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group__icon"><svg data-icon="external"></svg></span>
                        <input class="input text-mono" type="text" id="slug" name="slug"
                               value="{{ old('slug', $material->slug) }}"
                               placeholder="rangkuman-penalaran-verbal" autocomplete="off">
                    </div>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('slug') ?? 'Slug ini sudah dipakai materi lain.' }}</span></span>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card__head">
                <div>
                    <div class="card__title">Penempatan</div>
                    <div class="card__sub">Bidang, topik, format, level akses, dan urutan tampil</div>
                </div>
            </div>

            <div class="stack" style="--stack:1.15rem">
                <div class="field{{ $errors->has('section_id') ? ' is-invalid' : '' }}">
                    <label class="label" for="section_id">Bidang</label>
                    <select class="select" id="section_id" name="section_id" required>
                        <option value="">Pilih bidang...</option>
                        @foreach ($sections as $option)
                            <option value="{{ $option->id }}" @selected((string) $currentSection === (string) $option->id)>
                                {{ $option->name }} ({{ $option->code }}){{ $option->is_active ? '' : ' — nonaktif' }}
                            </option>
                        @endforeach
                    </select>
                    <span class="field__hint">Satu materi hanya punya satu bidang.</span>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('section_id') ?? 'Bidang wajib dipilih.' }}</span></span>
                </div>

                <div class="field{{ $errors->has('topic_id') ? ' is-invalid' : '' }}">
                    <label class="label" for="topic_id">Topik</label>
                    <select class="select" id="topic_id" name="topic_id" required>
                        <option value="">Pilih topik...</option>
                        @foreach ($topics as $topic)
                            <option value="{{ $topic->id }}" data-section="{{ $topic->section_id }}"
                                    @selected((string) old('topic_id', $material->topic_id) === (string) $topic->id)>
                                {{ $topic->name }}{{ $topic->is_active ? '' : ' — nonaktif' }}
                            </option>
                        @endforeach
                    </select>
                    <span class="field__hint">Topik berada di dalam bidang. <a href="{{ route('admin.topics.create') }}">Buat topik baru</a></span>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('topic_id') ?? 'Topik wajib dipilih.' }}</span></span>
                </div>

                <div class="grid grid-2" style="--gap:1.15rem">
                    <div class="field{{ $errors->has('type') ? ' is-invalid' : '' }}">
                        <label class="label" for="type">Format</label>
                        <select class="select" id="type" name="type" required>
                            @foreach (\App\Enums\MaterialType::cases() as $type)
                                <option value="{{ $type->value }}" @selected(old('type', $material->type->value) === $type->value)>
                                    {{ $type->label() }}
                                </option>
                            @endforeach
                        </select>
                        <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('type') ?? 'Format materi tidak dikenal.' }}</span></span>
                    </div>

                    <div class="field{{ $errors->has('access_level') ? ' is-invalid' : '' }}">
                        <label class="label" for="access_level">Level Akses</label>
                        <select class="select" id="access_level" name="access_level" required>
                            @foreach (\App\Enums\MaterialAccess::cases() as $access)
                                <option value="{{ $access->value }}" @selected(old('access_level', $material->access_level->value) === $access->value)>
                                    {{ $access->label() }}
                                </option>
                            @endforeach
                        </select>
                        <span class="field__hint">Premium butuh paket aktif peserta.</span>
                        <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('access_level') ?? 'Level akses tidak dikenal.' }}</span></span>
                    </div>

                    <div class="field{{ $errors->has('position') ? ' is-invalid' : '' }}">
                        <label class="label" for="position">Urutan Tampil</label>
                        <div class="input-group">
                            <span class="input-group__icon"><svg data-icon="filter"></svg></span>
                            <input class="input" type="number" id="position" name="position" min="0" max="999"
                                   value="{{ old('position', $material->position ?? 0) }}">
                        </div>
                        <span class="field__hint">Angka kecil tampil lebih dulu di dalam topik.</span>
                        <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('position') ?? 'Nilai 0 sampai 999.' }}</span></span>
                    </div>

                    <div class="field{{ $errors->has('status') ? ' is-invalid' : '' }}">
                        <label class="label" for="status">Status</label>
                        <select class="select" id="status" name="status" required>
                            @foreach (\App\Enums\MaterialStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $material->status->value) === $status->value)>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        <span class="field__hint">Draft tidak tampil untuk peserta.</span>
                        <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('status') ?? 'Status tidak dikenal.' }}</span></span>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============ SUMBER KONTEN ============ --}}
        <section class="card" id="sumberKartu">
            <div class="card__head">
                <div>
                    <div class="card__title">Sumber Konten</div>
                    <div class="card__sub">Bergantian mengikuti format: PDF memakai berkas, video memakai URL</div>
                </div>
            </div>

            {{-- PDF --}}
            <div class="stack" id="sumberPdf" style="--stack:1.15rem">
                <div class="field{{ $errors->has('file') ? ' is-invalid' : '' }}">
                    <label class="label" for="file">
                        Berkas PDF
                        <span class="label__opt">— {{ $isEdit ? 'kosongkan bila tidak ingin mengganti berkas' : 'boleh kosong selama masih Draft' }}</span>
                    </label>

                    {{-- Input native disembunyikan di dalam dropzone: tetap bisa
                         diakses keyboard dan tetap bekerja tanpa JavaScript. --}}
                    <label class="dropzone" for="file">
                        <span class="dropzone__icon"><svg data-icon="download"></svg></span>
                        <strong style="display:block;color:var(--ink);font-family:var(--font-display)">Letakkan berkas di sini</strong>
                        <span class="text-sm text-soft">atau klik untuk memilih berkas. Maksimal 20 MB, format PDF.</span>
                        <input type="file" id="file" name="file" accept="application/pdf,.pdf"
                               class="input" style="max-width:340px;margin-top:.9rem;background:var(--surface)"
                               data-file-label>
                    </label>

                    <span class="field__hint">PDF disimpan secara privat; aksesnya diperiksa sesuai hak peserta.</span>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('file') ?? 'Berkas harus berformat PDF dan maksimal 20 MB.' }}</span></span>
                </div>

                @if ($isEdit && $material->file_path)
                    <div class="card card--tight card--dashed">
                        <div class="card__title" style="font-size:var(--fs-sm)">Berkas saat ini</div>
                        <div class="kv" style="margin-top:.6rem">
                            <div class="kv__row"><span class="kv__key">Ukuran</span><span class="kv__val">{{ $material->humanFileSize() ?? '—' }}</span></div>
                            <div class="kv__row"><span class="kv__key">Jumlah halaman</span><span class="kv__val">{{ $material->page_count ?? '—' }}</span></div>
                            <div class="kv__row"><span class="kv__key">Diunggah</span><span class="kv__val">{{ $material->created_at?->format('d M Y') }}</span></div>
                        </div>
                    </div>
                @endif

                <div class="field{{ $errors->has('page_count') ? ' is-invalid' : '' }}">
                    <label class="label" for="page_count">Jumlah halaman</label>
                    <div class="input-group">
                        <span class="input-group__icon"><svg data-icon="file"></svg></span>
                        <input class="input" type="number" id="page_count" name="page_count" min="1" max="2000"
                               value="{{ old('page_count', $material->page_count) }}">
                    </div>
                    <span class="field__hint">Dipakai sebagai label di kartu materi, bukan untuk memotong PDF.</span>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('page_count') ?? 'Nilai 1 sampai 2.000.' }}</span></span>
                </div>
            </div>

            {{-- Video --}}
            <div class="stack" id="sumberVideo" style="--stack:1.15rem">
                <div class="field{{ $errors->has('video_url') ? ' is-invalid' : '' }}">
                    <label class="label" for="video_url">URL Video</label>
                    <div class="input-group">
                        <span class="input-group__icon"><svg data-icon="play"></svg></span>
                        <input class="input" type="url" id="video_url" name="video_url"
                               value="{{ old('video_url', $material->video_url) }}"
                               placeholder="https://" autocomplete="off">
                    </div>
                    <span class="field__hint">Gunakan URL sumber video yang dapat diputar oleh browser.</span>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('video_url') ?? 'URL video harus berupa tautan valid.' }}</span></span>
                </div>

                <div class="field{{ $errors->has('duration_minutes') ? ' is-invalid' : '' }}">
                    <label class="label" for="duration_minutes">Durasi (menit)</label>
                    <div class="input-group">
                        <span class="input-group__icon"><svg data-icon="clock"></svg></span>
                        <input class="input" type="number" id="duration_minutes" name="duration_minutes" min="1" max="600"
                               value="{{ old('duration_minutes', $material->duration_minutes) }}">
                    </div>
                    <span class="field__hint">Tampil sebagai label di kartu video.</span>
                    <span class="field__error"><svg data-icon="alert"></svg><span>{{ $err('duration_minutes') ?? 'Nilai 1 sampai 600.' }}</span></span>
                </div>
            </div>
        </section>
    </div>

    {{-- ============ KOLOM KANAN ============ --}}
    <aside class="stack" style="--stack:1.25rem">

        <section class="card card--tight">
            <div class="card__title" style="font-size:var(--fs-base)">Ringkasan</div>
            <div class="kv" style="margin-top:.85rem">
                <div class="kv__row"><span class="kv__key">ID materi</span><span class="kv__val">{{ $isEdit ? '#'.$material->id : 'otomatis' }}</span></div>
                <div class="kv__row"><span class="kv__key">Format</span><span class="kv__val">{{ \App\Enums\MaterialType::tryFrom(old('type', $material->type->value))?->shortLabel() }}</span></div>
                <div class="kv__row"><span class="kv__key">Slug</span><span class="kv__val">{{ old('slug', $material->slug) ?: 'otomatis' }}</span></div>
                <div class="kv__row"><span class="kv__key">Dibuat</span><span class="kv__val">{{ $isEdit ? $material->created_at->format('d M Y') : '—' }}</span></div>
                <div class="kv__row"><span class="kv__key">Diubah terakhir</span><span class="kv__val">{{ $isEdit && $material->updated_at ? $material->updated_at->format('d M Y H:i') : '—' }}</span></div>
            </div>
        </section>

        <div class="alert alert--info">
            <span class="alert__icon"><svg data-icon="info"></svg></span>
            <div>
                <strong>Materi Terbit harus punya sumber</strong>
                PDF wajib punya berkas, video wajib punya URL. Tanpa sumber, materi ditolak saat disimpan &mdash;
                bukan setelah peserta mengeluh tidak bisa membukanya.
            </div>
        </div>

        <div class="btn-row" style="--gap:.65rem">
            <button class="btn btn--primary btn--block" type="submit">
                {{ $isEdit ? 'Simpan Perubahan' : 'Buat Materi' }}
                <span class="btn__icon"><svg data-icon="check"></svg></span>
            </button>
            <a class="btn btn--ghost btn--block" href="{{ $returnUrl }}">Batal</a>
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
    <script>
        (function () {
            const section = document.getElementById('section_id');
            const topic = document.getElementById('topic_id');
            const filterTopics = function () {
                Array.from(topic.options).forEach(function (option) {
                    if (!option.value) return;
                    option.hidden = option.dataset.section !== section.value;
                    option.disabled = option.hidden;
                });
                if (topic.selectedOptions[0]?.disabled) topic.value = '';
            };
            section.addEventListener('change', filterTopics);
            filterTopics();

            const type = document.getElementById('type');
            const pdf = document.getElementById('sumberPdf');
            const video = document.getElementById('sumberVideo');

            if (!type || !pdf || !video) return;

            // Field yang tidak aktif ikut terkirim supaya nilainya tidak
            // dianggap sengaja dikosongkan oleh admin.
            const toggle = function () {
                const isVideo = type.value === 'video';
                pdf.hidden = isVideo;
                video.hidden = !isVideo;
            };

            type.addEventListener('change', toggle);
            toggle();
        })();
    </script>
@endpush
