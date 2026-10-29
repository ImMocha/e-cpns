<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MaterialAccess;
use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Models\Material;
use App\Models\Section;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Materi contoh.
 *
 * Field `file_path` diisi dengan berkas PDF placeholder yang benar-benar ditulis
 * ke disk privat, supaya `migrate:fresh --seed` menghasilkan demo yang bisa
 * dibuka dari sisi peserta. Isinya cuma dokumen satu halaman bertuliskan
 * "placeholder" — diganti begitu admin mengunggah PDF asli.
 */
class MaterialSeeder extends Seeder
{
    /**
     * Penanda di dalam PDF placeholder.
     *
     * Dipakai untuk membedakan "file yang dibuat seeder" dari "file yang
     * diunggah admin". Kalau penanda ini hilang, berkasnya dianggap milik admin
     * dan tidak boleh ditimpa.
     */
    private const PLACEHOLDER_MARKER = 'PLACEHOLDER-LONTAR-v1';

    /** Batas keras halaman placeholder supaya berkas seed tidak membengkak. */
    private const MAX_PLACEHOLDER_PAGES = 200;

    public function run(): void
    {
        $this->call(TopicSeeder::class);
        $sections = Section::query()->ordered()->get()->keyBy('code');

        foreach (array_merge($this->definitions(), $this->additionalDefinitions()) as $definition) {
            $code = $definition['section_code'];
            $topicName = $definition['topic_name'] ?? 'Umum';
            unset($definition['section_code'], $definition['topic_name']);

            $section = $sections->get($code);

            if ($section === null) {
                continue;
            }

            $material = Material::withTrashed()->firstOrCreate(
                ['slug' => Str::slug($definition['title'])],
                array_merge($definition, [
                    'section_id' => $section->id,
                    'topic_id' => Topic::withTrashed()->firstOrCreate(['section_id' => $section->id, 'name' => $topicName])->id,
                    'created_by' => null,
                ]),
            );

            $this->ensurePlaceholderPdf($material);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function additionalDefinitions(): array
    {
        $definitions = [];

        foreach (TopicSeeder::catalog() as $topicIndex => $topic) {
            foreach ($topic['lesson_titles'] as $index => $title) {
                $isVideo = $index % 2 === 1;
                $isDraft = $index === 3 && $topicIndex % 3 === 2;
                $definitions[] = [
                    'section_code' => $topic['section_code'],
                    'topic_name' => $topic['name'],
                    'title' => $title,
                    'summary' => $isVideo
                        ? 'Video placeholder untuk menguji pemutar; bukan pembahasan CPNS asli. Durasi label adalah contoh.'
                        : 'PDF placeholder untuk menguji pembaca pada topik '.$topic['name'].'. Ganti dengan materi asli melalui admin.',
                    'type' => $isVideo ? MaterialType::Video->value : MaterialType::Pdf->value,
                    'access_level' => MaterialAccess::Premium->value,
                    'status' => $isDraft ? MaterialStatus::Draft->value : MaterialStatus::Published->value,
                    'file_path' => $isVideo ? null : 'materials/pdf/demo-'.Str::slug($title).'.pdf',
                    'file_size' => null,
                    'page_count' => $isVideo ? null : 8 + ($topicIndex % 6) * 4 + $index * 3,
                    'video_url' => $isVideo ? 'https://developer.mozilla.org/shared-assets/videos/flower.mp4' : null,
                    'duration_minutes' => $isVideo ? 6 + ($topicIndex % 8) * 3 + $index : null,
                    'position' => $index + 1,
                    'published_at' => $isDraft ? null : now()->subDays(2 + $topicIndex)->startOfDay(),
                ];
            }
        }

        return $definitions;
    }

    /**
     * Tulis PDF placeholder kalau berkasnya belum ada.
     *
     * Berkas milik admin tidak pernah ditimpa. `migrate:fresh` juga tidak
     * mengosongkan disk privat, jadi placeholder versi lama masih ada di sana;
     * karena itu file yanggeneratedarker di bawah di-regenerate, sedangkan
     * file unggahan asli dibiarkan utuh.
     */
    private function ensurePlaceholderPdf(Material $material): void
    {
        if ($material->type !== MaterialType::Pdf || $material->file_path === null) {
            return;
        }

        $disk = Storage::disk(Material::PDF_DISK);

        if ($disk->exists($material->file_path)) {
            $existing = (string) $disk->get($material->file_path);

            if (! str_contains($existing, self::PLACEHOLDER_MARKER)) {
                return;
            }
        } elseif (! $material->wasRecentlyCreated) {
            return;
        }

        $contents = $this->placeholderPdf($material->title, $this->placeholderPageCount($material));
        $disk->put($material->file_path, $contents);

        $material->forceFill(['file_size' => strlen($contents)])->saveQuietly();
    }

    /**
     * Berapa halaman yang digambar untuk placeholder.
     *
     * Mengikuti `page_count` supaya angka di header kartu dan isi dokumennya
     * konsisten — placeholder 60 halaman sementara header menulis "72 halaman" akan
     * terasa tidak sinkron. Kalau nilainya melebihi batas keras, `page_count`
     * ikut diturunkan supaya keduanya tetap cocok.
     */
    private function placeholderPageCount(Material $material): int
    {
        $declared = max(1, (int) ($material->page_count ?? 1));

        if ($declared <= self::MAX_PLACEHOLDER_PAGES) {
            return $declared;
        }

        $material->forceFill(['page_count' => self::MAX_PLACEHOLDER_PAGES])->saveQuietly();

        return self::MAX_PLACEHOLDER_PAGES;
    }

    /**
     * PDF sah dengan jumlah halaman tertentu supaya browser bisa membukanya.
     *
     * Objek disusun manual karena isi tiap halaman berbeda. Menarik pustaka PDF
     * hanya demi membuat berkas placeholder tidak sepadan dengan dependensinya.
     */
    private function placeholderPdf(string $title, int $pageCount): string
    {
        $pageCount = max(1, $pageCount);

        /*
         * Penomoran objek:
         *   1 = katalog, 2 = pohon halaman
         *   3,5,7,… = objek halaman ke-n      (2n+1)
         *   4,6,8,… = objek isi halaman ke-n    (2n+2)
         *   terakhir = font
         */
        $fontObject = (2 * $pageCount) + 3;

        $kids = implode(' ', array_map(
            static fn (int $number): string => (2 * $number + 1).' 0 R',
            range(1, $pageCount),
        ));

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            "<< /Type /Pages /Kids [{$kids}] /Count {$pageCount} >>",
        ];

        for ($page = 1; $page <= $pageCount; $page++) {
            $content = $this->placeholderPageContent($title, $page, $pageCount);

            $objects[] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] '
                .'/Resources << /Font << /F1 %d 0 R >> >> /Contents %d 0 R >>',
                $fontObject,
                2 * $page + 2,
            );

            $objects[] = '<< /Length '.strlen($content)." >>\nstream\n{$content}\nendstream";
        }

        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = "%PDF-1.4\n";

