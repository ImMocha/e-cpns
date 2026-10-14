<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Material;
use App\Models\Section;
use App\Models\Topic;
use Database\Seeders\MaterialSeeder;
use Database\Seeders\SectionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaterialSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_catalog_is_varied_and_repeatable_without_duplicate_ids(): void
    {
        Storage::fake(Material::PDF_DISK);
        $this->seed(SectionSeeder::class);
        $this->seed(MaterialSeeder::class);
        $ids = Material::query()->orderBy('id')->pluck('id')->all();
        $topicIds = Topic::query()->orderBy('id')->pluck('id')->all();

        $this->seed(MaterialSeeder::class);

        $this->assertSame($ids, Material::query()->orderBy('id')->pluck('id')->all());
        $this->assertSame($topicIds, Topic::query()->orderBy('id')->pluck('id')->all());
        $this->assertGreaterThanOrEqual(80, count($ids));
        foreach (Section::all() as $section) {
            $this->assertGreaterThanOrEqual(3, $section->topics()->count());
            $this->assertTrue($section->materials()->where('type', 'pdf')->exists());
            $this->assertTrue($section->materials()->where('type', 'video')->exists());
        }
        $this->assertTrue(Material::where('access_level', 'free')->exists());
        $this->assertTrue(Material::where('access_level', 'premium')->exists());
        $this->assertTrue(Material::where('status', 'draft')->exists());
        $this->assertTrue(Topic::where('is_active', false)->exists());
        $this->assertTrue(Topic::doesntHave('materials')->exists());
        $this->assertSame(0, Material::whereNull('topic_id')->count());
        $this->assertSame(0, Material::whereHas('topic', fn ($query) => $query->whereColumn('topics.section_id', '!=', 'materials.section_id'))->count());
        $pdf = Material::where('type', 'pdf')->firstOrFail();
        Storage::disk(Material::PDF_DISK)->assertExists($pdf->file_path);
        $this->assertStringStartsWith('%PDF-', Storage::disk(Material::PDF_DISK)->get($pdf->file_path));
        $this->assertStringContainsString('Ganesha Nusa', Storage::disk(Material::PDF_DISK)->get($pdf->file_path));
    }

    public function test_reseeding_preserves_admin_changes_files_and_archived_materials(): void
    {
        Storage::fake(Material::PDF_DISK);
        $this->seed(SectionSeeder::class);
        $this->seed(MaterialSeeder::class);
        $material = Material::where('type', 'pdf')->firstOrFail();
        $material->update([
            'title' => 'Judul yang diperbarui admin', 'page_count' => 5,
            'file_path' => 'materials/pdf/admin-original.pdf', 'position' => 99,
        ]);
        Storage::disk(Material::PDF_DISK)->put($material->file_path, '%PDF-original-admin');
        $material->topic->update(['description' => 'Deskripsi admin', 'is_active' => false, 'position' => 50]);
        $material->delete();
        $count = Material::withTrashed()->count();

        $this->seed(MaterialSeeder::class);

        $this->assertSoftDeleted($material);
        $this->assertSame($count, Material::withTrashed()->count());
        $this->assertDatabaseHas('materials', [
            'id' => $material->id, 'title' => 'Judul yang diperbarui admin',
            'page_count' => 5, 'file_path' => 'materials/pdf/admin-original.pdf', 'position' => 99,
        ]);
        $this->assertDatabaseHas('topics', [
            'id' => $material->topic_id, 'description' => 'Deskripsi admin', 'is_active' => false, 'position' => 50,
        ]);
        $this->assertSame('%PDF-original-admin', Storage::disk(Material::PDF_DISK)->get($material->file_path));
    }
}
