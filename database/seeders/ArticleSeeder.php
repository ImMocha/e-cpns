<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Section;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $section = Section::query()->active()->ordered()->first();
        if ($section === null) {
            return;
        }

        Article::firstOrCreate(['slug' => 'mulai-belajar-dengan-rencana'], [
            'section_id' => $section->id,
            'title' => 'Mulai Belajar dengan Rencana',
            'excerpt' => 'Artikel contoh untuk mengenal editor dan menyusun kebiasaan belajar.',
            'body' => '<h2>Tentukan target belajar</h2><p>Mulailah dengan <strong>target yang realistis</strong> dan waktu belajar yang konsisten.</p><blockquote>Perkembangan kecil yang dilakukan setiap hari akan membantu membangun kebiasaan.</blockquote><h3>Langkah pertama</h3><ol><li>Pilih satu bidang untuk dipelajari.</li><li>Baca materi dan catat konsep penting.</li><li>Kerjakan latihan dan pelajari pembahasannya.</li></ol>',
            'status' => ArticleStatus::Draft,
        ]);
    }
}
