<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TestimonialManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_edit_testimonials(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.testimonials.create'))->assertSee('Tambah Testimoni')->assertSee('Tersembunyi (hidden)');
        $this->post(route('admin.testimonials.store'), [
            'name' => 'Peserta Pertama', 'institution' => 'Instansi Pertama',
            'content' => 'Saya dapat mempelajari materi dengan lebih teratur.', 'is_visible' => 1,
        ])->assertRedirect(route('admin.testimonials.index'))->assertSessionHasNoErrors();

        $testimonial = Testimonial::sole();
        $this->assertTrue($testimonial->is_visible);
        $this->get(route('admin.testimonials.edit', $testimonial))->assertSee('Peserta Pertama');
        $this->put(route('admin.testimonials.update', $testimonial), [
            'name' => 'Peserta Kedua', 'institution' => null,
            'content' => 'Latihan dan pembahasan membantu saya memahami materi.', 'is_visible' => 0,
        ])->assertRedirect(route('admin.testimonials.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('testimonials', 1);
        $this->assertDatabaseHas('testimonials', [
            'id' => $testimonial->id, 'name' => 'Peserta Kedua', 'institution' => null,
            'content' => 'Latihan dan pembahasan membantu saya memahami materi.', 'is_visible' => false,
        ]);
    }

    public function test_admin_can_hide_and_restore_a_testimonial_without_losing_its_data(): void
    {
        $testimonial = Testimonial::factory()->visible()->create();
        $original = $testimonial->only(['id', 'name', 'institution', 'content']);
        $createdAt = $testimonial->getRawOriginal('created_at');
        $this->actingAs(User::factory()->admin()->create());

        $this->from(route('admin.testimonials.index'))->patch(route('admin.testimonials.status', $testimonial), [
            'is_visible' => 0, 'content' => 'Isi palsu yang tidak boleh menggantikan data.',
        ])->assertRedirect(route('admin.testimonials.index'))->assertSessionHas('success');
        $this->assertFalse($testimonial->fresh()->is_visible);
        $this->get(route('home'))->assertDontSee($testimonial->content);
        $this->get(route('home'))->assertDontSee($testimonial->content);
        $this->get(route('admin.testimonials.index'))->assertSee($testimonial->content)->assertSee('Tersembunyi (hidden)');

        $this->from(route('admin.testimonials.index'))->patch(route('admin.testimonials.status', $testimonial), [
            'is_visible' => 1,
        ])->assertRedirect(route('admin.testimonials.index'))->assertSessionHas('success');
        $this->assertTrue($testimonial->fresh()->is_visible);
        $this->assertSame($original, $testimonial->fresh()->only(array_keys($original)));
        $this->assertSame($createdAt, $testimonial->fresh()->getRawOriginal('created_at'));
        $this->assertDatabaseCount('testimonials', 1);
        $this->get(route('home'))->assertSee($testimonial->content);
        $this->get(route('home'))->assertSee($testimonial->content);
    }

    public function test_repeated_visibility_requests_keep_the_requested_state(): void
    {
        $testimonial = Testimonial::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        $this->patch(route('admin.testimonials.status', $testimonial), ['is_visible' => 1])->assertSessionHasNoErrors();
        $this->patch(route('admin.testimonials.status', $testimonial), ['is_visible' => 1])->assertSessionHasNoErrors();

        $this->assertTrue($testimonial->fresh()->is_visible);
        $this->assertDatabaseCount('testimonials', 1);
    }

    #[DataProvider('unauthorizedVisitors')]
    public function test_management_routes_refuse_unauthorized_visitors(string $visitor, ?string $redirectRoute): void
    {
        $testimonial = Testimonial::factory()->create();
        $original = $testimonial->only(['name', 'institution', 'content', 'is_visible']);
        $user = match ($visitor) {
            'member' => User::factory()->create(),
            'inactive admin' => User::factory()->admin()->inactive()->create(),
            'unverified admin' => User::factory()->admin()->unverified()->create(),
            default => null,
        };
        $routes = [
            ['GET', 'admin.testimonials.index', []],
            ['GET', 'admin.testimonials.create', []],
            ['POST', 'admin.testimonials.store', []],
            ['GET', 'admin.testimonials.edit', [$testimonial]],
            ['PUT', 'admin.testimonials.update', [$testimonial]],
            ['PATCH', 'admin.testimonials.status', [$testimonial]],
        ];
        $payload = ['name' => 'Penyusup', 'content' => 'Konten dari pengguna tanpa izin admin.', 'is_visible' => 1];

        foreach ($routes as [$method, $route, $parameters]) {
            if ($user !== null) {
                $this->actingAs($user);
            }

            $response = $this->call($method, route($route, $parameters), $payload);

            if ($redirectRoute !== null) {
                $response->assertRedirect(route($redirectRoute));
            } else {
                $response->assertForbidden();
            }
        }

        $this->assertDatabaseCount('testimonials', 1);
        $this->assertDatabaseHas('testimonials', ['id' => $testimonial->id, ...$original]);
    }

    public static function unauthorizedVisitors(): array
    {
        return [
            'guest' => ['guest', 'login'],
            'member' => ['member', null],
            'inactive admin' => ['inactive admin', 'login'],
            'unverified admin' => ['unverified admin', 'verification.notice'],
        ];
    }

    #[DataProvider('invalidVisibility')]
    public function test_invalid_visibility_returns_422_without_changing_the_record(array $payload): void
    {
        $testimonial = Testimonial::factory()->visible()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patchJson(route('admin.testimonials.status', $testimonial), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('is_visible');

        $this->assertTrue($testimonial->fresh()->is_visible);
        $this->assertDatabaseCount('testimonials', 1);
    }

    public static function invalidVisibility(): array
    {
        return [
            'missing' => [[]], 'null' => [['is_visible' => null]],
            'status string' => [['is_visible' => 'hidden']], 'array' => [['is_visible' => [0]]],
            'out of range' => [['is_visible' => 2]],
        ];
    }

    public function test_invalid_admin_content_and_status_do_not_create_or_update_records(): void
    {
        $testimonial = Testimonial::factory()->create();
        $original = $testimonial->only(['name', 'institution', 'content', 'is_visible']);
        $this->actingAs(User::factory()->admin()->create());

        $this->postJson(route('admin.testimonials.store'), [])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'content', 'is_visible']);
        $this->putJson(route('admin.testimonials.update', $testimonial), [
            'name' => ['invalid'], 'content' => 'short', 'is_visible' => 'hidden',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'content', 'is_visible']);

        $this->assertDatabaseCount('testimonials', 1);
        $this->assertDatabaseHas('testimonials', ['id' => $testimonial->id, ...$original]);
    }

    public function test_admin_listing_search_and_visibility_filters_preserve_pagination(): void
    {
        Testimonial::factory()->count(13)->create(['name' => 'Peserta Pilihan']);
        Testimonial::factory()->visible()->create(['name' => 'Peserta Pilihan Publik']);
        Testimonial::factory()->create(['name' => 'Orang Lain', 'institution' => 'Instansi Lain', 'content' => 'Latihan soal sangat membantu persiapan.']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.testimonials.index', ['q' => 'Peserta Pilihan', 'status' => 'hidden']))
            ->assertSee('Peserta Pilihan')->assertDontSee('Peserta Pilihan Publik')->assertDontSee('Orang Lain')
            ->assertViewHas('testimonials', fn ($testimonials): bool => $testimonials->total() === 13 && $testimonials->count() === 12)
            ->assertViewHas('counts', ['total' => 15, 'visible' => 1, 'hidden' => 14])
            ->assertSee('status=hidden')->assertSee('page=2');

        $this->get(route('admin.testimonials.index', ['q' => 'Peserta Pilihan', 'status' => 'visible']))
            ->assertSee('Peserta Pilihan Publik')
            ->assertViewHas('testimonials', fn ($testimonials): bool => $testimonials->total() === 1);
    }

    public function test_unfiltered_admin_listing_includes_both_statuses(): void
    {
        $visible = Testimonial::factory()->visible()->create();
        $hidden = Testimonial::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.testimonials.index'))
            ->assertSee($visible->content)->assertSee($hidden->content)->assertSee('Sembunyikan')->assertSee('Tampilkan');
    }

    public function test_invalid_admin_filters_return_422(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->getJson(route('admin.testimonials.index', ['q' => ['invalid'], 'status' => 'draft']))
            ->assertUnprocessable()->assertJsonValidationErrors(['q', 'status']);
    }

    public function test_nonexistent_testimonials_return_404(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.testimonials.edit', 99999))->assertNotFound();
        $this->patch(route('admin.testimonials.status', 99999), ['is_visible' => 1])->assertNotFound();
        $this->put(route('admin.testimonials.update', 99999), [
            'name' => 'Peserta Baru', 'content' => 'Pengalaman belajar yang sangat membantu.', 'is_visible' => 1,
        ])->assertNotFound();

        $this->assertDatabaseCount('testimonials', 0);
    }

    public function test_admin_pages_escape_all_testimonial_text(): void
    {
        $testimonial = Testimonial::factory()->create([
            'name' => '<script>alert("name")</script>',
            'institution' => '<img src=x onerror=alert("institution")>',
            'content' => '</textarea><script>alert("content")</script>',
        ]);
        $this->actingAs(User::factory()->admin()->create());

        foreach (['admin.testimonials.index', 'admin.testimonials.edit'] as $route) {
            $response = $this->get(route($route, $route === 'admin.testimonials.edit' ? $testimonial : []));

            foreach (['name', 'institution', 'content'] as $field) {
                $response->assertSee($testimonial->$field)->assertDontSee($testimonial->$field, false);
            }
        }
    }
}
