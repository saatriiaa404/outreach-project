# Roadmap Implementasi Prototype — Tahap 2

> **Status dokumen:** ACTIVE — disetujui pemilik proyek pada 27 Oktober 2026  
> **Dasar:** gate IMP-001 dibuka dengan cakupan BOOT-001 dan roadmap Tahap 2; kondisi awal commit `9a6f17e` (BOOT-001 selesai)  
> **Penting:** Persetujuan roadmap bukan persetujuan migration, seed, dependensi baru, atau perubahan baseline. Titik bertanda 🔒 memerlukan persetujuan eksplisit terpisah sebelum dikerjakan.

## Aturan Pelaksanaan

- Satu task menjadi satu commit yang dapat diverifikasi (sekitar satu hari kerja atau kurang).
- Ikuti `AGENTS.md`: otorisasi server-side, data dummy, tanpa push tanpa izin, dan sinkronisasi dokumen kanonis bila perilaku terdokumentasi berubah.
- Urutan fase dapat disesuaikan bila dependensi mengizinkan; perubahan urutan dicatat di dokumen ini.

## Fase 0 — Lingkungan dan bootstrap (selesai)

ENV-001, BOOT-001 (Laravel 13.38 di `project/web/`, terhubung ke MySQL tanpa migration). Lihat `docs/engineering/setup.md` bagian 9.

## Fase 1 — Fondasi data, auth, dan pengujian

| ID | Task | Persetujuan |
|---|---|---|
| R1.1 | Dokumen desain migration (`docs/engineering/migration-design.md`) 22 tabel domain + `users.nip`: tipe kolom (termasuk `mck_layak` dan kolom uang), FK, index, kode nilai enum; revisi migration `users` bawaan (`password_hash`, tanpa `remember_token`/`email_verified_at`, tanpa tabel `sessions` dan `password_reset_tokens`) sesuai D-030 dan D-041 | ✅ Disetujui (D-043) |
| R1.2 | Implementasi migration per kelompok: master dan akun, Outreach, form, file dan lampiran, versi dan approval, history/audit/notifikasi/arsip/special unlock | ✅ Selesai 27 Oktober 2026 (`web/database/migrations/`, 22 tabel domain + `migrations`) |
| R1.3 | Model User memakai `password_hash`, role, dan status; akun `INACTIVE` tidak dapat login; checkbox "remember me" dibuang | ✅ Selesai 27 Oktober 2026 (19 tes lulus pada `outreach_prototype_test`) |
| R1.4 | Kerangka otorisasi: policy per role, ownership/scope, status, dan resource, beserta tes akses ditolak | ✅ Selesai 27 Oktober 2026 (`OutreachPolicy`, scope `visibleTo`, middleware akun INACTIVE; 165 tes lulus) |
| R1.5 | Basis data uji dan menjalankan tes bawaan yang tersisa | ✅ Diputuskan: MySQL terpisah `outreach_prototype_test` (27 Oktober 2026); tes bawaan dijalankan ulang setelah R1.3 |
| R1.6 | Seeder data dummy sesuai `docs/prototype/dummy-data.md` | ✅ Selesai 27 Oktober 2026: master Source/Type, tiga akun demo, DEMO-001 dan DEMO-003 (DRAFT). DEMO-002 dan DEMO-004 menyusul setelah R6/R7 karena butuh versi, approval, dan lampiran |

## Fase 2 — Outreach inti

