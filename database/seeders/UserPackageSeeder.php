<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Database\Seeder;

class UserPackageSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }
        $this->call(PackageSeeder::class);
        $package = Package::query()->where('name', PackageSeeder::SANDBOX_PACKAGE_NAME)->firstOrFail();
        $user = User::query()->where('email', 'peserta@lontar.asia')->first();
        if ($user === null) {
            return;
        }
        $grant = UserPackage::firstOrCreate(['user_id' => $user->id, 'package_id' => $package->id], [
            'name' => $package->name, 'price' => $package->price, 'tryout_quota' => $package->tryout_quota,
            'tryouts_used' => 0, 'starts_at' => now()->subDays(31), 'expires_at' => now()->subDay(),
            'note' => 'Contoh riwayat paket kedaluwarsa untuk pengembangan lokal.',
        ]);
        $grant->materials()->syncWithoutDetaching($package->materials()->pluck('materials.id'));
        $grant->exams()->syncWithoutDetaching($package->exams()->pluck('exams.id'));
    }
}
