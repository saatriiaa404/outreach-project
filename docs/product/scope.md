# Project Scope — Prototype v1.6

## 1. Tujuan dan Batas Umum

Prototype membuktikan alur pendataan Outreach secara lokal menggunakan data dummy. Prototype bukan sistem produksi dan tidak boleh menggunakan data warga nyata.

- Lingkungan awal: satu komputer, lokal, non-produksi.
- Hosting/domain bukan prasyarat prototype awal.
- Penggunaan melalui LAN oleh 1–5 pengguna dapat dievaluasi secara terpisah dan bukan bagian otomatis dari ruang lingkup awal.
- Authentication menggunakan akun dummy, tetapi pemeriksaan role, permission, dan scope tetap wajib diterapkan pada action yang dilindungi.
- Kebutuhan yang belum terverifikasi harus dicatat sebagai `PENDING VALIDATION`; placeholder untuk demonstrasi tidak boleh diperlakukan sebagai aturan final.

## 2. In Scope

Prototype mencakup:

- Pembuatan dan pengisian Outreach dengan data dummy untuk role PETUGAS, KEPALA, dan ADMIN.
- Form kondisional berdasarkan Source dan Type.
- Form Mustahik dan Kifayah untuk Baznas/CSR; Bangga Surabaya menggunakan Outreach tanpa form Mustahik/Kifayah.
- Penyimpanan draft, autosave, persistence setelah aplikasi ditutup dan dibuka kembali, serta perlindungan optimistic concurrency.
- Empat lampiran dasar wajib untuk finalisasi: KTP, KK, foto klien, dan foto rumah, berupa gambar (D-034). Draft tetap boleh disimpan sebelum lampiran lengkap.
- Lampiran di luar empat wajib diunggah sebagai "Lampiran lain"; Kelurahan tidak menetapkan daftar per jenis bantuan, lampiran ini tidak memblokir Finalisasi, dan satu Outreach memuat paling banyak 10 lampiran termasuk empat wajib (D-033, D-064).
- Signature dummy, preview, submission version, historical version, history/audit, dan notifikasi in-app.
- Workflow Return/Revision/Approval dan pemeriksaan keberhasilan pembuatan semua PDF wajib sebelum approval berhasil.
- Pembuatan PDF sesuai jenis bantuan: Outreach, Mustahik, dan Kifayah untuk Baznas/CSR; Outreach untuk Bangga Surabaya. Halaman lampiran yang diwajibkan disertakan setelah halaman utama PDF Outreach.
- Special Unlock ADMIN secara terkendali sesuai Workflow dan permission; ADMIN tidak dapat menyetujui Outreach.
- Dashboard per role dan pencarian berdasarkan nama, NIK, ID internal, Source, Type, status, dan rentang tanggal, selalu dibatasi scope role (D-039).
- Pengelolaan oleh ADMIN: master Source/Type dengan kode yang tidak dapat diubah (D-040), akun dummy termasuk NIP Kepala (D-041), template tanda tangan Kepala, dan template stempel.
- Penandaan status arsip secara manual oleh ADMIN paling lambat satu bulan sejak persetujuan Kepala; data tetap di aplikasi dan tanpa integrasi E-Surat/E-Arsip (D-038).
- Pemeriksaan dasar backup database dan lampiran serta uji restore sebelum serah terima prototype, setelah alur utama stabil. Pada prototype, backup dilakukan manual oleh ADMIN setiap minggu ke media terpisah (D-038). Ini tidak berarti menyediakan operasi backup terjadwal untuk penggunaan produksi.
- Pengujian menggunakan data dummy, termasuk alur sukses, kegagalan PDF/lampiran, pembatasan akses, versioning, dan persistence.

## 3. Out of Scope

Hal-hal berikut tidak dibangun sebagai kemampuan operasional dalam prototype awal:

- Integrasi langsung ke E-Surat/E-Arsip dan verifikasi NIK eksternal.
- Penggunaan data warga nyata atau deployment produksi.
- Hosting/domain dan pengoperasian layanan produksi.
- Backup terjadwal yang berjalan terus-menerus untuk operasional produksi, pemantauan backup produksi, serta layanan pemulihan operasional setelah serah terima. Uji backup/restore terbatas untuk evaluasi prototype tetap termasuk In Scope sebagaimana dijelaskan di atas.
- Offline-first.
- Microservices, API gateway, atau Redis/queue sebagai komponen wajib.
- QR/scan.
- Auto-scoring, rekomendasi otomatis, atau mesin penentu kelayakan Mustahik.
- Integrasi otomatis dengan uploader E-Surat.
- Prosedur organisasi/operasional di luar alur yang dibutuhkan untuk menguji prototype.

## 4. Pending Validation

Hal-hal berikut harus tetap ditandai `PENDING VALIDATION` sampai ada bukti atau konfirmasi yang memadai:

- Sumber resmi label/status kesejahteraan atau desil.
- Format PDF final yang belum dapat dipastikan dari template sumber.
- Batas ukuran unggahan, tipe file yang diterima, dan parameter kompresi final; nilai awal untuk pengujian harus didokumentasikan sebagai konfigurasi prototype, bukan aturan operasional final. Empat lampiran wajib sudah ditetapkan berupa gambar (D-034).
- Kebijakan retensi dan prosedur koreksi resmi. Prosedur arsip manual untuk prototype sudah ditetapkan pada D-038.
- Prosedur pergantian Kepala dan penyesuaian kewenangan terkait.
- Penyimpanan nomor/reference arsip dan cara verifikasi arsip E-Surat/E-Arsip; uploader adalah ADMIN secara manual (D-038).
- Pemilik, frekuensi, dan media backup untuk operasional setelah serah terima; backup prototype mengikuti D-038.
- Kebutuhan hosting/domain atau penggunaan LAN setelah evaluasi awal.
- Persyaratan digital stamp atau tanda tangan digital resmi.
- Format dan posisi cetak NIP Kepala pada blok pengesahan PDF (D-041).

Prototype boleh menampilkan placeholder atau mencatat pertanyaan validasi, tetapi tidak boleh menyimpulkan jawaban atau menerapkannya sebagai aturan final tanpa persetujuan yang sesuai.

## 5. Batas Perubahan

Scope ini harus dibaca bersama `docs/product/PRD.md`, `docs/architecture/workflow.md`, `docs/architecture/roles-permissions.md`, `docs/architecture/security.md`, dan `docs/architecture/database.md`.

- Workflow v2.1 adalah baseline aktif.
- Database v1.1 dengan 22 tabel adalah baseline aktif dan tidak berubah melalui revisi scope ini.
- Perubahan yang memengaruhi perilaku bisnis, keamanan, workflow, atau baseline yang dikunci harus dicatat dan disetujui secara eksplisit sebelum diterapkan.
- Scope ini tidak membuka implementation gate. Sebelum bootstrap, source code, schema, migration, atau seed dibuat, status kesiapan harus dirangkum dan persetujuan eksplisit untuk membuka gate harus diperoleh.
