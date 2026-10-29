@extends('layouts.admin.main')
@section('title', ($exam->exists ? 'Edit' : 'Tambah').' '.$kind->label().' — Admin Ganesha Nusa')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb">@if($isQuiz)<a href="{{ route('admin.materials.index') }}">Materi</a><span class="crumbs__sep">›</span>@endif<a href="{{ route($routePrefix.'.index') }}">{{ $kind->label() }}</a><span class="crumbs__sep">›</span><span class="crumbs__current">{{ $exam->exists ? 'Edit' : 'Tambah' }} {{ $kind->label() }}</span></nav>
<form method="POST" enctype="multipart/form-data" action="{{ $exam->exists ? route($routePrefix.'.update', $exam) : route($routePrefix.'.store') }}" data-exam-form data-question-order="{{ json_encode($questionOrder) }}" data-default-section="{{ $exam->topic_id ? $topics->firstWhere('id', $exam->topic_id)?->section_id : '' }}">
@csrf @if ($exam->exists) @method('PUT') @endif
<header class="page-head content-page-head exam-form-head"><div><span class="eyebrow">{{ $kind->label() }}</span><h1>{{ $exam->exists ? 'Edit' : 'Tambah' }} {{ $kind->label() }}</h1><p>Pengaturan baru berlaku untuk pengerjaan berikutnya.</p></div><div class="btn-row"><a class="btn btn--ghost" href="{{ $returnUrl }}">{{ $isQuiz ? 'Kembali ke Materi' : 'Kembali' }}</a><button class="btn btn--outline" name="status" value="draft">Simpan draft</button><button class="btn btn--primary" name="status" value="published">Simpan &amp; terbitkan</button></div></header>
@if ($errors->any())<div class="article-errors" role="alert"><strong>Sesi belum tersimpan:</strong><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
<div class="exam-admin-layout">
<div class="question-form-stack">
<section class="card question-fields">
<div class="field question-wide"><label class="label" for="exam-title">Nama {{ strtolower($kind->label()) }}</label><input class="input" id="exam-title" name="title" maxlength="180" value="{{ old('title', $exam->title) }}" required></div>
<input type="hidden" name="kind" value="{{ $exam->kind }}">
<div class="field question-wide"><label class="label" for="exam-slug">Alamat sesi</label><input class="input" id="exam-slug" name="slug" data-auto-slug="exams" data-slug-url="{{ route('admin.slugs.preview') }}" data-slug-id="{{ $exam->id }}" value="{{ old('slug', $exam->slug) }}" placeholder="Otomatis dari judul" maxlength="80" pattern="[a-z0-9]+(-[a-z0-9]+)*" @readonly($exam->exists)><p class="field__hint">Terisi otomatis dari judul, dengan akhiran jika sudah dipakai. Alamat tetap setelah sesi dibuat.</p></div>
<div class="field question-wide"><label class="label" for="exam-description">Deskripsi</label><textarea class="textarea" id="exam-description" name="description" rows="3" maxlength="2000">{{ old('description', $exam->description) }}</textarea></div>
@unless($isQuiz)
<div class="field question-wide">
    <label class="label" for="exam-cover">Gambar kartu {{ strtolower($kind->label()) }}</label>
    <img class="exam-cover-preview" src="{{ $exam->coverUrl() }}" alt="Pratinjau gambar kartu {{ $exam->title ?: strtolower($kind->label()) }}" width="960" height="540">
    <input class="input" id="exam-cover" type="file" name="cover" accept="image/jpeg,image/png,image/webp" aria-describedby="exam-cover-help">
    <p class="field__hint" id="exam-cover-help">JPG, PNG, atau WebP, maksimal 5 MB. Disarankan gambar mendatar 16:9. Tanpa unggahan, kartu memakai ilustrasi bawaan.</p>
    @error('cover')<p class="field__hint" role="alert">{{ $message }}</p>@enderror
    @if($exam->cover_path)<label class="check"><input type="checkbox" name="remove_cover" value="1" @checked(old('remove_cover'))> Hapus gambar unggahan dan gunakan ilustrasi bawaan</label>@endif
