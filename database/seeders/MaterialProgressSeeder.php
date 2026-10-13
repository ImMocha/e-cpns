<?php

namespace Database\Seeders;

use App\Enums\MaterialAccess;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\User;
use Illuminate\Database\Seeder;

class MaterialProgressSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'peserta@lontar.asia')->first();

        if ($user === null) {
            return;
        }

        $materials = Material::query()->visibleToMembers()
            ->where('access_level', MaterialAccess::Free->value)
            ->orderBy('id')->limit(2)->get()
            ->filter(fn (Material $material): bool => $material->hasAvailableContent())
            ->values();

        foreach ($materials as $index => $material) {
            MaterialProgress::query()->firstOrCreate(
                ['user_id' => $user->id, 'material_id' => $material->id],
                ['last_viewed_at' => now()->subDays(2 - $index), 'completed_at' => $index === 0 ? now()->subDay() : null],
            );
        }
    }
}
