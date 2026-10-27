# Canonical Reconciliation Matrix — Checkpoint v1.6

## Aturan

Matriks ini adalah evidence register, bukan pengganti dokumen kanonis.

`VERIFIED locally` berarti sumber lokal ditemukan dan dibandingkan dalam cakupan yang dinyatakan. Status ini bukan persetujuan Kelurahan atau pemilik formulir atas aturan yang masih `PENDING VALIDATION`.

Status task dan status verifikasi evidence merupakan dua hal berbeda. Task tidak boleh ditandai selesai hanya karena file sumber tersedia.

## Matriks Rekonsiliasi

| Concern | Dokumen kanonis | Sumber/evidence | Status verifikasi | Keputusan / blocker |
|---|---|---|---|---|
| Otoritas checkpoint | README, AGENTS, index, decisions | Checkpoint v1.6 yang dikelola di luar repository; arsip checkpoint v1.5 | IN PROGRESS | D-005 berstatus SUPERSEDED; D-009 menetapkan checkpoint v1.6 sebagai snapshot perencanaan terbaru. Checkpoint dikelola terpisah dari repository sehingga matriks ini tidak menggunakan tautan relatif yang mengarah ke path yang belum terverifikasi. Nama checkpoint tetap v1.6 untuk revisi dokumentasi biasa; checkpoint bernomor baru memerlukan keputusan eksplisit. |
| Workflow/state/role boundary | `architecture/workflow.md`, `roles-permissions.md` | Workflow v2.1 dan dokumen kanonis terkait | VERIFIED baseline; cross-document review pending | Baseline workflow v2.1 tetap LOCKED. |
| Database dan model data | `architecture/database.md` | Database v1.1 | VERIFIED baseline | Baseline 22 tabel tetap LOCKED; tidak ada perubahan schema yang diotorisasi. |
| Product dan scope | `product/PRD.md`, `product/scope.md` | Checkpoint v1.6 dan dokumen aktif | PARTIAL | Review per concern masih diperlukan sebelum implementasi. |
| Form Outreach | `domain/forms/outreach.md` | `blank_outreach.pdf`; catatan historis tentang PDF Outreach terisi menurut keterangan pengguna; checkpoint v1.6. PDF terisi tidak ditemukan dalam `project(20261018-170917).zip` terbaru yang diperiksa. | PARTIAL | `outreach.md` menjadi spesifikasi aktif prototype (D-032); desil, kendaraan, MCK, dan Bantuan ditutup oleh D-019 sampai D-021 dan D-027; lampiran mengikuti D-033 dan D-034; NIP Kepala mengikuti D-041 (format cetak masih pending). Sisa: evidence VAL-002 dan OUT-VAL-05, format NIP, serta verifikasi output PDF. PDF terisi historis dilaporkan memuat data warga asli; klaim persetujuan berasal dari keterangan pengguna dan tidak diverifikasi secara independen. Jangan masukkan file asli ke paket handover/coding; gunakan template kosong, data sintetis, atau salinan tersamarkan. |
| Form Mustahik | `domain/forms/mustahik.md` | `mustahik.pdf`; mapping 25 `question_code`; spesifikasi Mustahik aktif. | PARTIAL | Aturan yang telah ditetapkan: seluruh 25 pertanyaan single-choice dan wajib dijawab sebelum finalisasi; tidak ada auto-selection, scoring, atau mesin kelayakan yang diasumsikan. Tanggungan dan Hutang single-choice (D-035); sumber data header ditetapkan (D-036); opsi beririsan menunggu klarifikasi pemilik form (D-023). Masih pending: aturan wajib field header, representasi snapshot/PDF final, dan validasi formal. |
| Form Kifayah | `domain/forms/kifayah.md` | `kifayah.pdf`; fixed fields dan 7 `question_code`; Kifayah Form Specification v1.7. | PARTIAL | Aturan yang telah ditetapkan pada v1.7: field mengikuti required rules pada register; nominal/hitungan wajib numerik dan minimal 0, nilai negatif/nonnumerik ditolak; semua kategori pengeluaran wajib diisi eksplisit; `0` berarti tidak ada pengeluaran/kategori tidak berlaku dan `NULL` berarti belum diisi; tidak ada batas maksimum arbitrer untuk `higher_education_count`; tanggal/tahun PDF mengikuti tanggal Outreach tersimpan; snapshot menyimpan data terstruktur per versi; kegagalan PDF wajib menggagalkan approval. Identitas ditampilkan read-only dari Outreach (D-036). Masih pending: transkripsi literal opsi dari PDF oleh pemilik proyek (D-037), makna kotak centang di samping nominal pengeluaran (PDF Kifayah tidak memuat kolom skor), format/posisi tanda tangan dan validasi visual PDF, serta validasi formal Kelurahan. Jangan otomatis mengubah isian kosong menjadi `0`. Kriteria FORM-002 pada `tasks.md` sudah diselaraskan dengan Kifayah v1.7 dalam paket terbaru. Status task tetap `IN PROGRESS` karena evidence pemetaan, validasi sumber, dan validasi formal belum seluruhnya terpenuhi. |
| Permission/security/UX | Dokumen concern terkait | Workflow v2.1, checkpoint v1.6, dokumen kanonis | PARTIAL | Periksa konsistensi dan dampak tanpa mengubah baseline secara diam-diam. |
| Persistence prototype | `prototype/`, `tasks.md`, `testing.md`, `decisions.md` | D-010 dan dokumen prototype/testing yang diperbarui | DECISION RECORDED; task evidence pending | D-008 berstatus SUPERSEDED oleh D-010. Prototype memakai database lokal non-produksi dan data dummy, satu komputer demo, tanpa hosting/domain; data diharapkan bertahan setelah aplikasi dimulai ulang. Ini tidak mengotorisasi perubahan schema/migration dan tidak membuktikan PERS-001 selesai sebelum kriteria testing/evidence dipenuhi. |
| Registry sumber dan path | `docs/index.md`, `docs/reference/` | Isi paket workspace dan file yang diperbarui terpisah | IN PROGRESS | Verifikasi registry, nama file, dan lokasi terhadap artefak aktual workspace `project/`. File hasil pembaruan yang diunduh harus menggantikan file asal secara manual oleh pengguna; keberadaan file di percakapan atau salinan terpisah tidak membuktikan path workspace aktif telah diverifikasi. Referensi berisi data warga asli harus dipisahkan dari paket handover/coding dan tidak dicantumkan pada source control yang dibagikan. |

