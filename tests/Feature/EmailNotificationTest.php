<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\MaterialStatus;
use App\Enums\QuestionStatus;
use App\Enums\TransactionStatus;
use App\Jobs\SendContentEmail;
use App\Models\Article;
use App\Models\Exam;
use App\Models\Material;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPackage;
use App\Notifications\AccountEmail;
use App\PackagePayment;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_changes_send_security_email_even_when_optional_emails_are_disabled(): void
    {
        $user = User::factory()->create(['notification_preferences' => array_fill_keys(array_keys(User::DEFAULT_NOTIFICATION_PREFERENCES), false)]);
        Notification::fake();

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'password', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertSessionHasNoErrors();

        Notification::assertSentTo($user, AccountEmail::class, fn (AccountEmail $notification): bool => $notification->preference === null);
    }

    public function test_admin_creation_and_email_changes_send_verification_for_unverified_accounts(): void
    {
        $admin = User::factory()->admin()->create();
        Notification::fake();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Peserta Baru', 'email' => 'peserta@example.com',
            'password' => 'password-123', 'password_confirmation' => 'password-123',
            'role' => 'user', 'status' => 'active',
        ])->assertSessionHasNoErrors();
        $user = User::where('email', 'peserta@example.com')->sole();
        $this->put(route('admin.users.update', $user), [
            'name' => 'Peserta Baru', 'email' => 'baru@example.com',
            'role' => 'user', 'status' => 'active',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'baru@example.com', 'email_verified_at' => null]);
        Notification::assertSentToTimes($user, VerifyEmail::class, 2);
    }

    public function test_incorrect_current_password_does_not_send_a_security_email(): void
    {
        $user = User::factory()->create();
        $oldPassword = $user->password;
        Notification::fake();

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'incorrect-password', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('current_password');

        $this->assertSame($oldPassword, $user->fresh()->password);
        Notification::assertNothingSent();
    }

    public function test_publishing_a_seeded_material_through_admin_sends_an_update(): void
    {
        $admin = User::factory()->admin()->create();
        $material = Material::factory()->video()->draft()->create();
        Queue::fake([SendContentEmail::class]);

        $this->actingAs($admin)->patch(route('admin.materials.status', $material))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('materials', ['id' => $material->id, 'status' => 'published', 'updated_by' => $admin->id]);
        Queue::assertPushed(SendContentEmail::class, fn (SendContentEmail $job): bool => $job->contentId === $material->id);
    }

    public function test_free_checkout_sends_confirmation_after_the_package_is_activated(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create(['price' => 0]);
        $package->materials()->attach(Material::factory()->video()->create());
        Notification::fake();

        $transaction = app(PackagePayment::class)->checkout($user, $package);

        $this->assertSame(TransactionStatus::Paid, $transaction->status);
        $this->assertDatabaseHas('user_packages', ['id' => $transaction->user_package_id, 'user_id' => $user->id]);
        Notification::assertSentTo($user, AccountEmail::class, fn (AccountEmail $notification): bool => $notification->details['Status'] === 'Lunas'
            && $notification->details['Total'] === 'Rp0' && $notification->actionUrl === route('transactions.show', $transaction));
    }

    /** @return array<string, array{TransactionStatus}> */
    public static function finalPaymentStatuses(): array
    {
        return [
            'paid' => [TransactionStatus::Paid], 'failed' => [TransactionStatus::Failed],
            'expired' => [TransactionStatus::Expired], 'refunded' => [TransactionStatus::Refunded],
            'partially refunded' => [TransactionStatus::PartiallyRefunded],
        ];
    }

    #[DataProvider('finalPaymentStatuses')]
    public function test_payment_status_change_sends_one_email_and_repeated_updates_do_not_duplicate_it(TransactionStatus $status): void
    {
        $transaction = Transaction::factory()->create();
        Notification::fake();

        $transaction->update(['status' => $status]);
        $transaction->update(['status' => $status, 'checked_at' => now()]);

        Notification::assertSentToTimes($transaction->user, AccountEmail::class, 1);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'status' => $status->value]);
    }

    public function test_pending_invoice_is_sent_only_once_after_the_payment_link_is_ready(): void
    {
        $transaction = Transaction::factory()->create();
        Notification::fake();

        $transaction->update(['payment_url' => 'https://app.sandbox.midtrans.com/payment/example']);
        $transaction->update(['payment_url' => 'https://app.sandbox.midtrans.com/payment/example']);

        Notification::assertSentToTimes($transaction->user, AccountEmail::class, 1);
    }

    public function test_payment_updates_respect_email_preferences(): void
    {
        $user = User::factory()->create(['notification_preferences' => ['payment_updates' => false]]);
        $transaction = Transaction::factory()->for($user)->create();
        Notification::fake();

        $transaction->update(['status' => TransactionStatus::Paid]);

        Notification::assertNothingSent();
    }

    public function test_queued_email_rechecks_preferences_before_delivery(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create();
        Queue::fake([SendQueuedNotifications::class]);
        $transaction->update(['status' => TransactionStatus::Paid]);
        $queuedJob = Queue::pushed(SendQueuedNotifications::class)->sole();
        $user->update(['notification_preferences' => ['payment_updates' => false]]);

        $restoredJob = unserialize(serialize($queuedJob));
        $restoredJob->handle(app(ChannelManager::class));

        $this->assertCount(0, Mail::mailer('array')->getSymfonyTransport()->messages());
        Queue::assertPushed(SendQueuedNotifications::class, 1);
    }

    public function test_rollback_does_not_queue_a_security_email(): void
    {
        config(['queue.default' => 'database']);
        $user = User::factory()->create();
        $oldPassword = $user->password;

        try {
            DB::transaction(function () use ($user): void {
                $user->update(['password' => 'rolled-back-password']);
                throw new RuntimeException('Rollback');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Rollback', $exception->getMessage());
        }

        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame($oldPassword, $user->fresh()->password);
    }

    public function test_committed_password_change_queues_a_retryable_email(): void
    {
        config(['queue.default' => 'database']);
        $user = User::factory()->create();

        DB::transaction(fn (): bool => $user->update(['password' => 'committed-password']));

        $this->assertDatabaseCount('jobs', 1);
        $payload = json_decode(DB::table('jobs')->sole()->payload, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(3, $payload['maxTries']);
        $this->assertSame('60,300', $payload['backoff']);
    }

    public function test_published_tryout_notifies_only_verified_active_participants_who_enabled_email(): void
    {
        $admin = User::factory()->admin()->create();
        $subscriber = User::factory()->create();
        $unsubscribed = User::factory()->create(['notification_preferences' => ['new_tryout' => false]]);
        $unverified = User::factory()->unverified()->create();
        $inactive = User::factory()->inactive()->create();
        $deleted = User::factory()->create();
        $deleted->delete();
        $exam = Exam::factory()->tryout()->create(['status' => QuestionStatus::Draft, 'created_by' => $admin->id, 'access_level' => 'free']);
        Notification::fake();

        $exam->update(['status' => QuestionStatus::Published]);

        Notification::assertSentTo($subscriber, AccountEmail::class, fn (AccountEmail $notification): bool => $notification->preference === 'new_tryout');
        Notification::assertNotSentTo([$admin, $unsubscribed, $unverified, $inactive, $deleted], AccountEmail::class);
    }

    public function test_premium_material_updates_notify_only_participants_with_active_access(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $expiredOwner = User::factory()->create();
        $material = Material::factory()->video()->premium()->draft()->create(['created_by' => $admin->id]);
        UserPackage::factory()->for($owner)->create()->materials()->attach($material);
        UserPackage::factory()->for($expiredOwner)->expired()->create()->materials()->attach($material);
        Notification::fake();

        $material->update(['status' => MaterialStatus::Published]);

        Notification::assertSentTo($owner, AccountEmail::class, fn (AccountEmail $notification): bool => $notification->preference === 'updated_materials'
            && $notification->actionUrl === route('materials.show', $material));
        Notification::assertNotSentTo([$outsider, $expiredOwner], AccountEmail::class);
    }

    public function test_published_material_content_changes_send_updates_but_position_changes_do_not(): void
    {
        $admin = User::factory()->admin()->create();
        $material = Material::factory()->video()->draft()->create(['created_by' => $admin->id]);
        Queue::fake([SendContentEmail::class]);

        $material->update(['status' => MaterialStatus::Published]);
        $material->update(['summary' => 'Materi telah direvisi.']);
        $material->update(['position' => 99]);

        Queue::assertPushed(SendContentEmail::class, 2);
    }

    public function test_package_recommendations_are_sent_only_to_opted_in_participants(): void
    {
        $admin = User::factory()->admin()->create();
        $subscriber = User::factory()->create(['notification_preferences' => ['package_recommendations' => true]]);
        $defaultUser = User::factory()->create();
        $package = Package::factory()->inactive()->create(['created_by' => $admin->id]);
        Notification::fake();

        $package->update(['is_active' => true]);

        Notification::assertSentTo($subscriber, AccountEmail::class, fn (AccountEmail $notification): bool => $notification->preference === 'package_recommendations');
        Notification::assertNotSentTo($defaultUser, AccountEmail::class);
    }

    public function test_published_news_is_sent_only_to_participants_who_enabled_announcements(): void
    {
        $admin = User::factory()->admin()->create();
        $subscriber = User::factory()->create(['notification_preferences' => ['platform_announcements' => true]]);
        $defaultUser = User::factory()->create();
        $article = Article::factory()->create(['created_by' => $admin->id]);
        Notification::fake();

        $article->update(['status' => ArticleStatus::Published, 'published_at' => now()]);

        Notification::assertSentTo($subscriber, AccountEmail::class, fn (AccountEmail $notification): bool => $notification->preference === 'platform_announcements'
            && $notification->actionUrl === route('articles.show', $article));
        Notification::assertNotSentTo($defaultUser, AccountEmail::class);
    }

    public function test_unpublished_content_is_skipped_if_withdrawn_before_the_email_job_runs(): void
    {
        $exam = Exam::factory()->tryout()->create(['status' => QuestionStatus::Draft]);
        User::factory()->create();
        Notification::fake();

        (new SendContentEmail(Exam::class, $exam->id))->handle();

        Notification::assertNothingSent();
    }

    public function test_notification_email_escapes_content_and_includes_plain_text(): void
    {
        $user = User::factory()->create();

        Notification::sendNow($user, new AccountEmail(
            '<script>title</script>', '<img src=x onerror=alert(1)>', 'Buka akun', route('login'),
            details: ['Paket' => '<script>details</script>'],
        ));

        $email = Mail::mailer('array')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $this->assertStringContainsString('&lt;script&gt;title&lt;/script&gt;', $email->getHtmlBody());
        $this->assertStringContainsString('&lt;img', $email->getHtmlBody());
        $this->assertStringNotContainsString('<script>', $email->getHtmlBody());
        $this->assertStringNotContainsString('<img src=x', $email->getHtmlBody());
        $this->assertStringContainsString('Buka akun: '.route('login'), $email->getTextBody());
    }
}
