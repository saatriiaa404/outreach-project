# Workflow & State Transition Specification

# 1. Document Information

| Item | Value |
|---|---|
| Project | Pengembangan Sistem Informasi Pendataan Outreach Berbasis Web di Kelurahan Manukan Kulon, Kota Surabaya |
| Document | Workflow & State Transition Specification |
| Version | 2.1 |
| Status | ACTIVE — Workflow Baseline (reconciled) |
| Partner | Kelurahan Manukan Kulon, Kota Surabaya |
| Development Team | 2 mahasiswa Teknik Informatika — Studi Independen |
| Development Method | Prototype |

### Document Responsibility

Dokumen ini merupakan sumber canonical untuk **workflow, state transition,
actor interaction, business action, transition gate, revision flow, approval
flow, exception flow, dan lifecycle behavior** sistem.

Struktur database berada di `docs/architecture/database.md`, struktur aplikasi
berada di `docs/architecture/architecture.md`, form domain berada di
`docs/domain/forms/`, permission detail berada di
`docs/architecture/roles-permissions.md`, dan security controls berada di
`docs/architecture/security.md`.

Dokumen ini menetapkan perilaku proses. Detail implementasi boleh berbeda selama
tidak mengubah state, authority, gate, historical integrity, dan business rules
yang ditetapkan di sini.

# 2. Purpose & Authority

## 2.1 Purpose

Workflow sistem dirancang untuk memindahkan proses Outreach dari pengisian
lapangan berbasis kertas menuju proses digital yang:

- dapat diisi secara bertahap dan disimpan sebagai Draft;
- dapat dilanjutkan kembali selama masih berada dalam kondisi editable;
- menggunakan alur dan form sesuai Source Outreach;
- mendukung lampiran dan tanda tangan sesuai peran;
- menyediakan Review sebelum Finalisasi;
- menggunakan historical version untuk setiap submission resmi;
- memungkinkan Return dan Revision secara terkontrol;
- menyediakan Special Unlock sebagai mekanisme exception administratif;
- mengatur proses pengesahan oleh Kepala Kelurahan;
- menghasilkan dan memvalidasi seluruh PDF wajib sebagai bagian dari approval gate;
- menyediakan PDF final untuk diambil atau dibuat ulang dari historical version yang sesuai;
- mempertahankan historical integrity, auditability, dan jejak keputusan.

## 2.2 Authority

Dokumen ini merupakan sumber canonical untuk:

- workflow behavior;
- state transition;
- actor interaction;
- business action;
- transition gate;
- revision flow;
- approval flow;
- exception flow;
- lifecycle behavior.

Dokumen ini tidak menjadi sumber canonical untuk struktur database,
permission detail, security control, atau struktur form. Hal tersebut diatur
oleh dokumen canonical terkait.

Apabila prototype atau implementasi berbeda dengan workflow yang telah
ditetapkan, perbedaan tersebut harus diperlakukan sebagai perubahan desain dan
tidak boleh diperbaiki secara diam-diam.

Ketentuan yang masih memerlukan validasi operasional dengan Kelurahan tetap
diberi status `PENDING VALIDATION` dan tidak boleh diperlakukan sebagai
keputusan final.

# 3. Workflow Overview

Workflow utama adalah:

```text
LOGIN
  ↓
CREATE OUTREACH
  ↓
SOURCE → TYPE → DATE
  ↓
DATA COLLECTION
  ↓
REVIEW
  ↓
FINALISASI
  ↓
MENUNGGU_PENGESAHAN
  ↓
KEPALA REVIEW
  ├── RETURN
  │     ↓
  │  PERLU_PERBAIKAN
  │     ↓
  │  PETUGAS REVISI
  │     ↓
  │  FINALISASI ULANG
  │     ↓
  │  MENUNGGU_PENGESAHAN
  │
  └── APPROVE
        ↓
      PDF GATE
        ├── FAIL
        │    ↓
        │  MENUNGGU_PENGESAHAN
        │
        └── SUCCESS
             ↓
           SELESAI
```

Source menentukan struktur/alur formulir, sedangkan Type menentukan jenis
intervensi. Source Baznas dan CSR menggunakan Mustahik dan Kifayah; Source
Bangga Surabaya tidak menggunakan kedua form tersebut dan tidak menampilkan
step Tanda Tangan sebelum approval.

Special Unlock merupakan exception mode administratif, bukan business state.

Return atau Revision tidak membuat record Outreach baru. Revision menggunakan
Outreach yang sama dan submission resmi berikutnya membuat historical version
baru.

Approval hanya berhasil jika seluruh PDF yang diwajibkan oleh Source berhasil dirender. Setelah approval dan seluruh PDF wajib berhasil, status menjadi `SELESAI`. PDF dapat diambil atau dibuat ulang dari historical version yang sesuai untuk proses administrasi/arsip Kelurahan.

# 4. Actors & Authority

## 4.1 PETUGAS

Petugas Lapangan merupakan actor utama pengumpulan dan pengisian data Outreach.

Petugas dapat:

- login;
- membuat Outreach;
- memilih Source, Type, dan Date saat pembuatan;
- mengisi dan menyimpan data Outreach;
- mengisi Mustahik dan Kifayah jika diwajibkan Source;
- mengunggah dan mengelola lampiran selama status mengizinkan;
- melakukan signature Petugas jika diwajibkan workflow;
- meminta signature Warga/Klien jika diwajibkan workflow;
- menyimpan dan melanjutkan Draft;
- melakukan Finalisasi;
- memperbaiki Outreach yang dikembalikan;
- melakukan Finalisasi Ulang;
- mengajukan Request Special Unlock untuk koreksi Outreach yang telah terkunci.

Petugas tidak dapat melakukan Special Unlock secara langsung.

Dalam alur normal, Petugas hanya dapat mengakses dan mengubah Outreach
miliknya sendiri sesuai status dan permission yang berlaku.

## 4.2 KEPALA KELURAHAN

Kepala Kelurahan memiliki scope pada Outreach dalam lingkup Kelurahan.

Kepala dapat:

- melihat antrean `MENUNGGU_PENGESAHAN`;
- membuka dan melakukan review Outreach;
- melihat data terkait, lampiran, dan hasil form;
- melakukan `RETURN` dengan alasan;
- meminta atau menggunakan jalur Special Unlock melalui Admin;
- melakukan `APPROVE / SAHKAN`;
- menjadi sumber otorisasi pengesahan Outreach.

Kepala tidak mengedit field Outreach secara langsung melalui alur approval
normal dan bukan executor Special Unlock.

## 4.3 ADMIN

Admin menangani administrasi sistem dan exception workflow.

Admin dapat:

- mengelola user;
- mengelola template signature Kepala;
- mengelola template stamp sesuai kewenangan;
- memonitor storage;
- mengelola Request Special Unlock;
- melakukan Special Unlock secara langsung tanpa request;
- melakukan seluruh controlled editing ketika Special Unlock aktif;
- melakukan `Lock` apabila koreksi masih membutuhkan pekerjaan Petugas/Warga;
- melakukan `Lock & Ajukan untuk Pengesahan` apabila submission sudah siap diajukan;
- melakukan tindakan administratif lain yang ditetapkan baseline.

Admin bukan pihak yang melakukan approval normal dan tidak dapat mengesahkan
Outreach atas nama Kepala Kelurahan.

# 5. State Model

## 5.1 Primary Business States

Outreach memiliki empat status bisnis utama:

```text
DRAFT
MENUNGGU_PENGESAHAN
PERLU_PERBAIKAN
SELESAI
```

Tidak ada state utama tambahan `DIISI_PETUGAS`.

`SPECIAL UNLOCK` bukan status bisnis. Special Unlock merupakan mode exception
administratif yang dapat berjalan di atas status bisnis yang sedang berlaku.

Contoh:

```text
SELESAI
+
SPECIAL UNLOCK ACTIVE
```

Status bisnis tetap `SELESAI` selama Admin sedang melakukan controlled editing.

Setelah koreksi resmi diajukan sebagai submission baru, dibuat historical
version baru dan status kembali ke `MENUNGGU_PENGESAHAN`.

## 5.2 State Meaning

| State | Meaning |
|---|---|
| `DRAFT` | Outreach masih berada dalam proses pengumpulan/pengisian dan belum memiliki active submission yang menunggu pengesahan. Draft boleh disimpan walaupun lampiran wajib belum lengkap. |
| `MENUNGGU_PENGESAHAN` | Submission terbaru telah difinalisasi dan menunggu keputusan Kepala Kelurahan. Status ini juga dipertahankan jika approval gagal karena salah satu PDF wajib tidak berhasil dirender. |
| `PERLU_PERBAIKAN` | Submission terbaru dikembalikan oleh Kepala dengan alasan/note dan dapat diperbaiki melalui workflow revisi. |
| `SELESAI` | Historical version terbaru telah disetujui Kepala dan seluruh PDF wajib untuk Source terkait berhasil dirender. |

