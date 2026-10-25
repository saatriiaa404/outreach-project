# Document Index — project1.1

## Tujuan

Indeks ini adalah registry dokumentasi dan router sumber kebenaran untuk `project1.1`. Satu jenis informasi hanya boleh memiliki satu rumah kanonis. Lokasi yang belum memiliki dokumen kanonis tidak boleh dianggap sudah selesai hanya karena direktori tujuannya tersedia.

## Urutan Baca

1. [`../README.md`](../README.md) — konteks proyek, status, dan aturan otoritas.
2. [`../AGENTS.md`](../AGENTS.md) — aturan kerja tunggal proyek untuk coding agent.
3. [`../CLAUDE.md`](../CLAUDE.md) — penunjuk khusus Claude Code; mengimpor `AGENTS.md`.
4. Dokumen ini — struktur, status, dan sumber kanonis.
5. [`project-management/decisions.md`](project-management/decisions.md) — keputusan aktif dan isu yang menunggu konfirmasi.
6. [`project-management/reconciliation.md`](project-management/reconciliation.md) — evidence dan status rekonsiliasi.
7. [`project-management/tasks.md`](project-management/tasks.md) — status task dan dependency.
8. Dokumen kanonis yang relevan dengan pekerjaan.

Jika konteks historis atau keputusan sebelumnya diperlukan, periksa file checkpoint v1.6 (`.md`), yang disimpan di luar repositori pada folder `../checkpoint/` (relatif terhadap folder `project/`). Gunakan repo lokal (atau ZIP repositori jika agent tidak punya akses langsung) untuk memeriksa kondisi berkas proyek saat ini; jangan menganggap checkpoint berada di dalam repositori atau ZIP, dan jangan menggunakan keduanya sebagai pengganti checkpoint. Jika file checkpoint tidak tersedia atau tidak dapat dibaca, sampaikan keterbatasan tersebut dan jangan mengarang isinya. Checkpoint v1.6 tetap bernama v1.6 untuk revisi dokumentasi biasa; jangan membuat checkpoint bernomor baru tanpa keputusan eksplisit pengguna.

## Registry Sumber Kebenaran

| Concern | Lokasi kanonis | Status |
|---|---|---|
| Konteks/status proyek | `README.md` | ACTIVE |
| Instruksi agent | `AGENTS.md` | ACTIVE — aturan kerja tunggal (D-017) |
| Instruksi Claude | `CLAUDE.md` | ACTIVE — penunjuk; mengimpor `AGENTS.md` |
| Peta dokumentasi | `docs/index.md` | ACTIVE |
| Keputusan | `docs/project-management/decisions.md` | ACTIVE |
| Matriks rekonsiliasi | `docs/project-management/reconciliation.md` | ACTIVE — verification in progress |
| Kebutuhan produk | `docs/product/PRD.md` | ACTIVE — reconciliation required |
| Batas scope | `docs/product/scope.md` | ACTIVE — reconciliation required |
| Terminologi | `docs/domain/glossary.md` | ACTIVE — reconciliation required |
| Form Outreach | `docs/domain/forms/outreach.md` | ACTIVE — reconciliation required |
| Form Mustahik | `docs/domain/forms/mustahik.md` | ACTIVE — reconciliation in progress; validation pending |
| Form Kifayah | `docs/domain/forms/kifayah.md` | ACTIVE — reconciliation in progress; validation pending |
| Workflow dan state transition | `docs/architecture/workflow.md` | ACTIVE — v2.1 Workflow Baseline / LOCKED |
| Database dan persistence | `docs/architecture/database.md` | ACTIVE — v1.1 Technical Baseline / 22 tables / LOCKED |
| Arsitektur aplikasi | `docs/architecture/architecture.md` | ACTIVE — reconciliation required |
| Role dan permission | `docs/architecture/roles-permissions.md` | ACTIVE — reconciliation required |
| Security | `docs/architecture/security.md` | ACTIVE — reconciliation required |
| UX/UI | `docs/ux/design.md` | ACTIVE — reconciliation required |
| Scope dan validasi prototype | `docs/prototype/` | ACTIVE — prototype scope and validation planning |
| Testing | `docs/engineering/testing.md` | ACTIVE — prototype test plan; execution pending prototype |
| Setup lingkungan lokal | `docs/engineering/setup.md` | DRAFT |
| Deployment | `docs/engineering/deployment.md` | NOT YET CREATED |
| Tugas | `docs/project-management/tasks.md` | ACTIVE — v1.6 Worklist |
| Changelog | `docs/project-management/changelog.md` | NOT YET CREATED |
| Materi sumber | `docs/reference/` | SOURCE FILES PRESENT — registry/path verification required |