</div>
@endunless
@php($selectedAccess = old('access_level', $exam->free_claim_enabled ? 'conditional' : 'premium'))
<div class="field question-wide"><label class="label" for="exam-access">Level akses</label><select class="select" id="exam-access" name="access_level"><option value="premium" @selected($selectedAccess === 'premium')>Premium — akses mengikuti paket peserta</option>@unless($isQuiz)<option value="conditional" @selected($selectedAccess === 'conditional')>Gratis Bersyarat — perlu persetujuan bukti</option>@endunless</select><p class="field__hint">Tryout Premium memakai kuota paket. Gratis Bersyarat terbuka setelah bukti peserta disetujui admin.</p></div>
@unless($isQuiz)
<div class="question-wide question-form-stack" data-exam-claim-settings @if($selectedAccess !== 'conditional') hidden @endif>
    <div class="field"><label class="label" for="exam-claim-terms">Syarat unlock gratis</label><textarea class="textarea" id="exam-claim-terms" name="free_claim_terms" rows="5" maxlength="5000" @disabled($selectedAccess !== 'conditional') @required($selectedAccess === 'conditional')>{{ old('free_claim_terms', $exam->free_claim_terms) }}</textarea><p class="field__hint">Tuliskan langkah yang harus dilakukan peserta dan bukti yang wajib diunggah. Admin memeriksa bukti sebelum sesi dibuka.</p>@error('free_claim_terms')<p class="field__hint" role="alert">{{ $message }}</p>@enderror</div>
    <div class="field"><label class="label" for="exam-claim-instagram">Tautan akun Instagram</label><input class="input" id="exam-claim-instagram" type="url" name="free_claim_instagram_url" maxlength="255" placeholder="https://www.instagram.com/ganeshanusa/" value="{{ old('free_claim_instagram_url', $exam->free_claim_instagram_url) }}" @disabled($selectedAccess !== 'conditional') @required($selectedAccess === 'conditional')>@error('free_claim_instagram_url')<p class="field__hint" role="alert">{{ $message }}</p>@enderror</div>
</div>
@endunless
<div class="field"><label class="label" for="exam-count">Jumlah soal per pengerjaan</label><input class="input" id="exam-count" type="number" name="question_count" min="1" max="200" value="{{ old('question_count', $exam->question_count) }}" required></div>
<div class="field"><label class="label" for="exam-duration">Durasi (menit)</label><input class="input" id="exam-duration" type="number" name="duration_minutes" min="{{ $isQuiz ? 0 : 1 }}" max="240" value="{{ old('duration_minutes', $exam->duration_minutes) }}" required><p class="field__hint">{{ $isQuiz ? 'Isi 0 untuk latihan tanpa timer.' : $kind->label().' wajib memiliki durasi 1–240 menit.' }}</p></div>
</section>
@if ($isQuiz)
<section class="card question-form-stack" data-exam-topic-settings>
<div><h2 class="card__title">Latihan Topik</h2><p class="card__sub">Latihan Topik tampil di bawah materi pada topik yang dipilih.</p></div>
<div class="field"><label class="label" for="exam-topic">Topik latihan</label><select class="select" id="exam-topic" name="topic_id"><option value="">Tanpa topik — latihan umum</option>
@foreach ($topics as $topicOption)<option value="{{ $topicOption->id }}" data-section-id="{{ $topicOption->section_id }}" @selected((string) old('topic_id', $exam->topic_id) === (string) $topicOption->id)>{{ $topicOption->section->name }} · {{ $topicOption->name }}{{ $topicOption->is_active && $topicOption->section->is_active ? '' : ' — nonaktif' }}</option>@endforeach
</select><p class="field__hint">Pilih soal yang sesuai materi melalui kumpulan soal sesi. Topik nonaktif beserta latihannya disembunyikan dari peserta.</p></div>
<div class="field"><label class="label" for="exam-completion">Kapan latihan bisa dimulai?</label><select class="select" id="exam-completion" name="requires_topic_completion"><option value="0" @selected(! old('requires_topic_completion', $exam->requires_topic_completion))>Kapan saja</option><option value="1" @selected(old('requires_topic_completion', $exam->requires_topic_completion))>Setelah semua materi topik ditandai selesai</option></select><p class="field__hint">Syarat selesai menghitung seluruh materi terbit dalam topik, termasuk materi premium. Sesi yang sudah dimulai tetap dapat dilanjutkan.</p></div>
</section>
@endif
<section class="card question-form-stack">
<div class="card__title">Penilaian &amp; randomisasi</div>
<p class="text-sm text-soft">Nilai {{ strtolower($kind->label()) }} menjumlahkan poin soal biasa dan skor pilihan TKP, dikurangi penalti pada jawaban salah soal biasa. Jawaban kosong bernilai 0. TKP berbobot memakai skor 1–5 per pilihan dan tidak memakai penalti salah. Nilai ambang memakai satuan poin.</p>
<div class="question-fields"><div class="field"><label class="label" for="exam-points">Poin per jawaban benar</label><input class="input" id="exam-points" type="number" name="points_per_correct" min="1" max="100" value="{{ old('points_per_correct', $exam->points_per_correct) }}" required></div><div class="field"><label class="label" for="exam-penalty">Penalti per jawaban salah</label><input class="input" id="exam-penalty" type="number" name="penalty_wrong" min="0" max="100" value="{{ old('penalty_wrong', $exam->penalty_wrong) }}" required></div>@if ($exam->kind !== 'tryout')<div class="field question-wide"><label class="label" for="exam-passing">Nilai ambang (opsional)</label><input class="input" id="exam-passing" type="number" name="passing_score" min="0" value="{{ old('passing_score', $exam->passing_score) }}"><p class="field__hint">Kosongkan jika sesi tidak memiliki nilai ambang.</p></div>@else<div class="question-wide"><x-passing-grades /></div>@endif</div>
<input type="hidden" name="random_questions" value="0"><label class="check"><input type="checkbox" name="random_questions" value="1" @checked(old('random_questions', $exam->random_questions))> Acak pengambilan dan urutan soal tiap pengerjaan</label>
<input type="hidden" name="random_options" value="0"><label class="check"><input type="checkbox" name="random_options" value="1" @checked(old('random_options', $exam->random_options))> Acak urutan pilihan jawaban</label>
<p class="field__hint">Urutan tetap selama sesi berjalan, termasuk setelah refresh.</p>
</section>
</div>
<section class="card exam-question-manager" id="soal-sesi">
<header class="exam-question-manager__head"><div><h2 class="card__title">Kumpulan soal sesi</h2><p class="card__sub">Pilih soal yang dipakai, atau tambah dan edit soal langsung di sini.</p></div><button class="btn btn--primary btn--sm" type="button" data-add-question><svg data-icon="plus"></svg> Tambah Soal</button></header>
<div class="exam-question-feedback" data-exam-question-message role="status" hidden></div>
<section class="exam-question-order" data-question-order hidden aria-labelledby="exam-order-title">
    <div class="exam-question-order__heading"><h3 id="exam-order-title">Urutan soal dipilih</h3><span class="badge badge--primary badge--flat" data-question-order-count>0 soal</span></div>
    <p class="field__hint" id="exam-order-help">Geser pegangan titik untuk mengurutkan soal, atau gunakan tombol naik/turun. Simpan sesi untuk menerapkan urutan.</p>
    <div class="exam-question-order__random" data-question-order-random hidden>
        <p>Pengacakan soal aktif. Nonaktifkan agar peserta mengikuti urutan di bawah ini.</p>
        <button class="btn btn--outline btn--sm" type="button" data-question-order-use>Gunakan urutan ini</button>
    </div>
    <p class="field__hint" data-question-order-limit></p>
    <ol class="exam-question-order__list" data-question-order-list aria-label="Urutan soal dipilih" aria-describedby="exam-order-help"></ol>
    <p class="field__hint" data-question-order-empty>Belum ada soal dipilih. Centang soal dari kumpulan di bawah.</p>
    <p class="sr-only" role="status" aria-live="polite" data-question-order-status></p>
    <template data-question-order-template>
        <li class="exam-order-item" data-question-order-item>
            <button class="exam-order-handle" type="button" data-question-order-handle aria-describedby="exam-order-help"><span aria-hidden="true">⠿</span></button>
            <span class="exam-order-number" data-question-order-number aria-hidden="true"></span>
            <span class="exam-order-title" data-question-order-title></span>
            <input type="hidden" name="question_ids[]">
            <div class="exam-order-actions">
                <button class="content-icon-button" type="button" data-question-order-up title="Naikkan soal"><svg data-icon="chevron-up"></svg></button>
                <button class="content-icon-button" type="button" data-question-order-down title="Turunkan soal"><svg data-icon="chevron-down"></svg></button>
            </div>
        </li>
    </template>
