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
- Empat lampiran dasar wajib untuk finalisasi: KTP, KK, foto klien, dan foto rumah. Draft tetap boleh disimpan sebelum lampiran lengkap.
- Lampiran tambahan per jenis bantuan sebagai kemampuan prototype apabila dibutuhkan; daftar resmi lampiran tambahan masih `PENDING VALIDATION`.
- Signature dummy, preview, submission version, historical version, history/audit, dan notifikasi in-app.
- Workflow Return/Revision/Approval dan pemeriksaan keberhasilan pembuatan semua PDF wajib sebelum approval berhasil.
- Pembuatan PDF sesuai jenis bantuan: Outreach, Mustahik, dan Kifayah untuk Baznas/CSR; Outreach untuk Bangga Surabaya. Halaman lampiran yang diwajibkan disertakan setelah halaman utama PDF Outreach.
- Special Unlock ADMIN secara terkendali sesuai Workflow dan permission; ADMIN tidak dapat menyetujui Outreach.
- Pemeriksaan dasar backup database dan lampiran serta uji restore sebelum serah terima prototype, setelah alur utama stabil. Ini tidak berarti menyediakan operasi backup terjadwal untuk penggunaan produksi.
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
- Daftar lampiran tambahan yang diwajibkan untuk setiap Type di luar empat lampiran dasar.
- Format PDF final yang belum dapat dipastikan dari template sumber.
- Batas ukuran unggahan, format file yang diterima, dan parameter kompresi final; nilai awal untuk pengujian harus didokumentasikan sebagai konfigurasi prototype, bukan aturan operasional final.
- Kebijakan retention/archive dan prosedur koreksi resmi.
- Prosedur pergantian Kepala dan penyesuaian kewenangan terkait.
- Mekanisme atau uploader E-Surat/E-Arsip.
- Pemilik, frekuensi, dan media backup untuk operasional setelah prototype.
- Kebutuhan hosting/domain atau penggunaan LAN setelah evaluasi awal.
- Persyaratan digital stamp atau tanda tangan digital resmi.

Prototype boleh menampilkan placeholder atau mencatat pertanyaan validasi, tetapi tidak boleh menyimpulkan jawaban atau menerapkannya sebagai aturan final tanpa persetujuan yang sesuai.

## 5. Batas Perubahan

Scope ini harus dibaca bersama `docs/product/PRD.md`, `docs/architecture/workflow.md`, `docs/architecture/roles-permissions.md`, `docs/architecture/security.md`, dan `docs/architecture/database.md`.

- Workflow v2.1 adalah baseline aktif.
- Database v1.1 dengan 22 tabel adalah baseline aktif dan tidak berubah melalui revisi scope ini.
- Perubahan yang memengaruhi perilaku bisnis, keamanan, workflow, atau baseline yang dikunci harus dicatat dan disetujui secara eksplisit sebelum diterapkan.
- Scope ini tidak membuka implementation gate. Sebelum bootstrap, source code, schema, migration, atau seed dibuat, status kesiapan harus dirangkum dan persetujuan eksplisit untuk membuka gate harus diperoleh.