        foreach ($objects as $index => $body) {
            $pdf .= ($index + 1)." 0 obj\n{$body}\nendobj\n";
        }

        return $pdf.'trailer'.PHP_EOL
            .'<< /Root 1 0 R /Size '.count($objects).' >>'.PHP_EOL
            .'%%EOF';
    }

    /** Isi satu halaman placeholder. */
    private function placeholderPageContent(string $title, int $page, int $total): string
    {
        $lines = $page === 1
            ? [
                'Materi Contoh - Ganesha Nusa',
                '',
                $title,
                '',
                'Ini berkas placeholder. Admin belum mengunggah',
                'PDF asli untuk materi ini. Ganti lewat halaman',
                'Ubah Materi di Admin Panel.',
                '',
                'Halaman 1 dari '.$total,
                self::PLACEHOLDER_MARKER,
            ]
            : [
                'Placeholder - Ganesha Nusa',
                '',
                $title,
                '',
                'Halaman '.$page.' dari '.$total.' pada berkas placeholder.',
                self::PLACEHOLDER_MARKER,
            ];

        $content = "BT\n/F1 18 Tf\n72 760 Td\n";

        foreach ($lines as $line) {
            $content .= '('.self::escapePdfText($line).") Tj\n0 -26 Td\n";
        }

        return $content.'ET';
    }

    /**
     * Escape teks untuk font PDF standar.
     *
     * Font `/Helvetica` hanya bisa merender ASCII. Aksen ditulis manual di sini
     * supaya tidak bergantung pada `mb_convert_encoding('ASCII//TRANSLIT')` yang
     * tidak tersedia di semua build PHP.
     */
    private static function escapePdfText(string $text): string
    {
        $ascii = strtr($text, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n', 'ý' => 'y',
            '—' => '-', '–' => '-', '’' => "'", '‘' => "'",
            '“' => '"', '”' => '"', '…' => '...',
        ]);

        // Sisanya dibuang, bukan diganti '?', supaya teks placeholder tetap
        // terbaca seperti kalimat biasa.
        $ascii = preg_replace('/[^\x20-\x7E]/', '', $ascii) ?? '';
        $ascii = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $ascii);

        return mb_substr($ascii, 0, 78);
    }

    /** @return array<int, array<string, mixed>> */
    private function definitions(): array
    {
        return [
            [
                'section_code' => 'TWK',
                'title' => 'Rangkuman Pengetahuan Umum Indonesia',
                'summary' => 'Sejarah, Pancasila, konstitusi, dan geopolitik dalam 48 halaman ringkas.',
                'type' => MaterialType::Pdf->value,
                'access_level' => MaterialAccess::Premium->value,
                'status' => MaterialStatus::Published->value,
                'file_path' => 'materials/pdf/contoh-pengetahuan-umum.pdf',
                'file_size' => 2_516_582,
                'page_count' => 48,
                'position' => 1,
                'published_at' => '2026-09-28 09:00:00',
            ],
            [
                'section_code' => 'TWK',
                'title' => 'Sejarah Indonesia Modern: Proklamasi hingga Reformasi',
                'summary' => 'Timeline penting yang sering muncul dalam soal TWK.',
                'type' => MaterialType::Pdf->value,
                'access_level' => MaterialAccess::Premium->value,
                'status' => MaterialStatus::Published->value,
                'file_path' => 'materials/pdf/contoh-sejarah-modern.pdf',
                'file_size' => 4_194_304,
                'page_count' => 64,
                'position' => 2,
                'published_at' => '2026-09-21 09:00:00',
            ],
            [
                'section_code' => 'TIU',
                'title' => 'Modul Matematika Dasar untuk ASN',
                'summary' => 'Perbandingan, persentase, dan penalaran logis dengan latihan bertingkat.',
                'type' => MaterialType::Pdf->value,
                'access_level' => MaterialAccess::Premium->value,
                'status' => MaterialStatus::Published->value,
                'file_path' => 'materials/pdf/contoh-matematika-dasar.pdf',
                'file_size' => 5_033_164,
                'page_count' => 72,
                'position' => 1,
                'published_at' => '2026-09-18 09:00:00',
            ],
            [
                'section_code' => 'TIU',
                'title' => 'Teknik Membaca Tabel dan Grafik untuk Soal TIU',
                'summary' => 'Cara cepat membaca tabel dan grafik tanpa salah tafsir.',
                'type' => MaterialType::Video->value,
                'access_level' => MaterialAccess::Premium->value,
                'status' => MaterialStatus::Published->value,
                'video_url' => 'https://cdn.example.com/contoh-tabel-dan-grafik.mp4',
                'duration_minutes' => 24,
                'position' => 2,
                'published_at' => '2026-09-12 09:00:00',
            ],
            [
                'section_code' => 'TIU',
                'title' => 'Penalaran Silogisme: 12 Pola yang Sering Muncul',
                'summary' => 'Bedakan silogisme valid dan invalid, lengkap dengan latihan.',
                'type' => MaterialType::Pdf->value,
                'access_level' => MaterialAccess::Premium->value,
                'status' => MaterialStatus::Draft->value,
                'file_path' => 'materials/pdf/contoh-silogisme.pdf',
                'file_size' => 2_097_152,
                'page_count' => 40,
                'position' => 3,
                'published_at' => null,
            ],
            [
                'section_code' => 'TKP',
                'title' => 'Panduan Integritas dan Etika ASN',
                'summary' => 'Contoh perilaku integritas di tempat kerja beserta simulasi kasus TKP.',
                'type' => MaterialType::Pdf->value,
                'access_level' => MaterialAccess::Premium->value,
                'status' => MaterialStatus::Published->value,
                'file_path' => 'materials/pdf/contoh-integritas-asan.pdf',
                'file_size' => 1_152_921,
                'page_count' => 36,
                'position' => 1,
                'published_at' => '2026-09-05 09:00:00',
            ],
            [
                'section_code' => 'TKP',
                'title' => 'Mengenali Lima Skenario TKP',
                'summary' => 'Lima skenario integritas yang paling sering muncul di soal.',
                'type' => MaterialType::Video->value,
                'access_level' => MaterialAccess::Premium->value,
                'status' => MaterialStatus::Published->value,
                'video_url' => 'https://cdn.example.com/contoh-skenario-tkp.mp4',
                'duration_minutes' => 15,
                'position' => 2,
                'published_at' => '2026-08-30 09:00:00',
            ],
            [
                'section_code' => 'KEW',
                'title' => 'Pancasila dalam Kehidupan ASN',
                'summary' => 'Penerapan sila kelima pada keputusan sehari-hari.',
                'type' => MaterialType::Video->value,
                'access_level' => MaterialAccess::Premium->value,
                'status' => MaterialStatus::Draft->value,
                'video_url' => 'https://cdn.example.com/contoh-pancasila-asan.mp4',
                'duration_minutes' => 22,
                'position' => 1,
                'published_at' => null,
            ],
        ];
    }
}
