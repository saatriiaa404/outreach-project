# UX Specification — Prototype v1.6

## Prinsip

UI mobile-first untuk Petugas, mudah direview Kepala, dan terkontrol untuk Admin. Status, ownership, dan lock selalu terlihat; penyembunyian tombol tidak menggantikan otorisasi server.

Prototype ini menggunakan data dummy dan ditujukan untuk demonstrasi lokal. Desain UI tidak mengubah Workflow v2.1 atau Database v1.1 (22 tabel).

Pustaka UI: Tailwind CSS + shadcn-vue melalui starter kit resmi Laravel untuk Vue (D-018). Revisi 27 Oktober 2026 menyelaraskan dokumen ini dengan D-015, D-016, D-018 sampai D-020, D-025, D-027, D-032 sampai D-034, D-036, dan D-038 sampai D-041 pada `docs/project-management/decisions.md`.

## Layar Minimum

| Layar | Pengguna | Fungsi |
|---|---|---|
| Login | Semua | Masuk dengan akun dummy sesuai role. |
| Dashboard/daftar Outreach | Semua | Ringkasan sesuai scope dan visibilitas role (D-039; lihat bagian Dashboard dan Pencarian). |
| Create/Stepper | PETUGAS | Source, Type, Date, lalu form kondisional. |
| Review/Finalisasi | PETUGAS | Ringkasan, blocker, status kelengkapan, preview, dan konfirmasi finalisasi. |
| Approval Queue | KEPALA | Review read-only, Return, Approve, dan status proses PDF. |
| Special Unlock Queue | ADMIN | Request, Direct Unlock, Lock, dan Lock & Ajukan sesuai workflow. Prototype (R8.1): antrean menampilkan permintaan terbuka (terlama di atas) dengan filter status; Admin memulai proses lalu menolak dengan alasan wajib, sedangkan permintaan yang diproses selesai otomatis saat Lock atau Lock & Ajukan (D-055). Petugas dan Kepala meminta serta membatalkan dari halaman Outreach. |
| Arsip | ADMIN | Penandaan status arsip manual dan catatan referensi, tanpa integrasi E-Surat/E-Arsip (D-038). |
| Master Source/Type | ADMIN | Menambah dan menonaktifkan Source/Type; `code` tampil read-only setelah dibuat (D-040). |
| Manajemen user dan template | ADMIN | Akun dummy, status aktif, NIP Kepala (D-041), template tanda tangan Kepala, dan template stempel. Tidak ada registrasi publik (D-014). |
| History/Notification | Semua | Timeline dan notifikasi in-app sesuai resource serta hak akses. |

## Stepper Petugas

1. Identitas Outreach
2. Data Klien
3. Keluarga
4. Rumah & Aset
5. Narasi
6. Mustahik — BAZNAS/CSR saja
7. Kifayah — BAZNAS/CSR saja
8. Lampiran
9. Tanda Tangan — BAZNAS/CSR saja
10. Review

Desktop memakai vertical stepper; mobile memakai `Langkah X dari N`. Status step: aktif, selesai, incomplete required, atau belum disentuh. Draft boleh incomplete; Finalisasi tidak.

## Validasi dan Lampiran

- Tampilkan empat lampiran dasar secara jelas: KTP, KK, Foto Klien, dan Foto Rumah (D-015). Keempatnya hanya menerima gambar (D-034).
- Draft boleh disimpan meskipun lampiran dasar belum lengkap. Finalisasi diblokir jika satu atau lebih lampiran dasar belum tersedia.
- Tampilkan blocker finalisasi secara spesifik dan arahkan Petugas ke bagian yang perlu diperbaiki.
- Jika unggah atau pemrosesan lampiran gagal, tampilkan pesan kesalahan yang jelas; jangan menampilkan lampiran sebagai berhasil tersimpan jika proses gagal.
- Persyaratan lampiran tambahan berdasarkan jenis bantuan ditandai `PENDING VALIDATION` sampai dikonfirmasi Kelurahan. Lampiran tambahan tidak memblokir Finalisasi dan tidak ditampilkan sebagai blocker (D-033).
- Lampiran berformat PDF ditandai bahwa pada PDF Outreach ia muncul sebagai halaman rujukan berisi nama berkas, bukan halaman yang dicetak ulang (D-034).

