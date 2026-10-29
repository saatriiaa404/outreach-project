# Outreach Form Specification — v1.6

> **Status dokumen:** ACTIVE — spesifikasi Form Outreach aktif untuk prototype (D-032)  
> **Baseline yang dirujuk:** Workflow v2.1; Database v1.1 (dengan amandemen D-041)  
> **Tujuan:** Menetapkan field, validasi, dan output PDF Outreach berdasarkan template kosong serta keputusan proyek. Catatan dari review contoh terisi terdahulu dipertahankan hanya sebagai catatan historis.  
> **Penting:** Dokumen ini tidak lagi bergantung pada rujukan Outreach Form Specification v1.5 yang tidak tersedia di repository. Status aktif dokumen ini tidak membuka gate implementasi.

Revisi Oktober 2026: OUT-VAL-01, OUT-VAL-03, OUT-VAL-06, dan OUT-VAL-07 ditutup serta kebijakan opsi formulir diputuskan sesuai D-019 sampai D-021, D-025, dan D-027. Revisi 27 Oktober 2026 menerapkan D-032 (dokumen aktif; field 14 dan 17 opsional), D-033 (lampiran tambahan tidak memblokir Finalisasi), D-034 (lampiran gambar dan halaman rujukan PDF), dan D-041 (NIP Kepala). Seluruh keputusan tercatat pada `docs/project-management/decisions.md`. Workflow v2.1 tidak berubah; Database v1.1 hanya berubah melalui amandemen D-041.

## 1. Applicability

Satu Outreach adalah record utama. Source menentukan alur form, sedangkan Type menentukan intervensi dan tetap berbeda dari Source.

Source dan Type tidak boleh diperlakukan sebagai satu konsep. Seluruh kombinasi Source × Type yang diizinkan baseline workflow tetap berlaku.

Dokumen ini harus dibaca bersama:

- `docs/architecture/workflow.md` untuk workflow, status, versioning, signature, approval, dan Special Unlock.
- `docs/architecture/database.md` untuk model persistence dan integritas data.
- `docs/domain/forms/mustahik.md` dan `docs/domain/forms/kifayah.md` untuk form tambahan jika Source mewajibkannya.

## 2. Referensi Formulir

### 2.1 Template kosong

`docs/reference/blank_outreach.pdf`

Digunakan sebagai acuan struktur, label, urutan bagian, identitas visual, dan bagian pengesahan pada formulir Outreach.

### 2.2 Contoh pengisian historis — tidak disertakan

Sebuah PDF Outreach terisi pernah digunakan dalam review terdahulu. Menurut klarifikasi pengguna, file tersebut berisi data warga nyata dan pengguna menyatakan persetujuan dari warga terkait telah diperoleh. File itu telah dikeluarkan dari direktori kerja dan tidak ditemukan dalam ZIP repository terbaru yang diaudit.

Pernyataan mengenai persetujuan dicatat sebagai keterangan pengguna; konten PDF dan bukti persetujuan tidak diverifikasi secara independen dalam audit dokumentasi. Karena file terisi tersebut tidak lagi tersedia di repository, dokumen ini tidak boleh merujuknya sebagai artefak aktif atau meminta coding agent membukanya.

Untuk review dan handover selanjutnya, gunakan `docs/reference/blank_outreach.pdf` bersama data sintetis, atau salinan yang telah disamarkan secara aman. Pastikan penyamaran mencakup teks, metadata yang relevan, serta gambar atau teks yang tertanam. Data contoh tidak boleh dijadikan nilai default aplikasi.

### 2.3 Prinsip penggunaan referensi

- Template kosong menentukan struktur dan label formulir.
- Catatan dari review contoh terisi terdahulu hanya merupakan petunjuk historis; jangan mengandalkannya sebagai artefak yang tersedia dalam repository saat ini.
- Aturan bisnis yang telah ditetapkan dalam baseline tetap berlaku kecuali direvisi melalui proses keputusan proyek.
- Jika template kosong, catatan historis, dan baseline tidak selaras, catat sebagai discrepancy/validasi; jangan menyelesaikannya dengan asumsi implementasi. Cari evidence yang aman atau minta konfirmasi pihak berwenang.

