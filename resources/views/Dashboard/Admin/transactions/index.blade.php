@extends('layouts.admin.main')
@section('title', 'Transaksi — Admin Ganesha Nusa')
@section('content')
<header class="page-head"><span class="eyebrow">Pembelian Paket</span><h1 style="margin-top:.5rem">Transaksi</h1><p>Pembayaran peserta dan aktivasi akses paket.</p></header>
<form class="card question-filter question-section" method="GET">
    <div class="field question-filter-search"><label class="label" for="transaction-search">Nama, email, atau nomor pesanan</label><input class="input" id="transaction-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="180"></div>
    <div class="field"><label class="label" for="transaction-status">Status</label><select class="select" id="transaction-status" name="status"><option value="">Semua status</option>@foreach ($statuses as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
    <button class="btn btn--primary">Cari</button>
</form>
<section class="card"><div class="table-wrap"><table class="table">
<thead><tr><th>Pesanan</th><th>Peserta</th><th>Paket / total</th><th>Pembayaran</th><th>Akses</th><th>Tanggal</th></tr></thead><tbody>
@forelse ($transactions as $transaction)
<tr>
    <td class="text-xs">{{ $transaction->order_id }}@if ($transaction->provider_reference)<p class="text-soft">{{ $transaction->provider_reference }}</p>@endif</td>
    <td><strong>{{ $transaction->user->name }}</strong><p class="text-xs text-soft">{{ $transaction->user->email }}</p></td>
    <td><strong>{{ $transaction->package_snapshot['name'] }}</strong><p>{{ $transaction->formattedAmount() }}</p></td>
    <td><span class="badge badge--{{ $transaction->status === \App\Enums\TransactionStatus::Paid ? 'mint' : 'amber' }} badge--flat">{{ $transaction->status->label() }}</span><p class="text-xs text-soft">{{ $transaction->provider === 'free' ? 'Paket gratis' : 'Midtrans · '.($transaction->is_production ? 'Production' : 'Sandbox') }}{{ $transaction->payment_type ? ' · '.str_replace('_', ' ', $transaction->payment_type) : '' }}</p></td>
    <td>{{ $transaction->userPackage?->statusLabel() ?? 'Belum aktif' }}@if ($transaction->userPackage)<p class="text-xs text-soft">@if ($transaction->userPackage->expires_at)s/d <x-local-time :value="$transaction->userPackage->expires_at" format="d/m/Y H:i" />@else Tanpa batas waktu @endif</p>@endif</td>
    <td><x-local-time :value="$transaction->created_at" format="d/m/Y H:i" />@if ($transaction->paid_at)<p class="text-xs text-soft">Lunas <x-local-time :value="$transaction->paid_at" format="d/m/Y H:i" /></p>@endif</td>
</tr>
@empty<tr><td colspan="6" class="text-soft">Belum ada transaksi yang sesuai.</td></tr>@endforelse
</tbody></table></div>
@if ($transactions->hasPages())<div class="exam-pager"><x-pager :paginator="$transactions" /></div>@endif
</section>
@endsection