## 5.3 State Diagram

```text
                +----------------+
                |     DRAFT      |
                +----------------+
                        |
                   FINALISASI
                        |
                        v
        +-------------------------------+
        |  MENUNGGU_PENGESAHAN          |
        +-------------------------------+
             |                    |
          RETURN                APPROVE
             |                    |
             v                    v
 +----------------------+      PDF GATE
 | PERLU_PERBAIKAN      |       |       \
 +----------------------+      FAIL     SUCCESS
             |                  |          |
       FINALISASI ULANG          |          v
             |                  |      SELESAI
             +------------------+
                  |
        MENUNGGU_PENGESAHAN
```

Jika PDF gagal pada approval, status tetap `MENUNGGU_PENGESAHAN`.

Special Unlock tidak digambarkan sebagai node state karena bukan business
state.

# 6. State Transition Matrix

| Current State | Action | Actor | Result | Gate |
|---|---|---|---|---|
| — | Create Outreach | PETUGAS | `DRAFT` | authentication + create permission |
| `DRAFT` | Save Draft | PETUGAS | tetap `DRAFT` | ownership + save validation |
| `DRAFT` | Autosave | PETUGAS/session | tetap `DRAFT` | dirty state + version check |
| `DRAFT` | Change Source/Type | PETUGAS | tetap `DRAFT` | belum Finalisasi + confirmation + impact handling |
| `DRAFT` | Finalisasi | PETUGAS | `MENUNGGU_PENGESAHAN` | full validation + required forms/attachments/signatures + version check |
| `MENUNGGU_PENGESAHAN` | Return | KEPALA | `PERLU_PERBAIKAN` | scope + review permission + note wajib |
| `MENUNGGU_PENGESAHAN` | Approve | KEPALA | `SELESAI` hanya jika seluruh PDF wajib berhasil dirender | authorization + version check + final validation + complete required-PDF gate |
| `MENUNGGU_PENGESAHAN` | Special Unlock | ADMIN | business state tetap; controlled exception mode | authority + reason + audit |
| `PERLU_PERBAIKAN` | Save Draft | PETUGAS | tetap `PERLU_PERBAIKAN` | ownership + editable state |
| `PERLU_PERBAIKAN` | Autosave | PETUGAS/session | tetap `PERLU_PERBAIKAN` | dirty state + version check |
| `PERLU_PERBAIKAN` | Finalisasi Ulang | PETUGAS | `MENUNGGU_PENGESAHAN` | full validation + new version |
| `SELESAI` | Normal edit | PETUGAS | ditolak | locked |
| `SELESAI` | Special Unlock | ADMIN | business state tetap `SELESAI`; controlled exception mode | authority + reason + audit |
| `SELESAI` / `MENUNGGU_PENGESAHAN` | Lock setelah Special Unlock | ADMIN | `PERLU_PERBAIKAN` jika masih membutuhkan pekerjaan Petugas/Warga | required follow-up exists |
| `SELESAI` / `MENUNGGU_PENGESAHAN` | Lock & Ajukan | ADMIN | `MENUNGGU_PENGESAHAN` | full validation + new version + version check |

Tidak ada transition langsung dari `DRAFT` ke `SELESAI`.

Tidak ada transition langsung dari `PERLU_PERBAIKAN` ke `SELESAI` tanpa
Finalisasi Ulang dan approval berikutnya.

Special Unlock tidak membuat business state baru.

`Lock & Ajukan untuk Pengesahan` merupakan submission action yang langsung
membuat historical version baru ketika berhasil.

# 7. Create Outreach Flow

## 7.1 Entry Condition

Petugas harus:

1. berhasil login;
2. memiliki role `PETUGAS`;
3. memiliki akun aktif.

## 7.2 Create Sequence

Saat membuat Outreach baru:

```text
Source Outreach
      ↓
Jenis Outreach
      ↓
Tanggal Outreach
      ↓
Mulai Pengumpulan Data
```

### Source Outreach

Pilihan:

- Baznas
- CSR
- Bangga Surabaya

Source menentukan struktur/alur formulir.

### Jenis Outreach

Pilihan:

- Rutilahu
- Alat Kesehatan
- Beasiswa
- Pendidikan (Kejar Paket)
- Bantuan Modal Usaha

Type menentukan jenis intervensi dan ditampilkan pada dokumen Outreach.

### Date

Tanggal Outreach menjadi bagian dari data Outreach.

## 7.3 Source / Type Change

Selama Outreach belum Finalisasi, Source dan Type dapat diubah melalui
mekanisme yang terkontrol.

Perubahan menggunakan confirmation dan impact handling apabila perubahan
berdampak terhadap:

- keberadaan form;
- data yang sudah diisi;
- signature;
- kebutuhan lampiran;
- workflow terkait.

Setelah perubahan, sistem melakukan loading/reload workflow agar konfigurasi
Source dan Type terbaru digunakan.

Detail Source Change dijelaskan pada Bagian 9 dan Type Change mengikuti prinsip
yang sama.

Setelah Finalisasi, Source dan Type tidak dapat diubah melalui normal editing.
Perubahannya dilakukan melalui Special Unlock.

## 7.4 Draft Code

Saat Outreach dibuat, sistem membuat `draft_code` internal.

Contoh pola:

```text
DRAFT-...
```

Nilai konkret generator dapat ditentukan implementasi selama tetap unik.

`outreach_number` belum dibuat pada tahap ini. `outreach_number` dibuat ketika
submission pertama berhasil melalui Finalisasi.

# 8. Data Collection Flow

## 8.1 Main Sequence

### Baznas / CSR

```text
1. Identitas Outreach
2. Data Klien
3. Keluarga
4. Rumah & Aset
5. Narasi
6. Mustahik
7. Kifayah
8. Lampiran
9. Tanda Tangan
10. Review
```

### Bangga Surabaya

```text
1. Identitas Outreach
2. Data Klien
3. Keluarga
4. Rumah & Aset
5. Narasi
6. Lampiran
7. Review
```

Step Mustahik dan Kifayah tidak digunakan pada Bangga Surabaya.

Step Tanda Tangan juga tidak ditampilkan pada Bangga Surabaya. Signature
Kepala diberikan pada tahap approval.

## 8.2 Step Navigation

Desktop menggunakan vertical stepper.

Mobile menggunakan indikator:

```text
Langkah X dari N
```

Petugas mengikuti alur berurutan. Draft tetap dapat dilanjutkan dan bagian
yang sudah diisi dapat ditinjau kembali.

Indikator langkah:

```text
● aktif
✓ selesai
! belum lengkap
○ belum disentuh
```

Completion step dinilai berdasarkan required fields pada step tersebut.

## 8.3 Save & Continue

Action `Simpan & Lanjutkan`:

1. melakukan validation pada step saat ini;
2. menyimpan data ke server;
3. memperbarui status indicator;
4. berpindah ke step berikutnya jika valid.

Draft incomplete tetap diperbolehkan secara umum.

## 8.4 Back Navigation

Petugas dapat kembali ke step sebelumnya selama Outreach masih editable.

Back navigation tidak mengubah status bisnis.

Save yang terjadi karena navigation menggunakan jalur service penyimpanan yang
sama dengan Save Draft.

# 9. Source-Dependent Workflow

## 9.1 Baznas

```text
Outreach
   ↓
Mustahik
   ↓
Kifayah
   ↓
Lampiran
   ↓
Tanda Tangan
   ↓
Review
```

Signature sebelum approval:

- Mustahik → Petugas Lapangan;
- Kifayah → Warga/Klien.

## 9.2 CSR

```text
Outreach
   ↓
Mustahik
   ↓
Kifayah
   ↓
Lampiran
   ↓
Tanda Tangan
   ↓
Review
```

Signature sebelum approval sama dengan Baznas.

## 9.3 Bangga Surabaya

```text
Outreach
   ↓
Lampiran
   ↓
Review
```

Tidak ada Mustahik, Kifayah, maupun signature Petugas/Warga sebelum approval.

Signature Kepala dilakukan pada tahap approval.

## 9.4 Source vs Type

```text
Source → menentukan struktur/alur formulir
Type   → menentukan jenis intervensi
```

Semua kombinasi Source × Type diperbolehkan.

## 9.5 Source Change Before Finalization

### Bangga Surabaya → Baznas/CSR

```text
Bangga Surabaya
      ↓
Ubah Source
      ↓
Konfirmasi
      ↓
Loading / reload
      ↓
Mustahik + Kifayah muncul
      ↓
Petugas melengkapi
```

### Baznas ↔ CSR

Mustahik dan Kifayah tetap digunakan. Data yang telah tersedia tidak
dihapus.

### Baznas/CSR → Bangga Surabaya

