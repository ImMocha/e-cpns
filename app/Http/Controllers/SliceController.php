<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Base sementara untuk halaman yang isinya masih hardcoded dari prototype.
 *
 * Hanya memilih view. Status aktif sidebar tidak lagi diteruskan dari
 * controller — partial sidebar menandainya sendiri dari route dengan
 * `request()->routeIs()`.
 */
abstract class SliceController extends Controller
{
    protected function slice(string $view, array $data = []): View
    {
        return view($view, $data);
    }
}
