# Database Specification

## 1. Document Information

| Item | Value |
|---|---|
| Project | Pengembangan Sistem Informasi Pendataan Outreach Berbasis Web di Kelurahan Manukan Kulon, Kota Surabaya |
| Document | Database Specification |
| Version | 1.1 |
| Status | ACTIVE — Technical Baseline |
| Database Engine | MySQL |
| Application | Laravel 13 |
| Amandemen | D-030, D-031, D-041, dan D-043 (27 Oktober 2026); lihat `docs/project-management/decisions.md` |

Amandemen D-041 menambah kolom `users.nip`; jumlah tabel tetap 22 dan label versi
tetap Database v1.1. D-030 dan D-031 hanya memperjelas tafsir dan teks, tanpa
perubahan kolom.

### Document Responsibility

Dokumen ini merupakan sumber canonical untuk **struktur data, tabel, field,
foreign key, cardinality, uniqueness, lifecycle data, historical versioning,
dan aturan persistence** pada baseline proyek.

Dokumen ini tidak menjadi sumber utama untuk:

- kebutuhan produk → `docs/product/PRD.md`;
- batas scope → `docs/product/scope.md`;
- istilah domain → `docs/domain/glossary.md`;
- detail field dan perilaku Form Outreach → `docs/domain/forms/outreach.md`;
- detail Form Mustahik → `docs/domain/forms/mustahik.md`;
- detail Form Kifayah → `docs/domain/forms/kifayah.md`;
- workflow/state transition → `docs/architecture/workflow.md`;
- role dan permission → `docs/architecture/roles-permissions.md`;
- security control → `docs/architecture/security.md`.

---

# 2. Database Principles

## 2.1 System of Record

Database menyimpan **current operational state** pada tabel domain utama dan
historical submission pada `outreach_versions`.

```text
Current record
    ↓
outreaches + related forms + active attachments

Historical submission
    ↓
outreach_versions + outreach_version_attachments
```

Current data dapat berubah selama Outreach masih berada pada status yang dapat
diedit. Historical version yang telah dibuat saat Finalisasi/Finalisasi Ulang
bersifat immutable.

## 2.2 No BLOB for Production Files

Isi file fisik tidak disimpan sebagai BLOB di MySQL. Database menyimpan metadata
file dan relasinya melalui tabel `files` serta tabel relasi yang sesuai.

## 2.3 No Total as Source of Truth

Total pendapatan keluarga dan total pengeluaran keluarga dihitung dari field
sumber. Total tidak disimpan sebagai kolom sumber kebenaran terpisah.

## 2.4 NULL versus Zero

Untuk field numerik yang dapat bernilai uang atau jumlah:

```text
NULL = belum diisi / belum diketahui
0    = nilai nol yang memang telah diisi secara eksplisit
```

Untuk field Kifayah, nilai `0` yang diisi eksplisit harus dipertahankan sebagai
nol dan `NULL` harus tetap menandakan belum diisi; UI/PDF tidak boleh
menyamarkan keduanya sebagai nilai yang sama. Representasi `-` hanya boleh
digunakan bila sesuai aturan tampilan form dan tidak menghilangkan perbedaan
antara data belum diisi dan nilai nol.

## 2.5 Delete Strategy

Normal delete terhadap record Outreach tidak tersedia pada v1. Anak/relasi inti
menggunakan prinsip `ON DELETE RESTRICT` sehingga historical integrity dan
referensi tidak hilang secara tidak sengaja.

---

# 3. Entity Overview

Database v1 terdiri dari **22 tabel domain**. Tabel infrastruktur framework
berada di luar hitungan ini dan dibatasi seminimal mungkin (D-030): session dan
cache memakai driver file, queue sinkron, tanpa reset password dan tanpa
registrasi publik, sehingga praktis hanya tabel `migrations` milik Laravel.

| # | Table | Domain |
|---:|---|---|
| 1 | `users` | Account & role |
| 2 | `outreach_sources` | Master Source |
| 3 | `outreach_types` | Master Type |
| 4 | `outreaches` | Core Outreach |
| 5 | `mustahik_forms` | Mustahik form |
| 6 | `mustahik_answers` | Mustahik answers |
| 7 | `kifayah_forms` | Kifayah form |
| 8 | `kifayah_answers` | Kifayah answers |
| 9 | `files` | Physical file registry |
| 10 | `outreach_attachments` | Current attachments |
| 11 | `outreach_version_attachments` | Historical attachment snapshot |
| 12 | `outreach_versions` | Historical submission versions |
| 13 | `outreach_approvals` | Head decisions |
| 14 | `signatures` | Actual Petugas/Warga signatures |
| 15 | `signature_templates` | Head signature templates |
| 16 | `stamp_templates` | Stamp templates |
| 17 | `document_template_versions` | Logical document template versions |
| 18 | `history_events` | Business history |
| 19 | `audit_logs` | Administrative/security audit |
| 20 | `notifications` | In-app notifications |
| 21 | `archive_records` | External archive references |
| 22 | `special_unlock_requests` | Administrative Special Unlock requests |

---

# 4. Master and Account Tables

## 4.1 `users`

Menyimpan akun pengguna sistem. Tidak ada tabel `roles` pada v1; role merupakan
preset sederhana.

```text
id                         PK
name
email                      UNIQUE
password_hash
nip                        NULL; diisi untuk user KEPALA (D-041)
role                       PETUGAS | KEPALA | ADMIN
status                     ACTIVE | INACTIVE
last_login_at
created_at
updated_at
```

### Rules

- `email` unik.
- User yang tidak lagi digunakan menjadi `INACTIVE`, bukan dihapus.
- Identitas user harus tetap tersedia untuk kebutuhan history/audit.
- Pada satu waktu hanya ada satu Kepala Kelurahan aktif.
- Enforcement satu Kepala aktif dilakukan pada service/transaction boundary.
- `nip` diisi untuk user KEPALA dan dicetak pada blok pengesahan PDF. Format dan
  posisi cetaknya masih **PENDING VALIDASI** (D-041).
- PDF historis membaca `nip` saat render. Perubahan `nip` dicatat pada
  `audit_logs` dan tidak dilakukan setelah NIP dipakai pada approval, kecuali
  melalui koreksi administratif (D-041).
- Kolom `password_hash` dipertahankan; model User Laravel disesuaikan untuk
  memakainya. Kolom bawaan framework seperti `remember_token`,
  `email_verified_at`, dan kolom 2FA tidak ditambahkan (D-030).

### Role meaning

Role `PETUGAS`, `KEPALA`, dan `ADMIN` adalah nilai domain yang digunakan dalam
otorisasi. Detail permission bukan tanggung jawab dokumen ini.

---

## 4.2 `outreach_sources`

Master **Sumber Outreach**.

```text
id                         PK
code                       UNIQUE
name
active
created_at
updated_at
```

### Seed baseline

| Code | Name |
|---|---|
| `BAZNAS` | Baznas |
| `CSR` | CSR |
| `BANGGA_SURABAYA` | Bangga Surabaya |

### Rules

