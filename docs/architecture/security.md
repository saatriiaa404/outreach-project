# Security Baseline --- Prototype v1.6

Dokumen ini menetapkan kontrol keamanan minimum untuk prototype sistem
Outreach Kelurahan Manukan Kulon. Alur bisnis dan gate mengikuti
`docs/architecture/workflow.md` v2.1; batas role mengikuti
`docs/architecture/roles-permissions.md` v1.6. Baseline Database v1.1
tetap dipertahankan. Dokumen ini tidak mengotorisasi perubahan skema
atau migrasi database.

Revisi 23 Oktober 2026: menambahkan kontrol autentikasi prototype sesuai
D-014 (`docs/project-management/decisions.md`). Tidak ada perubahan lain.

Revisi 27 Oktober 2026: menambahkan batas fitur autentikasi bawaan (D-030),
tipe file lampiran wajib (D-034), batas visibilitas pada pencarian (D-039),
dan audit perubahan NIP Kepala (D-041).

## Kontrol Wajib

-   **Otorisasi server-side:** Authentication, role, ownership/scope,
    status, action permission, dan resource permission wajib diperiksa
    di server. UI bukan kontrol keamanan.
-   **Autentikasi prototype:** Login memakai mekanisme bawaan Laravel
    dengan akun dummy. Akun dibuat dan dikelola ADMIN; registrasi publik
    dinonaktifkan. Prototype tidak menambahkan kebijakan password atau
    sesi khusus di luar bawaan Laravel; kebijakan production tetap
    `PENDING VALIDATION`. Fitur reset password dan registrasi tidak
    disediakan; session memakai driver file (D-030).
-   **Validasi parent Outreach:** Akses atau perubahan attachment, form
    Mustahik/Kifayah, signature, historical version, PDF, dan approval
    harus memvalidasi hak akses terhadap parent Outreach serta scope dan
    status yang relevan.
-   **File privat:** Lampiran dan PDF tidak boleh tersedia melalui
    public raw URL sebagai jalur akses normal. Unduhan harus melewati
    pemeriksaan otorisasi terhadap Outreach dan versi terkait.
-   **Validasi upload:** Periksa tipe yang diizinkan, ukuran, isi file,
    dan duplicate hash. Jangan hanya mengandalkan ekstensi atau
    `Content-Type` dari client. Terapkan batas ukuran berdasarkan hasil
    pengujian prototype; jangan mengklaim batas final sebelum diuji.
-   **Penanganan kegagalan file:** Jika file tidak dapat diproses,
    dikompresi, disimpan, atau dimasukkan ke PDF, tampilkan error yang
    jelas. Jangan diam-diam menghilangkan lampiran atau menganggap
    proses berhasil.
-   **Lampiran wajib dan Finalisasi:** Empat lampiran dasar---KTP, KK,
    Foto Klien, dan Foto Rumah---harus tersedia dan berupa gambar yang
    lolos validasi isi file (bukan hanya ekstensi) agar Finalisasi dapat
    berhasil (D-034). Draft boleh disimpan saat lampiran belum lengkap.
    Kegagalan validasi tidak boleh dilewati dengan memanipulasi UI atau
    request.
-   **PDF dan approval gate:** Semua PDF wajib untuk jenis bantuan
    terkait harus berhasil dibuat sebelum approval berhasil. Jika ada
    PDF wajib gagal dibuat, approval harus gagal, status tetap
    `MENUNGGU_PENGESAHAN`, status tidak menjadi `SELESAI`, dan
    notifikasi `OUTREACH_COMPLETED` tidak dikirim.
-   **Pencarian dan visibilitas:** Dashboard, daftar, pencarian, dan URL
    langsung menerapkan batas scope dan visibilitas role yang sama
    (D-039). Kriteria pencarian NIK tidak diletakkan pada path atau query
    string URL agar tidak tercatat pada log atau riwayat browser.
-   **Identifier dan data uji:** Jangan gunakan NIK sebagai identifier
    URL. Gunakan data dummy yang tidak mengandung PII nyata; jangan
    menyalin data warga nyata ke fixture, log, screenshot, atau laporan
    prototype.
-   **Concurrency:** `edit_version` atau mekanisme optimistic
    concurrency harus mencegah stale write dan silent overwrite. Konflik
    perubahan harus ditampilkan sebagai konflik yang dapat
    ditindaklanjuti, bukan ditimpa diam-diam.
-   **History dan audit:** History versi serta audit untuk tindakan
    sensitif harus append-only sesuai rancangan yang telah disepakati.
    Perubahan NIP Kepala termasuk tindakan yang dicatat pada audit
    (D-041).
    Jangan mengubah atau menghapus jejak historis melalui alur aplikasi
    biasa.
-   **Perubahan dan tanda tangan:** Perubahan data yang memengaruhi
    attestation harus mengikuti aturan refresh tanda tangan dan approval
    dalam workflow. Perubahan lampiran saja tidak otomatis memerlukan
    tanda tangan ulang, kecuali aturan workflow menyatakan sebaliknya.
-   **Rahasia dan kredensial:** Jangan menyimpan secret, token, password
    production, atau kredensial di repository, dokumentasi, fixture,
    screenshot, atau log.
-   **Kesalahan dan logging:** Pesan error kepada pengguna harus cukup
    jelas untuk membantu pemulihan, tetapi tidak membocorkan secret,
    stack trace sensitif, atau data pribadi. Log tidak boleh menyimpan
    isi dokumen sensitif yang tidak diperlukan.

## Status Validasi dan Batas Prototype

-   HTTPS, backup/restore production, provider hosting, retensi
    operasional, dan hardening deployment tetap `PENDING VALIDATION`
    atau pekerjaan fase deployment; jangan menyatakannya sudah
    diterapkan hanya berdasarkan prototype lokal.
-   Persistensi harus diuji dengan skenario simpan, tutup aplikasi, lalu
    buka kembali. Backup mingguan database dan seluruh lampiran serta
    uji restore dilakukan setelah alur utama prototype stabil dan
    sebelum serah terima.
-   Batas ukuran upload dan kompresi akhir harus ditentukan melalui
    pengujian prototype. Batas teknis sementara (D-047): gambar JPEG,
    PNG, WEBP, PDF hanya untuk lampiran tambahan, maksimal 10 MB per
    berkas sebelum kompresi. Persyaratan lampiran tambahan per jenis bantuan
    perlu dikonfirmasi kepada Kelurahan sebelum ditetapkan sebagai
    aturan formal.
-   Prototype awal tetap lokal, non-production, dan menggunakan data
    dummy. Pengujian LAN untuk 1--5 pengguna merupakan evaluasi
    terpisah, bukan asumsi bahwa deployment production telah aman.
-   Kontrol yang belum diuji atau membutuhkan konfirmasi harus tetap
    ditandai `PENDING VALIDATION`; jangan mengubah statusnya menjadi
    tervalidasi tanpa bukti pengujian atau konfirmasi yang sesuai.

## Aturan Rekonsiliasi

Jika kontrol keamanan tampak bertentangan dengan workflow canonical atau
matriks role, tandai konflik yang spesifik dan rekonsiliasikan sebelum
implementasi. Jangan membuat perubahan perilaku bisnis, baseline
database, skema, atau migrasi secara diam-diam.
