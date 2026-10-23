<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VoucherManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_replace(['code' => 'HEMAT20', 'type' => 'fixed', 'value' => 20000,
            'minimum_amount' => 0, 'usage_limit' => null, 'starts_on' => null, 'ends_on' => null,
            'package_id' => null, 'is_active' => true], $overrides);
    }

    public function test_admin_creates_edits_and_changes_voucher_status(): void
    {
        $this->freezeTime();
        $package = Package::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.vouchers.create'))->assertSee('Buat Voucher');
        $this->post(route('admin.vouchers.store'), $this->payload(['code' => ' hemat20 ', 'package_id' => $package->id,
            'starts_on' => today()->toDateString(), 'ends_on' => today()->addDays(2)->toDateString()]))
            ->assertRedirect(route('admin.vouchers.index'))->assertSessionHasNoErrors();
        $voucher = Voucher::sole();

        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'code' => 'HEMAT20', 'package_id' => $package->id, 'value' => 20000]);
        $this->get(route('admin.vouchers.index'))->assertSee('HEMAT20')->assertSee($package->name);
        $this->get(route('admin.vouchers.edit', $voucher))->assertSee('Ubah Voucher');
        $this->put(route('admin.vouchers.update', $voucher), $this->payload(['type' => 'percentage', 'value' => 25]))
            ->assertRedirect(route('admin.vouchers.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'type' => 'percentage', 'value' => 25, 'package_id' => null]);
        $this->patch(route('admin.vouchers.status', $voucher), ['is_active' => false])->assertRedirect(route('admin.vouchers.index'));
        $this->assertFalse($voucher->fresh()->is_active);
        $this->patch(route('admin.vouchers.status', $voucher), ['is_active' => true])->assertRedirect(route('admin.vouchers.index'));
        $this->assertTrue($voucher->fresh()->is_active);
    }

    /** @return array<string, array{string, string}> */
    public static function endpoints(): array
    {
        return ['index' => ['GET', 'index'], 'create' => ['GET', 'create'], 'store' => ['POST', 'store'],
            'edit' => ['GET', 'edit'], 'update' => ['PUT', 'update'], 'status' => ['PATCH', 'status']];
    }

    #[DataProvider('endpoints')]
    public function test_guests_and_members_cannot_manage_vouchers(string $method, string $action): void
    {
        $voucher = Voucher::factory()->create();
        $url = route('admin.vouchers.'.$action, $voucher);

        $this->call($method, $url, $this->payload())->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->call($method, $url, $this->payload())->assertForbidden();

        $this->assertDatabaseCount('vouchers', 1);
        $this->assertSame(20000, $voucher->fresh()->value);
        $this->assertTrue($voucher->fresh()->is_active);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidValues(): array
    {
        return [
            'missing code' => [['code' => ''], 'code'],
            'array code' => [['code' => ['bad']], 'code'],
            'invalid code' => [['code' => '<script>'], 'code'],
            'long code' => [['code' => str_repeat('A', 41)], 'code'],
            'invalid type' => [['type' => 'other'], 'type'],
            'zero discount' => [['value' => 0], 'value'],
            'fraction discount' => [['value' => 1.5], 'value'],
            'percentage over 100' => [['type' => 'percentage', 'value' => 101], 'value'],
            'nominal overflow' => [['value' => 4294967296], 'value'],
            'negative minimum' => [['minimum_amount' => -1], 'minimum_amount'],
            'zero quota' => [['usage_limit' => 0], 'usage_limit'],
            'bad start date' => [['starts_on' => 'tomorrow'], 'starts_on'],
            'bad end date' => [['ends_on' => 'not-date'], 'ends_on'],
            'backwards dates' => [['starts_on' => '2026-10-21', 'ends_on' => '2026-10-20'], 'ends_on'],
            'missing package' => [['package_id' => 999999], 'package_id'],
        ];
    }

    #[DataProvider('invalidValues')]
    public function test_rejects_invalid_voucher_without_saving(array $overrides, string $field): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.vouchers.store'), $this->payload($overrides))->assertInvalid([$field]);

        $this->assertDatabaseCount('vouchers', 0);
    }

    public function test_duplicate_codes_are_case_insensitive_and_deleted_packages_are_rejected(): void
    {
        Voucher::factory()->create(['code' => 'HEMAT20']);
        $package = Package::factory()->create();
        $package->delete();
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.vouchers.store'), $this->payload(['code' => 'hemat20']))
            ->assertInvalid(['code' => 'Kode voucher sudah digunakan.']);
        $this->post(route('admin.vouchers.store'), $this->payload(['code' => 'OTHER', 'package_id' => $package->id]))
            ->assertInvalid(['package_id']);

        $this->assertDatabaseCount('vouchers', 1);
    }

    public function test_quota_cannot_be_lowered_below_existing_reservations(): void
    {
        $voucher = Voucher::factory()->create(['code' => 'HEMAT20', 'usage_limit' => 3]);
        Transaction::factory()->count(2)->for($voucher)->create();
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route('admin.vouchers.update', $voucher), $this->payload(['usage_limit' => 1]))
            ->assertInvalid(['usage_limit' => 'Kuota tidak boleh lebih kecil']);
        $this->assertSame(3, $voucher->fresh()->usage_limit);
        $this->put(route('admin.vouchers.update', $voucher), $this->payload(['usage_limit' => 2]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $voucher->fresh()->usage_limit);
    }

    public function test_filters_statistics_and_statuses_use_saved_data(): void
    {
        $this->freezeTime();
        $active = Voucher::factory()->create(['code' => 'ACTIVE', 'usage_limit' => 1]);
        $inactive = Voucher::factory()->inactive()->create(['code' => 'INACTIVE']);
        $expired = Voucher::factory()->create(['code' => 'EXPIRED', 'ends_on' => today()->subDay()]);
        $scheduled = Voucher::factory()->create(['code' => 'SCHEDULED', 'starts_on' => today()->addDay()]);
        Transaction::factory()->for($active)->create(['status' => TransactionStatus::Paid, 'discount_amount' => 20000]);
        Transaction::factory()->for($inactive)->create(['discount_amount' => 10000]);
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.vouchers.index'))->assertSee('Kuota habis')
            ->assertViewHas('stats', ['total' => 4, 'paid' => 1, 'discount' => 20000, 'pending' => 1]);
        foreach (['active' => $active, 'inactive' => $inactive, 'expired' => $expired, 'scheduled' => $scheduled] as $state => $voucher) {
            $this->get(route('admin.vouchers.index', ['state' => $state]))
                ->assertViewHas('vouchers', fn ($rows): bool => $rows->modelKeys() === [$voucher->id]);
        }
        $this->get(route('admin.vouchers.index', ['q' => 'EXPIRED']))
            ->assertViewHas('vouchers', fn ($rows): bool => $rows->modelKeys() === [$expired->id]);
        $this->get(route('admin.vouchers.index', ['q' => "' OR 1=1 --"]))->assertSee('Voucher tidak ditemukan');
    }
}
