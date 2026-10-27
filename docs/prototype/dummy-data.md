# Dummy Data Scenario — v1.6

Gunakan hanya data sintetis. Jangan memakai nama lengkap, NIK, alamat, foto, atau dokumen milik warga nyata. Semua file, signature, dan hasil PDF harus diberi label demo agar tidak disalahartikan sebagai dokumen resmi.

Baseline aktif: Workflow v2.1 dan Database v1.1 (22 tabel). Dokumen ini menyediakan skenario data untuk demonstrasi; bukan otorisasi untuk mengubah schema, migration, seed, atau membuka gate implementasi.

## Akun Demo

| Role | Nama demo | Email demo |
|---|---|---|
| PETUGAS | Rina Petugas | `petugas.demo@example.test` |
| KEPALA | Budi Kepala | `kepala.demo@example.test` |
| ADMIN | Sari Admin | `admin.demo@example.test` |

Gunakan password demo yang berbeda dari password pribadi dan jangan memakai akun produksi. Pada prototype, password ketiga akun diambil dari `DEMO_USER_PASSWORD` di `web/.env` lokal (tidak di-commit) saat `php artisan db:seed` dijalankan; NIP Kepala demo adalah nilai sintetis `000000000000000001`. Jika sistem belum memiliki akun dummy, penyiapannya dilakukan hanya setelah gate implementasi dibuka dan mengikuti keputusan implementasi yang disetujui.

## Record Demo

| Kode | Source | Type | Status awal | Skenario utama |
|---|---|---|---|---|
| DEMO-001 | BAZNAS | RUTILAHU | DRAFT | Lengkapi Outreach, lampiran wajib, form yang berlaku, lalu Finalisasi |
| DEMO-002 | CSR | BEASISWA | MENUNGGU_PENGESAHAN | Kepala melakukan Return; Petugas revisi dan Finalisasi Ulang; uji approval dan PDF gate |
| DEMO-003 | BANGGA_SURABAYA | BANTUAN_MODAL_USAHA | DRAFT | Alur Bangga Surabaya; pastikan form Mustahik/Kifayah dan signature Petugas/Warga sebelum approval tidak ditampilkan jika tidak diwajibkan Workflow v2.1 |
| DEMO-004 | BAZNAS | ALAT_KESEHATAN | SELESAI | Uji Special Unlock dan pastikan history lama tetap utuh |

Gunakan nama sintetis seperti `Warga Demo Satu`, `Warga Demo Dua`, dan token identitas seperti `DUMMY-NIK-001`. Jangan gunakan nomor identitas yang menyerupai atau berpotensi menjadi NIK warga nyata. Data alamat, nomor kontak, dan tanggal lahir—jika dibutuhkan untuk demonstrasi—harus jelas sintetis.

## Lampiran Demo

Untuk setiap record yang diuji pada alur finalisasi, siapkan empat lampiran wajib dengan konten sintetis:

| Jenis lampiran | Contoh nama file | Catatan |
|---|---|---|
| KTP | `DEMO-001-ktp-dummy.png` | Gambar sintetis ber-watermark DEMO |
| KK | `DEMO-001-kk-dummy.png` | Gambar sintetis ber-watermark DEMO |
| Foto klien | `DEMO-001-klien-dummy.jpg` | Ilustrasi/foto sintetis ber-label DEMO |
| Foto rumah | `DEMO-001-rumah-dummy.jpg` | Ilustrasi/foto sintetis ber-label DEMO |

Jangan memakai hasil scan KTP/KK asli, foto warga nyata, atau dokumen yang diambil dari internet dan dapat mengidentifikasi seseorang. Lampiran tambahan berdasarkan jenis bantuan boleh dipakai untuk demonstrasi jika diperlukan, tetapi persyaratan formalnya masih `PENDING VALIDATION` sampai dikonfirmasi Kelurahan.

Simpan pula satu file untuk menguji kegagalan pemrosesan atau ukuran yang melebihi batas yang sedang diuji. Catat ukuran dan hasilnya; jangan mengasumsikan batas final sebelum hasil pengujian tersedia.

## Data Form Demo

- **Outreach:** isi field menggunakan nilai sintetis yang konsisten dan mudah ditelusuri dari satu record ke PDF terkait.
- **Mustahik:** gunakan 25 pertanyaan single-choice sesuai label dan opsi dari dokumen/sumber yang tersedia. Jangan mengarang label atau opsi yang belum diverifikasi; tandai sebagai `PENDING VALIDATION`. Jangan mengisi default otomatis, scoring, atau hasil kelayakan.
- **Kifayah:** isi seluruh kategori pengeluaran secara eksplisit. Gunakan `0` untuk tidak ada pengeluaran/kategori tidak berlaku; gunakan `NULL` untuk field yang belum diisi. Jangan mengubah field kosong menjadi `0` secara otomatis. Uji nilai negatif dan input nonnumerik sebagai input yang harus ditolak.
- **Tahun PDF Kifayah:** pastikan tahun berasal dari tanggal Outreach yang tersimpan, bukan tanggal saat PDF dibuat.
- **Signature:** gunakan tanda tangan sintetis/placeholder yang diberi label DEMO, bukan gambar tanda tangan orang nyata.

