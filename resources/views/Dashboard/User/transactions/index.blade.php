@extends('layouts.user.main')
@section('title', 'Transaksi Saya — Ganesha Nusa')
@section('content')
<header class="page-head page-head__row"><div><span class="eyebrow">Pembelian Paket</span><h1 style="margin-top:.5rem">Transaksi Saya</h1><p>Lanjutkan pembayaran dan lihat riwayat pembelian paketmu.</p></div><a class="btn btn--primary" href="{{ route('packages.index') }}">Pilih paket</a></header>
<section class="card"><div class="table-wrap"><table class="table">
<thead><tr><th>Pesanan</th><th>Paket</th><th>Total</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
<tbody>
@forelse ($transactions as $transaction)
<tr><td class="text-xs">{{ $transaction->order_id }}</td><td><strong>{{ $transaction->package_snapshot['name'] }}</strong></td><td>{{ $transaction->formattedAmount() }}</td><td><span class="badge badge--{{ $transaction->status === \App\Enums\TransactionStatus::Paid ? 'mint' : 'amber' }} badge--flat">{{ $transaction->status->label() }}</span></td><td><x-local-time :value="$transaction->created_at" format="d/m/Y H:i" /></td><td><a class="btn btn--ghost btn--sm" href="{{ route('transactions.show', $transaction) }}">Detail</a></td></tr>
@empty<tr><td colspan="6" class="text-soft">Belum ada transaksi. Pilih paket untuk mulai belajar.</td></tr>@endforelse
</tbody></table></div>
@if ($transactions->hasPages())<div class="exam-pager"><x-pager :paginator="$transactions" /></div>@endif
</section>
@endsection
