<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ExamCatalog;
use App\Models\ExamAttempt;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TryoutController extends Controller
{
    public function __construct(private ExamCatalog $catalog) {}

    public function index(Request $request): View
    {
        return view('Dashboard.User.tryouts.index', $this->catalog->listing($request->user(), 'tryout'));
    }

    public function start(Request $request, string $tryout): RedirectResponse
    {
        return redirect()->to($this->catalog->start($request->user(), 'tryout', $tryout)->attemptUrl());
    }

    public function attempt(Request $request, string $tryout): View|RedirectResponse
    {
        $id = $request->has('attempt') ? $request->integer('attempt') : null;
        abort_if($id !== null && $id < 1, 404);
        $attempt = $this->catalog->active($request->user(), 'tryout', $tryout, $id);
        if ($attempt->status === ExamAttempt::COMPLETED) {
            return redirect()->to($attempt->resultUrl());
        }
        if ($attempt->isExpired()) {
            $attempt->complete(true);

            return redirect()->to($attempt->resultUrl());
        }

        return view('Dashboard.User.tryouts.attempt', ['attempt' => $attempt, 'clientConfig' => $attempt->clientConfig()]);
    }
}