Perubahan bersifat destruktif terhadap form terkait.

```text
Ubah Source
↓
Confirmation destructive
↓
Bersihkan Mustahik + Kifayah
↓
Invalidasi signature terkait
↓
Loading / reload
↓
Workflow Bangga Surabaya
```

## 9.6 Source Change Through Special Unlock

Admin dapat mengubah Source melalui Special Unlock.

Jika Source baru membutuhkan Mustahik/Kifayah:

```text
Admin Special Unlock
↓
Admin ubah Source
↓
Admin Lock
↓
PERLU_PERBAIKAN
↓
Petugas melengkapi form/signature yang dibutuhkan
↓
Finalisasi Ulang
↓
MENUNGGU_PENGESAHAN
```

Jika Source baru tidak membutuhkan pekerjaan lanjutan, Admin menggunakan
`Lock & Ajukan untuk Pengesahan`.

## 9.7 Type Change

Type dapat diubah sebelum Finalisasi melalui confirmation dan impact handling.
Jika perubahan Type tidak memiliki konsekuensi destruktif, data yang kompatibel
tetap dipertahankan. Jika perubahan mempunyai konsekuensi destruktif, sistem
harus menjelaskan bagian workflow/data yang terdampak sebelum perubahan dilakukan.

Setelah Finalisasi, Type hanya dapat diubah melalui Special Unlock.

Jika Type baru membutuhkan pengisian ulang, signature ulang, atau perubahan
workflow, hasil koreksi mengikuti alur yang sesuai sebelum submission berikutnya.

Detail attachment wajib per Type mengikuti hasil validasi Kelurahan.

# 10. Outreach Save Rules

## 10.1 Save Draft

Save Draft dapat dilakukan sebelum seluruh data lengkap.

Hasil:

```text
persist current state
status remains current editable business state
```

Save Draft tidak membuat historical `outreach_versions`.

## 10.2 Autosave

Autosave berjalan ketika:

- form berada dalam kondisi dirty;
- interval sekitar 60 detik tercapai;
- atau terjadi save trigger dari navigation/action yang relevan.

Autosave menggunakan backend/service yang sama dengan Save Draft.

Autosave tidak membuat historical version baru dan tidak wajib membuat History
event pada setiap autosave.

## 10.3 Save State

State autosave:

```text
IDLE
SAVING
SAVED
FAILED
CONFLICT
CONNECTION_LOST
```

UI harus memberikan feedback yang jelas ketika state berubah.

# 11. Connection Loss & Recovery

Baseline bukan offline-first.

Ketika koneksi terputus:

```text
connection lost
    ↓
warning UI
    ↓
local unsaved state tidak dijamin pulih
```

Setelah koneksi kembali:

- sistem dapat memuat state terakhir yang berhasil disimpan;
- perubahan setelah save terakhir tidak dijamin tersedia;
- pengguna diberi status bahwa data yang benar-benar tersimpan berada di server.

Tidak ada IndexedDB merge, background synchronization, atau local-first
conflict resolution pada v1.

# 12. Concurrent Editing

## 12.1 Same Owner

Petugas yang sama dapat membuka Outreach yang sama pada beberapa tab/session.

Sistem tidak menggunakan hard lock sebagai mekanisme utama.

## 12.2 Server as Source of Truth

Server dan database merupakan source of truth.

## 12.3 Optimistic Concurrency

`edit_version` pada Outreach digunakan untuk mendeteksi stale write.

Pola:

```text
Tab A reads version 5
Tab B reads version 5

Tab A saves → version 6

Tab B tries save using version 5
       ↓
CONFLICT
       ↓
Tidak menimpa data version 6
```

## 12.4 Conflict Behavior

Ketika conflict:

- data baru di server tidak boleh diam-diam ditimpa;
- request stale dianggap gagal/conflict;
- tidak ada automatic merge pada v1;
- pengguna perlu memuat ulang/menyesuaikan perubahan berdasarkan state terbaru.

`edit_version` berlaku pada Save Draft, Autosave, Revision, dan controlled
editing administratif yang memerlukan update terhadap record.

# 13. Mustahik Workflow

## 13.1 Applicability

Mustahik digunakan hanya pada:

```text
Source = Baznas
atau
Source = CSR
```

## 13.2 Completion

Form Mustahik memiliki 25 pertanyaan berdasarkan source form.

Petugas memilih jawaban.

Context dari Outreach boleh ditampilkan untuk membantu proses survey, tetapi:

- context bukan answer;
- context bukan recommendation;
- context tidak mencentang pilihan otomatis;
- context tidak menghasilkan score otomatis.

## 13.3 Multiplicity

Baseline pertanyaan Mustahik menggunakan single choice.

Pengecualian yang masih `PENDING VALIDATION`:

- Tanggungan;
- Hutang.

## 13.4 Signature

Form Mustahik ditandatangani oleh Petugas Lapangan melalui signature pad.

## 13.5 Finalization Gate

Finalisasi tidak boleh berhasil jika requirement Mustahik yang diwajibkan
untuk Source terkait belum terpenuhi.

# 14. Kifayah Workflow

## 14.1 Applicability

Kifayah digunakan hanya pada:

```text
Source = Baznas
atau
Source = CSR
```

## 14.2 Input Ownership

Kifayah diisi oleh Petugas.

Data Outreach dapat digunakan sebagai contextual information untuk mendukung
pengisian, tetapi bukan automatic answer source.

## 14.3 Main Content

Workflow Kifayah mencakup:

- informasi Mustahik;
- sumber pendapatan;
- pengeluaran keluarga;
- pengeluaran tambahan;
- pendidikan;
- status tempat tinggal;
- penerimaan bantuan pemerintah;
- kepemilikan aset;
- beban keuangan;
- pernyataan tanggung jawab;
- signature Warga/Klien.

Tidak ada field `statement_acknowledged`.

Pernyataan tanggung jawab tetap ditampilkan pada dokumen. Signature
Warga/Klien sendiri menjadi tindakan persetujuan/acknowledgement.

## 14.4 Zero-Value Rule

Semantik nilai Kifayah yang berlaku:

- `0` berarti tidak ada pengeluaran / tidak berlaku untuk kategori tersebut;
- `NULL` berarti field belum diisi;
- field kosong tidak boleh otomatis dikonversi menjadi `0`;
- nilai numerik harus non-negatif; input negatif atau nonnumerik ditolak.

Tidak ada auto-fill nilai `0`. Tahun yang dicetak pada PDF Kifayah berasal dari tanggal Outreach yang tersimpan, bukan tanggal pembuatan atau pengunduhan PDF.

## 14.5 Signature

Kifayah ditandatangani oleh Warga/Klien melalui signature pad.

# 15. Attachment Workflow

## 15.1 Upload Entry

Lampiran diunggah sebagai bagian dari Outreach dan dapat digunakan kembali
pada form/proses terkait.

## 15.2 Baseline Required Attachments

Empat lampiran dasar wajib tersedia untuk Finalisasi:

- KTP;
- KK;
- Foto Klien;
- Foto Rumah.

Draft tetap boleh disimpan meskipun lampiran dasar belum lengkap. Finalisasi ditolak jika salah satu lampiran dasar yang diwajibkan belum tersedia. Lampiran tambahan dapat disediakan sesuai kebutuhan; persyaratan wajib tambahan per Jenis Outreach masih `PENDING VALIDATION` sampai dikonfirmasi dengan Kelurahan.

## 15.3 Upload Pipeline

```text
Select / Capture
      ↓
Client Validation
      ↓
Server Validation
      ↓
Content Hash / Duplicate Check
      ↓
Optimize / Compress
      ↓
Private Storage
      ↓
Register File Metadata
      ↓
Attach to Outreach
```

## 15.4 Device Input

Petugas dapat menggunakan:

- camera;
- gallery;
- file/device picker.

## 15.5 Compression

Baseline teknis foto:

- sisi terpanjang sekitar **2.000 px**;
- JPEG quality sekitar **80**;
- tidak melakukan upscale;
- target sekitar 0.5–1.5 MB bila memungkinkan;
- keterbacaan dokumen menjadi prioritas;
- original tidak disimpan permanen setelah hasil optimasi berhasil.

Parameter tersebut merupakan technical baseline internal dan dapat disesuaikan
melalui prototype testing.

PDF dioptimasi seperlunya tanpa merusak keterbacaan/dokumen.

## 15.6 Duplicate Prevention

File identik tidak boleh didaftarkan dua kali pada Outreach yang sama.

Pencegahan menggunakan:

- reference uniqueness;
- content hash;
- pemeriksaan aplikasi/transaksi.

## 15.7 Editable Window

Sebelum Finalisasi:

```text
add
replace
delete
```

Setelah Finalisasi:

```text
locked
```

Perubahan setelah lock menggunakan Return atau Special Unlock sesuai kondisi.

