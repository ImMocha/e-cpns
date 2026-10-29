<?php

declare(strict_types=1);

namespace Tests\Feature\Member;

use App\Models\Material;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Akses materi dari sisi peserta (§10, §38).
 *
 * Yang dijaga di sini bukan cuma "halaman terbuka", tapi juga hal yang lebih
 * halus: draft tidak boleh bocor lewat HTML, dan PDF premium tidak boleh
 * diambil dengan menebak URL.
 */
class MaterialAccessTest extends TestCase
{
    use RefreshDatabase;

    private function peserta(): User
    {
        return User::factory()->create();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /** @param  array<string, mixed>  $override */
    private function publishedPdf(array $override = [], ?Section $section = null): Material
    {
        return Material::factory()->pdf()->published()->create(array_merge([
            'section_id' => ($section ?? Section::factory()->create())->id,
        ], $override));
    }

    /**
     * Materi PDF yang berkasnya benar-benar ada di disk.
     *
     * Tanpa ini `fileExists()` bernilai false dan halaman ikut menampilkan
     * cabang "berkas belum tersedia" — bukan halaman bacanya.
     */
    private function publishedPdfWithFile(array $override = [], ?Section $section = null): Material
    {
        Storage::fake(Material::PDF_DISK);

        $material = $this->publishedPdf($override, $section);
        $material->update(['file_path' => 'materials/pdf/ada.pdf']);

        Storage::disk(Material::PDF_DISK)->put('materials/pdf/ada.pdf', '%PDF-isi');

        return $material;
    }

    // ---- Daftar ---------------------------------------------------------------

    public function test_participant_sees_published_materials(): void
    {
        $material = $this->publishedPdf(['title' => 'Rangkuman Pengetahuan Umum']);

        $this->actingAs($this->peserta())
            ->get(route('materials.index'))
            ->assertOk()
            ->assertSee($material->topic->name)
            ->assertSee(route('materials.topic', $material->topic), false);
    }

    public function test_draft_materials_are_absent_from_the_page_entirely(): void
    {
        // Bukan "disembunyikan" — judulnya harus benar-benar tidak ada di HTML,
        // supaya tidak bisa ditemukan lewat view-source.
        $draft = Material::factory()->pdf()->draft()->create(['title' => 'Materi Belum Terbit']);

        $this->actingAs($this->peserta())
            ->get(route('materials.index'))
            ->assertOk()
            ->assertDontSee($draft->title);
    }

    public function test_materials_of_inactive_sections_are_hidden(): void
    {
        $section = Section::factory()->inactive()->create();
        $material = $this->publishedPdf(['title' => 'Materi Bidang Nonaktif'], $section);

        $this->actingAs($this->peserta())
            ->get(route('materials.index'))
            ->assertOk()
            ->assertDontSee($material->title);
    }

    public function test_soft_deleted_materials_are_hidden(): void
    {
        $material = $this->publishedPdf(['title' => 'Materi Sudah Dihapus']);
        $material->delete();

        $this->actingAs($this->peserta())
            ->get(route('materials.index'))
            ->assertOk()
            ->assertDontSee($material->title);
    }

    public function test_counts_reflect_only_visible_materials(): void
    {
        $this->publishedPdf(['title' => 'Materi Terbit']);
        Material::factory()->pdf()->draft()->create(['title' => 'Materi Draft']);
        $this->publishedPdf([], Section::factory()->inactive()->create());

        // Ringkasan di header harus 1, bukan 3 — draft dan bidang nonaktif
        // tidak boleh ikut terhitung.
        $body = (string) $this->actingAs($this->peserta())
            ->get(route('materials.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Materi Draft', $body);

        preg_match_all('/stat__label">Tersedia<\/div>\s*<div[^>]*>([\d.,]+)<\/div>/', $body, $matches);
        $this->assertSame('1', $matches[1][0] ?? '', 'header harus menghitung hanya materi yang tayang');
    }

    public function test_list_can_be_filtered_by_section(): void
    {
        $twk = Section::factory()->create(['name' => 'TWK']);
        $tiu = Section::factory()->create(['name' => 'TIU']);

        $wanted = $this->publishedPdf(['title' => 'Materi TWK'], $twk);
        $this->publishedPdf(['title' => 'Materi TIU'], $tiu);

        $this->actingAs($this->peserta())
            ->get(route('materials.index', ['section_id' => $twk->id]))
            ->assertOk()
            ->assertViewHas('topics', fn ($topics): bool => $topics->contains('id', $wanted->topic_id))
            ->assertDontSee('Materi TIU');
    }

    public function test_list_can_be_filtered_by_format_and_searched(): void
    {
        $pdf = $this->publishedPdf(['title' => 'Modul Matematika Dasar']);
        $video = Material::factory()->video()->published()->create(['title' => 'Teknik Membaca Tabel']);

        $this->actingAs($this->peserta())
            ->get(route('materials.index', ['type' => 'video']))
            ->assertOk()
            ->assertViewHas('topics', fn ($topics): bool => $topics->contains('id', $video->topic_id) && ! $topics->contains('id', $pdf->topic_id))
            ->assertDontSee('Modul Matematika Dasar');

        $this->actingAs($this->peserta())
            ->get(route('materials.index', ['q' => 'Matematika']))
            ->assertOk()
            ->assertViewHas('topics', fn ($topics): bool => $topics->contains('id', $pdf->topic_id) && ! $topics->contains('id', $video->topic_id))
            ->assertDontSee('Teknik Membaca Tabel');
    }

    public function test_section_pills_only_list_fields_that_have_published_materials(): void
    {
        $ada = Section::factory()->create(['name' => 'TWK']);
        Section::factory()->create(['name' => 'Bidang Kosong']);

        $this->publishedPdf([], $ada);

        $this->actingAs($this->peserta())
            ->get(route('materials.index'))
            ->assertOk()
            ->assertSee('TWK (1)')
            ->assertDontSee('Bidang Kosong');
    }

    // ---- Halaman baca --------------------------------------------------------

    public function test_participant_can_open_a_free_pdf_material(): void
    {
        $material = $this->publishedPdfWithFile(['title' => 'Materi Gratis']);

        $this->actingAs($this->peserta())
            ->get(route('materials.show', $material))
            ->assertOk()
            ->assertSee('Materi Gratis')
            ->assertSee('Tanpa unduh &amp; cetak', false);
    }

    public function test_participant_can_open_a_free_video_material(): void
    {
        $material = Material::factory()->video()->published()->free()->create(['title' => 'Video Gratis']);

        $this->actingAs($this->peserta())
            ->get(route('materials.show', $material))
            ->assertOk()
            ->assertSee('Video Gratis')
            ->assertSee($material->video_url);
    }

    public function test_premium_material_page_opens_with_its_reader_locked(): void
    {
        $premium = Material::factory()->pdf()->premium()->published()->create(['title' => 'Materi Premium']);

        $this->actingAs($this->peserta())
            ->get(route('materials.show', $premium))
            ->assertOk()
            ->assertViewHas('contentAccessible', false)
            ->assertDontSee(route('materials.file', $premium));
    }

    public function test_premium_material_is_listed_but_marked_locked(): void
    {
        $premium = Material::factory()->pdf()->premium()->published()->create(['title' => 'Materi Premium']);

        $this->actingAs($this->peserta())
            ->get(route('materials.topic', $premium->topic))
            ->assertOk()
            ->assertSee('Materi Premium')
            ->assertSee('Terkunci');
    }

    public function test_draft_material_page_is_not_found_for_participant(): void
    {
        $draft = Material::factory()->pdf()->draft()->create();

        $this->actingAs($this->peserta())
            ->get(route('materials.show', $draft))
            ->assertNotFound();
    }

    public function test_materials_of_inactive_sections_are_not_found(): void
    {
        $section = Section::factory()->inactive()->create();
        $material = $this->publishedPdf([], $section);

        $this->actingAs($this->peserta())
            ->get(route('materials.show', $material))
            ->assertNotFound();
    }

    public function test_related_materials_come_from_the_same_section(): void
    {
        $section = Section::factory()->create();
        $current = $this->publishedPdf(['title' => 'Materi Sedang Dibaca'], $section);
        $this->publishedPdf(['title' => 'Materi Sekelas'], $section);
        $this->publishedPdf(['title' => 'Materi Bidang Lain']);

        $body = $this->actingAs($this->peserta())->get(route('materials.show', $current))->assertOk()->getContent();

        $this->assertStringContainsString('Materi Sekelas', (string) $body);
        $this->assertStringNotContainsString('Materi Bidang Lain', (string) $body);
    }

    // ---- Berkas PDF -----------------------------------------------------------

    public function test_participant_can_download_a_free_pdf(): void
    {
        $material = $this->publishedPdfWithFile(['title' => 'Materi Gratis']);

        $this->actingAs($this->peserta())
            ->get(route('materials.file', $material))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_premium_pdf_cannot_be_fetched_directly_by_a_participant(): void
    {
        // Ini yang paling mudah bocor: kalau gating hanya ada di UI, orang
        // cukup mengedit URL dan mengambil berkasnya.
        $material = Material::factory()->pdf()->premium()->published()->create();
        $material->update(['file_path' => 'materials/pdf/rahasia.pdf']);
        Storage::disk(Material::PDF_DISK)->put('materials/pdf/rahasia.pdf', '%PDF-isi');

        $this->actingAs($this->peserta())
            ->get(route('materials.file', $material))
            ->assertForbidden();
    }

    public function test_guest_cannot_fetch_the_pdf_file(): void
    {
        $material = $this->publishedPdfWithFile();

        $this->get(route('materials.file', $material))->assertRedirect(route('login'));
    }

    public function test_missing_pdf_returns_not_found(): void
    {
        Storage::fake(Material::PDF_DISK);
        $material = $this->publishedPdf();
        $material->update(['file_path' => 'materials/pdf/hilang.pdf']);

        $this->actingAs($this->peserta())
            ->get(route('materials.file', $material))
            ->assertNotFound();
    }

    public function test_admin_can_open_and_fetch_everything_including_drafts(): void
    {
        Storage::fake(Material::PDF_DISK);
        $material = Material::factory()->pdf()->premium()->draft()->create();
        $material->update(['file_path' => 'materials/pdf/pratinjau.pdf']);
        Storage::disk(Material::PDF_DISK)->put('materials/pdf/pratinjau.pdf', '%PDF-isi');

        $this->actingAs($this->admin())->get(route('materials.show', $material))->assertOk();
        $this->actingAs($this->admin())->get(route('materials.file', $material))->assertOk();
    }

    // ---- Model ----------------------------------------------------------------

    public function test_is_accessible_by_follows_access_level(): void
    {
        $peserta = $this->peserta();

        $this->assertTrue(Material::factory()->free()->plain()->create(['file_path' => 'a.pdf'])->isAccessibleBy($peserta));
        $this->assertFalse(Material::factory()->premium()->plain()->create(['file_path' => 'a.pdf'])->isAccessibleBy($peserta));
        $this->assertFalse(Material::factory()->free()->plain()->create(['file_path' => 'a.pdf'])->isAccessibleBy(null));
    }

    public function test_access_notice_explains_the_actual_reason(): void
    {
        $peserta = $this->peserta();

        // Premium tapi sumbernya ada → peserta tinggal perlu paket.
        $locked = Material::factory()->premium()->plain()->create(['file_path' => 'a.pdf']);
        $this->assertStringContainsString('Premium', (string) $locked->accessNotice($peserta));

        // Premium tanpa sumber → masalahnya isi, bukan paket. Pesan yang salah
        // akan membuat peserta menghubungi tim yang keliru.
        $broken = Material::factory()->premium()->plain()->create(['file_path' => null]);
        $this->assertStringContainsString('belum punya berkas', (string) $broken->accessNotice($peserta));

        // Gratis dan boleh diakses → tidak ada pesan sama sekali.
        $open = Material::factory()->free()->plain()->create(['file_path' => 'a.pdf']);
        $this->assertNull($open->accessNotice($peserta));

        // Gratis tanpa sumber tetap "boleh diakses": yang bermasalah adalah
        // berkasnya, bukan halamannya. Formatter di halaman baca yang
        // menjelaskannya.
        $freeButBroken = Material::factory()->free()->plain()->create(['file_path' => null]);
        $this->assertTrue($freeButBroken->isAccessibleBy($peserta));
        $this->assertNull($freeButBroken->accessNotice($peserta));
    }

    public function test_is_visible_to_member_matches_the_listing_scope(): void
    {
        $published = $this->publishedPdf();
        $this->assertTrue($published->isVisibleToMember());

        $this->assertFalse(Material::factory()->pdf()->draft()->create()->isVisibleToMember());
        $this->assertFalse($this->publishedPdf([], Section::factory()->inactive()->create())->isVisibleToMember());

        $deleted = $this->publishedPdf();
        $deleted->delete();
        $this->assertFalse($deleted->fresh()->isVisibleToMember());
    }

    public function test_file_url_is_a_route_not_a_public_storage_path(): void
    {
        $material = $this->publishedPdf();
        $material->update(['file_path' => 'materials/pdf/a.pdf']);

        $url = (string) $material->fileUrl();

        $this->assertStringContainsString('/materials/'.$material->id.'/file', $url);
        $this->assertStringNotContainsString('/storage/', $url, 'URL publik membuat gating premium tidak berarti');
    }

    // ---- Penampil PDF tanpa tombol unduh / cetak ----------------------------

    public function test_pdf_is_never_embedded_with_a_browser_pdf_viewer(): void
    {
        $material = $this->publishedPdfWithFile(['title' => 'Materi Gratis']);

        $body = (string) $this->actingAs($this->peserta())
            ->get(route('materials.show', $material))
            ->assertOk()
            ->getContent();

        // Viewer bawaan browser selalu menyediakan toolbar Unduh dan Cetak.
        // Selama PDF-nya diserahkan sebagai dokumen, keduanya tidak bisa dicegah.
        foreach (['<object', '<embed', '<iframe'] as $tag) {
            $this->assertStringNotContainsString(
                $tag,
                $body,
                "Tag {$tag} memunculkan viewer bawaan browser lengkap dengan tombol unduh",
            );
        }

        $this->assertStringContainsString('data-pdf-reader', $body);
    }

    public function test_reader_toolbar_has_no_download_or_print_button(): void
    {
        $material = $this->publishedPdfWithFile();

        $body = (string) $this->actingAs($this->peserta())
            ->get(route('materials.show', $material))
            ->assertOk()
            ->getContent();

        preg_match_all('/aria-label="([^"]+)"/', $body, $matches);

        foreach ($matches[1] as $label) {
            $this->assertDoesNotMatchRegularExpression(
                '/unduh|download|cetak|print|simpan/i',
                $label,
                "Tombol \"{$label}\" menyediakan jalan mengunduh atau mencetak",
            );
        }
    }

    public function test_reader_shows_the_material_title_and_has_a_separate_fullscreen_control(): void
    {
        $material = $this->publishedPdfWithFile(['title' => 'Rangkuman Pengetahuan Umum']);

        $this->actingAs($this->peserta())->get(route('materials.show', $material))
            ->assertSee('<span>Rangkuman Pengetahuan Umum</span>', false)
            ->assertDontSee($material->slug.'.pdf')
            ->assertSee('id="pdfview-fullscreen"', false)
            ->assertSee('aria-label="Layar penuh"', false)
            ->assertSee('aria-label="Sesuaikan lebar halaman"', false);
    }

    public function test_reader_marks_the_document_with_the_participant_identity(): void
    {
        Storage::fake(Material::PDF_DISK);
        $peserta = $this->peserta();
        $material = $this->publishedPdf();
        $material->update(['file_path' => 'materials/pdf/a.pdf']);
        Storage::disk(Material::PDF_DISK)->put('materials/pdf/a.pdf', '%PDF-isi');

        $body = (string) $this->actingAs($peserta)
            ->get(route('materials.show', $material))
            ->assertOk()
            ->getContent();

        // Watermark ditindaklanjuti pdf.js dari atribut ini.
        $this->assertStringContainsString('data-watermark="'.$peserta->name.' — '.$peserta->email.'"', $body);
    }

    public function test_pdf_response_is_not_cached_by_the_browser(): void
    {
        $material = $this->publishedPdfWithFile();

        $response = $this->actingAs($this->peserta())->get(route('materials.file', $material));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');

        // pdf.js mengambil lewat fetch(), jadi inline; yang penting salinannya
        // tidak disimpan browser setelah peserta logout. Urutan directive
        // berbeda antar versi, jadi yang dicek isi bukan string persis.
        $cacheControl = (string) $response->headers->get('cache-control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
    }
}
