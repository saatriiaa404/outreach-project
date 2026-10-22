# Kifayah Form Specification --- v1.7

## 1. Authority dan Batasan

-   Kifayah berlaku hanya untuk Source `BAZNAS` dan `CSR`;
    `BANGGA_SURABAYA` tidak menggunakan form Kifayah.
-   Database v1.1 dan batas 22 tabel tetap **LOCKED**. Dokumen ini
    memperjelas perilaku form berdasarkan keputusan yang sudah
    dikonfirmasi; tidak mengubah schema, migration, atau
    `question_code`.
-   Data Outreach hanya menjadi informasi kontekstual. Jawaban Kifayah
    tidak boleh dipilih atau diisi otomatis dari data Outreach.
-   Kifayah disimpan sebagai data form terstruktur. Snapshot historis
    harus merepresentasikan data pada saat versi dibuat; jawaban atau
    snapshot historis tidak boleh dimutasi secara retroaktif.
-   Form Kifayah ditandatangani Warga/Klien sebagai acknowledgement.
    Tidak ada field `statement_acknowledged`; identitas tanda tangan
    tetap mengikuti baseline database.

## 2. Fixed Field Register

  ------------------------------------------------------------------------------
  Field database                Label pada sumber Tipe           Aturan nilai /
                                                                 status
  ----------------------------- ----------------- -------------- ---------------
  `income_head_family`          Pendapatan Kepala Nominal        Wajib diisi;
                                Keluarga per                     nilai numerik
                                Bulan                            minimal 0;
                                                                 nilai negatif
                                                                 dan input
                                                                 nonnumerik
                                                                 ditolak

  `income_other_members`        Pendapatan        Nominal        Wajib diisi;
                                Anggota Lain per                 nilai numerik
                                Bulan                            minimal 0;
                                                                 nilai negatif
                                                                 dan input
                                                                 nonnumerik
                                                                 ditolak

  `expense_head_household`      Kepala Rumah      Nominal        Wajib diisi
                                Tangga                           eksplisit;
                                                                 nilai numerik
                                                                 minimal 0;
                                                                 nilai negatif
                                                                 dan input
                                                                 nonnumerik
                                                                 ditolak; `0`
                                                                 diperbolehkan
                                                                 bila tidak ada
                                                                 pengeluaran;
                                                                 tidak otomatis
                                                                 mengisi field
                                                                 kosong

  `expense_housewife`           Ibu Rumah Tangga  Nominal        Wajib diisi
                                                                 eksplisit;
                                                                 nilai numerik
                                                                 minimal 0;
                                                                 nilai negatif
                                                                 dan input
                                                                 nonnumerik
                                                                 ditolak; `0`
                                                                 diperbolehkan
                                                                 bila tidak ada
                                                                 pengeluaran
                                                                 atau kategori
                                                                 tidak berlaku

  `expense_adult_working`       Anak Dewasa       Nominal        Wajib diisi
                                (Bekerja)                        eksplisit;
                                                                 nilai numerik
                                                                 minimal 0;
                                                                 nilai negatif
                                                                 dan input
                                                                 nonnumerik
                                                                 ditolak; `0`
                                                                 diperbolehkan
                                                                 bila tidak ada
                                                                 pengeluaran
                                                                 atau kategori
                                                                 tidak berlaku

  `expense_adult_not_working`   Anak Dewasa       Nominal        Wajib diisi
                                (Tidak Bekerja)                  eksplisit;
                                                                 nilai numerik
                                                                 minimal 0;
                                                                 nilai negatif
                                                                 dan input
                                                                 nonnumerik
                                                                 ditolak; `0`
                                                                 diperbolehkan
                                                                 bila tidak ada
                                                                 pengeluaran
                                                                 atau kategori
                                                                 tidak berlaku

  `expense_school_sma`          Anak Sekolah      Nominal        Wajib diisi
                                (SMA/Setingkat)                  eksplisit;
                                                                 nilai numerik
                                                                 minimal 0;
                                                                 nilai negatif
                                                                 dan input
                                                                 nonnumerik
                                                                 ditolak; `0`
                                                                 diperbolehkan
                                                                 bila tidak ada
                                                                 pengeluaran
                                                                 atau kategori
                                                                 tidak berlaku

  `expense_school_smp`          Anak Sekolah      Nominal        Wajib diisi
                                (SMP/Setingkat)                  eksplisit;
                                                                 nilai numerik
                                                                 minimal 0;
                                                                 nilai negatif
                                                                 dan input
                                                                 nonnumerik
                                                                 ditolak; `0`
                                                                 diperbolehkan
                                                                 bila tidak ada
                                                                 pengeluaran
                                                                 atau kategori
                                                                 tidak berlaku

  `expense_school_sd`           Anak Sekolah      Nominal        Wajib diisi
                                (SD/Setingkat)                   eksplisit;
                                                                 nilai numerik
                                                                 minimal 0;
                                                                 nilai negatif
                                                                 dan input
                                                                 nonnumerik
                                                                 ditolak; `0`
                                                                 diperbolehkan
                                                                 bila tidak ada
                                                                 pengeluaran
                                                                 atau kategori
                                                                 tidak berlaku

  `expense_baby_not_school`     Anak/Bayi Belum   Nominal        Wajib diisi
                                Sekolah                          eksplisit;
                                                                 nilai numerik
                                                                 minimal 0;
                                                                 nilai negatif
                                                                 dan input
                                                                 nonnumerik
                                                                 ditolak; `0`
                                                                 diperbolehkan
                                                                 bila tidak ada
                                                                 pengeluaran
                                                                 atau kategori
                                                                 tidak berlaku

  `expense_disability`          Penyandang        Nominal        Wajib diisi
                                Disabilitas                      eksplisit;
                                                                 nilai numerik
                                                                 minimal 0;
                                                                 nilai negatif
                                                                 dan input
                                                                 nonnumerik
                                                                 ditolak; `0`
                                                                 diperbolehkan
                                                                 bila tidak ada
                                                                 pengeluaran
                                                                 atau kategori
                                                                 tidak berlaku

  `expense_breastfeeding`       Ibu Menyusui      Nominal        Wajib diisi
                                                                 eksplisit;
                                                                 nilai numerik
                                                                 minimal 0;
                                                                 nilai negatif
                                                                 dan input
                                                                 nonnumerik
                                                                 ditolak; `0`
                                                                 diperbolehkan
                                                                 bila tidak ada
                                                                 pengeluaran
                                                                 atau kategori
                                                                 tidak berlaku

  `expense_pregnant`            Ibu Hamil         Nominal        Wajib diisi
                                                                 eksplisit;
                                                                 nilai numerik
                                                                 minimal 0;
                                                                 nilai negatif
                                                                 dan input
                                                                 nonnumerik
                                                                 ditolak; `0`
                                                                 diperbolehkan
                                                                 bila tidak ada
                                                                 pengeluaran
                                                                 atau kategori
                                                                 tidak berlaku

  `expense_widow_not_working`   Status Janda      Nominal        Wajib diisi
                                (Tidak Bekerja)                  eksplisit;
                                                                 nilai numerik
                                                                 minimal 0;
                                                                 nilai negatif
                                                                 dan input
                                                                 nonnumerik
                                                                 ditolak; `0`
                                                                 diperbolehkan
                                                                 bila tidak ada
                                                                 pengeluaran
                                                                 atau kategori
                                                                 tidak berlaku

  `higher_education_count`      Jumlah Keluarga   Integer        Wajib diisi;
                                yang Sedang                      bilangan bulat
                                Menempuh                         minimal 0;
                                Pendidikan Tinggi                nilai negatif
                                                                 dan input
                                                                 nonnumerik
                                                                 ditolak; tidak
                                                                 ada batas
                                                                 maksimum
                                                                 arbitrer yang
                                                                 ditetapkan
  ------------------------------------------------------------------------------