## 15.8 Historical Attachments

Perubahan attachment aktif tidak boleh mengubah attachment yang direferensikan
historical version.

Historical version menyimpan snapshot relasi/metadata attachment melalui
`outreach_version_attachments`.

Perubahan attachment saja setelah lock tidak mewajibkan signature ulang.

# 16. Signature Workflow

## 16.1 Signature Parties

```text
Baznas / CSR
Petugas Lapangan → Mustahik
Warga / Klien    → Kifayah
Kepala Kelurahan → Approval Outreach

Bangga Surabaya
Kepala Kelurahan → Approval Outreach
```

## 16.2 Petugas / Warga Signature

Petugas dan Warga menggunakan signature pad langsung pada perangkat.

Signature pad dirancang cukup besar untuk penggunaan jari pada perangkat
mobile.

Tidak ada manual upload signature untuk Petugas/Warga pada alur normal.

## 16.3 Conditional Signature Step

Untuk Baznas/CSR, Tanda Tangan menjadi bagian dari Data Collection.

Untuk Bangga Surabaya, Tanda Tangan **tidak menjadi step Data Collection**.
Tidak ada signature Petugas/Warga sebelum approval. Signature Kepala dilakukan
pada tahap approval.

## 16.4 Signature Revision

Sebelum Finalisasi, signature dapat di-clear dan dilakukan ulang.

Setelah Finalisasi, signature yang telah digunakan pada historical version
bersifat immutable.

Jika perubahan data form membuat signature pihak terkait tidak lagi mewakili
data yang dia tanda tangani, signature tersebut harus dibuat ulang.

Perubahan attachment saja tidak mewajibkan signature ulang.

## 16.5 Head Signature

Kepala menggunakan stored signature template yang dikelola Admin.

Approval menyimpan referensi template yang digunakan agar historical document
dapat direkonstruksi dengan template yang tepat.

## 16.6 Stamp

Template stamp disiapkan secara versioned.

Penggunaan stempel digital pada PDF masih `PENDING VALIDATION` terhadap kebijakan
Kelurahan.

# 17. Review Workflow

## 17.1 Petugas Review

Sebelum Finalisasi, Petugas membuka Review.

Review menampilkan:

- status kelengkapan per section;
- incomplete required fields;
- attachment status;
- signature status sesuai Source;
- data ringkas;
- populated Outreach preview;
- relevant Mustahik/Kifayah result jika digunakan.

Bangga Surabaya tidak memiliki pre-approval signature Petugas/Warga sehingga
signature tersebut tidak menjadi blocker pada Review.

## 17.2 Review Navigation

Section yang incomplete dapat dipilih untuk kembali ke area terkait.

Review tidak secara otomatis memperbaiki atau mengisi data.

## 17.3 Preview

Preview menggunakan struktur logical document yang sama dengan PDF.

Preview berada di browser menggunakan HTML/CSS.

Preview tidak diwajibkan pixel-perfect identik dengan PDF renderer.

# 18. Finalization Workflow

## 18.1 Entry Condition

Untuk Finalisasi/Finalisasi Ulang oleh Petugas, Outreach harus:

- berstatus `DRAFT` atau `PERLU_PERBAIKAN`;
- dimiliki Petugas yang melakukan action;
- memenuhi full validation;
- memenuhi required related forms;
- memiliki empat lampiran dasar: KTP, KK, Foto Klien, dan Foto Rumah;
- memenuhi lampiran tambahan yang telah ditetapkan sebagai wajib setelah validasi resmi;
- memenuhi required signatures sesuai Source;
- lolos version/conflict check.

Draft boleh disimpan saat lampiran belum lengkap. Jika file wajib tidak dapat diproses, tampilkan error yang jelas dan jangan diam-diam menghilangkan file tersebut dari data atau output.

## 18.2 Finalization First Submission

Urutan konseptual:

```text
Petugas clicks Finalisasi
        ↓
authorization check
        ↓
full validation
        ↓
attachment check
        ↓
signature check
        ↓
create outreach_number if first submission
        ↓
create historical version
        ↓
lock current Outreach workflow
        ↓
status = MENUNGGU_PENGESAHAN
        ↓
History + Notification
```

`outreach_number` dibuat pada Finalisasi pertama, bukan pada Create Draft.

## 18.3 Administrative Submission After Special Unlock

Apabila hasil Special Unlock tidak memerlukan pekerjaan lanjutan
Petugas/Warga, Admin menggunakan `Lock & Ajukan untuk Pengesahan`.

Action tersebut:

1. melakukan authorization dan version check;
2. melakukan final validation;
3. membuat historical version baru;
4. mengunci hasil koreksi;
5. mengubah status bisnis menjadi `MENUNGGU_PENGESAHAN`;
6. membuat history yang relevan.

Admin tidak melakukan approval.

Jika hasil Special Unlock masih memerlukan pekerjaan Petugas/Warga, Admin
menggunakan `Lock` dan mengembalikan workflow ke `PERLU_PERBAIKAN`.

## 18.4 Finalization Must Not Partially Succeed

Business action penting menggunakan transaction boundary yang sesuai.

Jika proses gagal sebelum commit:

- status tidak boleh berubah seolah-olah Finalisasi berhasil;
- historical version tidak boleh tertinggal tanpa alasan yang sah;
- notification tidak boleh menjadi satu-satunya indikator keberhasilan.

# 19. Historical Version Creation

## 19.1 Version Trigger

Historical version dibuat ketika:

- Finalisasi pertama oleh Petugas;
- Finalisasi Ulang setelah Return;
- `Lock & Ajukan untuk Pengesahan` oleh Admin setelah Special Unlock;
- submission berikutnya oleh Petugas setelah Admin melakukan `Lock` dan
  workflow dikembalikan ke Petugas.

Special Unlock atau Save biasa tidak otomatis membuat historical version.

## 19.2 No Version on Draft Save

Hal berikut tidak membuat historical version:

- Save Draft;
- Autosave;
- navigation save;
- perubahan field biasa selama status editable;
- controlled editing sebelum submission resmi selesai.

## 19.3 Version Snapshot

Historical version berisi snapshot domain-oriented minimal:

```text
meta
client
family_income
expenses
house
assets
narrative
mustahik (jika relevan)
kifayah (jika relevan)
```

Byte file tidak dimasukkan ke snapshot JSON.

Attachment ditangani melalui snapshot relationship reference.

## 19.4 Version Immutability

Version historis tidak diubah setelah dibuat.

Tidak ada status `SUPERSEDED` pada v1.

Ketika historical version baru dibuat sementara version sebelumnya masih
`PENDING_APPROVAL`, version lama tetap immutable dan status version-nya tidak
diubah. Version terbaru menjadi latest/current submission yang digunakan oleh
workflow aktif.

# 20. Approval Queue Workflow

## 20.1 Entry

Setelah Finalisasi atau `Lock & Ajukan` berhasil:

```text
status = MENUNGGU_PENGESAHAN
```

Outreach masuk antrean review Kepala dalam scope Kelurahan.

## 20.2 Notification

Kepala menerima in-app notification tipe:

```text
OUTREACH_FINALIZED
```

Notification bukan source of truth. Queue ditentukan dari status dan
submission/version terbaru.

## 20.3 Review Scope

Kepala dapat melihat:

- data Outreach;
- Mustahik jika berlaku;
- Kifayah jika berlaku;
- lampiran;
- signature status;
- preview dokumen;
- history yang relevan.

Kepala tidak mengubah data secara langsung pada approval normal.

Jika diperlukan koreksi, Kepala dapat memilih Return atau menggunakan jalur
Special Unlock melalui Admin.

# 21. Return Workflow

## 21.1 Return Action

Kepala dapat memilih:

```text
KEMBALIKAN
```

ketika submission perlu diperbaiki.

## 21.2 Return Note

Reason/note wajib diisi.

Return tanpa alasan tidak valid.

## 21.3 Transition

```text
MENUNGGU_PENGESAHAN
        ↓
      RETURN
        ↓
PERLU_PERBAIKAN
```

## 21.4 Version Impact

Historical version yang dikembalikan tetap tersimpan dan immutable.

Return tidak menghapus historical version.

## 21.5 History

History mencatat:

- aktor Kepala;
- waktu;
- event Return;
- reason/note;
- version terkait.

## 21.6 Notification

Petugas pemilik menerima:

```text
OUTREACH_RETURNED
```

Notifikasi berisi informasi yang cukup untuk mengetahui bahwa Outreach perlu
diperbaiki dan merujuk pada alasan Return.

# 22. Revision Workflow

## 22.1 Entry

Outreach berada pada:

```text
PERLU_PERBAIKAN
```

## 22.2 Petugas Editing

Petugas pemilik dapat memperbaiki:

- data Outreach;
- Mustahik;
- Kifayah;
- lampiran;
- signature yang harus dibuat ulang akibat perubahan data form.

Normal editing tetap berada pada record Outreach yang sama.

## 22.3 Signature Revision Rule

Jika perubahan menyentuh data form yang terkait signature:

```text
data form berubah
↓
signature pihak terkait wajib dibuat ulang
```

Jika perubahan hanya attachment:

```text
attachment changed only
↓
signature tidak perlu dibuat ulang
```

## 22.4 Autosave During Revision

Autosave tetap aktif pada kondisi editable.

Perubahan autosave tidak mengubah historical version sebelumnya.

## 22.5 Refinalisasi

Setelah perbaikan lengkap:

```text
PERLU_PERBAIKAN
       ↓
FINALISASI ULANG
       ↓
create new version
       ↓
MENUNGGU_PENGESAHAN
```

## 22.6 Historical Result

Contoh:

```text
OUT-2026-00001
   |
   +-- Version 1 → RETURNED
   |
   +-- Version 2 → PENDING_APPROVAL
```

Version 1 tetap dapat direkonstruksi.

# 23. Approve Workflow

## 23.1 Entry Condition

Kepala harus:

- authenticated;
- role `KEPALA`;
- memiliki scope pada Outreach;
- melihat latest version yang masih pending;
- lolos version/concurrency check.

## 23.2 Approval Action

Action:

```text
SAHKAN / APPROVE
```

## 23.3 PDF Gate

Approval harus memvalidasi dan merender **seluruh PDF wajib** sesuai Source sebelum approval dapat di-commit.

PDF wajib:

- **Baznas / CSR:** (1) Outreach PDF dengan halaman lampiran yang diwajibkan ditempatkan setelah halaman utama Outreach dalam file PDF yang sama; (2) Mustahik PDF terpisah; (3) Kifayah PDF terpisah.
- **Bangga Surabaya:** Outreach PDF dengan halaman lampiran yang diwajibkan setelah halaman utama Outreach. Mustahik dan Kifayah tidak dibuat untuk Source ini.

```text
Approve request
      ↓
authorization / version check
      ↓
final validation
      ↓
render every required PDF for Source
      ↓
all required PDFs succeeded?
       |              |
      NO             YES
       |              |
       v              v
approval fails    commit approval
status remains    + status SELESAI
MENUNGGU_PENGESAHAN
```

Halaman lampiran berada setelah halaman form Outreach dan tetap menjadi bagian dari Outreach PDF; lampiran tidak boleh dihilangkan dari output wajib.

## 23.4 Failure Rule

Jika salah satu PDF wajib gagal dibuat:

- action approval gagal dan menampilkan alasan yang aman serta dapat ditindaklanjuti;
- approval tidak dianggap berhasil dan tidak boleh terjadi partial approval;
- status tetap `MENUNGGU_PENGESAHAN`;
- status tidak boleh menjadi `SELESAI`;
- notification `OUTREACH_COMPLETED` tidak dikirim;
- historical version dan referensi historis tidak boleh menjadi tidak konsisten.

Setelah penyebab kegagalan diperbaiki, Kepala dapat mencoba approval kembali.

## 23.5 Success Rule

Jika seluruh PDF wajib berhasil dirender dan transaction business action berhasil:

```text
outreach_approval = APPROVED
outreach status = SELESAI
```

Approval menyimpan:

- Kepala yang mengesahkan;
- decision;
- template signature Kepala yang digunakan;
- stamp template yang digunakan jika ada;
- waktu keputusan;
- version terkait.

# 24. Completion Workflow

## 24.1 SELESAI

`SELESAI` berarti dua hal telah terpenuhi secara bersama:

1. Kepala telah menyetujui historical version terbaru;
2. seluruh PDF wajib untuk Source terkait berhasil dirender.

## 24.2 Completion Notification

Setelah completion berhasil, Petugas pemilik menerima:

```text
OUTREACH_COMPLETED
```

## 24.3 Lock

Setelah `SELESAI`:

- Petugas tidak dapat mengedit melalui normal flow;
- attachment tidak dapat diubah melalui normal flow;
- signature historical tidak dapat diubah;
- approval immutable.

Koreksi khusus menggunakan Special Unlock.

Special Unlock pada `SELESAI` mempertahankan business state `SELESAI` selama
exception edit mode aktif. Setelah koreksi diajukan sebagai submission baru,
workflow kembali ke `MENUNGGU_PENGESAHAN`.

# 25. Special Unlock Workflow

## 25.1 Purpose

Special Unlock adalah mekanisme exception administratif untuk koreksi Outreach
yang tidak dapat diedit melalui normal flow.

Special Unlock dapat diterapkan pada Outreach yang berstatus:

- `MENUNGGU_PENGESAHAN`;
- `SELESAI`.

Special Unlock bukan business state.

## 25.2 Request dan Executor

Requester yang diizinkan:

- Petugas;
- Kepala Kelurahan.

Executor/editor:

- Admin.

Request bukan prasyarat. Admin dapat melakukan Special Unlock langsung sesuai
kewenangan administratif.

## 25.3 User Flow

```text
Request / kebutuhan koreksi
        ↓
Admin
        ↓
Special Unlock
        ↓
Controlled Editing
        ↓
Apakah masih perlu Petugas/Warga?
       /              \
     YES              NO
      ↓                ↓
    Lock          Lock & Ajukan
      ↓                ↓
PERLU_PERBAIKAN   New Version
      ↓                ↓
Petugas lanjut   MENUNGGU_PENGESAHAN
      ↓                ↓
Finalisasi Ulang    Kepala Review
      ↓                ↓
New Version       Approve
```

Admin melakukan seluruh perubahan saat Special Unlock aktif.

## 25.4 Full Controlled Editing

Pada `MENUNGGU_PENGESAHAN` maupun `SELESAI`, Admin dapat mengubah seluruh
data/form yang relevan dalam workflow.

Controlled editing tetap tunduk pada:

- authorization;
- version/conflict check;
- validation;
- reason;
- audit/history;
- historical integrity.

## 25.5 Lock

`Lock` tanpa `Ajukan` hanya digunakan apabila hasil koreksi masih membutuhkan
pekerjaan lanjutan Petugas/Warga.

Setelah Lock:

```text
PERLU_PERBAIKAN
```

dan proses dilanjutkan oleh Petugas.

## 25.6 Lock & Ajukan untuk Pengesahan

Jika submission sudah lengkap dan tidak membutuhkan pekerjaan lanjutan
Petugas/Warga, Admin **wajib** menggunakan:

```text
Lock & Ajukan untuk Pengesahan
```

Action ini langsung:

1. memvalidasi hasil koreksi;
2. membuat historical version baru;
3. mengunci hasil koreksi;
4. mengubah status menjadi `MENUNGGU_PENGESAHAN`.

Admin tidak melakukan approval.

## 25.7 Version Integrity

Perubahan saat Special Unlock tidak mengedit historical version lama.

Historical version baru hanya dibuat ketika hasil koreksi benar-benar
diajukan sebagai submission resmi.

## 25.8 History / Audit

Minimal dicatat:

- siapa yang melakukan unlock;
- waktu;
- alasan;
- Outreach/version terkait;
- perubahan penting setelah unlock.

Event minimum:

```text
SPECIAL_UNLOCKED
SPECIAL_UNLOCK_EDITED
```

## 25.9 Administrative Request Queue

Request Special Unlock dikelola melalui administrative request queue.

Tidak ada notification khusus `SPECIAL_UNLOCK_REQUESTED` pada v1.

Lifecycle request didefinisikan pada Bagian 31.

# 26. PDF Workflow

## 26.1 Preview

Preview sebelum finalisasi:

```text
Current Outreach Data
      ↓
Logical Document Template
      ↓
Browser HTML/CSS Preview
```

Sebelum approval, Kepala juga dapat melihat populated document preview dan lampiran.

## 26.2 Required Final PDFs

PDF wajib dibuat berdasarkan Source dan submission/historical version yang berlaku.

### Baznas / CSR

1. **Outreach PDF** — halaman utama form Outreach, dilanjutkan halaman lampiran wajib.
2. **Mustahik PDF** — file terpisah.
3. **Kifayah PDF** — file terpisah.

### Bangga Surabaya

1. **Outreach PDF** — halaman utama form Outreach, dilanjutkan halaman lampiran wajib.

Mustahik dan Kifayah tidak dibuat untuk Bangga Surabaya. Halaman lampiran merupakan bagian dari Outreach PDF, tetapi dipisahkan secara visual dari halaman form utama. Lampiran yang gagal diproses tidak boleh diam-diam dihilangkan.

## 26.3 Approval Gate and Generation Failure

Saat Kepala melakukan approval, sistem harus berhasil merender semua PDF yang diwajibkan untuk Source tersebut. Jika satu saja gagal:

