@extends('layouts.admin.main')
@section('title', ($exam->exists ? 'Edit' : 'Tambah').' '.($isQuiz ? 'Latihan' : 'Tryout').' — Admin Ganesha Nusa')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb">@if($isQuiz)<a href="{{ route('admin.materials.index') }}">Materi</a><span class="crumbs__sep">›</span>@endif<a href="{{ route($routePrefix.'.index') }}">{{ $isQuiz ? 'Latihan' : 'Tryout' }}</a><span class="crumbs__sep">›</span><span class="crumbs__current">{{ $exam->exists ? 'Edit' : 'Tambah' }} {{ $isQuiz ? 'Latihan' : 'Tryout' }}</span></nav>
<form method="POST" action="{{ $exam->exists ? route($routePrefix.'.update', $exam) : route($routePrefix.'.store') }}" data-exam-form data-default-section="{{ $exam->topic_id ? $topics->firstWhere('id', $exam->topic_id)?->section_id : '' }}">
@csrf @if ($exam->exists) @method('PUT') @endif
<header class="page-head content-page-head exam-form-head"><div><span class="eyebrow">{{ $isQuiz ? 'Latihan Pembelajaran' : 'Simulasi Ujian' }}</span><h1>{{ $exam->exists ? 'Edit' : 'Tambah' }} {{ $isQuiz ? 'Latihan' : 'Tryout' }}</h1><p>Pengaturan baru berlaku untuk pengerjaan berikutnya.</p></div><div class="btn-row"><a class="btn btn--ghost" href="{{ $returnUrl }}">{{ $isQuiz ? 'Kembali ke Materi' : 'Kembali' }}</a><button class="btn btn--outline" name="status" value="draft">Simpan draft</button><button class="btn btn--primary" name="status" value="published">Simpan &amp; terbitkan</button></div></header>
@if ($errors->any())<div class="article-errors" role="alert"><strong>Sesi belum tersimpan:</strong><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
<div class="exam-admin-layout">
<div class="question-form-stack">
<section class="card question-fields">
<div class="field question-wide"><label class="label" for="exam-title">Nama {{ $isQuiz ? 'latihan' : 'tryout' }}</label><input class="input" id="exam-title" name="title" maxlength="180" value="{{ old('title', $exam->title) }}" required></div>
<input type="hidden" name="kind" value="{{ $exam->kind }}">
<div class="field question-wide"><label class="label" for="exam-slug">Alamat sesi</label><input class="input" id="exam-slug" name="slug" value="{{ old('slug', $exam->slug) }}" placeholder="{{ $isQuiz ? 'contoh: latihan-tiu' : 'contoh: tryout-skd' }}" maxlength="80" pattern="[a-z0-9]+(-[a-z0-9]+)*" @readonly($exam->exists) required><p class="field__hint">Huruf kecil, angka, dan tanda hubung. Alamat tetap setelah sesi dibuat.</p></div>
<div class="field question-wide"><label class="label" for="exam-description">Deskripsi</label><textarea class="textarea" id="exam-description" name="description" rows="3" maxlength="2000">{{ old('description', $exam->description) }}</textarea></div>
<div class="field question-wide"><label class="label" for="exam-access">Level akses</label><select class="select" id="exam-access" name="access_level"><option value="free" @selected(old('access_level', $exam->access_level?->value ?? 'free') === 'free')>Gratis</option><option value="premium" @selected(old('access_level', $exam->access_level?->value) === 'premium')>Premium — memerlukan paket yang memuat sesi ini</option></select><p class="field__hint">{{ $isQuiz ? 'Latihan premium tidak memotong kuota tryout.' : 'Tryout premium memakai kuota paket peserta.' }}</p></div>
<div class="field"><label class="label" for="exam-count">Jumlah soal per pengerjaan</label><input class="input" id="exam-count" type="number" name="question_count" min="1" max="200" value="{{ old('question_count', $exam->question_count) }}" required></div>
<div class="field"><label class="label" for="exam-duration">Durasi (menit)</label><input class="input" id="exam-duration" type="number" name="duration_minutes" min="{{ $isQuiz ? 0 : 1 }}" max="240" value="{{ old('duration_minutes', $exam->duration_minutes) }}" required><p class="field__hint">{{ $isQuiz ? 'Isi 0 untuk latihan tanpa timer.' : 'Tryout wajib memiliki durasi 1–240 menit.' }}</p></div>
</section>
@if ($isQuiz)
<section class="card question-form-stack" data-exam-topic-settings>
<div><h2 class="card__title">Latihan pada topik</h2><p class="card__sub">Latihan tampil di bawah materi pada topik yang dipilih.</p></div>
<div class="field"><label class="label" for="exam-topic">Topik latihan</label><select class="select" id="exam-topic" name="topic_id"><option value="">Tanpa topik — latihan umum</option>
@foreach ($topics as $topicOption)<option value="{{ $topicOption->id }}" data-section-id="{{ $topicOption->section_id }}" @selected((string) old('topic_id', $exam->topic_id) === (string) $topicOption->id)>{{ $topicOption->section->name }} · {{ $topicOption->name }}{{ $topicOption->is_active && $topicOption->section->is_active ? '' : ' — nonaktif' }}</option>@endforeach
</select><p class="field__hint">Pilih soal yang sesuai materi melalui kumpulan soal sesi. Topik nonaktif beserta latihannya disembunyikan dari peserta.</p></div>
<div class="field"><label class="label" for="exam-completion">Kapan latihan bisa dimulai?</label><select class="select" id="exam-completion" name="requires_topic_completion"><option value="0" @selected(! old('requires_topic_completion', $exam->requires_topic_completion))>Kapan saja</option><option value="1" @selected(old('requires_topic_completion', $exam->requires_topic_completion))>Setelah semua materi topik ditandai selesai</option></select><p class="field__hint">Syarat selesai menghitung seluruh materi terbit dalam topik, termasuk materi premium. Sesi yang sudah dimulai tetap dapat dilanjutkan.</p></div>
</section>
@endif
<section class="card question-form-stack">
<div class="card__title">Penilaian &amp; randomisasi</div>
<p class="text-sm text-soft">{{ $isQuiz ? 'Nilai latihan memakai skala 0–100 dari total poin yang diperoleh dibandingkan skor maksimal.' : 'Nilai tryout menjumlahkan poin soal biasa dan skor pilihan TKP, dikurangi penalti pada jawaban salah soal biasa.' }} Jawaban kosong bernilai 0. TKP berbobot memakai skor 1–5 per pilihan dan tidak memakai penalti salah.</p>
<div class="question-fields"><div class="field"><label class="label" for="exam-points">Poin per jawaban benar</label><input class="input" id="exam-points" type="number" name="points_per_correct" min="1" max="100" value="{{ old('points_per_correct', $exam->points_per_correct) }}" required></div><div class="field"><label class="label" for="exam-penalty">Penalti per jawaban salah</label><input class="input" id="exam-penalty" type="number" name="penalty_wrong" min="0" max="100" value="{{ old('penalty_wrong', $exam->penalty_wrong) }}" required></div><div class="field question-wide"><label class="label" for="exam-passing">Nilai ambang (opsional)</label><input class="input" id="exam-passing" type="number" name="passing_score" min="0" value="{{ old('passing_score', $exam->passing_score) }}"><p class="field__hint">Kosongkan jika sesi tidak memiliki nilai ambang.</p></div></div>
<input type="hidden" name="random_questions" value="0"><label class="check"><input type="checkbox" name="random_questions" value="1" @checked(old('random_questions', $exam->random_questions))> Acak pengambilan dan urutan soal tiap pengerjaan</label>
<input type="hidden" name="random_options" value="0"><label class="check"><input type="checkbox" name="random_options" value="1" @checked(old('random_options', $exam->random_options))> Acak urutan pilihan jawaban</label>
<p class="field__hint">Urutan tetap selama sesi berjalan, termasuk setelah refresh.</p>
</section>
</div>
<section class="card exam-question-manager" id="soal-sesi">
<header class="exam-question-manager__head"><div><h2 class="card__title">Kumpulan soal sesi</h2><p class="card__sub">Pilih soal yang dipakai, atau tambah dan edit soal langsung di sini.</p></div><button class="btn btn--primary btn--sm" type="button" data-add-question><svg data-icon="plus"></svg> Tambah Soal</button></header>
<div class="exam-question-feedback" data-exam-question-message role="status" hidden></div>
<div class="exam-pool-filters"><div class="input-group"><span class="input-group__icon"><svg data-icon="search"></svg></span><input class="input" id="exam-question-search" type="search" placeholder="Cari judul soal atau bidang..." aria-label="Cari soal sesi" data-exam-question-search></div><select class="select" data-exam-question-section aria-label="Filter bidang soal"><option value="">Semua bidang</option>@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->name }}</option>@endforeach</select></div>
<div class="exam-pool-summary"><label class="check"><input type="checkbox" data-exam-selected-only> Hanya soal dipilih</label><span class="badge badge--primary badge--flat" data-exam-selected aria-live="polite">0 soal dipilih</span></div>
<div class="exam-question-pool" data-exam-question-pool>
@foreach ($questions as $question)
@include('Dashboard.Admin.exams._question', ['question' => $question, 'isSelected' => in_array($question->id, is_array(old('question_ids', $selected)) ? old('question_ids', $selected) : [])])
@endforeach
</div>
<div class="exam-pool-empty" data-exam-pool-empty @if($questions->isNotEmpty()) hidden @endif><svg data-icon="search"></svg><strong>Tidak ada soal yang sesuai</strong><p>Ubah filter atau tambahkan soal baru untuk sesi ini.</p></div>
<p class="field__hint">Pilihan kumpulan soal diterapkan saat sesi disimpan. Soal draft atau belum tersedia tidak diambil untuk pengerjaan baru.</p>
</section>
</div>
</form>
@endsection
@section('modals')
<dialog class="exam-question-dialog" data-exam-question-dialog aria-labelledby="inline-question-title">
  @include('Dashboard.Admin.questions._form', ['question' => $inlineQuestion, 'inline' => true, 'selectedExamIds' => []])
</dialog>
@endsection
