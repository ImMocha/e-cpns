const guide = (id, audience, category, routes, title, summary, sections, fields = []) => ({
  id, audience, category, routes, title, summary, sections, fields,
});

export const helpGuides = [
  guide('admin-start', 'admin', 'Mulai di sini', ['admin.dashboard'], 'Panduan awal administrator',
    'Kenali hubungan antarfitur dan urutan menyiapkan kelas, dari bidang sampai peserta bisa belajar serta mencetak sertifikat.', [
      ['Urutan menyiapkan pembelajaran', [
        '1. Buat Bidang, misalnya TWK, TIU, atau TKP. 2. Buat Topik di dalam bidang. 3. Tambahkan Materi PDF atau video pada topik. 4. Siapkan Soal & Pembahasan. 5. Buat Latihan pada topik dan Tryout untuk simulasi. 6. Bila berbayar, masukkan konten premium ke Paket Belajar. 7. Periksa hasilnya melalui Mode Peserta.',
        'Bidang adalah kelompok besar; topik adalah subkelompok pembelajaran; materi adalah bahan belajar; soal adalah pertanyaan; sesi latihan/tryout adalah kumpulan soal beserta aturan pengerjaannya. Paket mengatur hak akses, bukan tempat menulis materi atau soal.',
      ]],
      ['Membaca dashboard', [
        'Ringkasan menampilkan peserta, materi, soal, artikel, dan sesi yang tersimpan. Bedakan jumlah bank/sesi yang dibuat dengan jumlah pengerjaan yang selesai. Satu peserta dapat menyelesaikan lebih dari satu sesi.',
        'Grafik aktivitas merangkum sesi latihan dan tryout yang selesai selama enam bulan terakhir. Aktivitas terkini membantu melihat hasil terbaru. Statistik pembaca artikel menunjukkan aktivitas membaca; pembaca unik berbeda dengan jumlah kunjungan.',
      ]],
      ['Draft, terbit, aktif, dan akses premium', [
        'Draft dipakai saat konten belum siap. Terbit membuat konten memenuhi syarat tampil, tetapi bidang/topik dan hak aksesnya juga harus sesuai. Status Aktif pada bidang/topik/paket berbeda dengan Terbit pada materi/soal/sesi.',
        'Premium memerlukan paket peserta yang aktif dan memang memuat konten tersebut. Hanya mengaktifkan paket tanpa memilih isinya belum memberikan akses ke semua materi atau tryout premium.',
      ]],
      ['Navigasi dan bantuan', [
        'Menu samping mengelompokkan Konten, Pengguna, dan Bisnis. Di ponsel, buka ikon menu terlebih dahulu. Mode Peserta membantu memeriksa tampilan pengguna; akun admin tetap dapat memiliki pengecualian akses, sehingga bukan pengganti pengujian dengan akun peserta.',
        'Gunakan pencarian/filter di halaman terkait. Kolom pencarian umum dan ikon notifikasi di bar atas masih berupa antarmuka contoh. Buka Bantuan & Panduan untuk membaca penjelasan atau menjalankan tur halaman tanpa mengubah data.',
      ]],
    ], [
      ['.stat', 'Angka ringkasan', 'Periksa label setiap angka. Jumlah peserta, jumlah konten, dan jumlah sesi selesai mengukur hal yang berbeda.'],
      ['[data-chart]', 'Grafik aktivitas', 'Grafik merangkum data yang ditunjukkan pada judulnya. Gunakan daftar aktivitas di bawah untuk memeriksa sesi individual.'],
      ['.table-wrap', 'Aktivitas dan hasil terbaru', 'Daftar ini membantu mengenali peserta dan sesi terbaru. Pengelolaan konten tetap dilakukan melalui menu masing-masing.'],
    ]),

  guide('admin-sections', 'admin', 'Struktur pembelajaran', ['admin.sections.'], 'Bidang: kelompok utama pembelajaran',
    'Kelola kategori utama seperti TWK, TIU, dan TKP, termasuk identitas, urutan, tampilan, serta statusnya.', [
      ['Cara menambahkan bidang', ['Buka Tambah Bidang, isi nama yang mudah dikenali, periksa kode, tentukan urutan dan tampilan, kemudian simpan. Setelah itu buat topik untuk mengelompokkan materi di dalam bidang.', 'Kode merupakan identitas ringkas yang digunakan bersama data soal dan tampilan bidang. Gunakan nama/kode yang konsisten; misalnya nama Tes Wawasan Kebangsaan dengan kode TWK.']],
      ['Pengaruh status dan urutan', ['Bidang nonaktif tidak ditampilkan sebagai bidang pembelajaran aktif bagi peserta. Materi, topik, atau soal di dalamnya dapat ikut tidak tersedia untuk akses/pengerjaan baru meskipun status kontennya terbit.', 'Angka urutan yang lebih kecil ditampilkan lebih dahulu. Pengaturan warna/ikon membantu membedakan bidang secara visual dan tidak memengaruhi skor atau hak akses.']],
      ['Sebelum menghapus atau menonaktifkan', ['Periksa topik, materi, soal, dan sesi yang menggunakan bidang. Baca pesan konfirmasi atau penolakan aplikasi; penghapusan dapat dibatasi ketika masih ada data terkait. Jangan menghapus bidang hanya untuk menyembunyikan sementara: pertimbangkan status nonaktif.', 'Jika bidang tidak muncul di formulir lain, periksa daftar dan status bidang terlebih dahulu. Selesaikan pesan validasi yang muncul sebelum menganggap perubahan sudah tersimpan.']],
    ], [
      ['#name', 'Nama bidang', 'Tulis nama kategori utama yang dipahami peserta. Topik yang lebih khusus dibuat melalui menu Topik, bukan sebagai bidang baru.'],
      ['#code', 'Kode bidang', 'Kode ringkas untuk identifikasi, misalnya TWK. Bila dikosongkan, aplikasi dapat mengisinya dari nama sesuai aturan formulir.'],
      ['#position', 'Urutan tampil', 'Angka lebih kecil ditampilkan lebih dahulu. Ini tidak menentukan bobot atau nilai ujian.'],
      ['[name="tone"]', 'Warna bidang', 'Pilih warna agar peserta mudah mengenali kelompok materi. Warna tidak mengubah penilaian.'],
      ['[name="is_active"]:not([type="hidden"])', 'Bidang aktif', 'Status ini memengaruhi ketersediaan konten bidang kepada peserta. Periksa dampaknya sebelum menonaktifkan bidang yang sudah digunakan.'],
    ]),

  guide('admin-topics', 'admin', 'Struktur pembelajaran', ['admin.topics.'], 'Topik: susun alur belajar',
    'Topik mengelompokkan materi dan latihan dalam satu bidang agar urutan belajar peserta jelas.', [
      ['Hubungan bidang, topik, dan materi', ['Satu topik berada pada satu bidang. Contoh: Bidang TWK → Topik Nasionalisme → Materi PDF, video, dan latihan Nasionalisme. Pilih bidang dahulu agar penempatan materi tidak salah.', 'Dari kartu topik, gunakan Tambah Materi atau Kelola Latihan. Latihan yang ditautkan ke topik dapat diberi syarat penyelesaian seluruh materi topik.']],
      ['Urutan, status, dan pencarian', ['Gunakan pencarian nama/deskripsi serta filter aktif, nonaktif, atau belum berisi materi. Filter hanya membatasi tampilan daftar; tidak mengubah data.', 'Topik nonaktif beserta latihannya disembunyikan dari peserta. Urutan lebih kecil tampil lebih dahulu. Topik kosong belum memberikan bahan belajar; tambahkan materi yang terbit dan dapat diakses.']],
      ['Memeriksa topik sebelum dipakai', ['Pastikan topik berada di bidang yang tepat dan bidangnya aktif. Bila peserta belum dapat memulai latihan, periksa apakah latihan mensyaratkan penyelesaian materi, termasuk materi premium yang masih terkunci.', 'Perubahan penempatan/status dapat memengaruhi navigasi belajar. Baca konfirmasi sebelum menghapus; selesaikan atau pindahkan hubungan konten sesuai aturan yang ditampilkan aplikasi.']],
    ], [
      ['#section_id', 'Bidang induk', 'Pilih bidang tempat topik ini berada. Materi topik harus menggunakan bidang yang sesuai.'],
      ['#name', 'Nama topik', 'Gunakan pokok bahasan yang spesifik, misalnya Nasionalisme atau Silogisme.'],
      ['#description', 'Deskripsi topik', 'Jelaskan apa yang dipelajari serta cakupan bahasannya agar topik mudah dikenali.'],
      ['#position', 'Urutan belajar', 'Nomor kecil tampil lebih awal di dalam bidang. Susun dari pengantar menuju pembahasan lanjutan.'],
      ['[name="is_active"]:not([type="hidden"])', 'Status topik', 'Topik nonaktif dan latihan yang berada di dalamnya tidak ditampilkan sebagai pembelajaran aktif bagi peserta.'],
    ]),

  guide('admin-materials', 'admin', 'Struktur pembelajaran', ['admin.materials.index', 'admin.materials.create', 'admin.materials.edit', 'admin.materials.show'], 'Materi PDF dan video',
    'Tambahkan bahan belajar, tentukan penempatannya, lalu atur sumber, status terbit, dan hak akses peserta.', [
      ['Alur menyiapkan materi', ['Pilih bidang dan topik, isi judul serta ringkasan, pilih format PDF/video, lengkapi sumber, atur gratis/premium, kemudian simpan. Gunakan draft selama sumber atau isi belum siap.', 'Judul minimal 5 karakter dan harus unik dalam bidang yang sama. Slug merupakan alamat materi; pilih alamat yang mudah dibaca dan hindari perubahan alamat konten yang sudah dibagikan.']],
      ['Sumber PDF dan video', ['PDF: unggah berkas PDF maksimal 20 MB. Berkas disimpan secara privat dan hak peserta diperiksa saat mengaksesnya. Saat mengedit, berkas lama dapat tetap dipakai jika tidak diganti.', 'Video: isi URL sumber yang dapat diputar browser. Tautan halaman biasa belum tentu merupakan URL video yang bisa diputar. Jumlah halaman PDF dan durasi video adalah label informasi, bukan pembatas berkas atau waktu belajar.']],
      ['Kapan materi terlihat dan terbuka?', ['Materi harus terbit, memiliki sumber, dan berada di bidang/topik yang aktif. Materi premium memerlukan paket aktif yang memuat materi tersebut; status Terbit saja tidak membuka akses berbayar.', 'Pilih sumber sesuai format. Formulir video tidak menyimpan PDF sebagai sumber dan formulir PDF tidak menggunakan URL video. Periksa kembali bila mengganti format materi.']],
      ['Progres dan masalah umum', ['Peserta menandai materi selesai melalui halaman materi. Menonton atau membuka halaman saja tidak selalu sama dengan menandainya selesai. Progres tersebut dapat menjadi prasyarat latihan pada topik.', 'Jika materi tidak muncul, periksa status, bidang/topik, filter daftar, dan akses paket. Jika sumber gagal dibuka, periksa berkas/URL aslinya. Kesalahan validasi berarti perubahan belum berhasil disimpan.']],
    ], [
      ['#title', 'Judul materi', 'Minimal 5 karakter dan unik dalam bidang ini. Gunakan nama yang menggambarkan isi bahan belajar.'],
      ['#summary', 'Ringkasan', 'Jelaskan tujuan belajar, isi utama, dan siapa yang perlu membaca materi ini. Ringkasan membantu peserta memilih bahan belajar.'],
      ['#slug', 'Alamat materi', 'Slug dipakai pada tautan materi. Periksa keunikannya dan dampak perubahan terhadap tautan yang sudah dibagikan.'],
      ['#section_id', 'Bidang materi', 'Pilih kelompok utama materi. Pilihan topik mengikuti bidang ini.'],
      ['#topic_id', 'Topik materi', 'Topik wajib berada di dalam bidang yang dipilih. Buat topik terlebih dahulu jika belum tersedia.'],
      ['#type', 'Format sumber', 'PDF memakai unggahan berkas, sedangkan video memakai URL. Lengkapi sumber yang sesuai setelah memilih format.'],
      ['#access_level', 'Gratis atau premium', 'Premium hanya terbuka melalui paket aktif yang memuat materi ini. Gratis dapat diakses peserta sesuai status terbit dan struktur pembelajaran.'],
      ['#position', 'Urutan dalam topik', 'Angka kecil tampil lebih dahulu. Atur urutan materi agar alur belajar jelas.'],
      ['#status', 'Draft atau terbit', 'Draft tidak ditampilkan untuk peserta. Materi terbit wajib memiliki sumber yang valid; bidang dan topik juga perlu aktif.'],
      ['#sumberKartu', 'Sumber konten', 'Untuk PDF, unggah PDF maksimal 20 MB. Untuk video, gunakan URL yang dapat diputar browser. Label halaman/durasi hanya memberi informasi kepada peserta.'],
    ]),

  guide('admin-quizzes', 'admin', 'Soal dan ujian', ['admin.materials.quizzes.'], 'Latihan materi dan prasyarat belajar',
    'Buat latihan untuk menguji pemahaman materi, dengan topik, akses, timer opsional, dan kumpulan soal sendiri.', [
      ['Latihan berbeda dengan tryout', ['Latihan dipakai untuk memahami materi dan hasilnya dikonversi ke skala 0–100. Latihan premium tidak mengurangi kuota tryout. Sertifikat kelulusan diberikan untuk tryout yang lulus, bukan untuk latihan.', 'Latihan bisa ditautkan ke topik atau menjadi latihan umum tanpa topik. Bila bertopik, latihan muncul bersama alur materi topik tersebut.']],
      ['Syarat penyelesaian topik', ['Pilihan Kapan saja memungkinkan peserta memulai sesuai aksesnya. Pilihan Setelah semua materi topik ditandai selesai mewajibkan penyelesaian seluruh materi terbit di topik, termasuk materi premium.', 'Jika memilih syarat selesai, tentukan topik terlebih dahulu. Pastikan paket peserta memuat materi premium prasyarat. Sesi yang sudah dimulai tetap dapat dilanjutkan sesuai aturan sesi.']],
      ['Jumlah soal, durasi, dan penilaian', ['Jumlah soal per pengerjaan harus didukung oleh soal terbit yang cukup di kumpulan soal sesi dan bidang aktif. Soal tersimpan di bank saja belum otomatis menjadi bagian latihan ini.', 'Durasi 0 berarti tanpa timer pada latihan; durasi positif memberi batas waktu. Atur poin benar, penalti salah, dan nilai ambang dalam skala hasil latihan 0–100. Acak soal/pilihan berlaku pada pengerjaan baru dan urutannya tetap selama sesi berjalan.']],
      ['Simpan dan terbitkan', ['Simpan draft untuk persiapan. Simpan & terbitkan memeriksa kesiapan sesi, termasuk ketersediaan soal. Jika kurang soal terbit, tambah/pilih soal atau sesuaikan jumlah soal.', 'Pengaturan dan soal pada sesi yang sudah dimulai disimpan sebagai salinan saat mulai. Perubahan setelahnya berlaku pada pengerjaan berikutnya, sehingga hasil lama tidak dihitung ulang.']],
    ]),

  guide('admin-exams', 'admin', 'Soal dan ujian', ['admin.exams.'], 'Tryout CAT, penilaian, dan sertifikat',
    'Atur simulasi ujian dari kumpulan soal, durasi, skor, passing grade, hingga syarat penerbitan sertifikat.', [
      ['Urutan membuat tryout', ['Isi nama, alamat sesi, deskripsi, level akses, jumlah soal, dan durasi. Atur penilaian serta randomisasi. Pilih soal pada Kumpulan soal sesi, lalu simpan draft atau terbitkan.', 'Alamat sesi memakai huruf kecil, angka, dan tanda hubung, misalnya tryout-skd-oktober. Jenis dan alamat sesi tidak dapat diganti setelah dibuat. Durasi tryout wajib 1–240 menit dan jumlah soal 1–200.']],
      ['Kumpulan soal sesi dan editor langsung', ['Centang soal yang akan digunakan. Gunakan pencarian, filter bidang, atau Hanya soal dipilih untuk memeriksa pilihan. Jumlah soal per pengerjaan bukan jumlah seluruh soal di bank; hanya soal terpilih yang memenuhi syarat dapat dipakai.', 'Tambah/Edit soal di dalam sesi menyimpan soal ke bank soal. Setelah itu simpan sesi untuk menerapkan pilihan kumpulannya. Mengubah soal yang dipakai beberapa sesi dapat memengaruhi pengerjaan baru di sesi lainnya; periksa penggunaan soal.']],
      ['Cara menghitung nilai', ['Soal biasa: jawaban benar mendapat poin benar, salah mengurangi nilai sesuai penalti, dan kosong bernilai 0. Skor akhir tidak kurang dari 0. Soal berbobot memakai skor pilihan 1–5 dan tidak memakai penalti salah.', 'Tryout menggunakan skor poin; maksimum bergantung pada jumlah dan jenis soal. Nilai ambang tidak boleh melebihi maksimum sesi. Jika soal diacak dari campuran jenis, periksa kembali komposisi dan rentang skor yang mungkin diperoleh peserta.']],
      ['Passing grade dan sertifikat', ['Passing grade adalah nilai ambang sesi, bukan angka tetap untuk semua tryout. Contoh: ambang 60 dari maksimum 100 berarti nilai 60 sudah memenuhi syarat. Jangan menganggap semua sesi memakai ambang resmi seleksi CPNS.', 'Sertifikat tersedia hanya untuk tryout selesai yang nilainya mencapai atau melebihi passing grade. Bila nilai ambang dikosongkan, sertifikat tidak diterbitkan. Sertifikat membuktikan pencapaian simulasi, bukan kelulusan seleksi CPNS.']],
      ['Akses, perubahan, dan pemeriksaan akhir', ['Tryout premium memerlukan paket aktif yang memuat sesi serta kuota tryout tersisa. Sesi gratis tidak memotong kuota paket. Masukkan tryout premium ke paket yang sesuai setelah menyiapkannya.', 'Pastikan jumlah soal terbit mencukupi, bidang soal aktif, kunci/bobot benar, durasi masuk akal, dan passing grade sesuai skala. Perubahan pengaturan berlaku untuk pengerjaan baru; sesi lama memakai salinan soal dan aturan saat dimulai.']],
    ]),

  guide('admin-questions', 'admin', 'Soal dan ujian', ['admin.questions.'], 'Bank soal, pilihan, kunci, dan pembahasan',
    'Tulis soal yang jelas, periksa penilaian setiap pilihan, lalu tentukan sesi latihan atau tryout yang menggunakannya.', [
      ['Membuat soal yang siap digunakan', ['Isi judul internal untuk pencarian, pilih bidang dan tingkat kesulitan, tulis pertanyaan, lengkapi pilihan, tentukan kunci/skor, serta tulis pembahasan. Gunakan pratinjau untuk memeriksa tampilan peserta.', 'Judul ringkas membantu pengelolaan bank soal; pertanyaan lengkap ditulis di area isi soal. Format teks dapat dipakai untuk penekanan, daftar, dan keterbacaan. Pastikan pilihan berbeda serta tidak memberi petunjuk jawaban secara tidak sengaja.']],
      ['Soal biasa dan soal berbobot', ['Soal biasa memiliki satu pilihan benar. Klik penanda pilihan untuk menetapkan kunci; poin benar dan penalti salah berasal dari pengaturan sesi.', 'Soal berbobot memberi nilai 1–5 pada setiap pilihan, sesuai kecocokan jawaban. Jawaban kosong bernilai 0 dan tidak terkena penalti salah. Periksa setiap bobot, termasuk pilihan terbaik, sebelum menerbitkan. Jangan hanya melihat huruf kunci.']],
      ['Media dan pengacakan pilihan', ['Gambar/video membantu menjelaskan soal. Periksa pratinjau setelah mengunggah, dan ikuti batas jenis/ukuran yang ditampilkan formulir. Jangan memasukkan informasi kunci jawaban pada nama/deskripsi media.', 'Jika ilustrasi sudah memuat label A–E atau opsi merujuk urutan tertentu, matikan izin pengacakan pilihan pada soal. Pengaturan ini mencegah arti opsi berubah ketika sesi meminta randomisasi.']],
      ['Sesi tujuan dan soal bersama', ['Centang sesi tujuan agar soal masuk ke kumpulan latihan/tryout tersebut. Satu soal dapat dipakai beberapa sesi. Menyimpan soal tidak membuat sesi draft otomatis terbit; periksa juga pengaturan sesi.', 'Soal harus terbit dan bidangnya aktif untuk diambil dalam pengerjaan baru. Mengedit soal bersama memengaruhi pengambilan berikutnya pada sesi terkait. Hasil/sesi yang sudah dimulai tetap memakai salinan sebelumnya.']],
      ['Pembahasan dan kendala umum', ['Pembahasan sebaiknya menjelaskan langkah penyelesaian dan alasan jawaban, bukan sekadar menyebut huruf kunci. Hak melihat pembahasan pada sisi peserta mengikuti aturan akses paket; kunci dan hasil tetap mengikuti sesi.', 'Jika soal tidak tersedia pada tryout, periksa status terbit, bidang aktif, pilihan sesi tujuan, dan filter daftar. Jika penyimpanan gagal, baca semua pesan validasi; pratinjau bukan tanda bahwa data telah tersimpan.']],
    ], [
      ['#question-title', 'Judul internal soal', 'Gunakan judul yang memudahkan pencarian dan membedakan soal, misalnya Pola Bilangan Berlipat. Isi pertanyaan ditulis di area terpisah.'],
      ['#question-section', 'Bidang soal', 'Bidang mengelompokkan soal dan harus aktif agar soal terbit dapat digunakan untuk sesi baru.'],
      ['#question-level', 'Kesulitan', 'Label kesulitan membantu mencari dan menyusun variasi soal; label ini tidak otomatis menambah poin.'],
      ['#sesi-tujuan', 'Sesi tujuan', 'Satu soal bisa dipakai beberapa latihan/tryout. Centang tujuan lalu simpan soal. Periksa dampaknya saat mengubah soal yang digunakan bersama.'],
      ['[data-question-rich-text][data-name="body"]', 'Isi pertanyaan', 'Tulis pertanyaan lengkap dan konteksnya. Gunakan pratinjau untuk memeriksa format dan keterbacaan.'],
      ['#question-scoring', 'Jenis penilaian', 'Biasa: satu kunci, nilai mengikuti sesi. Berbobot: setiap pilihan punya skor 1–5, jawaban kosong 0, tanpa penalti salah.'],
      ['[data-option-list]', 'Pilihan dan penilaian', 'Isi semua pilihan. Pada soal biasa pilih satu kunci; pada soal berbobot periksa nilai setiap pilihan.'],
      ['[data-shuffle-options]', 'Izin pengacakan pilihan', 'Matikan jika gambar atau isi pertanyaan merujuk label A–E/urutan pilihan tertentu.'],
      ['[data-question-rich-text][data-name="explanation"]', 'Pembahasan', 'Jelaskan alasan dan langkah penyelesaian. Hak akses pembahasan peserta dapat bergantung pada paket aktif.'],
      ['.question-preview', 'Pratinjau soal', 'Periksa pertanyaan, media, pilihan, dan pembahasan sebelum menyimpan. Pratinjau tidak menyimpan data.'],
    ]),

  guide('admin-articles', 'admin', 'Publikasi', ['admin.articles.'], 'Artikel dan editor publikasi',
    'Tulis artikel, atur identitas serta cover, periksa pratinjau, lalu simpan draft atau terbitkan.', [
      ['Identitas dan isi', ['Isi judul, bidang, cuplikan singkat, alamat artikel, serta cover bila diperlukan. Cuplikan membantu pembaca memahami isi sebelum membuka artikel; bukan pengganti isi lengkap.', 'Gunakan editor untuk paragraf, judul bagian, format tebal/miring, daftar, tautan, dan tabel. Teks dapat disusun bertahap; simpan draft agar pekerjaan tersimpan sebelum artikel siap dipublikasikan.']],
      ['Cover, tautan, dan pratinjau', ['Unggah cover sesuai jenis/ukuran yang diterima formulir. Hapus cover lama hanya bila memang ingin menghilangkannya. Perubahan cover dan isi baru berlaku setelah penyimpanan berhasil.', 'Pratinjau menunjukkan susunan artikel, tetapi tidak membuktikan semua tautan eksternal benar. Periksa tautan tujuan dan keterbacaan di layar kecil. Jangan menaruh informasi privat peserta dalam artikel publik.']],
      ['Draft, terbit, dan statistik', ['Draft dipakai untuk artikel yang belum siap. Terbit membuat artikel tersedia sesuai aturan publikasi aplikasi. Gunakan status yang tepat ketika memperbarui artikel yang sudah dibaca pengguna.', 'Jumlah kunjungan dan pembaca unik mengukur hal berbeda. Dashboard menampilkan ringkasan serta artikel paling banyak dibaca. Bila artikel tidak muncul, periksa status dan bidang terkait.']],
    ], [
      ['#article-title', 'Judul artikel', 'Tulis judul yang jelas dan sesuai isi, maksimal sesuai batas formulir.'],
      ['#article-section', 'Bidang artikel', 'Pilih bidang yang sesuai agar artikel mudah ditemukan dalam kategorinya.'],
      ['#article-excerpt', 'Cuplikan', 'Ringkas manfaat dan pokok isi; cuplikan ditampilkan sebagai pengantar artikel.'],
      ['#article-slug', 'Alamat artikel', 'Alamat dapat diisi dari judul. Perhatikan tautan yang sudah dibagikan sebelum mengubahnya.'],
      ['#article-cover', 'Gambar cover', 'Pilih JPEG, PNG, atau WebP sesuai batas formulir, lalu periksa pratinjau.'],
      ['[data-editor-content]', 'Editor isi', 'Susun paragraf dan heading. Toolbar membantu format, tautan, daftar, serta tabel. Pratinjau bukan penyimpanan.'],
      ['[data-preview-title]', 'Pratinjau publikasi', 'Periksa tampilan judul dan isi sebelum memilih Simpan draft atau Terbitkan.'],
    ]),

  guide('admin-users', 'admin', 'Peserta dan akses', ['admin.users.'], 'Akun peserta, peran, dan status',
    'Kelola identitas akun, verifikasi email, hak admin/peserta, status aktif, serta data provinsi untuk leaderboard.', [
      ['Menambahkan dan mengedit akun', ['Isi nama lengkap serta email yang unik. Pada akun baru, isi password dan konfirmasi yang sama. Saat mengedit, biarkan password kosong jika tidak ingin menggantinya.', 'Nama pada profil dipakai pada tampilan peserta dan sertifikat. Telepon, instansi, alamat, serta provinsi membantu identifikasi; provinsi juga digunakan untuk peringkat lokal. Periksa data sebelum menyimpan.']],
      ['Peran, status aktif, dan verifikasi email', ['Peran menentukan kewenangan. Peserta memakai area belajar; Admin dapat mengelola data aplikasi. Berikan peran admin hanya kepada pengelola yang memang berwenang.', 'Status Aktif berbeda dengan email terverifikasi. Akun nonaktif dibatasi aksesnya, sedangkan akun yang belum memverifikasi email diarahkan ke alur verifikasi. Menandai email terverifikasi secara manual harus didasarkan pada pemeriksaan admin.']],
      ['Paket bukan bagian dari peran akun', ['Mengubah peran atau status aktif tidak otomatis membeli/memberikan paket premium. Pemberian paket manual dilakukan dari pengelolaan Paket Belajar menggunakan email peserta dan alasan pemberian.', 'Jika peserta tidak dapat mengakses materi, periksa status/verifikasi akun dahulu, lalu paket, masa aktif, isi paket, dan kuota tryout. Jangan langsung mengubah peserta menjadi admin untuk mengatasi kendala akses.']],
      ['Pencarian dan tindakan sensitif', ['Gunakan filter/pencarian daftar untuk menemukan akun sebelum mengubahnya. Cocokkan nama dan email agar tidak salah peserta.', 'Baca pesan konfirmasi sebelum menonaktifkan atau menghapus akun. Perubahan akun berdampak pada akses pengguna; aplikasi dapat menolak tindakan tertentu untuk melindungi akun admin yang sedang digunakan atau data terkait.']],
    ], [
      ['#name', 'Nama lengkap', 'Nama dipakai pada profil dan sertifikat. Gunakan nama yang ingin dicantumkan peserta.'],
      ['#email', 'Email akun', 'Email dipakai untuk masuk dan menemukan akun saat aktivasi paket. Pastikan unik serta benar.'],
      ['#password', 'Password', 'Minimal 8 karakter sesuai formulir. Saat mengedit, kosongkan agar password lama tetap digunakan.'],
      ['#password_confirmation', 'Konfirmasi password', 'Isi sama persis dengan password baru. Pada edit tanpa perubahan password, keduanya dapat dikosongkan.'],
      ['[name="email_verified"]', 'Verifikasi email', 'Status ini berbeda dengan akun aktif. Centang hanya setelah admin memastikan kepemilikan email.'],
      ['#province', 'Provinsi', 'Dipakai untuk penempatan leaderboard lokal peserta. Pilih sesuai data peserta.'],
      ['[name="role"]', 'Peran akun', 'Admin memiliki kewenangan pengelolaan data. Peserta tidak perlu diubah menjadi admin untuk membuka paket premium.'],
      ['[name="status"]', 'Status akun', 'Nonaktif membatasi akses peserta. Status aktif tidak menggantikan verifikasi email atau paket premium.'],
    ]),

  guide('admin-packages', 'admin', 'Peserta dan akses', ['admin.packages.'], 'Paket belajar, kuota, dan aktivasi peserta',
    'Rangkai konten premium, tentukan harga serta masa aktif, lalu pantau atau berikan akses paket kepada peserta.', [
      ['Menyusun isi paket', ['Isi nama dan deskripsi yang menjelaskan manfaat paket. Pilih materi/video dan sesi latihan/tryout yang termasuk paket. Paket membuka konten premium yang dipilih, bukan seluruh konten premium dalam aplikasi.', 'Siapkan konten terbit sebelum mengaktifkan paket untuk katalog. Konten gratis tetap dapat diakses sesuai aturannya dan tidak memotong kuota paket. Mengubah akses konten menjadi premium dilakukan pada pengaturan kontennya.']],
      ['Harga, masa aktif, dan kuota', ['Harga diisi dalam rupiah tanpa titik pemisah: 99000 berarti Rp99.000. Masa aktif dihitung sejak aktivasi paket peserta; kosong berarti tanpa batas waktu.', 'Kuota tryout kosong berarti tanpa batas, 0 berarti tidak menyediakan jatah tryout, dan angka positif membatasi total penggunaan. Latihan tidak memotong kuota tryout. Memulai sesi tryout premium baru dapat menggunakan kuota; melanjutkan sesi yang sama bukan pembelian paket baru.']],
      ['Leaderboard dan status katalog', ['Akses lokal mengikuti provinsi peserta. Akses nasional mencakup lokal juga. Hak tersebut berlaku selama paket peserta aktif sesuai ketentuan saat aktivasi.', 'Menonaktifkan paket menghentikan aktivasi baru melalui katalog; hak peserta yang sudah aktif tetap mengikuti ketentuan saat aktivasi. Mengarsipkan paket tidak sama dengan mencabut akses setiap peserta.']],
      ['Paket peserta dan pemberian manual', ['Pada paket yang sudah dibuat dan aktif, masukkan email akun peserta serta alasan pemberian, kemudian Berikan paket. Masa aktif dimulai saat aktivasi. Aktivasi ganda untuk paket yang sama dan masih aktif ditolak.', 'Gunakan tabel Paket Peserta untuk memeriksa masa aktif, sisa kuota, status, dan catatan. Pencabutan akses adalah tindakan terpisah; cocokkan peserta dan baca konfirmasi sebelum melanjutkan.']],
      ['Perubahan paket dan masalah akses', ['Paket peserta menyimpan salinan ketentuan/isinya. Untuk pembelian, rincian dicatat saat checkout; pemberian manual memakai ketentuan saat aktivasi admin. Perubahan katalog setelahnya tidak otomatis menambah isi, masa aktif, atau kuota pada paket peserta lama.', 'Jika peserta masih terkunci, periksa paket peserta yang benar, status aktif, tanggal berakhir, isi yang menjadi haknya, dan sisa kuota. Memasukkan konten ke paket hari ini belum tentu menambahkannya ke paket yang dibeli atau diaktifkan sebelumnya.']],
    ], [
      ['#package-name', 'Nama paket', 'Nama ditampilkan di katalog dan ketentuan paket peserta. Buat nama yang membedakan paket.'],
      ['#package-description', 'Deskripsi manfaat', 'Jelaskan cakupan materi/sesi, masa aktif, dan manfaat tanpa menjanjikan konten yang tidak dipilih.'],
      ['#package-price', 'Harga rupiah', 'Isi angka rupiah tanpa titik: 99000 = Rp99.000. Nilai 0 adalah paket tanpa biaya.'],
      ['#package-duration', 'Masa aktif', 'Dihitung dalam hari sejak aktivasi. Kosong berarti tanpa batas waktu, bukan 0 hari.'],
      ['#package-quota', 'Total kuota tryout', 'Kosong = tanpa batas; 0 = tanpa jatah tryout; angka positif = batas total penggunaan. Latihan tidak memotong kuota ini.'],
      ['#package-leaderboard', 'Hak leaderboard', 'Lokal mengikuti provinsi peserta; nasional mencakup lokal. Hak disalin saat aktivasi dan berlaku selama paket aktif.'],
      ['[name="is_active"]:not([type="hidden"])', 'Tampilkan di katalog', 'Paket aktif dapat diaktifkan peserta sesuai aturan katalog. Menonaktifkan tidak mencabut hak paket yang sudah aktif.'],
      ['[name="material_ids[]"]', 'Materi yang termasuk', 'Pilih materi premium yang benar-benar termasuk paket. Isi baru berlaku untuk aktivasi paket berikutnya.'],
      ['[name="exam_ids[]"]', 'Sesi yang termasuk', 'Pilih latihan dan tryout yang menjadi hak paket. Kuota tetap diperiksa untuk memulai tryout premium.'],
      ['#grant-email', 'Aktivasi manual', 'Gunakan email akun peserta terdaftar. Paket harus aktif dan aktivasi ganda paket yang sama tidak diperbolehkan selama masih aktif.'],
      ['#grant-note', 'Alasan pemberian', 'Catat alasan yang dapat dipahami pengelola lain. Pemberian paket manual terpisah dari transaksi pembayaran.'],
    ]),

  guide('admin-transactions', 'admin', 'Bisnis', ['admin.transactions.'], 'Memantau pembayaran dan aktivasi paket',
    'Cocokkan status pembayaran dengan hak paket peserta dan bedakan transaksi produksi, sandbox, serta paket gratis.', [
      ['Mencari transaksi yang benar', ['Cari nama, email, atau nomor pesanan, lalu gunakan filter status. Nomor pesanan membantu membedakan beberapa pembelian dari peserta yang sama.', 'Kolom paket/total memakai rincian paket pada saat transaksi. Harga paket sekarang dapat berbeda dari harga yang dibayar pada pesanan lama.']],
      ['Status pembayaran dan status akses', ['Status pembayaran menunjukkan proses pesanan, misalnya menunggu, lunas, kedaluwarsa, dibatalkan, atau gagal sesuai status yang tersedia. Akses paket ditampilkan di kolom berbeda; jangan menyamakan status pembayaran dengan masa aktif akses saat ini.', 'Pembayaran yang berhasil diproses dapat mengaktifkan paket. Paket yang pernah dibayar tetap bisa kedaluwarsa atau dicabut. Gunakan kolom masa aktif untuk menilai hak peserta saat ini.']],
      ['Production, Sandbox, dan paket gratis', ['Production adalah lingkungan pembayaran nyata; Sandbox digunakan untuk pengujian dan bukan bukti dana nyata masuk. Paket gratis tidak melewati pembayaran berbayar.', 'Halaman admin ini memantau data; tidak menyediakan tombol untuk mengubah transaksi menjadi lunas secara manual. Jika status belum berubah, peserta dapat memeriksa status dari detail transaksi. Verifikasi pesanan/lingkungan yang benar sebelum memberi akses manual lewat paket.']],
    ], [
      ['#transaction-search', 'Cari pesanan', 'Masukkan nama, email, atau nomor pesanan agar tidak tertukar dengan pembelian lain.'],
      ['#transaction-status', 'Filter status', 'Status pembayaran berbeda dengan status akses paket. Gunakan filter untuk memfokuskan daftar, lalu periksa kolom akses.'],
      ['.table-wrap', 'Rincian transaksi', 'Periksa pesanan, peserta, total, status pembayaran, lingkungan Production/Sandbox, dan hak paket secara bersamaan.'],
    ]),

  guide('admin-leaderboard', 'admin', 'Peserta dan akses', ['admin.leaderboard-simulation.'], 'Leaderboard dan peserta virtual',
    'Kelola tampilan profil contoh berlabel Virtual pada peringkat lokal dan nasional tanpa mengubah hasil peserta nyata.', [
      ['Apa itu peserta virtual?', ['Peserta virtual adalah profil contoh dengan skor contoh untuk mengisi papan peringkat awal. Profil ini diberi label Virtual di sisi peserta dan bukan hasil ujian peserta nyata.', 'Peringkat peserta nyata dihitung dari persentase skor terbaik tryout selesai. Menampilkan profil virtual dapat memengaruhi posisi yang terlihat karena profil contoh ikut dalam papan peringkat.']],
      ['Aktifkan atau nonaktifkan', ['Tombol utama menampilkan atau menyembunyikan seluruh profil virtual. Profil dapat dibuat saat pertama kali diaktifkan. Ketika dimatikan, profil contoh tidak ikut peringkat lokal/nasional.', 'Menonaktifkan tidak menghapus nilai atau riwayat peserta nyata. Profil virtual tetap tersimpan untuk dipakai kembali; kondisi tersimpan tidak selalu berarti sedang ditampilkan.']],
      ['Jumlah acak per provinsi', ['Isi minimum dan maksimum 1–100 dengan minimum tidak melebihi maksimum. Setiap provinsi mendapat jumlah dalam rentang tersebut; jumlah yang sudah sesuai dipertahankan agar susunan relatif stabil.', 'Simpan jumlah memperbarui rentang populasi, bukan otomatis membalik status aktif/nonaktif. Periksa status tampilan dan tabel sebaran provinsi setelah penyimpanan.']],
    ], [
      ['form:has([name="enabled"]):not(:has(#virtual-minimum))', 'Status peserta virtual', 'Tombol ini benar-benar mengubah apakah profil contoh ditampilkan. Tur hanya menjelaskan dan tidak menekan tombolnya.'],
      ['#virtual-minimum', 'Minimum per provinsi', 'Batas bawah jumlah profil contoh: 1–100. Harus tidak lebih besar daripada maksimum.'],
      ['#virtual-maximum', 'Maksimum per provinsi', 'Batas atas: 1–100. Jumlah per provinsi dapat berbeda dalam rentang yang dipilih.'],
      ['.table-wrap', 'Contoh profil dan sebaran', 'Nama dan skor contoh tidak membuktikan aktivitas belajar nyata. Periksa label Virtual saat membaca peringkat.'],
    ]),

  guide('admin-vouchers', 'admin', 'Bisnis', ['admin.vouchers.'], 'Voucher: mengenali tampilan contoh',
    'Halaman voucher saat ini merupakan demonstrasi antarmuka, bukan pengelolaan diskon yang tersimpan dan diterapkan pada pembayaran.', [
      ['Batas fungsi saat ini', ['Daftar voucher dan angka ringkasan pada halaman ini adalah data contoh. Formulir Buat Voucher memakai pengiriman demo; pesan berhasil tidak berarti voucher tersimpan di database.', 'Tombol ubah/nonaktifkan serta modal memperlihatkan rancangan pengelolaan. Jangan menjanjikan kode diskon kepada peserta berdasarkan isi halaman ini.']],
      ['Arti kolom rancangan', ['Nominal adalah potongan rupiah, misalnya 20000 berarti Rp20.000. Persentase adalah potongan berdasarkan persen. Tanggal, kuota, dan minimum transaksi menggambarkan batas penggunaan yang direncanakan.', 'Ketentuan penerapan pada checkout, validasi kuota/tanggal, penyimpanan, dan perhitungan diskon belum menjadi fitur aktif halaman ini. Gunakan harga paket yang benar-benar tersimpan untuk transaksi saat ini.']],
      ['Cara menggunakan halaman dengan tepat', ['Pencarian tabel membantu memeriksa data contoh yang ditampilkan. Buka modal bila ingin memahami rancangan kolom, tetapi jangan menganggap perubahan di modal sebagai kebijakan pembayaran yang aktif.', 'Untuk memberikan akses khusus saat ini, gunakan aktivasi paket manual di Paket Belajar dengan alasan yang jelas. Tindakan tersebut memberi akses paket dan bukan penerapan voucher/diskon transaksi.']],
    ], [
      ['.page-head', 'Voucher masih demonstrasi', 'Data dan form di halaman ini merupakan contoh. Pesan demo tidak menyimpan voucher dan tidak mengubah total checkout.'],
      ['#tabelVoucher', 'Daftar contoh', 'Kode, nilai, tanggal, kuota, dan angka pemakaian di sini bukan laporan transaksi voucher nyata.'],
    ]),

  guide('member-dashboard', 'member', 'Mulai belajar', ['dashboard'], 'Mulai belajar di Ganesha Nusa',
    'Kenali materi, latihan, tryout, riwayat, paket, dan sertifikat agar bisa memilih langkah belajar berikutnya.', [
      ['Urutan belajar yang disarankan', ['Lengkapi profil, terutama nama dan provinsi. Buka Materi, pilih bidang/topik, pelajari PDF atau video, lalu tandai materi yang sudah dipahami sebagai selesai.', 'Gunakan latihan untuk menguji pemahaman dan tryout untuk simulasi ujian. Periksa hasil serta riwayat, lalu pelajari kembali bagian yang masih lemah. Sertifikat tersedia untuk tryout selesai yang mencapai passing grade.']],
      ['Gratis dan premium', ['Konten gratis dapat dibuka sesuai statusnya. Konten premium memerlukan paket aktif yang memang memuat konten tersebut. Paket bisa memiliki masa aktif dan kuota tryout.', 'Buka Paket Belajar untuk memilih paket, Transaksi untuk memantau pembayaran, dan halaman profil untuk memeriksa data pribadi. Akun aktif dan email terverifikasi tetap diperlukan untuk area belajar.']],
      ['Menggunakan panduan', ['Bantuan & Panduan berisi penjelasan seluruh menu peserta. Cari kata seperti sertifikat, kuota, timer, atau pembayaran untuk menemukan pembahasan yang relevan.', 'Tur halaman menjelaskan tombol dan bagian yang terlihat tanpa mengisi, mengirim jawaban, membeli paket, atau mengubah data. Kolom pencarian umum dan ikon notifikasi di bar atas masih berupa antarmuka contoh; gunakan pencarian pada halaman fitur.']],
    ]),

  guide('member-materials', 'member', 'Belajar dan berlatih', ['materials.'], 'Materi, PDF, video, dan progres',
    'Temukan bahan belajar per bidang/topik, baca atau tonton sumbernya, lalu kelola tanda selesai.', [
      ['Menemukan dan membuka materi', ['Pilih bidang/topik serta filter yang tersedia. Buka kartu materi untuk membaca PDF atau menonton video. Label jumlah halaman dan durasi membantu memperkirakan waktu belajar.', 'Jika materi terkunci, periksa apakah paket aktif kamu memuat materi tersebut. Paket lain yang juga aktif belum tentu membuka konten yang sama.']],
      ['Pembaca PDF dan video', ['Gunakan kontrol halaman dan perbesaran PDF yang tersedia untuk membaca dengan nyaman. Video memakai sumber yang disiapkan pengelola; koneksi atau URL sumber yang tidak tersedia dapat menyebabkan pemutaran gagal.', 'Jika PDF/video tidak terbuka, muat ulang saat koneksi stabil dan pastikan sesi login serta hak akses masih berlaku. Bila tetap gagal, catat judul materi dan pesan yang muncul untuk disampaikan kepada pengelola.']],
      ['Tandai selesai dan latihan topik', ['Tandai selesai setelah mempelajari materi. Membuka halaman atau menonton video saja tidak otomatis berarti progres sudah ditandai selesai. Tanda selesai dapat dibatalkan jika ingin mempelajari ulang.', 'Sebagian latihan hanya dapat dimulai setelah seluruh materi terbit pada topik ditandai selesai, termasuk materi premium. Jika masih terkunci, periksa materi yang belum selesai dan hak paketmu.']],
    ]),

  guide('member-question-bank', 'member', 'Belajar dan berlatih', ['question-bank.'], 'Bank soal dan latihan satu per satu',
    'Cari soal sesuai bidang/kesulitan, jawab satu soal, lalu periksa umpan balik dan pembahasannya sesuai hak akses.', [
      ['Mencari dan menjawab', ['Gunakan filter yang tersedia, lalu buka soal. Baca pertanyaan dan media sebelum memilih jawaban. Kirim pilihan melalui tombol jawaban pada halaman untuk mendapat umpan balik.', 'Soal biasa memiliki satu kunci benar, sedangkan soal berbobot memiliki skor pilihan. Hasil satu soal pada bank soal berbeda dari hasil sesi tryout bertimer.']],
      ['Kunci dan pembahasan', ['Kunci membantu memeriksa pilihan; pembahasan menjelaskan langkah atau alasan. Akses pembahasan lengkap mengikuti ketentuan paket aktif yang berlaku di aplikasi.', 'Jika soal berubah saat dibuka, aplikasi dapat meminta kamu membaca ulang versi terbaru sebelum menjawab. Ini mencegah penilaian jawaban terhadap isi soal yang sudah berbeda.']],
      ['Jika soal tidak ditemukan', ['Periksa filter pencarian dan kembali ke daftar. Soal yang belum terbit atau bidangnya nonaktif dapat tidak tersedia. Jika media gagal, periksa koneksi dan muat ulang halaman.', 'Untuk simulasi banyak soal dengan nilai akhir, gunakan Latihan atau Tryout CAT. Bank soal cocok untuk membahas pertanyaan satu per satu.']],
    ]),

  guide('member-quizzes', 'member', 'Belajar dan berlatih', ['quizzes.index'], 'Memilih latihan materi',
    'Latihan membantu mengukur pemahaman dan dapat mensyaratkan penyelesaian materi pada topik.', [
      ['Sebelum memulai', ['Baca judul, topik, jumlah soal, durasi, serta status akses. Sebagian latihan tanpa timer; sebagian memakai batas waktu. Latihan bertopik dapat meminta semua materi topik ditandai selesai terlebih dahulu.', 'Latihan premium memerlukan paket aktif yang memuat sesi tersebut, tetapi tidak memotong kuota tryout. Materi premium yang menjadi prasyarat juga perlu dapat diakses.']],
      ['Mulai atau lanjutkan', ['Mulai membuat sesi pengerjaan. Jika sudah ada sesi yang sedang berjalan, gunakan Lanjutkan agar kembali ke jawaban yang tersimpan. Baca status penyimpanan ketika mengerjakan.', 'Hasil latihan ditampilkan pada skala 0–100. Latihan tidak menerbitkan sertifikat tryout; gunakan hasilnya untuk mengenali materi yang perlu dipelajari ulang.']],
    ]),

  guide('member-tryouts', 'member', 'Belajar dan berlatih', ['tryouts.index'], 'Memilih tryout CAT',
    'Periksa jumlah soal, durasi, akses paket, dan kuota sebelum memulai simulasi ujian.', [
      ['Persiapan sebelum mulai', ['Sediakan waktu sesuai durasi, koneksi yang stabil, dan baca ketentuan sesi. Tryout bertimer terus menghitung waktu meskipun kamu keluar atau berpindah halaman.', 'Tryout premium memerlukan paket aktif yang memuat sesi dan kuota tersisa. Memulai sesi premium baru dapat mengurangi kuota. Tryout gratis tidak memotong kuota paket.']],
      ['Sesi berjalan dan hasil', ['Jika sudah ada sesi berjalan, lanjutkan sesi tersebut untuk memakai jawaban yang tersimpan. Sesi yang habis waktunya dapat diselesaikan otomatis dari jawaban terakhir yang berhasil tersimpan.', 'Setelah selesai, buka hasil untuk melihat nilai, kunci, dan pembahasan sesuai akses. Sertifikat hanya tersedia jika nilai mencapai passing grade sesi. Ambang tidak selalu sama antartryout.']],
    ]),

  guide('member-attempt', 'member', 'Saat mengerjakan', ['quizzes.attempt', 'tryouts.attempt'], 'Mengerjakan soal dan menyimpan jawaban',
    'Kenali timer, navigasi nomor soal, tanda ragu, status simpan, serta proses pengumpulan. Membuka bantuan tidak menghentikan timer.', [
      ['Timer dan jawaban', ['Timer menunjukkan sisa waktu sesi jika ada batas waktu. Waktu tetap berjalan ketika bantuan terbuka, browser di-refresh, kamu keluar, atau berpindah halaman. Jika perlu membaca panduan panjang, lakukan sebelum memulai sesi.', 'Pilih jawaban pada soal. Navigasi nomor soal membantu berpindah; tanda ragu membantu meninjau ulang dan tidak menggantikan pilihan jawaban. Filter Hanya belum dijawab membantu memeriksa soal kosong.']],
      ['Memastikan jawaban tersimpan', ['Periksa status penyimpanan server, terutama saat koneksi terputus. Simpan ulang dapat digunakan untuk mencoba kembali. Jangan menganggap jawaban sudah terkirim hanya karena pilihan terlihat di layar.', 'Saat koneksi pulih, aplikasi mencoba menyimpan kembali. Jawaban yang belum berhasil tersimpan berisiko tidak masuk hasil bila waktu berakhir atau perangkat ditutup.']],
      ['Keluar dan kumpulkan', ['Keluar meminta penyimpanan lalu kembali ke daftar; timer tetap berjalan. Kumpulkan mengakhiri sesi dan meminta konfirmasi. Periksa nomor kosong/ragu sebelum menyetujui pengumpulan.', 'Ketika waktu habis, sesi bertimer dapat dikumpulkan otomatis menggunakan jawaban yang tersimpan. Setelah selesai, jawaban sesi tidak dapat diedit. Untuk pengerjaan baru, kembali ke daftar sesuai akses/kuota.']],
      ['Pintasan keyboard', ['Di luar kolom input dan dialog, tombol panah dapat berpindah soal, tombol pilihan dapat memilih opsi, dan penanda ragu memiliki pintasan sesuai sesi. Gunakan tombol di layar bila tidak yakin.', 'Selama tur bantuan aktif, interaksi dengan bagian yang disorot dinonaktifkan agar tidak mengirim jawaban atau mengumpulkan sesi tanpa sengaja. Timer tetap berjalan.']],
    ], [
      ['#timerWrap', 'Waktu tersisa', 'Waktu terus berjalan selama bantuan terbuka dan saat keluar. Sesi tanpa timer tidak menampilkan bagian ini.'],
      ['#questionStage', 'Soal dan pilihan', 'Baca isi dan media sebelum menjawab. Tur tidak memilih atau mengubah jawabanmu.'],
      ['#palette', 'Nomor soal', 'Gunakan nomor untuk berpindah dan meninjau status jawaban/tanda ragu.'],
      ['#filterUnanswered', 'Periksa soal kosong', 'Filter ini membantu menemukan soal yang belum dijawab. Menandai ragu tidak mengisi jawaban.'],
      ['#connectionStatus', 'Status penyimpanan', 'Pastikan jawaban berhasil tersimpan di server. Jika gagal, pulihkan koneksi lalu gunakan Simpan ulang.'],
      ['#exitBtn', 'Simpan dan keluar', 'Keluar tidak menghentikan waktu. Sesi berjalan dapat dilanjutkan selama belum selesai atau habis waktu.'],
      ['#submitBtn', 'Kumpulkan jawaban', 'Pengumpulan mengakhiri sesi setelah konfirmasi. Periksa jawaban dahulu; tur tidak akan menekan tombol ini.'],
    ]),

  guide('member-result', 'member', 'Hasil belajar', ['quizzes.result', 'tryouts.result'], 'Membaca hasil, kunci, dan pembahasan',
    'Pahami nilai total, rincian bagian, jawaban kosong, serta syarat cetak sertifikat.', [
      ['Nilai dan ambang', ['Latihan memakai hasil skala 0–100; tryout memakai skor poin sesuai aturan sesi. Bandingkan nilai dengan maksimum dan nilai ambang yang tercantum, bukan dengan angka dari sesi lain.', 'Soal biasa memakai poin benar dan penalti salah. Soal berbobot memakai nilai pilihan dan tidak terkena penalti salah. Jawaban kosong bernilai 0. Label terkumpul otomatis berarti sesi diselesaikan ketika waktu habis.']],
      ['Rincian jawaban dan pembahasan', ['Nilai per bagian membantu menentukan prioritas belajar. Jumlah benar/salah pada soal biasa berbeda dari jumlah soal berbobot yang dijawab; keduanya tidak selalu dapat dianggap sebagai akurasi yang sama.', 'Buka rincian soal untuk membandingkan pilihanmu dan kunci. Pembahasan lengkap tersedia sesuai hak akses paket; pesan terkunci tidak berarti nilai hilang.']],
      ['Cetak dan kerjakan lagi', ['Cetak Sertifikat tersedia untuk tryout selesai yang mencapai passing grade. Tidak ada sertifikat untuk latihan, tryout di bawah ambang, atau sesi tanpa passing grade.', 'Kerjakan lagi membuat sesi baru sesuai akses/kuota; tidak mengganti hasil lama. Perubahan soal/pengaturan admin setelah sesi dimulai tidak menghitung ulang hasil sesi yang tersimpan.']],
    ]),

  guide('member-history', 'member', 'Hasil belajar', ['history.'], 'Riwayat pengerjaan',
    'Temukan sesi yang berjalan, lanjutkan pengerjaan, atau buka kembali hasil sesi selesai.', [
      ['Filter dan status', ['Gunakan jenis latihan/tryout dan status sedang dikerjakan/selesai. Filter hanya mengubah daftar yang terlihat. Satu sesi adalah satu pengerjaan; sesi berulang tetap memiliki riwayat tersendiri.', 'Lanjutkan Sesi membuka jawaban yang tersimpan, sedangkan Lihat Hasil membuka penilaian sesi selesai. Waktu sesi bertimer tetap berjalan meskipun sedang berada di riwayat.']],
      ['Membaca kolom hasil', ['Nilai ditampilkan bersama nilai maksimal agar perbandingan lebih adil. Jumlah benar, salah, kosong, serta soal berbobot dapat memiliki arti berbeda sesuai jenis penilaian.', 'Jika sesi yang waktunya habis berubah menjadi selesai, itu adalah penyelesaian otomatis berdasarkan jawaban yang tersimpan. Gunakan Statistik untuk melihat perkembangan gabungan dan hasil sesi untuk detail.']],
    ]),

  guide('member-statistics', 'member', 'Hasil belajar', ['statistics.'], 'Statistik dan perkembangan belajar',
    'Gunakan ringkasan sesi selesai untuk melihat kebiasaan belajar dan bagian yang perlu ditingkatkan.', [
      ['Membaca ringkasan dengan tepat', ['Jumlah sesi bukan jumlah soal. Durasi total menunjukkan waktu pengerjaan yang tercatat. Grafik tren membantu melihat perubahan dari sesi ke sesi, tetapi tingkat kesulitan dan maksimum nilai dapat berbeda.', 'Akurasi soal biasa dan skor soal berbobot mengukur hal berbeda. Jangan menyimpulkan semua soal TKP yang dijawab pasti benar atau memperoleh skor tertinggi.']],
      ['Menentukan langkah berikutnya', ['Buka hasil sesi untuk memeriksa bagian yang nilainya rendah, pelajari materi terkait, lalu coba latihan atau tryout berikutnya. Statistik bukan jaminan hasil seleksi resmi.', 'Jika belum ada data, selesaikan latihan/tryout terlebih dahulu. Sesi yang masih berjalan belum menghasilkan nilai akhir untuk ringkasan hasil.']],
    ]),

  guide('member-leaderboard', 'member', 'Hasil belajar', ['leaderboard.'], 'Peringkat lokal, nasional, dan profil virtual',
    'Bandingkan persentase skor tryout terbaik dan pahami batas akses serta label pada papan peringkat.', [
      ['Cara membaca peringkat', ['Peringkat peserta nyata memakai persentase skor terbaik dari tryout yang selesai. Persentase membantu membandingkan sesi dengan nilai maksimum berbeda; nilai dari latihan tidak menjadi dasar peringkat tryout.', 'Akun aktif, email terverifikasi, hasil tryout, dan provinsi memengaruhi data peserta yang tersedia. Skor persentase sama dapat memperoleh posisi peringkat yang sama.']],
      ['Lokal dan nasional', ['Peringkat lokal mengikuti provinsi profilmu. Akses lokal/nasional mengikuti hak paket aktif; nasional mencakup lokal juga. Jika terkunci, periksa paket dan masa aktifnya.', 'Pastikan provinsi pada profil sesuai. Perubahan provinsi dapat mengubah kelompok peringkat lokal tempat kamu terlihat.']],
      ['Label Virtual', ['Profil berlabel Virtual adalah profil contoh dengan skor simulasi yang ditampilkan jika fitur diaktifkan pengelola. Profil tersebut bukan bukti peserta nyata telah mengerjakan ujian.', 'Posisi yang terlihat dapat berubah saat profil virtual diaktifkan/dinonaktifkan atau saat skor terbaik peserta lain berubah. Riwayat nilai tryoutmu tetap dapat dilihat secara terpisah.']],
    ]),

  guide('member-packages', 'member', 'Paket dan pembayaran', ['packages.'], 'Memilih paket belajar',
    'Periksa isi, harga, masa aktif, kuota tryout, dan akses leaderboard sebelum memilih paket.', [
      ['Apa yang perlu dibandingkan?', ['Paket dapat memuat materi/video, latihan, tryout, dan hak leaderboard yang berbeda. Premium tidak otomatis berarti semua konten berbayar dalam aplikasi terbuka.', 'Masa aktif dihitung sejak aktivasi. Kuota tryout membatasi penggunaan sesi premium sesuai ketentuan paket; latihan tidak memotong kuota tersebut. Baca isi paket yang dipilih sebelum menuju checkout.']],
      ['Paket aktif dan perubahan katalog', ['Paketmu mengikuti rincian pembelian yang dicatat saat checkout, atau ketentuan saat pemberian manual oleh admin. Perubahan harga, isi, kuota, atau masa aktif pada katalog setelah itu tidak otomatis mengubah hak paketmu.', 'Jika konten masih terkunci, periksa bahwa paketmu aktif, belum kedaluwarsa/dicabut, memuat konten tersebut, dan masih memiliki kuota untuk tryout premium baru.']],
    ]),

  guide('member-checkout', 'member', 'Paket dan pembayaran', ['checkout'], 'Checkout dan membuat pesanan',
    'Periksa rincian paket serta total pembayaran sebelum membuat atau melanjutkan pesanan.', [
      ['Sebelum melanjutkan', ['Cocokkan paket yang dipilih, harga, masa aktif, isi, dan kuota. Pesanan mencatat rincian saat dibuat; harga katalog yang berubah kemudian tidak otomatis mengganti rincian pesanan lama.', 'Baca metode/lingkungan pembayaran yang ditampilkan. Pembayaran Sandbox adalah pengujian dan bukan transaksi dana nyata. Paket gratis memiliki alur aktivasi tanpa pembayaran berbayar.']],
      ['Setelah pesanan dibuat', ['Lanjutkan melalui halaman detail transaksi dan instruksi pembayaran yang tersedia. Jangan membuat banyak pesanan hanya karena status belum berubah; periksa pesanan yang sudah ada dahulu.', 'Tombol bayar dan konfirmasi pembayaran adalah tindakan nyata di luar tur. Bantuan tidak menekan tombol pembayaran, mengirim formulir, atau mengaktifkan paket untukmu.']],
      ['Tentang voucher', ['Halaman voucher admin saat ini masih contoh dan tidak menjamin kode diskon dapat diterapkan pada checkout. Gunakan total yang benar-benar ditampilkan pada pesanan.', 'Jika rincian paket atau total tidak sesuai, kembali ke pilihan paket sebelum melanjutkan pembayaran.']],
    ]),

  guide('member-transactions', 'member', 'Paket dan pembayaran', ['transactions.'], 'Transaksi, pembayaran, dan status akses',
    'Pantau pesananmu, lanjutkan pembayaran yang masih berlaku, dan pastikan akses paket sudah aktif.', [
      ['Daftar dan detail pesanan', ['Buka pesanan yang sesuai nomor, paket, serta tanggalnya. Detail transaksi menampilkan total, status, dan pilihan pembayaran yang tersedia.', 'Menunggu pembayaran belum sama dengan lunas. Kedaluwarsa/dibatalkan/gagal memerlukan tindak lanjut sesuai tombol yang tersedia. Jangan menganggap paket aktif hanya karena halaman pembayaran pernah dibuka.']],
      ['Periksa status pembayaran', ['Gunakan pemeriksaan/perbarui status bila tersedia setelah membayar. Status diproses dari penyedia pembayaran; pastikan koneksi stabil dan tunggu hasil pemeriksaan.', 'Cocokkan status pembayaran dengan status akses paket. Paket yang sudah aktif dapat berakhir sesuai masa aktif; pembayaran lama yang lunas tidak berarti hak akses berlaku selamanya.']],
      ['Bukti pembayaran dan bantuan', ['Simpan nomor pesanan serta informasi pembayaran ketika menghubungi pengelola. Bila tersedia, bukti/rincian transaksi dapat dicetak melalui tombol halaman.', 'Jangan membagikan password atau data rahasia pembayaran. Jika membayar di lingkungan Sandbox, hasilnya adalah pengujian. Panduan hanya menjelaskan langkah, bukan konfirmasi bahwa dana sudah diterima.']],
    ]),

  guide('member-certificates', 'member', 'Hasil belajar', ['certificates.'], 'Syarat dan cara cetak sertifikat',
    'Sertifikat tersedia untuk tryout selesai yang mencapai passing grade sesi, lalu dapat dicetak atau disimpan sebagai PDF.', [
      ['Kapan sertifikat tersedia?', ['Sesi harus merupakan tryout, sudah selesai, memiliki passing grade, dan nilainya mencapai atau melebihi ambang tersebut. Nilai tepat di ambang sudah memenuhi syarat.', 'Contoh: nilai 60 dengan ambang 60 memenuhi syarat; 59 belum. Angka ambang mengikuti sesi, bukan selalu 60. Latihan dan tryout tanpa nilai ambang tidak menerbitkan sertifikat.']],
      ['Periksa data sebelum cetak', ['Nama mengikuti profil saat sertifikat dibuka. Pastikan nama lengkap benar. Sertifikat juga memuat judul tryout, nilai, tanggal penyelesaian, dan nomor sertifikat.', 'Sertifikat hanya dapat dibuka oleh pemiliknya. Nomor sertifikat bukan tautan verifikasi publik. Sertifikat membuktikan pencapaian simulasi, bukan kelulusan seleksi CPNS resmi.']],
      ['Cetak atau simpan PDF', ['Pilih Lihat & Cetak, kemudian Cetak / Simpan PDF. Gunakan kertas A4 dengan orientasi landscape. Matikan header/footer bawaan browser agar alamat halaman tidak ikut tercetak.', 'Pilih printer untuk cetak kertas atau tujuan Simpan sebagai PDF untuk berkas digital. Jika warna latar tidak muncul, periksa opsi grafis latar pada dialog cetak. Jika sertifikat belum ada, periksa hasil tryout dan passing grade.']],
    ], [
      ['.certificate-toolbar', 'Cetak melalui browser', 'Pilih A4 landscape. Untuk berkas digital, pilih Simpan sebagai PDF pada tujuan cetak dan matikan header/footer browser.'],
      ['.certificate-document', 'Periksa isi sertifikat', 'Pastikan nama, judul tryout, nilai, passing grade, dan tanggal benar sebelum mencetak. Nama mengikuti profil.'],
      ['[data-print]', 'Cetak / Simpan PDF', 'Tombol membuka dialog cetak browser. Tur tidak mengirim dokumen ke printer.'],
    ]),

  guide('member-profile', 'member', 'Akun', ['profile.'], 'Profil, provinsi, dan keamanan akun',
    'Perbarui identitas yang dipakai pada pembelajaran, leaderboard lokal, dan sertifikat.', [
      ['Identitas dan provinsi', ['Nama dipakai pada profil serta sertifikat, jadi gunakan nama lengkap yang benar. Provinsi menentukan kelompok leaderboard lokal; pilih sesuai data diri.', 'Email pada profil tidak bisa diubah sendiri; hubungi admin bila perlu koreksi. Data kontak/instansi membantu pengenalan akun; isi seperlunya dengan data yang benar.']],
      ['Mengganti password dan menyimpan', ['Gunakan bagian perubahan password untuk mengganti sandi, isi konfirmasi sesuai formulir, lalu simpan. Mengubah profil tidak selalu sama dengan mengganti password; gunakan tombol pada bagian yang sesuai.', 'Baca pesan validasi bila data ditolak. Jangan membagikan password kepada orang lain. Keluar dari akun ketika memakai perangkat bersama.']],
      ['Pengaturan dan tindakan akun', ['Pengaturan notifikasi mengikuti opsi yang tersedia pada halaman; ikon/angka notifikasi di bar atas masih berupa tampilan contoh, bukan daftar pesan yang benar-benar dikirim.', 'Tindakan penghapusan akun berbeda dengan mengedit profil. Baca konfirmasi dan dampaknya sebelum melanjutkan. Bantuan tidak menekan tombol hapus atau menyimpan perubahan.']],
    ]),

  guide('member-articles', 'member', 'Belajar dan berlatih', ['articles.'], 'Membaca artikel pembelajaran',
    'Temukan bacaan pendukung per bidang dan gunakan isi artikel untuk memperluas pemahaman.', [
      ['Mencari dan membaca', ['Gunakan kategori/pencarian yang tersedia, lalu buka judul artikel. Cuplikan adalah pengantar; isi lengkap berada di halaman bacaan.', 'Periksa judul bagian, gambar, dan tautan di dalam artikel. Membaca artikel berbeda dari menandai materi selesai dan tidak otomatis memenuhi prasyarat latihan topik.']],
      ['Jika artikel tidak tersedia', ['Artikel yang belum terbit atau tidak lagi tersedia dapat tidak muncul pada daftar. Kembali ke katalog dan periksa filter terlebih dahulu.', 'Artikel adalah bahan pendukung belajar. Untuk mengikuti progres topik, gunakan menu Materi; untuk nilai akhir dan sertifikat, gunakan Tryout.']],
    ]),
];

