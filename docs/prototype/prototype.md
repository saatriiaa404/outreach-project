# Prototype Scope — v1.7

## Tujuan

Prototype adalah aplikasi web fungsional untuk demonstrasi dan validasi bersama Kelurahan Manukan Kulon. Prototype menggunakan **database lokal non-production** dan **data dummy/sintetis saja** agar alur dapat diuji lintas sesi, termasuk status, approval, historical version, dan PDF gate.

Acuan perilaku dan data adalah [Workflow v2.1](../architecture/workflow.md) dan [Database v1.1](../architecture/database.md). Database v1.1 dengan 22 tabel tetap merupakan baseline terkunci.

## Lingkungan dan Batas Persistence

- Prototype dijalankan secara lokal pada satu komputer untuk demonstrasi.
- Gunakan database lokal yang terpisah dari production dan hanya isi dengan data dummy/sintetis.
- Hosting dan domain tidak diperlukan untuk demo lokal.
- Data pada database lokal diharapkan bertahan ketika aplikasi dimulai ulang agar skenario lintas sesi, history, dan approval dapat didemonstrasikan.
- Engine database dan konfigurasi teknis mengikuti keputusan yang disetujui: MySQL dan stack prototype pada D-013 (lihat [Architecture](../architecture/architecture.md)), serta lingkungan lokal Laragon pada D-024. Dokumen ini tidak menetapkan atau mengubahnya.
- Keputusan persistence ini **bukan** keputusan untuk mengubah schema Database v1.1, dan tidak mengotorisasi migration, tabel, field, relasi, constraint, seed, atau bootstrap baru.
- Jika kelak perlu menguji akses serentak 1–5 Petugas, evaluasi akses LAN sebagai tahap terpisah. Akses LAN bukan bagian dari cakupan demo saat ini.

## Cakupan Demo

Prototype menunjukkan alur lengkap `PETUGAS → KEPALA → ADMIN`:

1. Petugas membuat Draft, memilih Source/Type, mengisi Outreach serta form Mustahik/Kifayah jika berlaku, mengunggah lampiran dummy, melengkapi signature yang diwajibkan, melakukan review, lalu Finalisasi.
2. Kepala mereview secara read-only, melakukan Return dengan alasan, atau Approve melalui PDF gate.
3. Petugas merevisi `PERLU_PERBAIKAN`, lalu melakukan Finalisasi Ulang yang membuat historical version baru.
4. Admin menangani request atau Direct Special Unlock pada `MENUNGGU_PENGESAHAN`/`SELESAI`; `Lock` menuju `PERLU_PERBAIKAN` dan `Lock & Ajukan` membuat version baru menuju `MENUNGGU_PENGESAHAN`, sesuai Workflow v2.1.
5. Untuk Bangga Surabaya, jangan tampilkan form Mustahik/Kifayah atau signature Petugas/Warga sebelum approval apabila tidak diwajibkan oleh Workflow v2.1.
6. Demonstrasikan output PDF berdasarkan Source:
   - **Baznas/CSR:** tiga PDF terpisah, yaitu Outreach dengan halaman lampiran wajib setelah formulir Outreach utama, Mustahik, dan Kifayah.
   - **Bangga Surabaya:** PDF Outreach dengan halaman lampiran wajib.
7. PDF dapat dibuat saat diminta, tetapi seluruh PDF yang diwajibkan harus berhasil dibuat ketika approval dilakukan. Jika pembuatan PDF wajib gagal selama approval, approval gagal, status tetap `MENUNGGU_PENGESAHAN`, pesan kegagalan yang jelas ditampilkan, keputusan approval sukses tidak disimpan, dan event `OUTREACH_COMPLETED` tidak dikirim.

## Lampiran

- Empat lampiran wajib Outreach: **KTP, KK, foto klien, dan foto rumah**.
- Draft boleh disimpan walaupun lampiran belum lengkap; Finalisasi harus ditolak sampai keempat lampiran wajib tersedia.
- Lampiran tambahan berdasarkan jenis bantuan dapat didukung untuk kebutuhan prototype, tetapi persyaratan formalnya tetap `PENDING VALIDATION` sampai dikonfirmasi Kelurahan.
- Kegagalan upload atau pemrosesan/kompresi harus menampilkan error yang jelas; lampiran tidak boleh dihilangkan secara diam-diam.
- Batas ukuran dan jumlah lampiran final harus ditentukan dari pengujian prototype, bukan diasumsikan tanpa bukti.
- Perubahan lampiran saja tidak otomatis mewajibkan tanda tangan ulang. Perubahan data form yang relevan setelah signature/approval harus mengikuti pembaruan signature dan alur approval yang diwajibkan.

## Di Luar Demo