### 2.1 Arti `0`, `NULL`, dan field kosong

-   `0`: Petugas menyatakan tidak ada pengeluaran atau kategori tersebut
    tidak berlaku.
-   `NULL`/belum diisi: Petugas belum mengisi atau belum memastikan
    nilainya. Untuk pengeluaran, seluruh kategori wajib diberi nilai
    secara eksplisit sebelum form dapat difinalisasi.
-   Sistem **tidak boleh** mengubah field kosong menjadi `0` secara
    otomatis.
-   Perbedaan antara `0` dan belum diisi harus tetap terlihat saat
    validasi form dan saat menyusun PDF/snapshot.
-   Semua field nominal dan hitungan harus menerima input numerik yang
    valid dan bernilai minimal 0. Tolak nilai negatif dan input
    nonnumerik.

## 3. Register Pertanyaan Pilihan

Pertanyaan pilihan bersifat **single-choice**: satu jawaban aktif untuk
setiap `question_code`. Nama kode mengikuti baseline Database v1.1 yang
terkunci.

  ----------------------------------------------------------------------------------
  `question_code`     Label pada     Opsi yang         Cardinality   Applicability
                      Form Had       tercetak pada                   
                      Kifayah        sumber                          
  ------------------- -------------- ----------------- ------------- ---------------
  `HOUSE_OWNERSHIP`   Kepemilikan    Tidak memiliki    Single        Baznas/CSR
                      Tempat Tinggal rumah;                          
                                     menyewa/kontrak                 

  `ELECTRICITY`       Daya Listrik   450 watt; 900     Single        Baznas/CSR
                                     watt; lebih dari                
                                     900 watt                        

  `GOVERNMENT_AID`    Bantuan        Ya; Tidak         Single        Baznas/CSR
                      Pemerintah                                     

  `BPJS_KIS_PBI`      BPJS/KIS/PBI   Ya; Tidak         Single        Baznas/CSR

  `MOTOR_VEHICLE`     Kendaraan      Ya; Tidak         Single        Baznas/CSR
                      Bermotor                                       

  `SAVINGS`           Tabungan       Ya; Tidak         Single        Baznas/CSR

  `DEBT`              Hutang         Ya; Tidak         Single        Baznas/CSR
  ----------------------------------------------------------------------------------

