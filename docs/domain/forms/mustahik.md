# Mustahik Form Specification — v1.7

Revisi Oktober 2026: register 25 pertanyaan dilengkapi dan diselaraskan secara literal dengan PDF sumber (D-022); `docs/project-management/decisions.md` memuat keputusannya. Revisi 27 Oktober 2026: Tanggungan dan Hutang ditetapkan single-choice (D-035) dan sumber data header form ditetapkan (D-036).

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
| Cardinality | **Single-choice untuk seluruh 25 pertanyaan**, termasuk `PHYSICAL_DEPENDENTS` dan `PHYSICAL_DEBT` (D-035). |
| Required | **Seluruh 25 pertanyaan wajib dijawab** sebelum finalisasi form Mustahik. Validasi tetap diperlukan untuk perilaku tampilan dan representasi PDF. |
| Snapshot/PDF | Snapshot harus merekam jawaban yang disubmit. Representasi final pada PDF masih perlu validasi. |
| Signature | Perubahan data form yang relevan memerlukan pembaruan tanda tangan sesuai workflow. Perubahan attachment saja tidak memicu tanda tangan ulang. |
| Scoring | Sistem tidak memilih jawaban atau menghitung skor secara otomatis. Kolom skor yang tercetak pada PDF sumber tidak menjadi izin untuk menambahkan perhitungan otomatis. |
| Historical data | Jawaban historis tidak boleh berubah secara retroaktif ketika label, opsi, atau aturan form diperbarui. |

## 3. Register 25 Pertanyaan

PDF kosong adalah sumber label dan opsi untuk prototipe. Tabel di bawah membedakan informasi yang terlihat pada sumber dari aturan operasional yang masih memerlukan validasi. `question_code` berasal dari Database v1.1. **Semua 25 pertanyaan wajib dijawab**; belum adanya validasi untuk opsi, tampilan, atau PDF tidak mengubah status wajib tersebut.

| question_code | Label / bagian pada sumber | Opsi yang terlihat pada PDF sumber | Cardinality | Required/conditional | Snapshot/PDF |
|---|---|---|---|---|---|
| `HOUSE_BUILDING_AREA` | luas Bangunan | sangat Kecil (`<4x4`); Kecil (`4x 4 - 5x6`); sedang (`5 x 7 -5 x 15`); Lebar (`>5 x 15`) | Single | Wajib | Perlu validasi |
| `HOUSE_WALL_TYPE` | Jenis Dinding | Triplek/Sesek; Papan; Tembok; Tembok Keramik | Single | Wajib | Perlu validasi |
| `HOUSE_OWNERSHIP_STATUS` | Status Tempat Tinggal | Kost; Kontrak; Menumpang/keluarga; Milik Sendiri | Single | Wajib | Perlu validasi |
| `HOUSE_MCK` | Fasilitas MCK | Tidak Punya; WC Umum; Punya Tidak Layak; Punya Layak | Single | Wajib | Perlu validasi |
| `HOUSE_DRINKING_WATER` | Sumber Air Minum | Bukan Air bersih; Air Bersih (beli); Sumur; Sumur Air bersih | Single | Wajib | Perlu validasi |
| `HOUSE_KITCHEN` | Kondisi Dapur | Kayu Bakar; Kompor Minyak; LPG 3 KG; LPG 12 KG | Single | Wajib | Perlu validasi |
| `HOUSE_ASSET_OWNERSHIP` | Kepemilikan Aset | Tidak Punya; Sawah/Kebun `< 500 m2`; Sawah/Kebun `> 500 m2`; Sawah/Kebun `> 1000 m2` | Single | Wajib | Perlu validasi |
| `HOUSE_VEHICLE_OWNERSHIP` | Kepemilikan Kendaraan | tidak Punya; Ontel; Sepeda Motr/+Ontel; Mobil | Single | Wajib | Perlu validasi |
| `HOUSE_FLOOR_TYPE` | Jenis Lantai | Tanah; Plester; Tekel/Ubin; Keramik | Single | Wajib | Perlu validasi |
| `HOUSE_ROOF_TYPE` | Jenis Atap | Genting/Asbas (bocor); Asbas; Genting; Dak/cor | Single | Wajib | Perlu validasi |
| `ECONOMIC_HEAD_EDUCATION` | Pendidikan Kepala Rumah tangga | Tidak Sekolah; Sd-smp; SMA; Perguruan Tinggi | Single | Wajib | Perlu validasi |
| `ECONOMIC_HEAD_OCCUPATION` | Pekerjaan Kepala Rumah Tangga | Tidak bekerja; Buruh tani/Serabutan; Petani; Karyawan/Tukang | Single | Wajib | Perlu validasi |
| `ECONOMIC_MONTHLY_INCOME` | Total Pendapatan 1 bulan | `< 300.000`; `< 750.000,-`; `< 1.500.000`; `> 3.500.000,-` | Single | Wajib | Perlu validasi |
| `ECONOMIC_FOOD_PURCHASE` | Kemampuan Membeli daging/ayam/Susu dalam Seminggu | Tidak Mampu; 1 kali; 2 Kali; 3 Kali | Single | Wajib | Perlu validasi |
| `ECONOMIC_CLOTHING_PURCHASE` | Kemampuan Membeli Pakaian Baru | 1 kali Setahun; 2 Kali Setahun; 3 Kali Setahun; 5 Kali Setahun | Single | Wajib | Perlu validasi |
| `ECONOMIC_OTHER_SUBSIDY` | Asumsi Subsidi Lain | Tidak Ada; Sumbangan Rutin; Kerabat /Orang lain; Anak | Single | Wajib | Perlu validasi |
| `ECONOMIC_MEAL_FREQUENCY` | Frekuensi Makan dalam sehari | 1 kali; 2 Kali; 3 Kali; 4 Kali | Single | Wajib | Perlu validasi |
| `ECONOMIC_LIGHTING` | Jenis Penerangan | Tidak Ada; Listrik 450; Listrik 900; Listrik `> 900` | Single | Wajib | Perlu validasi |
| `ECONOMIC_SIDE_BUSINESS` | Usaha Sampingan | Tidak ada; Ada, tidak jalan; Ada, hasil tidak rutin; Ada, hasil rutin | Single | Wajib | Perlu validasi |
| `ECONOMIC_GOVERNMENT_AID` | Bansos Pemerintah | PKH; BPNT / Raskin; PBI JKN / KIS; Tidak Ada | Single | Wajib | Perlu validasi |
| `PHYSICAL_CONDITION` | Keadaan Fisik | Manula Sakit; Manula; Cacat Produktif; Sehat/Produktif | Single | Wajib | Perlu validasi |
| `PHYSICAL_DEPENDENTS` | Tanggungan | Anak Yang Masih Sekolah; Lansia (tidak produktif); Keluarga lainya; Tidak ada | **Single** | Wajib | Perlu validasi |
| `PHYSICAL_DEBT` | Hutang | Rentenir; Nonrentenir; Tidak ada; Bank | **Single** | Wajib | Perlu validasi |
| `PHYSICAL_HEALTH_CAPABILITY` | Kemampuan Memenuhi Kesehatan | Tidak ada; KIS/ PBI JKN; BPJS Kelas 3; BPJS Kelas 2 | Single | Wajib | Perlu validasi |
| `PHYSICAL_SAVINGS` | Tabungan | Tidak ada; Emas `< 5 grm`; Emas `> 5 grm`; Emas `> 10 grm` | Single | Wajib | Perlu validasi |

