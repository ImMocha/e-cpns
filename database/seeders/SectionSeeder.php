<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ColorTone;
use App\Models\Section;
use Illuminate\Database\Seeder;

/**
 * Bidang dasar CPNS plus beberapa bidang tambahan, untuk menunjukkan bahwa
 * daftarnya boleh diperluas — bukan enum tertutup.
 */
class SectionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->definitions() as $definition) {
            Section::updateOrCreate(
                ['code' => $definition['code']],
                array_merge($definition, ['slug' => Section::makeSlug($definition['name'])]),
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function definitions(): array
    {
        return [
            [
                'name' => 'TWK', 'code' => 'TWK', 'position' => 1,
                'description' => 'Pengetahuan Umum: sejarah, geografi, bahasa, ekonomi, dan ilmu pengetahuan alam.',
                'icon' => 'book', 'tone' => ColorTone::Primary->value,
            ],
            [
                'name' => 'TIU', 'code' => 'TIU', 'position' => 2,
                'description' => 'Intelektual Umum: matematika, logika, penalaran verbal dan analitis.',
                'icon' => 'chart', 'tone' => ColorTone::Pink->value,
            ],
            [
                'name' => 'TKP', 'code' => 'TKP', 'position' => 3,
                'description' => 'Karakteristik dan Potensi: integritas, kerja sama, dan orientasi layanan.',
                'icon' => 'shield', 'tone' => ColorTone::Amber->value,
            ],
            [
                'name' => 'Umum', 'code' => 'UMUM', 'position' => 4,
                'description' => 'Konten lintas bidang, misalnya tips belajar dan pengumuman.',
                'icon' => 'folder', 'tone' => ColorTone::Ink->value,
            ],
            [
                'name' => 'Bahasa Indonesia', 'code' => 'BIN', 'position' => 5,
                'description' => 'Tata bahasa, pemahaman baca, dan penulisan.',
                'icon' => 'pen-line', 'tone' => ColorTone::Mint->value,
            ],
            [
                'name' => 'Hukum Indonesia', 'code' => 'HKM', 'position' => 6,
                'description' => 'Kitab Undang-undang, hukum tata negara, dan hukum privasi.',
                'icon' => 'clipboard', 'tone' => ColorTone::Violet->value,
            ],
            [
                'name' => 'Kewarganegaraan', 'code' => 'KEW', 'position' => 7,
                'description' => 'Pancasila, UUD 1945, dan kebangsaan.',
                'icon' => 'crown', 'tone' => ColorTone::Coral->value,
            ],
        ];
    }
}