## 3. Identitas Outreach

| Field | Ketentuan |
|---|---|
| Source | Wajib; menentukan workflow dan form yang terkait; tidak ditampilkan pada PDF utama Outreach. |
| Type | Wajib; menentukan intervensi; ditampilkan di bawah judul `LAPORAN HASIL OUTREACH` pada PDF utama. |
| Tanggal Outreach | Wajib; menjadi sumber tanggal pada kalimat pembuka PDF. |

Aturan kombinasi Source × Type dan pengaruhnya terhadap workflow mengikuti `docs/architecture/workflow.md`.

## 4. Field dan Validasi

Status required/optional di bawah ini mengikuti dokumen ini dan keputusan proyek yang tercatat (D-032). Keberadaan suatu label di template kosong tidak dengan sendirinya menentukan status validasinya.

### 4.1 Data Klien

Template memiliki 23 field Data Klien.

| No. | Label pada template | Aturan |
|---:|---|---|
| 1 | Nama | Wajib |
| 2 | NIK | Wajib |
| 3 | Tempat dan Tanggal Lahir / Umur | Wajib; diisi terpisah pada form (D-045): Tempat lahir (teks) dan Tanggal lahir (isian tanggal, tidak setelah Tanggal Outreach). Umur tampil otomatis dalam tahun penuh terhadap Tanggal Outreach dan tidak disimpan. PDF tetap satu baris sesuai template |
| 4 | Agama | Wajib; daftar opsi perlu bersumber dari ketentuan yang disetujui |
| 5 | Alamat KTP | Wajib |
| 6 | Alamat Domisili | Wajib |
| 7 | Status Kependudukan | Wajib; pilihan `WNI` atau `WNA` sesuai Database v1.1 (D-025) |
| 8 | Menjadi warga Surabaya sejak | Wajib |
| 9 | Pekerjaan | Wajib; teks bebas pada prototype sampai daftar opsi disetujui (OUT-VAL-03) |
| 10 | Status Hubungan dalam Keluarga | Wajib; daftar opsi perlu bersumber dari ketentuan yang disetujui |
| 11 | Jumlah anggota keluarga dalam 1 KK | Wajib; berbeda dari jumlah KK dalam satu rumah |
| 12 | Jumlah KK dalam 1 rumah | Wajib; berbeda dari jumlah anggota keluarga dalam satu KK |
| 13 | Status tempat tinggal | Wajib; daftar opsi perlu bersumber dari ketentuan yang disetujui |
| 14 | Penghasilan klien sebulan | Opsional (D-032): boleh kosong dan tidak memblokir Finalisasi; bila diisi tetap divalidasi sesuai jenis datanya (nilai uang) |
| 15 | No. telp yang bisa dihubungi | Wajib |
| 16 | Status Perkawinan | Wajib; daftar opsi perlu bersumber dari ketentuan yang disetujui |
| 17 | Pendidikan terakhir | Opsional (D-032): boleh kosong dan tidak memblokir Finalisasi; bila diisi tetap divalidasi; teks bebas sesuai D-025 |
| 18 | Status Kesejahteraan | Wajib; Petugas memilih desil 1–10; label diturunkan otomatis (lihat Pemetaan Status Kesejahteraan di bawah tabel; D-019) |
| 19 | Jaminan Kesehatan yang dimiliki | Wajib; daftar opsi atau bentuk input harus ditetapkan dari sumber yang disetujui |
| 20 | Apakah Klien merokok | Wajib; pilihan jawaban terbatas |
| 21 | Bantuan yang pernah diterima | Wajib; pilihan Ya/Tidak (`bantuan_pernah_diterima`) dan uraian teks bebas (`bantuan_detail`) yang wajib bila Ya; PDF menampilkan uraian atau `Tidak ada` (D-027) |
| 22 | Apakah klien punya pinjaman / hutang | Wajib; menentukan apakah field besar hutang berlaku |
| 23 | Berapa besar pinjaman / hutang | Wajib jika field 22 menunjukkan klien memiliki hutang; tidak berlaku jika klien tidak memiliki hutang |

