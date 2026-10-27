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
| R5.1 | Signature pad Petugas (Mustahik) dan Warga (Kifayah) | 🔒 Pustaka atau canvas buatan sendiri |
| R5.2 | Halaman Review dan preview HTML/CSS | — |

## Fase 6 — Finalisasi dan versi

| ID | Task | Persetujuan |
|---|---|---|
| R6.1 | Kontrak JSON `snapshot_data` | 🔒 Kontrak disetujui |
| R6.2 | Finalisasi dan Finalisasi Ulang transaksional, `outreach_number`, versi, lampiran versi, history, notifikasi `OUTREACH_FINALIZED`, proteksi double submit | — |

## Fase 7 — Approval dan PDF gate

| ID | Task | Persetujuan / ketergantungan |
|---|---|---|
| R7.1 | Pasang Dompdf (stack D-013) | 🔒 |
| R7.2 | Template PDF Outreach (halaman lampiran gambar dan halaman rujukan PDF), Mustahik, Kifayah; `document_template_versions`; blok pengesahan sesuai VAL-004 | — |
| R7.3 | Antrean Kepala, Return dengan catatan wajib, `OUTREACH_RETURNED` | — |
| R7.4 | Approve dengan PDF gate dan uji kegagalan | — |
| R7.5 | Pengambilan PDF on-demand dari versi historis; PETUGAS tidak dapat mengunduh | — |

## Fase 8 — Special Unlock

| ID | Task |
|---|---|
| R8.1 | Lifecycle request Special Unlock |
| R8.2 | Unlock langsung, controlled edit dengan alasan wajib, `Lock` dan `Lock & Ajukan` dengan event D-031 dan audit |

## Fase 9 — Administrasi

| ID | Task | Persetujuan |
|---|---|---|
| R9.1 | Manajemen user: aktif/nonaktif, satu Kepala aktif, NIP dengan audit; fitur bawaan ubah profil dan hapus akun sendiri sudah dihapus, ganti password sendiri tetap ada | ✅ Keputusan D-044 (bagian halaman pengaturan selesai) |
| R9.2 | Master Source/Type dengan `code` tidak dapat diubah | — |
| R9.3 | Template tanda tangan Kepala dan template stempel berversi | — |
| R9.4 | Penandaan arsip manual (D-038) | — |

## Fase 10 — Dashboard, pencarian, notifikasi, dan riwayat

| ID | Task | Persetujuan |
|---|---|---|
| R10.1 | Dashboard per peran dan pencarian berbatas scope; NIK tidak masuk URL | — |
| R10.2 | Notifikasi in-app dan timeline history | — |
| R10.3 | Pembersihan notifikasi berumur 30 hari | 🔒 Cara scheduler di Windows lokal |

## Fase 11 — Penerimaan dan serah terima

| ID | Task |
|---|---|
| R11.1 | Skenario `docs/engineering/testing.md` 1–23 dan `docs/prototype/demo-checklist.md` |
| R11.2 | PERS-001: simpan, tutup, buka kembali |
| R11.3 | Prosedur backup dan restore D-038 serta uji restore |

## Keputusan Terbuka

1. Workflow CI bawaan `web/.github/workflows/tests.yml` sebelum ada push.
2. Pustaka: kompresi gambar, signature pad, Dompdf (diajukan saat fasenya).
