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
| R1.1 | Dokumen desain migration 22 tabel domain + `users.nip`: tipe kolom (termasuk `mck_layak` dan kolom uang), FK, index, kode nilai enum; revisi migration `users` bawaan (`password_hash`, tanpa `remember_token`/`email_verified_at`, tanpa tabel `sessions` dan `password_reset_tokens`) sesuai D-030 dan D-041 | 🔒 Desain disetujui |
| R1.2 | Implementasi migration per kelompok: master dan akun, Outreach, form, file dan lampiran, versi dan approval, history/audit/notifikasi/arsip/special unlock | 🔒 Menjalankan migration |
| R1.3 | Model User memakai `password_hash`, role, dan status; akun `INACTIVE` tidak dapat login; checkbox "remember me" dibuang | Ikut R1.1 |
| R1.4 | Kerangka otorisasi: policy per role, ownership/scope, status, dan resource, beserta tes akses ditolak | — |
| R1.5 | Basis data uji dan menjalankan tes bawaan yang tersisa | 🔒 Pilihan DB uji |
| R1.6 | Seeder data dummy sesuai `docs/prototype/dummy-data.md` | 🔒 Seed |

## Fase 2 — Outreach inti

| ID | Task |
|---|---|
| R2.1 | Buat Outreach: Source → Type → Date, `draft_code`, status `DRAFT` |
| R2.2 | Stepper dan form Data Klien, Keluarga, Rumah & Aset, Narasi (D-019 sampai D-021, D-025, D-027, D-032) |
| R2.3 | Save Draft dan autosave dengan `edit_version` serta state autosave |
| R2.4 | Validasi Draft vs Finalisasi, semantik `NULL`/`0`/`-`, total turunan |
| R2.5 | Ganti Source/Type sebelum Finalisasi beserta penanganan destruktif |

## Fase 3 — Lampiran

| ID | Task | Persetujuan |
|---|---|---|
| R3.1 | Pipeline unggah: validasi isi file, hash SHA-256, penyimpanan privat, `files`/`outreach_attachments`, reaktivasi soft delete | — |
| R3.2 | Kompresi gambar (usulan: GD bawaan) | 🔒 Bila memakai pustaka |
| R3.3 | Empat lampiran wajib berupa gambar, lampiran tambahan tidak memblokir, lampiran PDF diterima (D-033, D-034) | — |

## Fase 4 — Mustahik dan Kifayah

| ID | Task | Bergantung pada |
|---|---|---|
| R4.1 | Form Mustahik: 25 pertanyaan single-choice literal, header sesuai D-036 | VAL-005 (aturan wajib header) |
| R4.2 | Form Kifayah: nominal `0`/`NULL`, identitas read-only, 7 pertanyaan pilihan | Register opsi VAL-003 |

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
| R9.1 | Manajemen user: aktif/nonaktif, satu Kepala aktif, NIP dengan audit; penanganan fitur bawaan ubah profil dan hapus akun sendiri | 🔒 Keputusan |
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

1. Desain migration R1.1.
2. Basis data uji: MySQL terpisah (usulan) atau SQLite in-memory.
3. Ganti password sendiri (usulan: boleh); halaman ubah profil dan hapus akun sendiri (usulan: dihapus).
4. Workflow CI bawaan `web/.github/workflows/tests.yml` sebelum ada push.
5. Pustaka: kompresi gambar, signature pad, Dompdf (diajukan saat fasenya).