**Pemetaan Status Kesejahteraan (D-019).** Petugas memilih desil 1–10. Angka disimpan pada `desil_1_10`, dan label diturunkan otomatis ke `status_kesejahteraan` sehingga keduanya tidak dapat saling bertentangan. Tidak ada perubahan schema. Label berikut mengikuti pembagian resmi desil kesejahteraan DTST yang dipakai Kelurahan, sesuai keterangan pemilik proyek pada 29 Oktober 2026 (D-064, menggantikan label awal D-019).

| Desil | Label |
|---:|---|
| 1 | Sangat Miskin |
| 2 | Miskin |
| 3 | Kelompok Hampir Miskin / Rentan |
| 4 | Kelompok Rentan Miskin / Menengah Bawah |
| 5 | Kelompok Pas-pasan / Menengah Bawah Stabil |
| 6 | Kelompok Menengah |
| 7 | Kelompok Menengah Atas |
| 8 | Kelompok Mapan / Menengah Atas |
| 9 | Kelompok Sangat Mapan |
| 10 | Kelompok Paling Sejahtera / Kaya |

Label yang sudah tersimpan sebelum D-064 tidak diubah; label baru berlaku saat Status Kesejahteraan disimpan berikutnya. Jangan menyatukan representasi desil dan label menjadi satu field input.

### 4.2 Pendapatan Keluarga dalam Sebulan

| No. | Label pada template | Aturan |
|---:|---|---|
| 1 | Pendapatan Kepala Keluarga | Wajib |
| 2 | Pendapatan Suami / Istri | Wajib |
| 3 | Pendapatan Anak yang masuk dalam satu KK | Wajib |
| 4 | Pendapatan lainnya | Wajib |
| — | Total Pendapatan Keluarga | Nilai turunan (derived), dihitung dari empat kategori di atas |

Total pendapatan bukan source of truth yang disimpan terpisah dari komponennya.

### 4.3 Pengeluaran Keluarga dalam Sebulan

Template memiliki 13 kategori pengeluaran.

| No. | Label pada template | Aturan |
|---:|---|---|
| 1 | Biaya sewa/kos/kontrak rumah | Wajib |
| 2 | Biaya belanja memasak | Wajib |
| 3 | Biaya air minum / gallon | Wajib |
| 4 | Biaya LPG / PGN | Wajib |
| 5 | Biaya Listrik / PLN | Wajib |
| 6 | Biaya PDAM | Wajib |
| 7 | Biaya sekolah | Wajib |
| 8 | Biaya Internet / Wifi / Indihome | Wajib |
| 9 | Biaya Pulsa HP | Wajib |
| 10 | Biaya Rokok | Wajib |
| 11 | Biaya berobat | Wajib |
| 12 | Biaya / Angsuran pinjaman | Wajib |
| 13 | Biaya BBM / bensin | Wajib |
| — | Total Pengeluaran Keluarga | Nilai turunan (derived), dihitung dari 13 kategori di atas |

Total pengeluaran bukan source of truth yang disimpan terpisah dari komponennya.

### 4.4 Kondisi Rumah dan Aset yang Dimiliki

| No. | Label pada template | Aturan |
|---:|---|---|
| 1 | Ukuran / Luas bangunan rumah | Wajib |
| 2 | Jenis lantai | Wajib |
| 3 | Jenis dinding | Wajib |
| 4 | Jenis atap bangunan | Wajib |
| 5 | Daya listrik | Wajib |
| 6 | Sumber air | Wajib |
| 7 | Fasilitas MCK | Wajib; empat nilai: Layak, Kurang layak, Tidak layak, Tidak ada (D-021) |
| 8 | Jumlah dan Jenis kendaraan yang dimiliki | Wajib; input angka per jenis (D-020) |
| 9 | Perhiasan | Opsional |
| 10 | Aset lainnya | Opsional |

