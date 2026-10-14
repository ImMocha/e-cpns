<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GrantPackageRequest;
use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserPackageController extends Controller
{
    public function store(GrantPackageRequest $request, Package $package): RedirectResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->firstOrFail();
        UserPackage::grant($user, $package, $request->user(), $request->validated('note'));

        return redirect()->route('admin.packages.edit', $package)->with('success', 'Paket diberikan kepada '.$user->name.'.');
    }

    public function destroy(Request $request, Package $package, UserPackage $userPackage): RedirectResponse
    {
        abort_unless($userPackage->package_id === $package->id, 404);
        $userPackage->revoke($request->user());

        return redirect()->route('admin.packages.edit', $package)->with('success', 'Akses paket peserta dicabut. Riwayat ujian tetap tersimpan.');
    }
}