const examFields = [
  ['#exam-title', 'Nama sesi', 'Nama ditampilkan pada katalog, hasil, riwayat, dan sertifikat tryout yang memenuhi syarat.'],
  ['#exam-slug', 'Alamat sesi', 'Gunakan huruf kecil, angka, dan tanda hubung. Jenis dan alamat sesi tetap setelah dibuat.'],
  ['#exam-description', 'Deskripsi sesi', 'Jelaskan cakupan bidang, tingkat kesiapan, dan tujuan sesi agar peserta memahami apa yang akan dikerjakan.'],
  ['#exam-access', 'Hak akses sesi', 'Premium memerlukan paket aktif yang memuat sesi. Tryout premium memakai kuota; latihan premium tidak memotong kuota tryout.'],
  ['#exam-count', 'Soal per pengerjaan', 'Jumlah ini harus didukung soal terpilih yang terbit dan bidang aktif. Tidak semua soal dalam bank otomatis dipakai.'],
  ['#exam-duration', 'Durasi', 'Tryout wajib 1–240 menit. Pada latihan, 0 berarti tanpa timer. Waktu sesi bertimer tetap berjalan saat peserta keluar.'],
  ['#exam-topic', 'Topik latihan', 'Pilih topik untuk menempatkan latihan di alur materi, atau tanpa topik untuk latihan umum.'],
  ['#exam-completion', 'Prasyarat materi', 'Syarat semua materi selesai mencakup seluruh materi terbit pada topik, termasuk premium. Pilih topik dahulu.'],
  ['#exam-points', 'Poin benar', 'Untuk soal biasa. Soal berbobot memakai nilai pilihan 1–5. Hasil latihan dikonversi ke skala 0–100.'],
  ['#exam-penalty', 'Penalti salah', 'Pengurangan untuk jawaban salah pada soal biasa. Kosong dan soal berbobot tidak menggunakan penalti salah.'],
  ['#exam-passing', 'Passing grade dan sertifikat', 'Ambang mengikuti skala sesi dan tidak boleh melebihi maksimum. Tryout selesai dengan nilai ≥ ambang dapat mencetak sertifikat. Kosong berarti tidak ada ambang dan tidak ada sertifikat tryout.'],
  ['[name="random_questions"]:not([type="hidden"])', 'Acak soal', 'Mengacak pengambilan dan urutan dari kumpulan soal sesi pada pengerjaan baru. Urutan tetap selama sesi berjalan.'],
  ['[name="random_options"]:not([type="hidden"])', 'Acak pilihan', 'Mengacak opsi yang diizinkan soalnya. Soal yang merujuk label/urutan tertentu sebaiknya melarang pengacakan.'],
  ['#soal-sesi', 'Kumpulan soal sesi', 'Centang soal yang dipakai. Soal yang memenuhi syarat terbit harus cukup untuk jumlah per pengerjaan. Simpan sesi setelah mengubah pilihan.'],
  ['[data-add-question]', 'Editor soal di dalam sesi', 'Soal yang ditambah/diedit disimpan ke bank soal. Pilihan kumpulan soal tetap perlu diterapkan dengan menyimpan sesi.'],
  ['button[name="status"][value="published"]', 'Simpan dan terbitkan', 'Menerbitkan memeriksa kesiapan dan jumlah soal. Perubahan pengaturan berlaku untuk sesi baru; hasil lama tidak dihitung ulang. Tur tidak menyimpan data.'],
];

