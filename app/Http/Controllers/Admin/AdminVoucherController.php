<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VoucherRequest;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\Voucher;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminVoucherController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:40'],
            'state' => ['nullable', Rule::in(['active', 'inactive', 'expired', 'scheduled'])]]);
        $paid = Transaction::query()->whereNotNull('voucher_id')->whereIn('status', [TransactionStatus::Paid, TransactionStatus::PartiallyRefunded]);

        return view('Dashboard.Admin.vouchers.index', [
            'vouchers' => Voucher::query()->with('package')->withCount([
                'transactions as reserved_count' => fn (Builder $query) => $query->reservingVoucher(),
                'transactions as paid_count' => fn (Builder $query) => $query->whereIn('status', [TransactionStatus::Paid, TransactionStatus::PartiallyRefunded]),
            ])
                ->when($filters['q'] ?? null, fn (Builder $query, string $search) => $query->where('code', 'like', '%'.$search.'%'))
                ->when(($filters['state'] ?? null) === 'active', fn (Builder $query) => $query->current())
                ->when(($filters['state'] ?? null) === 'inactive', fn (Builder $query) => $query->where('is_active', false))
                ->when(($filters['state'] ?? null) === 'expired', fn (Builder $query) => $query->where('ends_on', '<', today()->toDateString()))
                ->when(($filters['state'] ?? null) === 'scheduled', fn (Builder $query) => $query->where('is_active', true)->where('starts_on', '>', today()->toDateString()))
                ->orderByDesc('id')->paginate(15)->withQueryString(),
            'filters' => $filters,
            'stats' => ['total' => Voucher::count(), 'paid' => (clone $paid)->count(), 'discount' => (clone $paid)->sum('discount_amount'),
                'pending' => Transaction::query()->whereNotNull('voucher_id')->where('status', TransactionStatus::Pending)->count()],
        ]);
    }

    public function create(): View
    {
        return $this->form(new Voucher(['type' => 'fixed', 'minimum_amount' => 0, 'is_active' => true]));
    }

    public function store(VoucherRequest $request): RedirectResponse
    {
        Voucher::create($request->validated());

        return redirect()->route('admin.vouchers.index')->with('success', 'Voucher berhasil dibuat.');
    }

    public function edit(Voucher $voucher): View
    {
        return $this->form($voucher);
    }

    public function update(VoucherRequest $request, Voucher $voucher): RedirectResponse
    {
        DB::transaction(function () use ($request, $voucher): void {
            $voucher = Voucher::query()->lockForUpdate()->findOrFail($voucher->id);
            $reserved = $voucher->transactions()->reservingVoucher()->count();
            if ($request->filled('usage_limit') && $request->integer('usage_limit') < $reserved) {
                throw ValidationException::withMessages(['usage_limit' => 'Kuota tidak boleh lebih kecil dari jumlah pemakaian dan pesanan yang dicadangkan.']);
            }
            $voucher->update($request->validated());
        });

        return redirect()->route('admin.vouchers.index')->with('success', 'Voucher berhasil diperbarui.');
    }

    public function toggleStatus(Request $request, Voucher $voucher): RedirectResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $voucher->update($data);

        return redirect()->route('admin.vouchers.index')->with('success', $voucher->fresh()->is_active ? 'Voucher diaktifkan.' : 'Voucher dinonaktifkan.');
    }

    private function form(Voucher $voucher): View
    {
        return view('Dashboard.Admin.vouchers.form', [
            'voucher' => $voucher, 'packages' => Package::query()->orderBy('name')->orderBy('id')->get(),
        ]);
    }
}
