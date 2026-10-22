# Product Requirements — Prototype v1.6

## 1. Problem dan Tujuan

Kelurahan Manukan Kulon memerlukan pendataan Outreach digital agar pengisian data di lapangan, pengelolaan lampiran, review, pengesahan, dan pembuatan dokumen administratif tidak lagi bergantung pada formulir kertas serta input ulang di kantor.

Prototype harus membuktikan alur utama tersebut secara lokal menggunakan data dummy. Prototype ditujukan untuk evaluasi kebutuhan dan alur kerja, bukan untuk langsung digunakan sebagai sistem produksi.

Tujuan prototype:
- Menguji pembuatan dan penyimpanan data Outreach.
- Menguji lampiran, tanda tangan, review, pengesahan, dan pembuatan PDF.
- Menguji pembatasan akses berdasarkan role dan scope.
- Menjaga riwayat versi dan audit agar perubahan data dapat ditelusuri.
- Mengidentifikasi kebutuhan yang masih memerlukan validasi pihak Kelurahan.

## 2. Batas Prototype

- Lingkungan awal: lokal, non-produksi, data dummy, dan satu komputer.
- Hosting/domain bukan prasyarat untuk prototype awal.
- Penggunaan melalui LAN oleh 1–5 pengguna merupakan evaluasi terpisah, bukan asumsi implementasi awal.
- Authentication menggunakan akun dummy, tetapi pemeriksaan hak akses tetap harus diterapkan pada setiap action yang dilindungi.
- Prototype tidak menetapkan atau menghitung kelayakan bantuan secara otomatis.
- Persyaratan yang belum terverifikasi harus ditandai `PENDING VALIDATION`, bukan dianggap sebagai keputusan final.

## 3. Role dan Akses Tingkat Tinggi

Prototype menggunakan tiga role:

- **PETUGAS** — membuat dan mengelola Outreach sesuai scope miliknya, mengisi formulir, mengelola lampiran, menyimpan draft, dan mengajukan data untuk pengesahan.
- **KEPALA** — memeriksa antrean pengesahan, mengembalikan data dengan catatan perbaikan, atau menyetujui data melalui alur approval yang berlaku.
- **ADMIN** — menjalankan fungsi administrasi yang ditetapkan, termasuk Special Unlock secara terkendali. ADMIN tidak memiliki hak untuk menyetujui Outreach.

Detail permission, scope, dan aturan transisi status mengikuti `docs/architecture/roles-permissions.md` serta `docs/architecture/workflow.md`. Ringkasan role ini tidak menggantikan aturan terperinci tersebut.

## 4. Requirement Fungsional

### 4.1 Membuat dan Mengisi Outreach

- Pengguna memulai Outreach dengan memilih `Source`, `Type`, dan `Date`.
- Field formulir berikutnya ditampilkan sesuai source dan type yang dipilih.
- Data dapat disimpan sebagai draft dan dilanjutkan kembali.
- Autosave harus memberikan umpan balik yang jelas ketika penyimpanan berhasil atau gagal.
- Optimistic concurrency harus mencegah perubahan yang saling menimpa tanpa terdeteksi.
- Data yang belum lengkap boleh disimpan sebagai draft, tetapi tidak boleh melewati syarat finalisasi.

### 4.2 Formulir Berdasarkan Jenis Bantuan

- **Baznas/CSR:** menggunakan formulir Outreach, Mustahik, dan Kifayah sesuai alur yang ditetapkan.
- **Bangga Surabaya:** menggunakan formulir Outreach; formulir Mustahik dan Kifayah tidak diterapkan.
- Form Mustahik memiliki 25 pertanyaan wajib dengan label dan pilihan yang mengikuti dokumen sumber. Tidak boleh ada pilihan default, pemilihan otomatis, scoring, atau mesin penentu kelayakan.
- Semua kategori pengeluaran Kifayah harus diisi secara eksplisit. Nilai `0` berarti tidak ada pengeluaran/tidak berlaku; `NULL` berarti belum diisi. Field kosong tidak boleh otomatis diubah menjadi nol.
- Input numerik Kifayah harus menerima nilai nonnegatif dan menolak nilai negatif atau nonnumerik.
- Tahun pada PDF Kifayah diambil dari tanggal Outreach yang tersimpan.
- Tidak boleh diberlakukan batas arbitrer jumlah anggota keluarga yang menempuh pendidikan tinggi tanpa dasar persyaratan yang terverifikasi.