- Source dipilih pada pembuatan Outreach.
- Source menentukan struktur/alur form.
- Source tidak ditampilkan pada dokumen Outreach utama.
- Source yang sudah digunakan tidak dihapus; gunakan `active` untuk
  mengendalikan penggunaan berikutnya.

---

## 4.3 `outreach_types`

Master **Jenis Outreach**.

```text
id                         PK
code                       UNIQUE
name
active
created_at
updated_at
```

### Seed baseline

| Code | Name |
|---|---|
| `RUTILAHU` | Rutilahu |
| `ALAT_KESEHATAN` | Alat Kesehatan |
| `BEASISWA` | Beasiswa |
| `PENDIDIKAN_KEJAR_PAKET` | Pendidikan (Kejar Paket) |
| `BANTUAN_MODAL_USAHA` | Bantuan Modal Usaha |

### Rules

- Type dipilih pada awal pembuatan Outreach.
- Type menentukan jenis intervensi.
- Type ditampilkan pada awal dokumen Outreach.
- Type yang sudah digunakan tidak dihapus; gunakan `active` untuk penggunaan
  berikutnya.
- Semua kombinasi Source × Type diperbolehkan pada baseline.

---

# 5. Core Outreach Table

## 5.1 `outreaches`

`outreaches` merupakan record utama sistem. Data identitas klien disimpan
langsung sebagai snapshot operational pada Outreach sehingga dokumen dan proses
historis tidak bergantung pada perubahan record identitas eksternal.

```text
id                         PK
draft_code                 UNIQUE
outreach_number            UNIQUE, NULL sampai Finalisasi pertama
source_id                  FK -> outreach_sources.id
type_id                    FK -> outreach_types.id
outreach_date
petugas_id                 FK -> users.id

# identity / client snapshot
nama
nik
tempat_tgl_lahir_umur
agama
alamat_ktp
alamat_domisili
status_kependudukan        WNI | WNA
tahun_warga_surabaya
pekerjaan
status_hubungan_keluarga
jumlah_anggota_kk
jumlah_kk_dalam_rumah
status_tempat_tinggal
penghasilan_klien
no_telp
status_perkawinan
pendidikan_terakhir
status_kesejahteraan
desil_1_10
jaminan_kesehatan
merokok
bantuan_pernah_diterima
bantuan_detail
punya_hutang
besar_hutang

# family income
penghasilan_kepala_keluarga
penghasilan_suami_istri
penghasilan_anak_1kk
penghasilan_lainnya

# 13 expense fields
pengeluaran_sewa
pengeluaran_belanja_memasak
pengeluaran_air_minum
pengeluaran_lpg_pgn
pengeluaran_listrik
pengeluaran_pdam
pengeluaran_sekolah
pengeluaran_internet
pengeluaran_pulsa
pengeluaran_rokok
pengeluaran_berobat
pengeluaran_angsuran
pengeluaran_bbm

# house / assets
luas_bangunan
jenis_lantai
jenis_dinding
jenis_atap
daya_listrik
sumber_air
mck_layak
jumlah_sepeda_angin
jumlah_sepeda_listrik
jumlah_becak
jumlah_sepeda_motor
jumlah_roda_4
jumlah_kendaraan_lainnya
nama_kendaraan_lainnya
perhiasan
aset_lainnya

# narrative
upaya_dilakukan
harapan_klien
uraian_alasan_intervensi

# state / concurrency
status                     DRAFT | MENUNGGU_PENGESAHAN | PERLU_PERBAIKAN | SELESAI
edit_version
created_at
updated_at
```

### Identity and ownership rules

- `nik` bukan primary key dan bukan identifier record.
- `draft_code` dibuat ketika Outreach dibuat sebagai draft.
- `outreach_number` belum ada ketika hanya draft; nomor internal dibuat pada
  Finalisasi pertama.
- `petugas_id` menunjuk Petugas pemilik Outreach.
- Ownership tidak ditentukan hanya dengan URL atau parameter request; permission
  dilakukan pada service/policy berdasarkan record terkait.

### Business rules

- Draft boleh memiliki field wajib yang belum terisi.
- Finalisasi melakukan validasi penuh.
- Field conditional mengikuti business rules form.
- `desil_1_10` menyimpan pilihan desil 1–10 dan `status_kesejahteraan` diisi
  otomatis dengan label pasangannya sesuai D-019 (pemetaan label ada di
  `docs/domain/forms/outreach.md`). Yang masih **PENDING VALIDASI** hanya
  evidence dokumen sumber resmi label tersebut (VAL-002); jangan mengubah atau
  menambah label di luar D-019.
- Total pendapatan dan total pengeluaran dihitung dari source fields.
- `besar_hutang` relevan bila `punya_hutang` bernilai Ya sesuai aturan form.
- Source dan Type dapat diubah sebelum Finalisasi dengan konfirmasi dan
  penanganan dampak perubahan. Setelah Finalisasi, perubahan hanya melalui
  Special Unlock.
- Special Unlock bukan nilai `status`; status bisnis tetap mencerminkan
  lifecycle Outreach.
- Normal delete tidak tersedia.

### Concurrency

`edit_version` dipakai untuk optimistic concurrency pada current Outreach.
Stale update tidak boleh diam-diam menimpa perubahan yang lebih baru.

---

# 6. Mustahik Tables

## 6.1 `mustahik_forms`

Dipakai hanya pada Source `BAZNAS` dan `CSR`.

```text
id                         PK
outreach_id                UNIQUE, FK -> outreaches.id
petugas_id                 FK -> users.id
survey_date
kepala_keluarga_nama
kepala_keluarga_nik_no_kk
kepala_keluarga_alamat
kepala_keluarga_rt
kepala_keluarga_rw
signature_id               NULL, FK -> signatures.id
created_at
updated_at
```

### Rules

- Satu Outreach memiliki maksimal satu Mustahik form aktif.
- `outreach_id` unique.
- Identitas Kepala Keluarga merupakan data khusus Mustahik.
- Sistem tidak boleh mengasumsikan Klien Outreach otomatis merupakan Kepala
  Keluarga.
- Signature menunjuk actual signature Petugas.

---

## 6.2 `mustahik_answers`

Menyimpan jawaban 25 pertanyaan Mustahik.

```text
id                         PK
mustahik_form_id           FK -> mustahik_forms.id
question_code
answer                     JSON
created_at
updated_at
UNIQUE(mustahik_form_id, question_code)
```

### Rules

- V1 tidak mempunyai master question table.
- `question_code` mengikuti form sumber dan canonical Mustahik document.
- Satu `question_code` memiliki satu answer aktif pada baseline single-choice.
- Semua 25 pertanyaan wajib diisi pada alur Mustahik yang berlaku dan setiap
  pertanyaan menggunakan **single-choice** sesuai dokumen/form sumber.
- Tidak ada automatic selection dari Outreach dan tidak ada default selection.
- Tidak ada automatic scoring atau mesin penentu kelayakan.
- Jawaban harus menggunakan option yang valid untuk `question_code` terkait,
  dengan label dan opsi mengikuti form sumber yang telah disepakati.

### Baseline question-code groups

