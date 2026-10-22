# Demo Checklist — v1.6

Gunakan checklist ini saat walkthrough prototype bersama pihak terkait. Catat setiap hasil berdasarkan pengujian nyata, bukan asumsi. Untuk setiap item, catat status/hasil, bukti atau screenshot, dan feedback sebagai `DISETUJUI`, `DIUBAH`, `DITAMBAHKAN`, `DITOLAK`, atau `MASIH PERLU KONFIRMASI`.

Baseline aktif: Workflow v2.1 dan Database v1.1 (22 tabel). Demo menggunakan database lokal non-production dan data dummy/sintetis saja. Checklist ini tidak membuka gate implementasi dan tidak mengotorisasi perubahan baseline.

## Persiapan Demo

- [ ] Pastikan demo menggunakan database lokal non-production dan data dummy/sintetis; tidak ada data pribadi warga nyata.
- [ ] Pastikan tiga akun dummy tersedia: PETUGAS, KEPALA, dan ADMIN.
- [ ] Siapkan data dummy untuk Source Baznas/CSR dan Bangga Surabaya, termasuk skenario lengkap dan skenario yang sengaja belum lengkap.
- [ ] Siapkan empat lampiran dummy wajib: KTP, KK, foto penerima, dan foto rumah.
- [ ] Siapkan skenario pembuatan PDF berhasil serta skenario kegagalan PDF wajib selama approval.
- [ ] Catat versi aplikasi, tanggal walkthrough, peserta, dan lingkungan pengujian.

## Petugas

- [ ] Login dengan akun PETUGAS dummy dan verifikasi bahwa akses sesuai kewenangan.
- [ ] Membuat Draft, memilih Source/Type, dan melihat konfirmasi/peringatan saat perubahan Source berdampak pada isian.
- [ ] Mengisi Outreach dan memverifikasi form kondisional sesuai Source/Type.
- [ ] Untuk Baznas/CSR, memverifikasi form Mustahik dan Kifayah yang berlaku.
- [ ] Memverifikasi bahwa Mustahik memiliki 25 pertanyaan single-choice; label/opsi yang belum dikonfirmasi dicatat sebagai `PENDING VALIDATION`.
- [ ] Mengisi nilai Kifayah; `0` berarti tidak ada pengeluaran/kategori tidak berlaku, `NULL` berarti belum diisi, dan nilai kosong tidak otomatis menjadi `0`.
- [ ] Mengunggah lampiran dummy dan memverifikasi empat lampiran wajib: KTP, KK, foto penerima, dan foto rumah.
- [ ] Memverifikasi Draft dapat disimpan saat lampiran wajib belum lengkap, tetapi Finalisasi ditolak sampai semua lampiran wajib tersedia.
- [ ] Memverifikasi preview, signature yang diwajibkan, Save Draft/autosave sesuai implementasi, dan penanda field yang belum lengkap.
- [ ] Memverifikasi review menampilkan blocker yang jelas dan Finalisasi menghasilkan version baru.
- [ ] Memverifikasi kegagalan upload/kompresi menampilkan error yang jelas dan tidak menghilangkan lampiran secara diam-diam.

## Kepala

- [ ] Membuka antrean `MENUNGGU_PENGESAHAN` dan memverifikasi review bersifat read-only sesuai kewenangan.
- [ ] Melakukan Return dengan alasan wajib; Petugas dapat memperbaiki data dan melakukan Finalisasi Ulang yang menghasilkan historical version baru.
- [ ] Memverifikasi approval hanya sukses setelah semua PDF yang diwajibkan untuk Source tersebut berhasil dibuat.
- [ ] Memverifikasi skenario PDF wajib gagal: status tetap `MENUNGGU_PENGESAHAN`, pesan kegagalan jelas, tidak ada approval sukses yang dipersistenkan, dan `OUTREACH_COMPLETED` tidak dikirim.
- [ ] Memverifikasi identitas Kepala yang menyetujui tercatat dalam riwayat approval.
- [ ] Memverifikasi perubahan data form setelah signature/approval mengikuti pembaruan signature dan alur approval yang diwajibkan; perubahan lampiran saja tidak otomatis memerlukan tanda tangan ulang.

## PDF dan Lampiran

