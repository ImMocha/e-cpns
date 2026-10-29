<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SessionCatalogSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([SessionQuestionSeeder::class, TryoutSessionSeeder::class, PracticeSessionSeeder::class]);
    }
}
