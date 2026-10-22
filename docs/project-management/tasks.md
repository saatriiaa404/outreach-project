# Canonical Reconciliation & Prototype Worklist — Checkpoint v1.6

## Status Operasional

**Gate implementasi prototype masih CLOSED.**

Belum boleh memulai bootstrap aplikasi, source code, schema, migration, seed, atau konfigurasi deployment. Gate hanya dapat dibuka setelah review kesiapan menunjukkan konflik kritis yang memengaruhi perilaku bisnis, autentikasi/keamanan, workflow, atau baseline terkunci telah diselesaikan atau diputuskan secara eksplisit, lalu pemilik proyek memberikan persetujuan eksplisit.

`PENDING VALIDATION` tidak otomatis menjadi blocker menyeluruh. Validasi yang aman dilakukan saat pengujian prototype atau review bersama Kelurahan boleh tetap berstatus pending, asalkan tidak menyembunyikan konflik kritis dan tidak ditandai seolah-olah sudah terverifikasi.

## Tugas Rekonsiliasi

| ID | Prioritas | Status | Task | Blocker/dependency | Dokumen/keputusan | Kriteria selesai |
|---|---|---|---|---|---|---|
| REC-001 | P0 | DONE — cakupan terbatas | Verifikasi keberadaan checkpoint dan tautan artefak yang dapat ditelusuri. | Bukti keberadaan checkpoint tersedia. | README, reconciliation, decisions | Keberadaan artefak dan tautannya tercatat. Tidak mengklaim seluruh rekonsiliasi selesai. |
| REC-002 | P0 | IN PROGRESS | Rekonsiliasi concern kanonis terhadap checkpoint v1.6. | Evidence dan status per concern. Konflik kritis harus diputuskan sebelum gate dibuka; validasi nonkritis dapat tetap pending dengan alasan yang tercatat. | reconciliation, D-007, D-009 | Setiap concern dalam matriks memiliki status dan evidence atau keputusan yang dapat ditelusuri. Concern kritis yang berdampak pada perilaku bisnis, auth/security, workflow, atau baseline terkunci tidak dibiarkan ambigu. |
| OUT-001 | P0 | IN PROGRESS | Rekonsiliasi spesifikasi Outreach dan perilaku PDF. | Field mapping, lampiran wajib/tambahan, serta perilaku approval dan PDF harus konsisten dengan baseline aktif. | outreach, workflow, database, PRD, reconciliation | Mapping dan discrepancy memiliki status/evidence. Empat lampiran dasar dibedakan dari persyaratan tambahan. Aturan PDF dan kegagalan rendering mengikuti baseline Workflow v2.1; perubahan baseline tidak boleh dilakukan diam-diam. Detail tambahan yang belum kritis boleh berstatus `PENDING VALIDATION` dengan tindak lanjut yang jelas. |
| FORM-001 | P0 | IN PROGRESS | Rekonsiliasi spesifikasi form Mustahik. | Mapping 25 pertanyaan harus dapat ditelusuri; opsi/label yang belum terverifikasi tidak boleh diklaim final. | mustahik, database, reconciliation | Mapping 25 `question_code` memiliki evidence yang dapat ditelusuri. Semua 25 pertanyaan wajib dijawab dan bersifat single-choice. Tidak ada default/auto-selection, scoring, atau mesin kelayakan yang diasumsikan. Opsi/label, metadata, dan detail snapshot/PDF yang belum didukung evidence tetap `PENDING VALIDATION`; required tidak ditandai pending karena sudah ditetapkan. |
| FORM-002 | P0 | IN PROGRESS | Rekonsiliasi spesifikasi form Kifayah. | Mapping fixed fields dan 7 pertanyaan harus dapat ditelusuri; opsi/label yang belum terverifikasi dan rincian yang memerlukan validasi sumber harus ditandai jelas. | kifayah, database, reconciliation | Fixed fields dan 7 `question_code` memiliki mapping yang dapat ditelusuri. Fixed fields wajib diisi sesuai Kifayah v1.7; semua input nominal/hitungan harus valid dan minimal 0, serta `higher_education_count` berupa bilangan bulat tanpa batas maksimum arbitrer. `0` berarti tidak ada pengeluaran/tidak berlaku; `NULL` berarti belum diisi, dan isian kosong tidak otomatis diubah menjadi `0`. Tanggal/tahun PDF mengikuti tanggal Outreach tersimpan. Identitas (diketik ulang atau ditampilkan dari Outreach), transkripsi final opsi/label, makna kolom skor, format/posisi tanda tangan, dan validasi formal Kelurahan tetap `PENDING VALIDATION`. |
| PERS-001 | P0 | PLANNED AFTER GATE | Uji persistence prototype melalui evidence testing. | Membutuhkan prototype yang dapat dijalankan; bukan prasyarat untuk mulai membangun prototype. | D-010, prototype, testing, reconciliation | Gunakan database lokal non-produksi dan data dummy untuk demo satu komputer, tanpa hosting/domain. Uji simpan, tutup aplikasi, buka kembali, dan verifikasi data tetap tersedia. Catat evidence sesuai `testing.md`. Tidak ada perubahan schema/migration yang diotorisasi oleh task ini. Tandai DONE hanya setelah pengujian berhasil dan evidence tersimpan. |
| DOC-001 | P1 | IN PROGRESS | Review dokumen kanonis dan konsistensi lintas dokumen. | REC-002 dan concern relevan. Gate hanya menunggu penyelesaian konflik kritis; item nonkritis yang pending harus memiliki alasan dan rencana validasi. | reconciliation, index, seluruh dokumen relevan | Dokumen relevan telah direview; konflik, evidence, status, dan tautan silang dicatat. Workflow v2.1 dan Database v1.1/22 tabel tetap menjadi baseline terkunci. |
| IMP-001 | P0 | BLOCKED | Review kesiapan dan buka gate implementasi prototype. | REC-002, OUT-001, FORM-001, FORM-002, DOC-001 hanya sejauh ada konflik kritis yang harus dibereskan sebelum implementasi. PERS-001 diuji setelah gate dibuka. | README, AGENTS, D-007, reconciliation, decisions | Siapkan readiness summary yang mencantumkan baseline aktif, konflik kritis yang selesai, keputusan yang masih pending beserta alasan/rencana validasi, risiko, dan acceptance scenario. Minta persetujuan eksplisit pemilik proyek sebelum membuka gate. Tidak ada gate otomatis berdasarkan status task. |

