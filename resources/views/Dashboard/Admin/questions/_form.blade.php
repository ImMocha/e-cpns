@php
    $inline = $inline ?? false;
    $questionValue = fn (string $key, mixed $default = null): mixed => $inline ? $default : old($key, $default);
    $formScoring = $questionValue('scoring_type', $question->scoring_type->value);
@endphp
@unless($inline)
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.dashboard') }}">Admin Dashboard</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><a href="{{ route('admin.questions.index') }}">Soal &amp; Pembahasan</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><span class="crumbs__current">{{ $question->exists ? 'Edit Soal' : 'Tambah Soal' }}</span></nav>
@endunless
<form method="POST" action="{{ $question->exists ? route('admin.questions.update', $question) : route('admin.questions.store') }}" enctype="multipart/form-data" data-question-editor @if($inline) data-inline-question-form @endif>
    @csrf
    @if ($question->exists) @method('PUT') @endif
    <input type="hidden" name="status" value="{{ $questionValue('status', $question->status->value) }}">
    @if($inline)
    <header class="exam-question-dialog__head"><div><span class="eyebrow">Editor Soal</span><h2 id="inline-question-title" data-inline-question-title>Tambah Soal</h2><p>Lengkapi soal dan periksa pratinjau sebelum menyimpan.</p></div><button class="content-icon-button" type="button" data-inline-close aria-label="Tutup editor soal"><svg data-icon="close"></svg></button></header>
    <button class="btn btn--ghost btn--sm" type="button" data-help-open data-help-guide="admin-questions">Bantuan editor soal</button>
    <div class="exam-question-feedback" data-inline-errors role="alert" hidden></div>
    <div class="exam-question-feedback exam-question-feedback--info" data-inline-shared hidden></div>
    <div class="exam-question-feedback" data-inline-discard hidden><p>Perubahan soal belum disimpan. Tutup editor dan buang perubahan?</p><div class="btn-row"><button class="btn btn--ghost btn--sm" type="button" data-inline-keep>Lanjutkan mengedit</button><button class="btn btn--outline btn--sm" type="button" data-inline-discard-confirm>Buang perubahan</button></div></div>
    @else
    <header class="page-head page-head__row">
        <div><span class="eyebrow">Ruang Soal</span><h1 style="margin-top:.5rem">{{ $question->exists ? 'Edit Soal' : 'Tambah Soal Baru' }}</h1><p>Lengkapi soal di kiri, periksa preview langsung di kanan.</p></div>
        <div class="btn-row"><a class="btn btn--ghost" href="{{ route('admin.questions.index') }}">Kembali</a><button class="btn btn--outline" name="status" value="draft"><svg data-icon="pencil"></svg> Simpan draft</button><button class="btn btn--primary" name="status" value="published"><svg data-icon="send"></svg> {{ $question->exists ? 'Simpan & terbitkan' : 'Terbitkan soal' }}</button></div>
    </header>
    @endif
    @if (! $inline && $errors->any())
        <div class="article-errors" role="alert"><strong>Soal belum tersimpan. Periksa kembali:</strong><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
    @endif
    <div class="question-editor-layout">
        <div class="question-form-stack">
            <section class="card">
                <div class="card__head"><div><div class="card__title">Informasi soal</div><div class="card__sub">Judul singkat untuk memudahkan pencarian</div></div><span class="badge {{ $question->status->badgeClass() }}" data-editor-status>{{ $question->status->label() }}</span></div>
                <div class="question-fields">
                    <div class="field question-wide"><label class="label" for="question-title">Judul soal</label><input class="input" id="question-title" name="title" maxlength="180" value="{{ $questionValue('title', $question->title) }}" placeholder="Contoh: Pola Bilangan Berlipat" required></div>
                    <div class="field"><label class="label" for="question-section">Bidang</label><select class="select" id="question-section" name="section_id" required><option value="">Pilih bidang</option>@foreach ($sections as $section)<option value="{{ $section->id }}" data-code="{{ $section->code }}" @selected((string) $questionValue('section_id', $question->section_id) === (string) $section->id)>{{ $section->name }}{{ $section->is_active ? '' : ' (nonaktif)' }}</option>@endforeach</select></div>
                    <div class="field"><label class="label" for="question-level">Tingkat kesulitan</label><select class="select" id="question-level" name="difficulty">@foreach ($difficulties as $difficulty)<option value="{{ $difficulty->value }}" @selected($questionValue('difficulty', $question->difficulty->value) === $difficulty->value)>{{ $difficulty->label() }}</option>@endforeach</select></div>
                </div>
            </section>
            @unless($inline)
            <section class="card question-form-stack" id="sesi-tujuan" data-question-exams>
                <div class="card__head"><div><div class="card__title">Sesi tujuan soal</div><div class="card__sub">Pilih latihan atau tryout yang memakai soal ini.</div></div></div>
                <div class="btn-row flex-wrap"><a class="btn btn--ghost btn--sm" href="{{ route('admin.materials.quizzes.index') }}" target="_blank" rel="noopener">Latihan Materi ↗</a><a class="btn btn--ghost btn--sm" href="{{ route('admin.exams.index') }}" target="_blank" rel="noopener">Tryout ↗</a></div>
                <input type="hidden" name="exam_ids" value="">
                @php
                    $formExamIds = is_array($questionValue('exam_ids', $selectedExamIds)) ? $questionValue('exam_ids', $selectedExamIds) : [];
                @endphp
                @if ($exams->isNotEmpty())
                    <div class="field"><label class="label" for="question-exam-search">Cari sesi</label><input class="input" type="search" id="question-exam-search" placeholder="Cari nama latihan atau tryout..." data-question-exam-search></div>
                    <p class="text-sm text-soft" data-question-exam-count aria-live="polite">{{ count($formExamIds) }} sesi dipilih</p>
                    <div class="question-exam-picker">
                        @foreach (['quiz' => 'Latihan', 'tryout' => 'Tryout CAT'] as $kind => $label)
                            @if ($exams->where('kind', $kind)->isNotEmpty())
                                <fieldset class="question-exam-group" data-question-exam-group><legend class="label">{{ $label }}</legend>
                                    @foreach ($exams->where('kind', $kind) as $exam)
                                        <div class="question-exam-choice" data-question-exam-item><label class="check"><input type="checkbox" name="exam_ids[]" value="{{ $exam->id }}" @checked(in_array($exam->id, $formExamIds))><span><strong>{{ $exam->title }}</strong><span class="text-xs text-soft">{{ $exam->status->label() }} · {{ $exam->questions_count }} soal di bank · {{ $exam->question_count }} soal per pengerjaan</span></span></label><a class="question-exam-open" href="{{ route($exam->kind === 'quiz' ? 'admin.materials.quizzes.edit' : 'admin.exams.edit', $exam) }}" aria-label="Buka pengaturan {{ $exam->title }}" target="_blank" rel="noopener"><svg data-icon="arrow-right"></svg></a></div>
                                    @endforeach
                                </fieldset>
                            @endif
                        @endforeach
                    </div>
                    <p class="text-sm text-soft" data-question-exam-empty hidden>Tidak ada sesi yang cocok dengan pencarian.</p>
                @else
                    <p class="text-sm text-soft">Belum ada sesi. <a class="font-bold text-primary" href="{{ route('admin.materials.quizzes.create') }}" target="_blank" rel="noopener">Buat latihan</a> melalui Materi atau <a class="font-bold text-primary" href="{{ route('admin.exams.create') }}" target="_blank" rel="noopener">buat tryout</a>, lalu pilih di sini.</p>
                @endif
                <p class="field__hint">Satu soal bisa masuk beberapa sesi. Pilihan disimpan bersama soal. Soal terbit di bidang aktif dapat diambil untuk pengerjaan baru, sesuai jumlah dan randomisasi sesi.</p>
            </section>
            @endunless
            <section class="card question-form-stack">
                <x-question-rich-text name="body" label="Pertanyaan" :value="$questionValue('body', $question->body)" />
                <div class="field"><label class="label" for="question-image">Gambar soal <span class="label__opt">(opsional)</span></label><input class="input" id="question-image" type="file" name="image" accept="image/jpeg,image/png,image/webp"><p class="field__hint">JPG, PNG, WebP · maksimal 2 MB</p>
                    @if ($question->image_path || $inline)<label class="check" data-existing-image-control @if(!$question->image_path) hidden @endif><input type="checkbox" name="remove_image" value="1" data-remove-image @checked($questionValue('remove_image'))> Hapus gambar lama</label>@endif
                </div>
                <div class="field"><label class="label" for="question-video">Video soal <span class="label__opt">(opsional)</span></label><input class="input" id="question-video" type="file" name="video" accept="video/mp4,video/webm,.mp4,.webm" aria-describedby="question-video-hint"><p class="field__hint" id="question-video-hint">MP4 atau WebM · maksimal 50 MB. Gunakan MP4 dengan codec H.264 untuk kompatibilitas luas.</p>
                    @if ($question->video_path || $inline)<label class="check" data-existing-video-control @if(!$question->video_path) hidden @endif><input type="checkbox" name="remove_video" value="1" data-remove-video @checked($questionValue('remove_video'))> Hapus video lama</label>@endif
                    <p class="question-error" data-video-error role="alert" hidden></p>
                </div>
            </section>
            <section class="card">
                <div class="card__head"><div><div class="card__title">Pilihan &amp; penilaian</div><div class="card__sub" data-scoring-hint>Isi pilihan sesuai jenis penilaian soal.</div></div><span class="badge badge--primary badge--flat" data-key-label>Kunci belum dipilih</span></div>
                <div class="field question-scoring-control"><label class="label" for="question-scoring">Jenis penilaian</label><select class="select" id="question-scoring" name="scoring_type" data-question-scoring>@foreach (\App\Enums\QuestionScoring::cases() as $scoring)<option value="{{ $scoring->value }}" @selected($formScoring === $scoring->value)>{{ $scoring->label() }}</option>@endforeach</select><p class="field__hint">TKP dapat memakai skor 1–5 untuk setiap pilihan. Jawaban kosong bernilai 0 dan tidak memakai penalti salah.</p></div>
                @php
                    $formOptions = $questionValue('options', $question->options);
                    $formOptions = is_array($formOptions) ? array_slice(array_values($formOptions), 0, 10) : $question->options;
                    $formOptions = array_map(fn ($option) => is_string($option) ? $option : '', $formOptions);
                    $formScores = $questionValue('option_scores', $question->option_scores ?? []);
                    $formScores = is_array($formScores) ? $formScores : [];
                @endphp
                <div class="question-options-editor" data-option-list>
                    @foreach ($formOptions as $index => $option)
                        <div class="question-option-row" data-option-row>
                            <label class="question-key-picker"><input type="radio" name="correct_option" value="{{ $index }}" @disabled($formScoring === 'weighted') @checked((string) $questionValue('correct_option', $question->correct_option) !== '' && (string) $questionValue('correct_option', $question->correct_option) === (string) $index) aria-label="Jadikan {{ chr(65 + $index) }} jawaban benar"><span data-option-letter>{{ chr(65 + $index) }}</span></label>
                            <textarea class="textarea" name="options[]" rows="2" maxlength="2000" aria-label="Pilihan {{ chr(65 + $index) }}" required>{{ $option }}</textarea>
                            <label class="question-option-score" data-option-score @if($formScoring !== 'weighted') hidden @endif><span>Skor</span><input class="input" type="number" name="option_scores[]" min="1" max="5" step="1" value="{{ $formScores[$index] ?? '' }}" @disabled($formScoring !== 'weighted') aria-label="Skor pilihan {{ chr(65 + $index) }}"></label>
                            <button class="btn btn--ghost btn--square btn--sm" type="button" data-remove-option aria-label="Hapus pilihan {{ chr(65 + $index) }}" hidden><svg data-icon="trash"></svg></button>
                        </div>
                    @endforeach
                </div>
                <button class="btn btn--ghost btn--sm question-add-option" type="button" data-add-option hidden><svg data-icon="plus"></svg> Tambah pilihan</button>
                <template data-option-template><div class="question-option-row" data-option-row><label class="question-key-picker"><input type="radio" name="correct_option"><span data-option-letter></span></label><textarea class="textarea" name="options[]" rows="2" maxlength="2000" required></textarea><label class="question-option-score" data-option-score hidden><span>Skor</span><input class="input" type="number" name="option_scores[]" min="1" max="5" step="1" disabled></label><button class="btn btn--ghost btn--square btn--sm" type="button" data-remove-option><svg data-icon="trash"></svg></button></div></template>
                <div class="question-hint"><input type="hidden" name="shuffle_options" value="0"><label class="check"><input type="checkbox" name="shuffle_options" value="1" data-shuffle-options @checked($questionValue('shuffle_options', $question->shuffle_options))> Izinkan pengacakan pilihan pada sesi</label><p class="field__hint">Matikan bila gambar soal sudah memuat label A–E agar pilihan tetap sesuai ilustrasi.</p></div>
            </section>
            <section class="card">
                <x-question-rich-text name="explanation" label="Pembahasan" :value="$questionValue('explanation', $question->explanation)" />
                <p class="field__hint question-hint">Pembahasan wajib diisi untuk menerbitkan soal. Peserta melihatnya setelah menjawab.</p>
            </section>
            <p class="text-sm text-soft" data-question-save-note>Perubahan disimpan saat Anda menekan Simpan draft atau Terbitkan soal.</p>
        </div>
        <aside class="question-preview">
            <section class="card question-preview-card">
                <div class="card__head"><div><div class="card__title">Preview langsung</div><div class="card__sub" data-preview-scoring-hint>Periksa pertanyaan dan penilaian</div></div><span class="badge badge--mint badge--flat">Live</span></div>
                <div class="question-preview-meta"><span class="badge badge--primary badge--flat" data-preview-field>Bidang</span><span class="text-sm text-soft" data-preview-level></span></div>
                <h2 class="question-preview-title" data-preview-title></h2>
                <div class="article-prose question-stem" data-preview-stem></div>
                <img class="question-image" data-preview-image data-existing-image="{{ $question->image_path ? route('question-bank.image', $question) : '' }}" alt="Gambar soal" hidden>
                <video class="question-video" data-preview-video data-existing-video="{{ $question->video_path ? route('question-bank.video', $question) : '' }}" controls playsinline preload="metadata" aria-label="Preview video soal" hidden>Browser Anda tidak mendukung pemutar video.</video>
                <div class="question-preview-options" data-preview-options></div>
                <div class="explain explain--info question-preview-explanation"><div class="explain__title"><svg data-icon="book"></svg> Pembahasan</div><div class="article-prose" data-preview-explanation></div></div>
            </section>
            <section class="card card--dashed question-checklist"><div class="card__title">Sebelum diterbitkan</div><ul><li>Bidang dan pertanyaan sudah terisi.</li><li>Semua pilihan berbeda dan jelas.</li><li data-scoring-checklist>Penilaian setiap pilihan telah diperiksa.</li><li>Pembahasan menjelaskan jawaban.</li></ul></section>
        </aside>
    </div>
    @if($inline)
    <footer class="exam-question-dialog__foot"><p data-inline-save-hint>Soal disimpan ke bank soal. Simpan sesi untuk menerapkan kumpulan soal yang dipilih.</p><div class="btn-row"><button class="btn btn--ghost" type="button" data-inline-close>Batal</button><button class="btn btn--outline" name="status" value="draft">Simpan draft</button><button class="btn btn--primary" name="status" value="published"><svg data-icon="send"></svg> Simpan &amp; terbitkan</button></div></footer>
    @endif
</form>

