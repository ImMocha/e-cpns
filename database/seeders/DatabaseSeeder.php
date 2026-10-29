<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->call([SectionSeeder::class, PremiumRf05Seeder::class, LearningPackageSeeder::class]);

            return;
        }

        $this->call([
            AdminUserSeeder::class,
            UserSeeder::class,
            LeaderboardBotSeeder::class,
            SectionSeeder::class,
            MaterialSeeder::class,
            ArticleSeeder::class,
            QuestionSeeder::class,
            ExamSeeder::class,
            TopicQuizSeeder::class,
            PremiumRf05Seeder::class,
            SessionCatalogSeeder::class,
            LearningPackageSeeder::class,
        ]);
    }
}