**Kendaraan (D-020).** Input berupa angka per jenis sesuai kolom Database v1.1: sepeda angin, sepeda listrik, becak, sepeda motor, roda 4, dan kendaraan lainnya beserta namanya. Petugas mengisi angka secara eksplisit (`0` bila tidak ada) tanpa auto-fill. Draft boleh kosong, sedangkan Finalisasi menuntut semua angka terisi. Nama kendaraan lainnya wajib bila jumlahnya lebih dari 0. PDF merangkai satu baris dari jenis yang bernilai lebih dari 0, atau `Tidak ada` bila semuanya 0. Ini berbeda dari pertanyaan Kepemilikan Kendaraan pada form Mustahik.

**MCK (D-021).** Empat nilai: Layak, Kurang layak, Tidak layak, Tidak ada, disimpan pada kolom `mck_layak` (nama kolom tetap). Tipe kolom dan kode nilai internal ditetapkan saat desain migration setelah gate implementasi dibuka. Ini berbeda dari pertanyaan MCK pada form Mustahik, dan jawaban Mustahik tidak diisi otomatis dari Outreach.

### 4.5 Narasi

| Label pada template | Aturan |
|---|---|
| Upaya yang telah dilakukan | Wajib |
| Harapan klien | Wajib |
| Uraian detail mengapa klien diusulkan intervensi ini | Wajib |

Narasi dapat memiliki panjang bervariasi. Isi tidak boleh dipotong hanya untuk mempertahankan jumlah halaman tetap.

## 5. Kontrol UI dan Daftar Pilihan

- Untuk field berkategori dengan daftar pilihan yang telah ditetapkan dan disetujui, kontrol **dropdown/select** merupakan pendekatan UI yang diusulkan.
- Field dengan jawaban biner seperti pertanyaan merokok dapat menggunakan pilihan terkontrol. Pemilihan dropdown atau kontrol lain tidak mengubah makna jawaban.
- Field narasi menggunakan area teks; field alamat menggunakan input teks yang sesuai; jumlah dan nilai uang menggunakan input dengan validasi sesuai jenis datanya.
- Daftar opsi tidak boleh dibuat berdasarkan tebakan. Sumber opsi harus berasal dari dokumen referensi atau keputusan validasi yang disetujui.
- Kebijakan opsi (OUT-VAL-02 dan OUT-VAL-03): dropdown hanya digunakan bila opsinya tertulis pada PDF atau ketentuan yang disetujui; selain itu field memakai teks bebas pada prototype. Contoh opsi yang sudah tertulis pada dokumen yang disetujui: Status Kependudukan `WNI` atau `WNA` (Database v1.1), serta jawaban Ya/Tidak untuk merokok dan hutang. Pekerjaan menggunakan teks bebas, sedangkan Bantuan yang pernah diterima mengikuti D-027 (pilihan Ya/Tidak dan uraian teks bebas). Field lain yang pada bagian 4.1 menyebut "daftar opsi perlu bersumber" menggunakan teks bebas sampai daftarnya disetujui, lalu dapat dijadikan dropdown tanpa mengubah makna jawaban.

## 6. Semantik Nilai: Belum Diisi, Nol, dan Tidak Ada

- `NULL` berarti field belum diisi. Nilai yang belum diisi tidak boleh otomatis ditafsirkan sebagai nol.
- `0` berarti nilai numerik nol yang dinyatakan secara eksplisit.
- Tanda `-` pada representasi PDF digunakan untuk keadaan yang secara eksplisit berarti **tidak ada** atau **tidak berlaku**, bukan untuk menyamarkan field yang belum diisi.
- Untuk komponen numerik yang secara eksplisit tidak memiliki nilai/pengeluaran, nilai harus tetap dapat dihitung dengan benar. Jika kondisi tersebut berarti nominal nol, nilai numeriknya adalah `0`; tanda `-` hanya merupakan representasi tampilan yang dipakai pada dokumen.
- Untuk field kondisional yang tidak berlaku—misalnya besar hutang ketika klien menyatakan tidak memiliki hutang—PDF dapat menampilkan `-`. Representasi persistence harus mengikuti kontrak database; dokumen ini tidak menetapkan sendiri nilai penyimpanannya.
- Format tampilan pada preview dan PDF (D-050): field 3 `Surabaya, 15 Juni 2006 / 20 tahun`; field 18 `Sejahtera (Desil 9)`; nominal rupiah bernilai `0` tampil `-`, nominal lain `Rp 1.500.000`; baris kendaraan `Sepeda motor 2, Sepeda angin 1` atau `Tidak ada`.
- Draft boleh belum lengkap. Pada tahap finalisasi, validasi harus membedakan field yang belum diisi dari field yang secara sah bernilai nol atau tidak berlaku.

