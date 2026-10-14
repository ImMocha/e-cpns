<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MaterialAccess;
use App\Models\Exam;
use App\Models\Material;
use App\Models\Package;
use App\Models\Question;
use App\Models\User;
use Database\Seeders\PackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageSeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedableContent(): void
    {
        Material::factory()->premium()->count(3)->create();
        $source = Exam::factory()->tryout()->create([
            'slug' => 'simulasi-bank-soal',
            'access_level' => MaterialAccess::Free,
            'question_count' => 2,
        ]);
        $source->questions()->attach(Question::factory()->published()->count(2)->create());
    }

    public function test_seeder_creates_active_sandbox_package_with_accessible_content(): void
    {
        config(['services.midtrans.is_production' => false]);
        $this->seedableContent();

        $this->seed(PackageSeeder::class);

        $package = Package::query()->where('name', PackageSeeder::SANDBOX_PACKAGE_NAME)->sole();
        $premiumTryout = $package->exams()->sole();
        $this->assertTrue($package->is_active);
        $this->assertSame(10000, $package->price);
        $this->assertSame(30, $package->duration_days);
        $this->assertSame(2, $package->tryout_quota);
        $this->assertSame(3, $package->materials()->count());
        $this->assertSame(3, $package->materials()->visibleToMembers()->count());
        $this->assertSame('tryout', $premiumTryout->kind);
        $this->assertSame(MaterialAccess::Premium, $premiumTryout->access_level);
        $this->assertSame(2, $premiumTryout->questions()->visibleToMembers()->count());
        $this->actingAs(User::factory()->create())->get(route('packages.index'))
            ->assertSee(PackageSeeder::SANDBOX_PACKAGE_NAME)->assertSee('Rp10.000');
    }

    public function test_reseeding_preserves_admin_edits_and_archived_package(): void
    {
        config(['services.midtrans.is_production' => false]);
        $this->seedableContent();
        $this->seed(PackageSeeder::class);
        $package = Package::query()->where('name', PackageSeeder::SANDBOX_PACKAGE_NAME)->sole();
        $premiumTryout = $package->exams()->sole();
        $package->update(['price' => 12500, 'duration_days' => 45]);
        $premiumTryout->update(['duration_minutes' => 20]);

        $this->seed(PackageSeeder::class);

        $this->assertSame(12500, $package->fresh()->price);
        $this->assertSame(45, $package->fresh()->duration_days);
        $this->assertSame(20, $premiumTryout->fresh()->duration_minutes);
        $this->assertSame(3, $package->materials()->count());
        $package->delete();
        $this->seed(PackageSeeder::class);
        $this->assertSame(1, Package::withTrashed()->count());
        $this->assertSoftDeleted($package);
        $this->assertSame(2, Exam::count());
    }

    public function test_seeder_skips_missing_content_or_production_mode(): void
    {
        config(['services.midtrans.is_production' => false]);
        $this->seed(PackageSeeder::class);
        $this->assertDatabaseCount('packages', 0);
        $this->seedableContent();
        config(['services.midtrans.is_production' => true]);

        $this->seed(PackageSeeder::class);

        $this->assertDatabaseCount('packages', 0);
        $this->assertSame(1, Exam::count());
    }
}
