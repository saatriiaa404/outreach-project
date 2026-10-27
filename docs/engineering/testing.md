# Prototype Test Plan — v1.7

## Tujuan dan Batas Pengujian

Rencana ini memvalidasi prototype lokal menggunakan database non-production dan data dummy saja. Pengujian dilakukan pada satu komputer untuk demonstrasi; hosting/domain dan akses LAN multi-pengguna bukan bagian dari cakupan saat ini.

Workflow v2.1 dan Database v1.1 (22 tabel) tetap menjadi baseline terkunci. Rencana ini tidak mengotorisasi perubahan schema, migration, seed, constraint, relasi, atau alur bisnis di luar baseline tersebut. Gate implementasi tetap CLOSED sampai readiness summary ditinjau dan pemilik proyek memberikan persetujuan eksplisit untuk membukanya.

## Acceptance Scenarios

1. **Draft dan Finalisasi:** PETUGAS membuat Draft untuk Source Baznas/CSR, melengkapi Outreach serta form Mustahik/Kifayah yang berlaku, melengkapi empat lampiran wajib, dan mengikuti aturan signature yang berlaku sebelum Finalisasi.
2. **Source dan Type:** verifikasi bahwa Source menentukan workflow/form yang berlaku dan Type menentukan intervensi yang ditampilkan. Bangga Surabaya tidak menampilkan form Mustahik/Kifayah atau signature Petugas/Warga sebelum approval jika memang tidak diwajibkan oleh Workflow v2.1.
3. **Return dan Revisi:** KEPALA melakukan Return dengan alasan; PETUGAS memperbaiki data dan melakukan Finalisasi Ulang yang membuat historical version baru.
4. **Approval dan PDF gate:** KEPALA melakukan approval. Approval hanya berhasil apabila seluruh PDF yang diwajibkan untuk Source tersebut berhasil dibuat. Status `SELESAI` tidak boleh ditetapkan sebelum persyaratan tersebut terpenuhi.
5. **Kegagalan PDF:** jika pembuatan PDF wajib gagal selama approval, approval harus gagal; status tetap `MENUNGGU_PENGESAHAN`; sistem menampilkan pesan kegagalan yang jelas dan aman; keputusan approval sukses tidak boleh disimpan; dan event `OUTREACH_COMPLETED` tidak boleh dikirim.
6. **Output PDF:** untuk Baznas/CSR, verifikasi tiga PDF terpisah: (a) Outreach dengan halaman lampiran wajib setelah formulir Outreach utama, (b) Mustahik, dan (c) Kifayah. Untuk Bangga Surabaya, verifikasi PDF Outreach dengan halaman lampiran wajib. Jangan membuat atau menampilkan PDF form yang tidak berlaku seolah-olah wajib. Pengujian juga harus mencakup kegagalan pembuatan PDF yang diwajibkan.
7. **Lampiran:** verifikasi empat lampiran Outreach wajib—KTP, KK, foto klien, dan foto rumah. Draft boleh disimpan saat lampiran belum lengkap, tetapi finalisasi harus ditolak sampai semuanya tersedia. Keempat lampiran wajib harus berupa gambar; file non-gambar ditolak berdasarkan isi file (D-034). Lampiran tambahan sesuai jenis bantuan dapat diuji sebagai kebutuhan prototype yang masih menunggu validasi formal Kelurahan dan tidak boleh memblokir finalisasi (D-033). Perubahan lampiran saja setelah signature/approval tidak otomatis memerlukan tanda tangan ulang; perubahan data form yang relevan harus mengikuti pembaruan signature dan alur approval yang diwajibkan.
8. **Pemrosesan lampiran:** verifikasi bahwa kegagalan upload atau kompresi menampilkan pesan yang jelas dan tidak menghilangkan lampiran secara diam-diam. Batas ukuran dan jumlah final ditetapkan berdasarkan hasil pengujian prototype; jangan mengasumsikan batas yang belum disepakati.
9. **Special Unlock:** ADMIN melakukan Special Unlock sesuai Workflow v2.1. `Lock` dan `Lock & Ajukan untuk Pengesahan` menghasilkan transisi status/version yang benar. Catat alasan serta perubahan pada riwayat. `Lock` tidak membuat version baru: status menjadi `PERLU_PERBAIKAN` sementara version terbaru tetap `APPROVED` atau `PENDING_APPROVAL`, dan history mencatat `SPECIAL_UNLOCK_LOCKED`. `Lock & Ajukan` membuat version baru dan history mencatat `SPECIAL_UNLOCK_SUBMITTED` (D-031).
10. **Integritas historis:** historical version, snapshot, signature/approval, dan referensi lampiran lama tetap tidak berubah setelah revisi atau unlock. Perubahan baru tidak boleh memutasi data historis secara retroaktif.
11. **Otorisasi:** PETUGAS tidak dapat membuka atau mengedit Outreach milik Petugas lain, atau mengubah Outreach terkunci melalui jalur edit normal. KEPALA melakukan review/approval sesuai kewenangan; ADMIN tidak bertindak sebagai pihak pengesah.
12. **Validasi dan konflik edit:** stale `edit_version`, lampiran duplikat, dan finalisasi yang belum lengkap ditolak dengan aman tanpa merusak data yang sudah tersimpan.
13. **Nilai Kifayah:** untuk kategori numerik, `0` berarti tidak ada pengeluaran atau kategori tidak berlaku; `NULL` berarti belum diisi. Pastikan sistem tidak otomatis mengubah nilai kosong menjadi `0`; nilai negatif dan input nonnumerik harus ditolak.
14. **Persistence lokal:** simpan data dummy, tutup lalu jalankan kembali aplikasi, dan verifikasi data yang telah disimpan tetap tersedia. Pastikan pengujian menggunakan database non-production dan tidak menggunakan data pribadi warga.
15. **Tanda tangan dan identitas Kepala:** verifikasi identitas Kepala yang menyetujui tersimpan dalam riwayat, serta nama dan NIP Kepala tercetak pada blok pengesahan PDF (D-041; format cetak NIP masih `PENDING VALIDATION`). Perubahan NIP tercatat pada audit. Jika Kepala berganti, dokumen yang telah disetujui tetap mencatat Kepala yang menyetujui sebelumnya. Penanganan antrean yang masih menunggu saat Kepala berganti masih `PENDING VALIDATION` (Workflow v2.1 bagian 31.3); catat hasilnya sebagai temuan, bukan sebagai perilaku final yang lulus.
16. **Arsip manual:** verifikasi bahwa prototype tidak mengklaim terintegrasi dengan E-Surat/E-Arsip. Pengarsipan dilakukan manual oleh ADMIN paling lambat satu bulan sejak persetujuan Kepala; data dan lampiran tetap berada di aplikasi setelah status arsip ditandai; ID internal tidak ditampilkan sebagai nomor arsip resmi (D-038). Hanya ADMIN yang dapat menandai status arsip.
17. **Form Mustahik:** verifikasi 25 pertanyaan wajib sebagai single-choice dan cocokkan label/opsi dengan sumber yang telah tersedia. Jangan menambahkan default, pilihan otomatis, scoring, atau mesin penentu kelayakan. Opsi/label yang belum memiliki bukti tetap `PENDING VALIDATION`. Tanggungan dan Hutang juga single-choice (D-035). Header PDF Mustahik mengambil data sesuai D-036, termasuk No. Telepon HP dari No. telp Outreach.
18. **Tahun PDF Kifayah:** verifikasi tahun pada PDF Kifayah berasal dari tanggal Outreach yang tersimpan, bukan dari tahun saat PDF dibuat.
19. **Identitas Kifayah:** Nama, NIK, Alamat (Alamat Domisili), dan No. HP tampil read-only dari Outreach dan tidak dapat diketik ulang; Kelurahan dan Kecamatan berasal dari konstanta konfigurasi. PDF Kifayah historis memakai identitas dari snapshot versi terkait (D-036).
20. **Lampiran PDF pada PDF Outreach:** lampiran gambar tercetak pada halaman lampiran; lampiran berformat PDF tampil sebagai halaman rujukan berisi nama berkas dan tidak menggagalkan PDF gate (D-034).
21. **Dashboard dan pencarian:** setiap role hanya melihat Outreach dalam scope dan visibilitasnya. KEPALA tidak melihat Draft dan untuk `PERLU_PERBAIKAN` hanya melihat status serta catatan Return. Pencarian berdasarkan nama, NIK, ID internal, Source, Type, status, dan rentang tanggal tidak mengembalikan Outreach di luar scope; kriteria NIK tidak muncul pada URL (D-039).
22. **Master data dan PDF final:** hanya ADMIN yang dapat menambah atau menonaktifkan Source/Type, dan `code` tidak dapat diubah; PETUGAS tidak dapat mengunduh atau mencetak PDF final, termasuk melalui URL langsung (D-040).
23. **Autentikasi dan infrastruktur:** registrasi publik dan reset password tidak tersedia; tabel infrastruktur Laravel di luar 22 tabel domain praktis hanya `migrations` (D-030).