## Skenario PDF

- **DEMO-001 — BAZNAS:** hasil yang diharapkan adalah tiga PDF terpisah: Outreach dengan halaman lampiran wajib setelah formulir utama, Mustahik, dan Kifayah.
- **DEMO-002 — CSR:** hasil yang diharapkan adalah tiga PDF terpisah: Outreach dengan halaman lampiran wajib setelah formulir utama, Mustahik, dan Kifayah.
- **DEMO-003 — BANGGA_SURABAYA:** hasil yang diharapkan adalah PDF Outreach dengan halaman lampiran wajib; jangan tampilkan PDF Mustahik/Kifayah sebagai output yang berlaku.
- Untuk skenario approval, seluruh PDF yang diwajibkan harus berhasil dibuat. Jika salah satu PDF wajib gagal saat approval, approval harus gagal, status tetap `MENUNGGU_PENGESAHAN`, pesan kegagalan yang jelas ditampilkan, tidak ada keputusan approval sukses yang disimpan, dan event `OUTREACH_COMPLETED` tidak dikirim.
- Setelah perbaikan dan approval yang berhasil, verifikasi PDF menggunakan versi data terbaru yang sah. Pastikan snapshot, signature, approval, serta referensi lampiran historis tidak berubah secara retroaktif.

## Skenario Negatif dan Integritas

- [ ] Required field belum lengkap memblokir Finalisasi; Draft tetap dapat disimpan.
- [ ] Salah satu dari empat lampiran wajib hilang: Finalisasi ditolak dan pesan menjelaskan lampiran yang belum ada.
- [ ] PDF wajib gagal saat approval: status tetap `MENUNGGU_PENGESAHAN`, tidak ada approval sukses yang tersimpan, dan `OUTREACH_COMPLETED` tidak dikirim.
- [ ] `edit_version` stale memunculkan conflict tanpa menimpa perubahan yang tersimpan.
- [ ] Lampiran dengan hash duplikat ditolak dengan aman dan tidak merusak data.
- [ ] Upload/kompresi gagal: sistem menampilkan error dan tidak menghilangkan lampiran secara diam-diam.
- [ ] Perubahan data form setelah signature/approval memicu pembaruan signature dan alur approval yang diwajibkan.
- [ ] Perubahan lampiran saja tidak otomatis memicu tanda tangan ulang.
- [ ] Special Unlock membuat perubahan/version sesuai Workflow v2.1 tanpa memutasi historical version lama.
- [ ] PETUGAS tidak dapat membaca atau mengubah Outreach milik Petugas lain.
- [ ] Nilai Kifayah `NULL` tetap berbeda dari `0`; nilai negatif dan input nonnumerik ditolak.
- [ ] Tutup dan buka kembali aplikasi: data yang tersimpan tetap tersedia pada database lokal non-production.

## Pencatatan Hasil

Untuk setiap skenario, catat `PASS`, `FAIL`, atau `BLOCKED`, langkah reproduksi, status/version sebelum dan sesudah, serta screenshot atau bukti yang relevan. Jangan menandai skenario `PASS` sebelum benar-benar dijalankan. Jika detail form atau aturan operasional belum didukung bukti yang cukup, tandai `PENDING VALIDATION` dan catat apa yang perlu dikonfirmasi.

## Catatan Penyelarasan Cluster 5 — 18 Oktober 2026

- Baseline aktif adalah Workflow v2.1 dan Database v1.1 dengan 22 tabel.
- Empat lampiran wajib adalah KTP, KK, foto klien, dan foto rumah.
- Baznas/CSR menghasilkan tiga PDF terpisah: Outreach dengan halaman lampiran wajib, Mustahik, dan Kifayah. Bangga Surabaya menghasilkan PDF Outreach dengan halaman lampiran wajib.
- Kegagalan pembuatan PDF wajib selama approval menggagalkan approval; status tetap `MENUNGGU_PENGESAHAN`, tidak ada approval sukses yang dipersistenkan, dan `OUTREACH_COMPLETED` tidak dikirim.
- Data dan lampiran di dokumen ini adalah contoh skenario, bukan data warga, bukan hasil pengujian yang telah lulus, dan bukan izin membuka gate implementasi.
