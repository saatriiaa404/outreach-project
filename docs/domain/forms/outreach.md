# Outreach Form Specification — Draft Revisi v1.6

> **Status dokumen:** DRAFT — menunggu review dan persetujuan  
> **Baseline yang dirujuk:** Outreach Form Specification v1.5; Workflow v2.1; Database v1.1  
> **Tujuan:** Memperjelas field, validasi, dan output PDF Outreach berdasarkan template kosong serta keputusan yang telah dibahas. Catatan dari review contoh terisi terdahulu dipertahankan hanya sebagai catatan historis yang perlu bukti aman untuk diverifikasi kembali.  
> **Penting:** Dokumen ini belum menggantikan baseline v1.5 dan belum boleh diperlakukan sebagai persetujuan pembukaan gate implementasi.

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

Status required/optional di bawah ini mengikuti baseline v1.5 atau keputusan yang sudah dibahas. Keberadaan suatu label di template kosong tidak dengan sendirinya menentukan status validasinya.

### 4.1 Data Klien

Template memiliki 23 field Data Klien.

| No. | Label pada template | Aturan |
|---:|---|---|
| 1 | Nama | Wajib |
| 2 | NIK | Wajib |
| 3 | Tempat dan Tanggal Lahir / Umur | Wajib |
| 4 | Agama | Wajib; daftar opsi perlu bersumber dari ketentuan yang disetujui |
| 5 | Alamat KTP | Wajib |
| 6 | Alamat Domisili | Wajib |
| 7 | Status Kependudukan | Wajib; daftar opsi perlu bersumber dari ketentuan yang disetujui |
| 8 | Menjadi warga Surabaya sejak | Wajib |
| 9 | Pekerjaan | Wajib; apakah menggunakan opsi terkontrol atau teks bebas perlu dipastikan |
| 10 | Status Hubungan dalam Keluarga | Wajib; daftar opsi perlu bersumber dari ketentuan yang disetujui |
| 11 | Jumlah anggota keluarga dalam 1 KK | Wajib; berbeda dari jumlah KK dalam satu rumah |
| 12 | Jumlah KK dalam 1 rumah | Wajib; berbeda dari jumlah anggota keluarga dalam satu KK |
| 13 | Status tempat tinggal | Wajib; daftar opsi perlu bersumber dari ketentuan yang disetujui |
| 14 | Penghasilan klien sebulan | Kondisional/opsional sesuai baseline; kondisi lebih rinci belum ditetapkan di dokumen ini |
| 15 | No. telp yang bisa dihubungi | Wajib |
| 16 | Status Perkawinan | Wajib; daftar opsi perlu bersumber dari ketentuan yang disetujui |
| 17 | Pendidikan terakhir | Kondisional/opsional sesuai baseline; kondisi lebih rinci belum ditetapkan di dokumen ini |
| 18 | Status Kesejahteraan | Wajib; mengikuti kesepakatan saat ini menggunakan desil 1–10 |
| 19 | Jaminan Kesehatan yang dimiliki | Wajib; daftar opsi atau bentuk input harus ditetapkan dari sumber yang disetujui |
| 20 | Apakah Klien merokok | Wajib; pilihan jawaban terbatas |
| 21 | Bantuan yang pernah diterima | Wajib; bentuk input dan pilihan nilai perlu ditetapkan |
| 22 | Apakah klien punya pinjaman / hutang | Wajib; menentukan apakah field besar hutang berlaku |
| 23 | Berapa besar pinjaman / hutang | Wajib jika field 22 menunjukkan klien memiliki hutang; tidak berlaku jika klien tidak memiliki hutang |

**Catatan historis yang masih perlu divalidasi:** review contoh pengisian terdahulu mencatat nilai `Sejahtera` pada field Status Kesejahteraan, sedangkan spesifikasi aplikasi saat ini menggunakan desil 1–10. PDF yang menjadi sumber catatan tersebut telah dikeluarkan dari repository, sehingga temuan ini tidak dapat diperiksa ulang dari artefak aktif. Jangan mengganti aturan aplikasi menjadi `Sejahtera` atau menyatukan kedua representasi. Cari evidence aman yang dapat ditelusuri atau konfirmasikan perbedaannya kepada pihak yang berwenang sebelum menutup `OUT-VAL-01`.

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
| 7 | Fasilitas MCK | Wajib |
| 8 | Jumlah dan Jenis kendaraan yang dimiliki | Wajib |
| 9 | Perhiasan | Opsional |
| 10 | Aset lainnya | Opsional |

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
- Opsi final, termasuk apakah field Pekerjaan dan Bantuan yang pernah diterima berupa dropdown atau teks bebas dengan opsi lainnya, belum seluruhnya didefinisikan dalam referensi yang tersedia.

