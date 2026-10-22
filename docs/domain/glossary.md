# Glossary — v1.7

| Istilah | Definisi |
|---|---|
| Outreach | Record utama pendataan dan workflow. |
| Source Outreach | Menentukan struktur/alur form: `BAZNAS`, `CSR`, atau `BANGGA_SURABAYA`. |
| Jenis Outreach | Jenis intervensi; berbeda dari Source Outreach. |
| Draft | Record yang belum memiliki submission aktif. Draft boleh disimpan meskipun lampiran wajib belum lengkap. |
| Finalisasi | Submission resmi oleh Petugas yang membuat historical version. Finalisasi mensyaratkan empat lampiran dasar lengkap: KTP, KK, foto warga, dan foto rumah. |
| MENUNGGU_PENGESAHAN | Status saat submission terbaru menunggu keputusan Kepala. |
| Return | Keputusan Kepala untuk mengembalikan submission dengan catatan; status menjadi `PERLU_PERBAIKAN`. |
| Finalisasi Ulang | Pembuatan submission/version baru setelah perbaikan. |
| SELESAI | Status ketika version terbaru berhasil disetujui dan seluruh PDF wajib berhasil dibuat. |
| Special Unlock | Mode exception administratif, bukan business state. |
| Lock | Aksi Admin setelah unlock yang menghasilkan `PERLU_PERBAIKAN` bila pekerjaan Petugas/Warga masih diperlukan. |
| Lock & Ajukan | Aksi atomik Admin yang membuat version baru dan mengubah status menjadi `MENUNGGU_PENGESAHAN`. |
| Current record | Data operasional yang dapat berubah pada state editable. |
| Historical version | Snapshot submission immutable pada `outreach_versions`. |
| History | Timeline proses Outreach. |
| Audit | Catatan append-only untuk tindakan administratif atau sensitif. |
| PENDING VALIDATION | Hal yang belum dikonfirmasi/disetujui Kelurahan; bukan requirement final dan tidak boleh diperlakukan sebagai keputusan final. |
| Lampiran dasar | Empat lampiran wajib: KTP, KK, foto warga, dan foto rumah. Draft boleh disimpan tanpa semuanya, tetapi finalisasi diblokir jika ada yang belum lengkap. |
| PDF final | Kumpulan dokumen final yang tersedia setelah persetujuan berhasil dan seluruh PDF wajib berhasil dibuat. Untuk `BAZNAS`/`CSR`, hasilnya tiga PDF terpisah: PDF Outreach (termasuk halaman lampiran), PDF Mustahik, dan PDF Kifayah. Untuk `BANGGA_SURABAYA`, hasilnya PDF Outreach termasuk halaman lampiran. |
| Kegagalan pembuatan PDF | Jika PDF wajib gagal dibuat selama proses persetujuan, persetujuan gagal, status tetap `MENUNGGU_PENGESAHAN`, keputusan persetujuan tidak disimpan sebagai berhasil, dan notifikasi `OUTREACH_COMPLETED` tidak dikirim. |
| Nilai `0` (Kifayah) | Menyatakan tidak ada pengeluaran atau kategori tidak berlaku. Nilai ini harus diisi secara eksplisit; tidak boleh otomatis menggantikan input kosong. |
| Nilai `NULL` (Kifayah) | Menyatakan kategori pengeluaran belum diisi. Berbeda dari nilai `0`. |

## Catatan penggunaan

- Detail field, pilihan jawaban, dan aturan form tetap mengacu pada dokumen domain masing-masing (`outreach.md`, `mustahik.md`, dan `kifayah.md`).
- Istilah dalam glossary ini tidak mengubah baseline Workflow v2.1 atau Database v1.1 (22 tabel).
- Butir berstatus `PENDING VALIDATION` tetap perlu divalidasi; jangan mengubahnya menjadi keputusan final tanpa konfirmasi.
