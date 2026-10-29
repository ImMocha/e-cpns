<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    #[DataProvider('errorPages')]
    public function test_http_errors_render_branded_pages_without_database_or_bundled_assets(int $status, string $message): void
    {
        config(['app.debug' => false]);
        Route::get('/_tests/error', static fn () => abort($status, 'Sensitive exception details'));

        $this->get('/_tests/error')->assertStatus($status)
            ->assertSee($message)->assertSee('Ganesha Nusa')->assertSee('Hubungi Kami')
            ->assertSee('noindex, nofollow', false)
            ->assertDontSee('Sensitive exception details')->assertDontSee('/build/assets/', false)
            ->assertDontSee('resources/js/app.js', false);
    }

    /** @return array<string, array{int, string}> */
    public static function errorPages(): array
    {
        return [
            'unauthenticated' => [401, 'Masuk dulu, yuk.'],
            'forbidden' => [403, 'Halaman ini punya akses khusus.'],
            'not found' => [404, 'Sepertinya kamu tersesat.'],
            'method not allowed' => [405, 'Jalur ini belum bisa dilalui.'],
            'expired session' => [419, 'Sesi kamu perlu disegarkan.'],
            'rate limited' => [429, 'Pelan-pelan, beri jeda sebentar.'],
            'server error' => [500, 'Ada kendala di server.'],
            'bad gateway' => [502, 'Sambungan layanan sedang terganggu.'],
            'maintenance' => [503, 'Kami sedang berbenah.'],
            'timeout' => [504, 'Responsnya lebih lama dari biasanya.'],
            'client fallback' => [418, 'Ada yang perlu diperiksa.'],
            'server fallback' => [501, 'Layanan sedang terkendala.'],
        ];
    }

    public function test_unknown_urls_use_the_custom_not_found_page(): void
    {
        $this->get('/halaman-yang-tidak-ada')->assertNotFound()->assertSee('Sepertinya kamu tersesat.');
    }

    public function test_unhandled_server_errors_keep_details_private_and_are_reported(): void
    {
        config(['app.debug' => false]);
        Exceptions::fake();
        Route::get('/_tests/server-error', static fn () => throw new RuntimeException('Private database credentials'));

        $this->get('/_tests/server-error')->assertInternalServerError()
            ->assertSee('Ada kendala di server.')->assertDontSee('Private database credentials');

        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_json_errors_keep_the_json_response_contract(): void
    {
        config(['app.debug' => false]);
        Exceptions::fake();
        Route::get('/_tests/json-error', static fn () => throw new RuntimeException('Private server details'));

        $this->getJson('/_tests/json-error')->assertInternalServerError()
            ->assertJsonPath('message', 'Server Error')->assertDontSee('<!DOCTYPE html>', false)
            ->assertDontSee('Private server details');

        Exceptions::assertReported(RuntimeException::class);
    }
}