Detail field, label, dan aturan formulir mengikuti dokumen domain terkait. Hal yang masih membutuhkan konfirmasi Kelurahan atau verifikasi dokumen sumber tetap berstatus `PENDING VALIDATION`.

### 4.3 Lampiran

- Empat lampiran dasar wajib untuk finalisasi: **KTP, KK, foto penerima, dan foto rumah**.
- Draft boleh disimpan meskipun lampiran wajib belum lengkap. Finalisasi harus ditolak dengan pesan yang menjelaskan lampiran yang masih kurang.
- Lampiran tambahan berdasarkan jenis bantuan dapat didukung pada prototype; daftar persyaratan resminya masih memerlukan validasi Kelurahan.
- Foto dikompresi secara otomatis sesuai hasil pengujian prototype.
- Jika pemrosesan atau penyimpanan lampiran gagal, sistem harus menampilkan kesalahan yang jelas dan tidak boleh diam-diam menghilangkan lampiran.
- Batas ukuran unggahan dan parameter kompresi ditetapkan setelah pengujian prototype, bukan diasumsikan sebelum pengujian.

### 4.4 Draft, Finalisasi, Review, dan Pengesahan

- Finalisasi membuat submission version baru dan mengubah status menjadi `MENUNGGU_PENGESAHAN`.
- Jika data dikembalikan oleh KEPALA, status menjadi `PERLU_PERBAIKAN` dan catatan perbaikan harus tersedia bagi PETUGAS.
- Finalisasi ulang setelah perbaikan membuat version baru; versi terdahulu tetap dipertahankan.
- KEPALA dapat mengembalikan data dengan catatan atau menyetujui data melalui alur yang ditentukan.
- Approval hanya berhasil jika seluruh PDF final yang diwajibkan berhasil dibuat.
- Jika pembuatan PDF yang diwajibkan gagal, approval harus gagal, alasan kegagalan ditampilkan, status tetap `MENUNGGU_PENGESAHAN`, dan notifikasi `OUTREACH_COMPLETED` tidak dikirim.
- ADMIN tidak boleh menyetujui Outreach.

### 4.5 Special Unlock ADMIN

- Special Unlock hanya boleh dilakukan melalui alur terkendali yang ditentukan dalam Workflow dan permission.
- Alur yang didukung mencakup request/direct unlock, controlled edit, serta tindakan `Lock` dan `Lock & Ajukan`.
- Tindakan dan perubahan yang dilakukan melalui Special Unlock harus tercatat dalam history/audit.
- Special Unlock tidak boleh menjadi jalan pintas untuk melewati pemeriksaan akses, versioning, atau alur pengesahan.

### 4.6 PDF dan Dokumen Final

- Untuk **Baznas/CSR**, sistem menghasilkan tiga PDF terpisah:
  1. PDF Outreach, dengan halaman lampiran wajib setelah halaman utama formulir Outreach.
  2. PDF Mustahik.
  3. PDF Kifayah.
- Untuk **Bangga Surabaya**, sistem menghasilkan PDF Outreach dengan halaman lampiran setelah halaman utama formulir Outreach.
- PDF harus dapat dipreview sebelum proses pengesahan sesuai alur yang ditetapkan.
- Kegagalan pembuatan salah satu PDF yang diwajibkan menggagalkan approval; sistem tidak boleh menandai Outreach sebagai `SELESAI` atau mengirim notifikasi `OUTREACH_COMPLETED`.
- Format PDF mengikuti template sumber yang tersedia. Ketidakpastian atau kebutuhan format yang belum dikonfirmasi harus dicatat sebagai `PENDING VALIDATION`.

### 4.7 Tanda Tangan, Versi, History, dan Audit

- Prototype mendukung tanda tangan, preview, historical version, history/audit, dan notifikasi in-app sesuai Workflow.
- Historical version harus immutable.
- Perubahan data formulir harus mempertahankan snapshot versi terdahulu dan memicu pembaruan tanda tangan serta alur pengesahan yang relevan.
- Perubahan lampiran saja tidak otomatis mewajibkan tanda tangan ulang.
- Perubahan pada lampiran atau formulir saat ini tidak boleh mengubah snapshot versi lama, termasuk referensi lampiran yang terkait dengan versi tersebut.
- Perubahan status dan action penting harus dapat ditelusuri melalui history/audit.

