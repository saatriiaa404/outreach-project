# Architecture Guide --- Prototype v1.6

## Baseline

Prototype menggunakan **modular monolith** dengan layered internal
structure:

``` text
Vue/Inertia → Controller → Validation/Service/Domain Action → Model/ORM → MySQL/Filesystem
```

Frontend menyajikan UI dan mengirim permintaan; controller menangani
HTTP boundary; validation/service/domain action menerapkan validasi,
otorisasi yang relevan, invariant, dan transisi state; model/ORM
menangani persistence; filesystem menyimpan file fisik secara privat.

Modul: Outreach, Mustahik, Kifayah, Attachments, Workflow, Approval,
Documents/PDF, Signatures, History/Audit, Notifications, dan Admin.

Revisi 27 Oktober 2026: menambahkan pustaka UI (D-018), konfigurasi
infrastruktur Laravel minimal (D-030), aturan lampiran pada PDF (D-034),
konstanta wilayah (D-036), dan lokasi kode (D-042). Keputusan tercatat pada
`docs/project-management/decisions.md`.

## Batas Implementasi

-   Stack prototype: Laravel 13, Vue 3 + TypeScript, Inertia, Vite,
    MySQL, Dompdf, dan Laravel Filesystem (D-013). Lapisan tampilan
    memakai Tailwind CSS + shadcn-vue melalui starter kit resmi Laravel
    untuk Vue (D-018). Signature pad Petugas/Warga memakai pustaka
    `signature_pad` 5.1.4 dengan versi terkunci (D-049).
-   Lokasi kode: aplikasi Laravel ditempatkan di `project/web/`, bukan
    di akar `project/`, agar tidak bertabrakan dengan `README.md`,
    `.gitignore`, dan `docs/` (D-042).
-   Infrastruktur Laravel dibuat minimal (D-030): session dan cache
    memakai driver file, queue sinkron (`sync`), tanpa reset password dan
    tanpa registrasi publik, sehingga tabel infrastruktur praktis hanya
    `migrations` dan berada di luar hitungan 22 tabel domain. Model User
    disesuaikan untuk memakai kolom `password_hash`; kolom bawaan seperti
    `remember_token`, `email_verified_at`, dan 2FA tidak ditambahkan.
    Detail teknis diverifikasi saat bootstrap.
-   Kelurahan dan Kecamatan pada form Mustahik dan Kifayah berasal dari
    konstanta konfigurasi aplikasi (Kelurahan Manukan Kulon, Kecamatan
    Tandes), bukan data per Outreach (D-036).
-   Controller tetap tipis. Aturan bisnis, transisi status, pemeriksaan
    gate, dan invariant ditempatkan pada service/domain action agar
    tidak hanya bergantung pada UI.
-   Current record dipisahkan dari historical version. File fisik
    disimpan di filesystem, bukan sebagai BLOB MySQL.
-   Baseline database tetap **Database Specification v1.1 dengan 22 tabel**.
    Dokumen ini tidak menambah/mengurangi tabel atau mengubah field, relasi,
    constraint, dan migration baseline.
-   Private file access, PDF gate, optimistic concurrency, dan
    server-side authorization adalah invariant.
-   Tidak ada microservice, API gateway, offline-first, Redis wajib,
    atau queue wajib pada prototype v1.6.
-   Prototype awal bersifat lokal, non-production, dan menggunakan data
    dummy. Evaluasi LAN untuk 1--5 pengguna merupakan tahap terpisah dan
    bukan klaim kesiapan deployment production.
-   File dan PDF harus diakses melalui jalur yang memeriksa otorisasi
    terhadap parent Outreach serta versi/resource yang diminta. Public
    raw URL bukan jalur akses normal.
-   Jika pemrosesan file atau pembuatan PDF wajib gagal, sistem harus
    menyampaikan error dengan jelas dan tidak boleh menganggap proses
    berhasil atau diam-diam menghilangkan lampiran.

## PDF dan Approval Gate

Pembuatan PDF mengikuti jenis bantuan dan workflow canonical:

-   **Baznas/CSR:** tiga PDF terpisah: (1) Outreach PDF dengan halaman
    lampiran wajib setelah formulir utama, (2) Mustahik PDF, dan (3)
    Kifayah PDF.
-   **Bangga Surabaya:** Outreach PDF dengan halaman lampiran setelah
    formulir utama.
-   Saat approval, semua PDF wajib untuk jenis bantuan terkait harus
    berhasil dibuat. Jika salah satu gagal, approval gagal dan keputusan
    `APPROVED` tidak boleh tersimpan; status tetap
    `MENUNGGU_PENGESAHAN`, status tidak menjadi `SELESAI`, dan
    notifikasi `OUTREACH_COMPLETED` tidak dikirim.