- action approval gagal dengan pesan error yang jelas dan aman;
- status tetap `MENUNGGU_PENGESAHAN`;
- approval tidak di-commit, status tidak menjadi `SELESAI`, dan `OUTREACH_COMPLETED` tidak dikirim;
- Kepala dapat mencoba kembali setelah masalah diperbaiki.

Paket wajib harus diperiksa secara lengkap, bukan hanya satu PDF atau satu output parsial.

## 26.4 On-Demand Retrieval and Historical Regeneration

PDF tidak menjadi source of truth dan tidak harus disimpan permanen sebagai file arsip aplikasi pada baseline ini. PDF dapat dibuat/diambil saat diperlukan dari:

```text
Historical Outreach Version
Historical Attachment References
Document Template Version
Relevant Signature / Approval / Stamp References
```

Untuk approval, render seluruh PDF wajib merupakan bagian dari gate. Pengambilan atau regenerasi berikutnya harus memakai historical version dan referensi yang benar. Kegagalan saat pengambilan/regen harus menampilkan error yang jelas, bukan paket parsial yang terlihat lengkap.

## 26.5 Data Correction and Template Change

Perubahan data form yang memengaruhi attestation harus diikuti refresh signature pihak terkait dan alur pengesahan yang berlaku sebelum PDF baru dianggap sebagai dokumen resmi. Perubahan attachment saja tidak otomatis mewajibkan tanda tangan ulang. Historical version, approval lama, signature, dan referensi lampiran harus tetap dipertahankan.

Ketika template baru aktif:

- dokumen lama tidak otomatis menggunakan template baru;
- historical document tetap menggunakan template version yang benar;
- submission yang disahkan setelah template baru aktif dapat menggunakan template baru.

Tahun pada PDF Kifayah harus berasal dari tanggal Outreach yang tersimpan, bukan tanggal PDF dibuat atau diunduh.

# 27. E-Surat / Archive Workflow

## 27.1 Baseline Boundary

Tidak ada integrasi API langsung dengan E-Surat pada v1.

Alur:

```text
PDF Final
   ↓
Download / Retrieve
   ↓
Upload manual ke E-Surat / E-Arsip
```

## 27.2 Archive Record

Aplikasi menyediakan record referensi untuk proses archive:

```text
archive_records
```

Record dapat mencatat:

- external system;
- archive reference jika tersedia;
- archived_at;
- verified_at jika tersedia.

## 27.3 Pending Validation

Masih perlu dikonfirmasi:

- siapa yang mengunggah ke E-Surat;
- apakah sistem perlu menyimpan nomor/reference archive;
- bagaimana verifikasi dokumen dianggap telah benar-benar terarsip.

# 28. History Workflow

## 28.1 Purpose

History merupakan timeline bisnis Outreach.

## 28.2 Important Events

Event baseline:

```text
CREATED
SECTION_SAVED
FINALIZED
RETURNED
REFINALIZED
APPROVED
SPECIAL_UNLOCKED
SPECIAL_UNLOCK_EDITED
```

`SECTION_SAVED` digunakan untuk penyimpanan manual/important save yang dianggap
relevan untuk timeline, bukan setiap autosave.

## 28.3 History Rules

History:

- append-only;
- tidak diedit secara normal;
- tidak dihapus secara normal;
- menampilkan actor dan timestamp;
- menyimpan reason Return/Special Unlock;
- dapat menghubungkan event ke version/approval;
- tidak perlu mencatat setiap autosave;
- tidak menjadikan perubahan status request sebagai business event utama.

Request Special Unlock dapat direferensikan ketika relevan.

## 28.4 Sensitive Data

Timeline UI tidak boleh mengekspos data sensitif lebih banyak daripada yang
diperlukan untuk memahami event.

# 29. Audit Workflow

## 29.1 Separation

Audit dipisahkan dari History.

```text
History = business timeline
Audit   = administrative / global / sensitive audit trail
```

## 29.2 Important Audit Actions

Contoh:

- create/deactivate user;
- role/status changes;
- signature template activation;
- stamp template activation;
- Special Unlock;
- perubahan Source/Type melalui mekanisme administratif;
- pemrosesan Request Special Unlock;
- tindakan administratif sensitif lainnya.

## 29.3 Audit Rules

Audit bersifat:

- append-only;
- tidak menjadi UI timeline bisnis utama;
- tidak menggunakan polymorphic FK sebagai baseline schema;
- mencatat actor, waktu, action, resource/context, dan reason bila relevan.

Audit lebih detail daripada History, tetapi tidak harus mencatat setiap keystroke
atau setiap autosave.

# 30. Notification Workflow

## 30.1 Channel

Baseline notification hanya:

```text
IN-APP
```

Tidak ada email/SMS/push external pada v1.

## 30.2 Notification Types

```text
OUTREACH_FINALIZED
OUTREACH_RETURNED
OUTREACH_COMPLETED
```

Tidak ada notification khusus untuk Request Special Unlock pada v1.

## 30.3 Trigger Mapping

| Business Event | Recipient | Notification |
|---|---|---|
| Finalisasi / submission berhasil | Kepala sesuai scope | `OUTREACH_FINALIZED` |
| Return | Petugas pemilik | `OUTREACH_RETURNED` |
| Approval + seluruh PDF wajib berhasil | Petugas pemilik | `OUTREACH_COMPLETED` |

## 30.4 Notification Retention

Setiap notification memiliki masa operasional 30 hari sejak `created_at`.

Cleanup hanya menghapus record yang sudah expired.

Penghapusan notification tidak menghapus:

- Outreach;
- History;
- Audit;
- Approval;
- historical version;
- files.

## 30.5 Delivery Failure

Jika notification gagal disimpan/dikirim dalam mekanisme in-app:

- business action yang sudah berhasil tidak di-roll back hanya karena notification gagal;
- user tetap dapat mengetahui status dari sumber bisnis utama.

# 31. Administrative User Workflow

## 31.1 Account Lifecycle

Akun user:

```text
ACTIVE
  ↕
INACTIVE
```

Deactivation tidak menghapus histori user.

## 31.2 Head Constraint

Pada satu waktu hanya ada satu Kepala Kelurahan aktif.

Enforcement dilakukan di service/transaction boundary.

## 31.3 Head Change with Pending Queue

Penanganan antrean ketika terjadi pergantian Kepala masih:

```text
PENDING VALIDATION
```

Workflow teknis harus menjaga agar approval tidak dapat dilakukan oleh actor
yang tidak berwenang.

## 31.4 Request Special Unlock Lifecycle

Request Special Unlock memiliki lifecycle administratif terpisah dari
business state Outreach:

```text
PENDING
  ├── CANCELLED
  └── PROCESSING
         ├── COMPLETED
         └── REJECTED
```

Aturan:

- `CANCELLED` hanya dapat dilakukan saat request masih `PENDING`;
- setelah Admin memulai `PROCESSING`, requester tidak dapat membatalkan request;
- `PROCESSING` harus berakhir sebagai `COMPLETED` atau `REJECTED`.

# 32. Workflow Permission Boundary

Workflow mengikuti pola:

```text
LOGIN
  ↓
ROLE
  ↓
OWNERSHIP / SCOPE
  ↓
STATUS
  ↓
ACTION PERMISSION
  ↓
RESOURCE PERMISSION
  ↓
HISTORY
```

## 32.1 PETUGAS

Normal flow hanya untuk Outreach miliknya.

Petugas tidak dapat menggunakan Search, direct URL, atau mekanisme lain untuk
melewati ownership/status gate.

## 32.2 KEPALA

Kepala dapat melihat lintas Petugas dalam scope Kelurahan yang sah.

Kepala hanya dapat melakukan approval terhadap latest submission yang masih
pending.

## 32.3 ADMIN

Admin memiliki administrative scope dan Special Unlock authority.

Admin dapat melakukan controlled editing sesuai workflow, tetapi tidak dapat
melakukan approval.

## 32.4 Search Does Not Bypass Workflow

Search dan filtering tidak boleh dipakai untuk melewati ownership, scope,
status, atau action permission.

## 32.5 Direct URL Does Not Bypass Workflow

Akses langsung ke URL/resource tidak boleh mengabaikan authorization,
resource permission, version check, dan state gate.

# 33. Transaction Boundary

## 33.1 Actions Requiring Strong Consistency

Business action berikut membutuhkan consistency tinggi:

- Finalisasi;
- Finalisasi Ulang;
- Return;
- Approve;
- `Lock & Ajukan untuk Pengesahan`;
- status transition;
- creation of historical version;
- Special Unlock;
- important template activation;
- processing Request Special Unlock yang mengubah state request.

## 33.2 General Pattern

```text
Authorize
  ↓
Validate
  ↓
Check current state/version
  ↓
Perform business changes
  ↓
Write historical/audit references as required
  ↓
Commit
```