### 4.8 Persistence dan Pemulihan

- Data harus tetap tersedia setelah aplikasi ditutup dan dibuka kembali.
- Uji persistence minimal mencakup simpan, tutup aplikasi, buka kembali, lalu verifikasi data dan lampiran.
- Backup mingguan mencakup database dan seluruh berkas lampiran.
- Uji restore dilakukan setelah alur utama prototype stabil dan sebelum serah terima.

## 5. Acceptance Criteria

Prototype memenuhi kriteria penerimaan berikut jika:

1. PETUGAS dapat membuat Outreach, menyimpan draft, menutup aplikasi, lalu membuka kembali data yang tersimpan.
2. Field formulir mengikuti source dan type yang dipilih.
3. Draft dapat disimpan ketika belum lengkap, tetapi finalisasi diblokir jika empat lampiran dasar belum tersedia.
4. Finalisasi menghasilkan submission version baru dengan status `MENUNGGU_PENGESAHAN`.
5. Return oleh KEPALA menghasilkan status `PERLU_PERBAIKAN` dan menyimpan catatan perbaikan.
6. Finalisasi ulang menghasilkan version baru tanpa mengubah historical version sebelumnya.
7. Approval hanya menghasilkan status `SELESAI` jika semua PDF yang diwajibkan berhasil dibuat.
8. Jika PDF wajib gagal dibuat, status tetap `MENUNGGU_PENGESAHAN`, alasan kegagalan ditampilkan, dan notifikasi `OUTREACH_COMPLETED` tidak dikirim.
9. Baznas/CSR menghasilkan PDF Outreach, Mustahik, dan Kifayah; Bangga Surabaya hanya menghasilkan PDF Outreach.
10. Historical version dan referensi lampiran versi lama tidak berubah ketika data versi saat ini diperbarui.
11. Perubahan formulir memicu pembaruan tanda tangan dan alur pengesahan yang relevan; perubahan lampiran saja tidak otomatis memicu tanda tangan ulang.
12. PETUGAS tidak dapat mengakses Outreach di luar scope yang diizinkan atau mengedit record terkunci melalui jalur normal.
13. ADMIN tidak dapat melakukan approval Outreach.
14. Special Unlock tercatat dan tidak melewati pemeriksaan akses atau versioning.
15. Kegagalan pemrosesan lampiran ditampilkan dengan jelas dan tidak menyebabkan lampiran hilang secara diam-diam.
16. Uji backup dan restore mencakup database serta lampiran.

## 6. Hal yang Masih Perlu Divalidasi

Hal-hal berikut tidak boleh dianggap sudah terkonfirmasi hanya karena prototype mendukungnya:

- Sumber resmi label/status kesejahteraan atau desil.
- Persyaratan lampiran tambahan per jenis bantuan.
- Detail template PDF yang belum dapat dipastikan dari dokumen sumber.
- Batas ukuran unggahan dan parameter kompresi final.
- Kebutuhan operasional LAN untuk beberapa pengguna setelah evaluasi prototype.

Temuan validasi harus dicatat dan diselesaikan melalui proses rekonsiliasi kebutuhan. Perubahan yang memengaruhi perilaku bisnis, keamanan, workflow, atau baseline yang sudah dikunci harus ditinjau dan disetujui secara eksplisit sebelum diterapkan.

## 7. Referensi dan Sumber Kebenaran

- `docs/architecture/workflow.md` — Workflow **v2.1** aktif; sumber kebenaran untuk transisi status dan alur kerja.
- `docs/architecture/roles-permissions.md` — sumber kebenaran untuk role, permission, dan scope.
- `docs/architecture/security.md` — sumber kebenaran untuk aturan keamanan.
- `docs/architecture/database.md` — Database **v1.1**, baseline 22 tabel; tidak diubah oleh PRD ini.
- `docs/domain/forms/outreach.md` — persyaratan formulir Outreach.
- `docs/domain/forms/mustahik.md` — persyaratan formulir Mustahik.
- `docs/domain/forms/kifayah.md` — persyaratan formulir Kifayah.
- `docs/prototype/prototype.md` — batas dan ketentuan evaluasi prototype.

PRD ini menjelaskan kebutuhan produk prototype. Jika ditemukan konflik material dengan baseline yang telah dikunci, jangan menyelesaikannya dengan asumsi atau mengubah baseline secara diam-diam; catat konflik dan minta keputusan eksplisit.
