<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\ColorTone;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SectionManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    // ---- Index ---------------------------------------------------------------

    public function test_admin_can_list_sections(): void
    {
        $sections = Section::factory()->count(3)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.sections.index'))
            ->assertOk()
            ->assertSee($sections->pluck('name')->all());
    }

    public function test_index_orders_by_position_then_name(): void
    {
        Section::factory()->create(['name' => 'Zeta', 'position' => 1]);
        Section::factory()->create(['name' => 'Alfa', 'position' => 1]);
        Section::factory()->create(['name' => 'Mida', 'position' => 0]);

        $this->actingAs($this->admin())
            ->get(route('admin.sections.index'))
            ->assertOk()
            ->assertSeeInOrder(['Mida', 'Alfa', 'Zeta']);
    }

    public function test_index_searches_by_name_code_and_description(): void
    {
        Section::factory()->create(['name' => 'Bahasa Indonesia', 'code' => 'BIN', 'description' => 'Tata bahasa dan penulisan']);
        Section::factory()->count(2)->create();

        $this->actingAs($this->admin())->get(route('admin.sections.index', ['q' => 'Bahasa']))
            ->assertOk()->assertSee('Bahasa Indonesia');

        $this->actingAs($this->admin())->get(route('admin.sections.index', ['q' => 'BIN']))
            ->assertOk()->assertSee('Bahasa Indonesia');

        $this->actingAs($this->admin())->get(route('admin.sections.index', ['q' => 'penulisan']))
            ->assertOk()->assertSee('Bahasa Indonesia');
    }

    public function test_index_shows_empty_state(): void
    {
        Section::factory()->count(2)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.sections.index', ['q' => 'tidak-ada-sama-sekali']))
            ->assertOk()
            ->assertSee('Bidang tidak ditemukan');
    }

    public function test_index_paginates(): void
    {
        Section::factory()->count(15)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.sections.index'))
            ->assertOk()
            ->assertSee('dari 15 bidang', false)
            ->assertSee(route('admin.sections.index', ['page' => 2]), false);
    }

    // ---- Create --------------------------------------------------------------

    public function test_admin_sees_create_form(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.sections.create'))
            ->assertOk()
            ->assertSee('Buat Bidang Baru');
    }

    public function test_admin_can_create_a_section(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.sections.store'), [
                'name' => 'Bahasa Indonesia',
                'code' => 'bin',
                'description' => 'Tata bahasa dan penulisan',
                'position' => '5',
                'icon' => 'pen-line',
                'tone' => 'mint',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.sections.index'));

        $section = Section::where('code', 'BIN')->sole();

        $this->assertSame('Bahasa Indonesia', $section->name);
        $this->assertSame('BIN', $section->code, 'kode harus dinormalisasi jadi kapital');
        $this->assertSame('bahasa-indonesia', $section->slug);
        $this->assertSame('pen-line', $section->icon);
        $this->assertSame('mint', $section->tone);
        $this->assertTrue($section->is_active);
        $this->assertSame($admin->id, $section->created_by);
    }

    public function test_admin_can_create_a_section_beyond_the_seeded_ones(): void
    {
        // TWK/TIU/TKP/Umum tidak lagi daftar tertutup.
        $this->actingAs($this->admin())
            ->post(route('admin.sections.store'), [
                'name' => 'Administrasi Negara',
                'code' => 'ADM',
                'position' => '9',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sections', ['code' => 'ADM', 'name' => 'Administrasi Negara']);
    }

    public function test_create_allows_leaving_appearance_blank(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.sections.store'), [
                'name' => 'Ilmu Komputer',
                'code' => 'ILK',
                'icon' => '',
                'tone' => '',
            ])
            ->assertSessionHasNoErrors();

        $section = Section::where('code', 'ILK')->sole();

        $this->assertNull($section->icon);
        $this->assertNull($section->tone);

        // Warna & ikon tetap di-resolve supaya tampilan tidak kosong.
        $this->assertInstanceOf(ColorTone::class, $section->resolvedTone());
        $this->assertContains($section->resolvedIcon(), Section::iconOptions());
    }

    // ---- Validation ----------------------------------------------------------

    public static function invalidPayloads(): array
    {
        return [
            'nama kosong' => [['name' => ''], 'name'],
            'nama terlalu pendek' => [['name' => 'A'], 'name'],
            'kode kosong' => [['code' => ''], 'code'],
            'kode karakter terlarang' => [['code' => 'TIU VER!'], 'code'],
            'kode terlalu pendek' => [['code' => 'T'], 'code'],
            'urutan negatif' => [['position' => '-1'], 'position'],
            'urutan terlalu besar' => [['position' => '1000'], 'position'],
            'deskripsi kepanjangan' => [['description' => str_repeat('x', 1001)], 'description'],
            'ikon tidak dikenal' => [['icon' => 'rocket'], 'icon'],
            'warna tidak dikenal' => [['tone' => 'neon'], 'tone'],
        ];
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('invalidPayloads')]
    public function test_create_rejects_invalid_input(array $override, string $errorKey): void
    {
        $admin = $this->admin();
        $countBefore = Section::count();

        $payload = array_merge([
            'name' => 'Bahasa Indonesia',
            'code' => 'BIN',
        ], $override);

        $this->actingAs($admin)
            ->post(route('admin.sections.store'), $payload)
            ->assertSessionHasErrors($errorKey);

        $this->assertSame($countBefore, Section::count(), 'bidang baru tidak boleh tersimpan');
    }

    public function test_create_rejects_duplicate_name_and_slug(): void
    {
        Section::factory()->create(['name' => 'Sejarah', 'slug' => 'sejarah']);

        $this->actingAs($this->admin())
            ->post(route('admin.sections.store'), [
                'name' => 'Sejarah',
                'slug' => 'sejarah',
                'code' => 'SEJ',
            ])
            ->assertSessionHasErrors(['name', 'slug']);
    }

    public function test_slug_is_made_unique_when_names_normalise_to_the_same_slug(): void
    {
        // Nama berbeda, tapi Str::slug() keduanya jadi "sejarah".
        Section::factory()->create(['name' => 'Sejarah', 'slug' => 'sejarah']);

        $this->actingAs($this->admin())
            ->post(route('admin.sections.store'), [
                'name' => 'Sejarah!',
                'code' => 'SEJ2',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('sejarah-2', Section::where('code', 'SEJ2')->sole()->slug);
    }

    // ---- Edit / Update -------------------------------------------------------

    public function test_admin_can_open_edit_form(): void
    {
        $section = Section::factory()->create(['name' => 'Bahasa Indonesia']);

        $this->actingAs($this->admin())
            ->get(route('admin.sections.edit', $section))
            ->assertOk()
            ->assertSee('Ubah Bidang Bahasa Indonesia');
    }

    public function test_admin_can_update_a_section(): void
    {
        $section = Section::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.sections.update', $section), [
                'name' => 'Kewarganegaraan',
                'code' => 'kew',
                'description' => 'Pancasila dan UUD 1945',
                'position' => '7',
                'icon' => 'crown',
                'tone' => 'coral',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.sections.index'));

        $section->refresh();

        $this->assertSame('Kewarganegaraan', $section->name);
        $this->assertSame('KEW', $section->code);
        $this->assertSame('crown', $section->icon);
        $this->assertSame('coral', $section->tone);
        $this->assertSame($admin->id, $section->updated_by);
    }

    public function test_update_allows_keeping_own_code_but_rejects_another_sections_code(): void
    {
        $actor = $this->admin();
        $other = Section::factory()->create(['code' => 'OTH']);

        $this->actingAs($actor)
            ->put(route('admin.sections.update', $other), [
                'name' => 'Nama Baru',
                'code' => $other->code,
            ])
            ->assertSessionHasNoErrors();

        $another = Section::factory()->create(['code' => 'ANI']);

        $this->actingAs($actor)
            ->put(route('admin.sections.update', $another), [
                'name' => $another->name,
                'code' => $other->code,
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_update_keeps_existing_slug_when_name_unchanged(): void
    {
        $section = Section::factory()->create(['name' => 'Sejarah', 'slug' => 'sejarah-indo']);

        $this->actingAs($this->admin())
            ->put(route('admin.sections.update', $section), [
                'name' => 'Sejarah',
                'slug' => $section->slug,
                'code' => $section->code,
            ]);

        $this->assertSame('sejarah-indo', $section->fresh()->slug);
    }

    public function test_update_regenerates_slug_when_slug_left_blank(): void
    {
        $section = Section::factory()->create(['name' => 'Sejarah', 'slug' => 'sejarah-lama']);

        $this->actingAs($this->admin())
            ->put(route('admin.sections.update', $section), [
                'name' => 'Geografi Indonesia',
                'slug' => '',
                'code' => $section->code,
            ]);

        $this->assertSame('geografi-indonesia', $section->fresh()->slug);
    }

    public function test_update_can_deactivate_a_section(): void
    {
        $section = Section::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.sections.update', $section), [
                'name' => $section->name,
                'code' => $section->code,
                'is_active' => '0',
            ]);

        $this->assertFalse($section->fresh()->is_active);
    }

    // ---- Delete --------------------------------------------------------------

    public function test_admin_can_delete_a_section(): void
    {
        $section = Section::factory()->create(['name' => 'Hukum Indonesia']);

        $this->actingAs($this->admin())
            ->delete(route('admin.sections.destroy', $section))
            ->assertRedirect(route('admin.sections.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($section);
    }

    public function test_seeded_sections_can_also_be_deleted(): void
    {
        // Tidak ada lagi proteksi "bidang inti" — TWK/TIU/TKP biasa saja.
        $twk = Section::factory()->create(['name' => 'TWK', 'code' => 'TWK']);

        $this->actingAs($this->admin())
            ->delete(route('admin.sections.destroy', $twk))
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted($twk);
    }

    public function test_deleted_section_disappears_from_index(): void
    {
        $section = Section::factory()->create(['name' => 'Hukum']);
        $section->delete();

        $this->actingAs($this->admin())
            ->get(route('admin.sections.index'))
            ->assertOk()
            ->assertDontSee('Hukum');
    }

    // ---- Access control ------------------------------------------------------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.sections.index'))->assertRedirect(route('login'));
    }

    public function test_participant_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.sections.index'))
            ->assertForbidden();
    }

    public function test_admin_sidebar_links_to_sections(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.sections.index'))
            ->assertOk()
            ->assertSee('>Bidang</span>', false);
    }

    // ---- Helpers -------------------------------------------------------------

    public function test_make_slug_falls_back_when_name_has_no_latin_characters(): void
    {
        $this->assertSame('bidang', Section::makeSlug('###'));
    }

    public function test_make_slug_ignores_the_given_id(): void
    {
        $section = Section::factory()->create(['name' => 'Sejarah', 'slug' => 'sejarah']);

        $this->assertSame('sejarah', Section::makeSlug('Sejarah', $section->id));
    }

    public function test_suggest_code_takes_first_three_letters(): void
    {
        $this->assertSame('BAH', Section::suggestCode('Bahasa Indonesia'));
        $this->assertSame('ADM', Section::suggestCode('Administrasi Negara'));
        $this->assertSame('BID', Section::suggestCode('###'));
    }

    public function test_every_section_icon_exists_in_the_icon_set(): void
    {
        $icons = (string) file_get_contents(resource_path('js/lontar/icons.js'));

        foreach (Section::ICONS as $icon) {
            $this->assertStringContainsString(
                "'{$icon}':",
                $icons,
                "Ikon '{$icon}' belum ada di icons.js",
            );
        }
    }

    public function test_every_color_tone_has_a_badge_and_icon_class_in_css(): void
    {
        // Tanpa test ini, enum bisa menambahkan tone yang tidak punya class CSS
        // dan badge-nya diam-diam tampil tanpa warna.
        $css = (string) file_get_contents(resource_path('css/lontar/components.css'));

        foreach (ColorTone::cases() as $tone) {
            $this->assertStringContainsString(
                '.'.$tone->badgeClass(),
                $css,
                "Class '{$tone->badgeClass()}' belum ada di components.css",
            );

            $this->assertStringContainsString(
                '.'.$tone->iconModifier(),
                $css,
                "Modifier '{$tone->iconModifier()}' belum ada di components.css",
            );
        }
    }

    public function test_resolved_tone_falls_back_to_position(): void
    {
        $tones = ColorTone::cases();

        $section = Section::factory()->plain()->make(['position' => 0]);
        $this->assertSame($tones[0], $section->resolvedTone());

        $section = Section::factory()->plain()->make(['position' => 3]);
        $this->assertSame($tones[3], $section->resolvedTone());
    }

    public function test_resolved_tone_prefers_the_stored_value(): void
    {
        $section = Section::factory()->make(['tone' => 'violet', 'position' => 0]);

        $this->assertSame(ColorTone::Violet, $section->resolvedTone());
    }
}
