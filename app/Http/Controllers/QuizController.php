<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ExamCatalog;
use App\Models\ExamAttempt;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function __construct(private ExamCatalog $catalog) {}

    public function index(Request $request): View
    {
        return view('Dashboard.User.quizzes.index', $this->catalog->listing($request->user(), 'quiz'));
    }

    public function start(Request $request, string $quiz): RedirectResponse
    {
        return redirect()->to($this->catalog->start($request->user(), 'quiz', $quiz)->attemptUrl());
    }

    public function attempt(Request $request, string $quiz): View|RedirectResponse
    {
        $id = $request->has('attempt') ? $request->integer('attempt') : null;
        abort_if($id !== null && $id < 1, 404);
        $attempt = $this->catalog->active($request->user(), 'quiz', $quiz, $id);
        if ($attempt->status === ExamAttempt::COMPLETED) {
            return redirect()->to($attempt->resultUrl());
        }
        if ($attempt->isExpired()) {
            $attempt->complete(true);

            return redirect()->to($attempt->resultUrl());
        }

        return view('Dashboard.User.quizzes.attempt', ['attempt' => $attempt, 'clientConfig' => $attempt->clientConfig()]);
    }

    public function result(Request $request, string $quiz): View
    {
        $id = $request->has('attempt') ? $request->integer('attempt') : null;
        abort_if($id !== null && $id < 1, 404);

        return view('Dashboard.User.tryouts.result', $this->catalog->result($request->user(), 'quiz', $quiz, $id));
    }
}