## 33.3 File Side Effects

File processing merupakan side effect yang perlu dikendalikan agar tidak
meninggalkan reference yang mengarah ke file yang gagal dibuat.

Implementasi dapat menggunakan temporary file/cleanup selama hasil akhirnya
mempertahankan konsistensi DB ↔ filesystem.

Approval tidak boleh di-commit sebagai berhasil apabila PDF gate gagal.

# 34. Double Submit Protection

Action seperti:

- Finalisasi;
- Finalisasi Ulang;
- Return;
- Approve;
- Special Unlock;
- Lock & Ajukan;
- Request Special Unlock;

harus dilindungi dari double submit.

Proteksi minimal:

- disabled processing state pada UI;
- server-side state/version check;
- transaction/unique constraints yang relevan;
- idempotency strategy bila diperlukan.

Double click tidak boleh menghasilkan:

- dua approval untuk version yang sama;
- dua historical version yang identik karena satu submit;
- dua request Special Unlock yang tidak semestinya untuk satu action.

# 35. Error State Behavior

Workflow mengenal state UI berikut:

```text
Loading
Empty
Error
Success
Warning
Locked / ReadOnly
Revision
Access Denied
Conflict
Processing
```

## 35.1 Validation Error

User tetap pada konteks action dan diberi field/section yang harus diperbaiki.

## 35.2 Access Denied

Action ditolak tanpa membocorkan resource yang tidak boleh diketahui user.

## 35.3 Locked

User diberi penjelasan bahwa resource berada pada state yang tidak editable.

## 35.4 Conflict

User diberi tahu bahwa data telah berubah pada session/tab lain dan request
tidak dapat menimpa state baru secara diam-diam.

## 35.5 Special Unlock Request Error

Jika request ditolak:

- status menjadi `REJECTED`;
- requester dapat melihat status dan alasan yang aman untuk ditampilkan;
- tidak ada perubahan business state Outreach hanya karena request ditolak.

## 35.6 PDF Error

Kegagalan salah satu PDF wajib saat approval harus menjelaskan bahwa proses approval belum selesai dan memberikan informasi error yang dapat ditindaklanjuti sejauh aman untuk ditampilkan. Status tetap `MENUNGGU_PENGESAHAN` dan `OUTREACH_COMPLETED` tidak dikirim. PDF yang gagal dibuat pada saat pengambilan/regen setelah approval juga harus menampilkan error yang jelas; jangan menampilkan hasil parsial seolah-olah paket lengkap berhasil.

# 36. Lifecycle Rules

## 36.1 Normal Edit Lifecycle

```text
DRAFT
   ↓
edit/save/autosave
   ↓
DRAFT
```

## 36.2 Submission Lifecycle

```text
DRAFT
   ↓
FINALISASI
   ↓
MENUNGGU_PENGESAHAN
```

## 36.3 Return Lifecycle

```text
MENUNGGU_PENGESAHAN
   ↓
RETURN
   ↓
PERLU_PERBAIKAN
```

## 36.4 Revision Lifecycle

```text
PERLU_PERBAIKAN
   ↓
edit/save/autosave
   ↓
FINALISASI ULANG
   ↓
MENUNGGU_PENGESAHAN
```

## 36.5 Completion Lifecycle

```text
MENUNGGU_PENGESAHAN
   ↓
APPROVE
   ↓
PDF SUCCESS
   ↓
SELESAI
```

## 36.6 Special Unlock During Review

```text
MENUNGGU_PENGESAHAN
   +
SPECIAL UNLOCK
   ↓
Admin controlled edit
   ↓
 ┌────────────────────────────┐
 │ Masih perlu Petugas/Warga? │
 └──────────────┬─────────────┘
          YES   │   NO
           ↓   │   ↓
         Lock  │ Lock & Ajukan
           ↓   │
PERLU_PERBAIKAN │
           ↓   │
     Petugas    │ New Version
     lanjut     │
           ↓   │
   Finalisasi   │ MENUNGGU_PENGESAHAN
   Ulang        │
           ↓   │
     New Version
```

## 36.7 Special Unlock After Completion

```text
SELESAI
   +
SPECIAL UNLOCK
   ↓
Admin controlled edit
   ↓
Lock
atau
Lock & Ajukan
   ↓
submission mengikuti workflow baru
```

Business state tetap `SELESAI` selama exception edit mode aktif.

Setelah submission baru diajukan, status menjadi `MENUNGGU_PENGESAHAN`.

# 37. Archive & Retention Interaction

## 37.1 Notification Retention

Notification: 30 hari per record.

## 37.2 Outreach Retention

Retensi keseluruhan Outreach dan historical files belum ditentukan oleh
Kelurahan.

## 37.3 No Premature Deletion

Sistem tidak melakukan automatic deletion Outreach/historical file hanya karena
status berubah.

Physical file hanya dapat dipurge ketika:

- tidak memiliki current reference;
- tidak memiliki historical reference;
- retention/policy mengizinkan.

Retention policy final mengikuti hasil validasi operasional Kelurahan.

# 38. Operational Scheduler Workflow

Scheduler digunakan untuk tugas operasional terjadwal yang diperlukan oleh
sistem.

Detail konfigurasi scheduler/cron merupakan concern engineering/deployment dan
diatur pada `docs/engineering/deployment.md`.

Workflow-level maintenance dapat mencakup:

- cleanup notification yang sudah >30 hari;
- orphan detection;
- health checks sederhana;
- maintenance tasks lain yang telah disetujui.

Scheduler tidak boleh melakukan aggressive delete tanpa dasar
retention/policy.

# 39. Backup & Recovery Interaction

Workflow production bergantung pada dua kelompok data:

```text
Database
+
Private Files
```

Backup harus mencakup keduanya sesuai baseline deployment.

Restore test dilakukan sebelum handover.

Detail owner, frequency, media, hosting target, dan operational backup policy
mengikuti hasil validasi Kelurahan dan dokumentasi deployment.

Backup bukan pengganti historical version atau archive workflow.

# 40. Workflow Invariants

Invariant berikut wajib dipertahankan selama implementasi:

1. `DRAFT` tidak dapat diperlakukan sebagai approved.
2. `MENUNGGU_PENGESAHAN` hanya dapat dicapai melalui Finalisasi Petugas atau
   `Lock & Ajukan` Admin yang valid.
3. `PERLU_PERBAIKAN` harus berasal dari Return Kepala atau hasil Special Unlock
   yang memang masih membutuhkan pekerjaan lanjutan Petugas/Warga.
4. `SELESAI` hanya dicapai setelah approval dan seluruh PDF wajib untuk Source terkait berhasil dirender.
5. Outreach PDF harus memuat halaman lampiran wajib setelah halaman utama form Outreach.
6. Baznas/CSR menghasilkan tiga PDF terpisah: Outreach, Mustahik, dan Kifayah. Bangga Surabaya menghasilkan Outreach PDF saja.
7. Kegagalan satu PDF wajib menggagalkan approval; status tetap `MENUNGGU_PENGESAHAN` dan `OUTREACH_COMPLETED` tidak dikirim.
8. Draft boleh disimpan dengan lampiran belum lengkap, tetapi Finalisasi mensyaratkan KTP, KK, Foto Klien, dan Foto Rumah.
9. Kifayah membedakan `0` (tidak ada/tidak berlaku) dari `NULL` (belum diisi); field kosong tidak otomatis menjadi `0`.
10. Tahun pada PDF Kifayah berasal dari tanggal Outreach yang tersimpan.
11. Historical version immutable.
12. Return tidak menghapus historical version.
13. Revision menggunakan Outreach yang sama.
14. Refinalisasi membuat historical version baru.
15. Save Draft/Autosave tidak membuat historical version baru.
16. Petugas tidak dapat mengakses Outreach Petugas lain melalui workflow normal.
17. Kepala tidak mengedit field Outreach melalui approval normal.
18. Admin bukan approver.
19. Search/filter tidak menjadi authorization bypass.
20. Stale write tidak boleh menimpa data baru.
21. Notification tidak menjadi source of truth.
22. Historical attachment tidak boleh berubah akibat current attachment edit.
23. Template baru tidak boleh mengubah historical document secara otomatis.
24. Source dan Type dapat berubah sebelum Finalisasi melalui mekanisme terkontrol;
    setelah Finalisasi perubahan dilakukan melalui Special Unlock.
25. Tidak ada automatic Mustahik/Kifayah answer selection.
26. Tidak ada automatic Mustahik scoring/eligibility decision tanpa rule resmi.
27. Special Unlock bukan business state.
28. Special Unlock hanya dapat dieksekusi dan diedit oleh Admin.
29. Request Special Unlock bukan prasyarat untuk Direct Special Unlock oleh Admin.
30. Admin tidak dapat melakukan approval.
31. `Lock & Ajukan untuk Pengesahan` membuat historical version baru ketika
    action berhasil.