```text
HOUSE_BUILDING_AREA
HOUSE_WALL_TYPE
HOUSE_OWNERSHIP_STATUS
HOUSE_MCK
HOUSE_DRINKING_WATER
HOUSE_KITCHEN
HOUSE_ASSET_OWNERSHIP
HOUSE_VEHICLE_OWNERSHIP
HOUSE_FLOOR_TYPE
HOUSE_ROOF_TYPE

ECONOMIC_HEAD_EDUCATION
ECONOMIC_HEAD_OCCUPATION
ECONOMIC_MONTHLY_INCOME
ECONOMIC_FOOD_PURCHASE
ECONOMIC_CLOTHING_PURCHASE
ECONOMIC_OTHER_SUBSIDY
ECONOMIC_MEAL_FREQUENCY
ECONOMIC_LIGHTING
ECONOMIC_SIDE_BUSINESS
ECONOMIC_GOVERNMENT_AID

PHYSICAL_CONDITION
PHYSICAL_DEPENDENTS
PHYSICAL_DEBT
PHYSICAL_HEALTH_CAPABILITY
PHYSICAL_SAVINGS
```

---

# 7. Kifayah Tables

## 7.1 `kifayah_forms`

Dipakai hanya pada Source `BAZNAS` dan `CSR`.

```text
id                         PK
outreach_id                UNIQUE, FK -> outreaches.id
income_head_family
income_other_members
expense_head_household
expense_housewife
expense_adult_working
expense_adult_not_working
expense_school_sma
expense_school_smp
expense_school_sd
expense_baby_not_school
expense_disability
expense_breastfeeding
expense_pregnant
expense_widow_not_working
higher_education_count
signature_id               NULL, FK -> signatures.id
created_at
updated_at
```

### Rules

- Satu Outreach memiliki maksimal satu Kifayah form aktif.
- `outreach_id` unique.
- Input tetap dilakukan Petugas.
- Data Outreach dapat dipakai sebagai contextual information, tetapi bukan
  automatic answer source.
- Tidak ada tabel `family_members` pada v1.
- Semua kategori pengeluaran Kifayah harus diisi secara eksplisit.
- `0` berarti tidak ada pengeluaran/tidak berlaku; `NULL` berarti belum diisi.
  Sistem tidak boleh mengubah nilai kosong menjadi `0` secara otomatis.
- Nilai numerik tidak boleh negatif dan harus menolak input nonnumerik.
- Jumlah anggota keluarga yang menempuh pendidikan tinggi tidak diberi batas
  maksimum arbitrer tanpa dasar kebutuhan yang tervalidasi.
- Tanda tangan Warga/Klien menjadi acknowledgement terpisah; tidak ada kolom
  `statement_acknowledged`.
- `signature_id` menunjuk actual signature Warga/Klien.

### Source form mapping

Field utama mengikuti Form Had Kifayah:

```text
INFORMASI MUSTAHIK
- Nama
- NIK
- Alamat Tempat Tinggal
- Kelurahan
- Kecamatan
- No. HP

SUMBER PENDAPATAN
- Pendapatan Kepala Keluarga Per Bulan
- Pendapatan Anggota Lain Per Bulan

PENGELUARAN KELUARGA PER BULAN
- Kepala Rumah Tangga
- Ibu Rumah Tangga
- Anak Dewasa (Bekerja)
- Anak Dewasa (Tidak Bekerja)
- Anak Sekolah (SMA/Setingkat)
- Anak Sekolah (SMP/Setingkat)
- Anak Sekolah (SD/Setingkat)
- Anak/Bayi Belum Sekolah

PENGELUARAN TAMBAHAN PER BULAN
- Penyandang Disabilitas
- Ibu Menyusui
- Ibu Hamil
- Status Janda (Tidak Bekerja)

PENDIDIKAN
- Jumlah Keluarga yang Sedang Menempuh Pendidikan Tinggi
```

Pilihan status rumah, listrik, bansos, BPJS/KIS/PBI, kendaraan bermotor,
tabungan, dan hutang disimpan pada `kifayah_answers`.

---

## 7.2 `kifayah_answers`

```text
id                         PK
kifayah_form_id            FK -> kifayah_forms.id
question_code
answer                     JSON
created_at
updated_at
UNIQUE(kifayah_form_id, question_code)
```

### Baseline question codes

```text
HOUSE_OWNERSHIP
ELECTRICITY
GOVERNMENT_AID
BPJS_KIS_PBI
MOTOR_VEHICLE
SAVINGS
DEBT
```

### Rules

- Satu `question_code` memiliki satu answer aktif pada baseline.
- Tidak ada automatic selection.
- Jawaban harus berasal dari pilihan yang ditentukan untuk pertanyaan tersebut.
- Nilai numerik Kifayah mengikuti aturan eksplisit: `0` berarti tidak ada
  pengeluaran/tidak berlaku, sedangkan `NULL` berarti belum diisi. Jangan
  mengonversi nilai kosong menjadi nol secara otomatis.

---

# 8. File Registry and Attachments

## 8.1 `files`

Registry terpusat untuk physical file yang digunakan lintas domain.

```text
id                         PK
storage_disk
storage_key
original_name
mime_type
extension
size_bytes
sha256_hash
width
height
purged_at                 NULL
created_at
updated_at
```

### Uses

Registry `files` dapat digunakan untuk:

- attachment Outreach;
- actual signature Petugas/Warga;
- template signature Kepala;
- template stamp.

### Rules

- Metadata dan relasi disimpan di database.
- Isi file disimpan melalui Laravel Filesystem.
- Production file bersifat private.
- `storage_key` tidak menjadi public raw URL secara langsung.
- File diproses dengan validasi, duplicate check, optimization/compression, lalu
  storage.
- File asli tidak disimpan permanen setelah hasil optimasi berhasil.
- `purged_at` digunakan untuk menandai physical file yang telah dipurge.

---

## 8.2 `outreach_attachments`

Menyimpan attachment aktif yang terkait dengan current Outreach.

```text
id                         PK
outreach_id                FK -> outreaches.id
file_id                    FK -> files.id
category
title
description
sort_order
deleted_at                 NULL
created_at
updated_at
UNIQUE(outreach_id, file_id)
```

### Baseline categories

```text
KTP
KK
FOTO_KLIEN
FOTO_RUMAH
TAMBAHAN
```

### Rules

- Attachment wajib baseline: KTP, KK, Foto Klien, Foto Rumah.
- Empat lampiran wajib baseline harus berupa gambar (D-034).
- Lampiran tambahan fleksibel sampai ada validasi per Jenis Outreach dan tidak
  memblokir Finalisasi pada prototype (D-033).
- Hanya lampiran gambar yang dicetak pada halaman lampiran PDF Outreach;
  lampiran berformat PDF tetap tersimpan dan ditampilkan sebagai halaman
  rujukan berisi nama berkas (D-034).
- Multiple file dalam satu kategori diperbolehkan sesuai kebutuhan dan batas
  validasi.
- `title`/`description` terutama digunakan untuk Foto Klien, Foto Rumah, dan
  Tambahan sesuai domain form.
