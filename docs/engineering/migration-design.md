# Desain Migration — Database v1.1 (R1.1)

> **Status dokumen:** DRAFT — menunggu persetujuan pemilik proyek (roadmap R1.1)  
> **Sumber kebenaran:** `docs/architecture/database.md` (Database v1.1, 22 tabel domain, amandemen D-030, D-031, D-041)  
> **Penting:** Dokumen ini hanya menerjemahkan model logis ke tipe fisik MySQL/Laravel sesuai ruang yang diberikan `database.md` bagian 26 (tipe, nullable, panjang, index tambahan, penamaan FK). Tidak ada tabel, kolom, relasi, atau aturan bisnis baru. Persetujuan dokumen ini belum mengizinkan menjalankan migration (R1.2 memerlukan persetujuan terpisah).

## 1. Prinsip Fisik

| Aspek | Usulan | Dasar |
|---|---|---|
| Engine dan charset | InnoDB, `utf8mb4` / `utf8mb4_unicode_ci` | `setup.md` bagian 4 |
| Primary key | `id` BIGINT UNSIGNED auto increment (`$table->id()`) | Konvensi Laravel |
| Foreign key | `foreignId(...)->constrained()` dengan `ON DELETE RESTRICT` dan `ON UPDATE RESTRICT`; nama constraint bawaan Laravel | `database.md` bagian 2.5 dan 22 |
| Nilai enum | `VARCHAR` + PHP backed enum + validasi aplikasi, bukan tipe `ENUM` MySQL | Q-01 |
| Nominal uang (Rp) | `BIGINT UNSIGNED` nullable, rupiah tanpa desimal | Q-02 |
| Hitungan (jumlah orang/kendaraan) | `SMALLINT UNSIGNED` nullable | Nilai minimal 0 |
| Field form Outreach | Nullable seluruhnya, karena Draft boleh belum lengkap; kewajiban ditegakkan validasi Finalisasi | `database.md` bagian 5.1 |
| Pilihan Ya/Tidak | `BOOLEAN` nullable (`NULL` = belum diisi) | D-025, D-027 |
| Tabel immutable/append-only | Hanya `created_at` (tanpa `updated_at`) sesuai daftar kolom baseline | `database.md` |
| Soft delete | Hanya `outreach_attachments.deleted_at` | `database.md` bagian 8.2 |

## 2. Tabel Infrastruktur dan Migration Bawaan Starter Kit (D-030)

| Berkas bawaan di `web/database/migrations/` | Tindakan |
|---|---|
| `0001_01_01_000000_create_users_table.php` | Ditulis ulang menjadi tabel `users` sesuai bagian 3.1; tabel `password_reset_tokens` dan `sessions` dihapus dari berkas |
| `0001_01_01_000001_create_cache_table.php` | Dihapus (cache memakai driver file) |
| `0001_01_01_000002_create_jobs_table.php` | Dihapus (queue `sync`) |

Tabel infrastruktur yang tersisa hanya `migrations` milik Laravel. Kode bawaan yang memakai `remember_token`, `email_verified_at`, atau kolom `password` disesuaikan pada R1.3.

## 3. Spesifikasi Tabel

Notasi: **N** = nullable. Semua FK `RESTRICT`. Kolom yang tidak disebut tipe khususnya mengikuti baris tabel.

### 3.1 `users`

| Kolom | Tipe | N | Catatan |
|---|---|---|---|
| `name` | VARCHAR(255) | | |
| `email` | VARCHAR(255) | | UNIQUE |
| `password_hash` | VARCHAR(255) | | Hash bawaan Laravel (bcrypt) |
| `nip` | VARCHAR(18) | ✓ | D-041; diisi untuk KEPALA; validasi 18 digit angka diterapkan di aplikasi (Q-03) |
| `role` | VARCHAR(10) | | `PETUGAS`, `KEPALA`, `ADMIN` |
| `status` | VARCHAR(10) | | `ACTIVE`, `INACTIVE`; default `ACTIVE` |
| `last_login_at` | TIMESTAMP | ✓ | |
| `created_at`, `updated_at` | TIMESTAMP | ✓ | |

