<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamClaim;
use App\Models\Question;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class HomeExamCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_three_published_sessions_per_kind_without_question_contents(): void
    {
        Exam::factory()->tryout()->count(4)->create(['access_level' => 'premium']);
        $practice = Exam::factory()->practice()->create(['access_level' => 'premium', 'description' => 'Latihan dari database']);
        Exam::factory()->practice()->create(['status' => 'draft', 'title' => 'Sesi belum terbit']);
        $question = Question::factory()->published()->create(['body' => 'Isi soal rahasia landing page']);
        $practice->questions()->attach($question);

        $this->get(route('home'))->assertSee('dan Latihan Soal')->assertSee('Latihan dari database')
            ->assertSee('Terkunci')->assertDontSee('Sesi belum terbit')->assertDontSee($question->body)
            ->assertViewHas('featuredExams', function (array $featured): bool {
                $this->assertCount(3, $featured['tryout']);
                $this->assertCount(1, $featured['practice']);
                $this->assertFalse($featured['tryout']->first()['hasAccess']);
                $this->assertFalse($featured['practice']->first()['hasAccess']);

                return true;
            });
    }

    /** @return array<string, array{string, bool, bool}> */
    public static function packageAccess(): array
    {
        return [
            'no package' => ['none', false, false],
            'unrelated package' => ['unrelated', false, false],
            'selected tryout' => ['selected', true, false],
            'expired premium' => ['expired', false, false],
            'active premium' => ['premium', true, true],
        ];
    }

    #[DataProvider('packageAccess')]
    public function test_home_respects_package_access_and_start_endpoints_enforce_it(string $state, bool $tryoutAccess, bool $practiceAccess): void
    {
        $user = User::factory()->create();
        $tryout = Exam::factory()->tryout()->create(['access_level' => 'premium']);
        $practice = Exam::factory()->practice()->create(['access_level' => 'premium']);
        $question = Question::factory()->published()->create();
        $tryout->questions()->attach($question);
        $practice->questions()->attach($question);
        if ($state !== 'none') {
            $package = UserPackage::factory()->for($user)->create([
                'tier' => in_array($state, ['premium', 'expired'], true) ? 'premium' : 'regular',
                'expires_at' => $state === 'expired' ? now()->subMinute() : now()->addMonth(),
            ]);
            if ($state === 'selected') {
                $package->exams()->attach($tryout);
            }
        }

        $this->actingAs($user)->get(route('home'))->assertViewHas('featuredExams', function (array $featured) use ($tryoutAccess, $practiceAccess): bool {
            $this->assertSame($tryoutAccess, $featured['tryout']->first()['hasAccess']);
            $this->assertSame($practiceAccess, $featured['practice']->first()['hasAccess']);

            return true;
        });

        $tryoutResponse = $this->post(route('tryouts.start', $tryout->slug));
        $practiceResponse = $this->post(route('practices.start', $practice->slug));
        if ($tryoutAccess) {
            $tryoutResponse->assertRedirect();
        } else {
            $tryoutResponse->assertForbidden();
        }
        if ($practiceAccess) {
            $practiceResponse->assertRedirect();
        } else {
            $practiceResponse->assertForbidden();
        }
        $this->assertDatabaseCount('exam_attempts', (int) $tryoutAccess + (int) $practiceAccess);
    }

    #[TestWith(['pending', false])]
    #[TestWith(['approved', true])]
    public function test_claimable_sessions_only_unlock_after_approval(string $status, bool $hasAccess): void
    {
        $user = User::factory()->create();
        $exam = Exam::factory()->tryout()->claimable()->create();
        ExamClaim::factory()->for($user)->for($exam)->create(['status' => $status]);

        $this->actingAs($user)->get(route('home'))
            ->assertViewHas('featuredExams', fn (array $featured): bool => $featured['tryout']->first()['hasAccess'] === $hasAccess);
    }
}