- Attachment dapat di-replace atau di-delete selama status mengizinkan.
- `deleted_at` adalah soft lifecycle marker untuk current attachment relation.
- File identik tidak boleh diunggah dua kali pada Outreach yang sama.
- Deteksi konten identik menggunakan `files.sha256_hash`; `content_hash` tidak
  diduplikasi pada tabel relasi.
- Jika relasi di-soft-delete lalu file yang sama dipasang kembali pada Outreach
  yang sama, sistem mengaktifkan kembali relasi lama (*reactivate*) alih-alih
  membuat relasi duplikat. Unique constraint tetap berlaku pada pasangan
  `(outreach_id, file_id)`; implementasi harus mereaktivasi record yang ada.
- Perubahan current attachment tidak boleh mengubah historical attachment
  snapshot.

---

## 8.3 `outreach_version_attachments`

Snapshot metadata attachment yang digunakan pada historical submission version.

```text
id                         PK
outreach_version_id        FK -> outreach_versions.id
file_id                    FK -> files.id
category
title
description
sort_order
created_at
UNIQUE(outreach_version_id, file_id)
```

### Rules

Relasi ini diperlukan agar historical version tetap menunjuk attachment yang
benar meskipun current `outreach_attachments` berubah.

```text
Current attachment changes
        ≠
Historical attachment changes
```

Physical file yang masih direferensikan historical version tidak boleh dipurge
sebelum retention/policy mengizinkan.

---

# 9. Historical Submission Tables

## 9.1 `outreach_versions`

Menyimpan snapshot historical pada Finalisasi/Finalisasi Ulang.

```text
id                         PK
outreach_id                FK -> outreaches.id
version_number
status                     PENDING_APPROVAL | RETURNED | APPROVED
snapshot_data              JSON
document_template_version_id FK -> document_template_versions.id
mustahik_signature_id      NULL, FK -> signatures.id
kifayah_signature_id       NULL, FK -> signatures.id
created_by                 FK -> users.id
created_at
finalized_at
UNIQUE(outreach_id, version_number)
```

### Snapshot structure

`snapshot_data` minimal domain-oriented dan mencakup:

```text
meta
client
family_income
expenses
house
assets
narrative
mustahik        (bila relevan)
kifayah         (bila relevan)
```

Byte file tidak disimpan dalam JSON.

### Rules

- Version dibuat pada Finalisasi dan Finalisasi Ulang.
- Save Draft dan Autosave tidak membuat version baru.
- Historical version immutable.
- Version lama tidak diubah ketika current data direvisi.
- Tidak ada status `SUPERSEDED` pada v1.
- `version_number` unik per Outreach dan meningkat secara berurutan.
- Versi historis tidak menggunakan status `SUPERSEDED`; versi lama tetap
  immutable dan versi terbaru menjadi acuan pengajuan aktif.
- Signature references merekam actual signatures yang dipakai pada submission.
- Referensi `mustahik_signature_id` dan `kifayah_signature_id` boleh `NULL` bila
  langkah tanda tangan tersebut tidak berlaku, termasuk alur Bangga Surabaya.
- Service harus memvalidasi `signer_type` terhadap form yang mereferensikan
  signature; foreign key saja tidak cukup.
- Pada revisi, tanda tangan perlu dibuat ulang hanya bila data form berubah;
  perubahan lampiran saja tidak mewajibkan re-sign.
- `document_template_version_id` merekam logical document template yang menjadi
  dasar historical document.

---

## 9.2 `outreach_approvals`

Keputusan Kepala untuk satu historical version.

```text
id                         PK
outreach_id                FK -> outreaches.id
outreach_version_id        UNIQUE, FK -> outreach_versions.id
kepala_id                  FK -> users.id
decision                   APPROVED | RETURNED
note                       NULL jika APPROVED; WAJIB jika RETURNED
head_signature_template_id NULL, FK -> signature_templates.id
stamp_template_id          NULL, FK -> stamp_templates.id
decided_at
created_at
```

### Cardinality

```text
1 Outreach Version → 0..1 Approval
```

### Rules

- Satu historical version hanya memiliki maksimal satu approval decision.
- Approval immutable setelah keputusan tercatat.
- `RETURNED` wajib memiliki `note`.
- `head_signature_template_id` wajib terisi untuk `APPROVED` dan boleh `NULL`
  untuk `RETURNED`, yang tidak memerlukan tanda tangan Kepala pada keputusan
  pengembalian.
- `stamp_template_id` nullable karena penggunaan stempel digital masih pending
  kebijakan Kelurahan.
- Approval tidak mengubah isi version.
- Approval hanya dilakukan oleh user dengan role Kepala sesuai authorization.

---

# 10. Signature Tables

## 10.1 `signatures`

Actual signature yang dibuat langsung dari perangkat.

```text
id                         PK
file_id                    FK -> files.id
signer_type                PETUGAS | WARGA
signer_user_id             NULL, FK -> users.id
signer_name_snapshot
signed_at
created_at
```

### Rules

- Petugas dan Warga menandatangani langsung melalui signature pad.
- Tidak ada manual signature upload untuk flow normal Petugas/Warga.
- `signer_user_id` boleh null untuk Warga karena Warga tidak harus memiliki akun.
- `signer_name_snapshot` mempertahankan nama pada saat signature dibuat.
- Signature yang telah dipakai pada historical submission tidak diedit/dihapus
  melalui flow normal.

---

## 10.2 `signature_templates`

Template signature Kepala Kelurahan yang dikelola Admin.

```text
id                         PK
file_id                    FK -> files.id
head_user_id               FK -> users.id
version_number
valid_from
valid_until
is_active
created_at
UNIQUE(head_user_id, version_number)
```

### Rules

- Template lama tidak diubah.
- Perubahan menghasilkan template version baru.
- Satu template aktif digunakan untuk Kepala aktif.
- Enforcement activation dilakukan pada service/transaction boundary.
- Historical approval menyimpan reference template yang dipakai sehingga
  perubahan template berikutnya tidak mengubah dokumen lama.

---

## 10.3 `stamp_templates`

Template stempel Kelurahan.

```text
id                         PK
file_id                    FK -> files.id
version_number
valid_from
valid_until
is_active
created_at
UNIQUE(version_number)
```

### Rules

- `version_number` unik.
- Maksimal satu stamp template aktif pada satu waktu.
- Template lama tidak diubah.
- Historical approval menyimpan reference yang digunakan bila stamp diterapkan.
- Penggunaan stamp digital pada PDF masih **PENDING VALIDASI** kebijakan
  Kelurahan.

---

## 10.4 `document_template_versions`

Version logical template dokumen yang dipakai oleh renderer.

```text
id                         PK
template_key
version_number
renderer_key
metadata                   JSON
is_active
created_at
```

### Purpose

Tabel ini berbeda dari:

- `signature_templates` → template signature Kepala;
- `stamp_templates` → template stempel.

`document_template_versions` menjaga agar logical layout dokumen historis dapat
direkonstruksi.

### Rules

- Template version lama immutable.
- Historical version menyimpan FK ke template version yang digunakan.
- Aktivasi template baru tidak mengubah historical document.
- Preview browser dan official PDF memakai logical document structure yang sama.

---

# 11. History and Audit Tables

## 11.1 `history_events`

Business history per Outreach.

