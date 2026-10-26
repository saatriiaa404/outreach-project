# Project Context — Outreach Kelurahan Manukan Kulon

## Status Dokumen

| Item | Nilai |
|---|---|
| Versi workspace | `project` (sebelumnya `project1.1`) |
| Status | ACTIVE — rekonsiliasi dokumen kanonis berlangsung |
| Fase | Persiapan implementasi prototype |
| Checkpoint aktif | v1.6 — Canonical Reconciliation Update |
| Baseline workflow | v2.1 — LOCKED |
| Baseline database | v1.1 — LOCKED / 22 tabel |
| Metode pengembangan | Prototype lokal |
| Mitra | Kelurahan Manukan Kulon, Kota Surabaya |

Dokumen ini adalah titik masuk untuk `project`. Checkpoint v1.6 merupakan snapshot perencanaan. Dokumen kanonis di workspace menjadi sumber kebenaran untuk concern masing-masing.

## Tujuan Proyek

Sistem Informasi Pendataan Outreach Berbasis Web ditujukan untuk membantu Kelurahan Manukan Kulon mendigitalisasi pendataan lapangan, mengurangi penginputan ulang, dan mendukung pemeriksaan serta pengelolaan dokumen Outreach secara terstruktur.

Role yang digunakan dalam baseline proyek adalah PETUGAS, KEPALA, dan ADMIN. Detail kewenangan dan alur mengikuti dokumen kanonis yang relevan.

## Aturan Versi dan Otoritas

1. `project` (sebelumnya `project1.1`) adalah workspace dokumentasi kanonis aktif.
2. `projectv1.0` dan checkpoint sebelum v1.6 tetap menjadi arsip historis, kecuali dirujuk secara eksplisit sebagai evidence atau riwayat.
3. Checkpoint v1.6 adalah snapshot perencanaan. Nama checkpoint tetap `v1.6` untuk revisi dokumentasi biasa; jangan membuat checkpoint bernomor baru tanpa keputusan eksplisit pengguna.
4. Dokumen kanonis di `docs/` menjadi sumber kebenaran untuk concern masing-masing. Checkpoint menjadi acuan perencanaan, bukan pengganti spesifikasi kanonis.
5. Workflow v2.1 dan Database v1.1 dengan 22 tabel tetap menjadi baseline terkunci. Perubahan material memerlukan persetujuan eksplisit dan pembaruan keputusan serta dokumen terkait.
6. `PENDING VALIDATION` bukan persetujuan mitra dan tidak boleh diperlakukan sebagai requirement final.
7. Konflik antar sumber harus dicatat beserta evidence, dampak, dan usulan penyelesaiannya. Jangan menyelesaikan konflik secara diam-diam.
8. Jangan membuka gate implementasi secara otomatis. Pengguna harus menyetujuinya secara eksplisit setelah menerima ringkasan kesiapan dan konflik kritis yang tersisa.
9. Jangan menganggap semua validasi eksternal atau seluruh dokumen yang belum selesai sebagai penghalang otomatis untuk mulai coding. Konflik kritis yang memengaruhi perilaku bisnis, otorisasi, keamanan, workflow, atau baseline terkunci harus diselesaikan sebelum gate dibuka.

## Aturan Penggunaan Claude

Claude digunakan sebagai coding agent untuk membantu pengembangan prototype berdasarkan dokumentasi kanonis proyek.

1. Baca `AGENTS.md`, `CLAUDE.md`, dan dokumen kanonis yang relevan sebelum mengerjakan tugas.
2. Perlakukan dokumentasi kanonis di `docs/` sebagai acuan utama. Checkpoint digunakan sebagai referensi perencanaan, bukan pengganti spesifikasi kanonis.
3. Jangan mengubah architecture, database, workflow/state, permissions, security, scope, atau keputusan yang berstatus `LOCKED` tanpa meminta konfirmasi terlebih dahulu.
4. Jangan memulai implementasi sebelum konflik dokumentasi kritis ditinjau dan gate implementasi dibuka melalui persetujuan eksplisit pengguna. Persetujuan membuka gate tidak berarti persetujuan untuk mengubah baseline terkunci.
5. Jika menemukan konflik, kekurangan informasi, atau asumsi yang belum tervalidasi, laporkan temuan, dampak, dan usulan solusi sebelum menerapkan perubahan yang berpengaruh.
6. Kerjakan perubahan secara terarah, prioritaskan keterbacaan dan maintainability, serta hindari over-engineering.
7. Jangan mengubah file di luar ruang lingkup tugas tanpa persetujuan.
8. Selama rekonsiliasi dokumentasi, kerjakan satu file Markdown per giliran sesuai alur yang disepakati. Checkpoint dibahas setelah satu cluster selesai ditinjau; beri tahu pengguna dan minta persetujuan sebelum membuat atau memperbaruinya.

## Kondisi dan Lingkup Prototype

Prototype awal bersifat lokal dan non-produksi, menggunakan dummy data, serta didemonstrasikan pada satu komputer. Hosting/domain dan penggunaan LAN multi-pengguna tidak termasuk lingkup demo awal, kecuali disetujui kemudian.

Pemetaan form Outreach, Mustahik, dan Kifayah perlu dibedakan dari validasi aturan operasional oleh pemilik formulir atau pihak Kelurahan. Hal yang masih menunggu konfirmasi harus ditandai `PENDING VALIDATION`; jangan menuliskannya sebagai keputusan final.

Pilihan implementasi teknis yang belum ditetapkan tidak boleh ditebak atau dianggap sudah disepakati. Validasi yang baru dapat dilakukan setelah prototype tersedia, termasuk pengujian penyimpanan dengan menutup dan membuka kembali aplikasi, dicatat sebagai pekerjaan validasi dan bukan penghalang otomatis bagi seluruh coding.

## Navigasi

- [Indeks dokumentasi](docs/index.md)
- [Instruksi agent](AGENTS.md)
- [Instruksi Claude](CLAUDE.md)
- [Log keputusan](docs/project-management/decisions.md)
- [Matriks rekonsiliasi](docs/project-management/reconciliation.md)
- [Daftar tugas](docs/project-management/tasks.md)

## Gate Implementasi Prototype

Gate implementasi dibuka hanya setelah pengguna menyetujui pembukaan gate secara eksplisit berdasarkan ringkasan kesiapan.

Sebelum meminta persetujuan tersebut, tinjau konflik kritis yang berpotensi mengubah perilaku bisnis, otorisasi, keamanan, workflow, atau baseline terkunci. Jangan mewajibkan semua validasi eksternal selesai jika validasi tersebut memang membutuhkan prototype atau konfirmasi pihak Kelurahan di tahap berikutnya.

Status selesai pada satu task tidak otomatis membuka gate implementasi. Sebaliknya, task validasi yang tertunda tidak otomatis memblokir seluruh coding jika tidak menimbulkan konflik kritis dan dapat diuji setelah prototype tersedia.

Pembukaan gate tidak mengizinkan perubahan terhadap Workflow v2.1, Database v1.1 / 22 tabel, atau keputusan terkunci lainnya tanpa persetujuan eksplisit terpisah.
