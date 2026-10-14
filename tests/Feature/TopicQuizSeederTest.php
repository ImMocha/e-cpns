<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MaterialAccess;
use App\Enums\QuestionStatus;
use App\Models\Exam;
use App\Models\Material;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\SectionSeeder;
use Database\Seeders\TopicQuizSeeder;
use Database\Seeders\TopicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicQuizSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_topical_questions_and_both_access_timings(): void
    {
        $this->seed(SectionSeeder::class);
        $this->seed(TopicSeeder::class);

        $this->seed(TopicQuizSeeder::class);

        $this->assertSame(3, Exam::query()->where('kind', 'quiz')->count());
        $this->assertSame(9, Question::query()->count());
        foreach ([
            'contoh-latihan-topik-pancasila' => ['TWK', 'Pancasila dan Ideologi Negara', true],
            'contoh-latihan-topik-numerik' => ['TIU', 'Kemampuan Numerik', false],
            'contoh-latihan-topik-pelayanan-publik' => ['TKP', 'Pelayanan Publik', true],
        ] as $slug => [$sectionCode, $topicName, $requiresCompletion]) {
            $exam = Exam::query()->where('slug', $slug)->firstOrFail();
            $this->assertSame($topicName, $exam->topic->name);
            $this->assertSame($sectionCode, $exam->topic->section->code);
            $this->assertSame($requiresCompletion, $exam->requires_topic_completion);
            $this->assertSame(MaterialAccess::Free, $exam->access_level);
            $this->assertSame(QuestionStatus::Published, $exam->status);
            $this->assertSame(3, $exam->question_count);
            $this->assertSame(3, $exam->questions()->visibleToMembers()->count());
            $this->assertSame(0, $exam->questions()->where('questions.section_id', '!=', $exam->topic->section_id)->count());
        }

        $topic = Topic::query()->where('name', 'Kemampuan Numerik')->firstOrFail();
        Material::factory()->video()->for($topic)->create(['section_id' => $topic->section_id]);
        $this->actingAs(User::factory()->create())->get(route('materials.index', ['topic_id' => $topic->id]))
            ->assertSee('Contoh: Latihan Kemampuan Numerik')->assertSee('Mulai latihan');
    }

    public function test_reseeding_preserves_admin_changes_and_archived_quizzes(): void
    {
        $this->seed(SectionSeeder::class);
        $this->seed(TopicSeeder::class);
        $this->seed(TopicQuizSeeder::class);
        $numerik = Exam::query()->where('slug', 'contoh-latihan-topik-numerik')->firstOrFail();
        $pancasila = Exam::query()->where('slug', 'contoh-latihan-topik-pancasila')->firstOrFail();
        $question = $numerik->questions()->firstOrFail();
        $numerik->update(['title' => 'Judul pilihan admin', 'requires_topic_completion' => true]);
        $question->update(['body' => '<p>Soal baru dari admin.</p>']);
        $numerik->questions()->detach($question);
        $pancasila->delete();

        $this->seed(TopicQuizSeeder::class);

        $this->assertSame(3, Exam::withTrashed()->count());
        $this->assertSame(9, Question::withTrashed()->count());
        $this->assertSame('Judul pilihan admin', $numerik->fresh()->title);
        $this->assertTrue($numerik->fresh()->requires_topic_completion);
        $this->assertSame('<p>Soal baru dari admin.</p>', $question->fresh()->body);
        $this->assertSame(2, $numerik->questions()->count());
        $this->assertSoftDeleted($pancasila);
    }

    public function test_seeder_skips_missing_topics_without_creating_questions(): void
    {
        $this->seed(TopicQuizSeeder::class);

        $this->assertDatabaseCount('exams', 0);
        $this->assertDatabaseCount('questions', 0);
    }
}