</section>
<div class="exam-pool-filters"><div class="input-group"><span class="input-group__icon"><svg data-icon="search"></svg></span><input class="input" id="exam-question-search" type="search" placeholder="Cari sesi, judul soal, atau bidang..." aria-label="Cari soal sesi" data-exam-question-search></div><select class="select" data-exam-question-section aria-label="Filter bidang soal"><option value="">Semua bidang</option>@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->name }}</option>@endforeach</select></div>
<div class="exam-pool-summary"><label class="check"><input type="checkbox" data-exam-selected-only> Hanya soal dipilih</label><span class="badge badge--primary badge--flat" data-exam-selected aria-live="polite">0 soal dipilih</span></div>
@include('Dashboard.Admin.exams._question-sources')
<div class="exam-pool-empty" data-exam-pool-empty @if($questions->isNotEmpty()) hidden @endif><svg data-icon="search"></svg><strong>Tidak ada soal yang sesuai</strong><p>Ubah filter atau tambahkan soal baru untuk sesi ini.</p></div>
<p class="field__hint">Pilihan dan urutan soal diterapkan saat sesi disimpan. Soal draft atau belum tersedia dilewati untuk pengerjaan baru; nomor soal peserta menyesuaikan.</p>
<noscript><p class="field__hint">Aktifkan JavaScript untuk menyinkronkan pilihan soal yang muncul di beberapa sesi.</p></noscript>
</section>
</div>
</form>
@endsection
@section('modals')
<dialog class="exam-question-dialog" data-exam-question-dialog aria-labelledby="inline-question-title">
  @include('Dashboard.Admin.questions._form', ['question' => $inlineQuestion, 'inline' => true, 'selectedExamIds' => []])
</dialog>
@endsection
