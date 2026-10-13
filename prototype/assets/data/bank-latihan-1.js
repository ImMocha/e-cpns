/* ==========================================================================
   LONTAR · Bank soal Latihan Silogisme (prototipe: 8 soal, tanpa timer)
   ========================================================================== */
window.LONTAR_BANKS = window.LONTAR_BANKS || {};

window.LONTAR_BANKS['latihan-1'] = {
  id: 'latihan-1',
  title: 'Latihan Silogisme dan Penalaran',
  subtitle: '8 soal, tanpa timer',
  durationSeconds: 0,
  showTimer: false,
  pointsPerCorrect: 12,
  penaltyWrong: 0,
  passingScore: 70,
  reviewUrl: 'tryout-hasil.html?id=latihan-1',
  exitUrl: 'quiz.html',
  questions: [
    {
      id: 1,
      section: 'TIU',
      topic: 'Silogisme',
      stem: 'Semua guru adalah sarjana. Semua sarjana yang aktif mengikuti pelatihan adalah peserta pelatihan. Kesimpulan yang benar adalah ...',
      options: [
        'Semua guru yang aktif mengikuti pelatihan',
        'Semua sarjana aktif mengikuti pelatihan',
        'Sebagian guru bukan sarjana',
        'Semua peserta pelatihan adalah sarjana',
        'Tidak dapat ditarik kesimpulan'
      ],
      answer: 0,
      explain: 'Guru termasuk kelompok sarjana. Karena sarjana yang aktif mengikuti pelatihan adalah peserta pelatihan, maka setiap guru juga peserta pelatihan.'
    },
    {
      id: 2,
      section: 'TIU',
      topic: 'Silogisme',
      stem: 'Semua anggota ASEAN juga anggota PBB. Tidak semua anggota PBB adalah anggota ASEAN. Kesimpulan yang tepat adalah ...',
      options: [
        'Semua anggota PBB adalah anggota ASEAN',
        'Sebagian anggota PBB bukan anggota ASEAN',
        'Tidak ada anggota PBB yang menjadi anggota ASEAN',
        'Semua anggota ASEAN berasal dari PBB',
        'Tidak dapat ditarik kesimpulan'
      ],
      answer: 1,
      explain: 'Pernyataan kedua berbentuk tidak semua, yang setara dengan ada sebagian yang tidak termasuk. Maka sebagian anggota PBB bukan anggota ASEAN.'
    },
    {
      id: 3,
      section: 'TIU',
      topic: 'Penalaran',
      stem: 'Anton lebih tinggi daripada Budi, dan Budi lebih tinggi daripada Citra. Siapa yang paling rendah di antara mereka?',
      options: ['Anton', 'Budi', 'Citra', 'Tidak dapat dipastikan', 'Ketiganya sama tinggi'],
      answer: 2,
      explain: 'Rantai hubungan: Anton lebih tinggi dari Budi, dan Budi lebih tinggi dari Citra. Maka Citra adalah yang paling rendah.'
    },
    {
      id: 4,
      section: 'TIU',
      topic: 'Silogisme',
      stem: 'Semua buku di rak A adalah buku tebal. Semua buku tebal berlatar hijau. Kesimpulan yang benar adalah ...',
      options: [
        'Semua buku di rak A berlatar hijau',
        'Semua buku berlatar hijau ada di rak A',
        'Sebagian buku di rak A tidak tebal',
        'Sebagian buku di rak A berwarna lain',
        'Tidak dapat ditarik kesimpulan'
      ],
      answer: 0,
      explain: 'Buku di rak A termasuk buku tebal, dan setiap buku tebal berlatar hijau, sehingga semua buku di rak A berlatar hijau.'
    },
    {
      id: 5,
      section: 'TIU',
      topic: 'Silogisme',
      stem: 'Tidak semua peserta yang lolos seleksi diterima. Semua peserta yang diterima telah mengikuti tes kesehatan. Kesimpulan yang benar adalah ...',
      options: [
        'Semua peserta yang mengikuti tes kesehatan diterima',
        'Sebagian peserta yang mengikuti tes kesehatan tidak diterima',
        'Semua peserta yang lolos seleksi diterima',
        'Tidak ada peserta yang lolos seleksi',
        'Tidak dapat ditarik kesimpulan'
      ],
      answer: 4,
      explain: 'Relasinya belum saling terhubung secara logis. Pernyataan pertama menyangkut peserta yang lolos seleksi, sedangkan pernyataan kedua menyangkut peserta yang diterima.'
    },
    {
      id: 6,
      section: 'TKP',
      topic: 'Integritas',
      stem: 'Anda bekerja dengan rekan setim yang sering terlambat mengumpulkan tugas. Sikap yang paling tepat adalah ...',
      options: [
        'Memperingatkan di depan rapat',
        'Menegur secara pribadi dengan tegas',
        'Langsung melapor kepada atasan',
        'Mengingatkan secara pribadi dan menawarkan bantuan',
        'Mengabaikan karena bukan tugas Anda'
      ],
      answer: 3,
      explain: 'Komunikasi pribadi yang membangun sambil menawarkan bantuan menyelesaikan masalah tanpa merusak hubungan kerja sama.'
    },
    {
      id: 7,
      section: 'TIU',
      topic: 'Matematika',
      stem: 'Jika 3x - 7 = 14, maka nilai x adalah ...',
      options: ['5', '6', '7', '8', '9'],
      answer: 2,
      explain: '3x = 14 + 7 = 21, sehingga x = 21 / 3 = 7.'
    },
    {
      id: 8,
      section: 'TKP',
      topic: 'Kolaborasi',
      stem: 'Anda menerima tugas yang di luar keahlian Anda dan harus selesai besok. Sikap yang paling tepat adalah ...',
      options: [
        'Menolak dengan halus karena tidak ahli',
        'Menerima tugas lalu meminta bantuan rekan yang ahli',
        'Menerima dan menyelesaikannya sendiri tanpa bantuan',
        'Meminta atasan untuk memindahkan tugas',
        'Menerima tetapi menunda hingga ada waktu luang'
      ],
      answer: 1,
      explain: 'Inisiatif untuk meminta bantuan menunjukkan kerja sama yang baik, sambil tetap bertanggung jawab atas hasil akhir.'
    }
  ]
};
