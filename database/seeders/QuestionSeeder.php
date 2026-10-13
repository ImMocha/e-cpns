<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $section = Section::query()->where('code', 'TIU')->first();
        if (! $section) {
            return;
        }
        $authorId = User::query()->where('role', 'admin')->value('id');
        $examples = [
            ['title' => 'Contoh: Pola Bilangan', 'body' => '<p>Perhatikan urutan bilangan: <strong>3, 6, 12, 24, …</strong></p><p>Bilangan berikutnya adalah …</p>',
                'options' => ['30', '36', '42', '48', '54'], 'correct_option' => 3,
                'explanation' => '<p>Setiap bilangan dikalikan <strong>2</strong>: 3 × 2 = 6, 6 × 2 = 12, dan 12 × 2 = 24. Maka 24 × 2 = <strong>48</strong>.</p>'],
            ['title' => 'Contoh: Perbandingan', 'body' => '<p>Perbandingan jumlah buku Rani dan Budi adalah <strong>2 : 3</strong>. Jika jumlah buku keduanya 40, berapa jumlah buku Budi?</p>',
                'options' => ['12', '16', '20', '24', '28'], 'correct_option' => 3,
                'explanation' => '<p>Total bagian = 2 + 3 = 5. Satu bagian = 40 ÷ 5 = 8. Buku Budi = 3 × 8 = <strong>24</strong>.</p>'],
            ['title' => 'Contoh: Penalaran Urutan', 'body' => '<p>Anton lebih tinggi daripada Budi. Budi lebih tinggi daripada Citra. Siapa yang paling rendah?</p>',
                'options' => ['Anton', 'Budi', 'Citra', 'Sama tinggi', 'Tidak dapat ditentukan'], 'correct_option' => 2,
                'explanation' => '<p>Urutannya adalah Anton, Budi, lalu Citra dari yang tertinggi. Jadi <strong>Citra</strong> paling rendah.</p>'],
        ];
        foreach ($examples as $example) {
            Question::query()->firstOrCreate(['section_id' => $section->id, 'title' => $example['title']], array_merge($example, [
                'difficulty' => QuestionDifficulty::Easy, 'status' => QuestionStatus::Published,
                'published_at' => now(), 'created_by' => $authorId, 'updated_by' => $authorId,
            ]));
        }
    }
}