Index tambahan: `(role, status)` untuk pemeriksaan satu Kepala aktif.

### 3.2 `outreach_sources` dan 3.3 `outreach_types`

| Kolom | Tipe | N | Catatan |
|---|---|---|---|
| `code` | VARCHAR(50) | | UNIQUE; tidak dapat diubah setelah dibuat (D-040, ditegakkan aplikasi) |
| `name` | VARCHAR(100) | | |
| `active` | BOOLEAN | | Default `true` |
| `created_at`, `updated_at` | TIMESTAMP | ✓ | |

Seed baseline (BAZNAS, CSR, BANGGA_SURABAYA; lima Type) dijalankan melalui seeder R1.6, bukan migration.

### 3.4 `outreaches`

| Kolom | Tipe | N | Catatan |
|---|---|---|---|
| `draft_code` | VARCHAR(30) | | UNIQUE (Q-04) |
| `outreach_number` | VARCHAR(30) | ✓ | UNIQUE; diisi pada Finalisasi pertama (Q-04) |
| `source_id`, `type_id` | FK | | Ke master |
| `outreach_date` | DATE | | Wajib saat dibuat |
| `petugas_id` | FK `users` | | Pemilik |
| `nama` | VARCHAR(255) | ✓ | |
| `nik` | VARCHAR(32) | ✓ | Bukan identifier; data demo memakai token sintetis (Q-05) |
| `tempat_tgl_lahir_umur` | VARCHAR(255) | ✓ | Satu isian teks sesuai template |
| `agama`, `pekerjaan`, `status_hubungan_keluarga`, `status_tempat_tinggal`, `status_perkawinan`, `pendidikan_terakhir` | VARCHAR(100) | ✓ | Teks bebas (D-025) |
| `alamat_ktp`, `alamat_domisili` | TEXT | ✓ | |
| `status_kependudukan` | VARCHAR(3) | ✓ | `WNI`, `WNA` |
| `tahun_warga_surabaya` | VARCHAR(50) | ✓ | Q-06 |
| `jumlah_anggota_kk`, `jumlah_kk_dalam_rumah` | SMALLINT UNSIGNED | ✓ | |
| `penghasilan_klien` | BIGINT UNSIGNED | ✓ | Opsional (D-032) |
| `no_telp` | VARCHAR(30) | ✓ | |
| `desil_1_10` | TINYINT UNSIGNED | ✓ | 1–10 (D-019) |
| `status_kesejahteraan` | VARCHAR(50) | ✓ | Label turunan D-019, diisi aplikasi |
| `jaminan_kesehatan` | VARCHAR(255) | ✓ | Teks bebas |
| `merokok`, `bantuan_pernah_diterima`, `punya_hutang` | BOOLEAN | ✓ | Ya/Tidak |
| `bantuan_detail` | TEXT | ✓ | Wajib bila Ya (D-027) |
| `besar_hutang` | BIGINT UNSIGNED | ✓ | Berlaku bila `punya_hutang` |
| `penghasilan_kepala_keluarga`, `penghasilan_suami_istri`, `penghasilan_anak_1kk`, `penghasilan_lainnya` | BIGINT UNSIGNED | ✓ | |
| 13 kolom `pengeluaran_*` | BIGINT UNSIGNED | ✓ | |
| `luas_bangunan`, `jenis_lantai`, `jenis_dinding`, `jenis_atap`, `daya_listrik`, `sumber_air` | VARCHAR(100) | ✓ | Teks bebas |
| `mck_layak` | VARCHAR(20) | ✓ | `LAYAK`, `KURANG_LAYAK`, `TIDAK_LAYAK`, `TIDAK_ADA` (D-021) |
| 6 kolom `jumlah_*` kendaraan | SMALLINT UNSIGNED | ✓ | D-020 |
| `nama_kendaraan_lainnya` | VARCHAR(255) | ✓ | |
| `perhiasan`, `aset_lainnya` | TEXT | ✓ | Opsional |
| `upaya_dilakukan`, `harapan_klien`, `uraian_alasan_intervensi` | TEXT | ✓ | Narasi panjang |
| `status` | VARCHAR(25) | | `DRAFT`, `MENUNGGU_PENGESAHAN`, `PERLU_PERBAIKAN`, `SELESAI`; default `DRAFT` |
| `edit_version` | INT UNSIGNED | | Default 0; naik setiap update |
| `created_at`, `updated_at` | TIMESTAMP | ✓ | |

