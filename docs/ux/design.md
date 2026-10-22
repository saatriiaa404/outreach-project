# UX Specification — Prototype v1.6

## Prinsip

UI mobile-first untuk Petugas, mudah direview Kepala, dan terkontrol untuk Admin. Status, ownership, dan lock selalu terlihat; penyembunyian tombol tidak menggantikan otorisasi server.

Prototype ini menggunakan data dummy dan ditujukan untuk demonstrasi lokal. Desain UI tidak mengubah Workflow v2.1 atau Database v1.1 (22 tabel).

## Layar Minimum

| Layar | Pengguna | Fungsi |
|---|---|---|
| Login | Semua | Masuk dengan akun dummy sesuai role. |
| Dashboard/daftar Outreach | Semua | Ringkasan sesuai scope akses dan status. |
| Create/Stepper | PETUGAS | Source, Type, Date, lalu form kondisional. |
| Review/Finalisasi | PETUGAS | Ringkasan, blocker, status kelengkapan, preview, dan konfirmasi finalisasi. |
| Approval Queue | KEPALA | Review read-only, Return, Approve, dan status proses PDF. |
| Special Unlock Queue | ADMIN | Request, Direct Unlock, Lock, dan Lock & Ajukan sesuai workflow. |
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

- Tampilkan empat lampiran dasar secara jelas: KTP, KK, foto warga, dan foto rumah.
- Draft boleh disimpan meskipun lampiran dasar belum lengkap. Finalisasi diblokir jika satu atau lebih lampiran dasar belum tersedia.
- Tampilkan blocker finalisasi secara spesifik dan arahkan Petugas ke bagian yang perlu diperbaiki.
- Jika unggah atau pemrosesan lampiran gagal, tampilkan pesan kesalahan yang jelas; jangan menampilkan lampiran sebagai berhasil tersimpan jika proses gagal.
- Persyaratan lampiran tambahan berdasarkan jenis bantuan ditandai `PENDING VALIDATION` sampai dikonfirmasi Kelurahan.

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
- Riwayat versi, tanda tangan, persetujuan, dan referensi lampiran historis harus tetap dapat ditelusuri sesuai hak akses.
- UI harus membedakan data operasional yang masih dapat diedit dari historical version yang tidak dapat diubah.

## Status, Hak Akses, dan Lock

- Status Outreach, pemilik/ownership, serta kondisi lock harus terlihat pada layar yang relevan.
- Kepala meninjau submission secara read-only dan menggunakan tindakan Return atau Approve sesuai workflow.
- Aksi khusus Admin hanya ditampilkan pada konteks dan state yang sesuai; server tetap wajib memvalidasi otorisasi dan transisi status.
- State locked harus menjelaskan bahwa pengeditan tidak tersedia dan, jika sesuai, menampilkan jalur tindakan yang diizinkan.
- Jangan menyimpulkan bahwa menyembunyikan tombol sudah cukup untuk mengamankan tindakan.

## Batasan

- Spesifikasi ini menjelaskan perilaku UI prototype, bukan bukti bahwa perilaku tersebut sudah diimplementasikan atau diuji.
- Detail field dan pilihan jawaban mengacu pada `docs/domain/forms/outreach.md`, `mustahik.md`, dan `kifayah.md`.
- Aturan alur status mengacu pada `docs/architecture/workflow.md`; hak akses mengacu pada `docs/architecture/roles-permissions.md` dan `security.md`.
- Keputusan operasional yang belum dikonfirmasi tetap `PENDING VALIDATION`.
- Dokumen ini tidak mengubah baseline Workflow v2.1, Database v1.1 (22 tabel), atau status implementation gate.