### Catatan fidelitas sumber

- Rentang luas bangunan dan pendapatan ditulis mengikuti teks PDF sumber. Rentang pendapatan yang tumpang tindih atau menyisakan celah tidak dinormalisasi.
- Opsi berikut beririsan atau menyisakan celah pada sumber dan dipertahankan apa adanya: tabungan `Emas > 5 grm` dan `Emas > 10 grm`; aset `Sawah/Kebun > 500 m2` dan `> 1000 m2`; pendapatan `< 300.000` dan `< 750.000,-` beririsan, serta tidak ada opsi untuk Rp1.500.000 sampai Rp3.500.000 maupun untuk nilai tepat di batas (mis. emas 5 gram). Klarifikasi ke pemilik form berstatus `PENDING VALIDATION` (D-023).
- Opsi keempat `Anak` pada pertanyaan Asumsi Subsidi Lain, serta label dan opsi delapan pertanyaan lain yang sebelumnya belum ditranskripsikan, dicocokkan dengan teks dan tampilan visual PDF sumber (D-022). Ejaan sumber seperti `Tekel`, `Motr`, `Asbas`, dan `lainya` dipertahankan.
- Ejaan, istilah, satuan, dan urutan opsi pada prototipe harus mengikuti PDF sumber. Bila pembacaan teks/OCR tidak cukup untuk memastikan posisi label atau opsi, periksa tampilan visual PDF; jangan menebak.
- Kolom skor pada PDF sumber tidak berarti aplikasi harus menghitung atau memilih skor otomatis. Perilaku skor masih perlu dikonfirmasi kepada pemilik form jika diperlukan dalam alur kerja.

### Petunjuk pengisian opsi yang beririsan

Petugas memilih opsi yang paling mendekati kondisi sebenarnya (misalnya emas 12 gram memilih `Emas > 10 grm`). Ini panduan pengisian, bukan perubahan opsi, tidak menormalisasi rentang, dan bukan aturan scoring (D-022).

### Header form dan Data Kepala Keluarga (D-036)

Bagian header PDF sumber diisi dari sumber berikut. Label PDF sumber tidak diubah (D-022), dan tidak ada perubahan schema.