Index (bagian 21.2): `petugas_id`, `source_id`, `type_id`, `status`, `outreach_date`, `nik`; tambahan `(status, petugas_id)` untuk dashboard.

### 3.5 `mustahik_forms`

| Kolom | Tipe | N | Catatan |
|---|---|---|---|
| `outreach_id` | FK | | UNIQUE |
| `petugas_id` | FK `users` | | |
| `survey_date` | DATE | ✓ | |
| `kepala_keluarga_nama` | VARCHAR(255) | ✓ | |
| `kepala_keluarga_nik_no_kk` | VARCHAR(32) | ✓ | |
| `kepala_keluarga_alamat` | TEXT | ✓ | |
| `kepala_keluarga_rt`, `kepala_keluarga_rw` | VARCHAR(5) | ✓ | |
| `signature_id` | FK `signatures` | ✓ | Signature Petugas |
| `created_at`, `updated_at` | TIMESTAMP | ✓ | |

### 3.6 `mustahik_answers` dan 3.8 `kifayah_answers`

| Kolom | Tipe | N | Catatan |
|---|---|---|---|
| `mustahik_form_id` / `kifayah_form_id` | FK | | |
| `question_code` | VARCHAR(50) | | Kode baseline; divalidasi aplikasi |
| `answer` | JSON | | Single-choice: `{"option": "<kode opsi>"}` (Q-07) |
| `created_at`, `updated_at` | TIMESTAMP | ✓ | |

UNIQUE `(form_id, question_code)`.

### 3.7 `kifayah_forms`

| Kolom | Tipe | N | Catatan |
|---|---|---|---|
| `outreach_id` | FK | | UNIQUE |
| `income_head_family`, `income_other_members` | BIGINT UNSIGNED | ✓ | |
| 12 kolom `expense_*` | BIGINT UNSIGNED | ✓ | `NULL` ≠ `0` |
| `higher_education_count` | SMALLINT UNSIGNED | ✓ | Tanpa batas maksimum arbitrer selain batas tipe |
| `signature_id` | FK `signatures` | ✓ | Signature Warga |
| `created_at`, `updated_at` | TIMESTAMP | ✓ | |

### 3.9 `files`

| Kolom | Tipe | N | Catatan |
|---|---|---|---|
| `storage_disk` | VARCHAR(50) | | Disk privat |
| `storage_key` | VARCHAR(255) | | UNIQUE (tambahan, lihat Q-08) |
| `original_name` | VARCHAR(255) | | |
| `mime_type` | VARCHAR(100) | | Hasil deteksi isi file |
| `extension` | VARCHAR(20) | | |
| `size_bytes` | BIGINT UNSIGNED | | |
| `sha256_hash` | CHAR(64) | | Index (tidak unik: berkas sama dapat dipakai Outreach lain) |
| `width`, `height` | INT UNSIGNED | ✓ | Gambar saja |
| `purged_at` | TIMESTAMP | ✓ | |
| `created_at`, `updated_at` | TIMESTAMP | ✓ | |

### 3.10 `outreach_attachments`

| Kolom | Tipe | N | Catatan |
|---|---|---|---|
| `outreach_id`, `file_id` | FK | | UNIQUE `(outreach_id, file_id)` |
| `category` | VARCHAR(20) | | `KTP`, `KK`, `FOTO_KLIEN`, `FOTO_RUMAH`, `TAMBAHAN` |
| `title` | VARCHAR(255) | ✓ | |
| `description` | TEXT | ✓ | |
| `sort_order` | INT UNSIGNED | | Default 0 |
| `deleted_at` | TIMESTAMP | ✓ | Soft delete |
| `created_at`, `updated_at` | TIMESTAMP | ✓ | |

Index tambahan: `(outreach_id, category)`.

### 3.11 `outreach_version_attachments`