-   Keberhasilan render PDF adalah prasyarat approval, bukan sekadar proses
    sesudah status selesai. Kegagalan file/PDF wajib harus menghasilkan error
    yang jelas dan tidak boleh diam-diam melewatkan output atau lampiran.
-   Pembuatan/unduhan PDF tidak dengan sendirinya mengubah status bisnis
    atau menggantikan proses pengiriman manual ke E-Surat/E-Arsip.
-   Halaman lampiran PDF Outreach hanya mencetak lampiran gambar.
    Lampiran berformat PDF ditampilkan sebagai halaman rujukan berisi nama
    berkas; tidak ada pustaka penggabung PDF pada prototype (D-034).

## Batas Persistence dan Workflow

-   **Database v1.1** tetap menentukan baseline persistence dan skema
    database. Dokumen arsitektur ini tidak mengotorisasi perubahan
    tabel, kolom, relasi, atau migrasi.
-   **Workflow v2.1** menentukan state, action, transisi, dan gate
    bisnis.
-   **Roles & Permission Boundary v1.6** menentukan batas akses per
    role, ownership/scope, status, action, dan resource.
-   **Security Baseline v1.6** menentukan kontrol keamanan minimum
    prototype.
-   Dokumen PRD, scope, dan form domain menentukan kebutuhan produk, batas
    scope, serta field/perilaku form; arsitektur tidak boleh menggantikan
    sumber canonical tersebut.
-   Implementasi harus mematuhi sumber-sumber canonical yang relevan. Jika
    ada konflik konkret yang memengaruhi perilaku bisnis, otorisasi/keamanan,
    workflow, atau baseline terkunci, tandai dan rekonsiliasikan sebelum
    implementasi; jangan menyelesaikannya melalui keputusan teknis diam-diam.
-   Pending validation yang memang menunggu pengujian prototype atau konfirmasi
    Kelurahan boleh tetap berstatus `PENDING VALIDATION`; jangan mengubahnya
    menjadi aturan final tanpa bukti atau keputusan yang disetujui.

## Prinsip Implementasi

-   Semua pemeriksaan otorisasi penting dilakukan di server; UI hanya
    mencerminkan izin dari server.
-   Optimistic concurrency (`edit_version` atau mekanisme yang telah
    ditetapkan) mencegah stale write dan silent overwrite.
-   History versi dan audit tindakan sensitif mengikuti aturan
    append-only yang telah disepakati.
-   Perubahan data yang memengaruhi attestation mengikuti aturan
    pembaruan tanda tangan dan approval pada workflow. Perubahan
    lampiran saja tidak otomatis memerlukan tanda tangan ulang kecuali
    ditentukan workflow.
-   Persistensi prototype harus diuji melalui siklus simpan, tutup
    aplikasi, lalu buka kembali. Backup database dan seluruh lampiran
    serta uji restore dilakukan setelah alur utama stabil dan sebelum
    serah terima.
-   Pilihan implementasi tidak boleh mengubah aturan bisnis, hak akses,
    bentuk output PDF, baseline database, atau kebutuhan migrasi tanpa
    persetujuan eksplisit.
-   Implementation gate tetap **CLOSED**. Dokumen arsitektur ini bukan
    persetujuan untuk membuat bootstrap/source code, schema, migration, atau
    seed. Sebelum pekerjaan tersebut dimulai, berikan ringkasan kesiapan dan
    minta persetujuan eksplisit untuk membuka gate.

## Status Validasi

Stack dan batasan di atas adalah baseline implementasi prototype, bukan
bukti bahwa seluruh kontrol telah diuji. HTTPS, provider hosting,
hardening deployment, retensi operasional, dan backup/restore production
tetap `PENDING VALIDATION` atau pekerjaan fase deployment. Batas akhir
upload/kompresi dan kebutuhan lampiran tambahan per jenis bantuan perlu
ditetapkan melalui pengujian prototype atau konfirmasi Kelurahan.

Aturan detail form Mustahik dan Kifayah tetap mengikuti dokumen form
canonical: Mustahik memiliki 25 pertanyaan wajib single-choice tanpa default,
automatic selection, atau scoring; Kifayah membedakan `0` (tidak ada/tidak
berlaku) dari `NULL` (belum diisi), dengan seluruh kategori pengeluaran diisi
secara eksplisit. Dokumen arsitektur ini hanya menetapkan batas implementasi,
bukan menggantikan spesifikasi form.
