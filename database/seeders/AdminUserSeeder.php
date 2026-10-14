<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun administrator untuk masuk ke Admin Panel.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@lontar.asia'],
            [
                'name' => 'Bagas Pratama',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
                'phone' => '0812 3456 7890',
                'address' => 'Jakarta Selatan, DKI Jakarta',
                'institution' => 'Ganesha Nusa',
                'last_login_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'editor@lontar.asia'],
            [
                'name' => 'Sari Wulandari',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'status' => UserStatus::Inactive,
                'email_verified_at' => now(),
                'phone' => '0813 2211 9087',
                'address' => 'Bandung, Jawa Barat',
                'institution' => 'Ganesha Nusa',
                'last_login_at' => null,
            ],
        );
    }
}
