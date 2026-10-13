@extends('layouts.admin.main')
@section('title', 'Quiz & Tryout — Admin LONTAR')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.dashboard') }}">Admin Dashboard</a><span class="crumbs__sep">›</span><span class="crumbs__current">Quiz &amp; Tryout</span></nav>
<header class="page-head page-head__row"><div><span class="eyebrow">Sesi Peserta</span><h1 style="margin-top:.5rem">Quiz &amp; Tryout</h1><p>Susun sesi dari bank soal, atur waktu dan penilaian, lalu terbitkan untuk peserta.</p></div><a class="btn btn--primary" href="{{ route('admin.exams.create') }}"><svg data-icon="plus"></svg> Buat sesi</a></header>
<form class="card question-filter question-section" method="GET">
    <div class="field question-filter-search"><label class="label" for="exam-search">Cari sesi</label><input class="input" id="exam-search" name="q" value="{{ request('q') }}"></div>
    <div class="field"><label class="label" for="exam-filter-kind">Jenis</label><select class="select" id="exam-filter-kind" name="kind"><option value="">Semua jenis</option><option value="quiz" @selected(request('kind') === 'quiz')>Latihan</option><option value="tryout" @selected(request('kind') === 'tryout')>Tryout CAT</option></select></div><button class="btn btn--primary">Cari</button>
</form>
<section class="card">
<div class="table-wrap"><table class="table"><thead><tr><th>Sesi</th><th>Jenis</th><th>Soal tersedia / diambil</th><th>Durasi</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse ($exams as $exam)
<tr><td><strong>{{ $exam->title }}</strong><p class="text-xs text-soft">{{ $exam->slug }}</p></td><td>{{ $exam->kind === 'quiz' ? 'Latihan' : 'Tryout CAT' }}</td><td>{{ $exam->available_count }} / {{ $exam->question_count }} @if ($exam->available_count < $exam->question_count)<span class="badge badge--amber badge--flat">Kurang soal</span>@endif</td><td>{{ $exam->duration_minutes ? $exam->duration_minutes.' menit' : 'Tanpa timer' }}</td><td><span class="badge {{ $exam->status->badgeClass() }}">{{ $exam->status->label() }}</span></td><td><div class="btn-row"><a class="btn btn--ghost btn--sm" href="{{ route('admin.exams.edit', $exam) }}">Edit</a><form method="POST" action="{{ route('admin.exams.destroy', $exam) }}" data-content-confirm data-confirm-title="Hapus sesi?" data-confirm-body="Sesi dihapus dari katalog. Riwayat dan sesi yang sudah berjalan tetap tersimpan.">@csrf @method('DELETE')<button class="btn btn--ghost btn--sm">Hapus</button></form></div></td></tr>
@empty
<tr><td colspan="6" class="text-soft">Belum ada sesi. Buat sesi dari soal yang sudah diterbitkan.</td></tr>
@endforelse
</tbody></table></div>
@if ($exams->hasPages())<div class="exam-pager"><x-pager :paginator="$exams" /></div>@endif
</section>
@endsection
