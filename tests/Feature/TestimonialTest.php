<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Models\Testimonial;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPackage;
use Database\Seeders\TestimonialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TestimonialTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_shows_testimonials_and_requires_login_to_write(): void
    {
        $this->get(route('home'))
            ->assertSee('Belum ada testimoni')
            ->assertSee('Masuk untuk Menulis')
            ->assertDontSee(route('testimonials.store'));
    }

    public function test_landing_carousel_includes_all_visible_testimonials_and_ignores_visibility_parameters(): void
    {
        Testimonial::factory()->visible()->count(10)->create();
        $hidden = Testimonial::factory()->create();
        $this->get(route('home', ['status' => 'hidden']))
            ->assertSee('data-testimonial-carousel', false)->assertDontSee($hidden->content)
            ->assertViewHas('testimonials', fn ($testimonials): bool => $testimonials->count() === 10);
    }

    public function test_homepage_carousel_includes_older_visible_testimonials(): void
    {
        Testimonial::factory()->visible()->create(['content' => 'Pengalaman lama yang tidak masuk cuplikan.']);
        $testimonials = Testimonial::factory()->visible()->count(3)->create();
        $hidden = Testimonial::factory()->create(['content' => 'Pengalaman tersembunyi yang belum boleh ditampilkan.']);

        $this->get(route('home'))->assertSee($testimonials->first()->content)
            ->assertSee($testimonials->last()->content)->assertDontSee($hidden->content)
            ->assertSee('Pengalaman lama yang tidak masuk cuplikan.')
            ->assertSee(route('home'));
    }

    public function test_purchaser_submission_uses_account_name_and_ignores_forged_attributes(): void
    {
        $this->actingAs($this->buyer());
        $this->post(route('testimonials.store'), [
            'name' => '  Peserta Baru  ', 'institution' => '  Instansi Peserta  ',
            'content' => 'Materi dan latihan membantu saya belajar lebih terarah.',
            'is_visible' => true, 'id' => 99999, 'created_at' => '2000-01-01 00:00:00',
        ])->assertRedirect(route('home').'#testimoni')->assertSessionHasNoErrors()
            ->assertSessionHas('testimonial_success');

        $testimonial = Testimonial::sole();
        $this->assertSame('Peserta Baru', $testimonial->name);
        $this->assertSame('Instansi Peserta', $testimonial->institution);
        $this->assertFalse($testimonial->is_visible);
        $this->assertNotSame(99999, $testimonial->id);
        $this->assertNotSame('2000-01-01', $testimonial->created_at->toDateString());
        $this->get(route('home'))->assertDontSee($testimonial->content);
    }

    public function test_submission_accepts_an_omitted_optional_institution(): void
    {
        $this->actingAs($this->buyer());
        $this->post(route('testimonials.store'), [
            'name' => 'Peserta Mandiri', 'content' => 'Latihan soal membantu persiapan saya.',
        ])->assertRedirect(route('home').'#testimoni')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('testimonials', ['name' => 'Peserta Baru', 'institution' => null, 'is_visible' => false]);
    }

    #[DataProvider('invalidSubmissions')]
    public function test_invalid_submissions_return_422_without_saving_data(string $field, mixed $value, string $message): void
    {
        $this->actingAs($this->buyer());
        $payload = ['name' => 'Peserta Baru', 'institution' => null, 'content' => 'Materi pembelajaran sangat membantu saya.'];
        $payload[$field] = $value;

        $this->postJson(route('testimonials.store'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors([$field => $message]);

        $this->assertDatabaseCount('testimonials', 0);
    }

    public static function invalidSubmissions(): array
    {
        return [
            'missing name' => ['name', null, 'Nama wajib diisi.'],
            'blank name' => ['name', '   ', 'Nama wajib diisi.'],
            'array name' => ['name', ['invalid'], 'Nama harus berupa teks.'],
            'long name' => ['name', str_repeat('a', 121), 'Nama maksimal 120 karakter.'],
            'array institution' => ['institution', ['invalid'], 'Instansi harus berupa teks.'],
            'long institution' => ['institution', str_repeat('a', 181), 'Instansi maksimal 180 karakter.'],
            'missing content' => ['content', null, 'Testimoni wajib diisi.'],
            'array content' => ['content', ['invalid'], 'Testimoni harus berupa teks.'],
            'short content' => ['content', '123456789', 'Testimoni minimal 10 karakter.'],
            'long content' => ['content', str_repeat('a', 2001), 'Testimoni maksimal 2000 karakter.'],
        ];
    }

    public function test_public_submission_rate_limit_blocks_the_fourth_request_without_saving_it(): void
    {
        $this->actingAs($this->buyer());
        $payload = ['name' => 'Peserta Baru', 'content' => 'Materi pembelajaran sangat membantu saya.'];

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post(route('testimonials.store'), $payload)->assertRedirect(route('home').'#testimoni');
        }

        $this->postJson(route('testimonials.store'), $payload)->assertTooManyRequests();
        $this->assertDatabaseCount('testimonials', 3);
    }

    #[DataProvider('publicPages')]
    public function test_public_pages_escape_every_testimonial_text_field(string $routeName): void
    {
        $testimonial = Testimonial::factory()->visible()->create([
            'name' => '<script>alert("name")</script>',
            'institution' => '<img src=x onerror=alert("institution")>',
            'content' => '<script>alert("content")</script>',
        ]);

        $response = $this->get(route($routeName));

        foreach (['name', 'institution', 'content'] as $field) {
            $response->assertSee($testimonial->$field)->assertDontSee($testimonial->$field, false);
        }
    }

    public static function publicPages(): array
    {
        return ['homepage' => ['home']];
    }

    public function test_admin_public_view_still_omits_hidden_testimonials(): void
    {
        $hidden = Testimonial::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('home', ['status' => 'hidden', 'is_visible' => 0]))
            ->assertDontSee($hidden->content)->assertViewHas('testimonials', fn ($testimonials): bool => $testimonials->isEmpty());
    }

    public function test_example_seeder_is_repeatable_and_keeps_example_content_hidden(): void
    {
        $this->seed(TestimonialSeeder::class);
        $this->seed(TestimonialSeeder::class);

        $this->assertDatabaseCount('testimonials', 7);
        $this->assertFalse(Testimonial::where('name', 'Contoh Peserta')->sole()->is_visible);
        $this->assertSame(6, Testimonial::query()->visible()->count());
    }

    public function test_old_public_url_redirects_to_the_landing_section(): void
    {
        $this->get(route('testimonials.index'))->assertRedirect(route('home').'#testimoni');
    }

    public function test_guest_cannot_submit_testimonials(): void
    {
        $this->post(route('testimonials.store'), [])->assertRedirect(route('login'));
        $this->assertDatabaseCount('testimonials', 0);
    }

    #[DataProvider('ineligiblePurchases')]
    public function test_non_purchasers_cannot_see_the_form_or_submit(string $status, int $amount): void
    {
        $user = User::factory()->create();
        Transaction::factory()->for($user)->create(['status' => $status, 'amount' => $amount]);
        $this->actingAs($user)->get(route('home'))->assertDontSee(route('testimonials.store'));
        $this->postJson(route('testimonials.store'), [
            'name' => $user->name, 'content' => 'Pengalaman belajar yang sangat membantu.',
        ])->assertForbidden();
        $this->assertDatabaseCount('testimonials', 0);
    }

    public static function ineligiblePurchases(): array
    {
        return [
            'pending' => ['pending', 150000], 'failed' => ['failed', 150000],
            'expired' => ['expired', 150000], 'refunded' => ['refunded', 150000],
            'partially refunded' => ['partially_refunded', 150000], 'free' => ['paid', 0],
        ];
    }

    public function test_granted_package_and_someone_elses_purchase_do_not_allow_submission(): void
    {
        $user = User::factory()->create();
        UserPackage::factory()->for($user)->create(['price' => 150000]);
        Transaction::factory()->create(['status' => TransactionStatus::Paid]);
        $this->actingAs($user)->postJson(route('testimonials.store'), [])->assertForbidden();
        $this->assertDatabaseCount('testimonials', 0);
    }

    public function test_past_purchaser_can_see_the_form_even_after_package_expiry(): void
    {
        $user = $this->buyer();
        UserPackage::factory()->for($user)->create(['starts_at' => now()->subMonths(2), 'expires_at' => now()->subMonth()]);
        $this->actingAs($user)->get(route('home'))->assertSee(route('testimonials.store'))->assertSee('Bagikan Pengalamanmu');
    }

    public function test_inactive_or_unverified_buyers_cannot_submit(): void
    {
        $user = $this->buyer();
        $user->update(['email_verified_at' => null]);
        $this->actingAs($user)->post(route('testimonials.store'), [])->assertRedirect(route('verification.notice'));
        $user->update(['email_verified_at' => now(), 'status' => 'inactive']);
        $this->actingAs($user)->post(route('testimonials.store'), [])->assertRedirect(route('login'));
        $this->assertDatabaseCount('testimonials', 0);
    }

    private function buyer(): User
    {
        $user = User::factory()->create(['name' => 'Peserta Baru']);
        Transaction::factory()->for($user)->create(['status' => TransactionStatus::Paid, 'paid_at' => now()]);

        return $user;
    }
}