## 7. Lampiran dan Aturan Lock

### 7.1 Lampiran wajib baseline

Mengikuti baseline Workflow v2.1 dan Database v1.1, lampiran wajib untuk setiap Outreach adalah:

- KTP
- Kartu Keluarga (KK)
- Foto klien
- Foto rumah

Empat kategori tersebut merupakan persyaratan baseline, bukan daftar final seluruh kemungkinan lampiran. Keempatnya harus berupa gambar (D-034).

### 7.2 Lampiran lain

Empat lampiran baseline di atas tetap wajib untuk semua Outreach. Kelurahan tidak menetapkan daftar resmi lampiran tambahan per Type (D-064): lampiran apa pun di luar empat wajib diunggah sebagai **Lampiran lain** dengan keterangan bebas, dan **tidak memblokir Finalisasi** (D-033). Satu Outreach memuat paling banyak 10 lampiran termasuk empat wajib, masing-masing paling besar 10 MB (D-064). Daftar saran per Type yang sebelumnya dipakai prototype dihapus.

Ketentuan lampiran tambahan ini merupakan keputusan kebutuhan proyek untuk prototype. Jangan menganggapnya sebagai perubahan schema Database v1.1. Lampiran dokumen lain, termasuk PDF, juga diperbolehkan sesuai kebutuhan proses; pada PDF Outreach, lampiran PDF ditampilkan sebagai halaman rujukan (lihat bagian 8.5, D-034). Kelurahan menetapkan batas 10 MB per berkas dan 10 lampiran per Outreach (D-064); tidak ada persyaratan kualitas khusus.

Source tetap menentukan workflow dan form terkait; Type menentukan intervensi. Semua kombinasi Source × Type yang diizinkan baseline workflow tetap berlaku.

### 7.3 Integritas lampiran

Attachment current hanya dapat berubah melalui jalur yang diizinkan workflow. Historical attachment reference tidak boleh rusak atau tertimpa ketika ada revisi.

## 8. Output PDF Outreach

### 8.1 Tanggal dokumen

Tanggal pada kalimat pembuka berasal dari Tanggal Outreach yang tersimpan. Hari, tanggal, bulan, dan tahun harus mengikuti tanggal tersebut secara konsisten. Tanggal contoh yang pernah tertanam pada dokumen tidak boleh menjadi nilai tetap pada output.

### 8.2 Judul dan Type

- Judul utama mengikuti template: `LAPORAN HASIL OUTREACH`.
- Type ditampilkan di bawah judul sesuai keputusan yang telah dibahas. Sejak template versi 3 (D-070) ditulis rata kiri sebagai `Jenis Outreach : Bantuan <Jenis>`; kata "Bantuan" tidak ditambahkan bila nama Jenis sudah diawali "Bantuan". Kop surat memakai gambar kop Kelurahan (`docs/reference/Kop Manukan.png`), dan margin serta lebar kolom mengikuti `blank_outreach.pdf`.
- Source tidak ditampilkan pada PDF utama Outreach.

### 8.3 Layout adaptif

