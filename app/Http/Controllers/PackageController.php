<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index(Request $request): View
    {
        return view('Dashboard.User.packages.index', [
            'packages' => Package::query()->active()->withContentAvailability()->with([
                'materials' => fn ($query) => $query->visibleToMembers(),
                'exams' => fn ($query) => $query->published(),
            ])->orderBy('position')->orderBy('id')->get(),
            'ownedPackages' => $request->user()->userPackages()->with([
                'materials' => fn ($query) => $query->visibleToMembers(),
                'exams' => fn ($query) => $query->published(),
            ])->latest('id')->get(),
        ]);
    }
}