## Field Formulir

- **Tempat dan Tanggal Lahir / Umur:** tiga isian: Tempat lahir, Tanggal lahir (isian tanggal seperti Google Form: ketik dd/mm/yyyy atau pilih dari kalender), dan Umur read-only yang terisi otomatis terhadap Tanggal Outreach (D-045).
- **Status Kesejahteraan:** pilihan desil 1–10; label pasangannya tampil otomatis dan tidak dapat diketik terpisah (D-019).
- **Kendaraan:** input angka per jenis (sepeda angin, sepeda listrik, becak, sepeda motor, roda 4, lainnya) tanpa auto-fill; nama kendaraan lainnya wajib bila jumlahnya lebih dari 0 (D-020).
- **Fasilitas MCK:** pilihan Layak, Kurang layak, Tidak layak, Tidak ada (D-021).
- **Opsi formulir:** dropdown hanya bila opsinya tertulis pada PDF atau ketentuan yang disetujui; selain itu teks bebas (D-025). Status Kependudukan memakai `WNI`/`WNA`.
- **Bantuan yang pernah diterima:** pilihan Ya/Tidak; uraian teks bebas tampil dan wajib bila Ya (D-027).
- **Penghasilan klien sebulan dan Pendidikan terakhir:** ditandai opsional; tidak muncul sebagai blocker Finalisasi (D-032).
- **Identitas Kifayah:** Nama, NIK, Alamat (Alamat Domisili), dan No. HP tampil read-only dari Outreach; Kelurahan dan Kecamatan dari konstanta konfigurasi (D-036).
- **Header Mustahik:** Nama Petugas dan wilayah tampil read-only; No. Telepon HP tampil dari No. telp Outreach dengan keterangan bahwa itu kontak Outreach (D-036).

## Dashboard dan Pencarian

- PETUGAS: daftar Outreach miliknya per status.
- KEPALA: antrean `MENUNGGU_PENGESAHAN` dan daftar `SELESAI`. Draft tidak ditampilkan; `PERLU_PERBAIKAN` hanya menampilkan status dan catatan Return pada riwayat (D-039). Prototype (R7.3): daftar Kepala dibuka pada antrean dengan pengajuan terlama di atas; filter status tersedia untuk semua peran.
- Halaman tinjau Kepala menampilkan versi yang diajukan, bukan data yang sedang berjalan. Return membuka dialog dengan catatan wajib; Petugas melihat catatan Return terbaru di langkah pengisian dan Review selama `PERLU_PERBAIKAN`.
- ADMIN: semua Outreach per status, permintaan Special Unlock, dan status arsip.
- Pencarian: nama, NIK, ID internal, Source, Type, status, dan rentang tanggal. Hasil selalu dibatasi scope role; NIK tidak dipakai sebagai identifier URL.

## State Tampilan

- Autosave: `IDLE`, `SAVING`, `SAVED`, `FAILED`, `CONFLICT`, `CONNECTION_LOST`. State ini menunjukkan status penyimpanan saat ini, bukan bukti bahwa persistensi simpan–tutup–buka kembali telah lulus pengujian.
- PDF: preview tersedia, generating, berhasil, atau gagal.
- Approval: menunggu pengesahan, berhasil disetujui, dikembalikan untuk perbaikan, atau gagal diproses.
- Empty, loading, validation error, access denied, conflict, dan locked.
- Label `PENDING VALIDATION` untuk keputusan operasional yang belum disetujui.

## Persetujuan dan PDF

