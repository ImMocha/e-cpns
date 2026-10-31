<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class QuestionImageSeeder extends Seeder
{
    public function run(): void
    {
        $section = Section::query()->where('code', 'TIU')->first();
        if ($section === null) {
            $this->command?->warn('Soal bergambar belum dibuat. Jalankan SectionSeeder untuk menyiapkan bidang TIU terlebih dahulu.');

            return;
        }

        $directory = database_path('seeders/data/question-images');
        $authorId = User::query()->where('role', 'admin')->value('id');
        $examples = [
            ['source_key' => 'example-image-square', 'title' => 'Contoh: Mengenali Persegi dari Gambar',
                'body' => '<p>Pilih gambar bangun datar yang memiliki <strong>empat sisi sama panjang</strong> dan <strong>empat sudut siku-siku</strong>.</p>',
                'options' => ['', '', '', '', ''], 'correct_option' => 2,
                'explanation' => '<p>Bangun yang dimaksud adalah <strong>persegi</strong>. Keempat sisinya sama panjang dan setiap sudutnya 90 derajat. Lingkaran tidak memiliki sisi lurus, sedangkan bangun lain pada pilihan memiliki tiga, lima, atau enam sisi.</p>',
                'explanation_image_path' => 'questions/images/examples/example-image-square/square-explanation.png'],
            ['source_key' => 'example-image-pentagon', 'title' => 'Contoh: Menghitung Sisi Bangun Bergambar',
                'body' => '<p>Perhatikan bentuk pada setiap pilihan. Bangun manakah yang memiliki <strong>tepat lima sisi lurus</strong>?</p>',
                'options' => ['Lingkaran', 'Segitiga', 'Persegi', 'Segi lima', 'Segi enam'], 'correct_option' => 3,
                'explanation' => '<p><strong>Segi lima</strong> memiliki lima sisi lurus. Telusuri garis tepinya satu per satu sesuai nomor pada gambar pembahasan. Segitiga memiliki tiga sisi, persegi empat sisi, dan segi enam enam sisi.</p>',
                'explanation_image_path' => 'questions/images/examples/example-image-pentagon/pentagon-explanation.png'],
        ];

        foreach ($examples as $example) {
            $optionPaths = array_map(fn (string $shape): string => 'questions/images/examples/'.$example['source_key'].'/'.$shape.'.png',
                ['circle', 'triangle', 'square', 'pentagon', 'hexagon']);
            DB::transaction(function () use ($example, $section, $authorId, $optionPaths, $directory): void {
                $question = Question::withTrashed()->firstOrCreate(['source_key' => $example['source_key']], array_merge($example, [
                    'section_id' => $section->id, 'option_image_paths' => $optionPaths,
                    'difficulty' => QuestionDifficulty::Easy, 'status' => QuestionStatus::Published,
                    'shuffle_options' => true, 'published_at' => now(), 'created_by' => $authorId, 'updated_by' => $authorId,
                ]));

                if (! $question->wasRecentlyCreated) {
                    return;
                }

                foreach ([...$optionPaths, $example['explanation_image_path']] as $path) {
                    if (Storage::disk('local')->exists($path)) {
                        continue;
                    }

                    if (! Storage::disk('local')->put($path, file_get_contents($directory.'/'.basename($path)))) {
                        throw new RuntimeException('Gambar contoh soal gagal disimpan.');
                    }
                }
            });
        }
    }
}
