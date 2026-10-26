# Decision Log --- project

## Aturan Penggunaan

Log ini menyimpan keputusan yang memengaruhi baseline, scope, workflow,
arsitektur, data, keamanan, atau operasi. Setiap entri harus
mencantumkan status, sumber/evidence, dampak, dan dokumen yang
terdampak.

Status yang diizinkan:

-   `LOCKED` --- keputusan aktif yang telah disetujui.
-   `PENDING VALIDATION` --- menunggu konfirmasi atau validasi pihak
    terkait.
-   `SUPERSEDED` --- keputusan lama yang digantikan entri lain.
-   `BLOCKED` --- tidak boleh dilanjutkan sampai evidence atau
    persetujuan eksplisit tersedia.

Jangan menggunakan log ini untuk mengubah requirement secara tersirat.
Keputusan historis dipertahankan; jika digantikan, ubah statusnya dan
rujuk keputusan pengganti.

## Entri Keputusan

  -----------------------------------------------------------------------------------------------------------------------------------
  ID          Status       Keputusan / Isu        Sumber /        Dampak                  Dokumen terdampak
                                                  Evidence                                
  ----------- ------------ ---------------------- --------------- ----------------------- -------------------------------------------
  D-001       SUPERSEDED   Checkpoint aktif untuk Digantikan      Checkpoint aktif        README, index
                           `project1.1` belum     D-005.          kemudian ditetapkan     
                           direkonsiliasi.                        oleh D-005 dan          
                                                                  diperbarui melalui      
                                                                  D-009.                  

  D-002       SUPERSEDED   Fase aktif belum       Digantikan      Fase sinkronisasi       README, index
                           direkonsiliasi.        D-005.          dokumentasi ditetapkan  
                                                                  oleh keputusan          
                                                                  berikutnya.             

  D-003       SUPERSEDED   Seluruh `checkpoint/`  Digantikan      Checkpoint v1.5         README, index
                           diperlakukan sebagai   D-005.          sebelumnya menjadi      
                           arsip historis.                        pengecualian sebagai    
                                                                  acuan perencanaan       
                                                                  aktif.                  

  D-004       LOCKED       Keputusan tertunda     Arahan pemilik  Tidak boleh memperluas  Product, domain, architecture, prototype
                           tidak boleh dinaikkan  proyek.         scope berdasarkan       
                           menjadi requirement                    asumsi lama. Validasi   
                           final sebelum                          yang memang memerlukan  
                           prototype dan                          prototype atau          
                           konsultasi mitra,                      konfirmasi mitra tetap  
                           kecuali ada arahan                     dapat berstatus         
                           eksplisit yang                         `PENDING VALIDATION`.   
                           mendesak.                                                      

  D-005       SUPERSEDED   Pada saat keputusan    Arahan pemilik  Dipertahankan sebagai   README, index, dokumen hasil sinkronisasi
                           ini dibuat, checkpoint proyek;         riwayat keputusan.      
                           v1.5 adalah acuan      checkpoint      Otoritas snapshot       
                           perencanaan aktif dan  v1.5.           perencanaan terbaru     
                           fase aktif adalah                      dialihkan ke D-009; isi 
                           sinkronisasi                           historis D-005 tidak    
                           dokumentasi kanonis                    dihapus.                
                           sebelum prototype.                                             

  D-006       SUPERSEDED   Workflow v2.0 dan      Keputusan       Workflow v2.0           `docs/architecture/workflow.md`,
                           Database v1.1 / 22     historis;       digantikan sebagai      `docs/architecture/database.md`
                           tabel dipromosikan     Workflow v2.0   baseline aktif oleh     
                           sebagai dokumen        dan Database    D-011. Database v1.1 /  
                           kanonis aktif tanpa    v1.1.           22 tabel tetap menjadi  
                           perubahan isi.                         baseline aktif dan      
                                                                  tidak berubah.          

  D-007       SUPERSEDED   Implementasi prototype Keputusan       Tidak lagi berarti      README, reconciliation, tasks
                           diblokir sampai        historis;       setiap dokumen atau     
                           seluruh dependensi     digantikan oleh validasi eksternal yang 
                           `IMP-001` dalam        D-012 untuk     tertunda otomatis       
                           `tasks.md` selesai,    membedakan      memblokir implementasi. 
                           didukung evidence, dan blocker kritis  Gate tetap memerlukan   
                           gate dibuka melalui    dari validasi   pemeriksaan kesiapan    
                           persetujuan eksplisit. yang memang     dan persetujuan         
                                                  harus menunggu  eksplisit pemilik       
                                                  prototype atau  proyek.                 
                                                  mitra.                                  

  D-008       SUPERSEDED   Mekanisme persistence  Digantikan oleh Dipertahankan sebagai   prototype, tasks, testing, reconciliation
                           prototype belum        D-010 setelah   riwayat; tidak lagi     
                           diputuskan.            keputusan       menjadi blocker         
                                                  eksplisit       keputusan persistence.  
                                                  pemilik proyek.                         

  D-009       LOCKED       Checkpoint v1.6 adalah Arahan pemilik  Dokumentasi kanonis     README, AGENTS, index, reconciliation,
                           snapshot perencanaan   proyek;         direkonsiliasi terhadap tasks
                           terbaru untuk          checkpoint      snapshot v1.6. Riwayat  
                           `project1.1`. Nama     v1.6.           v1.5 tetap tersedia dan 
                           checkpoint tetap v1.6                  tidak dihapus.          
                           untuk revisi                                                   
                           dokumentasi biasa;                                             
                           checkpoint bernomor                                            
                           baru memerlukan                                                
                           keputusan eksplisit.                                           

  D-010       LOCKED       Prototype menggunakan  Keputusan       Data demo diharapkan    `docs/prototype/prototype.md`,
                           database lokal         eksplisit       bertahan setelah        `docs/engineering/testing.md`, `tasks.md`,
                           non-production dengan  pemilik proyek  aplikasi dimulai ulang. `reconciliation.md`, README dan dokumen
                           data dummy dan         dalam           Keputusan ini bukan     terkait
                           dijalankan pada satu   pembahasan      persetujuan penggunaan  
                           komputer untuk         persistence     data warga nyata, bukan 
                           demonstrasi.           prototype.      schema production, dan  
                           Hosting/domain tidak                   tidak mengotorisasi     
                           diperlukan untuk demo                  migration/schema baru.  
                           lokal.                                 Akses LAN untuk 1--5    
                                                                  Petugas dapat           
                                                                  dievaluasi sebagai      
                                                                  tahap terpisah jika     
                                                                  dibutuhkan.             

  D-011       LOCKED       Workflow v2.1 menjadi  Persetujuan     Workflow v2.0           `docs/architecture/workflow.md`,
                           baseline workflow      eksplisit       dipertahankan sebagai   `docs/architecture/database.md`,
                           aktif setelah pemilik  pemilik proyek  riwayat dan tidak lagi  `docs/architecture/roles-permissions.md`,
                           proyek menyetujui      dalam           menjadi baseline aktif. `docs/architecture/security.md`,
                           promosi hasil          percakapan;     Tidak ada perubahan     `docs/architecture/architecture.md`
                           rekonsiliasi. Database revisi workflow skema/migrasi database  
                           v1.1 / 22 tabel tetap  yang            yang diotorisasi oleh   
                           baseline database      menyelaraskan   keputusan ini.          
                           aktif.                 struktur PDF,                           
                                                  lampiran,                               
                                                  approval gate,                          
                                                  dan aturan                              
                                                  Kifayah.                                

  D-012       LOCKED       Sebelum gate           Arahan pemilik  Setelah dokumen kritis  README, reconciliation, tasks, testing,
                           implementasi dibuka,   proyek dalam    disinkronkan, siapkan   prototype, dokumen canonical terkait
                           lakukan pemeriksaan    rekonsiliasi    ringkasan kesiapan,     
                           kesiapan yang berfokus dokumentasi.    blocker yang tersisa,   
                           pada konflik kritis                    dan validasi tertunda.  
                           yang memengaruhi                       Jangan mulai bootstrap  
                           perilaku bisnis,                       aplikasi, source code,  
                           otorisasi/keamanan,                    migration, schema, atau 
                           workflow, atau                         seed sebelum pemilik    
                           baseline terkunci.                     proyek memberikan       
                           Dokumen atau validasi                  persetujuan eksplisit   
                           yang memang memerlukan                 untuk membuka gate      
                           pengujian prototype                    implementasi.           
                           atau konfirmasi                                                
                           Kelurahan dapat tetap                                          
                           `PENDING VALIDATION`                                           
                           dan bukan otomatis                                             
                           menjadi blocker                                                
                           menyeluruh.                                                    

  D-013       LOCKED       Stack prototype lokal: Persetujuan     Menetapkan stack untuk  `docs/architecture/architecture.md`,
                           Laravel 13, Vue 3 +    eksplisit       prototype lokal         `docs/architecture/database.md`,
                           TypeScript, Inertia,   pemilik proyek, non-production. Tidak   `docs/prototype/prototype.md`
                           Vite, MySQL, Dompdf,   23 Oktober      mengubah Database v1.1
                           dan Laravel            2026. Sesuai    / 22 tabel dan tidak
                           Filesystem. Tidak ada  architecture.md mengotorisasi
                           framework frontend     dan             bootstrap, migration,
                           tambahan atau          database.md.    schema, atau seed.
                           pengganti.                             Versi dependensi
                                                                  diverifikasi saat
                                                                  bootstrap setelah gate
                                                                  dibuka.

  D-014       LOCKED       Autentikasi prototype  Persetujuan     Tidak ada kebijakan     `docs/architecture/security.md`,
                           memakai mekanisme      eksplisit       password atau sesi      `docs/architecture/roles-permissions.md`,
                           bawaan Laravel dengan  pemilik proyek, khusus di luar bawaan   `docs/product/scope.md`
                           akun dummy. Akun       23 Oktober      Laravel pada prototype.
                           dibuat dan dikelola    2026.           Otorisasi tetap
                           ADMIN; registrasi                      server-side sesuai
                           publik dinonaktifkan.                  roles-permissions.md
                                                                  dan security.md.
                                                                  Kebijakan production
                                                                  tetap PENDING
                                                                  VALIDATION.

  D-015       LOCKED       Label lampiran dasar   Persetujuan     Tidak ada perubahan     `PRD.md`, `scope.md`, `tasks.md`,
                           adalah "Foto Klien"    eksplisit       database (nilai         `reconciliation.md`, `prototype.md`,
                           (bukan "foto           pemilik proyek, FOTO_KLIEN tetap).      `testing.md`, `dummy-data.md`,
                           penerima"). Empat      23 Oktober      Istilah "foto penerima" `demo-checklist.md`
                           lampiran dasar: KTP,   2026. Sesuai    pada dokumen pendukung
                           KK, Foto Klien, Foto   formulir PDF,   diselaraskan. Frasa
                           Rumah.                 outreach.md,    seperti "foto penerima
                                                  dan enum        beasiswa" pada lampiran
                                                  FOTO_KLIEN.     tambahan per Type tidak
                                                                  terdampak.

  D-016       LOCKED       Batas peran ADMIN:     Persetujuan     Klarifikasi matriks     `docs/architecture/roles-permissions.md`,
                           tidak dapat membuat    eksplisit       peran, bukan perubahan  `docs/architecture/workflow.md` (hanya
                           Outreach dari nol dan  pemilik proyek, baseline. Workflow v2.1 dibaca, tidak diubah), `docs/ux/design.md`
                           tidak memiliki Draft.  23 Oktober      dan Database v1.1 tidak
                           Edit data hanya selama 2026. Sesuai    berubah. Baris ADMIN
                           Special Unlock dengan  Workflow v2.1   pada matriks (Draft,
                           alasan wajib dicatat.  (Lock & Ajukan  Finalisasi, scope
                           Lock & Ajukan adalah   sebagai         administratif) perlu
                           submission action      submission      disesuaikan.
                           setara Finalisasi:     action) dan
                           validasi penuh,        checkpoint
                           historical version     v1.6.
                           baru, status
                           MENUNGGU_PENGESAHAN.
                           Lock mengembalikan ke
                           PERLU_PERBAIKAN. Hanya
                           Outreach yang telah
                           difinalisasi muncul di
                           antrean Kepala. ADMIN
                           tidak melakukan
                           Approve atau Return.
                           Scope administratif:
                           melihat seluruh
                           Outreach dalam
                           Kelurahan tanpa batas
                           kepemilikan Petugas.

  D-017       LOCKED       Alur kerja Claude      Persetujuan     Menggantikan aturan     `AGENTS.md`, `CLAUDE.md`, `docs/index.md`
                           Code: Claude Code      eksplisit       lama "berikan file      (perlu disesuaikan, satu file per giliran)
                           bekerja langsung pada  pemilik proyek, revisi yang dapat
                           repo Git lokal di      23 Oktober      diunduh" untuk fase
                           folder Project, dengan 2026.           Claude Code. Tidak
                           commit per task dan                    membuka gate
                           repo GitHub private.                   implementasi. Aturan
                           Checkpoint tetap                       satu file Markdown per
                           dikelola di luar repo                  giliran selama
                           dan dirujuk lewat                      rekonsiliasi dan
                           path.                                  persetujuan perubahan
                                                                  substantif tetap
                                                                  berlaku.

  D-018       LOCKED       Pustaka UI prototype:  Persetujuan     Hanya lapisan tampilan; `docs/architecture/architecture.md`,
                           Tailwind CSS +         eksplisit       tidak mengubah          `docs/ux/design.md`
                           shadcn-vue, melalui    pemilik proyek, arsitektur. Verifikasi
                           starter kit resmi      23 Oktober      kit untuk Laravel 13
                           Laravel untuk Vue.     2026.           dan penonaktifan
                                                                  registrasi publik
                                                                  dilakukan saat
                                                                  bootstrap setelah gate
                                                                  dibuka. Dependensi
                                                                  tambahan (mis. tanda
                                                                  tangan) tetap butuh
                                                                  alasan jelas.

  D-019       LOCKED       Status Kesejahteraan   Persetujuan     Menutup OUT-VAL-01:     `docs/domain/forms/outreach.md`,
                           memakai desil 1-10: 1  eksplisit       label "Sejahtera" pada  `docs/architecture/database.md` (catatan
                           Sangat Miskin; 2       pemilik proyek, contoh lama adalah      PENDING VALIDASI, tanpa perubahan schema),
                           Miskin; 3 Hampir       23 Oktober      label Desil 9. Tidak    `docs/ux/design.md`
                           Miskin; 4 Rentan       2026. Daftar    ada perubahan schema.
                           Miskin; 5 Transisi     label sesuai    Tautkan evidence sumber
                           Menengah; 6 Menengah;  sumber menurut  resmi bila tersedia.
                           7 Menengah; 8 Menengah pemilik proyek
                           Atas; 9 Sejahtera; 10  (Desil 6 dan 7
                           Sangat Sejahtera.      memang
                           Petugas memilih desil; sama-sama
                           angka disimpan di      "Menengah").
                           desil_1_10 dan label   Dokumen sumber
                           diisi otomatis ke      resmi belum
                           status_kesejahteraan.  dilampirkan
                                                  sebagai
                                                  evidence.

  D-020       LOCKED       Kendaraan pada         Persetujuan     Menutup OUT-VAL-06      `docs/domain/forms/outreach.md`,
                           Outreach diinput       eksplisit       tanpa perubahan schema. `docs/ux/design.md`,
                           sebagai angka per      pemilik proyek, Draft boleh kosong;     `docs/prototype/prototype.md`
                           jenis (kolom jumlah_*  23 Oktober      Finalisasi menuntut
                           terpisah di Database   2026.           semua angka terisi.
                           v1.1) beserta nama                     Berbeda dari pertanyaan
                           kendaraan lainnya.                     Kepemilikan Kendaraan
                           Petugas mengisi angka                  pada form Mustahik.
                           secara eksplisit (0
                           bila tidak ada), tanpa
                           auto-fill. Nama wajib
                           bila jumlah lainnya
                           lebih dari 0. PDF
                           merangkai satu baris
                           dari jenis bernilai
                           lebih dari 0, atau
                           "Tidak ada" bila
                           semuanya 0.

  D-021       LOCKED       Fasilitas MCK pada     Persetujuan     Menutup OUT-VAL-07      `docs/domain/forms/outreach.md`,
                           Outreach memakai empat eksplisit       tanpa mengganti nama    `docs/architecture/database.md` (hanya
                           nilai: Layak, Kurang   pemilik proyek, atau relasi kolom. Tipe dibaca)
                           layak, Tidak layak,    23 Oktober      dan kode nilai internal
                           Tidak ada. Nama kolom  2026.           ditetapkan saat desain
                           mck_layak tetap (opsi                  migration setelah gate
                           A); makna empat                        dibuka. Berbeda dari
                           nilainya                               pertanyaan MCK pada
                           didokumentasikan.                      Mustahik; jawaban
                                                                  Mustahik tidak diisi
                                                                  otomatis.

  D-022       LOCKED       Register Mustahik      Persetujuan     Opsi tidak              `docs/domain/forms/mustahik.md` (termasuk
                           mengikuti PDF sumber   eksplisit       dinormalisasi dan tidak ECONOMIC_OTHER_SUBSIDY),
                           secara literal.        pemilik proyek, ada scoring otomatis.   `docs/reference/mustahik.pdf` (sumber)
                           Delapan pertanyaan     23 Oktober      Petunjuk "paling
                           yang belum             2026. Sumber:   mendekati" adalah
                           ditranskripsikan       PDF Mustahik    panduan pengisian,
                           dimasukkan ke          (teks dan       bukan perubahan opsi.
                           register, dan opsi     tampilan visual Register di mustahik.md
                           keempat "Anak"         diperiksa).     perlu diperbarui.
                           ditambahkan pada
                           pertanyaan Asumsi
                           Subsidi Lain. Ejaan
                           sumber dan opsi yang
                           beririsan
                           dipertahankan.
                           Petunjuk pengisian:
                           petugas memilih opsi
                           yang paling mendekati.

  D-023       PENDING      Klarifikasi ke pemilik Belum ada       Prototype tetap memakai `docs/domain/forms/mustahik.md`,
              VALIDATION   form (di PDF tertera   jawaban pemilik opsi PDF apa adanya     `docs/project-management/reconciliation.md`
                           BAZNAS Kota Surabaya): form; dicatat   sampai ada klarifikasi
                           opsi Mustahik yang     sebagai bahan   resmi. Tidak ada
                           beririsan atau         klarifikasi.    keputusan perilaku atau
                           memiliki celah, yaitu                  scoring yang diambil.
                           pendapatan Rp1,5-3,5
                           juta tanpa opsi, dan
                           nilai tepat di batas
                           (mis. emas 5 gram).

  D-024       LOCKED       Lingkungan lokal       Persetujuan     Tidak mengubah          `docs/engineering/setup.md` (dokumen baru,
                           Windows memakai        eksplisit       baseline. Pemilik       draft dibuat), `docs/index.md`,
                           Laragon (bukan Herd).  pemilik proyek, proyek telah memeriksa  `docs/project-management/tasks.md`
                           Terverifikasi: PHP     23 Oktober      EULA Laragon dan
                           8.5.12 (cadangan       2026. Evidence: memutuskan tetap
                           8.3.36) dengan         keluaran        memakainya. Langkah
                           ekstensi yang          perintah dari   rinci ditulis di
                           dibutuhkan aktif       komputer        setup.md.
                           termasuk zip, Composer pemilik proyek.
                           2.10.3, Node v24.22.1,
                           npm 11.19.1, MySQL
                           8.4.3 berjalan. Web
                           server bawaan Laragon;
                           Apache atau Nginx
                           tidak memengaruhi
                           aplikasi.

  D-025       LOCKED       Kebijakan opsi         Persetujuan     Agama, Status Hubungan  `docs/domain/forms/outreach.md`,
                           formulir Outreach:     eksplisit       dalam Keluarga, Status  `docs/ux/design.md`
                           dropdown hanya bila    pemilik proyek, tempat tinggal, Status
                           opsinya tertulis pada  24 Oktober      Perkawinan, dan Jaminan
                           PDF atau ketentuan     2026. Menutup   Kesehatan memakai teks
                           yang disetujui; selain OUT-VAL-02      bebas sampai daftar
                           itu teks bebas pada    sebagai         opsinya disetujui, lalu
                           prototype. Pekerjaan   kebijakan.      dapat menjadi dropdown
                           memakai teks bebas.                    tanpa mengubah makna
                           Opsi yang sudah                        jawaban.
                           tertulis pada dokumen
                           yang disetujui (mis.
                           Status Kependudukan
                           WNI atau WNA pada
                           Database v1.1;
                           Ya/Tidak untuk merokok
                           dan hutang) boleh
                           berupa dropdown.

  D-026       LOCKED       Peran file aturan      Persetujuan     Menggantikan duplikasi  `AGENTS.md`, `CLAUDE.md`, `docs/index.md`
                           agent: AGENTS.md       eksplisit       aturan pada dua file.
                           menjadi satu-satunya   pemilik proyek, Aturan tetap dapat
                           sumber aturan kerja    24 Oktober      dipakai alat lain bila
                           (bahasa Indonesia,     2026.           Claude tidak dipakai.
                           target gabungan                        Aturan LOCKED dan gate
                           sekitar 200 baris).                    tidak diperlemah.
                           CLAUDE.md hanya
                           mengimpor AGENTS.md
                           dan memuat catatan
                           khusus Claude Code.

  D-027       LOCKED       Bantuan yang pernah    Persetujuan     Menutup OUT-VAL-03 dan  `docs/domain/forms/outreach.md` (kolom
                           diterima (Outreach)    eksplisit       menyelesaikan DEC-001   `bantuan_pernah_diterima` dan
                           mengisi dua kolom      pemilik proyek, tanpa perubahan schema. `bantuan_detail`), `docs/ux/design.md`
                           Database v1.1 dari     25 Oktober      Tipe kolom ditetapkan
                           satu isian formulir:   2026.           saat desain migration;
                           pilihan Ya/Tidak, dan                  bila kolom Ya/Tidak
                           uraian teks bebas yang                 ternyata tidak cocok,
                           wajib bila Ya serta                    keputusan ditinjau
                           tidak berlaku bila                     ulang.
                           Tidak. PDF menampilkan
                           uraian, atau "Tidak
                           ada" bila Tidak.

  D-028       LOCKED       Nama workspace         Persetujuan     Tidak mengubah          `README.md`, `docs/index.md`
                           `project1.1` diganti   eksplisit       baseline. D-001 dan
                           menjadi `project`;     pemilik proyek, D-009 tetap sebagai
                           keduanya merujuk       25 Oktober      riwayat dan tidak
                           folder yang sama.      2026.           ditulis ulang. Nama
                           Rujukan `project1.1`                   lama dicatat pada
                           pada dokumen historis                  README.md agar jejaknya
                           dibaca sebagai                         tidak hilang.
                           `project`.

  D-029       LOCKED       Checkpoint berfungsi   Persetujuan     Merevisi bagian D-017   `AGENTS.md`, `CLAUDE.md`, `docs/index.md`,
                           sebagai memori         eksplisit       tentang checkpoint yang `docs/engineering/setup.md`,
                           perencanaan untuk AI   pemilik proyek, dirujuk lewat path.     `docs/project-management/tasks.md`
                           percakapan yang        25 Oktober      Tidak mengubah
                           membantu membahas dan  2026. Sesuai    baseline. Sumber
                           menyusun dokumen.      Workflow v2.1   kebenaran tetap docs/
                           Checkpoint berada di   (checkpoint di  dan decisions.md.
                           luar repo, bukan       luar repository Checkpoint tetap
                           sumber implementasi,   dan bukan       bernama v1.6 dan
                           dan tidak dibaca agent sumber          dikelola pengguna.
                           implementasi kecuali   implementasi).
                           pengguna secara
                           eksplisit memintanya.
  -----------------------------------------------------------------------------------------------------------------------------------

## Template Entri Baru

  ------------------------------------------------------------------------------------
  ID          Status                 Keputusan / Sumber /      Dampak      Dokumen
                                     Isu         Evidence                  terdampak
  ----------- ---------------------- ----------- ------------- ----------- -----------
  D-XXX       `LOCKED` /             Ringkasan   Persetujuan   Dampak pada File
              `PENDING VALIDATION` / singkat     atau dokumen  perilaku    kanonis
              `SUPERSEDED` /                     sumber yang   atau scope  yang
              `BLOCKED`                          dapat                     terdampak
                                                 ditelusuri                

  ------------------------------------------------------------------------------------

## Rekonsiliasi yang Diperlukan

Sebelum gate implementasi dibuka, lakukan pemeriksaan kesiapan terhadap
konflik kritis yang memengaruhi perilaku bisnis, otorisasi/keamanan,
workflow, dan baseline terkunci. Catat blocker beserta evidence dan
dampaknya. Validasi yang memerlukan prototype atau konfirmasi Kelurahan
dapat tetap `PENDING VALIDATION` apabila tidak menimbulkan konflik
kritis yang harus diselesaikan sebelumnya.

Setelah dokumen kritis disinkronkan, susun ringkasan kesiapan yang
menyatakan: hal yang sudah konsisten, blocker yang masih ada, validasi
yang tertunda, serta alasan mengapa tiap validasi tertunda tidak
otomatis menghalangi implementasi atau justru masih menjadi blocker.
Gate implementasi tetap tertutup sampai pemilik proyek memberikan
persetujuan eksplisit.

Keputusan persistence tercatat pada D-010 dan D-008 berstatus
`SUPERSEDED`. Workflow v2.1 tercatat sebagai baseline aktif pada D-011;
Database v1.1 / 22 tabel tetap tidak berubah. Pencatatan keputusan ini
tidak dengan sendirinya menyelesaikan semua pekerjaan sinkronisasi atau
membuka gate implementasi.

Keputusan baru harus merujuk evidence atau persetujuan yang dapat
ditelusuri. Perubahan dokumentasi biasa tidak otomatis mengubah baseline
yang terkunci. Jangan mengotorisasi perubahan skema atau migrasi melalui
implikasi dokumentasi.

## Catatan Keputusan — 23 sampai 25 Oktober 2026

D-013 sampai D-022 dan D-024 sampai D-029 dicatat berdasarkan persetujuan
eksplisit pemilik proyek dalam percakapan pada 23 sampai 25 Oktober 2026.
D-023 masih `PENDING VALIDATION`. Entri ini tidak mengubah Workflow v2.1 atau
Database v1.1 / 22 tabel, dan tidak membuka gate implementasi. Dokumen
yang terdampak disesuaikan satu file per giliran dengan persetujuan
pemilik proyek.