- [ ] Baznas/CSR menghasilkan tiga PDF terpisah: Outreach dengan halaman lampiran wajib setelah formulir utama, Mustahik, dan Kifayah.
- [ ] Bangga Surabaya menghasilkan PDF Outreach dengan halaman lampiran wajib.
- [ ] PDF hanya memuat form yang berlaku untuk Source terkait; tidak menampilkan form yang tidak berlaku seolah-olah wajib.
- [ ] Tahun pada PDF Kifayah berasal dari tanggal Outreach yang tersimpan.
- [ ] Periksa konten PDF, kelengkapan halaman lampiran, keterbacaan, dan kecocokan data dengan versi yang disetujui.
- [ ] Periksa bahwa versi PDF, signature, approval, snapshot, dan referensi lampiran historis tetap konsisten setelah revisi.

## Admin

- [ ] Memproses request dan Direct Special Unlock sesuai Workflow v2.1 tanpa mengubah historical version lama.
- [ ] Memverifikasi `Lock` menghasilkan status `PERLU_PERBAIKAN` sesuai workflow.
- [ ] Memverifikasi `Lock & Ajukan` membuat version baru dan status `MENUNGGU_PENGESAHAN`.
- [ ] History/audit menampilkan actor, waktu, alasan, dan perubahan penting.
- [ ] Memverifikasi ADMIN tidak dapat bertindak sebagai pihak pengesah.
- [ ] Memverifikasi penandaan arsip tidak mengklaim integrasi otomatis E-Surat/E-Arsip; proses arsip tetap manual.

## Persistence, Otorisasi, dan Riwayat

- [ ] Simpan data dummy, tutup aplikasi, buka kembali, dan pastikan data tersimpan tetap tersedia.
- [ ] Verifikasi PETUGAS tidak dapat membuka/mengedit Outreach milik Petugas lain.
- [ ] Verifikasi Outreach terkunci tidak dapat diubah melalui jalur edit normal.
- [ ] Verifikasi stale `edit_version`, lampiran duplikat, dan Finalisasi yang belum lengkap ditolak tanpa merusak data tersimpan.
- [ ] Verifikasi historical version, snapshot, approval, signature, dan referensi lampiran lama tidak berubah setelah revisi/unlock.
- [ ] Catat hasil pengujian sebagai `PASS`, `FAIL`, atau `BLOCKED`, lengkap dengan langkah reproduksi dan bukti.

## Pending Validation

Item berikut harus tetap diberi label `PENDING VALIDATION` sampai tersedia bukti atau konfirmasi yang memadai. Jangan menandainya disetujui hanya karena walkthrough berjalan.

- [ ] Persyaratan formal lampiran tambahan berdasarkan jenis bantuan.
- [ ] Batas ukuran/jumlah lampiran setelah uji kompresi dan upload.
- [ ] Label/opsi Mustahik yang belum dapat diverifikasi dari sumber.
- [ ] Persyaratan produksi, termasuk hosting/domain, keamanan operasional, retensi, backup, dan restore.
- [ ] Kebutuhan akses LAN/multi-pengguna, jika diajukan sebagai tahap terpisah.
- [ ] Integrasi E-Surat/E-Arsip.
- [ ] Persyaratan digital stamp/tanda tangan digital yang belum dikonfirmasi.
- [ ] Aturan operasional tambahan yang belum didukung Workflow v2.1 atau bukti dari Kelurahan.

## Penutupan Walkthrough

- [ ] Ringkas feedback per item menggunakan `DISETUJUI`, `DIUBAH`, `DITAMBAHKAN`, `DITOLAK`, atau `MASIH PERLU KONFIRMASI`.
- [ ] Catat issue, pemilik tindak lanjut, bukti yang dibutuhkan, dan target review berikutnya.
- [ ] Pisahkan feedback yang dapat ditangani tanpa mengubah baseline dari usulan yang membutuhkan keputusan eksplisit.
- [ ] Jangan menandai implementasi siap atau membuka gate hanya berdasarkan checklist demo. Gate tetap CLOSED sampai readiness summary ditinjau dan pemilik proyek memberikan persetujuan eksplisit.

## Catatan Penyelarasan Cluster 5 — 18 Oktober 2026

- Baseline aktif adalah Workflow v2.1 dan Database v1.1 dengan 22 tabel.
- Empat lampiran wajib adalah KTP, KK, foto penerima, dan foto rumah.
- PDF Baznas/CSR: Outreach dengan halaman lampiran wajib, Mustahik, dan Kifayah sebagai tiga PDF terpisah. PDF Bangga Surabaya: Outreach dengan halaman lampiran wajib.
- Kegagalan PDF wajib selama approval menggagalkan approval; status tetap `MENUNGGU_PENGESAHAN`, tidak ada approval sukses yang dipersistenkan, dan `OUTREACH_COMPLETED` tidak dikirim.
- Checklist ini merupakan alat walkthrough. Item hanya boleh ditandai selesai jika benar-benar diuji dan memiliki bukti.