const additionalFields = {
  'admin-sections': [
    ['#slug', 'Alamat bidang', 'Slug membentuk alamat kategori. Kosong dapat diisi otomatis dari nama; periksa keunikannya.'],
    ['#description', 'Deskripsi bidang', 'Jelaskan lingkup bidang agar pengelola dan peserta memahami jenis materi yang termasuk.'],
    ['#icon', 'Ikon bidang', 'Ikon membedakan bidang secara visual dan tidak mengubah nilai atau hak akses.'],
  ],
  'admin-questions': [
    ['#question-image', 'Gambar pendukung', 'JPEG, PNG, atau WebP maksimal 2 MB. Periksa teks dan label opsi di dalam gambar sebelum mengizinkan pengacakan pilihan.'],
    ['#question-video', 'Video pendukung', 'MP4/WebM maksimal 50 MB. MP4 H.264 membantu kompatibilitas browser. Pratinjau media sebelum terbit.'],
  ],
  'admin-packages': [
    ['#package-position', 'Urutan katalog', 'Angka kecil ditampilkan lebih dahulu pada katalog. Urutan tidak menambah hak akses atau kuota.'],
  ],
  'admin-users': [
    ['#phone', 'Kontak peserta', 'Nomor telepon membantu identifikasi akun; menyimpannya tidak otomatis mengirim pesan/pengingat.'],
    ['#institution', 'Instansi atau asal', 'Isi identitas instansi/asal peserta dengan jelas agar tidak tertukar dengan akun lain.'],
    ['#address', 'Alamat', 'Data kontak tambahan. Untuk peringkat lokal, gunakan pilihan provinsi, bukan hanya teks alamat.'],
  ],
  'member-dashboard': [
    ['main .stat', 'Ringkasan belajar', 'Baca label angka dan jenis aktivitasnya. Sesi selesai berbeda dari materi yang ditandai selesai.'],
    ['main [data-chart]', 'Perkembangan', 'Gunakan grafik sebagai ringkasan lalu buka hasil sesi untuk rincian nilai dan jawaban.'],
  ],
  'member-materials': [
    ['.material-field-pills', 'Pilih bidang', 'Bidang mengelompokkan topik. Pencarian dan filter format membantu mempersempit bahan belajar.'],
    ['.lesson-topic', 'Buka topik', 'Buka kelompok topik untuk melihat materi PDF/video serta latihan yang terkait. Materi terkunci memerlukan paket yang sesuai.'],
    ['form[action$="/progress"]', 'Tandai materi selesai', 'Gunakan setelah mempelajari materi. Tanda selesai dapat menjadi syarat memulai latihan topik; hanya membuka materi belum cukup.'],
    ['[data-pdf-reader]', 'Pembaca PDF', 'Gunakan kontrol halaman dan perbesaran. Pembaca materi ini membatasi fitur unduh/cetak; cetak sertifikat tersedia melalui menu Sertifikat.'],
    ['video', 'Materi video', 'Putar sumber video yang disediakan pengelola. Setelah belajar, periksa tanda selesai materi.'],
  ],
  'member-question-bank': [
    ['.question-bank-navigation', 'Pindah soal', 'Sebelumnya/Berikutnya mengikuti daftar/filter soal yang dipilih. Tidak sama dengan sesi tryout bertimer.'],
    ['.question-answer-options', 'Pilih jawaban', 'Baca pertanyaan lalu pilih opsi sebelum Periksa jawaban. Pada soal berbobot setiap pilihan dapat bernilai berbeda.'],
    ['.question-answer-footer', 'Periksa jawaban', 'Tombol mengirim pilihan untuk diperiksa. Tur ini tidak memilih atau mengirim jawaban.'],
    ['.question-feedback', 'Umpan balik dan pembahasan', 'Bandingkan pilihan dengan kunci/skor. Pembahasan lengkap mengikuti hak akses yang berlaku.'],
  ],
  'member-quizzes': [
    ['.exam-metrics', 'Jumlah soal dan durasi latihan', 'Durasi 0 berarti tanpa timer. Periksa jumlah soal dan maksimum hasil sebelum mulai.'],
    ['.exam-catalog', 'Akses dan prasyarat', 'Latihan premium perlu paket yang memuatnya. Latihan topik dapat meminta seluruh materi ditandai selesai. Latihan tidak memotong kuota tryout.'],
  ],
  'member-tryouts': [
    ['.exam-metrics', 'Persiapan tryout', 'Periksa jumlah soal, durasi, dan skor maksimal. Timer tetap berjalan setelah sesi dimulai, termasuk ketika keluar.'],
    ['.exam-catalog', 'Mulai atau lanjutkan', 'Tryout premium baru memerlukan paket yang memuatnya dan kuota tersisa. Lanjutkan memakai sesi berjalan, bukan membuat hasil baru.'],
  ],
  'member-result': [
    ['.result-hero', 'Nilai dan passing grade', 'Bandingkan nilai dengan maksimum dan ambang sesi. Nilai tepat di ambang sudah memenuhi syarat sertifikat tryout.'],
    ['.scorebar', 'Nilai per bagian', 'Gunakan bagian dengan nilai rendah sebagai prioritas belajar berikutnya. Skor soal berbobot berbeda dengan jumlah benar.'],
    ['main details', 'Rincian jawaban', 'Buka rincian setelah tur untuk melihat pilihan, kunci, dan pembahasan sesuai hak akses.'],
  ],
  'member-history': [
    ['main .pills', 'Filter riwayat', 'Pilih semua, tryout, atau latihan. Filter status membedakan sesi berjalan dengan sesi selesai.'],
    ['main .table-wrap', 'Lanjutkan atau lihat hasil', 'Lanjutkan Sesi membuka jawaban yang tersimpan; Lihat Hasil membuka penilaian. Timer sesi berjalan tidak berhenti saat di halaman ini.'],
  ],
  'member-statistics': [
    ['[aria-label="Jenis aktivitas"]', 'Pilih jenis aktivitas', 'Ringkasan latihan dan tryout dapat berbeda skala. Pilih jenis aktivitas yang ingin dievaluasi.'],
    ['[aria-label="Rentang waktu"]', 'Pilih periode', '30 hari, 3 bulan, atau semua membatasi data yang dihitung. Sesi berjalan belum menjadi hasil akhir.'],
    ['main .stat', 'Nilai dan akurasi', 'Perhatikan maksimum nilai serta jenis soal. Akurasi benar/salah tidak menyamakan semua jawaban berbobot sebagai benar penuh.'],
    ['main [data-chart]', 'Grafik hasil', 'Gunakan grafik untuk melihat pola lalu periksa hasil sesi individual sebelum membandingkan sesi yang berbeda.'],
  ],
  'member-leaderboard': [
    ['[aria-label="Cakupan leaderboard"]', 'Lokal atau nasional', 'Lokal mengikuti provinsi profil. Nasional membutuhkan hak yang sesuai pada paket aktif dan mencakup lokal juga.'],
    ['main .table-wrap', 'Peringkat dan profil virtual', 'Peringkat memakai persentase tryout terbaik. Label Virtual berarti profil contoh dengan skor simulasi, bukan peserta nyata.'],
  ],
  'member-packages': [
    ['.price', 'Isi dan harga paket', 'Bandingkan materi/sesi, harga, masa aktif, kuota, serta leaderboard. Paket tidak otomatis membuka seluruh konten premium.'],
    ['.price__foot', 'Pilih atau aktifkan paket', 'Tombol membawa ke pemeriksaan rincian paket. Tur tidak membuat pesanan atau mengaktifkan akses.'],
  ],
  'member-checkout': [
    ['[aria-labelledby="checkout-package-title"]', 'Periksa isi paket', 'Pastikan materi, latihan, tryout, masa aktif, dan kuotanya sesuai kebutuhanmu sebelum membuat pesanan.'],
    ['#checkout-summary-title', 'Ringkasan pembelian', 'Periksa harga/total yang benar-benar ditampilkan. Voucher contoh pada admin tidak menjamin potongan berlaku.'],
    ['main form', 'Lanjutkan sesuai pilihanmu', 'Tindakan pada formulir dapat membuat pesanan atau mengaktifkan paket gratis. Tur hanya menjelaskan dan tidak menekan tombol.'],
  ],
  'member-transactions': [
    ['main .table-wrap', 'Temukan pesanan', 'Cocokkan nomor pesanan, paket, tanggal, dan status agar tidak tertukar dengan pembelian lain.'],
    ['form[action$="/refresh"]', 'Periksa status', 'Meminta pemeriksaan status pembayaran. Belum lunas tidak sama dengan akses paket aktif.'],
    ['form[action$="/pay"]', 'Lanjutkan pembayaran', 'Tombol bisa menyiapkan atau membuka pembayaran. Tur tidak membuat komitmen pembayaran untukmu.'],
  ],
  'member-profile': [
    ['#avatar', 'Foto profil', 'Gunakan JPEG, PNG, atau WebP sesuai batas formulir, lalu simpan perubahan profil.'],
    ['#name', 'Nama pada sertifikat', 'Isi nama lengkap dengan benar sebelum membuka dan mencetak sertifikat.'],
    ['#email', 'Email akun', 'Email ditampilkan hanya-baca. Hubungi admin jika perlu memperbaikinya.'],
    ['#province', 'Peringkat lokal', 'Provinsi menentukan kelompok leaderboard lokal dan ditampilkan bersama namamu di papan peringkat.'],
    ['#current_password', 'Password saat ini', 'Bagian ini digunakan saat mengganti sandi. Isi sesuai petunjuk lalu konfirmasikan password baru.'],
    ['#password', 'Password baru', 'Minimal 8 karakter sesuai formulir. Mengganti password adalah tindakan tersendiri dari menyimpan profil.'],
    ['#password_confirmation', 'Konfirmasi sandi baru', 'Harus sama persis dengan password baru sebelum menyimpan perubahan sandi.'],
    ['#delete_current_password', 'Penghapusan akun', 'Ini bagian tindakan hapus akun, bukan penggantian password. Baca dampak dan konfirmasi sebelum menggunakannya. Tur tidak menghapus akun.'],
  ],
  'member-articles': [
    ['main .article-prose', 'Isi bacaan', 'Membaca artikel tidak otomatis menandai materi topik selesai. Gunakan Materi untuk progres belajar dan Tryout untuk hasil simulasi.'],
  ],
};

for (const item of helpGuides) {
  if (['admin-exams', 'admin-quizzes'].includes(item.id)) item.fields = examFields;
  item.fields = [...item.fields, ...(additionalFields[item.id] || [])];
}

export function findHelpGuide(routeName, audience) {
  return helpGuides.find(item => item.audience === audience && item.routes.some(route => route.endsWith('.') ? routeName.startsWith(route) : routeName === route))
    ?? helpGuides.find(item => item.id === (audience === 'admin' ? 'admin-start' : 'member-dashboard'));
}

export function searchHelpGuides(query, audience) {
  const words = query.toLocaleLowerCase('id').trim().split(/\s+/).filter(Boolean);
  return helpGuides.filter(item => item.audience === audience && words.every(word =>
    [item.title, item.summary, item.category, ...item.sections.flat(2), ...item.fields.flat()].join(' ').toLocaleLowerCase('id').includes(word)));
}