- Urutan bagian mengikuti formulir resmi: Data Klien, Pendapatan Keluarga, Pengeluaran Keluarga, Kondisi Rumah dan Aset, Upaya yang Telah Dilakukan, Harapan Klien, Uraian Detail, lalu pengesahan.
- Layout menyesuaikan panjang konten secara dinamis. Narasi boleh berlanjut ke halaman berikutnya dan jumlah halaman tidak harus selalu tiga.
- Bagian pengesahan ditempatkan setelah seluruh konten utama; konten dan blok pengesahan tidak boleh saling menimpa atau terpotong.
- Identitas visual, label, urutan bagian, dan karakter resmi formulir dipertahankan. Penyesuaian dilakukan untuk mengikuti panjang isi dan keterbacaan, bukan untuk mendesain ulang formulir secara bebas. Perilaku layout adaptif ini telah dikonfirmasi pengguna sebagai disetujui Kelurahan.
- Hasil render harus diperiksa untuk memastikan tabel, narasi, pemisahan halaman, dan pengesahan tetap terbaca.

### 8.4 Identitas Kepala Kelurahan dan tanda tangan

Mengikuti keputusan yang telah dibahas sebelumnya:

- Identitas pejabat mengikuti Kepala Kelurahan yang aktif, bukan nama/NIP yang dikunci dari contoh PDF.
- Nama diambil dari akun Kepala yang mengesahkan dan dicetak huruf kapital, pangkat dari kolom `users.pangkat` sebagaimana tercatat saat pengesahan, dan NIP dari kolom `users.nip` (D-041, D-065).
- Tata letak blok pengesahan (dikonfirmasi pemilik proyek, 27 Oktober 2026; VAL-004) berupa blok rata tengah dengan urutan baris:

  ```text
  Surabaya, <tanggal>
  Mengetahui,
  Lurah Manukan Kulon
  <ruang tanda tangan Kepala>
  <NAMA KEPALA>
  <pangkat Kepala>
  NIP. <nip Kepala>
  ```

  Stempel aktif, bila ada, dicetak menimpa sisi kiri tanda tangan (D-065). Kop PDF Outreach memuat logo Pemkot Surabaya di sebelah kiri teks kop; PDF Mustahik dan Kifayah memuat logo Baznas Kota Surabaya di tengah atas (D-064, template PDF versi 2).

  Nama dicetak sesuai data akun Kepala, termasuk gelar (contoh tata letak memakai huruf kapital). Letak tanda tangan di antara jabatan dan nama mengikuti kelaziman blok pengesahan dan perlu dicek pada hasil render. `<tanggal>` adalah tanggal Kepala mengesahkan (`outreach_approvals.decided_at`), bukan Tanggal Outreach dan bukan tanggal PDF diunduh; regenerasi PDF historis memakai tanggal pengesahan yang tersimpan (keputusan pemilik proyek, 27 Oktober 2026). Tanggal pada kalimat pembuka tetap mengikuti Tanggal Outreach (bagian 8.1). Nama dan NIP pejabat nyata tidak dicantumkan pada dokumen, kode, atau data prototype; data demo memakai nilai sintetis.
- Tanda tangan Kepala Kelurahan menggunakan template tanda tangan tersimpan sesuai aturan workflow dan user management.
- Identitas atau tanda tangan tidak boleh dikarang atau diambil dari data contoh sebagai nilai permanen.
- Detail sumber data dan pembaruan identitas pejabat harus konsisten dengan dokumen role/user management dan workflow.

### 8.5 Komposisi output PDF

Komposisi PDF mengikuti baseline Workflow v2.1:

- **Source Baznas/CSR:** hasilkan tiga PDF terpisah:
  1. **PDF Outreach** — formulir Outreach utama, diikuti halaman lampiran wajib dan lampiran terkait yang disertakan untuk Outreach.
  2. **PDF Mustahik** — formulir Mustahik.
  3. **PDF Kifayah** — formulir Kifayah.
- **Source Bangga Surabaya:** hasilkan **PDF Outreach** dengan halaman lampiran wajib dan lampiran terkait setelah halaman utama. PDF Mustahik dan Kifayah tidak dibuat untuk source ini.

Pembuatan PDF Mustahik dan Kifayah hanya berlaku pada source yang mewajibkannya. Jangan menghasilkan form yang tidak diwajibkan oleh workflow.

Komposisi ini menggantikan catatan konflik lama pada draf yang membandingkan keputusan tiga PDF terpisah dengan baseline PDF gabungan. Untuk dokumen ini, Workflow v2.1 adalah acuan aktif; jangan kembali menggunakan aturan Workflow v2.0 yang sudah digantikan.

