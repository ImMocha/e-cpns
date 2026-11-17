<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\UserPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TryoutTokenController extends Controller
{
    public function __invoke(Request $request, UserPackage $userPackage, Exam $exam): RedirectResponse
    {
        $redeemed = $userPackage->redeemTryoutToken($request->user(), $exam);

        return redirect()->route('tryouts.index')->with('success', $redeemed
            ? 'Kuota percobaan tryout siap digunakan. Satu token telah digunakan.'
            : 'Tryout ini masih memiliki kuota percobaan.');
    }
}