## Verifikasi dan Bukti

- Lakukan walkthrough dengan tiga akun dummy—PETUGAS, KEPALA, dan ADMIN—serta catat status, version, history, dan notifikasi pada setiap transisi penting.
- Catat hasil setiap skenario sebagai `PASS`, `FAIL`, atau `BLOCKED`; sertakan langkah reproduksi dan bukti/screenshot yang relevan.
- Uji tampilan mobile untuk stepper PETUGAS dan desktop untuk antrean KEPALA/ADMIN.
- Uji pembuatan PDF secara deterministik: hasil sukses, kegagalan pembuatan saat approval, kesesuaian output per Source, halaman lampiran, dan penggunaan data yang benar.
- Verifikasi data tetap tersimpan setelah restart aplikasi, tanpa menautkan database demo ke lingkungan production.
- Jangan menyatakan skenario lulus jika belum dijalankan. Keputusan atau perilaku yang belum didukung bukti memadai harus dicatat sebagai `PENDING VALIDATION`; skenario yang belum dapat diuji karena dependensi belum tersedia dicatat `BLOCKED` dengan alasan.
- Simpan screenshot pada langkah penting untuk dokumentasi laporan, dengan hanya menggunakan data dummy.
- Setelah alur utama stabil, lakukan uji backup mingguan database beserta seluruh lampiran oleh ADMIN secara manual ke media terpisah (D-038) dan uji restore sebelum serah terima. Ini merupakan kriteria kesiapan handover, bukan klaim bahwa backup/restore operasional sudah tersedia sekarang.