```text
id                         PK
outreach_id                FK -> outreaches.id
actor_id                   FK -> users.id
actor_name_snapshot
actor_role_snapshot
event_type
outreach_version_id        NULL, FK -> outreach_versions.id
approval_id                NULL, FK -> outreach_approvals.id
description
reason
field_changes              JSON
metadata                   JSON
created_at
```

### Baseline event types

```text
CREATED
SECTION_SAVED
FINALIZED
RETURNED
REFINALIZED
APPROVED
SPECIAL_UNLOCKED
SPECIAL_UNLOCK_EDITED
SPECIAL_UNLOCK_LOCKED
SPECIAL_UNLOCK_SUBMITTED
```

`SPECIAL_UNLOCK_LOCKED` mencatat `Lock` setelah Special Unlock, dan
`SPECIAL_UNLOCK_SUBMITTED` mencatat `Lock & Ajukan untuk Pengesahan` (D-031).
Penambahan ini hanya memperluas daftar nilai `event_type`; kolom tidak berubah.

### Rules

- History append-only.
- Normal edit/delete terhadap history tidak disediakan.
- Autosave tidak dicatat sebagai event pada setiap interval.
- `SECTION_SAVED` hanya digunakan pada kondisi save penting yang memang perlu
  masuk timeline.
- `field_changes` dapat menyimpan perubahan penting dengan format before/after.
- Sensitive data tidak ditampilkan berlebihan pada UI history.

---

## 11.2 `audit_logs`

Audit administratif/global/sensitif dipisahkan dari business history.

```text
id                         PK
actor_id                   FK -> users.id
action
target_type
target_id
before_data                JSON
after_data                 JSON
metadata                   JSON
created_at
```

### Rules

- Append-only.
- Tidak memakai polymorphic FK untuk target.
- Dipakai antara lain untuk perubahan user, role/status, aktivasi template,
  Special Unlock, dan tindakan administratif penting.
- Audit lebih detail daripada timeline business history bila diperlukan.
- Audit tidak menjadi pengganti `history_events`.

---

# 12. Notification Table

## 12.1 `notifications`

Notifikasi in-app untuk event operasional utama.

```text
id                         PK
user_id                   FK -> users.id
type                      OUTREACH_FINALIZED | OUTREACH_RETURNED | OUTREACH_COMPLETED
title
message
outreach_id                FK -> outreaches.id
outreach_version_id        FK -> outreach_versions.id
approval_id                NULL, FK -> outreach_approvals.id
read_at                    NULL
created_at
```

### Notification flow

```text
Finalisasi
    → Kepala menerima OUTREACH_FINALIZED

Return
    → Petugas pemilik menerima OUTREACH_RETURNED

Approval + PDF berhasil + SELESAI
    → Petugas pemilik menerima OUTREACH_COMPLETED
```

### Retention

- Setiap notification memiliki umur 30 hari sejak `created_at`.
- Hanya notification yang sudah expired yang dihapus.
- Penghapusan notification tidak menghapus History, Audit, Approval, Outreach,
  version, atau file.
- Tidak ada notifikasi khusus Special Unlock pada v1; Admin menggunakan antrean
  `special_unlock_requests`.
- `read_at = NULL` berarti unread.
- `read_at` terisi berarti read.
- Kegagalan notification delivery tidak me-roll back business action yang sudah
  berhasil.

---

# 13. Archive Reference Table

## 13.1 `archive_records`

Mencatat referensi terhadap proses pengarsipan eksternal.

```text
id                         PK
outreach_id                FK -> outreaches.id
outreach_version_id        FK -> outreach_versions.id
external_system
archive_reference          NULL
archived_at                 NULL
verified_at                NULL
created_at
```

### Rules

- Tabel ini bukan penyimpanan PDF.
- PDF final tetap output on-demand.
- `external_system` dapat menyimpan nama sistem arsip/E-Surat.
- `archive_reference` disiapkan untuk nomor/reference dari sistem eksternal bila
  prosedur Kelurahan menggunakannya.
- `archived_at` tetap `NULL` sampai arsip eksternal benar-benar dibuat;
  `verified_at` tetap `NULL` sampai verifikasi benar-benar dilakukan.
- Pengarsipan dilakukan manual oleh Admin paling lambat satu bulan sejak
  persetujuan Kepala, tanpa integrasi E-Surat/E-Arsip; ID internal aplikasi
  bukan nomor arsip resmi (D-038).
- Pemakaian `archive_reference` dan cara verifikasi arsip masih pending
  validasi.

---

## 13.2 `special_unlock_requests`

Menyimpan permintaan administratif untuk Special Unlock. Tabel ini terpisah dari
status bisnis Outreach dan dari `history_events`.

```text
id                         PK
outreach_id                FK -> outreaches.id
requester_id               FK -> users.id
requester_role_snapshot
reason
status                     PENDING | PROCESSING | REJECTED | COMPLETED | CANCELLED
admin_id                   NULL, FK -> users.id
processed_reason           NULL
created_at
updated_at
processed_at               NULL
```

### Rules

- `PENDING` dapat diproses Admin atau dibatalkan sebelum pemrosesan dimulai.
- Siklus normal: `PENDING` → `PROCESSING` → `COMPLETED` atau `REJECTED`.
- Pembatalan hanya diizinkan dari `PENDING`: `PENDING` → `CANCELLED`.
- `PROCESSING` tidak dapat dibatalkan.
- Admin adalah pelaksana, bukan approver terpisah atas permintaan ini.
- Admin juga dapat melakukan Special Unlock langsung tanpa membuat request.
- Permintaan tidak mengubah status bisnis Outreach menjadi status Special Unlock.
- Versi pertama tidak mengirim notifikasi khusus untuk request ini; Admin memakai
  antrean permintaan.
- Pembuatan/pemrosesan request harus mencegah double-submit dan menggunakan
  concurrency checks yang sesuai.
- Sediakan index untuk foreign key serta antrean, terutama `status` dan
  `created_at`; detail indeks mengikuti query aktual.

### Status invariants

```text
DRAFT                    → tidak memiliki versi pengajuan aktif
MENUNGGU_PENGESAHAN       → versi terbaru berstatus PENDING_APPROVAL
PERLU_PERBAIKAN           → versi terbaru berstatus RETURNED
SELESAI                   → versi terbaru berstatus APPROVED
```

Saat Special Unlock mengedit Outreach berstatus `SELESAI`, status tetap
`SELESAI` selama pengeditan administratif berlangsung. Ketika hasil koreksi
secara atomik diajukan sebagai versi baru, versi baru menjadi `PENDING_APPROVAL`
dan status Outreach menjadi `MENUNGGU_PENGESAHAN`.

**Pengecualian (D-031):** setelah Admin melakukan `Lock` pasca Special Unlock
tanpa membuat versi baru, Outreach berstatus `PERLU_PERBAIKAN` boleh memiliki
versi terbaru `APPROVED` atau `PENDING_APPROVAL`. `Lock` tersebut dicatat pada
`history_events` dengan event `SPECIAL_UNLOCK_LOCKED`. Versi terbaru tidak
diubah; submission berikutnya oleh Petugas membuat versi baru.

