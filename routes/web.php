<?php

use App\Http\Controllers\Admin\AdminArticleController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminExamController;
use App\Http\Controllers\Admin\AdminPackageController;
use App\Http\Controllers\Admin\AdminQuestionController;
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\Admin\AdminVoucherController;
use App\Http\Controllers\Admin\IntegrationSettingController;
use App\Http\Controllers\Admin\LeaderboardSimulationController;
use App\Http\Controllers\Admin\MaterialController as AdminMaterialController;
use App\Http\Controllers\Admin\SectionController as AdminSectionController;
use App\Http\Controllers\Admin\TopicController as AdminTopicController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserPackageController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamAttemptController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialProgressController;
use App\Http\Controllers\MidtransNotificationController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionBankController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TryoutController;
use App\Http\Controllers\TryoutResultController;
use App\Http\Middleware\SecureIntegrationSettings;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');
Route::resource('articles', ArticleController::class)->only(['index', 'show']);
Route::post('/payments/midtrans/notification', MidtransNotificationController::class)->name('payments.midtrans.notification');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| Member
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/materials', [MaterialController::class, 'index'])->name('materials.index');
    Route::get('/materials/{material}/file', [MaterialController::class, 'file'])->name('materials.file');
    Route::post('/materials/{material}/progress', [MaterialProgressController::class, 'store'])->name('materials.progress.store');
    Route::delete('/materials/{material}/progress', [MaterialProgressController::class, 'destroy'])->name('materials.progress.destroy');
    Route::get('/materials/{material}', [MaterialController::class, 'show'])->name('materials.show');
    Route::get('/question-bank', [QuestionBankController::class, 'index'])->name('question-bank.index');
    Route::get('/question-bank/{question}/image', [QuestionBankController::class, 'image'])->name('question-bank.image');
    Route::get('/question-bank/{question}/video', [QuestionBankController::class, 'video'])->name('question-bank.video');
    Route::post('/question-bank/{question}/answer', [QuestionBankController::class, 'answer'])->name('question-bank.answer');
    Route::get('/question-bank/{question}', [QuestionBankController::class, 'show'])->name('question-bank.show');

    Route::get('/quizzes', [QuizController::class, 'index'])->name('quizzes.index');
    Route::post('/quizzes/{quiz}/start', [QuizController::class, 'start'])->name('quizzes.start');
    Route::get('/quizzes/{quiz}/attempt', [QuizController::class, 'attempt'])->name('quizzes.attempt');
    Route::get('/quizzes/{quiz}/result', [QuizController::class, 'result'])->name('quizzes.result');

    Route::get('/tryouts', [TryoutController::class, 'index'])->name('tryouts.index');
    Route::post('/tryouts/{tryout}/start', [TryoutController::class, 'start'])->name('tryouts.start');
    Route::get('/tryouts/{tryout}/attempt', [TryoutController::class, 'attempt'])->name('tryouts.attempt');
    Route::get('/tryouts/{tryout}/result', TryoutResultController::class)->name('tryouts.result');

    Route::patch('/exam-attempts/{examAttempt}/save', [ExamAttemptController::class, 'save'])
        ->name('exam-attempts.save');
    Route::post('/exam-attempts/{examAttempt}/submit', [ExamAttemptController::class, 'submit'])
        ->name('exam-attempts.submit');
    Route::get('/exam-attempts/{examAttempt}/questions/{question}/media/{media}', [ExamAttemptController::class, 'media'])
        ->whereIn('media', ['image', 'video'])->name('exam-attempts.media');

    Route::get('/history', HistoryController::class)->name('history.index');
    Route::get('/statistics', StatisticsController::class)->name('statistics.index');
    Route::get('/leaderboard', LeaderboardController::class)->name('leaderboard.index');

    Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::post('/transactions/{transaction}/refresh', [TransactionController::class, 'refresh'])->middleware('throttle:10,1')->name('transactions.refresh');
    Route::post('/transactions/{transaction}/pay', [TransactionController::class, 'pay'])->middleware('throttle:10,1')->name('transactions.pay');
    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/{examAttempt}', [CertificateController::class, 'show'])
        ->whereNumber('examAttempt')->name('certificates.show');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::patch('/profile/notifications', [ProfileController::class, 'updateNotifications'])
        ->name('profile.notifications.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
|
| TODO(policy): setiap resource butuh Policy sendiri (lihat App\Policies) agar
| authorize() per aksi, bukan hanya role.
|
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'active', 'role:admin', 'verified'])
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::middleware(SecureIntegrationSettings::class)->group(function (): void {
            Route::get('settings/integrations', [IntegrationSettingController::class, 'index'])->name('settings.index');
            Route::patch('settings/integrations/{integration}', [IntegrationSettingController::class, 'update'])
                ->whereIn('integration', ['midtrans', 'mail'])->middleware('throttle:integration-settings')->name('settings.update');
            Route::post('settings/integrations/{integration}/test', [IntegrationSettingController::class, 'test'])
                ->whereIn('integration', ['midtrans', 'mail'])->middleware(['throttle:integration-settings', 'throttle:integration-connection-tests'])->name('settings.test');
        });

        // Pengelolaan user. `users` sebagai segment URL, `user` sebagai nama
        // parameter supaya generated URL dan route-model binding konsisten.
        Route::patch('users/{user}/status', [AdminUserController::class, 'toggleStatus'])->name('users.status');
        Route::resource('users', AdminUserController::class)->parameters(['users' => 'user']);
        Route::get('leaderboard-simulation', [LeaderboardSimulationController::class, 'index'])->name('leaderboard-simulation.index');
        Route::patch('leaderboard-simulation', [LeaderboardSimulationController::class, 'update'])->name('leaderboard-simulation.update');

        // Pengelolaan bidang (TWK / TIU / TKP / dan bidang lain).
        Route::resource('sections', AdminSectionController::class)->parameters(['sections' => 'section']);
        Route::resource('topics', AdminTopicController::class);

        // Pengelolaan materi. `toggleStatus` mendahului resource supaya
        // `materials/{material}` tidak menabrak jalur `materials/{material}/...`.
        Route::prefix('materials')->name('materials.')->group(function (): void {
            Route::resource('quizzes', AdminExamController::class)->except('show')->parameters(['quizzes' => 'exam']);
        });
        Route::patch('materials/{material}/status', [AdminMaterialController::class, 'toggleStatus'])
            ->name('materials.status');
        Route::resource('materials', AdminMaterialController::class)->parameters(['materials' => 'material']);

        Route::resource('questions', AdminQuestionController::class);
        Route::resource('exams', AdminExamController::class)->except('show');
        Route::resource('articles', AdminArticleController::class);
        Route::resource('packages', AdminPackageController::class)->except('show')->withTrashed(['edit']);
        Route::post('packages/{package}/members', [UserPackageController::class, 'store'])->name('packages.members.store');
        Route::delete('packages/{package}/members/{userPackage}', [UserPackageController::class, 'destroy'])->withTrashed()->name('packages.members.destroy');
        Route::get('/transactions', AdminTransactionController::class)->name('transactions.index');
        Route::get('/vouchers', AdminVoucherController::class)->name('vouchers.index');
    });
