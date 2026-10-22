# Mustahik Form Specification — v1.7

## 1. Authority dan Batasan

Mustahik berlaku hanya untuk Source **Baznas** dan **CSR**. Source **Bangga Surabaya** tidak menggunakan form Mustahik.

Referensi untuk prototipe adalah PDF kosong Mustahik yang disediakan (`docs/reference/mustahik.pdf`). Label dan opsi yang ditampilkan pada PDF dipertahankan sebagaimana tercetak. Rentang atau pilihan yang tampak tumpang tindih/tidak konsisten tidak boleh diperbaiki diam-diam; catat sebagai karakteristik sumber dan minta klarifikasi kepada pemilik form jika diperlukan.

Database v1.1 tetap menjadi baseline terkunci:
- 25 `question_code`;
- jawaban disimpan pada `mustahik_answers.answer` dengan tipe JSON;
- unique `(mustahik_form_id, question_code)`;
- tidak ada perubahan schema atau migration yang diizinkan oleh pembaruan spesifikasi ini.

## 2. Kontrak Bersama Setiap Pertanyaan

| Properti | Aturan |
|---|---|
| Applicability | Baznas/CSR; tidak berlaku untuk Bangga Surabaya. |
| Answer persistence | `mustahik_answers.question_code` + `answer` JSON. |
| Cardinality | **Single-choice untuk seluruh 25 pertanyaan**, termasuk `PHYSICAL_DEPENDENTS` dan `PHYSICAL_DEBT`. |
| Required | **Seluruh 25 pertanyaan wajib dijawab** sebelum finalisasi form Mustahik. Validasi tetap diperlukan untuk perilaku tampilan dan representasi PDF. |
| Snapshot/PDF | Snapshot harus merekam jawaban yang disubmit. Representasi final pada PDF masih perlu validasi. |
| Signature | Perubahan data form yang relevan memerlukan pembaruan tanda tangan sesuai workflow. Perubahan attachment saja tidak memicu tanda tangan ulang. |
| Scoring | Sistem tidak memilih jawaban atau menghitung skor secara otomatis. Kolom skor yang tercetak pada PDF sumber tidak menjadi izin untuk menambahkan perhitungan otomatis. |
| Historical data | Jawaban historis tidak boleh berubah secara retroaktif ketika label, opsi, atau aturan form diperbarui. |

## 3. Register 25 Pertanyaan

PDF kosong adalah sumber label dan opsi untuk prototipe. Tabel di bawah membedakan informasi yang terlihat pada sumber dari aturan operasional yang masih memerlukan validasi. `question_code` berasal dari Database v1.1. **Semua 25 pertanyaan wajib dijawab**; belum adanya validasi untuk opsi, tampilan, atau PDF tidak mengubah status wajib tersebut.

| question_code | Label / bagian pada sumber | Opsi yang terlihat pada PDF sumber | Cardinality | Required/conditional | Snapshot/PDF |
|---|---|---|---|---|---|
| `HOUSE_BUILDING_AREA` | Luas bangunan | Sangat kecil (`<4x4`); kecil (`4x4–5x6`); sedang (`5x7–5x15`); lebar (`>5x15`) | Single | Wajib | Perlu validasi |
| `HOUSE_WALL_TYPE` | Dinding | Triplek/sesek; papan; tembok; tembok keramik | Single | Wajib | Perlu validasi |
| `HOUSE_OWNERSHIP_STATUS` | Status tempat tinggal | Kost; kontrak; menumpang/keluarga; milik sendiri | Single | Wajib | Perlu validasi |
| `HOUSE_MCK` | MCK | Tidak punya; WC umum; punya tidak layak; punya layak | Single | Wajib | Perlu validasi |
| `HOUSE_DRINKING_WATER` | Sumber air minum | Bukan air bersih; air bersih (beli); sumur; sumur air bersih | Single | Wajib | Perlu validasi |
| `HOUSE_KITCHEN` | Kondisi dapur | Kayu bakar; kompor minyak; LPG 3 kg; LPG 12 kg | Single | Wajib | Perlu validasi |
| `HOUSE_ASSET_OWNERSHIP` | Label perlu dicocokkan kembali dengan posisi/teks PDF | Belum ditranskripsikan secara pasti dalam spesifikasi ini | Single | Wajib | Perlu validasi |
| `HOUSE_VEHICLE_OWNERSHIP` | Label perlu dicocokkan kembali dengan posisi/teks PDF | Belum ditranskripsikan secara pasti dalam spesifikasi ini | Single | Wajib | Perlu validasi |
| `HOUSE_FLOOR_TYPE` | Jenis lantai | Belum ditranskripsikan secara pasti dalam spesifikasi ini | Single | Wajib | Perlu validasi |
| `HOUSE_ROOF_TYPE` | Jenis atap | Belum ditranskripsikan secara pasti dalam spesifikasi ini | Single | Wajib | Perlu validasi |
| `ECONOMIC_HEAD_EDUCATION` | Pendidikan kepala keluarga | Tidak sekolah; SD–SMP; SMA; perguruan tinggi | Single | Wajib | Perlu validasi |
| `ECONOMIC_HEAD_OCCUPATION` | Pekerjaan kepala keluarga | Tidak bekerja; buruh tani/serabutan; petani; karyawan/tukang | Single | Wajib | Perlu validasi |
| `ECONOMIC_MONTHLY_INCOME` | Pendapatan per bulan | `<300.000`; `<750.000`; `<1.500.000`; `>3.500.000` | Single | Wajib | Perlu validasi |
| `ECONOMIC_FOOD_PURCHASE` | Kemampuan membeli daging/ayam/susu per minggu | Tidak mampu; 1x; 2x; 3x | Single | Wajib | Perlu validasi |
| `ECONOMIC_CLOTHING_PURCHASE` | Pembelian pakaian per tahun | 1x; 2x; 3x; 5x | Single | Wajib | Perlu validasi |
| `ECONOMIC_OTHER_SUBSIDY` | Bantuan/sumbangan lain | Tidak ada; sumbangan rutin; kerabat/orang lain | Single | Wajib | Perlu validasi |
| `ECONOMIC_MEAL_FREQUENCY` | Frekuensi makan | Belum ditranskripsikan secara pasti dalam spesifikasi ini | Single | Wajib | Perlu validasi |
| `ECONOMIC_LIGHTING` | Penerangan | Belum ditranskripsikan secara pasti dalam spesifikasi ini | Single | Wajib | Perlu validasi |
| `ECONOMIC_SIDE_BUSINESS` | Usaha sampingan | Belum ditranskripsikan secara pasti dalam spesifikasi ini | Single | Wajib | Perlu validasi |
| `ECONOMIC_GOVERNMENT_AID` | Bantuan pemerintah | Belum ditranskripsikan secara pasti dalam spesifikasi ini | Single | Wajib | Perlu validasi |
| `PHYSICAL_CONDITION` | Kondisi fisik | Manula sakit; manula; cacat produktif; sehat/produktif | Single | Wajib | Perlu validasi |
| `PHYSICAL_DEPENDENTS` | Tanggungan | Anak masih sekolah; lansia tidak produktif; keluarga lainnya; tidak ada | **Single** | Wajib | Perlu validasi |
| `PHYSICAL_DEBT` | Hutang | Rentenir; nonrentenir; tidak ada; bank | **Single** | Wajib | Perlu validasi |
| `PHYSICAL_HEALTH_CAPABILITY` | Kemampuan/akses kesehatan | Tidak ada; KIS/PBI JKN; BPJS kelas 3; BPJS kelas 2 | Single | Wajib | Perlu validasi |
| `PHYSICAL_SAVINGS` | Tabungan | Tidak ada; emas `<5gr`; emas `>5gr`; emas `>10gr` | Single | Wajib | Perlu validasi |