---

# 14. Relationship Map

## 14.1 Core relationships

```text
users 1 ─── N outreaches
outreach_sources 1 ─── N outreaches
outreach_types 1 ─── N outreaches
```

## 14.2 Related forms

```text
outreaches 1 ─── 0..1 mustahik_forms
mustahik_forms 1 ─── N mustahik_answers

outreaches 1 ─── 0..1 kifayah_forms
kifayah_forms 1 ─── N kifayah_answers
```

## 14.3 Current attachments and physical files

```text
outreaches 1 ─── N outreach_attachments
files 1 ─── N outreach_attachments
```

## 14.4 Historical versions

```text
outreaches 1 ─── N outreach_versions
outreach_versions 1 ─── N outreach_version_attachments
files 1 ─── N outreach_version_attachments
```

## 14.5 Approval

```text
outreach_versions 1 ─── 0..1 outreach_approvals
users 1 ─── N outreach_approvals
```

## 14.6 Signatures and templates

```text
files 1 ─── N signatures
users 1 ─── N signature_templates
files 1 ─── N signature_templates
files 1 ─── N stamp_templates

outreach_versions N ─── 1 document_template_versions
```

## 14.7 History, audit, notification, archive

```text
outreaches 1 ─── N history_events
outreach_versions 1 ─── N history_events      (optional link)
outreach_approvals 1 ─── N history_events     (optional link)

users 1 ─── N notifications
outreaches 1 ─── N notifications
outreach_versions 1 ─── N notifications
outreach_approvals 1 ─── N notifications      (optional link)

outreaches 1 ─── N archive_records
outreach_versions 1 ─── N archive_records

outreaches 1 ─── N special_unlock_requests
users 1 ─── N special_unlock_requests (requester)
users 1 ─── N special_unlock_requests (admin, optional)
```

---

# 15. Full Relationship Diagram

```text
                         ┌──────────────────┐
                         │      users       │
                         └────────┬─────────┘
                                  │
                    ┌─────────────┼──────────────┐
                    │             │              │
                    ▼             ▼              ▼
             ┌────────────┐ ┌────────────┐ ┌──────────────┐
             │ outreaches │ │ approvals  │ │ notifications│
             └─────┬──────┘ └──────┬─────┘ └──────┬───────┘
                   │                │              │
          ┌────────┼────────┐       │              │
          │        │        │       │              │
          ▼        ▼        ▼       ▼              ▼
     mustahik  kifayah  attachments approvals   history/audit
       │          │          │
       ▼          ▼          ▼
    answers     answers     files
                              │
                ┌─────────────┼──────────────┐
                ▼             ▼              ▼
           signatures   signature_templates stamp_templates
                              
                   outreaches
                       │
                       ▼
               outreach_versions
                 │       │       │
                 │       │       └──> document_template_versions
                 │       │
                 │       └──────────> outreach_version_attachments ──> files
                 │
                 └───────────────> approvals / history / notifications
                       │
                       ▼
                archive_records

users + outreaches ──> special_unlock_requests
```

Diagram di atas adalah logical ERD representation; detail constraint tetap
mengacu pada section table specification dan migration implementation.

---

# 16. Source × Form Persistence Rules

| Source | Outreach | Mustahik | Kifayah |
|---|---:|---:|---:|
| Baznas | Ya | Ya | Ya |
| CSR | Ya | Ya | Ya |
| Bangga Surabaya | Ya | Tidak | Tidak |

### Important distinction

```text
Sumber Outreach
    ↓
menentukan struktur/alur form

Jenis Outreach
    ↓
menentukan jenis intervensi
    ↓
ditampilkan pada dokumen Outreach
```

Semua kombinasi Source × Type tetap diperbolehkan pada baseline.

---

# 17. Historical Integrity Model

## 17.1 Current Data versus Version

```text
CURRENT
Outreach + related form answers + current attachments

               ↓ Finalisasi

HISTORICAL
outreach_versions
+ outreach_version_attachments
+ signature references
+ template reference
```

Current record boleh berubah setelah Return.
Historical version tidak berubah.

## 17.2 Version Example

```text
OUT-2026-00001
    │
    ├── Version 1 — PENDING_APPROVAL
    │        │
    │        └── RETURNED
    │
    └── Current data diperbaiki
             │
             └── Version 2 — PENDING_APPROVAL
                        │
                        └── APPROVED
```

Version 1 dan Version 2 tetap merupakan snapshot yang dapat direkonstruksi.

---

# 18. Document Reconstruction Boundary

Dokumen historis harus dapat direkonstruksi dari kombinasi:

```text
Historical Outreach Version
        +
Historical attachment references
        +
Document Template Version
        +
Relevant Mustahik/Kifayah snapshot context
        +
Signature references
        +
Approval template references
        ↓
Historical Document Reconstruction
```

PDF dibuat on-demand dan tidak wajib disimpan permanen sebagai source of truth.
Output untuk Baznas/CSR terdiri dari **tiga PDF terpisah**:
1. PDF Outreach, dengan halaman lampiran wajib/terkait ditempatkan setelah form
   Outreach utama;
2. PDF Mustahik;
3. PDF Kifayah.

Untuk Bangga Surabaya, output adalah PDF Outreach dengan halaman lampiran setelah
form Outreach utama; tidak ada PDF Mustahik atau Kifayah. Lampiran bukan sekadar
file yang terpisah dari output Outreach: halaman lampiran harus disertakan pada
PDF Outreach sesuai aturan di atas. Pengiriman ke E-Surat dilakukan manual pada
baseline, tanpa mengasumsikan integrasi API.

PDF tidak menjadi source of truth.

Perubahan template berikutnya tidak boleh membuat historical document otomatis
berubah.

---

# 19. File Lifecycle and Purge Rules

## 19.1 Upload lifecycle

```text
Client capture / select
        ↓
Server validation
        ↓
Content hash / duplicate check
        ↓
Optimize / compress
        ↓
Private storage
        ↓
files record
        ↓
Current / historical reference
```

## 19.2 Purge conditions

Physical file hanya dapat dipurge ketika:

- tidak memiliki current attachment reference;
- tidak memiliki historical version reference;
- tidak digunakan oleh signature/template reference;
- retention/policy mengizinkan purge.

`purged_at` digunakan sebagai lifecycle marker.

## 19.3 No automatic aggressive deletion

Sistem tidak melakukan auto-delete file hanya karena current attachment berubah.
Historical references harus diperiksa terlebih dahulu.

---

# 20. Delete and Retention Rules

| Entity | Normal delete | Baseline handling |
|---|---|---|
| User | Tidak | `INACTIVE` |
| Source | Tidak | `active=false` |
| Type | Tidak | `active=false` |
| Outreach | Tidak | Lifecycle/status |
| Current attachment relation | Tidak pada locked data | Controlled replace/delete |
| Historical version | Tidak | Immutable |
| Approval | Tidak | Immutable |
| History | Tidak | Append-only |
| Audit | Tidak | Append-only |
| Notification | Ya setelah expired | 30-day record retention |
| Physical file | Bersyarat | Purge only when unreferenced + policy |