## Catatan Evidence Form

- Keberadaan PDF membuktikan bahwa file sumber tersedia di workspace, bukan persetujuan formal pemilik formulir.
- PDF Outreach terisi yang dimaksud pengguna memuat data warga asli; pengguna menyatakan persetujuan warga telah diperoleh. Matriks ini mencatat keterangan tersebut, tetapi tidak mengklaim telah memverifikasi persetujuan secara independen. Jangan menyebut file tersebut sebagai data dummy. File terisi itu tidak ditemukan dalam `project(20261018-170917).zip` terbaru yang diperiksa; ini tidak membuktikan status file di luar paket tersebut. Jangan sertakan file asli dalam paket coding/handover Claude atau salinan repository yang dibagikan. Untuk memeriksa tata letak, gunakan PDF kosong, data sintetis, atau salinan yang telah disamarkan dengan benar.
- PDF kosong tetap menjadi referensi bentuk form.
- Contoh PDF terisi Mustahik dan Kifayah belum tercatat tersedia dalam checkpoint.
- Jangan mengarang opsi, scoring, cardinality, required rules, atau perilaku PDF yang tidak didukung sumber.
- Perbedaan nama file dalam paket harus direkonsiliasi dengan file aktual; jangan menganggap nama file yang diharapkan pasti ada.

## Rekonsiliasi Outreach — Detail yang Belum Tervalidasi

