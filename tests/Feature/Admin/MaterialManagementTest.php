<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\MaterialAccess;
use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Models\Material;
use App\Models\Section;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MaterialManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /** @return array<string, mixed> */
    private function payload(array $override = []): array
    {
        $sectionId = $override['section_id'] ?? Section::factory()->create()->id;
        $topicSectionId = Section::withTrashed()->whereKey($sectionId)->exists()
            ? $sectionId : Section::factory()->create()->id;
        $topic = Topic::firstOrCreate(['section_id' => $topicSectionId, 'name' => 'Umum']);

        return array_merge([
            'section_id' => $sectionId,
            'topic_id' => $topic->id,
            'title' => 'Rangkuman Penalaran Verbal',
            'summary' => 'Pola kalimat dan logika dasar.',
            'type' => MaterialType::Pdf->value,
            'access_level' => MaterialAccess::Free->value,
            'status' => MaterialStatus::Draft->value,
            'position' => '1',
        ], $override);
    }

    // ---- Index ----------------------------------------------------------------

    public function test_admin_can_list_materials(): void
    {
        $materials = Material::factory()->count(3)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.materials.index'))
            ->assertOk()
            ->assertSee($materials->pluck('title')->all());
    }

    public function test_index_shows_section_name_and_format(): void
    {
        $section = Section::factory()->create(['name' => 'Hukum Indonesia', 'code' => 'HKM']);
        Material::factory()->pdf()->create(['section_id' => $section->id, 'title' => 'Kitab Undang-undang']);

        $this->actingAs($this->admin())
            ->get(route('admin.materials.index'))
            ->assertOk()
            ->assertSee('Kitab Undang-undang')
            ->assertSee('Hukum Indonesia')
            ->assertSee('PDF');
    }

    public function test_index_searches_by_title_and_summary(): void
    {
        Material::factory()->create(['title' => 'Sejarah Indonesia Modern']);
        Material::factory()->create(['title' => 'Matematika Dasar', 'summary' => 'Perbandingan dan persentase']);

        $this->actingAs($this->admin())->get(route('admin.materials.index', ['q' => 'Sejarah']))
            ->assertOk()->assertSee('Sejarah Indonesia Modern')->assertDontSee('Matematika Dasar');

        $this->actingAs($this->admin())->get(route('admin.materials.index', ['q' => 'persentase']))
            ->assertOk()->assertSee('Matematika Dasar')->assertDontSee('Sejarah Indonesia Modern');
    }

    public function test_index_filters_by_section_type_and_status(): void
    {
        $twk = Section::factory()->create(['name' => 'TWK']);
        $tiu = Section::factory()->create(['name' => 'TIU']);

        $wanted = Material::factory()->pdf()->published()->create([
            'section_id' => $twk->id,
            'title' => 'Yang Terfilter',
        ]);

        Material::factory()->video()->draft()->create(['section_id' => $tiu->id, 'title' => 'Video Draft']);
        Material::factory()->pdf()->draft()->create(['section_id' => $tiu->id, 'title' => 'Pdf Draft']);

        $this->actingAs($this->admin())
            ->get(route('admin.materials.index', [
                'section_id' => $twk->id,
                'type' => 'pdf',
                'status' => 'published',
            ]))
            ->assertOk()
            ->assertSee($wanted->title)
            ->assertDontSee('Video Draft')
            ->assertDontSee('Pdf Draft');
    }

    public function test_index_shows_empty_state(): void
    {
        Material::factory()->count(2)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.materials.index', ['q' => 'tidak-ada-sama-sekali']))
            ->assertOk()
            ->assertSee('Materi tidak ditemukan');
    }

    public function test_index_paginates(): void
    {
        Material::factory()->count(15)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.materials.index'))
            ->assertOk()
            ->assertSee('dari 15 materi', false)
            ->assertSee(route('admin.materials.index', ['page' => 2]), false);
    }

    public function test_index_warns_when_no_section_exists(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.materials.index'))
            ->assertOk()
            ->assertSee('Belum ada bidang.');
    }

    // ---- Create ---------------------------------------------------------------

    public function test_admin_sees_create_form(): void
    {
        $section = Section::factory()->create(['name' => 'Kewarganegaraan']);

        $this->actingAs($this->admin())
            ->get(route('admin.materials.create'))
            ->assertOk()
            ->assertSee('Buat Materi Baru')
            ->assertSee('Kewarganegaraan');
    }

    public function test_admin_can_create_a_draft_pdf_material_without_uploading_anything(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.materials.store'), $this->payload())
            ->assertRedirect(route('admin.materials.index'));

        $material = Material::where('title', 'Rangkuman Penalaran Verbal')->sole();

        $this->assertSame($material->type, MaterialType::Pdf);
        $this->assertSame($material->access_level, MaterialAccess::Free);
        $this->assertSame($material->status, MaterialStatus::Draft);
        $this->assertSame('rangkuman-penalaran-verbal', $material->slug);
        $this->assertNull($material->published_at);
        $this->assertNull($material->file_path);
        $this->assertSame($admin->id, $material->created_by);
    }

    public function test_admin_can_create_a_pdf_material_and_upload_the_file(): void
    {
        Storage::fake(Material::PDF_DISK);

        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->payload([
                'status' => MaterialStatus::Published->value,
                'file' => UploadedFile::fake()->create('rangkuman.pdf', 900, 'application/pdf'),
                'page_count' => '48',
            ]))
            ->assertRedirect(route('admin.materials.index'));

        $material = Material::where('title', 'Rangkuman Penalaran Verbal')->sole();

        $this->assertNotNull($material->file_path);
        Storage::disk(Material::PDF_DISK)->assertExists($material->file_path);
        $this->assertGreaterThan(0, $material->file_size);
        $this->assertSame(48, $material->page_count);
        $this->assertNotNull($material->published_at, 'materi terbit harus punya tanggal terbit');
    }

    public function test_admin_can_create_a_video_material(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->payload([
                'title' => 'Teknik Membaca Tabel dan Grafik',
                'type' => MaterialType::Video->value,
                'video_url' => 'https://cdn.example.com/tabel.mp4',
                'duration_minutes' => '24',
                'status' => MaterialStatus::Published->value,
            ]))
            ->assertSessionHasNoErrors();

        $material = Material::where('title', 'Teknik Membaca Tabel dan Grafik')->sole();

        $this->assertSame($material->type, MaterialType::Video);
        $this->assertSame('https://cdn.example.com/tabel.mp4', $material->video_url);
        $this->assertSame(24, $material->duration_minutes);
        $this->assertSame('24 menit', $material->humanDuration());
        $this->assertNull($material->file_path);
    }

    public function test_video_fields_are_discarded_when_type_is_pdf(): void
    {
        // AdminLeave URL video terisi lalu ganti formatnya ke PDF: URL-nya
        // tidak boleh nyangkut di database.
        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->payload([
                'type' => MaterialType::Pdf->value,
                'video_url' => 'https://cdn.example.com/tertinggal.mp4',
                'duration_minutes' => '30',
                'page_count' => '20',
            ]))
            ->assertSessionHasNoErrors();

        $material = Material::where('title', 'Rangkuman Penalaran Verbal')->sole();

        $this->assertNull($material->video_url);
        $this->assertNull($material->duration_minutes);
        $this->assertSame(20, $material->page_count);
    }

    // ---- Validation -----------------------------------------------------------

    public static function invalidPayloads(): array
    {
        return [
            'bidang kosong' => [['section_id' => ''], 'section_id'],
            'bidang tidak ada' => [['section_id' => 9999], 'section_id'],
            'judul kosong' => [['title' => ''], 'title'],
            'judul terlalu pendek' => [['title' => 'Abc'], 'title'],
            'format tidak dikenal' => [['type' => 'podcast'], 'type'],
            'level akses tidak dikenal' => [['access_level' => 'gratis'], 'access_level'],
            'status tidak dikenal' => [['status' => 'terbit'], 'status'],
            'URL video tidak valid' => [
                ['type' => 'video', 'video_url' => 'bukan-tautan', 'status' => 'draft'],
                'video_url',
            ],
            'durasi negatif' => [
                ['type' => 'video', 'duration_minutes' => '-5', 'status' => 'draft'],
                'duration_minutes',
            ],
            'durasi kelewat besar' => [
                ['type' => 'video', 'duration_minutes' => '601', 'status' => 'draft'],
                'duration_minutes',
            ],
            'halaman kelewat banyak' => [['page_count' => '2001'], 'page_count'],
            'urutan negatif' => [['position' => '-1'], 'position'],
            'ringkasan kepanjangan' => [['summary' => str_repeat('x', 1001)], 'summary'],
        ];
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('invalidPayloads')]
    public function test_create_rejects_invalid_input(array $override, string $errorKey): void
    {
        $countBefore = Material::count();

        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->payload($override))
            ->assertSessionHasErrors($errorKey);

        $this->assertSame($countBefore, Material::count(), 'materi baru tidak boleh tersimpan');
    }

    public function test_create_rejects_duplicate_title_within_the_same_section(): void
    {
        $section = Section::factory()->create();
        Material::factory()->create(['section_id' => $section->id, 'title' => 'Rangkuman Penalaran Verbal']);

        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->payload(['section_id' => $section->id]))
            ->assertSessionHasErrors('title');
    }

    public function test_same_title_is_allowed_in_a_different_section(): void
    {
        $first = Section::factory()->create();
        $second = Section::factory()->create();
        Material::factory()->create(['section_id' => $first->id, 'title' => 'Pengantar Hukum']);

        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->payload([
                'section_id' => $second->id,
                'title' => 'Pengantar Hukum',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Material::where('title', 'Pengantar Hukum')->count());
    }

    public function test_published_pdf_requires_an_uploaded_file(): void
    {
        // Tanpa berkas, materi akan tampil di daftar tapi tidak bisa dibuka.
        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->payload([
                'status' => MaterialStatus::Published->value,
            ]))
            ->assertSessionHasErrors('file');
    }

    public function test_published_video_requires_a_video_url(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->payload([
                'type' => MaterialType::Video->value,
                'status' => MaterialStatus::Published->value,
            ]))
            ->assertSessionHasErrors('video_url');
    }

    public function test_uploaded_file_is_rejected_for_video_type(): void
    {
        Storage::fake(Material::PDF_DISK);

        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->payload([
                'type' => MaterialType::Video->value,
                'video_url' => 'https://cdn.example.com/a.mp4',
                'status' => MaterialStatus::Draft->value,
                'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('file');
    }

    public function test_uploaded_file_must_be_a_pdf(): void
    {
        Storage::fake(Material::PDF_DISK);

        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->payload([
                'file' => UploadedFile::fake()->create('gambar.png', 20, 'image/png'),
            ]))
            ->assertSessionHasErrors('file');
    }

    public function test_uploaded_file_larger_than_the_limit_is_rejected(): void
    {
        Storage::fake(Material::PDF_DISK);

        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->payload([
                'file' => UploadedFile::fake()->create(
                    'r.pdf',
                    Material::MAX_PDF_KILOBYTES + 1,
                    'application/pdf',
                ),
            ]))
            ->assertSessionHasErrors('file');
    }

    public function test_soft_deleted_section_cannot_be_selected(): void
    {
        $section = Section::factory()->create();
        $section->delete();

        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->payload(['section_id' => $section->id]))
            ->assertSessionHasErrors('section_id');
    }

    public function test_slug_is_made_unique_when_titles_normalise_to_the_same_slug(): void
    {
        $section = Section::factory()->create();
        Material::factory()->create(['section_id' => $section->id, 'title' => 'Sejarah Indonesia', 'slug' => 'sejarah-indonesia']);

        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->payload([
                'section_id' => $section->id,
                'title' => 'Sejarah Indonesia!',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('sejarah-indonesia-2', Material::where('title', 'Sejarah Indonesia!')->sole()->slug);
    }

    // ---- Edit / Update --------------------------------------------------------

    public function test_admin_can_open_edit_form(): void
    {
        $material = Material::factory()->create(['title' => 'Modul Matematika Dasar']);

        $this->actingAs($this->admin())
            ->get(route('admin.materials.edit', $material))
            ->assertOk()
            ->assertSee('Ubah Materi Modul Matematika Dasar');
    }

    public function test_admin_can_update_metadata_only(): void
    {
        $material = Material::factory()->pdf()->create(['title' => 'Judul Lama']);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.materials.update', $material), [
                'section_id' => $material->section_id,
                'topic_id' => $material->topic_id,
                'title' => 'Judul Baru',
                'summary' => 'Ringkasan baru.',
                'type' => MaterialType::Pdf->value,
                'access_level' => MaterialAccess::Premium->value,
                'status' => MaterialStatus::Published->value,
                'position' => '4',
            ])
            ->assertRedirect(route('admin.materials.index'));

        $material->refresh();

        $this->assertSame('Judul Baru', $material->title);
        $this->assertSame('Ringkasan baru.', $material->summary);
        $this->assertSame($material->access_level, MaterialAccess::Premium);
        $this->assertSame(4, $material->position);
        $this->assertSame($admin->id, $material->updated_by);
    }

    public function test_update_can_publish_without_reuploading_the_file(): void
    {
        Storage::fake(Material::PDF_DISK);

        $material = Material::factory()->pdf()->draft()->create();
        $material->update(['file_path' => 'materials/pdf/lama.pdf', 'file_size' => 1024]);

        $this->actingAs($this->admin())
            ->put(route('admin.materials.update', $material), [
                'section_id' => $material->section_id,
                'topic_id' => $material->topic_id,
                'title' => $material->title,
                'type' => MaterialType::Pdf->value,
                'access_level' => $material->access_level->value,
                'status' => MaterialStatus::Published->value,
            ])
            ->assertSessionHasNoErrors();

        $material->refresh();

        $this->assertSame('materials/pdf/lama.pdf', $material->file_path, 'berkas lama harus tetap dipakai');
        $this->assertNotNull($material->published_at);
    }

    public function test_update_rejects_publishing_when_the_file_is_cleared_somehow(): void
    {
        // Sanity check untuk jalur validasi: materi tanpa berkas tidak boleh terbit.
        $material = Material::factory()->pdf()->draft()->create();
        $material->forceFill(['file_path' => null, 'file_size' => null])->save();

        $this->actingAs($this->admin())
            ->put(route('admin.materials.update', $material), [
                'section_id' => $material->section_id,
                'topic_id' => $material->topic_id,
                'title' => $material->title,
                'type' => MaterialType::Pdf->value,
                'access_level' => $material->access_level->value,
                'status' => MaterialStatus::Published->value,
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_update_replaces_the_old_file_and_deletes_it_from_disk(): void
    {
        Storage::fake(Material::PDF_DISK);
        Storage::disk(Material::PDF_DISK)->put('materials/pdf/lama.pdf', 'lama');

        $material = Material::factory()->pdf()->create();
        $material->update(['file_path' => 'materials/pdf/lama.pdf', 'file_size' => 10]);

        $this->actingAs($this->admin())
            ->put(route('admin.materials.update', $material), [
                'section_id' => $material->section_id,
                'topic_id' => $material->topic_id,
                'title' => $material->title,
                'type' => MaterialType::Pdf->value,
                'access_level' => $material->access_level->value,
                'status' => $material->status->value,
                'file' => UploadedFile::fake()->create('baru.pdf', 120, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $material->refresh();

        $this->assertNotSame('materials/pdf/lama.pdf', $material->file_path);
        Storage::disk(Material::PDF_DISK)->assertExists($material->file_path);
        Storage::disk(Material::PDF_DISK)->assertMissing('materials/pdf/lama.pdf');
    }

    public function test_switching_to_video_discards_the_stored_pdf(): void
    {
        Storage::fake(Material::PDF_DISK);
        Storage::disk(Material::PDF_DISK)->put('materials/pdf/lama.pdf', 'lama');

        $material = Material::factory()->pdf()->create();
        $material->update(['file_path' => 'materials/pdf/lama.pdf', 'file_size' => 10]);

        $this->actingAs($this->admin())
            ->put(route('admin.materials.update', $material), [
                'section_id' => $material->section_id,
                'topic_id' => $material->topic_id,
                'title' => $material->title,
                'type' => MaterialType::Video->value,
                'access_level' => $material->access_level->value,
                'status' => MaterialStatus::Draft->value,
                'video_url' => 'https://cdn.example.com/baru.mp4',
                'duration_minutes' => '18',
            ])
            ->assertSessionHasNoErrors();

        $material->refresh();

        $this->assertNull($material->file_path);
        $this->assertNull($material->file_size);
        Storage::disk(Material::PDF_DISK)->assertMissing('materials/pdf/lama.pdf');
    }

    public function test_update_allows_keeping_its_own_title_and_slug(): void
    {
        $material = Material::factory()->pdf()->create(['title' => 'Judul Tetap', 'slug' => 'judul-tetap']);

        $this->actingAs($this->admin())
            ->put(route('admin.materials.update', $material), [
                'section_id' => $material->section_id,
                'topic_id' => $material->topic_id,
                'title' => 'Judul Tetap',
                'slug' => 'judul-tetap',
                'type' => MaterialType::Pdf->value,
                'access_level' => $material->access_level->value,
                'status' => $material->status->value,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('judul-tetap', $material->fresh()->slug);
    }

    public function test_update_can_move_a_material_to_another_section(): void
    {
        $material = Material::factory()->create();
        $target = Section::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.materials.update', $material), [
                'section_id' => $target->id,
                'topic_id' => Topic::factory()->create(['section_id' => $target->id])->id,
                'title' => $material->title,
                'type' => MaterialType::Pdf->value,
                'access_level' => $material->access_level->value,
                'status' => $material->status->value,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($target->id, $material->fresh()->section_id);
    }

    public function test_update_can_return_a_material_to_draft(): void
    {
        $material = Material::factory()->published()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.materials.update', $material), [
                'section_id' => $material->section_id,
                'topic_id' => $material->topic_id,
                'title' => $material->title,
                'type' => MaterialType::Pdf->value,
                'access_level' => $material->access_level->value,
                'status' => MaterialStatus::Draft->value,
            ])
            ->assertSessionHasNoErrors();

        $material->refresh();

        $this->assertSame($material->status, MaterialStatus::Draft);
        $this->assertNull($material->published_at, 'materi draft tidak boleh punya tanggal terbit');
    }

    // ---- Publish toggle -------------------------------------------------------

    public function test_admin_can_publish_a_draft_from_the_list(): void
    {
        $material = Material::factory()->pdf()->draft()->create();

        $this->actingAs($this->admin())
            ->patch(route('admin.materials.status', $material))
            ->assertRedirect(route('admin.materials.index'))
            ->assertSessionHas('success');

        $this->assertTrue($material->fresh()->isPublished());
        $this->assertNotNull($material->fresh()->published_at);
    }

    public function test_admin_can_return_a_published_material_to_draft(): void
    {
        $material = Material::factory()->published()->create();

        $this->actingAs($this->admin())
            ->patch(route('admin.materials.status', $material))
            ->assertSessionHas('success');

        $material->refresh();

        $this->assertSame($material->status, MaterialStatus::Draft);
        $this->assertNull($material->published_at);
    }

    public function test_publish_is_blocked_when_the_material_has_no_source(): void
    {
        $material = Material::factory()->draft()->withoutSource()->create();

        $this->actingAs($this->admin())
            ->patch(route('admin.materials.status', $material))
            ->assertRedirect(route('admin.materials.index'))
            ->assertSessionHas('error');

        $this->assertSame($material->status, MaterialStatus::Draft);
    }

    // ---- Delete ---------------------------------------------------------------

    public function test_admin_can_delete_a_material(): void
    {
        $material = Material::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.materials.destroy', $material))
            ->assertRedirect(route('admin.materials.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($material);
    }

    public function test_deleted_material_keeps_its_file_so_it_can_be_restored(): void
    {
        Storage::fake(Material::PDF_DISK);

        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->payload([
                'file' => UploadedFile::fake()->create('rangkuman.pdf', 100, 'application/pdf'),
            ]));

        $material = Material::where('title', 'Rangkuman Penalaran Verbal')->sole();

        $this->actingAs($this->admin())->delete(route('admin.materials.destroy', $material));

        $this->assertSoftDeleted($material);
        Storage::disk(Material::PDF_DISK)->assertExists($material->file_path);
    }

    public function test_deleted_material_disappears_from_index(): void
    {
        $material = Material::factory()->create(['title' => 'Materi Yang Dihapus']);
        $material->delete();

        $this->actingAs($this->admin())
            ->get(route('admin.materials.index'))
            ->assertOk()
            ->assertDontSee('Materi Yang Dihapus');
    }

    // ---- Access control -------------------------------------------------------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.materials.index'))->assertRedirect(route('login'));
    }

    public function test_participant_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.materials.index'))
            ->assertForbidden();
    }

    public function test_show_redirects_to_the_edit_form(): void
    {
        $material = Material::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.materials.show', $material))
            ->assertRedirect(route('admin.materials.edit', $material));
    }

    // ---- Helpers --------------------------------------------------------------

    public function test_human_file_size_uses_indian_number_format(): void
    {
        $material = Material::factory()->plain()->make(['file_size' => 2_516_582]);

        $this->assertSame('2,4 MB', $material->humanFileSize());
    }

    public function test_human_file_size_handles_small_and_zero_values(): void
    {
        $this->assertSame('512 B', Material::factory()->plain()->make(['file_size' => 512])->humanFileSize());
        $this->assertSame('0 B', Material::factory()->plain()->make(['file_size' => 0])->humanFileSize());
        $this->assertNull(Material::factory()->plain()->make(['file_size' => null])->humanFileSize());
    }

    public function test_file_url_is_null_when_no_file_is_uploaded(): void
    {
        $material = Material::factory()->plain()->make(['file_path' => null]);

        $this->assertNull($material->fileUrl());
    }

    public function test_has_content_source_depends_on_the_matching_column(): void
    {
        $this->assertTrue(Material::factory()->pdf()->plain()->create([
            'file_path' => 'materials/pdf/a.pdf',
        ])->hasContentSource());

        $this->assertFalse(Material::factory()->pdf()->plain()->create([
            'file_path' => null,
        ])->hasContentSource());

        $this->assertTrue(Material::factory()->video()->plain()->create([
            'video_url' => 'https://cdn.example.com/a.mp4',
        ])->hasContentSource());

        $this->assertFalse(Material::factory()->video()->plain()->create([
            'video_url' => null,
        ])->hasContentSource());
    }

    public function test_make_slug_falls_back_when_title_has_no_latin_characters(): void
    {
        $this->assertSame('materi', Material::makeSlug('###'));
    }

    public function test_make_slug_ignores_the_given_id(): void
    {
        $material = Material::factory()->create(['title' => 'Sejarah', 'slug' => 'sejarah']);

        $this->assertSame('sejarah', Material::makeSlug('Sejarah', $material->id));
    }
}