| Bagian pada PDF sumber | Sumber data | Catatan |
|---|---|---|
| Nama Petugas | Nama user dari `mustahik_forms.petugas_id` | Read-only |
| Tanggal/Hari (Survey) | `mustahik_forms.survey_date` | Hari diturunkan dari tanggal |
| Kelurahan dan Kecamatan (header survei) | Konstanta konfigurasi: Kelurahan Manukan Kulon, Kecamatan Tandes | Bukan data per Outreach |
| Nama, NIK/No KK Kepala Keluarga | `kepala_keluarga_nama`, `kepala_keluarga_nik_no_kk` | Data khusus Mustahik; Klien Outreach tidak otomatis dianggap Kepala Keluarga |
| Alamat Kepala Keluarga: Jalan, RT, RW | `kepala_keluarga_alamat`, `kepala_keluarga_rt`, `kepala_keluarga_rw` | Data khusus Mustahik |
| Alamat Kepala Keluarga: Kelurahan dan Kecamatan | Konstanta konfigurasi yang sama | Bukan data per Outreach |
| Nomor Telepon HP | `outreaches.no_telp` (field 15 Outreach) | Kontak Outreach, **bukan** nomor Kepala Keluarga yang terverifikasi; tidak ada kolom nomor Kepala Keluarga pada Database v1.1 |

**Aturan wajib header (D-048, VAL-005 selesai).** Seluruh field header yang diisi Petugas wajib sebelum Finalisasi: Tanggal survei (`survey_date`), Nama, NIK/No KK, Alamat (Jalan), RT, dan RW Kepala Keluarga. Draft boleh kosong. Tanggal survei terisi awal dari Tanggal Outreach saat form pertama kali dibuka dan tetap dapat diubah. Tombol eksplisit "Salin dari data Klien" menyalin Nama, NIK, dan Alamat Domisili Klien bila Klien memang Kepala Keluarga; penyalinan tidak pernah terjadi otomatis, dan RT/RW diisi manual.

## 4. Data Konteks dan Batas Perilaku

Data Outreach boleh ditampilkan sebagai konteks bagi petugas, tetapi tidak boleh otomatis memilih jawaban Mustahik. Jawaban harus dipilih secara eksplisit pada form Mustahik.

Perubahan spesifikasi tidak mengubah jawaban pada versi historis. Jika label atau opsi nantinya diklarifikasi oleh pemilik form, perubahan diterapkan secara terkendali dan tidak menulis ulang submission yang sudah disimpan.

## 5. Hal yang Masih Memerlukan Validasi

1. Validasi pemilik form atas penerapan 25 pertanyaan wajib, termasuk penanganan input yang belum lengkap sebelum finalisasi.
2. Konfirmasi pemetaan `question_code` terhadap posisi pertanyaan pada PDF. Label dan opsi register telah dilengkapi dan dicocokkan dengan teks serta tampilan visual PDF (D-022); pemetaan kode ke posisi mengikuti nama dan urutan kode.
3. Apakah kolom skor diisi manual, hanya dicetak, atau memiliki aturan lain. Tidak ada scoring otomatis sebelum ada keputusan eksplisit.
4. Representasi jawaban pada PDF hasil aplikasi. Sumber data header (D-036) dan aturan wajib header (D-048) sudah ditetapkan.
5. Persetujuan formal pemilik form terhadap PDF sumber yang dipakai sebagai acuan prototipe.
6. Klarifikasi opsi yang beririsan atau menyisakan celah (D-023).

Hal-hal yang belum divalidasi di atas tidak menghalangi pencatatan keputusan single-choice, tetapi harus diselesaikan sebelum perilaku final yang bergantung padanya dianggap disetujui.

## 6. Output PDF dan Approval Gate

- Mustahik hanya berlaku untuk Source Baznas dan CSR; Bangga Surabaya tidak menggunakan form ini.
- PDF Mustahik dihasilkan sebagai file terpisah dari PDF Outreach dan PDF Kifayah untuk alur Baznas/CSR.
- Jawaban yang dirender harus berasal dari jawaban yang tersimpan pada submission version terkait. Jangan mengambil jawaban terkini untuk mengganti isi PDF atau snapshot versi lama.
- Jika PDF Mustahik yang diwajibkan gagal dibuat pada proses approval, approval harus gagal. Status Outreach tetap `MENUNGGU_PENGESAHAN` dan notifikasi `OUTREACH_COMPLETED` tidak dikirim.
- Kolom skor pada PDF sumber tidak mengizinkan sistem menghitung skor atau memilih jawaban otomatis.

## 7. Batas Perubahan Database

Pembaruan spesifikasi ini hanya mencatat hasil audit form. Database v1.1 dengan 22 tabel tetap terkunci; tidak ada perubahan tabel, kolom, constraint, migration, atau mekanisme scoring yang diizinkan secara implisit. Workflow v2.1 adalah baseline aktif.