Prosedur arsip manual ditetapkan pada D-038, tetapi retensi keseluruhan
Outreach belum dikunci oleh Kelurahan. Tidak ada auto-delete sebelum kebijakan
retensi jelas.

---

# 21. Index and Constraint Baseline

## 21.1 Explicit uniqueness

Minimal uniqueness yang sudah ditetapkan:

```text
users.email
outreach_sources.code
outreach_types.code
outreaches.draft_code
outreaches.outreach_number
mustahik_forms.outreach_id
mustahik_answers (mustahik_form_id, question_code)
kifayah_forms.outreach_id
kifayah_answers (kifayah_form_id, question_code)
outreach_attachments (outreach_id, file_id)
outreach_versions (outreach_id, version_number)
outreach_approvals.outreach_version_id
outreach_version_attachments (outreach_version_id, file_id)
signature_templates (head_user_id, version_number)
stamp_templates.version_number
files.storage_key                                   (D-043)
document_template_versions (template_key, version_number)   (D-043)
```

Tipe fisik, panjang kolom, dan index tambahan mengikuti
`docs/engineering/migration-design.md` yang disetujui pada D-043.

## 21.2 Important lookup indexes

Implementasi database sebaiknya menyediakan index yang sesuai untuk query utama,
terutama pada:

- `outreaches.petugas_id`;
- `outreaches.source_id`;
- `outreaches.type_id`;
- `outreaches.status`;
- `outreaches.outreach_date`;
- pencarian NIK sesuai kebutuhan query aplikasi;
- `history_events.outreach_id`;
- `notifications.user_id`, `notifications.created_at`;
- `special_unlock_requests.status`, `special_unlock_requests.created_at`;
- `special_unlock_requests.outreach_id`, `requester_id`, dan `admin_id`;
- foreign key fields secara umum.

Index detail yang belum ditulis sebagai migration contract harus mengikuti query
aktual saat implementasi dan tidak boleh mengubah domain model.

---

# 22. Foreign Key and Deletion Policy

Prinsip baseline:

```text
Parent deletion
      ↓
RESTRICT child references
```

Foreign key pada domain penting harus mencegah penghapusan parent yang masih
dibutuhkan oleh historical/current records.

Logical deactivation menggunakan status/active flag lebih diutamakan daripada
physical deletion untuk master data.

---

# 23. Transaction Boundaries

Database transaction penting untuk action yang mengubah business state secara
atomik.

## 23.1 Finalisasi

Transaksi bisnis utama harus mengamankan perubahan yang membentuk satu hasil
Finalisasi yang konsisten:

```text
BEGIN BUSINESS TRANSACTION
    ↓
Validate current state
    ↓
Version/concurrency check
    ↓
Create outreach_versions
    ↓
Create historical attachment references
    ↓
Update Outreach status
    ↓
Create history event
    ↓
COMMIT BUSINESS TRANSACTION
    ↓
Handle notification persistence/delivery separately
```

Pembuatan versi historis, referensi lampiran historis, perubahan status Outreach,
dan pencatatan history terkait harus berhasil atau gagal sebagai satu kesatuan.
Notifikasi bukan bagian dari transaksi bisnis utama: data notifikasi dibuat atau
diproses setelah commit melalui mekanisme terpisah. Kegagalan penyimpanan atau
pengiriman notifikasi tidak boleh membatalkan atau me-roll back Finalisasi yang
telah berhasil.

Physical file operations membutuhkan handling terpisah agar kegagalan file tidak
meninggalkan database reference yang tidak valid. Operasi file harus diselesaikan
atau divalidasi sesuai kebutuhan sebelum referensinya dikomit ke database.

Batas transaksi yang sepadan juga berlaku untuk Finalisasi Ulang, Return, dan
Approve: validasi status/versi, perubahan status, pembuatan versi atau keputusan,
serta history terkait harus konsisten. Untuk setiap aksi, penanganan notifikasi
dilakukan terpisah setelah transaksi bisnis berhasil di-commit.

## 23.2 Special Unlock

Special Unlock harus memakai transaction/concurrency boundary yang mengamankan
status Outreach, versi terbaru, audit/history yang relevan, serta perubahan data
admin. `edit_version` juga berlaku selama Admin mengedit.

Dua jalur setelah pengeditan:

- **Lock**: bila Petugas/Warga masih perlu melengkapi pekerjaan, kunci hasil edit
  dan pindahkan status ke `PERLU_PERBAIKAN`.
- **Lock & Ajukan untuk Pengesahan**: satu aksi atomik yang membuat versi
  historis baru, mengunci hasil, dan mengubah status menjadi
  `MENUNGGU_PENGESAHAN`.

Saat Special Unlock terhadap Outreach `SELESAI`, status tetap `SELESAI` selama
Admin masih mengedit. Jangan menyimpan status antara khusus untuk Special Unlock.
Request Special Unlock juga harus memiliki pencegahan double-submit.

## 23.3 Approval

Approval menggunakan gate:

```text
Authorize
    ↓
Check current version
    ↓
Render final PDF
    ↓
Success?
 ├─ No  → do not mark approval complete
 └─ Yes → persist approval + completed status
```

Jika render PDF gagal, persetujuan gagal dan status tetap
`MENUNGGU_PENGESAHAN`. `OUTREACH_COMPLETED` dikirim hanya setelah PDF berhasil
serta status menjadi `SELESAI`. Notifikasi diproses terpisah dan kegagalannya
tidak membatalkan transaksi bisnis.

Detail process/workflow berada pada `docs/architecture/workflow.md`.

---

# 24. Concurrency Data Rules

## 24.1 Current record concurrency

`edit_version` pada `outreaches` digunakan sebagai optimistic concurrency token.

Contoh:

```text
Client A reads edit_version = 7
Client B reads edit_version = 7

A saves → version 8
B saves with expected version 7
      ↓
CONFLICT
```

B tidak boleh overwrite data A secara diam-diam. Mekanisme optimistic concurrency
juga berlaku saat Admin melakukan Special Unlock.

Aksi yang wajib memiliki transaction-safe concurrency dan pencegahan double-submit:

- Finalisasi;
- Finalisasi Ulang;
- Return;
- Approve;
- Special Unlock;
- Lock & Ajukan untuk Pengesahan;
- Request Special Unlock.

## 24.2 Historical version concurrency

`outreach_versions.version_number` unik per Outreach. Creation harus terjadi
secara transaction-safe sehingga dua Finalisasi tidak menghasilkan version number
identik.

---

# 25. Pending Validation That Affects Database

Bagian berikut belum boleh dianggap final sebelum validasi Kelurahan atau
penetapan teknis yang memang belum tersedia:

1. Evidence dokumen sumber resmi label desil; label dan pemetaannya sudah
   ditetapkan pada D-019 (VAL-002).
2. Lampiran tambahan wajib berdasarkan Jenis Outreach (tidak memblokir
   Finalisasi pada prototype, D-033).
3. Batas ukuran dan jumlah file lampiran, serta tipe gambar yang diterima.
   Empat lampiran wajib harus berupa gambar (D-034).