Kolom sama dengan 3.10 tanpa `deleted_at` dan `updated_at`; FK `outreach_version_id`; UNIQUE `(outreach_version_id, file_id)`.

### 3.12 `outreach_versions`

| Kolom | Tipe | N | Catatan |
|---|---|---|---|
| `outreach_id` | FK | | |
| `version_number` | INT UNSIGNED | | UNIQUE `(outreach_id, version_number)` |
| `status` | VARCHAR(20) | | `PENDING_APPROVAL`, `RETURNED`, `APPROVED` |
| `snapshot_data` | JSON | | Kontrak isi disetujui terpisah (R6.1) |
| `document_template_version_id` | FK | | Lihat Q-09 |
| `mustahik_signature_id`, `kifayah_signature_id` | FK `signatures` | ✓ | `NULL` untuk Bangga Surabaya |
| `created_by` | FK `users` | | Petugas atau Admin (Lock & Ajukan) |
| `created_at` | TIMESTAMP | ✓ | |
| `finalized_at` | TIMESTAMP | | |

### 3.13 `outreach_approvals`

| Kolom | Tipe | N | Catatan |
|---|---|---|---|
| `outreach_id` | FK | | |
| `outreach_version_id` | FK | | UNIQUE |
| `kepala_id` | FK `users` | | |
| `decision` | VARCHAR(10) | | `APPROVED`, `RETURNED` |
| `note` | TEXT | ✓ | Wajib bila `RETURNED` (aplikasi) |
| `head_signature_template_id` | FK | ✓ | Wajib bila `APPROVED` (aplikasi) |
| `stamp_template_id` | FK | ✓ | |
| `decided_at` | TIMESTAMP | | Sumber tanggal blok pengesahan (VAL-004) |
| `created_at` | TIMESTAMP | ✓ | |

### 3.14 `signatures`

`file_id` FK; `signer_type` VARCHAR(10) (`PETUGAS`, `WARGA`); `signer_user_id` FK N; `signer_name_snapshot` VARCHAR(255); `signed_at` TIMESTAMP; `created_at`.

### 3.15 `signature_templates` dan 3.16 `stamp_templates`

| Kolom | Tipe | N | Catatan |
|---|---|---|---|
| `file_id` | FK | | |
| `head_user_id` | FK `users` | | Hanya `signature_templates` |
| `version_number` | INT UNSIGNED | | UNIQUE `(head_user_id, version_number)` / UNIQUE `(version_number)` |
| `valid_from` | TIMESTAMP | | |
| `valid_until` | TIMESTAMP | ✓ | |
| `is_active` | BOOLEAN | | Satu aktif ditegakkan di service |
| `created_at` | TIMESTAMP | ✓ | |

### 3.17 `document_template_versions`

`template_key` VARCHAR(50); `version_number` INT UNSIGNED; `renderer_key` VARCHAR(100); `metadata` JSON N; `is_active` BOOLEAN; `created_at`. Index `(template_key, is_active)`; lihat Q-09 dan Q-10.

### 3.18 `history_events`

`outreach_id` FK; `actor_id` FK `users`; `actor_name_snapshot` VARCHAR(255); `actor_role_snapshot` VARCHAR(10); `event_type` VARCHAR(40) (sepuluh nilai termasuk D-031); `outreach_version_id` FK N; `approval_id` FK N; `description` TEXT N; `reason` TEXT N; `field_changes` JSON N; `metadata` JSON N; `created_at`. Index `(outreach_id, created_at)`.

### 3.19 `audit_logs`

`actor_id` FK `users`; `action` VARCHAR(100); `target_type` VARCHAR(50); `target_id` BIGINT UNSIGNED N (bukan FK polimorfik); `before_data`, `after_data`, `metadata` JSON N; `created_at`. Index `(target_type, target_id)`, `actor_id`, `created_at`.

### 3.20 `notifications`

`user_id` FK; `type` VARCHAR(30); `title` VARCHAR(255); `message` TEXT; `outreach_id` FK; `outreach_version_id` FK; `approval_id` FK N; `read_at` TIMESTAMP N; `created_at`. Index `(user_id, read_at)`, `created_at`.