## 6. Semantik Nilai: Belum Diisi, Nol, dan Tidak Ada

- `NULL` berarti field belum diisi. Nilai yang belum diisi tidak boleh otomatis ditafsirkan sebagai nol.
- `0` berarti nilai numerik nol yang dinyatakan secara eksplisit.
- Tanda `-` pada representasi PDF digunakan untuk keadaan yang secara eksplisit berarti **tidak ada** atau **tidak berlaku**, bukan untuk menyamarkan field yang belum diisi.
- Untuk komponen numerik yang secara eksplisit tidak memiliki nilai/pengeluaran, nilai harus tetap dapat dihitung dengan benar. Jika kondisi tersebut berarti nominal nol, nilai numeriknya adalah `0`; tanda `-` hanya merupakan representasi tampilan yang dipakai pada dokumen.
- Untuk field kondisional yang tidak berlaku—misalnya besar hutang ketika klien menyatakan tidak memiliki hutang—PDF dapat menampilkan `-`. Representasi persistence harus mengikuti kontrak database; dokumen ini tidak menetapkan sendiri nilai penyimpanannya.
- Draft boleh belum lengkap. Pada tahap finalisasi, validasi harus membedakan field yang belum diisi dari field yang secara sah bernilai nol atau tidak berlaku.

## 7. Lampiran dan Aturan Lock

### 7.1 Lampiran wajib baseline

Mengikuti baseline Workflow v2.1 dan Database v1.1, lampiran wajib untuk setiap Outreach adalah:

- KTP
- Kartu Keluarga (KK)
- Foto klien
- Foto rumah

Empat kategori tersebut merupakan persyaratan baseline, bukan daftar final seluruh kemungkinan lampiran.

### 7.2 Lampiran tambahan per Type

Berdasarkan keputusan kebutuhan proyek terbaru, empat lampiran baseline di atas tetap wajib untuk semua Outreach. Selain itu, lampiran tambahan berikut berlaku sesuai Type:

| Type | Lampiran tambahan |
|---|---|
| Rutilahu | Foto tambahan rumah; dapat lebih dari satu |
| Alat Kesehatan | Foto orang yang membutuhkan alat kesehatan |
| Beasiswa | Foto penerima beasiswa |
| Pendidikan (Kejar Paket) | Foto orang yang membutuhkan bantuan pendidikan |
| Bantuan Modal Usaha | Foto penerima bantuan |

Ketentuan lampiran tambahan ini merupakan keputusan kebutuhan proyek untuk prototype. Jangan menganggapnya sebagai perubahan schema Database v1.1. Lampiran dokumen lain, termasuk PDF, juga diperbolehkan sesuai kebutuhan proses. Tidak ada batas ukuran atau kualitas khusus yang ditetapkan Kelurahan; batas teknis aplikasi, jika diperlukan, harus diperlakukan sebagai batas teknis dan bukan persyaratan Kelurahan.

Source tetap menentukan workflow dan form terkait; Type menentukan intervensi serta lampiran tambahan yang relevan. Semua kombinasi Source × Type yang diizinkan baseline workflow tetap berlaku.

### 7.3 Integritas lampiran

Attachment current hanya dapat berubah melalui jalur yang diizinkan workflow. Historical attachment reference tidak boleh rusak atau tertimpa ketika ada revisi.

## 8. Output PDF Outreach

### 8.1 Tanggal dokumen

Tanggal pada kalimat pembuka berasal dari Tanggal Outreach yang tersimpan. Hari, tanggal, bulan, dan tahun harus mengikuti tanggal tersebut secara konsisten. Tanggal contoh yang pernah tertanam pada dokumen tidak boleh menjadi nilai tetap pada output.

### 8.2 Judul dan Type

