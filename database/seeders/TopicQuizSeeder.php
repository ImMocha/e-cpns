<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MaterialAccess;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Section;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TopicQuizSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = User::query()->where('role', 'admin')->value('id');

        foreach ($this->definitions() as $definition) {
            $section = Section::query()->where('code', $definition['section_code'])->first();
            if ($section === null) {
                continue;
            }

            $topic = Topic::query()->whereBelongsTo($section)->where('name', $definition['topic_name'])->first();
            if ($topic === null || Exam::withTrashed()->where('slug', $definition['slug'])->exists()) {
                continue;
            }

            DB::transaction(function () use ($authorId, $definition, $section, $topic): void {
                $questionIds = [];

                foreach ($definition['questions'] as $questionDefinition) {
                    $question = Question::withTrashed()->firstOrCreate(
                        ['section_id' => $section->id, 'title' => $questionDefinition['title']],
                        $questionDefinition + [
                            'difficulty' => QuestionDifficulty::Easy,
                            'status' => QuestionStatus::Published,
                            'published_at' => now(),
                            'created_by' => $authorId,
                            'updated_by' => $authorId,
                        ],
                    );

                    if ($question->trashed() || $question->status !== QuestionStatus::Published
                        || $question->published_at === null || $question->published_at->isFuture()) {
                        return;
                    }

                    $questionIds[] = $question->id;
                }

                $exam = Exam::withTrashed()->firstOrCreate(
                    ['slug' => $definition['slug']],
                    [
                        'kind' => 'quiz',
                        'title' => $definition['title'],
                        'description' => 'Soal contoh untuk topik '.$topic->name.'. Admin dapat mengubah soal dan pengaturan latihan.',
                        'topic_id' => $topic->id,
                        'requires_topic_completion' => $definition['requires_topic_completion'],
                        'access_level' => MaterialAccess::Premium,
                        'status' => QuestionStatus::Published,
                        'question_count' => count($questionIds),
                        'duration_minutes' => 0,
                        'points_per_correct' => 5,
                        'penalty_wrong' => 0,
                        'random_questions' => true,
                        'random_options' => true,
                        'created_by' => $authorId,
                        'updated_by' => $authorId,
                    ],
                );

                if ($exam->wasRecentlyCreated) {
                    $exam->questions()->sync($questionIds);
                }
            });
        }
    }

    /** @return list<array{section_code: string, topic_name: string, slug: string, title: string, requires_topic_completion: bool, questions: list<array{title: string, body: string, options: list<string>, correct_option: int, explanation: string}>}> */
    private function definitions(): array
    {
        return [
            [
                'section_code' => 'TWK',
                'topic_name' => 'Pancasila dan Ideologi Negara',
                'slug' => 'contoh-latihan-topik-pancasila',
                'title' => 'Contoh: Latihan Pancasila dan Ideologi Negara',
                'requires_topic_completion' => true,
                'questions' => [
                    [
                        'title' => 'Contoh Topik: Kerja Sama dalam Keberagaman',
                        'body' => '<p>Warga dari berbagai latar belakang bekerja sama membersihkan lingkungan. Sikap ini paling sesuai dengan sila Pancasila yang mana?</p>',
                        'options' => ['Ketuhanan Yang Maha Esa', 'Kemanusiaan yang Adil dan Beradab', 'Persatuan Indonesia', 'Kerakyatan yang Dipimpin oleh Hikmat Kebijaksanaan dalam Permusyawaratan/Perwakilan', 'Keadilan Sosial bagi Seluruh Rakyat Indonesia'],
                        'correct_option' => 2,
                        'explanation' => '<p>Kerja sama lintas latar belakang menjaga persatuan, sesuai sila ketiga: <strong>Persatuan Indonesia</strong>.</p>',
                    ],
                    [
                        'title' => 'Contoh Topik: Musyawarah Warga',
                        'body' => '<p>Warga mendengarkan pendapat semua pihak sebelum mengambil keputusan bersama. Nilai Pancasila yang paling tampak adalah ...</p>',
                        'options' => ['Persatuan', 'Ketuhanan', 'Keadilan sosial', 'Permusyawaratan', 'Kemanusiaan'],
                        'correct_option' => 3,
                        'explanation' => '<p>Mendengar pendapat dan mencari keputusan bersama mencerminkan sila keempat, yaitu <strong>permusyawaratan</strong>.</p>',
                    ],
                    [
                        'title' => 'Contoh Topik: Menghormati Ibadah',
                        'body' => '<p>Memberi kesempatan kepada tetangga untuk beribadah menurut keyakinannya merupakan penerapan sila ...</p>',
                        'options' => ['Pertama', 'Kedua', 'Ketiga', 'Keempat', 'Kelima'],
                        'correct_option' => 0,
                        'explanation' => '<p>Menghormati keyakinan dan ibadah orang lain mencerminkan sila pertama, <strong>Ketuhanan Yang Maha Esa</strong>.</p>',
                    ],
                ],
            ],
            [
                'section_code' => 'TIU',
                'topic_name' => 'Kemampuan Numerik',
                'slug' => 'contoh-latihan-topik-numerik',
                'title' => 'Contoh: Latihan Kemampuan Numerik',
                'requires_topic_completion' => false,
                'questions' => [
                    [
                        'title' => 'Contoh Topik: Deret Geometri',
                        'body' => '<p>Tentukan bilangan berikutnya dalam deret <strong>4, 8, 16, 32, ...</strong></p>',
                        'options' => ['36', '40', '48', '64', '72'],
                        'correct_option' => 3,
                        'explanation' => '<p>Setiap suku dikalikan 2. Jadi 32 × 2 = <strong>64</strong>.</p>',
                    ],
                    [
                        'title' => 'Contoh Topik: Perbandingan Buku',
                        'body' => '<p>Buku Rani dan Budi berbanding <strong>2 : 3</strong>. Jumlah seluruh buku mereka 40. Berapa buku Budi?</p>',
                        'options' => ['12', '16', '20', '24', '30'],
                        'correct_option' => 3,
                        'explanation' => '<p>Total bagian 2 + 3 = 5. Buku Budi = 3/5 × 40 = <strong>24</strong>.</p>',
                    ],
                    [
                        'title' => 'Contoh Topik: Persentase Dasar',
                        'body' => '<p>Berapakah <strong>25%</strong> dari <strong>80</strong>?</p>',
                        'options' => ['10', '15', '20', '25', '30'],
                        'correct_option' => 2,
                        'explanation' => '<p>25% = 1/4, sehingga 1/4 × 80 = <strong>20</strong>.</p>',
                    ],
                ],
            ],
            [
                'section_code' => 'TKP',
                'topic_name' => 'Pelayanan Publik',
                'slug' => 'contoh-latihan-topik-pelayanan-publik',
                'title' => 'Contoh: Latihan Pelayanan Publik',
                'requires_topic_completion' => true,
                'questions' => [
                    [
                        'title' => 'Contoh Topik: Keluhan Berkas Warga',
                        'body' => '<p>Seorang warga mengeluh karena berkas permohonannya belum lengkap. Apa langkah pelayanan yang paling tepat?</p>',
                        'options' => ['Mengabaikan keluhan karena persyaratan sudah tertulis', 'Meminta warga kembali lain hari tanpa penjelasan', 'Mendengarkan keluhan, menjelaskan berkas yang kurang, dan membantu warga memahami langkah berikutnya', 'Meloloskan permohonan tanpa berkas yang dipersyaratkan', 'Mengalihkan warga tanpa mencatat keluhannya'],
                        'correct_option' => 2,
                        'explanation' => '<p>Petugas perlu mendengarkan keluhan dan memberi arahan yang jelas sambil tetap mengikuti persyaratan layanan.</p>',
                    ],
                    [
                        'title' => 'Contoh Topik: Gangguan Sistem Layanan',
                        'body' => '<p>Sistem layanan sedang bermasalah saat warga menunggu. Tindakan yang paling membantu adalah ...</p>',
                        'options' => ['Membiarkan warga menunggu tanpa kabar', 'Menutup loket tanpa penjelasan', 'Meminta semua warga mencari informasi sendiri', 'Menjelaskan kendala, menyampaikan perkiraan tindak lanjut, dan memakai prosedur alternatif yang tersedia', 'Menjanjikan selesai segera tanpa mengetahui kondisi sistem'],
                        'correct_option' => 3,
                        'explanation' => '<p>Informasi yang jujur dan langkah alternatif sesuai prosedur membantu warga menentukan tindakan berikutnya.</p>',
                    ],
                    [
                        'title' => 'Contoh Topik: Perbedaan Pendapat Petugas',
                        'body' => '<p>Dua petugas berbeda pendapat tentang urutan penanganan permohonan. Apa langkah yang paling tepat?</p>',
                        'options' => ['Memilih berdasarkan kedekatan pribadi', 'Mendiskusikan urutan secara profesional dan merujuk prosedur layanan', 'Menunda semua permohonan hingga akhir hari', 'Meminta warga memutuskan tanpa penjelasan', 'Mengutamakan warga yang paling keras menyampaikan protes'],
                        'correct_option' => 1,
                        'explanation' => '<p>Koordinasi yang baik dan rujukan pada prosedur membuat layanan tetap adil dan konsisten.</p>',
                    ],
                ],
            ],
        ];
    }
}
