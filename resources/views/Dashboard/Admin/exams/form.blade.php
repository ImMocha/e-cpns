@extends('layouts.admin.main')
@section('title', ($exam->exists ? 'Edit' : 'Buat').' Sesi — Admin LONTAR')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.exams.index') }}">Quiz &amp; Tryout</a><span class="crumbs__sep">›</span><span class="crumbs__current">{{ $exam->exists ? 'Edit' : 'Buat' }} Sesi</span></nav>
<form method="POST" action="{{ $exam->exists ? route('admin.exams.update', $exam) : route('admin.exams.store') }}" data-exam-form>
@csrf @if ($exam->exists) @method('PUT') @endif
<header class="page-head page-head__row"><div><span class="eyebrow">Pengaturan Sesi</span><h1 style="margin-top:.5rem">{{ $exam->exists ? 'Edit Sesi' : 'Buat Sesi' }}</h1><p>Pengaturan baru berlaku untuk pengerjaan berikutnya.</p></div><div class="btn-row"><a class="btn btn--ghost" href="{{ route('admin.exams.index') }}">Kembali</a><button class="btn btn--outline" name="status" value="draft">Simpan draft</button><button class="btn btn--primary" name="status" value="published">Simpan &amp; terbitkan</button></div></header>
@if ($errors->any())<div class="article-errors" role="alert"><strong>Sesi belum tersimpan:</strong><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
<div class="exam-admin-layout">
<div class="question-form-stack">
<section class="card question-fields">
<div class="field question-wide"><label class="label" for="exam-title">Nama sesi</label><input class="input" id="exam-title" name="title" maxlength="180" value="{{ old('title', $exam->title) }}" required></div>
<div class="field"><label class="label" for="exam-kind">Jenis sesi</label>@if ($exam->exists)<input type="hidden" name="kind" value="{{ $exam->kind }}"><input class="input" value="{{ $exam->kind === 'quiz' ? 'Latihan' : 'Tryout CAT' }}" readonly>@else<select class="select" id="exam-kind" name="kind"><option value="quiz" @selected(old('kind', $exam->kind) === 'quiz')>Latihan</option><option value="tryout" @selected(old('kind', $exam->kind) === 'tryout')>Tryout CAT</option></select>@endif</div>
<div class="field"><label class="label" for="exam-slug">Alamat sesi</label><input class="input" id="exam-slug" name="slug" value="{{ old('slug', $exam->slug) }}" placeholder="contoh: latihan-tiu" maxlength="80" pattern="[a-z0-9]+(-[a-z0-9]+)*" @readonly($exam->exists) required><p class="field__hint">Huruf kecil, angka, dan tanda hubung. Alamat tetap setelah sesi dibuat.</p></div>
<div class="field question-wide"><label class="label" for="exam-description">Deskripsi</label><textarea class="textarea" id="exam-description" name="description" rows="3" maxlength="2000">{{ old('description', $exam->description) }}</textarea></div>
<div class="field"><label class="label" for="exam-count">Jumlah soal per pengerjaan</label><input class="input" id="exam-count" type="number" name="question_count" min="1" max="200" value="{{ old('question_count', $exam->question_count) }}" required></div>
<div class="field"><label class="label" for="exam-duration">Durasi (menit)</label><input class="input" id="exam-duration" type="number" name="duration_minutes" min="0" max="240" value="{{ old('duration_minutes', $exam->duration_minutes) }}" required><p class="field__hint">0 = tanpa timer untuk latihan. Tryout wajib 1–240 menit.</p></div>
</section>
<section class="card question-form-stack">
<div class="card__title">Penilaian &amp; randomisasi</div>
<p class="text-sm text-soft">Latihan memakai skala 0–100. Tryout menjumlahkan poin benar dikurangi penalti salah; jawaban kosong bernilai 0. Nilai minimum 0.</p>
<div class="question-fields"><div class="field"><label class="label" for="exam-points">Poin per jawaban benar</label><input class="input" id="exam-points" type="number" name="points_per_correct" min="1" max="100" value="{{ old('points_per_correct', $exam->points_per_correct) }}" required></div><div class="field"><label class="label" for="exam-penalty">Penalti per jawaban salah</label><input class="input" id="exam-penalty" type="number" name="penalty_wrong" min="0" max="100" value="{{ old('penalty_wrong', $exam->penalty_wrong) }}" required></div><div class="field question-wide"><label class="label" for="exam-passing">Nilai ambang (opsional)</label><input class="input" id="exam-passing" type="number" name="passing_score" min="0" value="{{ old('passing_score', $exam->passing_score) }}"><p class="field__hint">Kosongkan jika sesi tidak memiliki nilai ambang.</p></div></div>
<input type="hidden" name="random_questions" value="0"><label class="check"><input type="checkbox" name="random_questions" value="1" @checked(old('random_questions', $exam->random_questions))> Acak pengambilan dan urutan soal tiap pengerjaan</label>
<input type="hidden" name="random_options" value="0"><label class="check"><input type="checkbox" name="random_options" value="1" @checked(old('random_options', $exam->random_options))> Acak urutan pilihan jawaban</label>
<p class="field__hint">Urutan tetap selama sesi berjalan, termasuk setelah refresh.</p>
</section>
</div>
<section class="card question-form-stack">
<div><div class="card__title">Kumpulan soal sesi</div><p class="card__sub">Pilih bank sumber. Soal diambil sebanyak jumlah yang diatur.</p></div>
<div class="field"><label class="label" for="exam-question-search">Cari judul atau bidang</label><input class="input" id="exam-question-search" type="search" placeholder="Cari soal..." data-exam-question-search></div>
<p class="text-sm text-soft" data-exam-selected aria-live="polite"></p>
<div class="exam-question-pool">
@foreach ($questions as $question)
@php($available = $question->status === \App\Enums\QuestionStatus::Published && $question->published_at?->lessThanOrEqualTo(now()) && $question->section?->is_active)
<label class="exam-pool-item" data-exam-pool-item><input type="checkbox" name="question_ids[]" value="{{ $question->id }}" @checked(in_array($question->id, is_array(old('question_ids', $selected)) ? old('question_ids', $selected) : []))><span><strong>{{ $question->title }}</strong><span class="text-xs text-soft">{{ $question->section?->name ?? 'Bidang dihapus' }} · {{ $question->difficulty->label() }} · {{ $available ? 'Terbit' : 'Tidak tersedia untuk sesi baru' }}{{ $question->video_path ? ' · Video' : '' }}{{ $question->image_path ? ' · Gambar' : '' }}</span></span><a href="{{ route('admin.questions.edit', $question) }}" aria-label="Edit {{ $question->title }}" target="_blank" rel="noopener">↗</a></label>
@endforeach
</div>
@if ($questions->isEmpty())<p class="text-soft">Bank soal masih kosong. <a href="{{ route('admin.questions.create') }}">Tambahkan soal</a> terlebih dahulu.</p>@endif
<p class="field__hint">Draft, soal belum tayang, bidang nonaktif, dan soal yang dihapus tidak diambil untuk sesi baru.</p>
</section>
</div>
</form>
@endsection
