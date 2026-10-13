<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class CertificateController extends SliceController
{
    public function __invoke(): View
    {
        return $this->slice('Dashboard.User.certificates.index');
    }
}