| ID | Task |
|---|---|
| R2.1 | Buat Outreach: Source → Type → Date, `draft_code`, status `DRAFT` — ✅ selesai 27 Oktober 2026 (halaman daftar dan buat, history `CREATED`) |
| R2.2 | Stepper dan form Data Klien, Keluarga, Rumah & Aset, Narasi (D-019 sampai D-021, D-025, D-027, D-032) — ✅ selesai 27 Oktober 2026 (Simpan Draft dan Simpan & Lanjutkan dengan `edit_version`; tanggal form buat terisi hari ini) |
| R2.3 | Save Draft dan autosave dengan `edit_version` serta state autosave — ✅ selesai 27 Oktober 2026 (autosave ±60 detik saat ada perubahan dan sebelum pindah langkah, lewat service yang sama dengan Save Draft tanpa event History; state `IDLE`/`SAVING`/`SAVED`/`FAILED`/`CONFLICT`/`CONNECTION_LOST`; konflik versi menjawab 409 dan menawarkan muat ulang) |
| R2.4 | Validasi Draft vs Finalisasi, semantik `NULL`/`0`/`-`, total turunan — ✅ selesai 27 Oktober 2026 (pemeriksaan kelengkapan Finalisasi untuk data form di `OutreachFormSchema::missingForFinalization`, dipakai indikator stepper dan nanti aksi Finalisasi R6.2; Draft tetap boleh tidak lengkap; total pendapatan/pengeluaran diturunkan dari komponen dan belum dihitung selama ada komponen kosong; representasi `-` diterapkan saat render PDF R7.2) |
| R2.5 | Ganti Source/Type sebelum Finalisasi beserta penanganan destruktif — ✅ selesai 27 Oktober 2026 (di langkah Identitas, hanya Petugas pemilik dan status `DRAFT`; Baznas/CSR → Bangga Surabaya wajib konfirmasi lalu menghapus form Mustahik, Kifayah, dan tanda tangannya (berkas tanda tangan ditandai `purged_at` dan dihapus dari storage); perubahan lain mempertahankan data; dicatat sebagai `SECTION_SAVED` dengan `field_changes` before/after karena daftar event tidak memiliki event khusus. Dampak ganti Type terhadap lampiran tambahan per Type ditinjau saat R3) |

## Fase 3 — Lampiran

| ID | Task | Persetujuan |
|---|---|---|
| R3.1 | Pipeline unggah: validasi isi file, hash SHA-256, penyimpanan privat, `files`/`outreach_attachments`, reaktivasi soft delete | ✅ Selesai 28 Oktober 2026 (batas teknis D-047; langkah Lampiran di stepper dengan ambil foto/pilih berkas, ganti, hapus; berkas privat hanya lewat route berotorisasi; tolak duplikat per Outreach dan pulihkan relasi lama bila berkas yang sama diunggah lagi) |
| R3.2 | Kompresi gambar (usulan: GD bawaan) | ✅ Selesai 28 Oktober 2026 dengan GD bawaan PHP, tanpa pustaka baru (JPEG, sisi terpanjang 2.000 px tanpa upscale, kualitas 80, orientasi EXIF, metadata dibuang; berkas asli tidak disimpan; PDF tidak diubah; uji: foto 4000×3000 3 MB menjadi 2000×1500 652 KB) |
| R3.3 | Empat lampiran wajib berupa gambar, lampiran tambahan tidak memblokir, lampiran PDF diterima (D-033, D-034) | ✅ Selesai 28 Oktober 2026 (empat lampiran dasar berupa gambar masuk pemeriksaan kelengkapan Finalisasi bersama data form; lampiran tambahan dan PDF tidak memblokir; saran lampiran tambahan per Jenis dari `outreach.md` 7.2 ditampilkan berlabel `PENDING VALIDATION`; lampiran PDF ditandai sebagai halaman rujukan. Ganti Jenis tidak menghapus lampiran tambahan karena kategorinya umum) |

## Fase 4 — Mustahik dan Kifayah