32. `Lock` tanpa `Ajukan` hanya digunakan ketika masih ada pekerjaan lanjutan
    Petugas/Warga.
33. Request Special Unlock hanya dapat `CANCELLED` saat masih `PENDING`.
34. Setelah `PROCESSING`, request berakhir sebagai `COMPLETED` atau `REJECTED`.
35. `special_unlock_requests` tidak memerlukan notification khusus pada v1.
36. Ketika version baru dibuat sementara version sebelumnya masih
    `PENDING_APPROVAL`, version lama tetap immutable dan tidak berubah menjadi
    `SUPERSEDED`; latest version menjadi current submission.
37. Bangga Surabaya tidak memerlukan pre-approval signature Petugas/Warga.
38. Perubahan data form yang terkait signature mewajibkan signature ulang pihak
    yang terdampak.
39. Perubahan attachment saja tidak mewajibkan signature ulang.

# 41. Pending Validation Boundary

Workflow baseline sengaja tidak mengunci beberapa keputusan operasional yang
memerlukan validasi Kelurahan.

## 41.1 Lampiran per Jenis Outreach

Masih perlu dikonfirmasi:

- lampiran wajib untuk Rutilahu;
- lampiran wajib untuk Alat Kesehatan;
- lampiran wajib untuk Beasiswa;
- lampiran wajib untuk Pendidikan (Kejar Paket);
- lampiran wajib untuk Bantuan Modal Usaha.

## 41.2 Format Lampiran

Masih perlu dikonfirmasi apakah lampiran tertentu harus berupa foto atau boleh
menggunakan PDF/dokumen lain.

## 41.3 Operational File Limits

Masih perlu dikonfirmasi apakah Kelurahan memiliki batas ukuran file atau
ketentuan operasional tertentu.

Kualitas/resolusi foto merupakan technical baseline internal dan tidak menunggu
validasi ini.

## 41.4 Perubahan Lampiran setelah Finalisasi

Masih perlu dikonfirmasi apakah ada kebutuhan operasional khusus untuk
penambahan/penggantian attachment setelah finalisasi, di luar workflow
Return/Special Unlock yang disediakan sistem.

## 41.5 Retention

Masih perlu dikonfirmasi berapa lama data Outreach dan historical attachment
harus tersedia.

## 41.6 Data Lama / Archive

Masih perlu dikonfirmasi bagaimana Kelurahan menangani data Outreach lama atau
sudah tidak aktif.

## 41.7 E-Surat / E-Arsip

Masih perlu dikonfirmasi:

- siapa uploader;
- apakah reference/nomor archive perlu disimpan;
- bagaimana verifikasi arsip dianggap berhasil.

## 41.8 Mustahik Multiplicity

Masih perlu dikonfirmasi apakah pertanyaan Tanggungan dan Hutang benar-benar
boleh multiple choice dan kategori lain yang memerlukan lebih dari satu jawaban.

## 41.9 Field Outreach Tambahan

Masih perlu dikonfirmasi apakah ada data operasional yang biasa dicatat Petugas
namun belum ada dalam baseline form.

## 41.9 Signature Template Kepala

Masih perlu dikonfirmasi apakah template signature Kepala yang disimpan di
sistem dapat digunakan untuk dokumen resmi.

## 41.9 Digital Stamp

Masih perlu dikonfirmasi apakah digital stamp boleh ditempatkan pada PDF atau
harus menggunakan stamp fisik setelah cetak.

## 41.9 Prosedur Koreksi Resmi

Secara desain sistem, Admin merupakan executor Special Unlock.

Yang masih perlu divalidasi adalah kesesuaian mekanisme tersebut dengan SOP dan
kewenangan operasional Kelurahan.

## 41.9 Petugas Setelah Return

Masih perlu dikonfirmasi apakah pada praktik Kelurahan Return selalu dikerjakan
oleh Petugas yang sama atau ada mekanisme penugasan lain.

## 41.9 Concurrent Usage

Target desain sementara sekitar 10 user bersamaan, bukan hard limit.

Validasi lapangan tetap diperlukan untuk mengetahui penggunaan nyata.

## 41.9 Head Replacement

Masih perlu dikonfirmasi bagaimana submission yang menunggu approval ditangani
ketika terjadi pergantian Kepala.

## 41.9 Backup

Masih perlu dikonfirmasi:

- siapa owner backup;
- frekuensi;
- media/lokasi backup;
- pembagian tanggung jawab dengan hosting.

## 41.9 Hosting & Domain

Masih perlu dikonfirmasi apakah Kelurahan sudah mempunyai hosting/domain atau
perlu disiapkan baru.

Tidak ada pending validation pada re-sign rule maupun PDF packaging; kedua
hal tersebut sudah menjadi baseline internal.

# 42. Cross-Document References

| Concern | Canonical Document |
|---|---|
| Product requirements | `docs/product/PRD.md` |
| Project scope | `docs/product/scope.md` |
| Terminology | `docs/domain/glossary.md` |
| Outreach form | `docs/domain/forms/outreach.md` |
| Mustahik form | `docs/domain/forms/mustahik.md` |
| Kifayah form | `docs/domain/forms/kifayah.md` |
| Architecture | `docs/architecture/architecture.md` |
| Database / ERD | `docs/architecture/database.md` |
| Roles & permissions | `docs/architecture/roles-permissions.md` |
| Security | `docs/architecture/security.md` |
| UI/UX | `docs/ux/design.md` |
| Prototype | `docs/prototype/prototype.md` |
| Deployment | `docs/engineering/deployment.md` |

Satu concern memiliki satu canonical source. Checkpoint/planner proyek berada
di luar repository dan tidak menjadi dependency runtime atau implementation
source.

# 43. Implementation Boundary

Dokumen ini mengatur **workflow behavior**, bukan implementasi framework
tertentu.

Implementasi bebas menentukan:

- nama class;
- route name;
- service class;
- event/listener internals;
- transaction helper;
- UI component implementation;

selama behavior berikut tetap benar:

```text
authorized actor
      ↓
valid state
      ↓
valid action
      ↓
validation / gate
      ↓
consistent transition
      ↓
historical integrity
```

Administrative exception seperti Special Unlock juga tetap tunduk pada
authority, validation, state/version check, audit, dan historical integrity.

Workflow tidak boleh dibypass hanya demi mempermudah implementasi.

Detail teknis scheduler, database types, indexes, service/class naming, dan
deployment berada di canonical engineering/architecture documents terkait.

# 44. Workflow Summary

Baseline proses lengkap:

```text
PETUGAS LOGIN
    ↓
CREATE OUTREACH
    ↓
SOURCE → TYPE → DATE
    ↓
DRAFT
    ↓
STEPWISE DATA COLLECTION
    ├── OUTREACH
    ├── MUSTAHIK (Baznas/CSR)
    ├── KIFAYAH (Baznas/CSR)
    └── LAMPIRAN
    ↓
SIGNATURE SESUAI SOURCE
    ├── Baznas/CSR
    │     ├── Petugas → Mustahik
    │     └── Warga → Kifayah
    └── Bangga Surabaya
          └── tidak ada pre-approval signature
    ↓
REVIEW
    ↓
FINALISASI
    ↓
CREATE HISTORICAL VERSION
    ↓
MENUNGGU_PENGESAHAN
    ↓
KEPALA REVIEW
    ├── RETURN + NOTE
    │      ↓
    │  PERLU_PERBAIKAN
    │      ↓
    │  PETUGAS REVISI
    │      ↓
    │  FINALISASI ULANG
    │      ↓
    │  VERSION BARU
    │      ↓
    │  MENUNGGU_PENGESAHAN
    │
    ├── SPECIAL UNLOCK
    │      ↓
    │  ADMIN CONTROLLED EDITING
    │      ├── LOCK
    │      │    ↓
    │      │ PERLU_PERBAIKAN
    │      │    ↓
    │      │ PETUGAS LANJUT
    │      │
    │      └── LOCK & AJUKAN
    │           ↓
    │        VERSION BARU
    │           ↓
    │        MENUNGGU_PENGESAHAN
    │
    └── APPROVE
           ↓
        PDF GATE
        ├── FAIL → tetap MENUNGGU_PENGESAHAN
        └── SUCCESS
              ↓
            SELESAI
              ↓
        PDF diambil/retrieve
              ↓
        Upload manual ke E-Surat/E-Arsip
```

Special Unlock bukan business state.

Administrative Request Special Unlock memiliki lifecycle terpisah:

```text
PENDING
  ├── CANCELLED
  └── PROCESSING
         ├── COMPLETED
         └── REJECTED
```

Workflow ini menjadi baseline operasional teknis sampai terdapat hasil validasi
Kelurahan atau perubahan desain yang terdokumentasi.
