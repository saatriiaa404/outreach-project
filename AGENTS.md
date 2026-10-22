# AGENTS.md — project/

## Read First

1. `README.md`
2. `AGENTS.md` — aturan umum untuk coding agent.
3. `CLAUDE.md` — instruksi operasional khusus Claude, jika tugas menggunakan Claude.
4. `docs/index.md`
5. `docs/project-management/decisions.md`
6. `docs/project-management/reconciliation.md`
7. `docs/project-management/tasks.md`
8. Dokumen kanonis yang relevan dengan task.
9. Checkpoint v1.6 sebagai snapshot perencanaan jika konteks historis atau keputusan sebelumnya diperlukan.

Jika konteks historis, keputusan sebelumnya, atau konteks perencanaan diperlukan, periksa file checkpoint v1.6 berformat Markdown (`.md`) yang disediakan pengguna. Jika file checkpoint tidak tersedia atau isinya tidak dapat dibaca, sampaikan keterbatasan tersebut dan jangan mengarang isinya. ZIP repositori proyek, jika disediakan, digunakan untuk memeriksa kondisi berkas proyek saat ini dan bukan pengganti checkpoint. Checkpoint tetap bernama v1.6 untuk revisi dokumentasi biasa; jangan membuat checkpoint bernomor baru tanpa keputusan eksplisit pengguna.

## Aturan Otoritas

- Dokumen kanonis di `docs/` menjadi sumber kebenaran untuk concern masing-masing.
- Checkpoint v1.6 adalah snapshot perencanaan, bukan pengganti dokumen kanonis.
- Workflow v2.1 dan Database v1.1 / 22 tabel adalah baseline terkunci.
- Jangan mengubah keputusan `LOCKED`, baseline database, atau state workflow tanpa persetujuan eksplisit dan pencatatan dampaknya pada dokumen yang relevan.
- `PENDING VALIDATION` bukan keputusan final Kelurahan dan tidak boleh ditulis seolah-olah sudah diverifikasi.
- Jika dua sumber berbeda, catat konflik, sumber, evidence yang tersedia, dan dampaknya. Jangan memilih salah satu secara diam-diam.
- Jangan meminta pengguna mengulang keputusan yang sudah jelas, kecuali ada evidence baru yang benar-benar bertentangan.

## Ruang Lingkup dan Gate Implementasi

- Prototype awal bersifat lokal, non-produksi, dan menggunakan dummy data. Demo awal berjalan pada satu komputer; hosting/domain dan penggunaan LAN bukan bagian dari lingkup demo awal.
- Jangan menunggu seluruh dokumen atau semua validasi eksternal selesai sebelum coding dapat dimulai.
- Sebelum implementasi dimulai, identifikasi dan selesaikan konflik dokumentasi kritis yang dapat mengubah perilaku bisnis, izin/otorisasi, keamanan, alur kerja, atau baseline yang terkunci.
- Tandai validasi yang masih tertunda secara jujur sebagai `PENDING VALIDATION`; validasi yang baru bisa dilakukan setelah prototype tersedia bukan penghalang otomatis bagi seluruh coding.
- Mulai implementasi hanya setelah pengguna secara eksplisit menyetujui pembukaan gate implementasi berdasarkan ringkasan kesiapan dan konflik kritis yang tersisa.
- Jangan memulai migration, mengubah schema, atau mengubah baseline 22 tabel tanpa persetujuan eksplisit.
- Jangan mengarang field formulir, opsi dropdown, cardinality, scoring, atau aturan validasi yang tidak didukung dokumen sumber atau evidence.
- Keberadaan PDF tidak membuktikan adanya persetujuan formal dari pemilik formulir.
- Gunakan dummy data. Jangan memasukkan PII nyata, secret, token, password, atau kredensial ke dokumentasi maupun data prototype.

## Aturan Produk dan Keamanan

- Terapkan otorisasi server-side, private file access, validasi upload, dan optimistic concurrency sesuai dokumen kanonis.
- Finalisasi outreach harus terblokir jika lampiran wajib belum lengkap; draft tetap boleh disimpan.
- Jika pembuatan PDF wajib gagal, proses pengesahan harus gagal dengan alasan yang jelas. Status tidak boleh berubah menjadi `SELESAI` dan notifikasi `OUTREACH_COMPLETED` tidak boleh dikirim; status tetap `MENUNGGU_PENGESAHAN`.
- Jenis bantuan Baznas/CSR memerlukan tiga PDF terpisah: (1) formulir outreach beserta lampiran, (2) Mustahik, dan (3) Kifayah. Bangga Surabaya memerlukan PDF outreach beserta lampiran saja.
- Lampiran pada PDF outreach ditempatkan pada halaman terpisah dari formulir utama.
- Pertahankan keterlacakan riwayat, tanda tangan, persetujuan, dan referensi lampiran sesuai workflow kanonis. Perubahan data formulir harus mengikuti aturan pembaruan tanda tangan dan pengesahan; perubahan lampiran saja tidak otomatis mewajibkan pengulangan tanda tangan.
- Jangan mengubah aturan produk atau keamanan hanya untuk mempermudah implementasi tanpa mencatat konflik dan meminta persetujuan jika perubahan bersifat substantif.

## Sebelum Mengubah File

- Identifikasi ruang lingkup, dokumen terdampak, dan potensi konflik dengan keputusan yang berlaku.
- Untuk perubahan substantif pada requirement, keputusan `LOCKED`, baseline, atau file di luar ruang lingkup tugas, sampaikan temuan, dampak, dan usulan perubahan terlebih dahulu; tunggu persetujuan pengguna.
- Kerjakan satu file Markdown per giliran sesuai alur kerja yang disepakati. Pertahankan nama dan lokasi asli file kecuali pengguna menyetujui perubahan.
- Jangan mengedit direktori lokal pengguna secara langsung. Berikan file revisi yang dapat diunduh agar pengguna dapat menggantinya sendiri.

## Setelah Perubahan

- Periksa dokumen dan tautan silang yang terdampak.
- Pastikan perubahan konsisten dengan keputusan yang masih berlaku dan tandai hal yang masih menunggu validasi.
- Jalankan pengujian relevan setelah implementasi diizinkan.
- Perbarui `tasks.md` hanya jika status dan evidence memenuhi kriteria selesai.
- Jangan membuat atau memperbarui checkpoint setiap kali satu file selesai. Lakukan setelah satu cluster dokumen selesai ditinjau, lalu beri tahu pengguna dan minta persetujuan sebelum checkpoint dibuat.
- Jangan membuka gate implementasi secara otomatis; gate dibuka setelah pengguna menyetujuinya secara eksplisit.
- Jangan menandai pekerjaan selesai hanya karena file berhasil diubah; verifikasi konsistensi dan evidence yang relevan.