1. **Lampiran:** empat kategori merupakan lampiran wajib baseline dan harus berupa gambar (D-034). Lampiran tambahan tidak memblokir Finalisasi pada prototype (D-033); persyaratan resminya berdasarkan Type atau Source masih `PENDING VALIDATION`.
2. **Status Kesejahteraan:** ditutup oleh D-019 (desil 1–10 dengan label otomatis); evidence sumber resmi label masih tertunda (VAL-002).
3. **Kendaraan:** ditutup oleh D-020 (angka per jenis pada kolom Database v1.1).
4. **Fasilitas MCK:** ditutup oleh D-021 (empat nilai pada `mck_layak`).
5. **PDF Outreach terisi:** pengguna menyatakan file terisi terdahulu menggunakan data warga asli dan persetujuan warga terkait telah diperoleh. Pernyataan persetujuan ini berasal dari keterangan pengguna, bukan hasil verifikasi independen. File tersebut tidak ditemukan dalam `project(20261018-170917).zip` terbaru yang diperiksa; jangan menganggap keterangan ini membuktikan status file di luar paket itu. File tidak boleh dijelaskan sebagai data dummy. Perlakukan sebagai data pribadi sensitif dan jangan sertakan dalam paket coding/handover; gunakan data sintetis, PDF kosong, atau salinan yang disamarkan untuk review.

Temuan tersebut tidak mengotorisasi perubahan Workflow v2.1, Database v1.1, cardinality, atau migration. Simpan setiap hasil validasi beserta sumber dan keputusan yang dapat ditelusuri.

## Keputusan Output PDF dan Arsip

### Keputusan output PDF terbaru

- **Baznas/CSR:** tiga PDF terpisah: (1) PDF Outreach dengan halaman lampiran wajib setelah formulir Outreach, (2) PDF Mustahik, dan (3) PDF Kifayah.
- **Bangga Surabaya:** PDF Outreach dengan halaman lampiran wajib setelah formulir Outreach.
- Pembuatan PDF dapat dilakukan saat dibutuhkan (*on demand*), tetapi pada proses approval semua PDF yang diwajibkan oleh jenis bantuan harus berhasil dibuat agar approval dapat berhasil.
- Jika pembuatan PDF wajib gagal selama approval, approval gagal; status tetap `MENUNGGU_PENGESAHAN`; sistem menampilkan alasan kegagalan yang jelas; dan notifikasi `OUTREACH_COMPLETED` tidak dikirim. Jangan menyimpan keputusan approval yang seolah-olah berhasil.
- Jika data formulir berubah setelah tanda tangan atau approval, tanda tangan yang relevan harus diperbarui dan alur approval yang berlaku dijalankan kembali. Perubahan lampiran saja tidak otomatis mewajibkan tanda tangan ulang. PDF tidak boleh menampilkan tanda tangan lama seolah menyetujui data formulir yang telah berubah.
- Riwayat versi, tanda tangan, approval terdahulu, dan referensi lampiran harus tetap dipertahankan. PDF harus merujuk data dan tanda tangan dari versi persetujuan terbaru yang sah.
- Workflow v2.1 dan Database v1.1/22 tabel tetap terkunci. Catatan rekonsiliasi ini tidak mengotorisasi perubahan schema, migration, atau seed.

### Keputusan arsip dan backup

Butir arsip dan backup di bawah ini dikukuhkan sebagai D-038 pada `decisions.md` (27 Oktober 2026), yang kini menjadi sumber kanonisnya.


- Data dan lampiran tetap berada di aplikasi setelah arsip. Batas satu bulan dihitung sejak persetujuan Kepala; Admin mengarsipkan secara manual ke folder lokal/E-Arsip, lalu menandai status arsip dan dapat menambahkan catatan/referensi bukti. Tidak ada integrasi langsung E-Surat/E-Arsip; PDF diunduh dan diunggah manual.
- ID internal aplikasi digunakan untuk pencarian/pelacakan, bukan sebagai nomor arsip resmi E-Surat.
- Backup dilakukan manual oleh Admin setiap minggu, mencakup database dan semua lampiran, dengan salinan lokal/drive dan media terpisah. PDF E-Surat bukan backup penuh aplikasi.
- Aturan lampiran tambahan berdasarkan Type/Source telah ditentukan untuk kebutuhan prototype dan tidak memblokir Finalisasi (D-033), tetapi tetap perlu ditandai terpisah dari bukti validasi formal pemilik formulir. Empat kategori lampiran Outreach baseline tetap wajib.

### Hal yang masih perlu direkonsiliasi/diverifikasi

