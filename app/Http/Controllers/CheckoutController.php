<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\MidtransGateway;
use App\Models\Package;
use App\PackagePayment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class CheckoutController extends Controller
{
    public function index(Request $request, MidtransGateway $gateway): View|RedirectResponse
    {
        abort_if($request->user()->isAdmin(), 403);
        if (! $request->filled('package')) {
            return redirect()->route('packages.index');
        }
        $id = $request->query('package');
        abort_unless(is_string($id) && ctype_digit($id), 404);
        $package = Package::query()->active()->with([
            'materials' => fn ($query) => $query->visibleToMembers(),
            'exams' => fn ($query) => $query->published(),
        ])->findOrFail($id);

        return view('Dashboard.User.checkout.index', [
            'package' => $package, 'paymentAvailable' => $package->price === 0 || $gateway->configured(),
            'alreadyOwned' => $request->user()->userPackages()->active()->whereBelongsTo($package)->exists(),
        ]);
    }

    public function store(CheckoutRequest $request, PackagePayment $payments): RedirectResponse
    {
        $transaction = $payments->checkout($request->user(), Package::query()->findOrFail($request->validated('package_id')));
        try {
            $payments->prepare($transaction);
        } catch (RuntimeException $exception) {
            return redirect()->route('transactions.show', $transaction)->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->route('transactions.show', $transaction);
    }
}