Catatan: nama tabel sama dengan tabel kanal notifikasi bawaan Laravel. Aplikasi memakai model sendiri (mis. `InAppNotification`) dan tidak memakai kanal `database` bawaan.

### 3.21 `archive_records`

`outreach_id` FK; `outreach_version_id` FK; `external_system` VARCHAR(100); `archive_reference` VARCHAR(255) N; `archived_at` TIMESTAMP N; `verified_at` TIMESTAMP N; `created_at`.

### 3.22 `special_unlock_requests`

`outreach_id` FK; `requester_id` FK `users`; `requester_role_snapshot` VARCHAR(10); `reason` TEXT; `status` VARCHAR(15) (`PENDING`, `PROCESSING`, `REJECTED`, `COMPLETED`, `CANCELLED`; default `PENDING`); `admin_id` FK N; `processed_reason` TEXT N; `created_at`, `updated_at`; `processed_at` TIMESTAMP N. Index `(status, created_at)`, `outreach_id`, `requester_id`, `admin_id`.

## 4. Urutan Berkas Migration (R1.2)

FK melingkar `mustahik_forms`/`kifayah_forms` → `signatures` tidak ada (signature tidak menunjuk form), sehingga urutan berikut cukup:

1. `users` (penulisan ulang berkas bawaan)
2. `outreach_sources`, `outreach_types`
3. `files`
4. `signatures`, `signature_templates`, `stamp_templates`, `document_template_versions`
5. `outreaches`
6. `mustahik_forms`, `mustahik_answers`, `kifayah_forms`, `kifayah_answers`
7. `outreach_attachments`
8. `outreach_versions`, `outreach_version_attachments`
9. `outreach_approvals`
10. `history_events`, `audit_logs`, `notifications`
11. `archive_records`, `special_unlock_requests`

Setiap kelompok satu berkas migration dengan `down()` yang menghapus tabel dalam urutan terbalik. Migration dijalankan hanya setelah persetujuan R1.2.

## 5. Pertanyaan Keputusan

| ID | Pertanyaan | Usulan |
|---|---|---|
| Q-01 | Representasi enum | `VARCHAR` + PHP backed enum; menambah nilai (mis. event D-031) tidak memerlukan `ALTER` kolom |
| Q-02 | Tipe nominal uang | `BIGINT UNSIGNED` rupiah tanpa desimal |
| Q-03 | Validasi NIP | 18 digit angka, nullable, wajib untuk KEPALA aktif |
| Q-04 | Format kode | `draft_code` = `DRAFT-` + 12 karakter acak; `outreach_number` = `OUT-<tahun Tanggal Outreach>-<urut 5 digit>`, nomor urut per tahun dibuat dalam transaksi dengan penguncian baris |
| Q-05 | Validasi NIK | Prototype tidak memaksa 16 digit karena data demo memakai token sintetis; hanya wajib terisi saat Finalisasi |
| Q-06 | `tahun_warga_surabaya` | `VARCHAR(50)` agar dapat menampung tahun atau keterangan seperti "sejak lahir"; label template tidak membatasi ke tahun |
| Q-07 | Bentuk JSON `answer` | `{"option": "<kode opsi>"}` dengan kode opsi stabil per pertanyaan; label literal disimpan di kode aplikasi dan snapshot |
| Q-08 | UNIQUE `files.storage_key` | Ditambahkan sebagai constraint teknis (satu berkas fisik satu record); tidak disebut eksplisit di bagian 21.1 sehingga butuh persetujuan |
| Q-09 | Satu FK `document_template_version_id` untuk tiga PDF | Satu baris `document_template_versions` mewakili paket template per Source (`template_key` mis. `PACKAGE_BAZNAS_CSR`, `PACKAGE_BANGGA_SURABAYA`), dengan `metadata` mencatat versi template Outreach, Mustahik, dan Kifayah di dalamnya; tanpa kolom baru |
| Q-10 | UNIQUE `(template_key, version_number)` | Ditambahkan sebagai constraint teknis; butuh persetujuan karena tidak disebut di bagian 21.1 |
| Q-11 | Panjang `VARCHAR` | Sesuai tabel di atas; dapat disesuaikan tanpa mengubah makna |