4. Field Outreach tambahan bila ada.
5. Perilaku antrean saat pergantian Kepala Kelurahan.
6. Penggunaan stempel digital pada PDF.
7. Pemakaian nomor/reference arsip dan verifikasi arsip; prosedur arsip manual
   sudah ditetapkan pada D-038.
8. Provider hosting konkret.
9. Provider object/file storage konkret.
10. Provider backup eksternal konkret dan kebijakan backup setelah serah
    terima; backup manual mingguan oleh Admin pada prototype ditetapkan pada
    D-038.
11. Parameter final compression/optimization.
12. Kebijakan retensi data Outreach.
13. Format dan posisi cetak NIP Kepala pada blok pengesahan (D-041).

Kontrak JSON terperinci untuk `snapshot_data` belum ditetapkan. Jangan mengarang
struktur finalnya sebelum kebutuhan rekonstruksi snapshot dipastikan.

Hal-hal berikut **sudah diputuskan dan bukan lagi pending validation**: koreksi
setelah Finalisasi melalui Special Unlock; aturan template tanda tangan Kepala
untuk `RETURNED`/`APPROVED`; re-sign hanya ketika data form berubah (bukan hanya
lampiran); 25 pertanyaan Mustahik wajib dengan single-choice tanpa default,
automatic selection, atau scoring; aturan nilai Kifayah `0` versus `NULL`;
output Baznas/CSR berupa tiga PDF terpisah; PDF Outreach menyertakan halaman
lampiran setelah form utama; output Bangga Surabaya berupa PDF Outreach dengan
halaman lampiran; PDF dibuat on-demand, bukan disimpan permanen sebagai
source of truth; serta keputusan D-030, D-031, D-033, D-034, D-038, dan D-041.

Pending validation tidak boleh diubah menjadi hard-coded business rule tanpa
keputusan baru.

---

# 26. Implementation Boundary

Dokumen ini mendefinisikan **logical persistence model**. Implementasi Laravel
boleh menambahkan detail teknis seperti:

- migration timestamps;
- database engine-specific type choices;
- nullable definitions;
- length/precision;
- indexes tambahan;
- casting/ORM configuration;
- foreign-key naming;

selama tidak mengubah business meaning dan invariant yang ditetapkan dokumen ini.

Implementasi tidak boleh diam-diam:

- mengganti 22-table model menjadi struktur yang berbeda;
- menghapus historical versioning;
- memindahkan file menjadi BLOB tanpa keputusan desain baru;
- membuat automatic score Mustahik;
- membuat automatic selection Mustahik/Kifayah;
- menghapus referensi historical attachment;
- menjadikan PDF sebagai satu-satunya source of truth.

---

# 27. Canonical Cross-Document References

| Concern | Canonical source |
|---|---|
| Product requirements | `docs/product/PRD.md` |
| Project scope | `docs/product/scope.md` |
| Terminology | `docs/domain/glossary.md` |
| Outreach form | `docs/domain/forms/outreach.md` |
| Mustahik form | `docs/domain/forms/mustahik.md` |
| Kifayah form | `docs/domain/forms/kifayah.md` |
| Architecture | `docs/architecture/architecture.md` |
| Workflow | `docs/architecture/workflow.md` |
| Roles & permissions | `docs/architecture/roles-permissions.md` |
| Security | `docs/architecture/security.md` |
| UX | `docs/ux/design.md` |
| Prototype | `docs/prototype/prototype.md` |
| Source/reference forms | `docs/reference/` |

---

# 28. Database Integrity Checklist

Sebelum implementasi dianggap sesuai baseline, pastikan:

- [ ] 22 tabel domain tersedia sesuai baseline; tabel infrastruktur framework
      minimal dan di luar hitungan (D-030).
- [ ] `users.nip` tersedia untuk user KEPALA (D-041).
- [ ] `special_unlock_requests` memiliki foreign key, status lifecycle, indeks antrean, dan aturan transaksi.
- [ ] Master Source dan Type menggunakan unique code.
- [ ] Outreach memiliki `draft_code` dan `outreach_number` sesuai lifecycle.
- [ ] `NIK` bukan primary key.
- [ ] Mustahik/Kifayah hanya tersedia untuk Source Baznas/CSR.
- [ ] Mustahik memiliki 25 question codes baseline, wajib diisi, dan single-choice.
- [ ] Mustahik tidak memakai default selection, automatic selection, atau scoring.
- [ ] Kifayah membedakan `0` (tidak ada/tidak berlaku) dari `NULL` (belum diisi).
- [ ] Kategori pengeluaran Kifayah wajib diisi eksplisit; nilai kosong tidak
      otomatis dikonversi menjadi nol.
- [ ] Kifayah tidak memakai family member table pada v1.
- [ ] Current attachment dan historical attachment dipisahkan.
- [ ] Lampiran soft-deleted dapat direaktivasi tanpa membuat relasi duplikat.
- [ ] `special_unlock_requests` terhubung ke Outreach, requester, dan Admin.
- [ ] Status Outreach konsisten dengan status versi terbaru, dengan
      pengecualian `Lock` pasca Special Unlock (D-031).
- [ ] `head_signature_template_id` nullable untuk RETURNED dan wajib untuk APPROVED.
- [ ] `stamp_templates.version_number` unik dan maksimal satu template aktif.
- [ ] `archive_records.archived_at` nullable hingga arsip benar-benar dilakukan.
- [ ] Physical files tidak disimpan sebagai BLOB.
- [ ] Historical versions immutable.
- [ ] Approval memiliki maksimal satu decision per version.
- [ ] Signature references dapat direkonstruksi.
- [ ] Template versions immutable dan direferensikan oleh historical version.
- [ ] History dan Audit dipisahkan.
- [ ] Notification retention tidak menghapus business records.
- [ ] Normal delete terhadap Outreach tidak tersedia.
- [ ] Foreign-key restrictions mencegah kehilangan historical references.
- [ ] Optimistic concurrency tersedia pada current Outreach dan Special Unlock.
- [ ] Aksi sensitif dan Lock & Ajukan menggunakan transaction-safe double-submit protection.
- [ ] Kegagalan notifikasi tidak me-roll back business action.
- [ ] Output PDF sesuai source: Baznas/CSR menghasilkan Outreach (beserta
      halaman lampiran), Mustahik, dan Kifayah sebagai tiga PDF terpisah;
      Bangga Surabaya menghasilkan Outreach beserta halaman lampiran.
- [ ] Kegagalan render PDF pada approval membuat approval gagal; status tetap
      `MENUNGGU_PENGESAHAN` dan `OUTREACH_COMPLETED` tidak dikirim.
- [ ] Label desil mengikuti D-019; evidence sumber resmi tetap pending (VAL-002).
- [ ] Pending validation tidak di-hard-code sebagai keputusan final.

---

# 29. Status Dokumen

**Database Specification v1.1 — ACTIVE** (dengan amandemen D-030, D-031, dan
D-041).

Dokumen ini menjadi canonical database baseline untuk pengembangan prototype.
Perubahan requirement atau hasil validasi Kelurahan yang memengaruhi persistence
harus diperbarui terlebih dahulu pada dokumen ini dan dicatat pada project
decision/changelog sesuai proses dokumentasi proyek.