## Struktur Direktori

```text
docs/
├── index.md
├── product/
├── domain/
├── architecture/
├── ux/
├── prototype/
├── engineering/
├── project-management/
└── reference/
```

## Status dan Aturan Otoritas

| Status | Arti |
|---|---|
| `ACTIVE` | Dokumen aktif dan kanonis untuk concern-nya. |
| `NOT YET CREATED` | Dokumen kanonis belum dibuat. |
| `PENDING VALIDATION` | Belum disetujui pihak yang berwenang; bukan aturan final. |
| `LOCKED` | Keputusan aktif yang tidak boleh diubah tanpa persetujuan baru. |
| `SUPERSEDED` | Keputusan lama yang digantikan keputusan baru. |
| `HISTORICAL` | Referensi historis yang tidak otomatis menentukan keputusan aktif. |
| `BLOCKED` | Pekerjaan atau keputusan tidak boleh dilanjutkan sebelum dependency kritis terpenuhi. |

Status `ACTIVE` menunjukkan bahwa dokumen merupakan sumber kanonis untuk concern-nya, bukan bahwa seluruh isinya telah selesai direkonsiliasi atau divalidasi. Status rekonsiliasi, validasi, dan penyelesaian task harus mengikuti evidence serta catatan pada dokumen manajemen proyek yang relevan.

- `project1.1` adalah workspace dokumentasi kanonis aktif.
- Checkpoint v1.6 adalah snapshot perencanaan terbaru, bukan pengganti spesifikasi kanonis.
- Checkpoint v1.5 dan versi sebelumnya tetap dipertahankan sebagai riwayat.
- Nama checkpoint v1.6 tidak berubah hanya karena revisi dokumen kanonis.
- Materi referensi hanya menjadi requirement setelah interpretasinya dicatat dalam dokumen kanonis. Persetujuan formal tetap harus dibuktikan secara terpisah.
- Jika dua sumber berbeda, catat konflik dan evidence terlebih dahulu; jangan memilih salah satu secara diam-diam.
- Jangan mengubah baseline terkunci atau membuka gate implementasi tanpa persetujuan eksplisit pengguna.

## Urutan Pekerjaan

1. Rekonsiliasi dokumen kanonis dengan checkpoint v1.6 dan keputusan aktif.
2. Rekonsiliasi form Outreach, Mustahik, dan Kifayah serta catat aturan yang masih membutuhkan evidence atau validasi.
3. Tinjau product, domain, architecture, permission, security, UX, prototype, dan testing untuk menemukan konflik kritis.
4. Tandai validasi yang belum selesai sebagai `PENDING VALIDATION`; validasi eksternal atau pengujian yang membutuhkan prototype tidak otomatis memblokir seluruh coding.
5. Verifikasi konsistensi lintas dokumen, tautan, status task, dan keputusan; selesaikan konflik kritis yang dapat mengubah perilaku bisnis, otorisasi, keamanan, workflow, atau baseline terkunci.
6. Sajikan ringkasan kesiapan, konflik kritis yang sudah diselesaikan, serta validasi yang masih tertunda kepada pengguna.
7. Mulai implementasi hanya setelah pengguna secara eksplisit menyetujui pembukaan gate.

Tahap finalisasi dokumentasi tidak otomatis membuka gate implementasi. Pembukaan gate juga tidak mengizinkan perubahan terhadap Workflow v2.1, Database v1.1 / 22 tabel, atau keputusan terkunci lainnya tanpa persetujuan eksplisit terpisah.