| ID | Task | Bergantung pada |
|---|---|---|
| R4.1 | Form Mustahik: 25 pertanyaan single-choice literal, header sesuai D-036 | ✅ Selesai 28 Oktober 2026 setelah VAL-005 diputuskan (D-048): langkah Mustahik untuk Baznas/CSR dengan header, tombol salin data Klien, 25 pertanyaan literal tanpa pilihan otomatis atau skor, jawaban `{"option": "<posisi 1-4 pada PDF>"}`, Simpan Draft dan autosave dengan `edit_version`, serta masuk pemeriksaan kelengkapan Finalisasi. Tanda tangan Petugas menyusul di R5.1 |
| R4.2 | Form Kifayah: nominal `0`/`NULL`, identitas read-only, 7 pertanyaan pilihan | ✅ Selesai 28 Oktober 2026: langkah Kifayah untuk Baznas/CSR dengan identitas read-only dari Outreach, 2 pendapatan, 12 pengeluaran, dan jumlah pendidikan tinggi yang wajib diisi eksplisit (`0` ≠ kosong, tanpa isi otomatis), 7 pertanyaan literal D-037 dengan kode posisi opsi, Simpan Draft dan autosave dengan `edit_version`, serta masuk pemeriksaan kelengkapan Finalisasi. Kotak centang di PDF (VAL-006) tidak diterapkan; tanda tangan Warga menyusul di R5.1 |

## Fase 5 — Tanda tangan dan Review

| ID | Task | Persetujuan |
|---|---|---|
| R5.1 | Signature pad Petugas (Mustahik) dan Warga (Kifayah) | ✅ Selesai 28 Oktober 2026 dengan pustaka `signature_pad` 5.1.4 (D-049): langkah Tanda Tangan untuk Baznas/CSR dengan tombol Ulangi dan Batalkan goresan terakhir; PNG divalidasi, dibersihkan, dan disimpan privat; tanda tangan dibuat baru per Outreach; dihapus otomatis bila data yang tercetak pada form yang ditandatangani berubah; masuk pemeriksaan kelengkapan Finalisasi |
| R5.2 | Halaman Review dan preview HTML/CSS | ✅ Selesai 28 Oktober 2026: langkah Review berisi status siap/belum, kelengkapan per bagian dengan tautan Lengkapi, status tanda tangan, preview Laporan Hasil Outreach (kop, judul, Jenis, kalimat pembuka dari Tanggal Outreach, empat tabel, narasi, blok Mengetahui, halaman lampiran dan rujukan PDF) yang dibangun dari `OutreachDocument` agar dipakai ulang oleh PDF R7.2, serta hasil Mustahik dan Kifayah. Format nilai mengikuti D-050. Tombol Finalisasi menyusul di R6.2 |

## Fase 6 — Finalisasi dan versi

| ID | Task | Persetujuan |
|---|---|---|
| R6.1 | Kontrak JSON `snapshot_data` | ✅ Disetujui 28 Oktober 2026 (D-051; `migration-design.md` 3.12.1) |
| R6.2 | Finalisasi dan Finalisasi Ulang transaksional, `outreach_number`, versi, lampiran versi, history, notifikasi `OUTREACH_FINALIZED`, proteksi double submit | ✅ Selesai 28 Oktober 2026: tombol Finalisasi di Review dengan konfirmasi; satu transaksi dengan kunci baris memvalidasi ulang kelengkapan, membuat `OUT-<tahun>-<5 digit>` (Q-04) pada submission pertama, versi berurutan dengan snapshot D-051, salinan lampiran versi, referensi tanda tangan yang dicek `signer_type`, status `MENUNGGU_PENGESAHAN`, history `FINALIZED`/`REFINALIZED`, dan notifikasi ke setiap Kepala aktif. Seed paket template `PACKAGE_BAZNAS_CSR` dan `PACKAGE_BANGGA_SURABAYA` v1 disetujui dan dijalankan ke `outreach_prototype`. Preview Draft kini juga dirender dari snapshot |

## Fase 7 — Approval dan PDF gate

