<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Section;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    public function run(): void
    {
        $sections = Section::query()->get()->keyBy('code');

        foreach (self::catalog() as $definition) {
            $section = $sections->get($definition['section_code']);
            if ($section === null) {
                continue;
            }

            Topic::withTrashed()->firstOrCreate(
                ['section_id' => $section->id, 'name' => $definition['name']],
                [
                    'description' => $definition['description'],
                    'position' => $definition['position'],
                    'is_active' => $definition['is_active'],
                ],
            );
        }
    }

    /**
     * Satu katalog demo untuk topik dan judul materi; bukan konten pembelajaran asli.
     *
     * @return array<int, array{section_code:string, name:string, position:int, is_active:bool, description:string, lesson_titles:array<int, string>}>
     */
    public static function catalog(): array
    {
        return [
            ['section_code' => 'TWK', 'name' => 'Pancasila dan Ideologi Negara', 'position' => 1, 'is_active' => true,
                'description' => 'Materi contoh pancasila dan ideologi negara untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Sejarah Perumusan Pancasila', 'Memahami Nilai Setiap Sila', 'Latihan Penerapan Pancasila', 'Pembahasan Kasus Ideologi Negara']],
            ['section_code' => 'TWK', 'name' => 'Konstitusi dan Sistem Pemerintahan', 'position' => 2, 'is_active' => true,
                'description' => 'Materi contoh konstitusi dan sistem pemerintahan untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Pengantar UUD 1945 dan Amandemen', 'Lembaga Negara dan Kewenangannya', 'Latihan Hubungan Antarlembaga', 'Pembahasan Sistem Pemerintahan']],
            ['section_code' => 'TWK', 'name' => 'Nasionalisme dan Bela Negara', 'position' => 3, 'is_active' => true,
                'description' => 'Materi contoh nasionalisme dan bela negara untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Rangkuman Wawasan Kebangsaan', 'Nasionalisme dalam Pelayanan Publik', 'Latihan Ancaman dan Ketahanan Nasional', 'Pembahasan Studi Kasus Bela Negara']],
            ['section_code' => 'TIU', 'name' => 'Kemampuan Verbal', 'position' => 1, 'is_active' => true,
                'description' => 'Materi contoh kemampuan verbal untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Daftar Sinonim dan Antonim Pilihan', 'Teknik Menyelesaikan Analogi Kata', 'Latihan Pemahaman Bacaan TIU', 'Pembahasan Penalaran Verbal']],
            ['section_code' => 'TIU', 'name' => 'Kemampuan Numerik', 'position' => 2, 'is_active' => true,
                'description' => 'Materi contoh kemampuan numerik untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Rumus Persentase dan Perbandingan', 'Pola Deret Angka dan Huruf', 'Latihan Aritmetika dan Soal Cerita', 'Pembahasan Hitung Cepat TIU']],
            ['section_code' => 'TIU', 'name' => 'Penalaran Logis dan Figural', 'position' => 3, 'is_active' => true,
                'description' => 'Materi contoh penalaran logis dan figural untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Dasar Silogisme dan Kesimpulan', 'Mengenali Pola Gambar dan Rotasi', 'Latihan Penalaran Analitis', 'Pembahasan Soal Figural Bertingkat']],
            ['section_code' => 'TKP', 'name' => 'Pelayanan Publik', 'position' => 1, 'is_active' => true,
                'description' => 'Materi contoh pelayanan publik untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Prinsip Pelayanan Prima ASN', 'Menangani Keluhan Masyarakat', 'Latihan Orientasi Pelayanan', 'Pembahasan Prioritas Pelayanan Publik']],
            ['section_code' => 'TKP', 'name' => 'Integritas dan Profesionalisme', 'position' => 2, 'is_active' => true,
                'description' => 'Materi contoh integritas dan profesionalisme untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Nilai Integritas dalam Pekerjaan', 'Menghadapi Konflik Kepentingan', 'Latihan Sikap Profesional ASN', 'Pembahasan Dilema Etika di Kantor']],
            ['section_code' => 'TKP', 'name' => 'Jejaring Kerja dan Adaptasi', 'position' => 3, 'is_active' => true,
                'description' => 'Materi contoh jejaring kerja dan adaptasi untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Dasar Kolaborasi Lintas Tim', 'Adaptasi terhadap Perubahan Kerja', 'Latihan Komunikasi dan Kerja Sama', 'Pembahasan Konflik dalam Tim']],
            ['section_code' => 'UMUM', 'name' => 'Persiapan Seleksi CPNS', 'position' => 1, 'is_active' => true,
                'description' => 'Materi contoh persiapan seleksi cpns untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Checklist Persiapan Seleksi CPNS', 'Menyusun Jadwal Belajar Mingguan', 'Template Evaluasi Hasil Latihan', 'Strategi Belajar untuk Pekerja']],
            ['section_code' => 'UMUM', 'name' => 'Strategi Mengerjakan CAT', 'position' => 2, 'is_active' => true,
                'description' => 'Materi contoh strategi mengerjakan cat untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Panduan Antarmuka Simulasi CAT', 'Manajemen Waktu saat Tryout', 'Lembar Analisis Kesalahan Tryout', 'Teknik Memilih Soal Prioritas']],
            ['section_code' => 'UMUM', 'name' => 'Kebiasaan Belajar Efektif', 'position' => 3, 'is_active' => true,
                'description' => 'Materi contoh kebiasaan belajar efektif untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Panduan Catatan dan Active Recall', 'Membangun Rutinitas Belajar Konsisten', 'Template Target Belajar Bulanan', 'Mengatasi Gugup Menjelang Ujian']],
            ['section_code' => 'BIN', 'name' => 'Ejaan dan Tata Bahasa', 'position' => 1, 'is_active' => true,
                'description' => 'Materi contoh ejaan dan tata bahasa untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Ringkasan Ejaan Bahasa Indonesia', 'Penulisan Kata dan Tanda Baca', 'Latihan Kalimat Efektif', 'Pembahasan Kesalahan Tata Bahasa']],
            ['section_code' => 'BIN', 'name' => 'Pemahaman Bacaan', 'position' => 2, 'is_active' => true,
                'description' => 'Materi contoh pemahaman bacaan untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Menentukan Ide Pokok Paragraf', 'Teknik Menemukan Informasi Tersirat', 'Latihan Simpulan dan Makna Kata', 'Pembahasan Bacaan Panjang']],
            ['section_code' => 'BIN', 'name' => 'Penalaran Bahasa', 'position' => 3, 'is_active' => true,
                'description' => 'Materi contoh penalaran bahasa untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Hubungan Makna Antarparagraf', 'Memahami Fakta dan Opini', 'Latihan Penyusunan Paragraf', 'Pembahasan Penalaran Bahasa']],
            ['section_code' => 'HKM', 'name' => 'Dasar Hukum Tata Negara', 'position' => 1, 'is_active' => true,
                'description' => 'Materi contoh dasar hukum tata negara untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Peta Konsep Hukum Tata Negara', 'Hierarki Peraturan dalam Soal Seleksi', 'Latihan Prinsip Negara Hukum', 'Pembahasan Kewenangan Lembaga']],
            ['section_code' => 'HKM', 'name' => 'Hukum Administrasi Pemerintahan', 'position' => 2, 'is_active' => true,
                'description' => 'Materi contoh hukum administrasi pemerintahan untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Pengantar Administrasi Pemerintahan', 'Asas Pemerintahan yang Baik', 'Latihan Keputusan Administrasi', 'Pembahasan Studi Kasus Administrasi']],
            ['section_code' => 'HKM', 'name' => 'Etika dan Tanggung Jawab Aparatur', 'position' => 3, 'is_active' => false,
                'description' => 'Materi contoh etika dan tanggung jawab aparatur untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Peta Konsep Tanggung Jawab Aparatur', 'Etika Penggunaan Informasi Dinas', 'Latihan Tanggung Jawab Pelayanan', 'Pembahasan Kasus Aparatur']],
            ['section_code' => 'KEW', 'name' => 'Hak dan Kewajiban Warga Negara', 'position' => 1, 'is_active' => true,
                'description' => 'Materi contoh hak dan kewajiban warga negara untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Ringkasan Hak dan Kewajiban Warga', 'Keseimbangan Hak dan Kewajiban', 'Latihan Partisipasi Warga Negara', 'Pembahasan Kasus Kewarganegaraan']],
            ['section_code' => 'KEW', 'name' => 'Bhinneka Tunggal Ika', 'position' => 2, 'is_active' => true,
                'description' => 'Materi contoh bhinneka tunggal ika untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Pengantar Keberagaman Indonesia', 'Toleransi dalam Lingkungan Kerja', 'Latihan Persatuan dalam Keberagaman', 'Pembahasan Konflik Sosial']],
            ['section_code' => 'KEW', 'name' => 'Partisipasi dan Kehidupan Demokrasi', 'position' => 3, 'is_active' => false,
                'description' => 'Materi contoh partisipasi dan kehidupan demokrasi untuk simulasi alur belajar CPNS.',
                'lesson_titles' => ['Peta Konsep Demokrasi Indonesia', 'Partisipasi dalam Kehidupan Publik', 'Latihan Prinsip Musyawarah', 'Pembahasan Kasus Demokrasi']],
            ['section_code' => 'UMUM', 'name' => 'Rencana Belajar Lanjutan', 'position' => 4, 'is_active' => true,
                'description' => 'Materi contoh rencana belajar lanjutan untuk simulasi alur belajar CPNS.',
                'lesson_titles' => []],
        ];
    }
}
