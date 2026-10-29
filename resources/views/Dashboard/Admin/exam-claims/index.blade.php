@extends('layouts.admin.main')
@section('title', 'Unlock Sesi Gratis — Admin Ganesha Nusa')
@section('content')
<header class="page-head page-head__row"><div><span class="eyebrow">Verifikasi Peserta</span><h1 style="margin-top:.5rem">Unlock Sesi Gratis</h1><p>Semua pengajuan tetap tercatat, termasuk yang diterima dan ditolak. Gunakan filter untuk meninjau statusnya.</p></div><span class="badge badge--amber badge--flat">{{ $pendingCount }} menunggu</span></header>
<nav class="btn-row question-section" aria-label="Filter status pengajuan">@foreach (['all' => 'Semua pengajuan', 'pending' => 'Butuh verifikasi', 'approved' => 'Diterima', 'rejected' => 'Ditolak'] as $value => $label)<a class="btn btn--{{ $status === $value ? 'primary' : 'ghost' }} btn--sm" href="{{ route('admin.exam-claims.index', ['status' => $value]) }}" @if ($status === $value) aria-current="page" @endif>{{ $label }}</a>@endforeach</nav>
<section class="card"><div class="table-wrap"><table class="table"><thead><tr><th>Peserta</th><th>Sesi</th><th>Diajukan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse ($claims as $claim)
<tr><td><strong>{{ $claim->user?->name ?? 'Akun dihapus' }}</strong><p class="text-xs text-soft">{{ $claim->user?->email }}</p><p class="text-xs text-soft">Instagram: {{ '@'.$claim->instagram_username }}</p></td><td>{{ $claim->exam_title }}<p class="text-xs text-soft">{{ \App\Enums\ExamKind::from($claim->kind)->label() }}</p></td><td><x-local-time :value="$claim->created_at" format="d M Y H:i" /></td><td><span class="badge badge--{{ $claim->status === 'approved' ? 'mint' : ($claim->status === 'rejected' ? 'coral' : 'amber') }} badge--flat">{{ match ($claim->status) { 'approved' => 'Diterima', 'rejected' => 'Ditolak', default => 'Butuh verifikasi' } }}</span></td><td><a class="btn btn--ghost btn--sm" href="{{ route('admin.exam-claims.show', $claim) }}">Periksa bukti</a></td></tr>
@empty<tr><td colspan="5" class="text-soft">Belum ada pengajuan dengan status ini.</td></tr>@endforelse
</tbody></table></div>@if ($claims->hasPages())<div class="exam-pager"><x-pager :paginator="$claims" /></div>@endif</section>
@endsection