| ID | Task | Persetujuan / ketergantungan |
|---|---|---|
| R7.1 | Pasang Dompdf (stack D-013) | ✅ Selesai 28 Oktober 2026: `dompdf/dompdf` 3.1.6 langsung (D-052) dan `PdfRenderer` tanpa akses remote/PHP/JavaScript, chroot ke `resources/views/pdf`, huruf DejaVu Sans, gambar privat sebagai data URI |
| R7.2 | Template PDF Outreach (halaman lampiran gambar dan halaman rujukan PDF), Mustahik, Kifayah; `document_template_versions`; blok pengesahan sesuai VAL-004 | ✅ Selesai 28 Oktober 2026: `OutreachPdf` merender PDF dari versi historis (snapshot, lampiran versi, tanda tangan versi) memakai view `pdf.v1.*` sesuai paket template versi tersebut; Outreach mengikuti template beserta halaman lampiran dan rujukan PDF serta blok pengesahan (diisi saat Approve, R7.4); Mustahik satu halaman tiga kolom dengan centang di kolom score; Kifayah dua halaman (D-053). Logo pada kop dan formulir belum dicetak karena berkas logonya belum tersedia di repo |
| R7.3 | Antrean Kepala, Return dengan catatan wajib, `OUTREACH_RETURNED` | ✅ Selesai 28 Oktober 2026: daftar Outreach Kepala dibuka pada antrean `MENUNGGU_PENGESAHAN` (pengajuan terlama lebih dulu) dengan filter status; halaman tinjau read-only menampilkan versi terbaru dari snapshot, lampiran dan tanda tangan versi, hasil Mustahik/Kifayah, serta riwayat pengajuan; Return dalam satu transaksi berkunci hanya untuk versi terbaru yang masih `PENDING_APPROVAL`, mencatat `outreach_approvals` `RETURNED` dengan catatan wajib tanpa tanda tangan Kepala, versi menjadi `RETURNED`, status `PERLU_PERBAIKAN`, history `RETURNED` beserta alasan, dan notifikasi `OUTREACH_RETURNED` ke Petugas pemilik. Untuk `PERLU_PERBAIKAN`, Kepala hanya melihat status dan riwayat beserta catatan Return (D-039); Petugas melihat catatan Return pada langkah pengisian dan Review, lalu Finalisasi Ulang. Tampilan daftar notifikasi menyusul di R10.2 |
| R7.4 | Approve dengan PDF gate dan uji kegagalan | ✅ Selesai 29 Oktober 2026: tombol Sahkan di halaman tinjau; satu transaksi berkunci hanya untuk versi terbaru yang masih `PENDING_APPROVAL` mencatat `outreach_approvals` `APPROVED` dengan template tanda tangan Kepala dan `decided_at`, versi `APPROVED`, status `SELESAI`, dan history `APPROVED`, lalu merender seluruh PDF wajib Source dari versi tersebut beserta blok pengesahan; bila satu PDF gagal, seluruhnya dibatalkan dengan pesan jelas dan status tetap `MENUNGGU_PENGESAHAN`. `OUTREACH_COMPLETED` dikirim setelah commit dan kegagalannya tidak membatalkan pengesahan. NIP dan template tanda tangan wajib; template sintetis untuk Kepala demo dan tanpa stempel (D-054). PDF tidak disimpan; unduhan on-demand di R7.5 |
| R7.5 | Pengambilan PDF on-demand dari versi historis; PETUGAS tidak dapat mengunduh | ✅ Selesai 29 Oktober 2026: bagian "PDF final" di halaman tinjau Outreach `SELESAI` untuk Kepala dan Admin; setiap unduhan merender ulang dokumen paket template dari versi `APPROVED` (snapshot, lampiran dan tanda tangan versi, keputusan pengesahan beserta template tanda tangannya) tanpa menyimpan berkas. Nama dan NIP Kepala dibaca saat render (database.md 4.1). PETUGAS ditolak 403 dan tidak melihat tombol unduh (D-040); dokumen di luar paket atau versi yang belum disahkan tidak dilayani; kegagalan render menampilkan pesan, bukan berkas parsial |

## Fase 8 — Special Unlock

