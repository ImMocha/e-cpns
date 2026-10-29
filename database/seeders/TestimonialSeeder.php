<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        Testimonial::firstOrCreate([
            'name' => 'Contoh Peserta',
            'content' => 'Contoh testimoni untuk pratinjau admin. Ganti dengan pengalaman peserta yang sebenarnya sebelum ditampilkan.',
        ], ['is_visible' => false]);

        $examples = [
            ['name' => 'Ayu Pratiwi (Demo)', 'institution' => 'Peserta Mandiri · Data Demo', 'content' => 'Materinya tersusun rapi, jadi saya lebih mudah menentukan topik yang perlu dipelajari setiap hari. Belajar setelah pulang kerja terasa lebih terarah.'],
            ['name' => 'Rizky Saputra (Demo)', 'institution' => 'Lulusan Baru · Data Demo', 'content' => 'Simulasi tryout membantu saya membiasakan diri dengan batas waktu. Setelah selesai, saya bisa meninjau jawaban dan mempelajari kembali bagian yang belum dipahami.'],
            ['name' => 'Nadia Putri (Demo)', 'institution' => 'Peserta Mandiri · Data Demo', 'content' => 'Saya suka pembahasan latihannya karena membantu memahami cara mengerjakan soal. Cocok untuk menemani persiapan belajar sedikit demi sedikit.'],
            ['name' => 'Dimas Prasetyo (Demo)', 'institution' => 'Karyawan Swasta · Data Demo', 'content' => 'Waktu belajar saya terbatas, tetapi materi dan latihan dalam satu platform membuat sesi belajar lebih praktis. Saya bisa melanjutkan persiapan saat ada waktu luang.'],
            ['name' => 'Salsabila Rahma (Demo)', 'institution' => 'Mahasiswa Tingkat Akhir · Data Demo', 'content' => 'Riwayat tryout memudahkan saya melihat materi yang masih perlu diulang. Saya jadi punya gambaran untuk menyusun target belajar berikutnya.'],
            ['name' => 'Fajar Hidayat (Demo)', 'institution' => 'Peserta Mandiri · Data Demo', 'content' => 'Tampilan belajarnya nyaman dan pilihan latihannya membantu saya menjaga rutinitas. Saya biasanya membaca materi terlebih dahulu, lalu mencoba soal untuk mengecek pemahaman.'],
        ];

        foreach ($examples as $example) {
            Testimonial::firstOrCreate(['name' => $example['name']], [
                'institution' => $example['institution'],
                'content' => $example['content'],
                'is_visible' => true,
            ]);
        }
    }
}