**Catatan validasi sumber:** daftar di atas merangkum label dan opsi
yang terbaca dari formulir Had Kifayah. Pastikan ejaan, pemetaan
label-ke-kode, dan seluruh opsi terhadap salinan PDF sumber sebelum
dianggap sebagai transkripsi final. Jika PDF memuat rincian opsi
tambahan yang tidak tercantum di tabel ini, jangan menambahkannya
berdasarkan asumsi.

## 4. Identitas Mustahik dan Penggunaan Data Outreach

Form Had Kifayah menampilkan informasi identitas berikut:

-   Nama
-   NIK
-   Alamat Tempat Tinggal
-   Kelurahan
-   Kecamatan
-   No. HP

Belum diputuskan apakah identitas ini diisi mandiri pada form Kifayah
atau ditampilkan dari data Outreach yang terkait. Karena itu, jangan
mengasumsikan perilaku salin otomatis atau membuat sumber data baru.
Perilaku tersebut perlu ditetapkan saat validasi tampilan form.

## 5. PDF, Snapshot, dan Approval

-   Untuk Source `BAZNAS` dan `CSR`, PDF Kifayah merupakan keluaran
    terpisah yang wajib dibuat bersama PDF Outreach dan PDF Mustahik.
-   PDF Kifayah memuat identitas Mustahik, pendapatan, kategori
    pengeluaran bulanan, jumlah anggota keluarga yang menempuh
    pendidikan tinggi, tujuh pertanyaan pilihan, tanggal yang mengikuti
    tanggal Outreach tersimpan, serta bagian tanda tangan sesuai sumber.
