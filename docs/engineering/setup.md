# Setup Lingkungan Lokal — Windows

> **Status dokumen:** DRAFT — dibuat dari hasil verifikasi lingkungan pemilik proyek (23 Oktober 2026)  
> **Rujukan:** D-024 pada `docs/project-management/decisions.md`; task ENV-001 dan DOC-003  
> **Penting:** Dokumen ini tidak membuka gate implementasi dan tidak mengotorisasi bootstrap, schema, migration, atau seed. Langkah yang menyentuh database ditandai dan dijalankan setelah gate dibuka.

## 1. Tujuan dan Batas

Prototype berjalan lokal, non-produksi, pada satu komputer Windows, dengan data dummy. Hosting, domain, dan penggunaan LAN berada di luar lingkup. Dokumen ini mencatat alat yang terverifikasi dan cara memeriksanya, bukan keputusan arsitektur baru.

## 2. Versi yang Terverifikasi

Dibuktikan dari keluaran perintah pemilik proyek pada 23 Oktober 2026.

| Komponen | Versi / kondisi |
|---|---|
| Paket lingkungan | Laragon di `D:\laragon` (EULA telah diperiksa pemilik proyek; Herd tidak dipakai) |
| PHP | 8.5.12 aktif; 8.3.36 tersedia sebagai cadangan |
| Composer | 2.10.3 |
| Node.js dan npm | v24.22.1 dan 11.19.1 |
| MySQL | 8.4.3; server berjalan dan dapat diakses sebagai `root` |
| Git | Git for Windows, terpasang terpisah dari Laragon |
| Ekstensi PHP | `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `hash`, `intl`, `mbstring`, `mysqlnd`, `openssl`, `pcre`, `PDO`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `zip` |
| Claude | Claude Desktop (tab Code) terpasang; verifikasi sesi pada task ENV-002 |

Laravel 13 mendukung PHP 8.3 sampai 8.5 menurut sumber yang diperiksa. Persyaratan resmi dan kompatibilitas dependensi diverifikasi saat bootstrap (BOOT-001). Jika dependensi menolak PHP 8.5, beralih ke 8.3 melalui Laragon dan catat perubahannya di sini.

## 3. Pengecekan Lingkungan

Jalankan di PowerShell biasa (bukan Administrator), mulai dari folder `project/`:

```powershell
php -v
composer -V
node -v
npm -v
mysql --version
php -m | Select-String -Pattern "^(dom|filter|hash|pcre|pdo|pdo_mysql|session|zip|mysqlnd|mbstring|openssl|fileinfo|gd|curl|xml|tokenizer|ctype|intl)$"
mysql -u root -e "SELECT VERSION();"
```

Hasil yang diharapkan: semua versi muncul seperti tabel di atas, ke-18 ekstensi tampil, dan MySQL menjawab `8.4.3`. Server MySQL harus sudah dinyalakan lewat tombol Start di Laragon.

## 4. MySQL Lokal (jalankan setelah gate dibuka)

Nama di bawah adalah usulan awal dan dapat disesuaikan. Password hanya ditulis pada file `.env` lokal yang tidak di-commit; jangan menuliskannya di dokumen, kode, atau pesan commit.

```sql
CREATE DATABASE outreach_prototype CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'outreach_app'@'localhost' IDENTIFIED BY '<password-lokal>';
GRANT ALL PRIVILEGES ON outreach_prototype.* TO 'outreach_app'@'localhost';
FLUSH PRIVILEGES;
```

Perintah ini hanya menyiapkan database kosong. Tabel, kolom, dan migration mengikuti Database v1.1 dan baru dibuat setelah persetujuan eksplisit.

## 5. Pengaturan PHP untuk Upload Lampiran

Batas ukuran lampiran belum diputuskan (`database.md` bagian 25 butir 3), sehingga nilai final ditunda. Yang perlu dipastikan saat bootstrap:

- Periksa dan naikkan dari bawaan: `upload_max_filesize`, `post_max_size`, `max_file_uploads`, dan `memory_limit` pada `php.ini` milik PHP aktif.
- Pastikan perubahan berlaku pada PHP yang dipakai web server, lalu mulai ulang layanan Laragon.
- Verifikasi dengan `php -i | Select-String "upload_max_filesize|post_max_size"`.
- Batas ini adalah batas teknis aplikasi, bukan persyaratan Kelurahan.

## 6. Masalah Umum

| Gejala | Penyebab dan penanganan |
|---|---|
| `npm` ditolak PowerShell, "running scripts is disabled" atau "not digitally signed" | Kebijakan eksekusi skrip. Jalankan `Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy RemoteSigned`, lalu `Get-ChildItem D:\laragon\bin\nodejs -Recurse -Filter *.ps1 \| Unblock-File`. Alternatif sementara: `npm.cmd`. |
| `php`, `composer`, atau `node` tidak dikenali | PATH Windows belum memuat Laragon. Tambahkan Laragon ke PATH, lalu tutup dan buka lagi PowerShell. |
| Ekstensi `zip` tidak muncul | Aktifkan lewat menu Laragon (PHP, Extensions) atau pasang `extension=zip` pada `php.ini`, lalu cek ulang dengan `php -m`. |
| `mysql` tidak bisa terhubung | Server belum berjalan. Tekan Start pada Laragon, lalu ulangi pengecekan. |
| `git push` pertama gagal "no upstream branch" | Jalankan `git push --set-upstream origin main` sekali. |
| `desktop.ini` ikut zip atau Git | Tambahkan `desktop.ini` ke `.gitignore`, jalankan `git rm --cached`, dan gunakan `git archive` untuk berbagi snapshot. |

## 7. Menghubungkan Claude Code ke Folder Proyek

1. Buka Claude Desktop, login dengan akun berbayar, lalu pilih tab **Code**.
2. Pilih sesi **Local** dan folder `C:\outreach-project\Project`.
3. Setujui impor `@AGENTS.md` pada `CLAUDE.md` bila muncul dialog persetujuan.
4. Gunakan mode perencanaan untuk audit kesiapan; mode yang meminta izin untuk tindakan yang mengubah file.
5. Jalankan satu sesi sekali waktu. Karena Desktop dapat memakai worktree Git, periksa lokasi perubahan melalui tampilan diff dan `git status` sebelum commit.
6. Folder checkpoint (`../checkpoint/`) berada di luar repo; berikan izin akses saat diminta.
7. Jangan menjalankan Claude Code atau PowerShell sebagai Administrator.

## 8. Keamanan dan Git

- File `.env`, kunci, dan password tidak boleh di-commit; pastikan masuk `.gitignore`.
- Gunakan data dummy. Jangan memasukkan data warga asli ke repository, database prototype, atau lampiran demo.
- Repository GitHub bersifat private. `git push`, force-push, dan perubahan riwayat Git hanya atas persetujuan eksplisit pemilik proyek (lihat `AGENTS.md`).

## 9. Perubahan Dokumen Ini

Perubahan versi alat, kebijakan PHP, atau pengaturan upload dicatat pada dokumen ini. Bila memengaruhi keputusan, catat juga di `decisions.md`.
