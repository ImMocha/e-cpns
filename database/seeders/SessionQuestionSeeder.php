<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionScoring;
use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SessionQuestionSeeder extends Seeder
{
    public const SOURCE_PREFIX = 'contoh-skd:';

    public function run(): void
    {
        $sections = Section::query()->active()->whereIn('code', ['TWK', 'TIU', 'TKP'])->get()->keyBy('code');
        if ($sections->count() !== 3) {
            $this->command?->warn('Bank soal contoh belum dibuat. Siapkan bidang aktif TWK, TIU, dan TKP terlebih dahulu.');

            return;
        }

        DB::transaction(function () use ($sections): void {
            foreach (['TWK' => $this->twk(), 'TIU' => $this->tiu(), 'TKP' => $this->tkp()] as $code => $definitions) {
                foreach ($definitions as $index => $definition) {
                    $number = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
                    $offset = $index % 5;
                    $options = [...array_slice($definition['options'], $offset), ...array_slice($definition['options'], 0, $offset)];
                    $weighted = $code === 'TKP';
                    $scores = [5, 4, 3, 2, 1];
                    Question::withTrashed()->firstOrCreate(['source_key' => self::SOURCE_PREFIX.$code.':'.$number], [
                        'section_id' => $sections->get($code)->id,
                        'title' => 'Contoh SKD · '.$code.' · '.$number.' · '.$definition['title'],
                        'body' => '<p>'.$definition['body'].'</p>', 'options' => $options,
                        'correct_option' => $weighted ? null : (5 - $offset) % 5,
                        'scoring_type' => $weighted ? QuestionScoring::Weighted : QuestionScoring::Binary,
                        'option_scores' => $weighted ? [...array_slice($scores, $offset), ...array_slice($scores, 0, $offset)] : null,
                        'shuffle_options' => true, 'explanation' => '<p>'.$definition['explanation'].'</p>',
                        'difficulty' => QuestionDifficulty::Medium, 'status' => QuestionStatus::Published, 'published_at' => now(),
                    ]);
                }
            }
        });
    }

    /** @return list<array{title: string, body: string, options: list<string>, explanation: string}> */
    private function twk(): array
    {
        $definitions = [
            ['Toleransi beribadah', 'Memberi kesempatan rekan menjalankan ibadah sesuai keyakinannya mencerminkan sila ...', ['Ketuhanan Yang Maha Esa', 'Keadilan Sosial bagi Seluruh Rakyat Indonesia', 'Persatuan Indonesia', 'Kerakyatan yang Dipimpin oleh Hikmat Kebijaksanaan dalam Permusyawaratan/Perwakilan', 'Kemanusiaan yang Adil dan Beradab'], 'Penghormatan terhadap keyakinan dan ibadah merupakan penerapan sila pertama.'],
            ['Martabat manusia', 'Menolong korban kecelakaan tanpa membedakan latar belakang mencerminkan sila ...', ['Kemanusiaan yang Adil dan Beradab', 'Persatuan Indonesia', 'Ketuhanan Yang Maha Esa', 'Keadilan Sosial bagi Seluruh Rakyat Indonesia', 'Kerakyatan yang Dipimpin oleh Hikmat Kebijaksanaan dalam Permusyawaratan/Perwakilan'], 'Pertolongan yang menghargai martabat setiap manusia mencerminkan sila kedua.'],
            ['Persatuan warga', 'Warga dari berbagai suku bergotong royong memperbaiki jembatan. Nilai yang paling menonjol adalah ...', ['Persatuan Indonesia', 'Keunggulan satu suku', 'Persaingan antardaerah', 'Pemisahan kelompok', 'Kepentingan pribadi'], 'Kerja sama lintas suku memperkuat persatuan sebagai penerapan sila ketiga.'],
            ['Musyawarah', 'Pengurus mendengarkan seluruh usulan sebelum menyepakati program bersama. Sikap ini mencerminkan ...', ['Permusyawaratan', 'Pemaksaan kehendak', 'Pengutamaan kelompok mayoritas saja', 'Pengabaian aspirasi', 'Persaingan pribadi'], 'Musyawarah memberi ruang bagi pertimbangan dan kepentingan bersama, sesuai sila keempat.'],
            ['Keadilan sosial', 'Pembagian bantuan sesuai kebutuhan warga yang diverifikasi secara adil mencerminkan ...', ['Keadilan sosial', 'Keistimewaan berdasarkan kedekatan', 'Pengutamaan keluarga pengurus', 'Kepentingan golongan', 'Diskriminasi daerah asal'], 'Sila kelima menekankan keadilan sosial, termasuk pemenuhan kebutuhan dengan perlakuan adil.'],
            ['Keberagaman', 'Makna semboyan Bhinneka Tunggal Ika yang paling tepat adalah ...', ['Berbeda-beda tetapi tetap satu', 'Semua perbedaan harus dihapus', 'Setiap daerah berdiri sendiri', 'Satu kelompok lebih utama', 'Persatuan hanya untuk kelompok serupa'], 'Semboyan ini menegaskan persatuan di tengah keberagaman.'],
            ['Bahasa persatuan', 'Dalam rapat lintas daerah, penggunaan bahasa Indonesia terutama bertujuan ...', ['Memudahkan komunikasi dan memperkuat persatuan', 'Menghapus seluruh bahasa daerah', 'Melarang budaya lokal', 'Membatasi peserta dari daerah lain', 'Menunjukkan keunggulan satu suku'], 'Bahasa Indonesia menjadi sarana komunikasi bersama tanpa menghapus bahasa daerah.'],
            ['Sumpah Pemuda', 'Sumpah Pemuda diperingati setiap tanggal ...', ['28 Oktober', '17 Agustus', '1 Juni', '20 Mei', '10 November'], 'Kongres Pemuda II menghasilkan Sumpah Pemuda pada 28 Oktober 1928.'],
            ['Proklamasi', 'Proklamasi Kemerdekaan Indonesia dibacakan pada ...', ['17 Agustus 1945', '28 Oktober 1928', '20 Mei 1908', '1 Juni 1945', '10 November 1945'], 'Proklamasi Kemerdekaan Indonesia dibacakan pada 17 Agustus 1945.'],
            ['Dasar negara', 'Dasar negara Indonesia adalah ...', ['Pancasila', 'Sumpah Pemuda', 'Bahasa Indonesia', 'Bhinneka Tunggal Ika', 'Indonesia Raya'], 'Pancasila merupakan dasar negara Indonesia.'],
            ['Bendera negara', 'Warna bendera negara Indonesia adalah ...', ['Merah dan putih', 'Putih dan biru', 'Merah dan hitam', 'Kuning dan hijau', 'Biru dan merah'], 'Bendera Indonesia adalah Sang Merah Putih.'],
            ['Lagu kebangsaan', 'Lagu kebangsaan Indonesia adalah ...', ['Indonesia Raya', 'Bagimu Negeri', 'Garuda Pancasila', 'Hari Merdeka', 'Tanah Airku'], 'Indonesia Raya merupakan lagu kebangsaan Indonesia.'],
            ['Lambang negara', 'Lambang negara Indonesia adalah ...', ['Garuda Pancasila', 'Pohon beringin', 'Banteng', 'Padi dan kapas', 'Rantai emas'], 'Garuda Pancasila adalah lambang negara; simbol lainnya terdapat pada perisainya.'],
            ['Gotong royong', 'Contoh gotong royong yang paling tepat adalah ...', ['Warga bekerja bersama membersihkan saluran air', 'Seseorang memaksakan kepentingannya', 'Kelompok menutup akses warga lain', 'Warga menghindari kegiatan bersama', 'Pengurus hanya melayani kerabat'], 'Gotong royong mengutamakan kerja sama dan manfaat bersama.'],
            ['Nasionalisme', 'Sikap nasionalisme dalam kehidupan sehari-hari tampak pada tindakan ...', ['Mengutamakan kepentingan bersama dan menghargai keragaman bangsa', 'Merendahkan budaya daerah lain', 'Mengabaikan lingkungan sekitar', 'Menolak bekerja sama dengan suku berbeda', 'Mementingkan golongan sendiri'], 'Nasionalisme dapat diwujudkan melalui kepedulian terhadap kepentingan bangsa dan persatuan.'],
            ['Integritas publik', 'Petugas menemukan kesalahan data yang menguntungkan dirinya. Tindakan yang mencerminkan integritas adalah ...', ['Melaporkan dan memperbaiki kesalahan melalui prosedur', 'Membiarkan karena menguntungkan', 'Menyembunyikan data pendukung', 'Menyalahkan pihak lain', 'Mengubah data agar sulit dilacak'], 'Integritas menuntut kejujuran dan tanggung jawab, termasuk saat tidak menguntungkan diri sendiri.'],
            ['Kepentingan umum', 'Keputusan penggunaan fasilitas bersama sebaiknya didasarkan pada ...', ['Kebutuhan bersama dengan aturan yang adil', 'Kedekatan dengan pengurus', 'Kekuatan kelompok tertentu', 'Keuntungan pribadi', 'Asal daerah pengguna'], 'Fasilitas bersama perlu dikelola untuk kepentingan umum secara adil.'],
            ['Hak dan kewajiban', 'Sikap yang menunjukkan keseimbangan hak dan kewajiban adalah ...', ['Menggunakan fasilitas umum sekaligus menjaga kebersihannya', 'Menuntut layanan sambil merusak sarana', 'Mengambil seluruh fasilitas untuk diri sendiri', 'Mengabaikan aturan antrean', 'Menghalangi akses warga lain'], 'Penggunaan fasilitas sebagai hak disertai kewajiban menjaga sarana dan menghormati pengguna lain.'],
            ['Menghargai pendapat', 'Saat pendapatnya tidak diterima dalam musyawarah, sikap yang tepat adalah ...', ['Menghormati hasil kesepakatan dan tetap berkontribusi', 'Mengganggu pelaksanaan keputusan', 'Memutus komunikasi dengan peserta', 'Menyebarkan fitnah tentang pengurus', 'Memaksakan keputusan sendiri'], 'Menghormati hasil musyawarah menunjukkan tanggung jawab terhadap kesepakatan bersama.'],
            ['Persatuan digital', 'Sebelum meneruskan pesan yang berpotensi memecah belah, tindakan terbaik adalah ...', ['Memeriksa kebenaran dan konteks melalui sumber tepercaya', 'Langsung menyebarkan ke semua grup', 'Menambah tuduhan agar menarik', 'Menghapus identitas sumber', 'Menganggap semua pesan viral pasti benar'], 'Verifikasi informasi membantu mencegah penyebaran kabar keliru yang merusak persatuan.'],
            ['Pelestarian budaya', 'Cara menghargai budaya daerah lain adalah ...', ['Mempelajari dan menghormatinya tanpa merendahkan budaya sendiri', 'Menganggap budaya sendiri selalu paling tinggi', 'Melarang pertunjukan budaya berbeda', 'Menjadikan adat lain bahan ejekan', 'Menghindari seluruh interaksi lintas budaya'], 'Penghargaan terhadap budaya lain memperkuat persatuan dalam keberagaman.'],
            ['Solidaritas', 'Ketika terjadi bencana di daerah lain, sikap yang memperkuat persatuan adalah ...', ['Membantu sesuai kemampuan melalui saluran yang tepercaya', 'Menolak karena berbeda daerah', 'Menjadikan korban bahan ejekan', 'Menghalangi pengiriman bantuan', 'Memanfaatkan penderitaan untuk keuntungan pribadi'], 'Solidaritas lintas daerah menunjukkan kepedulian sebagai satu bangsa.'],
            ['Antidiskriminasi', 'Pelayanan kepada warga dari latar belakang berbeda seharusnya ...', ['Diberikan secara adil berdasarkan kebutuhan dan aturan', 'Mendahulukan warga yang satu suku', 'Mengikuti pilihan agama petugas', 'Dibatasi bagi pendatang', 'Ditentukan oleh kedekatan pribadi'], 'Pelayanan yang adil menghormati kesetaraan martabat manusia.'],
            ['Bela negara', 'Contoh kontribusi bela negara dalam keseharian adalah ...', ['Bekerja jujur, menjaga persatuan, dan peduli lingkungan', 'Merusak fasilitas umum', 'Menyebarkan provokasi', 'Mengabaikan keselamatan bersama', 'Menolak kerja sama lintas kelompok'], 'Bela negara dapat diwujudkan lewat kontribusi positif sesuai peran masing-masing.'],
            ['Tanggung jawab', 'Pengurus kegiatan menyampaikan laporan penggunaan dana secara terbuka. Nilai yang diterapkan adalah ...', ['Akuntabilitas', 'Kerahasiaan tanpa alasan', 'Pengutamaan golongan', 'Pemaksaan kehendak', 'Persaingan pribadi'], 'Laporan yang dapat diperiksa menunjukkan pertanggungjawaban atas amanah bersama.'],
            ['Kepedulian sosial', 'Contoh kepedulian sosial yang tepat adalah ...', ['Membantu tetangga yang kesulitan tanpa mempermalukannya', 'Memamerkan kesulitan tetangga untuk hiburan', 'Menolak membantu karena berbeda suku', 'Meminta imbalan sebelum menolong korban', 'Mengabaikan kebutuhan warga rentan'], 'Kepedulian sosial dilaksanakan dengan menghargai martabat penerima bantuan.'],
            ['Antrean', 'Menaati antrean layanan umum mencerminkan sikap ...', ['Menghormati hak orang lain', 'Mendahulukan kepentingan pribadi', 'Mencari perlakuan khusus', 'Menghindari tanggung jawab', 'Menolak aturan bersama'], 'Antrean yang tertib melindungi hak pengguna layanan lain.'],
            ['Dialog', 'Perbedaan pandangan antarkelompok paling baik diselesaikan melalui ...', ['Dialog yang saling menghormati dan mencari titik temu', 'Ancaman terhadap pihak lain', 'Penyebaran kabar yang belum benar', 'Pengucilan seluruh kelompok berbeda', 'Pemaksaan tanpa mendengar alasan'], 'Dialog mendorong pemahaman dan penyelesaian yang menjaga persatuan.'],
            ['Keadilan kesempatan', 'Pemberian kesempatan mengikuti kegiatan warga sebaiknya ...', ['Terbuka secara adil dengan persyaratan yang relevan', 'Hanya untuk kerabat pengurus', 'Bergantung pada asal suku', 'Dibatasi menurut kedekatan pribadi', 'Ditentukan oleh pemberian hadiah'], 'Kesempatan yang adil tidak didasarkan pada diskriminasi atau keuntungan pribadi.'],
            ['Menjaga fasilitas', 'Menjaga fasilitas umum berarti ...', ['Merawat sarana agar tetap bermanfaat bagi semua', 'Menguasai sarana untuk kelompok sendiri', 'Merusak sarana yang tidak disukai', 'Menghalangi warga lain menggunakannya', 'Mengabaikan kerusakan yang membahayakan'], 'Fasilitas umum merupakan kepentingan bersama yang perlu dijaga keberlanjutannya.'],
        ];

        return array_map(fn (array $item): array => ['title' => $item[0], 'body' => $item[1], 'options' => $item[2], 'explanation' => $item[3]], $definitions);
    }

    /** @return list<array{title: string, body: string, options: list<string>, explanation: string}> */
    private function tiu(): array
    {
        $definitions = [];
        for ($variant = 1; $variant <= 5; $variant++) {
            $start = 3 * $variant;
            $step = $variant + 2;
            $answer = $start + 4 * $step;
            $sequence = implode(', ', [$start, $start + $step, $start + 2 * $step, $start + 3 * $step]);
            $definitions[] = $this->numeric('Deret aritmetika '.$variant, 'Bilangan berikutnya pada deret '.$sequence.', ... adalah ...', $answer,
                'Selisih setiap suku adalah '.$step.'. Suku berikutnya adalah '.$answer.'.', $step);
            $price = (100 + 20 * $variant) * 1000;
            $discount = 10 + 5 * $variant;
            $answer = intdiv($price * (100 - $discount), 100);
            $definitions[] = $this->numeric('Persentase diskon '.$variant, 'Harga sebuah barang Rp'.$price.' mendapat diskon '.$discount.'%. Harga setelah diskon adalah ... rupiah.', $answer,
                'Harga akhir = '.$price.' × '.(100 - $discount).'/100 = '.$answer.' rupiah.', 1000);
            $first = $variant + 2;
            $second = $variant + 3;
            $unit = 5 * $variant + 5;
            $total = ($first + $second) * $unit;
            $answer = $second * $unit;
            $definitions[] = $this->numeric('Perbandingan buku '.$variant, 'Buku Rani dan Budi berbanding '.$first.' : '.$second.'. Jumlahnya '.$total.'. Berapa buku Budi?', $answer,
                'Satu bagian = '.$total.'/'.($first + $second).' = '.$unit.'. Buku Budi = '.$second.' × '.$unit.' = '.$answer.'.');
            $workers = $variant + 4;
            $days = 12 + 2 * $variant;
            $answer = intdiv($days, 2);
            $definitions[] = $this->numeric('Perbandingan pekerjaan '.$variant, 'Sebanyak '.$workers.' pekerja menyelesaikan pekerjaan dalam '.$days.' hari. Jika pekerja menjadi '.(2 * $workers).' orang dengan kemampuan sama, waktu yang dibutuhkan adalah ... hari.', $answer,
                'Untuk pekerjaan tetap, jumlah pekerja dan waktu berbanding terbalik. Pekerja dua kali lipat memerlukan '.$days.'/2 = '.$answer.' hari.');
            $base = 12 + 2 * $variant;
            $values = [$base, $base + 2, $base + 4, $base + 6, $base + 8];
            $answer = $base + 4;
            $definitions[] = $this->numeric('Rata-rata '.$variant, 'Rata-rata dari '.implode(', ', $values).' adalah ...', $answer,
                'Jumlah bilangan = '.array_sum($values).'. Rata-rata = '.array_sum($values).'/5 = '.$answer.'.');
            $speed = 40 + 5 * $variant;
            $hours = 2 + $variant;
            $answer = $speed * $hours;
            $definitions[] = $this->numeric('Jarak dan waktu '.$variant, 'Kendaraan melaju konstan '.$speed.' km/jam selama '.$hours.' jam. Jarak yang ditempuh adalah ... km.', $answer,
                'Jarak = kecepatan × waktu = '.$speed.' × '.$hours.' = '.$answer.' km.', 10);
            $types = [['arsip digital', 'dokumen elektronik', 'data yang dicadangkan'], ['petugas loket', 'pegawai terlatih', 'peserta pelatihan layanan'], ['buku referensi', 'koleksi perpustakaan', 'barang tercatat'], ['anggota tim A', 'peserta rapat', 'penerima undangan'], ['laporan bulanan', 'dokumen evaluasi', 'dokumen yang diverifikasi']];
            [$a, $b, $c] = $types[$variant - 1];
            $definitions[] = ['title' => 'Silogisme '.$variant, 'body' => 'Semua '.$a.' adalah '.$b.'. Semua '.$b.' adalah '.$c.'. Kesimpulan yang pasti benar adalah ...',
                'options' => ['Semua '.$a.' adalah '.$c.'.', 'Semua '.$c.' adalah '.$a.'.', 'Tidak ada '.$a.' yang termasuk '.$c.'.', 'Semua '.$b.' adalah '.$a.'.', 'Tidak ada hubungan yang dapat disimpulkan.'],
                'explanation' => 'Hubungan kelompok bersifat transitif: jika A termasuk B dan B termasuk C, semua A termasuk C. Hubungan sebaliknya tidak otomatis benar.'];
        }

        return $definitions;
    }

    /** @return array{title: string, body: string, options: list<string>, explanation: string} */
    private function numeric(string $title, string $body, int $answer, string $explanation, int $step = 1): array
    {
        return ['title' => $title, 'body' => $body,
            'options' => array_map(strval(...), [$answer, $answer + $step, $answer - $step, $answer + 2 * $step, $answer + 3 * $step]),
            'explanation' => $explanation];
    }

    /** @return list<array{title: string, body: string, options: list<string>, explanation: string}> */
    private function tkp(): array
    {
        $competencies = [
            ['Integritas', [
                'Anda menemukan pengeluaran ganda dalam laporan yang akan dikirim hari ini.',
                'Rekan meminta Anda mengubah tanggal dokumen agar keterlambatannya tidak terlihat.',
                'Kesalahan penginputan membuat capaian kerja pribadi Anda terlihat lebih tinggi daripada kenyataan.',
                'Bukti pendukung sebuah laporan tidak sesuai dengan angka yang dicatat.',
                'Anda mengetahui bahwa satu transaksi belum dicantumkan dalam rekap yang menjadi tanggung jawab tim.',
            ], ['Memeriksa fakta, menyampaikan temuan secara jujur, dan menindaklanjuti perbaikan melalui prosedur.', 'Menyampaikan temuan kepada penanggung jawab untuk ditindaklanjuti.', 'Meminta rekan memeriksa ulang tanpa memastikan tindak lanjutnya.', 'Menunda pembahasan agar tidak mengganggu target pekerjaan.', 'Mengabaikan atau menyembunyikan masalah agar laporan terlihat baik.'],
                'Kejujuran disertai verifikasi dan perbaikan sesuai prosedur memperoleh nilai tertinggi. Mengabaikan atau menyembunyikan masalah memperoleh nilai terendah.'],
            ['Pelayanan publik', [
                'Seorang warga lanjut usia kesulitan memahami formulir layanan.',
                'Warga datang dengan berkas yang belum lengkap dan belum memahami langkah berikutnya.',
                'Pemohon layanan kebingungan karena informasi persyaratan di papan pengumuman kurang jelas.',
                'Seorang warga mengeluhkan proses layanan dan meminta penjelasan dengan nada kecewa.',
                'Pemohon dengan kebutuhan aksesibilitas memerlukan bantuan untuk menyelesaikan proses layanan.',
            ], ['Mendengarkan kebutuhan, memberi penjelasan yang jelas, dan membantu sesuai prosedur tanpa mengurangi hak pemohon lain.', 'Menjelaskan prosedur dengan sopan dan mengarahkan pada bantuan yang tersedia.', 'Memberikan informasi umum lalu mempersilakan pemohon mencoba sendiri.', 'Meminta pemohon mencari penjelasan di tempat lain tanpa arahan.', 'Mengabaikan keluhan dan menyalahkan pemohon atas kesulitannya.'],
                'Pelayanan terbaik menggabungkan empati, kejelasan informasi, aksesibilitas, dan kepatuhan pada prosedur.'],
            ['Kerja sama', [
                'Rekan satu tim kewalahan ketika tenggat laporan bersama sudah dekat.',
                'Pembagian pekerjaan tim belum seimbang sehingga beberapa tugas berisiko terlambat.',
                'Dua anggota tim memiliki pemahaman berbeda tentang hasil yang harus diserahkan.',
                'Ada pekerjaan lintas bagian yang tertunda karena koordinasi belum berjalan.',
                'Anggota baru belum memahami alur kerja sehingga pekerjaan tim melambat.',
            ], ['Mengajak tim menyamakan kebutuhan dan membagi bantuan secara jelas sambil menjaga tanggung jawab sendiri.', 'Menawarkan bantuan sesuai kemampuan dan berkoordinasi dengan penanggung jawab.', 'Menyampaikan masalah dalam rapat tanpa menawarkan dukungan yang jelas.', 'Menunggu anggota lain menyelesaikan masalah meskipun pekerjaan bersama terhambat.', 'Menyalahkan rekan dan menolak berkoordinasi.'],
                'Kolaborasi yang aktif, terarah, dan menjaga tanggung jawab masing-masing memperoleh skor tertinggi.'],
            ['Adaptasi', [
                'Prosedur pelayanan berubah dan mulai berlaku pada pekan ini.',
                'Anda ditempatkan sementara pada tugas yang alurnya berbeda dari pekerjaan sebelumnya.',
                'Tim menggunakan sistem pencatatan baru untuk menggantikan proses manual.',
                'Jadwal pelaksanaan kegiatan berubah sehingga rencana kerja perlu disesuaikan.',
                'Struktur tim berubah dan Anda perlu bekerja dengan penanggung jawab baru.',
            ], ['Mempelajari perubahan, meminta klarifikasi bila diperlukan, dan menyesuaikan cara kerja secara terencana.', 'Mengikuti arahan baru dan mempelajari panduan yang tersedia.', 'Mencoba mengikuti perubahan hanya saat diminta langsung.', 'Menunda penyesuaian dan terus memakai cara lama tanpa konfirmasi.', 'Menolak perubahan tanpa mempelajari alasan dan kebutuhannya.'],
                'Adaptasi dinilai dari kemauan belajar, klarifikasi, dan penerapan perubahan dengan bertanggung jawab.'],
            ['Literasi digital', [
                'Anda menerima pesan yang meminta data akun melalui tautan yang tidak dikenal.',
                'Sebuah dokumen internal hendak dikirim melalui layanan berbagi file yang belum disetujui.',
                'Rekan mengusulkan penggunaan akun bersama untuk mempercepat pekerjaan.',
                'Anda menemukan informasi pekerjaan yang meragukan dalam grup percakapan.',
                'Aplikasi kerja menampilkan permintaan akses data yang tidak sesuai dengan kebutuhan tugas.',
            ], ['Memverifikasi sumber dan kebutuhan akses, menjaga kerahasiaan data, serta berkonsultasi melalui kanal resmi bila perlu.', 'Menghentikan tindakan sementara dan meminta konfirmasi penanggung jawab.', 'Menunggu pendapat rekan tanpa memeriksa sumber resmi.', 'Mengikuti permintaan karena tampak membantu menyelesaikan pekerjaan.', 'Membagikan data atau akses tanpa verifikasi agar proses lebih cepat.'],
                'Penggunaan teknologi yang bertanggung jawab memerlukan verifikasi, pembatasan akses, dan perlindungan data.'],
            ['Sosial budaya', [
                'Rekan dari latar budaya berbeda memiliki kebiasaan komunikasi yang belum Anda pahami.',
                'Tim menyusun kegiatan yang pesertanya memiliki keyakinan beragam.',
                'Terjadi salah paham karena penggunaan istilah daerah dalam rapat lintas wilayah.',
                'Seorang anggota tim merasa pendapatnya kurang didengar karena berbeda latar belakang.',
                'Jadwal kegiatan perlu mempertimbangkan kebutuhan ibadah anggota tim.',
            ], ['Mendengarkan kebutuhan semua pihak dan mencari pengaturan yang inklusif sambil menjaga tujuan bersama.', 'Menghormati perbedaan dan menanyakan cara berkoordinasi yang sesuai.', 'Mengikuti keputusan umum tanpa menggali kebutuhan pihak yang terdampak.', 'Menghindari interaksi dengan rekan yang berbeda latar belakang.', 'Memaksakan kebiasaan sendiri dan merendahkan pihak yang berbeda.'],
                'Sikap inklusif mengutamakan penghormatan, komunikasi, dan solusi yang memungkinkan semua pihak berkontribusi.'],
            ['Profesionalisme', [
                'Anda menerima dua pekerjaan mendesak dengan tenggat yang berdekatan.',
                'Hasil pekerjaan perlu diperiksa ulang, sementara tenggat penyerahan sudah dekat.',
                'Ada tugas tambahan penting ketika pekerjaan rutin Anda belum selesai.',
                'Ketergantungan pada data dari bagian lain berpotensi menghambat penyelesaian tugas.',
                'Anda menyadari bahwa rencana penyelesaian tugas awal tidak lagi realistis.',
            ], ['Menilai prioritas dan risiko, menyusun rencana, serta mengomunikasikan kebutuhan atau kendala sejak dini.', 'Mengonfirmasi prioritas kepada penanggung jawab lalu menyesuaikan pekerjaan.', 'Mengerjakan tugas yang paling mudah dahulu tanpa memeriksa prioritas.', 'Menunggu sampai tenggat untuk menyampaikan kendala.', 'Mengabaikan kualitas atau menyatakan pekerjaan selesai padahal belum diperiksa.'],
                'Profesionalisme mencakup penetapan prioritas, mutu hasil, dan komunikasi kendala sebelum terlambat.'],
            ['Inisiatif', [
                'Anda melihat warga sering salah mengisi bagian yang sama pada formulir.',
                'Pencatatan berulang menyebabkan tim sering melakukan kesalahan yang sama.',
                'Ada langkah kerja yang berpotensi disederhanakan tanpa mengubah kewenangan petugas.',
                'Umpan balik pemohon menunjukkan bahwa informasi layanan perlu diperjelas.',
                'Tim belum memiliki ringkasan panduan untuk menjawab pertanyaan yang sering muncul.',
            ], ['Mengumpulkan bukti masalah dan mengusulkan perbaikan yang dapat diuji dengan persetujuan pihak berwenang.', 'Menyampaikan ide perbaikan kepada penanggung jawab untuk dipertimbangkan.', 'Mencatat masalah untuk dibahas bila ada kesempatan.', 'Membiarkan keadaan karena belum ada perintah khusus.', 'Mengubah prosedur sendiri tanpa koordinasi dan tanpa menilai dampaknya.'],
                'Inisiatif yang baik berbasis masalah nyata, dapat dievaluasi, dan tetap menghormati kewenangan serta prosedur.'],
            ['Pengembangan diri', [
                'Tugas baru membutuhkan kemampuan analisis yang belum Anda kuasai.',
                'Hasil evaluasi menunjukkan bahwa ketelitian pekerjaan Anda perlu ditingkatkan.',
                'Anda memperoleh umpan balik bahwa penjelasan lisan Anda sulit dipahami pemohon.',
                'Perubahan aplikasi kerja menuntut keterampilan yang belum pernah Anda pelajari.',
                'Anda ingin meningkatkan mutu hasil kerja setelah melihat contoh praktik baik dari tim lain.',
            ], ['Mengidentifikasi kesenjangan, menyusun latihan atau pembelajaran, dan mengevaluasi penerapannya dalam pekerjaan.', 'Mencari panduan dan meminta bimbingan rekan yang kompeten.', 'Membaca materi sekilas tanpa rencana latihan yang jelas.', 'Menunggu pelatihan wajib tanpa melakukan upaya belajar mandiri.', 'Menolak umpan balik dan menganggap kemampuan saat ini pasti cukup.'],
                'Pengembangan diri memerlukan keterbukaan pada umpan balik, pembelajaran aktif, dan evaluasi hasil.'],
        ];
        $definitions = [];
        foreach ($competencies as [$title, $situations, $options, $explanation]) {
            foreach ($situations as $index => $situation) {
                $definitions[] = ['title' => $title.' '.($index + 1), 'body' => $situation.' Bagaimana Anda merespons?',
                    'options' => $options, 'explanation' => $explanation.' Skor 1–5 pada soal contoh ini disusun untuk latihan, bukan kunci resmi seleksi.'];
            }
        }

        return $definitions;
    }
}