- Hosting, domain, serta kepemilikan dan operasi production.
- Integrasi otomatis E-Surat/E-Arsip; proses arsip tetap dilakukan secara manual di luar aplikasi.
- Penggunaan data warga nyata dan verifikasi NIK eksternal.
- Email/SMS/push.
- Offline-first, Redis/queue wajib, dan infrastruktur production.
- Pengujian akses LAN/multi-komputer, kecuali disetujui sebagai tahap terpisah.

Backup/restore dan prosedur operasional production bukan bagian dari demonstrasi awal prototype. Namun, ini tidak berarti backup operasional tidak diperlukan untuk penggunaan nyata. Kesiapan backup mingguan database beserta seluruh lampiran dan uji restore perlu dinilai sebelum serah terima. Persyaratan production tetap `PENDING VALIDATION`; jangan dipresentasikan sebagai persetujuan Kelurahan.

## Aturan Prototype

- Gunakan data sintetis tanpa PII nyata.
- Terapkan state, ownership, approval gate, historical version, signature, dan Special Unlock sesuai Workflow v2.1.
- Media/PDF boleh disederhanakan untuk demo, tetapi harus menunjukkan preview, hasil sukses/gagal, dan status akhir yang benar. Penyederhanaan tidak boleh mengubah perilaku bisnis yang telah dikunci.
- `special_unlock_requests` adalah data administratif, bukan business state.
- Perubahan data setelah finalisasi tidak boleh mengubah snapshot historis lama. Pertahankan versi, signature, approval, dan referensi lampiran historis.
- Nilai numerik Kifayah `0` berarti tidak ada pengeluaran atau kategori tidak berlaku; `NULL` berarti belum diisi. Jangan otomatis mengubah field kosong menjadi `0`. Tolak nilai negatif dan input nonnumerik.
- Form Mustahik memiliki 25 pertanyaan wajib single-choice. Jangan menambahkan default, pemilihan otomatis, scoring, atau mesin penentu kelayakan. Label/opsi yang belum didukung bukti tetap `PENDING VALIDATION`.
- Tahun pada PDF Kifayah berasal dari tanggal Outreach yang tersimpan.
- Jangan membuat migration, schema, tabel, field, relasi, constraint, seed, bootstrap, atau konfigurasi deployment production pada tahap ini.
- Jangan menganggap keputusan lokal non-production sebagai izin membuka gate implementasi. Gate tetap CLOSED sampai readiness summary ditinjau dan persetujuan eksplisit pemilik proyek diberikan.

## Kriteria Selesai

- Tiga role hanya melihat aksi sesuai otoritasnya.
- Database lokal non-production digunakan dengan data dummy saja dan data tetap tersedia setelah aplikasi dimulai ulang.
- Skenario demo berakhir pada status/version/history yang benar dan didukung bukti pengujian.
- Output PDF yang sesuai Source dapat dihasilkan dengan konten dan halaman lampiran yang benar.
- Kegagalan pembuatan PDF wajib saat approval tidak menghasilkan status `SELESAI`, tidak menyimpan approval sukses, dan tidak mengirim `OUTREACH_COMPLETED`.
- Finalisasi ditolak jika salah satu dari empat lampiran wajib belum tersedia.
- Perubahan setelah finalisasi tidak mengubah snapshot lama; perubahan form dan lampiran mengikuti aturan signature/approval yang relevan.
- Tidak ada perubahan pada baseline Workflow v2.1 atau schema Database v1.1 (22 tabel) tanpa keputusan dan otorisasi terpisah.
- Dokumen ini menetapkan cakupan, bukan bukti bahwa pengujian telah lulus dan bukan persetujuan membuka gate implementasi.

## Catatan Penyelarasan Cluster 5 — 18 Oktober 2026

Bagian ini menegaskan keputusan yang berlaku jika masih ada frasa lama yang bertentangan:

- Baseline aktif: Workflow v2.1 dan Database v1.1 dengan 22 tabel.
- Baznas/CSR menghasilkan tiga PDF terpisah: Outreach dengan halaman lampiran wajib, Mustahik, dan Kifayah. Bangga Surabaya menghasilkan PDF Outreach dengan halaman lampiran wajib.
- Jika PDF wajib gagal dibuat selama approval, approval gagal; status tetap `MENUNGGU_PENGESAHAN`; tidak ada approval sukses yang dipersistenkan; `OUTREACH_COMPLETED` tidak dikirim.
- Empat lampiran wajib: KTP, KK, foto klien, dan foto rumah.
- Gate implementasi tetap CLOSED sampai readiness review dan persetujuan eksplisit. Validasi nonkritis dapat tetap `PENDING VALIDATION` dengan alasan dan rencana validasi; jangan menandainya `PASS` tanpa evidence.