Lampiran harus disertakan sebagai halaman setelah halaman utama PDF Outreach. Pada prototype, hanya lampiran gambar yang dicetak pada halaman lampiran; lampiran berformat PDF tetap tersimpan sebagai attachment dan ditampilkan sebagai halaman rujukan berisi nama berkas, tanpa penggabungan halaman PDF (D-034). Berkas lampiran asli tetap disimpan sebagai attachment terkelola sesuai aturan versioning dan historical integrity; PDF hasil render tidak menggantikan referensi attachment asli. Jika pembuatan salah satu PDF wajib gagal saat approval, approval harus gagal, status tetap `MENUNGGU_PENGESAHAN`, alasan kegagalan harus ditampilkan, dan notifikasi `OUTREACH_COMPLETED` tidak boleh dikirim.

## 9. Lifecycle

- Draft boleh disimpan dalam kondisi belum lengkap.
- Finalisasi memerlukan seluruh field, form, lampiran, dan tanda tangan yang diwajibkan oleh workflow.
- Status, versioning, refinalisasi, approval, Special Unlock, dan batas kewenangan mengikuti `docs/architecture/workflow.md`.
- Model persistence dan integritas data mengikuti `docs/architecture/database.md`.
- Dokumen ini tidak memberikan izin untuk mengubah baseline workflow maupun database.

## 10. Item Validasi yang Masih Terbuka

| ID | Item | Status/catatan |
|---|---|---|
| OUT-VAL-01 | Pemetaan Status Kesejahteraan terhadap `status_kesejahteraan`, `desil_1_10`, dan label `Sejahtera` pada contoh | **CLOSED** — D-019. Desil 1–10 pada `desil_1_10`, label otomatis pada `status_kesejahteraan`; `Sejahtera` adalah label Desil 9. Evidence sumber resmi label belum dilampirkan |
| OUT-VAL-02 | Daftar opsi dropdown untuk field berkategori | **Kebijakan diputuskan (D-025)** — dropdown hanya bila opsi tertulis pada PDF atau ketentuan yang disetujui, selain itu teks bebas (lihat bagian 5). Daftar opsi untuk field lain tetap menunggu ketentuan yang disetujui |
| OUT-VAL-03 | Apakah Pekerjaan dan Bantuan yang pernah diterima menggunakan dropdown, teks bebas, atau kombinasi | **CLOSED** — D-025 dan D-027. Pekerjaan teks bebas; Bantuan mengisi dua kolom Database v1.1 dari pilihan Ya/Tidak dan uraian teks bebas. Tanpa perubahan schema |
| OUT-VAL-04 | Lampiran tambahan berdasarkan Type | **CLOSED** — D-064. Tidak ada daftar resmi per Type; lampiran di luar empat wajib masuk "Lampiran lain", maksimal 10 lampiran per Outreach |
| OUT-VAL-05 | Evidence persetujuan posisi Type dan layout adaptif | Pengguna mengonfirmasi keduanya sudah disetujui Kelurahan; tautkan bukti/artefak persetujuan di catatan evidence gate jika belum tersimpan |
| OUT-VAL-06 | Pemetaan field kendaraan | **CLOSED** — D-020. Input angka per jenis pada kolom Database v1.1; PDF merangkai satu baris dari jenis bernilai lebih dari 0, atau `Tidak ada`. Tanpa perubahan schema |
| OUT-VAL-07 | Pemetaan `Fasilitas MCK` | **CLOSED** — D-021. Empat nilai (Layak, Kurang layak, Tidak layak, Tidak ada) pada kolom `mck_layak`; nama kolom tetap, bukan boolean |
| OUT-VAL-09 | Format dan posisi cetak NIP Kepala pada blok pengesahan | **CLOSED** — tata letak dan sumber tanggal (tanggal pengesahan) dikonfirmasi pemilik proyek, 27 Oktober 2026 (bagian 8.4, VAL-004). Letak tanda tangan dicek pada hasil render |
| OUT-VAL-08 | Status dan penanganan contoh PDF terisi | Menurut klarifikasi pengguna, PDF terisi terdahulu berisi data warga nyata dan persetujuan warga telah diperoleh. File telah dikeluarkan dari direktori kerja dan tidak ditemukan dalam ZIP repository terbaru yang diaudit. Konten serta bukti persetujuan tidak diverifikasi secara independen. Jangan menambahkan kembali file nyata ke repository atau paket handover; gunakan template kosong, data sintetis, atau salinan yang telah disamarkan secara aman. |

