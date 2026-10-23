<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Http\Requests\CheckoutRequest;
use App\MidtransGateway;
use App\Models\Package;
use App\PackagePayment;
use App\VoucherDiscount;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CheckoutController extends Controller
{
    public function index(Request $request, MidtransGateway $gateway, VoucherDiscount $discounts): View|RedirectResponse
    {
        abort_if($request->user()->isAdmin(), 403);
        if (! $request->filled('package')) {
            return redirect()->route('packages.index');
        }
        $id = $request->query('package');
        abort_unless(is_string($id) && ctype_digit($id), 404);
        $package = Package::query()->active()->withContentAvailability()->with([
            'materials' => fn ($query) => $query->visibleToMembers(),
            'exams' => fn ($query) => $query->published(),
        ])->findOrFail($id);
        $pending = $request->user()->transactions()->whereBelongsTo($package)->where('status', TransactionStatus::Pending)->first();
        if ($pending !== null) {
            return redirect()->route('transactions.show', $pending);
        }

        $request->validate(['voucher_code' => ['nullable', 'string', 'max:40']]);
        $voucherError = null;
        try {
            $quote = $discounts->quote($package, $request->query('voucher_code'));
        } catch (ValidationException $exception) {
            $voucherError = $exception->errors()['voucher_code'][0];
            $quote = $discounts->quote($package, null);
        }

        return view('Dashboard.User.checkout.index', [
            'package' => $package, 'quote' => $quote, 'voucherError' => $voucherError, 'paymentAvailable' => $quote['amount'] === 0 || $gateway->configured(),
            'contentAvailable' => $package->hasAvailableContent(),
            'alreadyOwned' => $request->user()->userPackages()->active()->whereBelongsTo($package)->exists(),
        ]);
    }

    public function store(CheckoutRequest $request, PackagePayment $payments): RedirectResponse
    {
        $transaction = $payments->checkout($request->user(), Package::query()->findOrFail($request->validated('package_id')), $request->validated('voucher_code'));
        try {
            $payments->prepare($transaction);
        } catch (RuntimeException $exception) {
            return redirect()->route('transactions.show', $transaction)->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->route('transactions.show', $transaction);
    }
}
