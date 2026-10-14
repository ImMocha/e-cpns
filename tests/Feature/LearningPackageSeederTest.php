<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LeaderboardAccess;
use App\Enums\MaterialAccess;
use App\Models\Exam;
use App\Models\Material;
use App\Models\Package;
use App\Models\Question;
use App\Models\Section;
use Database\Seeders\LearningPackageSeeder;
use Database\Seeders\PremiumTryoutSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningPackageSeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedableContent(): void
    {
        foreach (array_keys(PremiumTryoutSeeder::SLUGS) as $sectionCode) {
            $section = Section::factory()->create(['code' => $sectionCode]);
            Question::factory()->published()->for($section)->count(3)->create();

            if ($sectionCode === 'TWK') {
                Material::factory()->premium()->for($section)->create();
            }
        }

        Exam::factory()->create(['slug' => 'latihan-skd-gratis']);
    }

    public function test_seeder_creates_three_annual_packages_with_real_content_and_catalog_prices(): void
    {
        $this->seedableContent();

        $this->seed(LearningPackageSeeder::class);

        $first = Package::query()->where('name', 'Paket 1')->sole();
        $second = Package::query()->where('name', 'Paket 2')->sole();
        $third = Package::query()->where('name', 'Paket 3')->sole();
        $this->assertSame([15000, 35000, 80000], [$first->price, $second->price, $third->price]);
        $this->assertSame([2, 3, null], [$first->tryout_quota, $second->tryout_quota, $third->tryout_quota]);
        $this->assertSame([LeaderboardAccess::None, LeaderboardAccess::Local, LeaderboardAccess::National],
            [$first->leaderboard_access, $second->leaderboard_access, $third->leaderboard_access]);
        $this->assertSame([365, 365, 365], [$first->duration_days, $second->duration_days, $third->duration_days]);
        $this->assertSame('1 tahun', $first->durationLabel());
        $this->assertSame([1, 3, 4], [$first->exams()->count(), $second->exams()->count(), $third->exams()->count()]);
        $this->assertSame([0, 0, 1], [$first->materials()->count(), $second->materials()->count(), $third->materials()->count()]);
        $this->assertSame(3, Exam::query()->where('kind', 'tryout')->where('access_level', MaterialAccess::Premium)->count());
        foreach (PremiumTryoutSeeder::SLUGS as $slug) {
            $exam = Exam::query()->where('slug', $slug)->sole();
            $this->assertSame(3, $exam->questions()->visibleToMembers()->count());
        }

        $this->get(route('home'))->assertSee('Ganesha Nusa')->assertSee('Akses 1 tahun')->assertSee('Rp15.000')->assertSee('Rp35.000')
            ->assertSee('Rp80.000')->assertDontSee('Paket Basic');
    }

    public function test_reseeding_preserves_admin_changes_and_archived_records(): void
    {
        $this->seedableContent();
        $this->seed(LearningPackageSeeder::class);
        $first = Package::query()->where('name', 'Paket 1')->sole();
        $second = Package::query()->where('name', 'Paket 2')->sole();
        $tryout = Exam::query()->where('slug', PremiumTryoutSeeder::SLUGS['TKP'])->sole();
        $first->update(['price' => 17500]);
        $first->exams()->detach();
        $second->delete();
        $tryout->update(['duration_minutes' => 45]);

        $this->seed(LearningPackageSeeder::class);

        $this->assertSame(3, Package::withTrashed()->count());
        $this->assertSame(17500, $first->fresh()->price);
        $this->assertSame(0, $first->exams()->count());
        $this->assertSoftDeleted($second);
        $this->assertSame(45, $tryout->fresh()->duration_minutes);
        $this->assertSame(3, Exam::query()->whereIn('slug', array_values(PremiumTryoutSeeder::SLUGS))->count());
    }

    public function test_seeder_does_not_sell_incomplete_bundle_or_material_access(): void
    {
        $section = Section::factory()->create(['code' => 'TWK']);
        Question::factory()->published()->for($section)->count(3)->create();

        $this->seed(LearningPackageSeeder::class);

        $this->assertSame(['Paket 1'], Package::query()->pluck('name')->all());
        $this->assertSame(1, Exam::count());
    }
}
