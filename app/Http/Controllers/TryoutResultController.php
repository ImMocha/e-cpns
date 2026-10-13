<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ExamCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TryoutResultController extends Controller
{
    public function __invoke(Request $request, string $tryout, ExamCatalog $catalog): View
    {
        $id = $request->has('attempt') ? $request->integer('attempt') : null;
        abort_if($id !== null && $id < 1, 404);

        return view('Dashboard.User.tryouts.result', $catalog->result($request->user(), 'tryout', $tryout, $id));
    }
}