### Catatan fidelitas sumber

- Rentang luas bangunan dan pendapatan ditulis mengikuti teks PDF sumber. Rentang pendapatan yang tumpang tindih atau menyisakan celah tidak dinormalisasi.
- Opsi tabungan `emas >5gr` dan `emas >10gr` beririsan; pertahankan sesuai sumber sampai ada klarifikasi resmi.
- Ejaan, istilah, satuan, dan urutan opsi pada prototipe harus mengikuti PDF sumber. Bila pembacaan teks/OCR tidak cukup untuk memastikan posisi label atau opsi, periksa tampilan visual PDF; jangan menebak.
- Kolom skor pada PDF sumber tidak berarti aplikasi harus menghitung atau memilih skor otomatis. Perilaku skor masih perlu dikonfirmasi kepada pemilik form jika diperlukan dalam alur kerja.

## 4. Data Konteks dan Batas Perilaku

Data Outreach boleh ditampilkan sebagai konteks bagi petugas, tetapi tidak boleh otomatis memilih jawaban Mustahik. Jawaban harus dipilih secara eksplisit pada form Mustahik.

Perubahan spesifikasi tidak mengubah jawaban pada versi historis. Jika label atau opsi nantinya diklarifikasi oleh pemilik form, perubahan diterapkan secara terkendali dan tidak menulis ulang submission yang sudah disimpan.

## 5. Hal yang Masih Memerlukan Validasi

1. Validasi pemilik form atas penerapan 25 pertanyaan wajib, termasuk penanganan input yang belum lengkap sebelum finalisasi.
2. Transkripsi dan pemetaan visual final untuk label/opsi yang belum pasti pada register.
3. Apakah kolom skor diisi manual, hanya dicetak, atau memiliki aturan lain. Tidak ada scoring otomatis sebelum ada keputusan eksplisit.
4. Pemetaan metadata/header pada PDF dan representasi jawaban pada PDF hasil aplikasi.
5. Persetujuan formal pemilik form terhadap PDF sumber yang dipakai sebagai acuan prototipe.

Hal-hal yang belum divalidasi di atas tidak menghalangi pencatatan keputusan single-choice, tetapi harus diselesaikan sebelum perilaku final yang bergantung padanya dianggap disetujui.

## 6. Output PDF dan Approval Gate

- Mustahik hanya berlaku untuk Source Baznas dan CSR; Bangga Surabaya tidak menggunakan form ini.
- PDF Mustahik dihasilkan sebagai file terpisah dari PDF Outreach dan PDF Kifayah untuk alur Baznas/CSR.
- Jawaban yang dirender harus berasal dari jawaban yang tersimpan pada submission version terkait. Jangan mengambil jawaban terkini untuk mengganti isi PDF atau snapshot versi lama.
- Jika PDF Mustahik yang diwajibkan gagal dibuat pada proses approval, approval harus gagal. Status Outreach tetap `MENUNGGU_PENGESAHAN` dan notifikasi `OUTREACH_COMPLETED` tidak dikirim.
- Kolom skor pada PDF sumber tidak mengizinkan sistem menghitung skor atau memilih jawaban otomatis.

## 7. Batas Perubahan Database

Pembaruan spesifikasi ini hanya mencatat hasil audit form. Database v1.1 dengan 22 tabel tetap terkunci; tidak ada perubahan tabel, kolom, constraint, migration, atau mekanisme scoring yang diizinkan secara implisit. Workflow v2.1 adalah baseline aktif.
