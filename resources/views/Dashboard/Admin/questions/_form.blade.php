<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.dashboard') }}">Admin Dashboard</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><a href="{{ route('admin.questions.index') }}">Soal &amp; Pembahasan</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><span class="crumbs__current">{{ $question->exists ? 'Edit Soal' : 'Tambah Soal' }}</span></nav>
<form method="POST" action="{{ $question->exists ? route('admin.questions.update', $question) : route('admin.questions.store') }}" enctype="multipart/form-data" data-question-editor>
    @csrf
    @if ($question->exists) @method('PUT') @endif
    <input type="hidden" name="status" value="{{ old('status', $question->status->value) }}">
    <header class="page-head page-head__row">
        <div><span class="eyebrow">Ruang Soal</span><h1 style="margin-top:.5rem">{{ $question->exists ? 'Edit Soal' : 'Tambah Soal Baru' }}</h1><p>Lengkapi soal di kiri, periksa preview langsung di kanan.</p></div>
        <div class="btn-row"><a class="btn btn--ghost" href="{{ route('admin.questions.index') }}">Kembali</a><button class="btn btn--outline" name="status" value="draft"><svg data-icon="pencil"></svg> Simpan draft</button><button class="btn btn--primary" name="status" value="published"><svg data-icon="send"></svg> {{ $question->exists ? 'Simpan & terbitkan' : 'Terbitkan soal' }}</button></div>
    </header>
    @if ($errors->any())
        <div class="article-errors" role="alert"><strong>Soal belum tersimpan. Periksa kembali:</strong><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
    @endif
    <div class="question-editor-layout">
        <div class="question-form-stack">
            <section class="card">
                <div class="card__head"><div><div class="card__title">Informasi soal</div><div class="card__sub">Judul singkat untuk memudahkan pencarian</div></div><span class="badge {{ $question->status->badgeClass() }}">{{ $question->status->label() }}</span></div>
                <div class="question-fields">
                    <div class="field question-wide"><label class="label" for="question-title">Judul soal</label><input class="input" id="question-title" name="title" maxlength="180" value="{{ old('title', $question->title) }}" placeholder="Contoh: Pola Bilangan Berlipat" required></div>
                    <div class="field"><label class="label" for="question-section">Bidang</label><select class="select" id="question-section" name="section_id" required><option value="">Pilih bidang</option>@foreach ($sections as $section)<option value="{{ $section->id }}" @selected((string) old('section_id', $question->section_id) === (string) $section->id)>{{ $section->name }}{{ $section->is_active ? '' : ' (nonaktif)' }}</option>@endforeach</select></div>
                    <div class="field"><label class="label" for="question-level">Tingkat kesulitan</label><select class="select" id="question-level" name="difficulty">@foreach ($difficulties as $difficulty)<option value="{{ $difficulty->value }}" @selected(old('difficulty', $question->difficulty->value) === $difficulty->value)>{{ $difficulty->label() }}</option>@endforeach</select></div>
                </div>
            </section>
            <section class="card question-form-stack">
                <x-question-rich-text name="body" label="Pertanyaan" :value="old('body', $question->body)" />
                <div class="field"><label class="label" for="question-image">Gambar soal <span class="label__opt">(opsional)</span></label><input class="input" id="question-image" type="file" name="image" accept="image/jpeg,image/png,image/webp"><p class="field__hint">JPG, PNG, WebP · maksimal 2 MB</p>
                    @if ($question->image_path)<label class="check"><input type="checkbox" name="remove_image" value="1" data-remove-image @checked(old('remove_image'))> Hapus gambar lama</label>@endif
                </div>
                <div class="field"><label class="label" for="question-video">Video soal <span class="label__opt">(opsional)</span></label><input class="input" id="question-video" type="file" name="video" accept="video/mp4,video/webm,.mp4,.webm" aria-describedby="question-video-hint"><p class="field__hint" id="question-video-hint">MP4 atau WebM · maksimal 50 MB. Gunakan MP4 dengan codec H.264 untuk kompatibilitas luas.</p>
                    @if ($question->video_path)<label class="check"><input type="checkbox" name="remove_video" value="1" data-remove-video @checked(old('remove_video'))> Hapus video lama</label>@endif
                    <p class="question-error" data-video-error role="alert" hidden></p>
                </div>
            </section>
            <section class="card">
                <div class="card__head"><div><div class="card__title">Pilihan jawaban</div><div class="card__sub">Isi 2–10 pilihan, lalu tandai satu jawaban yang benar.</div></div><span class="badge badge--primary badge--flat" data-key-label>Kunci belum dipilih</span></div>
                @php
                    $formOptions = old('options', $question->options);
                    $formOptions = is_array($formOptions) ? array_slice(array_values($formOptions), 0, 10) : $question->options;
                    $formOptions = array_map(fn ($option) => is_string($option) ? $option : '', $formOptions);
                @endphp
                <div class="question-options-editor" data-option-list>
                    @foreach ($formOptions as $index => $option)
                        <div class="question-option-row" data-option-row>
                            <label class="question-key-picker"><input type="radio" name="correct_option" value="{{ $index }}" @checked((string) old('correct_option', $question->correct_option) !== '' && (string) old('correct_option', $question->correct_option) === (string) $index) aria-label="Jadikan {{ chr(65 + $index) }} jawaban benar"><span data-option-letter>{{ chr(65 + $index) }}</span></label>
                            <textarea class="textarea" name="options[]" rows="2" maxlength="2000" aria-label="Pilihan {{ chr(65 + $index) }}" required>{{ $option }}</textarea>
                            <button class="btn btn--ghost btn--square btn--sm" type="button" data-remove-option aria-label="Hapus pilihan {{ chr(65 + $index) }}" hidden><svg data-icon="trash"></svg></button>
                        </div>
                    @endforeach
                </div>
                <button class="btn btn--ghost btn--sm question-add-option" type="button" data-add-option hidden><svg data-icon="plus"></svg> Tambah pilihan</button>
                <template data-option-template><div class="question-option-row" data-option-row><label class="question-key-picker"><input type="radio" name="correct_option"><span data-option-letter></span></label><textarea class="textarea" name="options[]" rows="2" maxlength="2000" required></textarea><button class="btn btn--ghost btn--square btn--sm" type="button" data-remove-option><svg data-icon="trash"></svg></button></div></template>
            </section>
            <section class="card">
                <x-question-rich-text name="explanation" label="Pembahasan" :value="old('explanation', $question->explanation)" />
                <p class="field__hint question-hint">Pembahasan wajib diisi untuk menerbitkan soal. Peserta melihatnya setelah menjawab.</p>
            </section>
            <p class="text-sm text-soft" data-question-save-note>Perubahan disimpan saat Anda menekan Simpan draft atau Terbitkan soal.</p>
        </div>
        <aside class="question-preview">
            <section class="card question-preview-card">
                <div class="card__head"><div><div class="card__title">Preview langsung</div><div class="card__sub">Periksa pertanyaan dan kunci jawaban</div></div><span class="badge badge--mint badge--flat">Live</span></div>
                <div class="question-preview-meta"><span class="badge badge--primary badge--flat" data-preview-field>Bidang</span><span class="text-sm text-soft" data-preview-level></span></div>
                <h2 class="question-preview-title" data-preview-title></h2>
                <div class="article-prose question-stem" data-preview-stem></div>
                <img class="question-image" data-preview-image data-existing-image="{{ $question->image_path ? route('question-bank.image', $question) : '' }}" alt="Gambar soal" hidden>
                <video class="question-video" data-preview-video data-existing-video="{{ $question->video_path ? route('question-bank.video', $question) : '' }}" controls playsinline preload="metadata" aria-label="Preview video soal" hidden>Browser Anda tidak mendukung pemutar video.</video>
                <div class="question-preview-options" data-preview-options></div>
                <div class="explain explain--info question-preview-explanation"><div class="explain__title"><svg data-icon="book"></svg> Pembahasan</div><div class="article-prose" data-preview-explanation></div></div>
            </section>
            <section class="card card--dashed question-checklist"><div class="card__title">Sebelum diterbitkan</div><ul><li>Bidang dan pertanyaan sudah terisi.</li><li>Semua pilihan berbeda dan jelas.</li><li>Kunci jawaban telah diperiksa.</li><li>Pembahasan menjelaskan jawaban.</li></ul></section>
        </aside>
    </div>
</form>