Item validasi harus ditutup melalui bukti atau keputusan yang bisa ditelusuri sebelum gate implementasi terkait dinyatakan selesai.

## 11. Kriteria Pemeriksaan Output PDF

Sebelum output PDF dinyatakan memenuhi spesifikasi, periksa bahwa:

1. Semua field dengan data tampil di bagian yang benar.
2. Type tampil di bawah judul dan Source tidak tampil pada PDF utama Outreach.
3. Hari, tanggal, bulan, dan tahun konsisten dengan Tanggal Outreach yang tersimpan.
4. Total pendapatan dan pengeluaran sesuai dengan komponen masing-masing.
5. `NULL`, nilai numerik `0`, dan kondisi yang ditampilkan sebagai `-` tidak tertukar.
6. Field kondisional mengikuti jawaban pengendalinya, termasuk besar hutang.
7. Narasi panjang tetap utuh dan dapat berlanjut ke halaman berikutnya.
8. Blok pengesahan diletakkan setelah konten utama, tanpa menimpa atau memotong isi.
9. Identitas Kepala Kelurahan aktif dan tanda tangan mengikuti konfigurasi yang sah.
10. Header, label, urutan bagian, dan karakter visual formulir resmi tetap terjaga.
11. PDF Outreach memuat halaman lampiran setelah halaman utama; Source Baznas/CSR menghasilkan PDF Outreach, Mustahik, dan Kifayah secara terpisah, sedangkan Bangga Surabaya hanya menghasilkan PDF Outreach.
12. Pengujian menggunakan data sintetis atau data yang telah disamarkan secara aman.
13. Field kendaraan dirangkai dari jenis yang bernilai lebih dari 0, atau menampilkan `Tidak ada` bila semuanya 0.
14. Bantuan yang pernah diterima menampilkan uraian bila jawabannya Ya, atau `Tidak ada` bila Tidak.
15. Lampiran gambar tercetak pada halaman lampiran; lampiran berformat PDF tampil sebagai halaman rujukan berisi nama berkas (D-034).
16. Blok pengesahan rata tengah memuat `Surabaya, <tanggal pengesahan>`, `Mengetahui,`, `Lurah Manukan Kulon`, tanda tangan, nama Kepala yang mengesahkan, dan `NIP. <nip>` (D-041, bagian 8.4).

---

## Catatan Perubahan untuk Review

Draf ini memperluas spesifikasi v1.5 dengan pemetaan field per bagian, aturan nilai `NULL`/`0`/`-`, penggunaan dropdown sebagai pendekatan UI untuk field berkategori, perilaku PDF adaptif, lampiran tambahan per Type, serta komposisi tiga PDF terpisah yang mengikuti Workflow v2.1. Contoh PDF terisi yang pernah digunakan dalam review terdahulu tidak disertakan karena memuat data warga nyata; gunakan template kosong dan data sintetis atau salinan yang telah disamarkan untuk review lanjutan. Draf ini kemudian dipromosikan menjadi spesifikasi aktif untuk prototype melalui D-032. Revisi Oktober 2026 menutup OUT-VAL-01, OUT-VAL-03, OUT-VAL-06, dan OUT-VAL-07 serta memutuskan kebijakan opsi formulir (D-019 sampai D-021, D-025, dan D-027); evidence sumber resmi label desil belum dilampirkan.

Sesuai D-032, dokumen ini menjadi spesifikasi Form Outreach aktif untuk prototype. Revisi 27 Oktober 2026 menambahkan D-033, D-034, dan D-041. Status aktif ini tidak membuka gate implementasi.