- Judul utama mengikuti template: `LAPORAN HASIL OUTREACH`.
- Type ditampilkan di bawah judul sesuai keputusan yang telah dibahas.
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

Lampiran harus disertakan sebagai halaman setelah halaman utama PDF Outreach. Berkas lampiran asli tetap disimpan sebagai attachment terkelola sesuai aturan versioning dan historical integrity; PDF hasil render tidak menggantikan referensi attachment asli. Jika pembuatan salah satu PDF wajib gagal saat approval, approval harus gagal, status tetap `MENUNGGU_PENGESAHAN`, alasan kegagalan harus ditampilkan, dan notifikasi `OUTREACH_COMPLETED` tidak boleh dikirim.

## 9. Lifecycle

- Draft boleh disimpan dalam kondisi belum lengkap.
- Finalisasi memerlukan seluruh field, form, lampiran, dan tanda tangan yang diwajibkan oleh workflow.
- Status, versioning, refinalisasi, approval, Special Unlock, dan batas kewenangan mengikuti `docs/architecture/workflow.md`.
- Model persistence dan integritas data mengikuti `docs/architecture/database.md`.
- Dokumen ini tidak memberikan izin untuk mengubah baseline workflow maupun database.

## 10. Item Validasi yang Masih Terbuka

| ID | Item | Status/catatan |
|---|---|---|
| OUT-VAL-01 | Pemetaan Status Kesejahteraan terhadap `status_kesejahteraan`, `desil_1_10`, dan label `Sejahtera` pada contoh | Spesifikasi saat ini menggunakan desil 1–10; makna dan pemetaan kedua kolom database serta perbedaan dengan label `Sejahtera` belum final. Jangan menyatukan representasi atau mengubah schema tanpa keputusan |
| OUT-VAL-02 | Daftar opsi dropdown untuk field berkategori | Belum lengkap; harus bersumber dari ketentuan yang disetujui |
| OUT-VAL-03 | Apakah Pekerjaan dan Bantuan yang pernah diterima menggunakan dropdown, teks bebas, atau kombinasi | Belum diputuskan |
| OUT-VAL-04 | Lampiran tambahan berdasarkan Type | Empat lampiran wajib baseline serta lampiran tambahan per Type telah ditetapkan untuk kebutuhan prototype. Validasi/persetujuan formal pemilik formulir dan implementasi detail tetap harus dicatat dengan evidence yang dapat ditelusuri |
| OUT-VAL-05 | Evidence persetujuan posisi Type dan layout adaptif | Pengguna mengonfirmasi keduanya sudah disetujui Kelurahan; tautkan bukti/artefak persetujuan di catatan evidence gate jika belum tersimpan |
| OUT-VAL-06 | Pemetaan field kendaraan | PDF memiliki satu field gabungan `Jumlah dan Jenis kendaraan yang dimiliki`, sedangkan database menyediakan beberapa kolom kendaraan. Cara input, agregasi, dan render PDF perlu ditetapkan tanpa mengubah baseline schema secara diam-diam |
| OUT-VAL-07 | Pemetaan `Fasilitas MCK` | Kesesuaian label PDF dengan kolom `mck_layak` perlu diperiksa. Jangan mengasumsikan bahwa field hanya menerima jawaban boolean sebelum makna dan pilihan nilainya dikonfirmasi |
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

---

## Catatan Perubahan untuk Review

Draf ini memperluas spesifikasi v1.5 dengan pemetaan field per bagian, aturan nilai `NULL`/`0`/`-`, penggunaan dropdown sebagai pendekatan UI untuk field berkategori, perilaku PDF adaptif, lampiran tambahan per Type, serta komposisi tiga PDF terpisah yang mengikuti Workflow v2.1. Contoh PDF terisi yang pernah digunakan dalam review terdahulu tidak disertakan karena memuat data warga nyata; gunakan template kosong dan data sintetis atau salinan yang telah disamarkan untuk review lanjutan. Draf ini tetap perlu ditinjau sebelum dipromosikan menjadi baseline form yang aktif.

Draf ini belum menjadi baseline baru. Sebelum dipromosikan, periksa kesesuaian dengan `workflow.md` v2.1, `database.md` v1.1, `mustahik.md`, `kifayah.md`, dan proses rekonsiliasi proyek. Jangan membuka gate implementasi hanya karena draf ini sudah tersedia.