-   Tanggal/tahun pada PDF Kifayah mengikuti tanggal Outreach yang
    tersimpan. Jangan menggunakan tahun statis dari contoh formulir
    sebagai nilai tetap.
-   Snapshot historis menyimpan representasi data Kifayah pada saat
    versi dibuat, bukan binary PDF atau lampiran.
-   Jika pembuatan PDF Kifayah yang diwajibkan gagal saat pengesahan,
    approval harus gagal dengan alasan yang jelas. Status Outreach tetap
    `MENUNGGU_PENGESAHAN`, tidak berubah menjadi `SELESAI`, dan
    notifikasi `OUTREACH_COMPLETED` tidak dikirim.
-   Perubahan pada data form Kifayah setelah tanda tangan harus
    mengikuti aturan workflow untuk memperbarui tanda tangan yang
    relevan. Perubahan yang hanya terjadi pada lampiran tidak mengubah
    data Kifayah dan tidak memicu re-sign.
-   Jangan menambahkan kalkulasi skor otomatis. Perilaku kolom skor yang
    terlihat pada formulir sumber belum ditetapkan sebagai fitur
    aplikasi.

## 6. Aturan Implementasi yang Sudah Dikonfirmasi

1.  Input Kifayah dilakukan oleh Petugas.
2.  Satu Outreach memiliki maksimal satu form Kifayah aktif sesuai
    baseline database.
3.  Setiap `question_code` memiliki satu jawaban aktif; jawaban harus
    berasal dari opsi pertanyaan tersebut.
4.  Data Outreach tidak boleh memilih jawaban Kifayah secara otomatis.
5.  Semua kategori pengeluaran wajib diisi secara eksplisit sebelum
    finalisasi. `0` dapat dipakai ketika tidak ada pengeluaran atau
    kategori tidak berlaku; `NULL` berarti belum diisi. Jangan
    menyamakan keduanya.
6.  Input nominal dan hitungan harus numerik dan minimal 0; tolak nilai
    negatif atau nonnumerik. Jangan menetapkan batas maksimum arbitrer
    untuk jumlah anggota keluarga yang menempuh pendidikan tinggi.
7.  Tahun/tanggal PDF Kifayah mengikuti tanggal Outreach yang tersimpan.
8.  Kegagalan membuat PDF Kifayah yang diwajibkan menggagalkan approval;
    status tetap `MENUNGGU_PENGESAHAN` dan notifikasi
    `OUTREACH_COMPLETED` tidak dikirim.
9.  Tidak ada tabel `family_members` baru pada baseline ini.
10. Jangan mengubah Database v1.1, menambah/mengganti kode pertanyaan,
    atau membuat migration sebagai bagian dari pembaruan spesifikasi
    ini.

## 7. Hal yang Masih Perlu Validasi

-   Apakah identitas Mustahik diketik ulang atau ditampilkan dari
    Outreach.
-   Transkripsi final label dan opsi tiap pertanyaan dengan pemeriksaan
    visual terhadap PDF sumber.
-   Makna dan perlakuan kolom skor pada formulir sumber; tidak ada
    scoring otomatis yang disetujui.
-   Format dan posisi tanda tangan pada PDF keluaran.
-   Validasi formal oleh Kelurahan bahwa spesifikasi ini sesuai dengan
    formulir Had Kifayah yang digunakan.

## 8. Batas Perubahan

Spesifikasi v1.7 mendokumentasikan aturan Kifayah yang telah
dikonfirmasi, termasuk kewajiban pengisian field, validasi nilai
numerik nonnegatif, semantik `0`/`NULL`, perilaku PDF dan approval, serta
batas implementasi. Pembaruan ini memperjelas perilaku form, tetapi
**bukan** persetujuan untuk mengubah schema Database v1.1, batas 22
tabel, migration, atau `question_code`, maupun membuka kembali keputusan
yang sudah LOCKED.