| ID | Task |
|---|---|
| R8.1 | Lifecycle request Special Unlock. ✅ Selesai 29 Oktober 2026: Petugas pemilik dan Kepala meminta dari halaman Outreach `MENUNGGU_PENGESAHAN`/`SELESAI` dengan alasan wajib; satu permintaan terbuka per Outreach; pengaju membatalkan selama `PENDING`; Admin bekerja dari antrean (menu Special Unlock, terbuka dan terlama di atas): Mulai proses lalu Tolak dengan alasan wajib yang terlihat oleh pengaju. Status Outreach tidak berubah. `COMPLETED` menyusul otomatis di R8.2 (D-055) |
| R8.2 | Unlock langsung, controlled edit dengan alasan wajib, `Lock` dan `Lock & Ajukan` dengan event D-031 dan audit. ✅ Selesai 29 Oktober 2026: Admin membuka Special Unlock dari halaman Outreach `MENUNGGU_PENGESAHAN`/`SELESAI` dengan alasan wajib; selama sesi aktif Admin mengedit lewat stepper biasa (termasuk Source/Jenis, lampiran, Mustahik, Kifayah) tanpa menandatangani, setiap perubahan tercatat `SPECIAL_UNLOCK_EDITED`; Lock (catatan wajib, `PERLU_PERBAIKAN` tanpa versi baru) atau Lock & Ajukan (validasi seperti Finalisasi, versi baru, `MENUNGGU_PENGESAHAN`, notifikasi Kepala); keduanya menyelesaikan permintaan yang diproses dan tercatat di `audit_logs`; Kepala tidak dapat memutus selama sesi aktif (D-056). Perbaikan ikutan: tanda tangan yang dirujuk versi historis tidak lagi dihapus ketika data diubah setelah Return atau Special Unlock |

## Fase 9 — Administrasi

| ID | Task | Persetujuan |
|---|---|---|
| R9.1 | Manajemen user: aktif/nonaktif, satu Kepala aktif, NIP dengan audit; fitur bawaan ubah profil dan hapus akun sendiri sudah dihapus, ganti password sendiri tetap ada | ✅ Keputusan D-044 (bagian halaman pengaturan selesai). ✅ Selesai 29 Oktober 2026: menu Pengguna khusus Admin untuk membuat akun dengan password awal, mengubah nama/email/status, reset password, satu Kepala aktif (Kepala lama dinonaktifkan dulu), NIP khusus Kepala dengan koreksi administratif beralasan setelah dipakai pada pengesahan, tidak bisa menonaktifkan diri sendiri atau Admin aktif terakhir; role tetap; semua tercatat di `audit_logs` (D-060) |
| R9.2 | Master Source/Type dengan `code` tidak dapat diubah | ✅ Selesai 29 Oktober 2026: menu Master Source/Jenis khusus Admin; ganti nama dan aktif/nonaktif untuk tiga Source baseline (tidak dapat ditambah) dan Jenis, tambah Jenis baru dengan kode unik yang tidak dapat diubah, jumlah pemakaian per item, minimal satu Source dan satu Jenis aktif, semua perubahan di `audit_logs` (D-057) |
| R9.3 | Template tanda tangan Kepala dan template stempel berversi | ✅ Selesai 29 Oktober 2026: menu Template TTD/Stempel khusus Admin; unggah PNG/JPG per akun Kepala menjadi versi baru yang langsung aktif (versi lama dinonaktifkan, tidak dihapus), nonaktifkan tanpa pengganti, riwayat versi dengan pratinjau; stempel dikelola dengan maksimal satu aktif tetapi belum dicetak (PENDING VALIDATION); audit untuk setiap perubahan (D-058) |
| R9.4 | Penandaan arsip manual (D-038) | ✅ Selesai 29 Oktober 2026: menu Arsip khusus Admin berisi Outreach `SELESAI` per versi yang disahkan dengan batas satu bulan sejak pengesahan (terlewat ditandai), filter belum/sudah/semua; tandai diarsipkan (sistem, referensi opsional, tanggal), koreksi, dan tandai terverifikasi, semuanya diaudit (D-059) |