- Pastikan seluruh dokumen terkait konsisten dengan keluaran PDF per jenis bantuan, halaman lampiran pada PDF Outreach, pembuatan *on demand*, kegagalan PDF wajib saat approval, koreksi setelah approval, dan preservasi riwayat versi.
- Tentukan bukti/log minimum untuk menjelaskan kegagalan pembuatan PDF wajib.
- Verifikasi bahwa implementasi hanya menyimpan approval yang berhasil setelah semua PDF wajib berhasil dibuat, serta menggunakan data dan tanda tangan versi persetujuan terbaru yang sah sambil mempertahankan versi terdahulu untuk audit.

## Gate Implementasi

`IMP-001` tetap `BLOCKED` sampai readiness review dilakukan dan pemilik proyek secara eksplisit membuka gate. Dependency task seperti `REC-002`, `OUT-001`, `FORM-001`, `FORM-002`, dan `DOC-001` harus ditinjau terhadap kriteria penyelesaian dan evidence masing-masing; jangan menandai task selesai hanya karena keputusan sudah dicatat. Keputusan D-010 menyelesaikan ketidakpastian keputusan persistence, tetapi tidak dengan sendirinya menyelesaikan task `PERS-001` atau membuka gate. `PERS-001` adalah acceptance test setelah gate dibuka, bukan prasyarat gate (lihat `tasks.md`). Validasi yang masih `PENDING VALIDATION` dapat tetap tertunda untuk prototype jika tidak mengubah perilaku kritis; statusnya harus dicatat dan tidak boleh dianggap sudah terverifikasi.

Concern berstatus `PARTIAL` harus direview dan hasilnya dicatat. Concern `BLOCKED` harus memperoleh keputusan atau evidence yang dibutuhkan. Status `VERIFIED locally` tidak menggantikan validasi pihak berwenang maupun persetujuan eksplisit pemilik proyek untuk membuka gate implementasi.

Pembaruan dokumentasi atau penyelesaian satu dependency tidak otomatis membuka gate. Tidak boleh memulai bootstrap aplikasi, source code, migration, schema, atau seed sebelum seluruh syarat tersebut terpenuhi dan gate dibuka secara eksplisit.


## Catatan Penyelarasan Cluster 5 — 18 Oktober 2026

Bagian ini melengkapi matriks di atas dan menjadi rujukan terbaru jika
masih ada frasa lama yang bertentangan dengan checkpoint v1.6.

- Baseline workflow aktif adalah **Workflow v2.1**. Referensi v2.0 pada
  catatan historis tidak lagi menjadi baseline aktif.
- Baseline database tetap **Database v1.1 dengan 22 tabel**; tidak ada
  otorisasi perubahan schema, migration, atau seed.
- Empat lampiran dasar Outreach wajib: KTP, KK, foto klien, dan foto
  rumah. Lampiran tambahan berdasarkan jenis bantuan dapat dipakai
  dalam prototype, tetapi validasi formalnya tetap `PENDING VALIDATION`.
- Baznas/CSR menghasilkan tiga PDF terpisah (Outreach beserta halaman
  lampiran, Mustahik, Kifayah). Bangga Surabaya menghasilkan PDF Outreach
  beserta halaman lampiran.
- Kegagalan PDF wajib selama approval menggagalkan approval. Status tetap
  `MENUNGGU_PENGESAHAN`, alasan harus ditampilkan, dan
  `OUTREACH_COMPLETED` tidak dikirim.
- `PENDING VALIDATION` bukan bukti selesai, tetapi juga tidak otomatis
  menjadi blocker menyeluruh untuk prototype. Dampak tiap item harus
  dinilai berdasarkan apakah ia memengaruhi perilaku bisnis kritis,
  authorization/security, workflow, atau baseline terkunci.
- Gate implementasi tetap **TERTUTUP**. Sebelum bootstrap, source code,
  schema, migration, atau seed, siapkan ringkasan kesiapan dan minta
  persetujuan eksplisit pemilik proyek.

## Catatan Penyelarasan Kifayah v1.7 dan Verifikasi Paket — 19 Oktober 2026

Pemeriksaan terhadap `project(20261018-170917).zip` memastikan bahwa `docs/domain/forms/kifayah.md` sudah menggunakan judul dan batas perubahan v1.7. Matriks ini membedakan aturan yang sudah ditetapkan dalam spesifikasi tersebut dari validasi yang masih terbuka:

- **Sudah ditetapkan:** fixed fields mengikuti required rules dalam register; input nominal dan hitungan harus numerik dan minimal 0; nilai negatif/nonnumerik ditolak; semua kategori pengeluaran diisi secara eksplisit; `0` dibedakan dari `NULL`; tidak ada batas maksimum arbitrer untuk `higher_education_count`; tanggal/tahun PDF mengikuti tanggal Outreach tersimpan; snapshot merepresentasikan data terstruktur pada versi dibuat; kegagalan membuat PDF Kifayah yang diwajibkan menggagalkan approval.
- **Masih perlu validasi (diperbarui 27 Oktober 2026):** identitas sudah ditetapkan ditampilkan dari Outreach (D-036); kolom skor tidak ada pada PDF Kifayah sehingga diganti pertanyaan tentang kotak centang; verifikasi visual label/opsi terhadap PDF `kifayah.pdf` dikerjakan sebagai transkripsi literal (D-037); format/posisi tanda tangan dan tampilan PDF akhir; serta konfirmasi formal Kelurahan. Item ini tidak boleh dianggap selesai tanpa evidence.
- **Sinkronisasi task:** `tasks.md` pada paket yang diperiksa sudah menyelaraskan kriteria FORM-001 dengan aturan Mustahik (25 pertanyaan wajib dijawab dan single-choice) serta FORM-002 dengan aturan Kifayah v1.7. Status kedua task tetap `IN PROGRESS` sampai evidence pemetaan, verifikasi visual label/opsi, detail metadata/snapshot/PDF, dan validasi formal yang relevan terpenuhi. Jangan menyatakan task selesai hanya karena redaksi kriteria telah disinkronkan.
- **Paket yang diverifikasi:** `project(20261018-170917).zip` berisi 26 file non-direktori, termasuk tiga PDF referensi (`blank_outreach.pdf`, `mustahik.pdf`, dan `kifayah.pdf`). PDF Outreach terisi yang historis tidak ditemukan dalam paket ini; hasil tersebut tidak membuktikan status file di luar paket.

Catatan ini tidak mengubah Workflow v2.1, Database v1.1/22 tabel, keputusan `LOCKED`, status task, atau gate implementasi. `IMP-001` tetap `BLOCKED` sampai readiness review dan persetujuan eksplisit pemilik proyek.

## Catatan Penyelarasan Audit Kesiapan — 27 Oktober 2026

Audit kesiapan Tahap 1 (PLAN-002) menghasilkan keputusan D-030 sampai D-042
pada `decisions.md`. Dokumen kanonis terdampak diselaraskan satu file per
giliran. Ringkasan dampaknya pada matriks ini:

- **Blocker audit yang telah diputuskan:** tabel infrastruktur Laravel di luar
  22 tabel domain (D-030); pengecualian invarian status setelah `Lock` dan
  event history baru (D-031); `outreach.md` menjadi spesifikasi aktif
  (D-032); lampiran tambahan tidak memblokir Finalisasi (D-033).
- **Keputusan lain:** lampiran gambar dan halaman rujukan PDF (D-034);
  Tanggungan dan Hutang single-choice (D-035); identitas Kifayah dan header
  Mustahik (D-036); transkripsi literal opsi Kifayah (D-037); arsip dan backup
  manual (D-038); visibilitas, dashboard, dan pencarian (D-039); master
  Source/Type dan larangan unduh PDF final oleh PETUGAS (D-040); kolom
  `users.nip` sebagai amandemen Database v1.1 (D-041); lokasi kode
  `project/web/` (D-042).
- **Masih tertunda:** klarifikasi opsi Mustahik (D-023, VAL-001); evidence
  label desil dan OUT-VAL-05 (VAL-002); transkripsi opsi Kifayah (D-037);
  format cetak NIP (D-041); aturan wajib header Mustahik; makna kotak centang
  Kifayah; serta validasi formal Kelurahan untuk ketiga form.

Catatan ini tidak membuka gate implementasi. `IMP-001` tetap `BLOCKED` sampai
readiness summary final disajikan dan pemilik proyek memberikan persetujuan
eksplisit.
