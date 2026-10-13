# PRD — Platform Belajar & Simulasi Tryout CAT CPNS

**Nama internal:** LONTAR · CPNS Online
**Referensi:** Invoice INV-Rifandi Sihombing
**Versi dokumen:** 1.0 (draft untuk implementasi)
**Tanggal:** Oktober 2026

---

## 0. Cara Membaca PRD Ini

Dokumen ini menerjemahkan brief invoice menjadi spesifikasi yang bisa langsung dikerjakan.

Ada tiga jenis isi di sini, dan bedanya penting:

| Penanda | Arti |
| --- | --- |
| **WAJIB** | Sudah disepakati client (tercantum di invoice). Tidak boleh berubah tanpa revisi kontrak. |
| **TBD** | Client **belum** memutuskan. Ada di kebutuhan bisnis, tapi bentuk teknisnya belum dikunci. Wajib dikonfirmasi sebelum development fase terkait dimulai. |
| **IMPLEMENTASI** | Keputusan teknis tim developer agar sistem bisa dibangun. Boleh ditunda atau diganti. |

> Prinsip utama: kita tidak mengunci keputusan bisnis yang belum pernah disepakati client.
> Semua item TBD dikumpulkan di [§44](#44-scope-yang-belum-dikunci--tbd-register) sebagai satu daftar kerja konfirmasi, bukan disebar di dalam dokumen.

---

## 1. Product Overview

Platform ini merupakan website media pembelajaran dan simulasi ujian CPNS yang menyediakan:

- Materi pembelajaran (PDF, video, artikel)
- Bank soal & pembahasan
- Latihan soal (quiz)
- Simulasi Try Out CAT dengan timer, randomisasi, dan autosave
- Sistem penilaian, hasil, dan pembahasan
- Statistik & grafik perkembangan nilai
- Riwayat pengerjaan dan leaderboard
- Sistem paket belajar berbayar + payment gateway + voucher
- Sertifikat digital
- Admin Panel untuk mengelola seluruh konten dan peserta

Dari sisi pengelola, tersedia Admin Panel untuk mengelola materi, kategori, soal, pembahasan, peserta, paket, transaksi, dan voucher.

Scope fitur tersebut tercantum pada invoice proyek.

---

## 2. Product Goals

1. Menyediakan media belajar CPNS berbasis web.
2. Menyediakan latihan soal untuk peserta.
3. Menyediakan simulasi ujian dengan konsep CAT.
4. Memberikan hasil dan penilaian setelah pengerjaan.
5. Memberikan pembahasan soal kepada peserta.
6. Membantu peserta memantau perkembangan hasil belajar.
7. Menyediakan leaderboard sebagai informasi peringkat peserta.
8. Menyediakan sistem paket belajar berbayar.
9. Menyediakan sistem administrasi untuk pengelola platform.

---

## 3. Target User

### 3.1 Peserta

Pengguna utama platform:

- Belajar materi CPNS
- Membaca materi PDF
- Menonton video pembelajaran
- Mengerjakan latihan
- Mengikuti tryout
- Melihat hasil
- Melihat pembahasan
- Memantau perkembangan nilai
- Membeli paket belajar
- Melihat leaderboard

### 3.2 Admin

Pengelola platform yang bertanggung jawab terhadap materi, kategori, soal, pembahasan, peserta, paket, transaksi, voucher, dan konten platform.

---

## 4. User Roles

| Role | Akses |
| --- | --- |
| **Guest** | Melihat halaman publik (landing, materi yang tersedia, paket, artikel publik) |
| **Peserta** | Mengakses fitur pembelajaran dan tryout sesuai hak akses |
| **Admin** | Mengelola konten dan peserta melalui Admin Panel |

**Catatan:** Invoice tidak mencantumkan role tambahan seperti Super Admin, Staff, Editor, atau Moderator. Karena itu role tersebut **belum menjadi bagian scope** dan masuk daftar Phase 2 (§45).

---

## 5. High-Level User Flow

### 5.1 Flow Guest

```
Landing Page
      ↓
Melihat informasi platform
      ↓
Melihat materi / paket / konten yang tersedia
      ↓
Register / Login
```

### 5.2 Flow Peserta

```
Register
   ↓
Login
   ↓
Dashboard
   ↓
Pilih aktivitas
   ├── Materi
   ├── Video
   ├── Artikel
   ├── Quiz / Latihan
   ├── Bank Soal
   ├── Tryout CAT
   ├── Riwayat
   ├── Statistik
   ├── Leaderboard
   ├── Sertifikat
   ├── Paket Belajar
   └── Profil
```

### 5.3 Flow Quiz / Latihan

```
Pilih Quiz
 ↓
Cek akses
 ↓
Mulai
 ↓
Kerjakan soal
 ↓
Autosave
 ↓
Submit
 ↓
Penilaian
 ↓
Hasil + Pembahasan
 ↓
Tersimpan ke Riwayat
```

### 5.4 Flow Tryout CAT

```
Pilih Tryout
     ↓
Cek akses
     ↓
Mulai Tryout
     ↓
Timer berjalan
     ↓
Soal → Jawab → Autosave
     ↓
Navigasi soal / tandai soal
     ↓
Submit  atau  waktu habis
     ↓
Sistem menghitung nilai
     ↓
Hasil Tryout
     ↓
Pembahasan
     ↓
Statistik / Leaderboard
```

### 5.5 Flow Pembelian

```
Pilih Paket
   ↓
Checkout (opsional pakai voucher)
   ↓
Payment Gateway
   ↓
Status Pembayaran
   ↓
Pembayaran berhasil
   ↓
Akses paket diberikan (entitlement)
```

---

## 6. Landing Page

Landing page menjadi halaman utama platform.

**Requirement (WAJIB):**

- Informasi mengenai platform
- Informasi layanan pembelajaran
- Informasi tryout
- Informasi paket belajar
- CTA untuk register/login
- Informasi konten yang tersedia

**Responsive (WAJIB):** desktop, tablet, mobile — tercantum pada invoice sebagai bagian scope *UI/UX & Responsive Website*.

**IMPLEMENTASI** — section landing page (sudah ada di prototype):

| Section | Anchor | Isi |
| --- | --- | --- |
| Hero | — | Headline, sub-headline, CTA, statistik platform, ilustrasi |
| Marquee | — | Daftar kapabilitas (TWK/TIU/TKP, Timer CAT, Autosave, dst.) |
| Fitur | `#fitur` | 6 kartu fitur utama |
| Materi | `#materi` | Cuplikan materi & kategori |
| Tryout | `#tryout` | Penjelasan alur simulasi CAT |
| Paket | `#paket` | Ringkasan paket belajar |
| Statistik/Social proof | — | Angka platform + testimonial |
| FAQ | `#faq` | Pertanyaan umum |
| CTA | — | Ajakan registrasi |

---

## 7. Authentication

### 7.1 Register (WAJIB)

Data minimal: **Nama, Email, Password, Konfirmasi Password**.

Field final dapat disesuaikan pada tahap UI/UX.

**IMPLEMENTASI —** form register memuat: nama lengkap, email, password, konfirmasi password, persetujuan syarat & ketentuan, checkbox "ingat saya", tombol Google OAuth (opsional, **TBD** apakah masuk scope).

### 7.2 Login (WAJIB)

```
Login
 ↓
Validasi akun
 ↓
Berhasil
 ↓
Dashboard Peserta
```

### 7.3 Logout (WAJIB)

Peserta dapat keluar dari akun.

### 7.4 Business Rule — Authentication

Fitur yang membutuhkan akun hanya dapat diakses setelah peserta login.

---

## 8. Dashboard Peserta (WAJIB)

Dashboard menjadi pusat aktivitas peserta. Minimal menampilkan:

- Informasi profil
- Progress belajar
- Aktivitas belajar
- Statistik nilai
- Riwayat pengerjaan
- Paket yang dimiliki
- Akses tryout
- Informasi leaderboard

**IMPLEMENTASI —** isi dashboard:

1. Greeting + tanggal + CTA (Belajar / Mulai Tryout)
2. 4 stat card: Nilai Terakhir, Tryout Selesai, Quiz Dikerjakan, Peringkat
3. Grafik perkembangan nilai (line chart, 4 sesi terakhir, garis rata-rata)
4. Progress belajar: ring persentase + rincian (materi selesai, video ditonton, quiz selesai, streak) + progress bar per kategori
5. Lanjutkan Belajar: 3 item terakhir yang belum selesai
6. Riwayat pengerjaan: 4 baris terbaru
7. Sidebar: kartu tryout berikutnya, paket aktif, leaderboard mini, ajakan sertifikat

---

## 9. Profil Peserta (WAJIB)

Peserta dapat melihat dan mengelola informasi profilnya.

**IMPLEMENTASI —** field: foto profil, nama, email, bio/biografi singkat, informasi akun (status, tanggal registrasi), dan tab pengaturan (ubah password, notifikasi, hapus akun).

---

## 10. Sistem Materi Pembelajaran (WAJIB)

Tiga jenis konten, semuanya tercantum pada scope invoice:

1. **Materi PDF**
2. **Video Pembelajaran**
3. **Artikel**

### 10.1 Materi PDF

Peserta dapat mengakses materi PDF.

```
Bidang → Materi → PDF
```

**Admin dapat:** membuat materi, mengunggah PDF, mengubah materi, menghapus materi.

**TBD:** apakah PDF dapat diunduh atau view-only (§44).

**IMPLEMENTASI —** `materials` sudah fungsional di `/admin/materials`. Unggahan
disimpan ke disk **privat** (`local` → `storage/app/private`) pada
`materials/pdf`, maksimal 20 MB, divalidasi sebagai PDF. `mimes:pdf` memeriksa
ekstensi; pemeriksaan magic byte dan antivirus sengaja belum ada (§48.4).

**Kenapa disk privat, bukan `public`:** kalau PDF berada di `public/` dengan
symlink `storage:link`, siapa pun bisa membacanya hanya dengan menebak URL —
gating premium jadi tidak berarti apa-apa. Berkas hanya boleh diambil lewat
`GET /materials/{material}/file`, satu-satunya tempat akses dicek.

**IMPLEMENTASI (sementara):** kolom `video_url` menyimpan tautan sebagai teks,
sehingga ketiga opsi bisa ditukar tanpa mengubah halaman. Belum ada player,
proxy, atau pemrosesan video.

### 10.2 Video Pembelajaran

```
Bidang → Video → Video Player
```

**TBD:** sumber video — upload langsung ke server, YouTube, Vimeo, atau storage/CDN lain (§44). Keputusan wajib diambil sebelum implementasi.

**IMPLEMENTASI (sementara):** kolom `video_url` menyimpan tautan sebagai teks,
sehingga ketiga opsi bisa ditukar tanpa mengubah halaman. Belum ada player,
proxy, atau pemrosesan video.

### 10.3 Artikel

Admin dapat membuat, mengedit, menghapus, mengatur kategori, dan menerbitkan artikel. Fitur Artikel tercantum pada Admin Panel scope invoice.

### 10.4 Halaman baca materi (IMPLEMENTASI)

`/materials` sudah fungsional dan memakai data nyata. Dua route tambahan:

| Route | Fungsi |
| --- | --- |
| `GET /materials/{material}` | Halaman baca. PDF dirender viewer milik sendiri, video diputar lewat `<video controls>` |
| `GET /materials/{material}/file` | Mengalirkan berkas PDF **setelah** akses dicek |

Yang dijamin:

| Aturan | Kenapa |
| --- | --- |
| Draft tidak ada di HTML | Bukan disembunyikan dengan CSS — judulnya benar-benar tidak ada, jadi tidak bisa ditemukan lewat view-source |
| Materi bidang nonaktif tersembunyi | `scopeVisibleToMembers()` menyaring `section.is_active` |
| Materi tidak tayang → **404** | Bukan 403. Keberadaannya tidak perlu terungkap ke yang tidak berhak |
| Premium ditolak di **berkas**, bukan cuma di halaman | Kalau gating hanya di UI, mengedit URL cukup untuk membajak |
| Admin boleh pratinjau draft | Perlu memeriksa sebelum diterbitkan; `show` dan `file` memperlakukannya sama |
| Respons PDF `no-store` | Salinan tidak boleh tertinggal di cache browser/proxy setelah peserta logout |
| Tanpa tombol unduh & cetak | PDF dirender viewer milik sendiri, bukan diserahkan ke viewer bawaan browser (§10.5) |
| Pesan akses menjelaskan sebabnya | "Butuh paket" dan "berkasnya belum ada" dua masalah berbeda; peserta diarahkan ke tim yang benar |
| Materi lain di bidang yang sama | Cara peserta menemukan materi berikutnya tanpa kembali ke daftar |

Belum ada: catatan progres baca (§21) dan penyambungan materi ke dashboard peserta.

### 10.5 Kenapa PDF tidak di-embed langsung (IMPLEMENTASI)

Klien minta peserta tidak bisa mengunduh PDF. Semula halamannya memakai
`<object data="…pdf">`, dan ternyata tombol **Unduh** dan **Cetak** milik
viewer bawaan browser tetap ada — jadi secara teknis peserta tetap bisa
mengunduhnya.

**Penyelesaiannya: PDF tidak pernah diserahkan ke browser sebagai dokumen.**
Berkasnya diambil pdf.js dan tiap halaman dirender ke `<canvas>`; toolbar
ditentukan sendiri, sehingga tombol unduh dan cetak tidak pernah muncul.

| Yang berubah | Kenapa |
| --- | --- |
| `<object>` / `<embed>` / `<iframe>` dihapus | Viewer bawaan browser selalu menyediakan Unduh & Cetak |
| Toolbar punya: halaman, zoom, sesuaikan lebar | Semua yang dibutuhkan membaca; tidak ada jalur unduh |
| `Ctrl+S` / `Ctrl+P` dan klik kanan di canvas diblokir | Penghadang untuk jalan pintas paling sering dipakai tanpa sengaja |
| `@media print` menyembunyikan viewer | Dialog cetak browser tidak mencetak halaman aplikasi |
| Watermark nama + email di tiap halaman | Salinan yang tetap bocor bisa ditelusuri balik ke pemiliknya |
| Teks tetap bisa disorot & dicari | Lapisan teks di atas canvas. Menyembunyikannya hanya merusak pengalaman baca tanpa mencegah apa pun |

**Batasnya, dan ini harus jujur kepada klien:** pdf.js tetap menarik byte PDF
ke browser untuk merendernya. Orang yang bertekad tetap bisa mengambilnya lewat
DevTools, dan "Save page as" tetap menyimpan halaman yang sudah dirender.
Tidak ada konten web yang 100% tidak bisa disalin — yang bisa dijamin adalah
**tidak ada tombol unduh/cetak, dan salinan yang bocor bisa ditelusuri.**
Proteksi sesungguhnya hanya berisi unenforceable DRM.

---

## 11. Bank Soal (WAJIB)

Bank soal adalah sumber utama soal latihan dan tryout.

**Struktur tiap soal:**

```
Kategori
Soal
Pilihan Jawaban
Jawaban Benar
Pembahasan
```

**Admin dapat:** tambah soal, edit soal, hapus soal, melihat daftar soal, mengelola pembahasan.

**IMPLEMENTASI —** dukungan opsional: bobot per soal, tingkat kesulitan, flag soal bermasalah, status draft/terbit.

---

## 12. Quiz / Latihan Soal (WAJIB)

```
Pilih Quiz → Mulai → Kerjakan soal → Submit → Sistem menilai → Hasil → Pembahasan
```

Setelah selesai, hasil latihan menampilkan: skor, jawaban benar, jawaban salah, dan pembahasan.

**Perbedaan dengan Tryout:**

| Aspek | Quiz / Latihan | Tryout CAT |
| --- | --- | --- |
| Timer | Opsional | WAJIB |
| Autosave | WAJIB | WAJIB |
| Randomisasi | Opsional | WAJIB |
| Skala nilai | Skor sederhana (mis. 0–100) | Skala poin CAT |
| Attempt yang masih berjalan | Satu sesi per tryout | Boleh berulang |
| Kuota | Tidak terbatas | Dibatasi kuota paket |

---

## 13. Sistem Penilaian (WAJIB)

Sistem secara otomatis menilai pengerjaan peserta:

```
Jawaban peserta → Bandingkan dengan jawaban benar → Hitung skor → Simpan hasil
```

> **PENTING.** Rumus penilaian CPNS **belum dijelaskan di invoice**. PRD ini **tidak mengunci** rumus apa pun. Rumus final wajib ditentukan bersama client sebelum fase penilaian dikerjakan.

**Rumus sementara yang dipakai prototype** (hanya untuk display, bukan keputusan final):

```
nilai = max(0, (benar × poin_per_benar) − (salah × penalti_salah))
```

Parameter `poin_per_benar`, `penalti_salah`, `nilai_ambang`, dan `jumlah_soal` disimpan per tryout agar bisa diubah tanpa menyentuh kode.

---

## 14. Pembahasan Soal (WAJIB)

Setiap soal dapat memiliki pembahasan. Setelah peserta menyelesaikan latihan/tryout, peserta dapat melihat pembahasan sesuai akses yang diberikan.

```
Soal → Jawaban peserta → Jawaban benar → Pembahasan
```

---

## 15. Riwayat Pengerjaan (WAJIB)

Peserta dapat melihat aktivitas pengerjaan sebelumnya.

| Aktivitas | Nilai | Tanggal |
| --- | --- | --- |
| Tryout SKD #1 | 420 | 01/10/2026 |
| Quiz TIU | 85 | 02/10/2026 |
| Tryout SKD #2 | 450 | 03/10/2026 |

**IMPLEMENTASI —** tabel riwayat memuat: nama aktivitas, kategori, jumlah soal, benar/salah, nilai, durasi, tanggal, status, dan aksi (lihat hasil / lanjut kerjakan / lihat pembahasan). Mendukung filter status dan pagination.

---

## 16. Simulasi Try Out CAT (WAJIB — Fitur Utama)

```
Daftar Tryout → Pilih Tryout → Cek akses → Mulai → Pengerjaan → Submit → Penilaian → Hasil
```

**TBD:** struktur TWK/TIU/TKP, jumlah soal per tryout, durasi tryout, dan mekanisme randomisasi (§44).

**IMPLEMENTASI —** halaman daftar tryout menampilkan paket tryout dengan: nama, jumlah soal, durasi, nilai ambang, jumlah peserta,-kuota tersisa, status akses (gratis / premium / terkunci), dan tombol mulai/lanjutkan/lihat hasil.

---

## 17. Timer Pengerjaan (WAJIB)

Timer berjalan selama sesi pengerjaan. Format `HH:MM:SS`.

Ketika waktu habis:

```
Timer = 00:00:00
       ↓
Sistem melakukan auto-submit
       ↓
Penilaian
```

**Business Rule:** nilai dihitung berdasarkan jawaban yang **tersimpan dalam attempt**, bukan jawaban yang masih ada di DOM. Browser yang crash atau ditutup tidak boleh mengubah hasil.

---

## 18. Randomisasi Soal (WAJIB)

Sistem **harus mendukung** randomisasi soal pada sesi tryout/latihan.

> Detail mekanisme belum ditentukan di invoice. Belum dikunci: random urutan soal, random pilihan jawaban, jumlah soal yang diambil dari bank, atau kombinasi semuanya.

**IMPLEMENTASI —** tiap attempt menyimpan snapshot soal + urutan opsi yang sudah diacak saat attempt dibuat. Dengan begitu hasil attempt lama tidak berubah meskipun bank soal diedit admin.

---

## 19. Navigasi Nomor Soal (WAJIB)

Peserta dapat berpindah antar soal melalui palet nomor.

```
01  02  03  04  05
06  07  08  09  10
```

Status soal divisualisasikan: `✓ Sudah dijawab`, `○ Belum dijawab`, `● Soal aktif`, plus status `Ditandai`.

**Fitur tambahan yang sudah ada di prototype dan tetap dipertahankan:** tandai soal (flag), filter "belum dijawab", pintasan keyboard (A–E pilih jawaban, panah kiri/kanan pindah soal, F tandai).

---

## 20. Autosave Jawaban (WAJIB)

```
User memilih jawaban → Jawaban disimpan → User lanjut soal berikutnya
```

Tujuan: mencegah kehilangan jawaban, menjaga progress pengerjaan, dan mempertahankan state pengerjaan.

> Invoice menyebut autosave sebagai fitur. **IMPLEMENTASI:** autosave dikirim ke server (bukan hanya localStorage) dengan throttling ±400 ms, serta tetap menjaga salinan lokal sebagai fallback.

---

## 21. Progress Belajar (WAJIB)

Dashboard menampilkan perkembangan aktivitas belajar.

```
████████░░ 80%
Materi selesai : 8/10
Quiz selesai   : 15/20
```

**IMPLEMENTASI —** indikator: materi selesai, video ditonton, quiz selesai, tryout selesai, streak belajar harian, plus persentase keseluruhan dan progress bar per kategori (TWK/TIU/TKP).

---

## 22. Statistik Nilai (WAJIB)

Peserta dapat melihat statistik hasil pengerjaan:

- Nilai
- Jumlah pengerjaan
- Nilai tertinggi
- Nilai terbaru
- Perkembangan nilai

Invoice secara eksplisit mencantumkan Statistik Nilai dan Grafik Perkembangan Nilai.

---

## 23. Grafik Perkembangan Nilai (WAJIB)

```
Nilai
500 |              ●
450 |         ●
400 |    ●
350 | ●
    +------------------
      T1  T2  T3  T4
```

**IMPLEMENTASI —** grafik line (perkembangan nilai), bar (distribusi nilai), donut (sebaran status jawaban benar/salah/kosong), sparkline (ringkasan di card). Mendukung rentang waktu 7 hari / 30 hari / semua, dan breakdown per kategori.

---

## 24. Sistem Paket Belajar (WAJIB)

```
Paket
 ├── Materi
 ├── Video
 ├── Quiz
 └── Tryout
```

Admin dapat menentukan konten yang tersedia dalam paket.

> **TBD:** harga paket, masa berlaku, jumlah tryout, jumlah materi, sistem subscription, dan apakah paket permanen. Semua ini tidak ditentukan invoice dan wajib dikonfirmasi (§44).

**IMPLEMENTASI —** entitas `package` (nama, harga, masa aktif, kuota tryout, materi & video yang diikutsertakan, fitur-fitur akses), `user_package` (entitlement peserta terhadap paket, masa aktif, kuota terpakai), dan voucher yang menempel pada transaksi.

---

## 25. Payment Gateway (WAJIB)

```
Pilih Paket → Checkout → Payment Gateway → Pembayaran → Status → Akses paket diberikan
```

**TBD:** provider payment gateway (§44).

**IMPLEMENTASI —** kontrak provider diisolasi di balik interface (`PaymentGatewayContract`) dengan implementasi `Fake` (untuk development/demo) dan adapter provider asli (Midtrans/Xendit) yang tinggal ditambahkan. Callback status pembayaran diverifikasi lewat signature provider, bukan request tanpa validasi.

---

## 26. Status Pembayaran (WAJIB)

Minimal sistem memiliki status:

- `Pending`
- `Paid` / `Success`
- `Failed`
- `Expired`

Status final menyesuaikan payment gateway yang dipilih.

**IMPLEMENTASI —** tabel `transactions` menyimpan `status` + `provider` + `provider_reference` + `paid_at` + `raw_payload`, sehingga status dari provider bisa diaudit.

---

## 27. Voucher / Diskon (WAJIB)

Peserta dapat menggunakan voucher saat pembelian.

```
Harga Paket       Rp100.000
Voucher           -Rp20.000
----------------------------
Total              Rp80.000
```

**Admin dapat mengelola voucher.** Minimal: kode voucher, tipe diskon, nilai diskon, status.

> **TBD:** tanggal mulai, tanggal berakhir, minimum transaksi, kuota voucher (§44).

**IMPLEMENTASI —** tipe diskon: `percentage` (potongan persen) dan `fixed` (potongan nominal). Syarat: masa berlaku, minimal transaksi, kuota total, kuota per pengguna, dan voucher eksklusif / bisa dikombinasikan.

---

## 28. Leaderboard (WAJIB)

```
Leaderboard
#1  User A     500
#2  User B     485
#3  User C     470
```

> **TBD:** apakah ranking bersifat per tryout, per kategori, atau global; dan apakah dasar urutannya skor saja atau skor + waktu. Mekanisme ranking wajib ditentukan sebelum implementasi final (§44).

**IMPLEMENTASI —** tabel `leaderboard_entries` menyimpan snapshot `(scope, ref_id, user_id, score, time_spent, rank, achieved_at)` saat attempt selesai, sehingga peringkat historis tetap konsisten walaupun aturan ranking berubah.

---

## 29. Sertifikat Digital (WAJIB)

```
Peserta memenuhi kondisi → Sistem generate sertifikat → Sertifikat dapat dilihat → Download
```

> **TBD:** trigger penerbitan sertifikat (§44) — misalnya menyelesaikan paket, menyelesaikan program, menyelesaikan tryout, atau mencapai nilai tertentu.

**IMPLEMENTASI —**once trigger disepakati, penerbitan dilakukan pada event yang sama dengan penyelesaian/scoring. Sertifikat punya nomor unik, nama peserta, kriteria yang dicapai, tanggal terbit, dan QR verification link publik.

---

## 30. Laporan Transaksi (WAJIB)

Admin dapat melihat transaksi yang terjadi di platform.

| ID | User | Paket | Total | Status |
| --- | --- | --- | --- | --- |
| INV001 | User A | Paket Premium | Rp99.000 | Paid |
| INV002 | User B | Paket Basic | Rp49.000 | Pending |

Invoice mencantumkan Laporan Transaksi sebagai fitur.

**IMPLEMENTASI —** tabel transaksi + filter (status, rentang tanggal, paket, peserta), ringkasan total di bagian atas (pendapatan, transaksi berhasil, pending, gagal), ekspor CSV.

---

## 31. Admin Panel (WAJIB)

```
PESERTA                          ADMIN
├── Dashboard                    ├── Dashboard
├── Materi                       ├── Kelola Materi
├── Video                        ├── Bank Soal & Pembahasan
├── Artikel                      ├── Artikel
├── Bank Soal                    ├── Peserta
├── Quiz                         ├── Paket Belajar
├── Tryout CAT                   ├── Transaksi
├── Hasil                        └── Voucher
├── Pembahasan
├── Riwayat
├── Statistik
├── Progress
├── Leaderboard
├── Paket
├── Payment
├── Voucher
└── Sertifikat
```

---

## 32. Admin Dashboard (WAJIB)

Ringkasan data platform:

- Total Peserta
- Total Materi
- Total Soal
- Total Tryout
- Total Transaksi
- Pendapatan

Angka dan widget final disesuaikan dengan data yang benar-benar tersedia dalam sistem.

**IMPLEMENTASI —** stat card (peserta, soal, tryout, pendapatan), grafik pendapatan 6 bulan, transaksi terbaru, aktivitas tryout terbesar, kartu "Perlu Tindakan", sebaran peserta, dan konten terakhir.

---

## 33. Kelola Materi & Bidang (WAJIB)

Admin dapat: membuat / mengedit / menghapus bidang; membuat / mengedit / menghapus materi; mengunggah PDF; mengelola video.

**Struktur bidang (IMPLEMENTASI):** `TWK`, `TIU`, `TKP`, `Umum`, dan bidang bebas — dikelola penuh di `/admin/sections` (§48.2). Tiap bidang punya tipe konten (PDF / Video / Artikel / Campuran).

**Struktur materi (IMPLEMENTASI):** `Bidang → Materi → Isi (PDF atau Video)`.
Setiap materi **milik satu bidang** lewat `section_id` — FK tunggal, bukan pivot,
supaya satu materi tidak muncul di dua bidang dan filter peserta tidak ambigu.

---

## 34. Kelola Soal & Pembahasan (WAJIB)

```
Bidang
   ↓
Soal
   ├── Pilihan A
   ├── Pilihan B
   ├── Pilihan C
   ├── Pilihan D
   └── Pilihan E
         ↓
   Jawaban benar
         ↓
     Pembahasan
```

Admin dapat: menambahkan soal, mengedit soal, menghapus soal, menentukan bidang, menentukan pilihan jawaban, menentukan jawaban benar, memasukkan pembahasan.

**IMPLEMENTASI —** form soal berbasis pilihan ganda 5 opsi (fleksibel 2–10 opsi), dengan pratinjau langsung tampilan soal, upload gambar/video soal, dan checklist validasi sebelum simpan.

---

## 35. Kelola Peserta (WAJIB)

```
Nama · Email · Tanggal Registrasi · Status Akun · Paket · Aktivitas
```

Admin dapat melihat detail peserta, mengubah data, dan mengaktifkan/nonaktifkan akun.

**IMPLEMENTASI —** tabel peserta dengan pencarian, filter (status, paket, tanggal), aksi (lihat detail, ubah role/status, reset password, hapus), plus panel detail berisi ringkasan aktivitas & nilai peserta.

---

## 36. Artikel Management (WAJIB)

Admin dapat: membuat artikel, mengedit artikel, menghapus artikel, mengatur kategori artikel, dan menerbitkan artikel.

**IMPLEMENTASI —** editor artikel dengan penublish draf, cover image, slug otomatis, dan preview sebelum publish. Artikel dapat dipublikasikan ke halaman publik maupun ditampilkan di dashboard peserta.

---

## 37. Data Model / Database Concept

Struktur data minimal (rancangan teknis konseptual untuk membantu development, **bukan struktur database yang secara eksplisit diberikan client/invoice**; struktur final dapat disederhanakan atau disesuaikan setelah flow final ditentukan):

```
users                    sections                materials
videos                   articles                questions
question_options         question_explanations

quizzes                  quiz_questions          quiz_attempts      quiz_answers
tryouts                  tryout_questions        tryout_attempts    tryout_answers
tryout_attempt_questions

packages                 package_items           user_packages
transactions             transaction_items       vouchers           voucher_usages
leaderboard_entries      certificates            certificates_items
```

**Catatan arsitektur (IMPLEMENTASI):**

- Tabel quiz & tryout sengaja tidak digabung karena jumlah kolom & aturan akses berbeda; keduanya memakai pola `*_attempt_questions` yang menyimpan **snapshot** soal, urutan opsi, dan jawaban agar attempt lama tidak berubah.
- `sections` dipakai bersama oleh materi, video, artikel, dan soal lewat **FK tunggal `section_id`**, supaya satu taksonomi cukup. Tanpa pivot — satu konten belong ke tepat satu bidang.
- **PDF dan video satu tabel.** Kolom `videos` di daftar atas tidak dipakai: `materials` menyimpan keduanya lewat `type`, jadi upload, progress, dan entitlement cukup sekali ditangani. Kalau video nanti butuh tabelnya sendiri (mis. transkrip, chapter), barulah `videos` dipisah.
- `payments` dipisah dari `transactions` jika nanti ada refund atau installment.

---

## 38. Access Control

```
Guest          → Public Content
Peserta        → Free Content
Peserta + Paket → Premium Content
```

Peserta **tidak boleh** mengakses konten premium tanpa entitlement/akses yang valid.

**IMPLEMENTASI —** satukan lewat satu helper `hasAccess($content)` yang mengecek:

1. `is_public` → true
2. `required_package_id` null → true
3. ada `user_package` aktif dengan `package_id` yang cocok → true

Helper ini dipakai konsisten oleh route, policy, query, dan blade partial, supaya tidak ada celah di satu layer saja.

**IMPLEMENTASI —** untuk materi, helper-nya hidup di
`Material::isAccessibleBy(?User $user)`:

1. admin → true (perlu pratinjau draft & premium sebelum terbit)
2. `access_level = free` → true
3. `access_level = premium` → butuh `hasActivePackage()`

**Point 3 belum aktif.** `hasActivePackage()` selalu `false` karena tabel
`packages` / `user_packages` belum ada (§24, TBD-08). Jadi premium masih
selalu terkunci untuk peserta — disengaja, supaya tidak ada materi premium
yang terbuka tanpa checked. Begitu tabel paket ada, hanya isi satu method itu
yang perlu diubah; sisanya sudah siap.

Berkas PDF tidak pernah disajikan lewat URL publik — semuanya lewat
`materials.file` (§10.4). Ini yang membuat gating di atas benar-benar berarti.

---

## 39. Business Rules

| # | Rule |
| --- | --- |
| 1 | **Authentication** — Fitur yang membutuhkan akun hanya dapat diakses setelah peserta login. |
| 2 | **Premium Content** — Konten premium hanya dapat diakses oleh peserta yang memiliki akses terhadap paket terkait. |
| 3 | **Payment** — Akses paket diberikan setelah status pembayaran dinyatakan berhasil. |
| 4 | **Tryout** — Tryout memiliki sesi pengerjaan dan timer. |
| 5 | **Autosave** — Jawaban peserta harus disimpan selama proses pengerjaan. |
| 6 | **Time Limit** — Jika waktu tryout habis, sistem melakukan submit terhadap pengerjaan. |
| 7 | **Scoring** — Nilai dihitung berdasarkan jawaban yang tersimpan dalam attempt. |
| 8 | **History** — Hasil pengerjaan yang sudah selesai disimpan sebagai riwayat peserta. |

**Rule tambahan yang muncul dari implementasi (perlu konfirmasi client):**

| # | Rule | Alasan |
| --- | --- | --- |
| 9 | Satu browser hanya boleh punya satu attempt `IN_PROGRESS` per tryout. | Mencegah answer farming dan attempt yang tidak fair sehingga merusak leaderboard. |
| 10 | Attempt `SUBMITTED` bersifat read-only. | Menjaga integritas riwayat & leaderboard historis. |
| 11 | Kuota tryout hanya berkurang setelah attempt di-*submit*. | Menghormati waktu peserta yang keluar di tengah jalan. |
| 12 | Admin yang menonaktifkan akun membatalkan sesi aktif peserta. | Mencegah akun nonaktif tetap mengerjakan tryout. |
| 13 | Submit bersifat idempoten. | Mencegah double-submit (tomboldobbel / refresh) menghasilkan dua attempt. |

---

## 40. Non-Functional Requirements

### 40.1 Responsive (WAJIB)

Desktop, tablet, smartphone. Dipercayakan ke satu design system (design tokens + component library), bukan diimplementasikan ulang per halaman.

### 40.2 Performance

- Halaman utama dan dashboard harus dibuat seefisien mungkin, **tanpa query/database request yang tidak diperlukan**.
- Lazy load gambar & video; target LCP < 2,5 detik di koneksi 3G.
- Eager load font & critical CSS; chart & asset berat dimuat sesuai kebutuhan halaman.
- Query di-numbering & di-cache untuk halaman yang didatangi banyak peserta (leaderboard, statistics, dashboard).
- Pagination pada semua tabel besar.

### 40.3 Security (WAJIB)

- Password di-hash (bcrypt/argon2, Never plain text)
- Authentication memakai session/token yang aman
- Authorization berdasarkan role
- Validasi input (server side, lewat Form Request)
- Proteksi CSRF
- Validasi file upload (mime, ekstensi, ukuran)
- Validasi akses konten premium
- Rate limiting pada login, register, dan checkout
- Logout meng-invalidasi session

### 40.4 Data Integrity

Jawaban tryout harus tersimpan dengan benar agar hasil penilaian tidak berubah akibat refresh atau perpindahan halaman.

**Uji wajib:** refresh di tengah tryout, tutup tab lalu buka lagi, kirim jawaban bersamaan dari dua tab, dan menjawab pada detik terakhir sebelum timer habis.

---

## 41. Tryout State Management

```
NOT_STARTED → IN_PROGRESS → SUBMITTED
```
```
IN_PROGRESS → TIMEOUT → SUBMITTED
```

Backend mengetahui kondisi setiap pengerjaan dari `attempt.status`, sehingga auto-submit dan submit manual bisa dibedakan pada riwayat.

**Status tambahan:** `EXPIRED` (attempt dari sesi lama yang tidak pernah dilanjutkan) — **TBD** apakah perlu TTL khusus.

---

## 42. Acceptance Criteria

### 42.1 Authentication

- [ ] User dapat register
- [ ] User dapat login
- [ ] User dapat logout
- [ ] User yang belum login tidak dapat mengakses halaman private

### 42.2 Learning

- [ ] Peserta dapat melihat materi
- [ ] Peserta dapat membuka PDF
- [ ] Peserta dapat mengakses video
- [ ] Peserta dapat membaca artikel
- [ ] Progress dapat ditampilkan

### 42.3 Quiz

- [ ] Peserta dapat memulai quiz
- [ ] Peserta dapat menjawab soal
- [ ] Sistem menghitung nilai
- [ ] Peserta dapat melihat pembahasan
- [ ] Riwayat tersimpan

### 42.4 Tryout

- [ ] Peserta dapat memulai tryout
- [ ] Timer berjalan
- [ ] Soal dapat dinavigasikan
- [ ] Jawaban autosave
- [ ] Soal dapat dirandomisasi
- [ ] Tryout dapat disubmit
- [ ] Sistem auto-submit ketika waktu habis
- [ ] Nilai dapat dihitung
- [ ] Hasil tersimpan

### 42.5 Statistik

- [ ] Peserta dapat melihat statistik
- [ ] Peserta dapat melihat perkembangan nilai
- [ ] Peserta dapat melihat leaderboard
- [ ] Riwayat pengerjaan dapat dilihat

### 42.6 Payment

- [ ] Peserta dapat memilih paket
- [ ] Peserta dapat checkout
- [ ] Payment gateway dapat digunakan
- [ ] Status pembayaran dapat diketahui
- [ ] Paket diberikan setelah pembayaran berhasil
- [ ] Voucher dapat digunakan

### 42.7 Admin

- [ ] Admin dapat login
- [ ] Admin dapat melihat dashboard
- [ ] Admin dapat mengelola materi
- [ ] Admin dapat mengelola bidang
- [ ] Admin dapat mengelola soal
- [ ] Admin dapat mengelola pembahasan
- [ ] Admin dapat mengelola peserta
- [ ] Admin dapat mengelola artikel
- [ ] Admin dapat melihat laporan transaksi

---

## 43. Definition of Done

Project dianggap selesai apabila:

1. Seluruh fitur yang tercantum dalam scope telah diimplementasikan
2. User dapat melakukan register/login
3. User dapat mengakses dashboard
4. Sistem pembelajaran dapat digunakan
5. Bank soal dan quiz dapat digunakan
6. Tryout CAT dapat dikerjakan
7. Timer dan autosave berjalan
8. Sistem menghasilkan nilai
9. Pembahasan dapat ditampilkan
10. Riwayat dan statistik dapat ditampilkan
11. Sistem paket dan payment dapat digunakan
12. Voucher dapat digunakan
13. Leaderboard dapat ditampilkan
14. Sertifikat dapat dihasilkan sesuai rule yang telah ditentukan
15. Admin dapat mengelola konten utama
16. Website responsive
17. Tidak terdapat bug kritis yang menghambat penggunaan fitur utama

---

## 44. Scope yang BELUM DIKUNCI — TBD Register

> **Ini bagian paling penting dari PRD.**
> Item di bawah ini **tidak** ada di invoice. Ada kebutuhan bisnisnya, tapi bentuk teknis / komersialnya belum pernah disepakati client.
> **Jangan diperlakukan sebagai sudah disetujui** — dan jangan dikunci di kode sebelum dikunci di sini.

| # | Item | Status | Dampak bila belum diputuskan |
| --- | --- | --- | --- |
| TBD-01 | Payment gateway provider | TBD | Blokir fase payment & checkout |
| TBD-02 | Rumus scoring CPNS | TBD | Blokir fase scoring & leaderboard |
| TBD-03 | Struktur TWK/TIU/TKP | **Selesai** — satu tabel `sections`, bidang bebas, tanpa sub-kategori (§48.2) | Taksonomi soal & materi tidak lagi terblokir. Yang belum diputuskan: **bobot/jumlah soal per bidang** (TBD-04) dan **rumus scoring** (TBD-02) |
| TBD-04 | Jumlah soal per tryout | TBD | Blokir konfigurasi tryout |
| TBD-05 | Durasi tryout | TBD | Blokir timer & sesi attempt |
| TBD-06 | Mekanisme randomisasi | TBD | Blokir engine tryout |
| TBD-07 | Mekanisme leaderboard | TBD | Blokir tabel & query ranking |
| TBD-08 | Masa berlaku paket | TBD | Blokir logika entitlement |
| TBD-09 | Harga paket | TBD | Blokir harga & transaksi |
| TBD-10 | Trigger sertifikat | TBD | Blokir fitur sertifikat |
| TBD-11 | Hosting video | TBD | Blokir modul video |
| TBD-12 | Download / view-only PDF | TBD | Blokir proteksi konten |
| TBD-13 | Sistem subscription | TBD | Blokir billing berulang |
| TBD-14 | Minimum transaksi voucher | TBD | Blokir logika voucher |
| TBD-15 | Kuota voucher | TBD | Blokir logika voucher |

**Di luar scope (sudah disepakati tidak dikerjakan):**

| Item | Status |
| --- | --- |
| Email notification | Belum termasuk scope |
| WhatsApp notification | Belum termasuk scope |
| Mobile application | Tidak termasuk scope |

**Saran 자원 tambah (masih TBD, perlu persetujuan):**

- Google / social login
- Halaman blog publik
- Ekspor PDF sertifikat dengan template custom
- Multi bahasa

---

## 45. Phase 2 / Future Development

Agar scope Rp4 juta tetap terkendali, fitur berikut diposisikan sebagai pengembangan berikutnya **apabila client meminta**:

- Mobile App Android/iOS
- AI Tutor CPNS
- AI-generated question
- Live class
- Forum peserta
- Chat dengan tutor
- Subscription otomatis
- Referral system
- Affiliate system
- Notification WhatsApp
- Notification email
- Advanced analytics
- Multiple admin roles
- CBT/CAT proctoring
- Anti-cheating system
- Webcam monitoring
- Question import Excel
- Question import Word/PDF
- Ranking nasional yang kompleks

Fitur-fitur tersebut **tidak termasuk scope invoice saat ini**.

---

## 46. Project Constraint

Berdasarkan invoice:

| Item | Nilai |
| --- | --- |
| Total sebelum diskon | Rp5.100.000 |
| Diskon | Rp1.100.000 |
| **Total project** | **Rp4.000.000** |
| DP | Rp1.200.000 |
| Final payment | Rp2.800.000 |

Estimasi penyelesaian: **20 Oktober 2026, pukul 14.00 WITA**.

Pengerjaan dimulai setelah **H+1 DP diterima**, dan revisi minor tidak mencakup perubahan flow atau penambahan fitur.

---

## 47. Lampiran — Peta Halaman

Semua halaman sudah tersedia sebagai prototype statis. Slicing ke Blade mempertahankan tampilan **persis sama** dengan prototype.

### 47.1 Publik & Autentikasi

| Halaman | Route | Sumber prototype |
| --- | --- | --- |
| Landing | `/` | `index.html` |
| Login | `/login` | `login.html` |
| Register | `/register` | `register.html` |

### 47.2 Peserta

| Halaman | Route | Sumber prototype |
| --- | --- | --- |
| Dashboard | `/dashboard` | `dashboard.html` |
| Materi | `/materials` | `materi.html` |
| Baca Materi | `/materials/{id}` | `materi.html` (pembaca PDF) |
| Bank Soal | `/question-bank` | `bank-soal.html` |
| Latihan (Quiz) | `/quizzes` | `quiz.html` |
| Kerjakan Latihan | `/quizzes/{quiz}/attempt` | `latihan-kerjakan.html` |
| Tryout CAT | `/tryouts` | `tryout.html` |
| Kerjakan Tryout | `/tryouts/{tryout}/attempt` | `tryout-kerjakan.html` |
| Hasil Tryout | `/tryouts/{tryout}/result` | `tryout-hasil.html` |
| Riwayat | `/history` | `riwayat.html` |
| Statistik | `/statistics` | `statistik.html` |
| Leaderboard | `/leaderboard` | `leaderboard.html` |
| Paket Belajar | `/packages` | `paket.html` |
| Checkout | `/checkout` | `checkout.html` |
| Sertifikat | `/certificates` | `sertifikat.html` |
| Profil | `/profile` | `profil.html` |

### 47.3 Admin

| Halaman | Route | Sumber prototype |
| --- | --- | --- |
| Admin Dashboard | `/admin` | `admin/index.html` |
| Kelola Materi | `/admin/materials` | `admin/materi.html` |
| Bidang | `/admin/sections` | `admin/materi.html` (modal kategori) |
| Form Materi | `/admin/materials/create` | `admin/materi.html` (modal materi) |
| Soal & Pembahasan | `/admin/questions` | `admin/soal.html` |
| Form Soal | `/admin/questions/create` | `admin/soal-form.html` |
| Artikel | `/admin/articles` | `admin/artikel.html` |
| Peserta | `/admin/users` | `admin/peserta.html` |
| Paket Belajar | `/admin/packages` | `admin/paket.html` |
| Transaksi | `/admin/transactions` | `admin/transaksi.html` |
| Voucher | `/admin/vouchers` | `admin/voucher.html` |

---

## 48. Lampiran — Design System

Nama internal design system: **LONTAR**.

**Karakter visual:** "sticker" / neo-brutalist lembut — border tebal, hard shadow tanpa blur, radius besar, palet popsatur namun tetap hangat (kertas krem), tipografi tegas.

**Berkas design token:**

| Token grup | Nilai inti |
| --- | --- |
| Brand | `--primary: #0191ED` + 5 turunan |
| Pop accents | pink `#FF6B9D`, amber `#FFC53D`, mint `#2DD4A7`, coral `#FF7A5C`, violet `#8B5CF6` |
| Netral | bg krem `#FFFDF7`, ink `#16324F`, border `#DCE8F2` |
| Semantik | success `#12A97C`, warning `#E8A317`, danger `#E5484D` |
| Tipografi | Display `Outfit`, Body `Plus Jakarta Sans`, Mono `JetBrains Mono` |
| Radius | 6 / 10 / 16 / 24 / 32 / full |
| Shadow | Hard shadow 2→12px, semua berbasis warna `--ink` |
| Motion | 140 / 240 / 420 ms, ease-pop `cubic-bezier(.34,1.56,.64,1)` |

**Aturan pemotongan Blade:** class, struktur DOM, dan urutan asset wajib **identik** dengan prototype. Yang berubah hanya hal berikut:

| Aspek | Prototype | Implementasi Laravel |
| --- | --- | --- |
| Layout | Inline per halaman | `layouts/*/main.blade.php` + `partials/` |
| Navigasi | `shell.js` render via JS | `layouts/{admin,user}/partials/sidebar.blade.php` (markup literal + `request()->routeIs()`) |
| Route | `materi.html` | `route('materials.index')` |
| Ikon | `icons.js` hydration | Tetap `<svg data-icon>` + `icons.js` |
| Data | Hardcoded di HTML | Hardcoded di view (fase ini), siap ditukar query |
| Identitas user | Hardcoded di `shell.js` | `auth()->user()` di dalam partial navbar/sidebar |
| Form | `data-validate` palsu | Nanti diganti Form Request + validasi server |
| Link `onclick` JS | `location.href='x.html'` | `route()` / `wire` |

### 48.1 Struktur yang sudah dibuat

**Design system (disalin verbatim dari prototype, tanpa modifikasi):**

| Berkas | Asal |
| --- | --- |
| `resources/css/lontar/tokens.css` | `prototype/assets/css/tokens.css` |
| `resources/css/lontar/base.css` | `prototype/assets/css/base.css` |
| `resources/css/lontar/components.css` | `prototype/assets/css/components.css` |
| `resources/js/lontar/icons.js` | `prototype/assets/js/icons.js` |
| `resources/js/lontar/app.js` | `prototype/assets/js/app.js` |
| `resources/js/lontar/charts.js` | `prototype/assets/js/charts.js` |
| `resources/js/lontar/quiz.js` | `prototype/assets/js/quiz.js` |
| `resources/js/lontar/data/bank-skd-5.js` | `prototype/assets/data/bank-skd-5.js` |
| `resources/js/lontar/data/bank-latihan-1.js` | `prototype/assets/data/bank-latihan-1.js` |

> `prototype/assets/js/shell.js` **tidak** disalin — perannya digantikan oleh Blade component.
> Font Outfit + Plus Jakarta Sans di-host lewat bunny.net oleh plugin `laravel:fonts` (bukan Google Fonts, agar tidak bergantung pada domain yang sering diblokir di Indonesia).

**Layout & component:**

```
resources/views/
├── layouts/
│   ├── base.blade.php      <html>/<head>/@vite/@fonts
│   ├── public.blade.php    landing page (nav + <main> + footer)
│   ├── guest.blade.php     login/register (header & footer sendiri)
│   ├── exam.blade.php      tryout-bar, tanpa sidebar
│   ├── admin/
│   │   ├── main.blade.php          shell Admin Panel
│   │   └── partials/
│   │       ├── navbar.blade.php    top bar + dropdown akun
│   │       ├── sidebar.blade.php   menu admin
│   │       └── footer.blade.php    footer tipis
│   └── user/
│       ├── main.blade.php          shell area peserta
│       └── partials/
│           ├── navbar.blade.php
│           ├── sidebar.blade.php
│           └── footer.blade.php
└── components/              Blade component yang dipakai lintas layout
    ├── logo.blade.php       <x-logo :sub="…" />
    ├── flash.blade.php      flash Laravel → toast LONTAR
    ├── pager.blade.php      paginator Laravel → .pager / .pager__btn
    ├── public-nav.blade.php
    └── footer.blade.php     footer besar untuk landing page
```

### 48.1a Aturan sidebar

Tidak ada file config untuk menu. Setiap partial sidebar menulis markahnya
sendiri, dan aturan yang dipakai konsisten di seluruh aplikasi:

| Aturan | Cara |
| --- | --- |
| Status aktif | `request()->routeIs('admin.users.*')` — **bukan** variabel `$active` dari controller |
| Grupos aktif ikut | Pakai wildcard `*`, bukan `.index`, supaya `edit` / `create` tetap menyorot induknya |
| Gate role | `@if (auth()->user()?->isAdmin())` |
| Ikon | Tetap `<svg data-icon="…">`, dihidrasi `resources/js/lontar/icons.js` |
| Class | Selalu class LONTAR (`.side__link`, `.side__group`, `.side__title`) |

Menyorot menu tidak lagi butuh controller yang mengirim `active`, jadi
`SliceController::slice()` sekarang hanya menerima nama view dan data opsional.
Ini menghapus satu sumber kebenaran yang harus dijaga sinkron.

### 48.1 Struktur view

Struktur folder mengikuti area aplikasi, bukan tabel database. Semua view berada di
`resources/views/` dan tidak perlu suffix `.blade` yang berulang.

```
resources/views/
├── LandingPage/
│   └── index.blade.php
├── auth/
│   ├── login.blade.php
│   └── register.blade.php
└── Dashboard/
    ├── Admin/                          ← area admin
    │   ├── index.blade.php             ← admin dashboard
    │   ├── users/                      ← pengelolaan user (CRUD, SUDAH DIKERJAKAN)
    │   │   ├── index.blade.php
    │   │   ├── create.blade.php
    │   │   ├── edit.blade.php
    │   │   └── _form.blade.php         ← partial form bersama
    │   ├── sections/                     ← pengelolaan bidang (CRUD, SUDAH DIKERJAKAN)
    │   │   ├── index.blade.php
    │   │   ├── create.blade.php
    │   │   ├── edit.blade.php
    │   │   └── _form.blade.php         ← partial form bersama
    │   ├── materials/                     ← pengelolaan materi (CRUD, SUDAH DIKERJAKAN)
    │   │   ├── index.blade.php
    │   │   ├── create.blade.php
    │   │   ├── edit.blade.php
    │   │   └── _form.blade.php         ← partial form bersama
    │   ├── questions/{index,create}.blade.php
    │   ├── articles/index.blade.php
    │   ├── packages/index.blade.php
    │   ├── transactions/index.blade.php
    │   └── vouchers/index.blade.php
    └── User/                           ← area peserta
        ├── index.blade.php             ← dashboard peserta
        ├── materials/
        │   ├── index.blade.php         ← daftar materi (data nyata)
        │   └── show.blade.php          ← halaman baca PDF / video
        ├── question-bank/index.blade.php
        ├── quizzes/{index,attempt}.blade.php
        ├── tryouts/{index,attempt,result}.blade.php
        ├── history/index.blade.php
        ├── statistics/index.blade.php
        ├── leaderboard/index.blade.php
        ├── packages/index.blade.php
        ├── checkout/index.blade.php
        ├── certificates/index.blade.php
        └── profile/edit.blade.php
```

**Konvensi nama file per sumber daya:** `index.blade.php`, `create.blade.php`,
`edit.blade.php`. Partial yang dipakai bersama lebih dari satu halaman diberi
awalan garis bawah (`_form.blade.php`).

### 48.2 Fitur yang sudah fungsional

| Fitur | Status |
| --- | --- |
| **Login & logout** | **Fungsional** — session, rate limit, proteksi akun nonaktif, redirect per role |
| **Pengelolaan user (Admin)** | **Fungsional** — `admin/users` dengan `index`, `create`, `edit`, `toggle status`, `delete` |
| **Pengelolaan bidang (Admin)** | **Fungsional** — `admin/sections` dengan `index`, `create`, `edit`, `delete` (soft delete) |
| **Pengelolaan materi (Admin)** | **Fungsional** — `admin/materials` dengan `index`, `create`, `edit`, `delete`, `toggle status`, unggah PDF |
| **Materi untuk peserta** | **Fungsional** — `/materials` + `/materials/{id}` (baca PDF / tonton video) + `/materials/{id}/file`, dengan gating free/premium. PDF lewat viewer sendiri tanpa tombol unduh/cetak (§10.5) |
| Sisa halaman | Tampilan saja, isi halaman masih data demo dari prototype |

#### A. Autentikasi

| Aspek | Implementasi |
| --- | --- |
| Login | `POST /login`, validasi server, `Auth::attempt()` |
| Logout | `POST /logout`, session di-invalidate, token di-regenerate |
| Session fixation | `session()->regenerate()` setelah login, `invalidate()` setelah logout |
| Rate limit | 5 percobaan per kombinasi email + IP |
| Akun nonaktif | Ditolak saat login **dan** sesi aktifnya dicabut di request berikutnya (PRD Aturan 12) |
| Role | Alias middleware `role:admin`; peserta yang coba ke `/admin` dapat 403 |
| Redirect | Admin → `/admin`, Peserta → `/dashboard`, dengan dukungan *intended URL* |
| Guest guard | 22 route terverifikasi 302 ke `/login` |
| App shell | Appbar & sidebar membaca `auth()->user()`, bukan config hardcoded |

#### B. Pengelolaan user

- Daftar peserta dengan pencarian (nama / email / telepon / instansi), filter
  role & status, sortable columns, dan pagination
- 4 stat card dari data nyata (total pengguna, akun aktif, administrator, akun nonaktif)
- Form create & edit dengan validasi server (Form Request) dan pesan error per field
- Role `admin` / `user` sebagai enum, status `active` / `inactive` sebagai enum
- Soft delete, plus proteksi: admin tidak bisa menghapus atau menonaktifkan akunnya sendiri
- Password di-hash otomatis; password kosong saat edit berarti "tidak diubah"
- Flash message Laravel ditampilkan sebagai toast LONTAR

**Tabel `users`** tambahan kolom: `role`, `status`, `phone`, `address`,
`institution`, `avatar_path`, `last_login_at`, `deleted_at`.

Akun demo dari seeder: `admin@lontar.asia` / `password`, peserta
`peserta@lontar.asia` / `password`, dan 34 peserta contoh lainnya dengan email acak.

#### C. Pengelolaan bidang

Halaman `admin/sections` — CRUD penuh atas **bidang**: kelompok besar konten.
Daftarnya **data, bukan enum tertutup**, jadi TWK/TIU/TKP/Umum hanya isi awal
dan admin bebas menambah, mengubah, atau menghapus bidang apa saja.

| Kolom | Fungsi |
| --- | --- |
| `name` | Nama bidang, unik. Contoh: `TWK`, `Bahasa Indonesia` |
| `slug` | Untuk URL, unik, otomatis dari nama bila dikosongkan |
| `code` | Kode unik kapital, dipakai di soal dan tryout. Contoh: `TWK`, `BIN` |
| `description` | Penjelasan singkat, opsional |
| `position` | Urutan tampil, kecil lebih dulu |
| `is_active` | Nonaktif → disembunyikan dari pilihan peserta |
| `icon` | Nama ikon dari LONTAR icon set, opsional |
| `tone` | Warna dari palet LONTAR, opsional |
| `created_by`, `updated_by` | Jejak audit, nullable |
| `deleted_at` | Soft delete — bidang yang masih dirujuk tidak hilang permanen |

Aturan yang ditegakkan:

| Aturan | Kenapa |
| --- | --- |
| Kode dinaikkan kapital otomatis | `bin` → `BIN` |
| Kode hanya `A-Z0-9-` | Mencegah kode ambigu saat dipakai di soal |
| Slug otomatis & unik | `Sejarah` lalu `Sejarah!` → `sejarah`, `sejarah-2` |
| **TWK/TIU/TKP boleh dihapus** | Tidak ada lagi proteksi "bidang inti". Kalau hanya ingin menyembunyikan dari peserta, nonaktifkan — isinya tetap aman |
| Ikon/warna boleh kosong | `Section::resolvedTone()` dan `resolvedIcon()` menebak dari `position` supaya tampilan tidak kosong |
| Warna harus punya class CSS | Test menjaga tiap tone punya `.badge--*` dan `.ico--*`. Tanpa itu, badge diam-diam tampil tanpa warna |

Enum `ColorTone` encapsulate pemetaan warna ke CSS: `iconModifier()` (`.ico--*`),
`badgeClass()`, `backgroundVar()`, dan `foreground()` — putih kecuali amber/mint
yang terlalu terang. Form edit menampilkan pratinjau ikon dan warna yang
langsung berubah mengikuti pilihan.

Tabel `materials` dan `articles` menunjuk ke sini lewat `section_id`.
**Tidak ada lagi layer sub-kategori.**

#### D. Pengelolaan materi

Halaman `admin/materials` — CRUD materi PDF dan video milik satu bidang.

| Kolom | Fungsi |
| --- | --- |
| `section_id` | Bidang pemilik. FK tunggal, `cascadeOnDelete` |
| `title` | Judul materi. **Unik per bidang**, bukan global |
| `slug` | Untuk URL, unik, otomatis dari judul bila kosong |
| `summary` | Ringkasan yang tampil di kartu materi |
| `type` | `pdf` \| `video` — enum `MaterialType` |
| `access_level` | `free` \| `premium` — enum `MaterialAccess` (§38) |
| `status` | `draft` \| `published` — enum `MaterialStatus` |
| `file_path`, `file_size`, `page_count` | Sumber & ukuran PDF |
| `video_url`, `duration_minutes` | Sumber & durasi video |
| `position` | Urutan tampil di dalam bidang |
| `published_at` | Terisi saat pertama kali terbit, dikosongkan saat kembali ke draft |
| `created_by`, `updated_by` | Jejak audit, nullable |
| `deleted_at` | Soft delete |

Tiga aturan yang jadi inti modul ini:

| Aturan | Kenapa |
| --- | --- |
| **Kolom sumber mengikuti format** | PDF tidak menyimpan `video_url`, video tidak menyimpan berkas. Kombinasi tidak masuk akal dinormalisasi jadi `null`, bukan disimpan setengah |
| **Terbit berarti punya sumber** | Materi `published` tanpa PDF/URL ditolak di server. Kalau tidak, peserta melihat kartu yang tidak bisa dibuka — masalah baru, bukan solusi |
| **Berkas lama dihapus setelah yang baru tersimpan** | Urutannya penting: kalau gagal di tengah, materi lama tidak ikut kehilangan isinya |

Catatan lain:

- Judul unik **per bidang** — dua bidang boleh punya materi bernama sama.
- Mengganti format PDF → video membuang berkas lama dari disk.
- `toggle status` (`PATCH /admin/materials/{material}/status`) menerbitkan atau
  menahan materi tanpa membuka form. Menahan turun ke draft juga mengosongkan
  `published_at`.
- Soft delete **tidak** menghapus berkas PDF, supaya materi bisa dipulihkan tanpa
  admin mengunggah ulang.
- Level akses &ldquo;Paket Tertentu&rdquo; di prototype **belum tersedia** —
  butuh tabel `packages` (§24).
- Tiga enum (`MaterialType`, `MaterialAccess`, `MaterialStatus`) encapsulate
  label, badge, dan ikon supaya view tidak menulis string warna sendiri.
- Halaman peserta `/materials` masih data prototype; materi nyata belum
  disambung ke sana (§48.4).

### 48.3 Yang belum ada (dan memang tidak ada di prototype)

Tidak ada route untuk halaman detail materi, detail bank soal, detail paket,
halaman settings terpisah, atau checkout per-paket — prototype tidak punya halaman
tersebut, jadi route-nya sengaja tidak dibuat. Kalau client memintanya, itu
penambahan flow, bukan revisi minor.

### 48.4 Utang teknis fase berikutnya

Semua item di bawah ini **harus** dikerjakan sebelum halaman dianggap production-ready:

1. **Register** — form masih tampilan prototype. Butuh `RegisterController::store()`,
   verifikasi email, dan `middleware('verified')`.
2. **Lupa password** — butuh mailer + route reset password.
3. **Ganti kolom tabel di daftar peserta** — kolom Paket / Aktivitas / Nilai Terakhir
   menunggu tabel `packages`, `attempts`, dan `leaderboard_entries` (§37). Sekarang
   diganti Role / Telepon / Registrasi / Login Terakhir supaya tidak menampilkan
   angka palsu.
4. **Sambungkan isi halaman ke data nyata** — app shell sudah pakai user yang login,
   tapi *isi* halaman masih hardcoded (mis. "Rani Kusuma" di leaderboard mini
   dashboard). Modul **admin** (user, bidang, materi) dan halaman **materi peserta**
   sudah tersambung; yang belum: dashboard peserta, bank soal, tryout,
   statistik, leaderboard.
5. **Ganti `data-validate`** dengan Form Request + validasi server (sudah dilakukan
   di modul user, login, bidang, dan materi; belum di modul lain).
6. **Pindahkan state tryout dari `localStorage` ke server** (§20, §41).
7. **Ganti bank soal hardcoded** dengan tabel `questions` + pengacakan (§18).
8. **Terapkan Aturan 38** (access control konten premium) di route, policy, dan query.
   `Material::isAccessibleBy()` sudah jadi titik tunggalnya dan premium sudah
   ditolak untuk peserta, tapi belum ada pembeda paket mana yang memenuhi —
   butuh `packages` / `user_packages` (§24, TBD-08).
9. **Policy per resource** — sekarang Admin Panel hanya dijaga `role:admin`;
   tiap resource butuh Policy sendiri untuk `authorize()` per aksi.
10. **Upload PDF: periksa isi berkas** — `mimes:pdf` hanya melihat ekstensi.
    File `.pdf` palsu berisi HTML akan lolos. Perlu validasi magic byte
    (`%PDF-`), idealnya plus pemeriksaan antivirus, sebelum materi dibaca peserta.
11. **Layanan berkas** — PDF disimpan di disk lokal privat
    (`storage/app/private`). Untuk production, pindahkan ke object storage
    privat dan layani lewat CDN atau route ber-token.
12. **Progres baca materi** (§21) — belum ada tabel yang mencatat materi mana
    yang sudah selesai dibaca peserta. Butuh tabel terpisah per peserta, bukan
    kolom di `materials`.
13. **Batas anti-unduh PDF** (§10.5) — pdf.js masih menarik byte PDF ke browser,
    dan `Ctrl+P` bisa diinzinkan dari dialog cetak atau diketik ulang di
    address bar. Yang dijamin hanya tidak adanya tombol. Kalau klien menuntut
    jaminan penuh, itu proyek DRM tersendiri — perlu biaya lisensi yang besar dan
    belum masuk scope.
