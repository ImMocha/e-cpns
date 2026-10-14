<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminTransactionController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:180'], 'status' => ['nullable', Rule::enum(TransactionStatus::class)]]);
        $query = Transaction::query()
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(fn (Builder $query) => $query->where('order_id', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')));
            });

        return view('Dashboard.Admin.transactions.index', [
            'transactions' => $query->with(['user', 'userPackage'])->latest('id')->paginate(20)->withQueryString(),
            'statuses' => TransactionStatus::cases(), 'filters' => $filters,
        ]);
    }
}