- Saat Kepala memilih Approve, sistem harus memastikan seluruh PDF wajib berhasil dibuat sebelum persetujuan dinyatakan berhasil.
- Jika pembuatan PDF wajib gagal, tampilkan pesan kegagalan yang jelas; jangan tampilkan persetujuan sebagai berhasil. Status tetap `MENUNGGU_PENGESAHAN`, keputusan persetujuan tidak disimpan sebagai berhasil, dan notifikasi `OUTREACH_COMPLETED` tidak dikirim.
- Untuk BAZNAS/CSR, tampilkan status tiga PDF terpisah: PDF Outreach (termasuk halaman lampiran), PDF Mustahik, dan PDF Kifayah.
- Untuk BANGGA_SURABAYA, tampilkan status PDF Outreach termasuk halaman lampiran.
- Tampilkan error dan langkah pemulihan yang relevan tanpa menyiratkan bahwa PDF yang gagal sudah tersedia.

## Tanda Tangan, Versi, dan Perubahan

- Perubahan data formulir setelah tanda tangan atau persetujuan harus mengikuti alur pembaruan tanda tangan dan persetujuan yang relevan.
- Perubahan lampiran saja tidak otomatis mewajibkan tanda tangan ulang.
- Penerapan prototype untuk workflow 16.4 sebelum Finalisasi: tanda tangan Petugas dihapus bila isian Mustahik atau No. telp Outreach berubah; tanda tangan Warga dihapus bila isian Kifayah atau Nama, NIK, Alamat Domisili, atau No. telp Outreach berubah (data yang tercetak pada form yang ditandatangani). Menyimpan data yang sama tidak menghapus tanda tangan. Halaman Mustahik dan Kifayah memperingatkan bila form sudah ditandatangani.
- Signature pad (D-049) menyediakan tombol "Ulangi" dan "Batalkan goresan terakhir"; tanda tangan selalu dibuat baru untuk setiap Outreach.
- Riwayat versi, tanda tangan, persetujuan, dan referensi lampiran historis harus tetap dapat ditelusuri sesuai hak akses.
- UI harus membedakan data operasional yang masih dapat diedit dari historical version yang tidak dapat diubah.

## Status, Hak Akses, dan Lock

- Status Outreach, pemilik/ownership, serta kondisi lock harus terlihat pada layar yang relevan.
- Kepala meninjau submission secara read-only dan menggunakan tindakan Return atau Approve sesuai workflow.
- Aksi khusus Admin hanya ditampilkan pada konteks dan state yang sesuai; server tetap wajib memvalidasi otorisasi dan transisi status.
- ADMIN tidak memiliki tombol membuat Outreach dan tidak memiliki Draft. Edit data oleh ADMIN hanya tersedia selama Special Unlock, dengan isian alasan wajib; ADMIN tidak memiliki aksi Approve atau Return (D-016).
- PETUGAS tidak memiliki aksi unduh atau cetak PDF final (D-040).
- State locked harus menjelaskan bahwa pengeditan tidak tersedia dan, jika sesuai, menampilkan jalur tindakan yang diizinkan.
- Jangan menyimpulkan bahwa menyembunyikan tombol sudah cukup untuk mengamankan tindakan.

## Batasan

- Spesifikasi ini menjelaskan perilaku UI prototype, bukan bukti bahwa perilaku tersebut sudah diimplementasikan atau diuji.
- Detail field dan pilihan jawaban mengacu pada `docs/domain/forms/outreach.md`, `mustahik.md`, dan `kifayah.md`.
- Aturan alur status mengacu pada `docs/architecture/workflow.md`; hak akses mengacu pada `docs/architecture/roles-permissions.md` dan `security.md`.
- Keputusan operasional yang belum dikonfirmasi tetap `PENDING VALIDATION`.
- Dokumen ini tidak mengubah baseline Workflow v2.1, Database v1.1 (22 tabel), atau status implementation gate.
