<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Peserta contoh untuk menguji daftar, pencarian, filter, dan pagination.
 *
 * Satu akun dibuat eksplisit (`peserta@lontar.asia`) supaya area peserta bisa
 * diuji tanpa mencari email acak. 34 sisanya tetap acak.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }
        User::factory()->create([
            'name' => 'Rani Kusuma',
            'email' => 'peserta@lontar.asia',
            'institution' => 'Dinas Pendidikan Kota Surabaya',
        ]);

        User::factory()->count(27)->create();
        User::factory()->count(4)->inactive()->create();
        User::factory()->count(3)->neverLoggedIn()->create();
    }
}