## Di Luar Cakupan Saat Ini

- Hosting/domain, akses LAN multi-pengguna, dan deployment production.
- Integrasi otomatis E-Surat/E-Arsip, notifikasi eksternal, verifikasi NIK eksternal, serta penggunaan data warga nyata.
- Perubahan schema Database v1.1, migration, seed, atau perubahan workflow terkunci tanpa persetujuan eksplisit.
- Klaim bahwa pengujian telah lulus sebelum skenario benar-benar dijalankan.

## Catatan Penyelarasan Cluster 5 — 18 Oktober 2026

Bagian ini menegaskan aturan yang berlaku jika terdapat sisa frasa lama yang bertentangan dalam dokumen:

- Baseline aktif adalah Workflow v2.1 dan Database v1.1 dengan 22 tabel.
- PDF Baznas/CSR terdiri dari Outreach dengan halaman lampiran wajib, Mustahik, dan Kifayah sebagai tiga PDF terpisah. Bangga Surabaya menghasilkan PDF Outreach dengan halaman lampiran wajib.
- Kegagalan pembuatan PDF yang diwajibkan selama approval menggagalkan approval; status tetap `MENUNGGU_PENGESAHAN`, tidak ada keputusan approval sukses yang dipersistenkan, dan `OUTREACH_COMPLETED` tidak dikirim.
- Empat lampiran wajib adalah KTP, KK, foto klien, dan foto rumah. Draft dapat disimpan tanpa lampiran lengkap, tetapi finalisasi tidak dapat dilakukan.
- `PENDING VALIDATION` tidak otomatis menjadi blocker menyeluruh. Catat dampak, alasan, dan cara validasinya; blocker gate ditentukan berdasarkan kriteria readiness dan dampak kritis.
- Dokumen ini adalah rencana pengujian, bukan bukti bahwa pengujian sudah dijalankan dan bukan izin untuk membuka gate implementasi.
