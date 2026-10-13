<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\SliceController;
use Illuminate\Contracts\View\View;

class AdminTransactionController extends SliceController
{
    public function __invoke(): View
    {
        return $this->slice('Dashboard.Admin.transactions.index');
    }
}
