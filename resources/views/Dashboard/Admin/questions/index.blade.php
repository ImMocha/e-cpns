@extends('layouts.admin.main')
@section('title', 'Soal & Pembahasan — Ganesha Nusa')
@section('content')
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.dashboard') }}">Admin Dashboard</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><span class="crumbs__current">Soal &amp; Pembahasan</span></nav>
    <header class="page-head page-head__row">
        <div><span class="eyebrow">Konten Ujian</span><h1 style="margin-top:.5rem">Soal &amp; Pembahasan</h1><p>Lihat penggunaan soal di latihan dan tryout, lalu atur sesi tujuannya.</p></div>
        <a class="btn btn--primary" href="{{ route('admin.questions.create') }}"><svg data-icon="plus"></svg> Tambah Soal</a>
    </header>
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 question-section">
        @foreach (['total' => ['Total Soal', 'layers', ''], 'published' => ['Terbit', 'check', 'ico--mint'], 'draft' => ['Draft', 'pencil', 'ico--amber'], 'sections' => ['Bidang Terisi', 'folder', 'ico--pink']] as $key => [$label, $icon, $tone])
            <article class="stat"><span class="stat__icon {{ $tone }}"><svg data-icon="{{ $icon }}"></svg></span><div><div class="stat__label">{{ $label }}</div><div class="stat__value">{{ number_format($stats[$key], 0, ',', '.') }}</div></div></article>
        @endforeach
    </section>
    <form method="GET" class="card question-filter question-management-filter question-section">
        <div class="field question-filter-search"><label class="label" for="question-search">Cari soal</label><input class="input" id="question-search" name="q" value="{{ request('q') }}" placeholder="Judul atau teks pertanyaan"></div>
        <div class="field"><label class="label" for="question-field">Bidang</label><select class="select" id="question-field" name="section_id"><option value="">Semua bidang</option>@foreach ($sections as $section)<option value="{{ $section->id }}" @selected((string) request('section_id') === (string) $section->id)>{{ $section->name }}</option>@endforeach</select></div>
        <div class="field"><label class="label" for="question-difficulty">Kesulitan</label><select class="select" id="question-difficulty" name="difficulty"><option value="">Semua level</option>@foreach ($difficulties as $difficulty)<option value="{{ $difficulty->value }}" @selected(request('difficulty') === $difficulty->value)>{{ $difficulty->label() }}</option>@endforeach</select></div>
        <div class="field"><label class="label" for="question-status">Status</label><select class="select" id="question-status" name="status"><option value="">Semua status</option><option value="draft" @selected(request('status') === 'draft')>Draft</option><option value="published" @selected(request('status') === 'published')>Terbit</option></select></div>
        <div class="field"><label class="label" for="question-usage">Penggunaan</label><select class="select" id="question-usage" name="usage"><option value="">Semua penggunaan</option><option value="quiz" @selected(request('usage') === 'quiz')>Dipakai di latihan topik</option><option value="practice" @selected(request('usage') === 'practice')>Dipakai di latihan soal</option><option value="tryout" @selected(request('usage') === 'tryout')>Dipakai di tryout</option><option value="unused" @selected(request('usage') === 'unused')>Belum masuk sesi</option></select></div>
        <div class="field question-filter-session"><label class="label" for="question-exam">Sesi tertentu</label><select class="select" id="question-exam" name="exam_id"><option value="">Semua sesi</option>@foreach (['quiz' => 'Latihan Topik', 'practice' => 'Latihan Soal', 'tryout' => 'Tryout CAT'] as $kind => $label)<optgroup label="{{ $label }}">@foreach ($exams->where('kind', $kind) as $exam)<option value="{{ $exam->id }}" @selected((string) request('exam_id') === (string) $exam->id)>{{ $exam->title }}{{ $exam->status === \App\Enums\QuestionStatus::Draft ? ' (draft)' : '' }}</option>@endforeach</optgroup>@endforeach</select></div>
        <div class="btn-row"><button class="btn btn--primary">Cari</button><a class="btn btn--ghost" href="{{ route('admin.questions.index') }}">Reset</a></div>
    </form>
    <section class="card">
        <div class="card__head"><div class="card__title">Daftar Soal</div><span class="text-sm text-soft">{{ $questions->total() }} soal ditemukan</span></div>
        <div class="table-wrap question-management-wrap"><table class="table question-management-table">
            <thead><tr><th>Soal</th><th>Bidang &amp; level</th><th>Digunakan di</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($questions as $question)
                    <tr>
                        <td class="question-title-cell"><a class="font-bold text-primary" href="{{ route('admin.questions.edit', $question) }}">{{ $question->title }}</a><div class="text-sm text-soft">{{ \Illuminate\Support\Str::limit(\App\ArticleContent::text($question->body), 95) }}</div><div class="text-xs text-soft">Kunci {{ chr(65 + $question->correct_option) }} · {{ $question->author?->name ?? 'Admin' }} · <x-local-time :value="$question->updated_at" format="d M Y" /></div></td>
                        <td data-label="Bidang & level"><strong>{{ $question->section?->name ?? 'Bidang dihapus' }}</strong><div class="text-xs text-soft">{{ $question->difficulty->label() }}</div></td>
                        <td class="question-usage-cell" data-label="Digunakan di">
                            <div class="question-usage-list">
                                @forelse ($question->exams as $exam)
                                    <a class="question-usage-link" href="{{ route(\App\Enums\ExamKind::from($exam->kind)->adminRoutePrefix().'.edit', $exam) }}"><span class="badge {{ $exam->kind === 'tryout' ? 'badge--amber' : 'badge--primary' }} badge--flat">{{ \App\Enums\ExamKind::from($exam->kind)->label() }}</span><span>{{ $exam->title }}@if ($exam->status === \App\Enums\QuestionStatus::Draft)<span class="text-xs text-soft"> · Draft</span>@endif</span><svg data-icon="arrow-right"></svg></a>
                                @empty
                                    <span class="text-sm text-soft">Belum masuk sesi</span>
                                    <a class="text-xs font-bold text-primary" href="{{ route('admin.questions.edit', $question) }}#sesi-tujuan">Pilih sesi tujuan <span aria-hidden="true">→</span></a>
                                @endforelse
                            </div>
                        </td>
                        <td data-label="Status"><span class="badge {{ $question->status->badgeClass() }}">{{ $question->status->label() }}</span></td>
                        <td class="question-management-actions"><div class="btn-row question-actions">
                            <a class="btn btn--ghost btn--sm" href="{{ route('admin.questions.edit', $question) }}" aria-label="Edit {{ $question->title }}"><svg data-icon="pencil"></svg> Edit</a>
                            <a class="btn btn--ghost btn--square btn--sm" href="{{ route('admin.questions.show', $question) }}" aria-label="Preview {{ $question->title }}"><svg data-icon="eye"></svg></a>
                            <form method="POST" action="{{ route('admin.questions.destroy', $question) }}" data-content-confirm data-confirm-title="Hapus soal?" data-confirm-body="Soal ini akan diarsipkan dan tidak digunakan pada pengerjaan baru. Riwayat peserta tetap tersimpan.">@csrf @method('DELETE')<button class="btn btn--ghost btn--square btn--sm" aria-label="Hapus {{ $question->title }}"><svg data-icon="trash"></svg></button></form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="question-management-empty"><div class="question-empty"><svg data-icon="clipboard"></svg><strong>Belum ada soal yang sesuai</strong><p class="text-soft">Tambahkan soal baru atau ubah pencarian dan filter.</p></div></td></tr>
                @endforelse
            </tbody>
        </table></div>
        <x-pager :paginator="$questions" />
    </section>
@endsection