## Fase 10 — Dashboard, pencarian, notifikasi, dan riwayat

| ID | Task | Persetujuan |
|---|---|---|
| R10.1 | Dashboard per peran dan pencarian berbatas scope; NIK tidak masuk URL | ✅ Selesai 29 Oktober 2026: dashboard Petugas (jumlah per status miliknya, Perlu Perbaikan dan Draft lebih dulu), Kepala (antrean dan Selesai), Admin (semua status, permintaan Special Unlock terbuka, arsip belum dan lewat batas); pencarian nama, NIK (sama persis), ID internal, Source, Jenis, rentang tanggal melalui POST dan sesi sehingga NIK tidak masuk URL; selalu di atas visibilitas peran (D-061) |
| R10.2 | Notifikasi in-app dan timeline history | ✅ Selesai 29 Oktober 2026: menu Notifikasi dengan jumlah belum dibaca, buka untuk menandai dibaca dan menuju Outreach sesuai hak akses saat ini, tandai semua dibaca; timeline lengkap di halaman tinjau (bagian yang disimpan, lampiran, tanda tangan, keputusan, Special Unlock beserta field yang diubah) dengan batas D-039 untuk Kepala (D-062) |
| R10.3 | Pembersihan notifikasi berumur 30 hari | ✅ Selesai 29 Oktober 2026: perintah `notifications:prune` dijadwalkan harian di scheduler Laravel (`schedule:work` atau Task Scheduler untuk `schedule:run`, lihat `setup.md`); notifikasi kedaluwarsa disembunyikan dari tampilan sebelum dibersihkan (D-063) |

## Fase 11 — Penerimaan dan serah terima

| ID | Task |
|---|---|
| R11.1 | Skenario `docs/engineering/testing.md` 1–23 dan `docs/prototype/demo-checklist.md` — ✅ Uji otomatis 29 Oktober 2026: 22 skenario PASS (No. 15 dengan temuan 31.3), No. 14 menunggu R11.2; 5 tes baru untuk celah No. 8, 18, 20, 23; walkthrough demo checklist bersama pihak terkait dijalankan pemilik proyek |
| R11.1a | Penerapan jawaban validasi Kelurahan — ✅ 29 Oktober 2026: label desil DTST, centang pengeluaran Kifayah, "Lampiran lain" dan batas 10 lampiran, pangkat/nama kapital/stempel pada pengesahan, logo Pemkot dan Baznas (template PDF v2), seed DEMO-002/DEMO-004, CI bawaan dihapus (D-064, D-065) |
| R11.2 | PERS-001: simpan, tutup, buka kembali — ✅ 30 Oktober 2026: 23 tabel dan 14 berkas identik setelah Laragon ditutup dan dibuka kembali (bukti di `testing.md`) |
| R11.3 | Prosedur backup dan restore D-038 serta uji restore — ✅ 30 Oktober 2026: perintah `backup:create` dan `backup:restore` (database dan berkas privat, dengan manifest hash), prosedur di `setup.md`, uji restore setelah simulasi kehilangan data cocok penuh (bukti di `testing.md`) |

## Fase 12 — Revisi pemilik proyek

| ID | Task |
|---|---|
| R12.1 | Revisi 30 Oktober 2026 — ✅ profil akun (D-067); tombol unggah template dan grafik dashboard Admin (D-068); pratinjau Mustahik/Kifayah sebagai dokumen (D-069); template PDF v3 meniru formulir sumber dan kop gambar (D-070); kolom angka NIK/RT/RW dan navigasi kolom (D-071); filter per kolom, Riwayat dapat dibuka-tutup, tombol Kembali (D-072); halaman awal langsung ke login |

## Keputusan Terbuka

1. Workflow CI bawaan `web/.github/workflows/tests.yml` sebelum ada push.
2. Pustaka: kompresi gambar, signature pad, Dompdf (diajukan saat fasenya).
