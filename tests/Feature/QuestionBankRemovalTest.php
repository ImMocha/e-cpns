<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuestionBankRemovalTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string}> */
    public static function retiredEndpoints(): array
    {
        return [
            'catalogue' => ['get', ''],
            'question' => ['get', '/1'],
            'image' => ['get', '/1/image'],
            'video' => ['get', '/1/video'],
            'answer' => ['post', '/1/answer'],
        ];
    }

    #[DataProvider('retiredEndpoints')]
    public function test_question_bank_endpoints_return_404_for_every_audience(string $method, string $suffix): void
    {
        $this->{$method}('/question-bank'.$suffix)->assertNotFound();
        $this->actingAs(User::factory()->create())->{$method}('/question-bank'.$suffix)->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())->{$method}('/question-bank'.$suffix)->assertNotFound();
    }

    /** @return array<string, array{string}> */
    public static function memberPages(): array
    {
        return [
            'dashboard' => ['dashboard'], 'materials' => ['materials.index'],
            'quizzes' => ['quizzes.index'], 'tryouts' => ['tryouts.index'],
            'packages' => ['packages.index'], 'history' => ['history.index'],
            'statistics' => ['statistics.index'],
        ];
    }

    #[DataProvider('memberPages')]
    public function test_member_pages_do_not_offer_question_bank(string $routeName): void
    {
        $this->actingAs(User::factory()->create())->get(route($routeName))
            ->assertOk()->assertDontSee('question-bank')->assertDontSee('Bank Soal')->assertDontSee('bank soal');
    }

    public function test_public_pages_only_offer_available_learning_features(): void
    {
        $this->get(route('home'))->assertSee(route('quizzes.index'))
            ->assertDontSee('question-bank')->assertDontSee('Bank Soal')->assertDontSee('bank soal');
        $this->get(route('register'))->assertDontSee('Bank Soal')->assertDontSee('bank soal');
    }

    public function test_admin_pages_keep_question_management_without_question_bank_links(): void
    {
        $question = Question::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Bank Soal')->assertDontSee('bank soal');
        $this->get(route('admin.questions.index'))->assertSee('Soal &amp; Pembahasan', false)
            ->assertDontSee('question-bank')->assertDontSee('Bank Soal');
        $this->get(route('admin.questions.edit', $question))->assertOk()->assertDontSee('question-bank')->assertDontSee('bank soal');
    }

    public function test_question_preview_and_media_are_restricted_to_admins(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('questions/images/private.png', 'private image');
        Storage::disk('local')->put('questions/videos/private.mp4', str_repeat('a', 100));
        $question = Question::factory()->create([
            'image_path' => 'questions/images/private.png', 'video_path' => 'questions/videos/private.mp4',
            'explanation' => '<p>Pembahasan khusus pratinjau admin.</p>',
        ]);
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.questions.show', $question))->assertForbidden();
        $this->getJson(route('admin.questions.show', $question))->assertForbidden();
        $this->get(route('admin.questions.image', $question))->assertForbidden();
        $this->get(route('admin.questions.video', $question))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.questions.show', $question))
            ->assertSee('Pembahasan khusus pratinjau admin.')
            ->assertSee(route('admin.questions.image', $question))->assertSee(route('admin.questions.video', $question))
            ->assertDontSee('question-bank');
        $this->get(route('admin.questions.image', $question))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('admin.questions.video', $question), ['Range' => 'bytes=10-19'])
            ->assertStatus(206)->assertHeader('Content-Range', 'bytes 10-19/100')->assertHeader('Content-Length', '10');
        $this->get(route('admin.questions.video', $question), ['Range' => 'bytes=200-300'])->assertStatus(416);
        Storage::disk('local')->assertExists(['questions/images/private.png', 'questions/videos/private.mp4']);
    }
}
