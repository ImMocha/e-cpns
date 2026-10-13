<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class CheckoutController extends SliceController
{
    public function index(): View
    {
        return $this->slice('Dashboard.User.checkout.index');
    }
}
