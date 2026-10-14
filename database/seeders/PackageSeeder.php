<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MaterialAccess;
use App\Enums\QuestionStatus;
use App\Models\Exam;
use App\Models\Material;
use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public const SANDBOX_PACKAGE_NAME = 'Contoh: Paket Uji Pembayaran Sandbox';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || config('services.midtrans.is_production')) {
            return;
        }

        $materialIds = Material::query()->visibleToMembers()
            ->where('access_level', MaterialAccess::Premium)
            ->orderBy('materials.id')->limit(3)->pluck('materials.id');

        $sourceTryout = Exam::query()->published()->where('kind', 'tryout')
            ->where('slug', 'simulasi-bank-soal')->first();
        $questionIds = $sourceTryout?->questions()->visibleToMembers()
            ->orderBy('questions.id')->limit(3)->pluck('questions.id');

        if ($materialIds->count() < 3 || $questionIds === null || $questionIds->isEmpty()) {
            $this->command?->warn('Paket uji belum dibuat. Jalankan seeder materi, soal, dan sesi contoh terlebih dahulu.');

            return;
        }

        $premiumTryout = Exam::withTrashed()->firstOrCreate(
            ['slug' => 'contoh-tryout-premium-paket-uji'],
            [
                'kind' => 'tryout',
                'title' => 'Contoh: Tryout Premium Paket Uji',
                'description' => 'Simulasi contoh untuk menguji pembelian paket dan kuota tryout.',
                'status' => QuestionStatus::Published,
                'access_level' => MaterialAccess::Premium,
                'question_count' => $questionIds->count(),
                'duration_minutes' => 10,
                'points_per_correct' => 5,
                'penalty_wrong' => 0,
                'random_questions' => true,
                'random_options' => true,
            ],
        );

        if ($premiumTryout->wasRecentlyCreated) {
            $premiumTryout->questions()->sync($questionIds);
        }

        if ($premiumTryout->trashed()
            || $premiumTryout->status !== QuestionStatus::Published
            || $premiumTryout->access_level !== MaterialAccess::Premium
            || $premiumTryout->questions()->visibleToMembers()->count() < $premiumTryout->question_count) {
            $this->command?->warn('Tryout premium contoh tidak tersedia; paket uji belum dibuat.');

            return;
        }

        $package = Package::withTrashed()->firstOrCreate(
            ['name' => self::SANDBOX_PACKAGE_NAME],
            [
                'description' => 'Paket contoh untuk mencoba checkout Midtrans Sandbox. Berisi 3 materi premium dan 1 tryout premium.',
                'price' => 10000,
                'duration_days' => 30,
                'tryout_quota' => 2,
                'is_active' => true,
                'position' => 0,
            ],
        );

        if ($package->wasRecentlyCreated) {
            $package->materials()->sync($materialIds);
            $package->exams()->sync([$premiumTryout->id]);
        }
    }
}
