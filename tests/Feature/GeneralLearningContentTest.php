<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Material;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\MaterialSeeder;
use Database\Seeders\SectionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GeneralLearningContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_seeded_copy_is_updated_and_reseeding_keeps_existing_topics_and_materials(): void
    {
        Storage::fake(Material::PDF_DISK);
        $this->seed([SectionSeeder::class, MaterialSeeder::class, ArticleSeeder::class]);
        $topic = Topic::query()->where('name', 'Persiapan Ujian')->sole();
        $material = Material::query()->where('title', 'Checklist Persiapan Ujian')->sole();
        $article = Article::sole();
        $topic->update(['name' => 'Persiapan Seleksi CPNS', 'description' => 'Materi contoh untuk simulasi alur belajar CPNS.']);
        $material->update(['title' => 'Checklist Persiapan Seleksi CPNS', 'slug' => 'checklist-persiapan-seleksi-cpns',
            'summary' => 'Video placeholder untuk menguji pemutar; bukan pembahasan CPNS asli. Durasi label adalah contoh.']);
        $article->update(['title' => 'Mulai Belajar CPNS dengan Rencana', 'slug' => 'mulai-belajar-cpns-dengan-rencana']);
        $materialIds = Material::query()->orderBy('id')->pluck('id')->all();
        $topicIds = Topic::query()->orderBy('id')->pluck('id')->all();

        $migration = require database_path('migrations/2026_10_31_092241_generalize_learning_content_wording.php');
        $migration->up();
        $migration->up();
        $this->seed([MaterialSeeder::class, ArticleSeeder::class]);

        $this->assertSame('Persiapan Ujian', $topic->fresh()->name);
        $this->assertSame('Materi contoh untuk simulasi alur belajar.', $topic->fresh()->description);
        $this->assertSame('Checklist Persiapan Ujian', $material->fresh()->title);
        $this->assertSame('checklist-persiapan-ujian', $material->fresh()->slug);
        $this->assertStringContainsString('bukan materi pembelajaran asli', $material->fresh()->summary);
        $this->assertSame('Mulai Belajar dengan Rencana', $article->fresh()->title);
        $this->assertSame('mulai-belajar-dengan-rencana', $article->fresh()->slug);
        $this->assertDatabaseCount('articles', 1);
        $this->assertSame($materialIds, Material::query()->orderBy('id')->pluck('id')->all());
        $this->assertSame($topicIds, Topic::query()->orderBy('id')->pluck('id')->all());
        $this->assertStringNotContainsString('CPNS', Storage::disk(Material::PDF_DISK)->get($material->fresh()->file_path));
    }

    public function test_copy_cleanup_preserves_question_scoring_attempt_answers_and_media_references(): void
    {
        $question = Question::factory()->published()->create([
            'title' => 'Latihan CPNS', 'body' => '<p>Tugas PNS.</p><img src="/images/cpns-diagram.png" alt="Diagram">',
            'options' => ['PNS', 'Pegawai'], 'correct_option' => 1, 'image_path' => 'questions/cpns-diagram.png',
            'explanation' => '<p>PNS-nya bekerja sama.</p>',
        ]);
        $exam = Exam::factory()->create(['title' => 'Latihan CPNS', 'random_options' => false]);
        $exam->questions()->attach($question);
        $attempt = ExamAttempt::resumeOrStart(User::factory()->create(), $exam);
        $attempt->update(['answers' => [(string) $question->id => 1]]);
        $snapshot = $attempt->question_snapshot;

        $migration = require database_path('migrations/2026_10_31_092241_generalize_learning_content_wording.php');
        $migration->up();

        $this->assertSame('Latihan ujian', $question->fresh()->title);
        $this->assertSame('<p>Tugas petugas.</p><img src="/images/cpns-diagram.png" alt="Diagram" loading="lazy">', $question->fresh()->body);
        $this->assertSame(['petugas', 'Pegawai'], $question->fresh()->options);
        $this->assertSame('questions/cpns-diagram.png', $question->fresh()->image_path);
        $this->assertSame($snapshot['mediaPaths'], $attempt->fresh()->question_snapshot['mediaPaths']);
        $this->assertSame($snapshot['questions'][0]['answer'], $attempt->fresh()->question_snapshot['questions'][0]['answer']);
        $this->assertSame('<p>petugasnya bekerja sama.</p>', $attempt->fresh()->question_snapshot['questions'][0]['explainHtml']);
        $this->assertSame('Latihan ujian', $attempt->fresh()->title);
        $this->assertSame([(string) $question->id => 1], $attempt->fresh()->answers);
        $attempt->complete();
        $this->assertSame(5, $attempt->score);
    }

    public function test_existing_topic_with_the_general_name_is_preserved_without_losing_relations(): void
    {
        $original = Topic::factory()->create(['name' => 'Persiapan Seleksi CPNS']);
        $existing = Topic::factory()->create(['section_id' => $original->section_id, 'name' => 'Persiapan Ujian']);
        $material = Material::factory()->video()->create(['topic_id' => $original->id, 'section_id' => $original->section_id]);

        $migration = require database_path('migrations/2026_10_31_092241_generalize_learning_content_wording.php');
        $migration->up();

        $this->assertSame('Persiapan Ujian ('.$original->id.')', $original->fresh()->name);
        $this->assertSame('Persiapan Ujian', $existing->fresh()->name);
        $this->assertSame($original->id, $material->fresh()->topic_id);
        $this->assertDatabaseCount('topics', 2);
    }
}
