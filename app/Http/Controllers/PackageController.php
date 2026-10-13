<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class PackageController extends SliceController
{
    public function index(): View
    {
        return $this->slice('Dashboard.User.packages.index');
    }
}