## Catatan Sinkronisasi Keputusan Terbaru

- D-008 berstatus `SUPERSEDED` oleh D-010 untuk keputusan pendekatan persistence. Riwayat keputusan lama tetap dipertahankan.
- D-010 menetapkan pendekatan persistence, tetapi bukan bukti bahwa persistence sudah teruji. Evidence simpan–tutup–buka kembali harus dikumpulkan setelah prototype tersedia.
- Baseline workflow aktif adalah **Workflow v2.1**. Jangan menyebut Workflow v2.0 sebagai baseline aktif.
- Output PDF mengikuti baseline yang terkunci:
  - **Baznas/CSR:** Outreach PDF dengan halaman lampiran yang diwajibkan setelah formulir utama, Mustahik PDF, dan Kifayah PDF sebagai tiga PDF terpisah.
  - **Bangga Surabaya:** Outreach PDF dengan halaman lampiran yang diwajibkan.
- Jika pembuatan PDF yang diwajibkan gagal saat approval, approval gagal; status tetap `MENUNGGU_PENGESAHAN`, pengguna mendapat pesan kegagalan yang jelas, keputusan approval sukses tidak disimpan, dan event `OUTREACH_COMPLETED` tidak dikirim.
- Perubahan data formulir setelah tanda tangan/approval mengharuskan pembaruan tanda tangan terkait dan alur approval yang diwajibkan. Perubahan lampiran saja tidak otomatis memerlukan tanda tangan ulang. Riwayat versi, tanda tangan, approval, dan referensi lampiran harus dipertahankan.
- Empat lampiran dasar wajib: KTP, KK, foto penerima, dan foto rumah. Finalisasi diblokir jika salah satu belum tersedia; draft tetap boleh disimpan. Persyaratan lampiran tambahan per jenis bantuan masih dapat menunggu validasi Kelurahan.
- Baseline database aktif adalah **Database v1.1 dengan 22 tabel**. Tidak ada perubahan tabel, field, relasi, constraint, migration, atau seed tanpa persetujuan eksplisit.
- Validasi label status kesejahteraan/desil harus merujuk sumber resmi. Selama sumber belum terverifikasi, tandai `PENDING VALIDATION`; jangan mengarang label.
- Gate implementasi tetap tertutup sampai readiness review selesai dan persetujuan eksplisit diberikan. Status pending yang nonkritis tidak boleh otomatis diubah menjadi blocker; sebaliknya, konflik kritis tidak boleh ditutupi sebagai pending biasa.

## Dependency Gate Implementasi

Sebelum gate dibuka, tinjau:

- `REC-002` — rekonsiliasi concern kanonis dan status evidence.
- `OUT-001` — konsistensi Outreach, lampiran, approval, dan PDF.
- `FORM-001` — struktur dan aturan form Mustahik.
- `FORM-002` — struktur dan aturan form Kifayah.
- `DOC-001` — konsistensi dokumen lintas sumber.

Task di atas tidak harus menunggu setiap validasi nonkritis dari Kelurahan jika validasi tersebut dapat dilakukan secara aman selama evaluasi prototype. Namun, konflik yang memengaruhi perilaku bisnis, auth/security, workflow, atau baseline terkunci harus diselesaikan atau diputuskan secara eksplisit sebelum gate dibuka.

`PERS-001` adalah acceptance test setelah prototype dapat dijalankan, bukan prasyarat untuk mulai mengimplementasikannya. Status DONE pada task lain tidak otomatis membuka gate.

## Acceptance Scenario Setelah Gate Dibuka

Rujuk `docs/engineering/testing.md`, termasuk:

- Draft dan Finalisasi.
- Return dan Revisi.
- Approval serta kegagalan pembuatan PDF yang diwajibkan.
- Special Unlock.
- Historical integrity.
- Server-side authorization.
- Concurrency.
- Duplicate attachment.
- Persistence: simpan, tutup aplikasi, buka kembali, dan verifikasi data.
- Backup mingguan database dan seluruh lampiran; uji restore setelah alur utama stabil dan sebelum handover.

## Larangan Saat Gate CLOSED

Jangan membuat bootstrap, source code, schema, migration, seed, atau konfigurasi deployment sebelum readiness summary disampaikan dan gate dibuka dengan persetujuan eksplisit. Jangan menandai task selesai tanpa evidence atau keputusan yang memenuhi kriteria selesai.

Status `IN PROGRESS` pada task form menunjukkan rekonsiliasi sedang berjalan, bukan bahwa seluruh aturan form telah divalidasi atau disetujui Kelurahan. `PENDING VALIDATION` harus menyebutkan apa yang belum terverifikasi dan kapan/cara validasinya akan dilakukan.
