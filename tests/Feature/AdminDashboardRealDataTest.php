<?php

namespace Tests\Feature;

use App\Enums\ExamBank;
use App\Models\Article;
use App\Models\ExamAttempt;
use App\Models\Material;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardRealDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_uses_saved_participants_materials_and_attempts(): void
    {
        $admin = User::factory()->admin()->create();
        $participant = User::factory()->create();
        User::factory()->inactive()->create();
        Material::factory()->published()->create(['title' => 'Materi Terbit Nyata']);
        Material::factory()->draft()->create(['title' => 'Materi Draft Nyata']);
        Question::factory()->published()->create();
        Question::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($participant, ExamBank::TryoutSkd5);
        $attempt->answers = ['1' => 2];
        $attempt->save();
        $attempt->complete();

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Materi Terbit Nyata')
            ->assertSee('Materi Draft Nyata')
            ->assertSee($participant->name)
            ->assertSee('Ringkasan Pembayaran')
            ->assertDontSee('TRX-90412')
            ->assertViewHas('participantCount', 2)
            ->assertViewHas('completedTryoutCount', 1)
            ->assertViewHas('publishedMaterialCount', 1)
            ->assertViewHas('draftMaterialCount', 1)
            ->assertViewHas('questionCount', 2)
            ->assertViewHas('publishedQuestionCount', 1)
            ->assertViewHas('draftQuestionCount', 1)
            ->assertViewHas('monthlyChart', fn (array $chart): bool => array_sum($chart['values']) === 1);
    }

    public function test_participant_cannot_open_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_dashboard_shows_article_readers_views_and_most_read_article(): void
    {
        $article = Article::factory()->published()->create(['title' => 'Artikel Paling Dibaca']);
        Article::factory()->published()->create(['title' => 'Artikel Belum Dibaca']);
        $reader = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($reader)->get(route('articles.show', $article))->assertOk();
        $this->get(route('articles.show', $article))->assertOk();
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertSee('Aktivitas Artikel')->assertSee('Artikel Paling Dibaca')
            ->assertViewHas('publishedArticleCount', 2)
            ->assertViewHas('articleReaderCount', 1)
            ->assertViewHas('articleViewCount', 2);
    }
}
