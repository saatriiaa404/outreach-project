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

  D-030       LOCKED       Baseline "22 tabel"    Persetujuan     Menafsirkan baseline 22 `docs/architecture/database.md`,
                           Database v1.1 adalah   eksplisit       tabel tanpa mengubah    `docs/architecture/architecture.md`,
                           tabel domain. Tabel    pemilik proyek, tabel domain. Detail    `docs/architecture/security.md`,
                           infrastruktur Laravel  27 Oktober 2026 teknis Laravel          `docs/engineering/setup.md`, `tasks.md`
                           berada di luar         (jawaban audit  diverifikasi saat
                           hitungan dan dibatasi  kesiapan Tahap  implementasi
                           seminimal mungkin:     1, F-01).       (BOOT-001). Tidak
                           session dan cache                      membuka gate dan tidak
                           memakai driver file,                   mengotorisasi bootstrap
                           queue sinkron (sync),                  atau migration.
                           tanpa reset password
                           dan tanpa registrasi
                           publik, sehingga
                           praktis hanya tabel
                           migrations. Kolom
                           users tidak ditambah
                           untuk fitur bawaan
                           (remember_token,
                           email_verified_at, dan
                           2FA tidak dipakai);
                           kolom password_hash
                           dipertahankan dengan
                           model User yang
                           disesuaikan.

  D-031       LOCKED       Pengecualian invarian  Persetujuan     Hanya teks invarian dan `docs/architecture/database.md` (bagian
                           status: setelah Lock   eksplisit       daftar nilai            11.1, 13.2, 28),
                           pasca Special Unlock   pemilik proyek, event_type; tidak ada   `docs/architecture/workflow.md` (bagian
                           tanpa versi baru,      27 Oktober 2026 perubahan kolom, tabel, 28.2), `docs/engineering/testing.md`
                           Outreach berstatus     (jawaban audit  atau relasi.
                           PERLU_PERBAIKAN boleh  kesiapan Tahap  Menyelaraskan Database
                           memiliki versi terbaru 1, F-02 dan     v1.1 dengan transisi
                           APPROVED atau          pilihan         Lock pada Workflow
                           PENDING_APPROVAL. Lock pencatatan      v2.1.
                           dan Lock & Ajukan      event).
                           dicatat pada
                           history_events dengan
                           nilai event_type baru
                           SPECIAL_UNLOCK_LOCKED
                           dan
                           SPECIAL_UNLOCK_SUBMITTED.

  D-032       LOCKED       outreach.md (Draft     Persetujuan     Menutup ketidakpastian  `docs/domain/forms/outreach.md`,
                           Revisi v1.6) menjadi   eksplisit       status dokumen          `docs/index.md` (bila status perlu),
                           spesifikasi Form       pemilik proyek, Outreach. Tanpa         `tasks.md`
                           Outreach aktif untuk   27 Oktober 2026 perubahan schema.
                           prototype dan tidak    (jawaban audit
                           lagi bergantung pada   kesiapan Tahap
                           rujukan baseline v1.5  1, F-03).
                           yang tidak tersedia di
                           repository. Field 14
                           (Penghasilan klien
                           sebulan) dan 17
                           (Pendidikan terakhir)
                           opsional: boleh
                           kosong, tidak
                           memblokir Finalisasi,
                           dan tetap divalidasi
                           bila diisi.

  D-033       LOCKED       Lampiran tambahan per  Persetujuan     Selaras dengan Workflow `docs/domain/forms/outreach.md`,
                           Type (outreach.md      eksplisit       v2.1 bagian 18.1:       `docs/architecture/workflow.md` (bagian
                           bagian 7.2) tidak      pemilik proyek, lampiran tambahan baru  15.2, 41.1), `PRD.md`,
                           memblokir Finalisasi   27 Oktober 2026 menjadi syarat setelah  `docs/ux/design.md`, `testing.md`
                           pada prototype. Empat  (jawaban audit  validasi resmi
                           lampiran dasar tetap   kesiapan Tahap  Kelurahan, yang tetap
                           wajib.                 1, F-04).       PENDING VALIDATION.
                                                                  Tanpa perubahan schema.

  D-034       LOCKED       Hanya lampiran berupa  Persetujuan     Menghindari kegagalan   `docs/domain/forms/outreach.md`,
                           gambar yang dicetak    eksplisit       PDF gate akibat         `docs/architecture/workflow.md` (bagian
                           pada halaman lampiran  pemilik proyek, keterbatasan Dompdf.    41.2), `docs/architecture/security.md`,
                           PDF Outreach. Lampiran 27 Oktober 2026 Daftar tipe gambar dan  `docs/ux/design.md`, `prototype.md`,
                           berformat PDF tetap    (jawaban audit  batas ukuran tetap      `testing.md`
                           disimpan sebagai       kesiapan Tahap  ditetapkan melalui
                           attachment dan         1, F-05).       pengujian prototype.
                           ditampilkan pada PDF                   Tanpa perubahan schema.
                           Outreach sebagai
                           halaman rujukan berisi
                           nama berkas. Tidak
                           menambah pustaka
                           penggabung PDF. Empat
                           lampiran wajib (KTP,
                           KK, Foto Klien, Foto
                           Rumah) harus berupa
                           gambar.

  D-035       LOCKED       Pertanyaan Mustahik    Persetujuan     Menyelaraskan           `docs/architecture/workflow.md`,
                           Tanggungan             eksplisit       workflow.md dengan      `docs/domain/forms/mustahik.md`
                           (PHYSICAL_DEPENDENTS)  pemilik proyek, mustahik.md dan
                           dan Hutang             27 Oktober 2026 database.md yang sudah
                           (PHYSICAL_DEBT)        (jawaban audit  menetapkan
                           bersifat               kesiapan Tahap  single-choice untuk
                           single-choice.         1, F-06).       seluruh 25 pertanyaan.
                           Pengecualian                           Tanpa perubahan schema.
                           multiple-choice pada
                           Workflow v2.1 bagian
                           13.3 dan 41.8 dicabut
                           untuk prototype.

  D-036       LOCKED       Identitas pada Form    Persetujuan     Tanpa perubahan schema. `docs/domain/forms/kifayah.md`,
                           Had Kifayah            eksplisit       Label PDF sumber tidak  `docs/domain/forms/mustahik.md`,
                           ditampilkan read-only  pemilik proyek, diubah (D-022).         `docs/architecture/database.md` (hanya
                           dari data Outreach,    27 Oktober 2026 Konstanta wilayah       dibaca), `testing.md`
                           tidak diketik ulang:   (jawaban audit  adalah konfigurasi
                           Nama, NIK, dan No. HP  kesiapan Tahap  aplikasi, bukan data
                           dari Outreach; Alamat  1, F-07 dan     per Outreach.
                           Tempat Tinggal dari    F-08 opsi a).
                           Alamat Domisili.
                           Header Form Mustahik:
                           Nama Petugas dari
                           petugas_id, Tanggal
                           dari survey_date,
                           identitas Kepala
                           Keluarga (Nama, NIK/No
                           KK, alamat, RT, RW)
                           dari kolom
                           kepala_keluarga_*, dan
                           No. Telepon HP diisi
                           No. telp Outreach
                           (field 15) sebagai
                           kontak Outreach, bukan
                           data Kepala Keluarga
                           yang terverifikasi.
                           Kelurahan dan
                           Kecamatan memakai
                           konstanta konfigurasi:
                           Kelurahan Manukan
                           Kulon, Kecamatan
                           Tandes.

  D-037       LOCKED       Opsi tujuh pertanyaan  Persetujuan     Opsi tidak              `docs/domain/forms/kifayah.md`,
                           Kifayah ditranskripsi  eksplisit       dinormalisasi dan tidak `tasks.md`
                           secara literal dari    pemilik proyek, ada scoring. Tanpa
                           kifayah.pdf dengan     27 Oktober 2026 perubahan schema atau
                           pendekatan D-022, dan  (jawaban audit  question_code.
                           seluruhnya             kesiapan Tahap
                           single-choice.         1, F-09).
                           Transkripsi dan
                           pencocokan visual
                           dikerjakan pemilik
                           proyek; sampai
                           hasilnya tercatat,
                           register opsi pada
                           kifayah.md tetap
                           PENDING VALIDATION.

  D-038       LOCKED       Arsip dilakukan manual Persetujuan     Mengukuhkan keputusan   `docs/architecture/workflow.md` (bagian
                           oleh ADMIN paling      eksplisit       arsip dan backup        27, 39, 41), `scope.md`,
                           lambat satu bulan      pemilik proyek, sebagai sumber kanonis. `roles-permissions.md`, `testing.md`,
                           sejak persetujuan      27 Oktober 2026 Prosedur rinci backup   `reconciliation.md`
                           Kepala; data dan       (jawaban audit  dan restore ditulis
                           lampiran tetap berada  kesiapan Tahap  pada dokumen
                           di aplikasi; tidak ada 1, F-10;        engineering. Tanpa
                           integrasi              sebelumnya      perubahan schema
                           E-Surat/E-Arsip (PDF   hanya tercatat  (archive_records
                           diunduh dan diunggah   pada            tetap).
                           manual); ID internal   reconciliation.md).
                           aplikasi bukan nomor
                           arsip resmi. Backup
                           dilakukan manual oleh
                           ADMIN setiap minggu,
                           mencakup database dan
                           seluruh lampiran, dan
                           disimpan pada media
                           terpisah.

  D-039       LOCKED       KEPALA tidak melihat   Persetujuan     Otorisasi server-side   `docs/architecture/roles-permissions.md`,
                           Outreach berstatus     eksplisit       menerapkan batas yang   `docs/ux/design.md`, `scope.md`,
                           DRAFT. Untuk           pemilik proyek, sama pada dashboard,    `testing.md`
                           PERLU_PERBAIKAN,       27 Oktober 2026 daftar, pencarian, dan
                           KEPALA hanya melihat   (jawaban audit  URL langsung. Tanpa
                           status dan catatan     kesiapan Tahap  perubahan schema.
                           Return pada riwayat,   1, F-11).
                           tanpa isi yang sedang
                           diedit. Dashboard
                           minimum: PETUGAS
                           melihat Outreach
                           miliknya per status;
                           KEPALA melihat antrean
                           MENUNGGU_PENGESAHAN
                           dan SELESAI; ADMIN
                           melihat semua Outreach
                           per status, permintaan
                           Special Unlock, dan
                           status arsip.
                           Pencarian berdasarkan
                           nama, NIK, ID
                           internal, Source,
                           Type, status, dan
                           rentang tanggal,
                           selalu dibatasi scope
                           role.

  D-040       LOCKED       Hanya ADMIN yang       Persetujuan     Menambah baris matriks  `docs/architecture/roles-permissions.md`,
                           mengelola master       eksplisit       peran. Tanpa perubahan  `docs/ux/design.md`, `testing.md`
                           Source dan Type. Kode  pemilik proyek, schema.
                           Source dan Type tidak  27 Oktober 2026
                           dapat diubah karena    (jawaban audit
                           menentukan komposisi   kesiapan Tahap
                           PDF dan workflow;      1, F-12).
                           penonaktifan memakai
                           kolom active. PETUGAS
                           tidak mengunduh PDF
                           final, sesuai matriks
                           peran (Print PDF:
                           PETUGAS Tidak).

  D-041       LOCKED       Amandemen Database     Persetujuan     Perubahan schema pada   `docs/architecture/database.md`,
                           v1.1: tabel users      eksplisit       baseline terkunci;      `docs/domain/forms/outreach.md` (bagian
                           mendapat kolom nip     pemilik proyek, jumlah tabel tetap 22   8.4), `roles-permissions.md`,
                           (nullable), diisi      27 Oktober 2026 dan label versi tetap   `testing.md`, `tasks.md`
                           untuk user KEPALA dan  (pilihan kolom  Database v1.1 dengan
                           dicetak pada blok      users.nip dan   amandemen ini. PDF
                           pengesahan PDF. Format persetujuan     historis membaca NIP
                           dan posisi cetak NIP   rencana         saat render, sehingga
                           PENDING VALIDATION     sinkronisasi).  perubahan NIP dicatat
                           sampai tata letak blok                 pada audit_logs dan
                           pengesahan                             tidak dilakukan setelah
                           dikonfirmasi pemilik                   NIP dipakai pada
                           proyek.                                approval kecuali
                                                                  melalui koreksi
                                                                  administratif. Tidak
                                                                  mengotorisasi migration
                                                                  sebelum gate dibuka.

  D-042       LOCKED       Kode aplikasi Laravel  Persetujuan     Mencegah tabrakan       `docs/architecture/architecture.md`,
                           ditempatkan di         eksplisit       dengan README.md,       `docs/engineering/setup.md`, `tasks.md`
                           subfolder web/ di      pemilik proyek, .gitignore, dan docs/.  (BOOT-001)
                           dalam project/, bukan  27 Oktober 2026 Tidak membuka gate dan
                           di akar project/.      (jawaban audit  tidak mengotorisasi
                                                  kesiapan Tahap  bootstrap.
                                                  1, F-14).

  D-043       LOCKED       Desain migration R1.1  Persetujuan     Dua constraint UNIQUE   `docs/engineering/migration-design.md`,
                           (docs/engineering/migration-design.md)eksplisitmenambah daftar `docs/architecture/database.md` (bagian
                           disetujui beserta      pemilik proyek, uniqueness Database     21.1), `roadmap.md`
                           usulan Q-01 sampai     27 Oktober 2026 v1.1 bagian 21.1; tidak
                           Q-11: enum sebagai     (roadmap R1.1). ada tabel atau kolom
                           VARCHAR dengan PHP                     baru. Tidak
                           backed enum, nominal                   mengotorisasi pembuatan
                           rupiah BIGINT                          atau menjalankan
                           UNSIGNED, NIP 18                       migration; R1.2
                           digit, format                          memerlukan persetujuan
                           draft_code dan                         terpisah.
                           outreach_number, NIK
                           tanpa paksaan 16 digit
                           pada prototype,
                           tahun_warga_surabaya
                           VARCHAR(50), JSON
                           answer {"option":
                           kode}, satu baris
                           document_template_versions
                           sebagai paket template
                           per Source, serta dua
                           constraint tambahan:
                           UNIQUE
                           files.storage_key dan
                           UNIQUE (template_key,
                           version_number) pada
                           document_template_versions.

  D-044       LOCKED       Semua role boleh       Persetujuan     Menegakkan D-014 dan    `roles-permissions.md`, `roadmap.md`,
                           mengganti password     eksplisit       aturan user tidak       kode `web/` (route dan halaman
                           sendiri. Halaman ubah  pemilik proyek, dihapus (Database v1.1  pengaturan)
                           profil sendiri (nama   27 Oktober 2026 bagian 20). Tanpa
                           dan email) serta hapus (roadmap R9.1). perubahan schema.
                           akun sendiri bawaan                    Pengelolaan akun oleh
                           starter kit dihapus;                   ADMIN tetap dikerjakan
                           data akun dan                          pada R9.1.
                           penonaktifan dikelola
                           ADMIN.

  D-045       LOCKED       Amandemen Database     Persetujuan     Perubahan schema pada   `database.md`, `migration-design.md`,
                           v1.1: field Outreach   eksplisit       baseline terkunci;      `outreach.md`, `design.md`, kode `web/`
                           "Tempat dan Tanggal    pemilik proyek, jumlah tabel tetap 22.
                           Lahir / Umur" diisi    27 Oktober 2026 Kolom lama
                           terpisah pada form     (pilihan kolom  tempat_tgl_lahir_umur
                           melalui kolom baru     baru dan acuan  tidak lagi diisi dan
                           outreaches.tempat_lahirumur Tanggal    dipertahankan sampai
                           (VARCHAR(100),         Outreach).      ada keputusan
                           nullable) dan                          penghapusan terpisah.
                           outreaches.tanggal_lahir               Format rangkaian baris
                           (DATE, nullable). Umur                 PDF ditetapkan saat
                           tidak disimpan;                        template PDF dibuat
                           dihitung otomatis                      (R7.2).
                           dalam tahun penuh
                           terhadap Tanggal
                           Outreach. Template PDF
                           tidak berubah: tetap
                           satu baris yang
                           dirangkai dari
                           ketiganya. Tanggal
                           lahir memakai isian
                           tanggal seperti Google
                           Form (ketik dd/mm/yyyy
                           atau pilih dari
                           kalender) dan tidak
                           boleh setelah Tanggal
                           Outreach.

  D-046       LOCKED       Amandemen Database     Persetujuan     Perubahan schema pada   `database.md`, `migration-design.md`,
                           v1.1: kolom            eksplisit       baseline terkunci;      kode `web/`
                           outreaches.tempat_tgl_lahir_umurpemilik proyek,jumlah tabel tetap 22.
                           dihapus karena         27 Oktober      Isian yang tidak dapat
                           digantikan             2026.           dibaca dibiarkan
                           tempat_lahir dan                       kosong, bukan ditebak.
                           tanggal_lahir (D-045).                 Rollback menambah
                           Isian lama dipindahkan                 kembali kolom tanpa
                           lebih dulu: bagian                     mengembalikan isinya.
                           sebelum koma menjadi
                           tempat_lahir, tanggal
                           berformat "15 Juni
                           2006" menjadi
                           tanggal_lahir, dan
                           bagian umur tidak
                           disimpan karena
                           dihitung.

  D-047       LOCKED       Batas teknis unggah    Persetujuan     Batas teknis            `database.md`, `security.md`,
                           lampiran (R3.1):       eksplisit       prototype, bukan        kode `web/`
                           gambar JPEG, PNG,      pemilik         ketentuan Kelurahan;
                           WEBP; PDF hanya pada   proyek, 28      dapat disesuaikan
                           kategori TAMBAHAN;     Oktober 2026.   setelah uji prototype.
                           maksimal 10 MB per                     HEIC tidak diterima.
                           berkas sebelum                         Hash tidak selalu sama
                           kompresi; jenis                        dengan isi berkas
                           berkas ditentukan                      tersimpan setelah
                           dari isi, bukan                        kompresi R3.2.
                           ekstensi. SHA-256
                           pada
                           files.sha256_hash
                           dihitung dari berkas
                           asli yang diunggah
                           dan dipakai untuk cek
                           duplikat per
                           Outreach.

  D-048       LOCKED       VAL-005 ditutup:       Persetujuan     Tanpa perubahan         `mustahik.md`, `tasks.md`,
                           seluruh field header   eksplisit       schema. Klien tetap     kode `web/`
                           Mustahik yang diisi    pemilik         tidak otomatis
                           Petugas wajib sebelum  proyek, 28      dianggap Kepala
                           Finalisasi             Oktober 2026.   Keluarga (D-036);
                           (survey_date,                          penyalinan hanya atas
                           kepala_keluarga_nama,                  tindakan Petugas.
                           kepala_keluarga_nik_n                  RT/RW diisi manual.
                           o_kk, kepala_keluarga
                           _alamat,
                           kepala_keluarga_rt,
                           kepala_keluarga_rw);
                           Draft boleh kosong.
                           Tanggal survei terisi
                           awal dari Tanggal
                           Outreach saat form
                           pertama kali dibuka
                           dan tetap dapat
                           diubah. Tombol
                           eksplisit "Salin dari
                           data Klien" menyalin
                           Nama, NIK, dan Alamat
                           Domisili Klien ke
                           data Kepala Keluarga;
                           tidak pernah
                           otomatis.

  D-049       LOCKED       Signature pad Petugas  Persetujuan     Dependensi frontend     `architecture.md`,
                           dan Warga (R5.1)       eksplisit       baru. Hasil pad         `roadmap.md`, kode `web/`
                           memakai pustaka npm    pemilik         dikirim sebagai PNG
                           signature_pad versi    proyek, 28      dan disimpan privat
                           5.1.4 (MIT, tanpa      Oktober 2026    melalui registry
                           dependensi turunan),   (opsi B).       files; validasi dan
                           versi dikunci. Di                      aturan tanda tangan
                           bawah pad tersedia                     ulang tetap di server.
                           tombol "Ulangi"
                           (hapus semua) dan
                           "Batalkan goresan
                           terakhir". Tanda
                           tangan dibuat baru
                           untuk setiap
                           Outreach; tanda
                           tangan sebelumnya
                           tidak dapat dipakai
                           ulang.

  D-050       LOCKED       Format tampilan nilai  Persetujuan     Hanya representasi      `outreach.md`, kode `web/`
                           Outreach pada preview  eksplisit       tampilan; nilai
                           Review dan PDF (R5.2,  pemilik         tersimpan tidak
                           R7.2): field 3         proyek, 28      berubah. Field yang
                           "Surabaya, 15 Juni     Oktober 2026.   belum diisi tidak
                           2006 / 20 tahun"                       pernah tampil sebagai
                           (umur terhadap                         "-" (outreach.md 6).
                           Tanggal Outreach);
                           field 18 "Sejahtera
                           (Desil 9)"; nominal
                           rupiah yang diisi 0
                           tampil "-" sedangkan
                           nominal lain tampil
                           "Rp 1.500.000" dan
                           total tetap dihitung
                           dari angka; baris
                           kendaraan "Sepeda
                           motor 2, Sepeda angin
                           1" dari jenis
                           bernilai lebih dari
                           0, kendaraan lainnya
                           memakai namanya,
                           "Tidak ada" bila
                           semuanya 0.

  D-051       LOCKED       Kontrak snapshot_data  Persetujuan     Tanpa perubahan         `migration-design.md`,
                           (R6.1) schema_version  eksplisit       schema. Preview dan     `database.md`, `roadmap.md`,
                           1: kelompok meta,      pemilik         PDF historis dirender   kode `web/`
                           client,                proyek, 28      dari snapshot.
                           family_income,         Oktober 2026,   Finalisasi membutuhkan
                           expenses, house,       termasuk empat  baris document_templat
                           assets, narrative,     keputusan       e_versions per Source;
                           mustahik, kifayah      usulan.         seed-nya memerlukan
                           (null untuk Bangga                     persetujuan terpisah
                           Surabaya); nilai                       (R6.2).
                           mentah dengan nama
                           kunci = nama kolom;
                           pilihan berkode
                           disimpan dengan
                           labelnya; hanya label
                           opsi yang disimpan,
                           label pertanyaan ikut
                           template; nilai
                           turunan tidak
                           disimpan; tanda
                           tangan, lampiran,
                           template, dan
                           approval tetap di
                           tabel masing-masing;
                           snapshot immutable
                           dan dibangun oleh
                           satu builder yang
                           diuji terhadap
                           kontrak.

  D-052       LOCKED       Dompdf dipasang        Persetujuan     Dependensi backend      `architecture.md`,
                           langsung sebagai       eksplisit       baru beserta            `security.md`, `roadmap.md`,
                           paket composer         pemilik         dompdf/php-font-lib,    kode `web/`
                           dompdf/dompdf versi    proyek, 28      dompdf/php-svg-lib,
                           3.1.6 (dikunci,        Oktober 2026    masterminds/html5, dan
                           LGPL-2.1), tanpa       (opsi A).       sabberworm/php-css-
                           pembungkus                             parser. Huruf PDF
                           barryvdh/laravel-                      tidak identik dengan
                           dompdf. Renderer                       Calibri pada template;
                           mematikan akses                        huruf lain memerlukan
                           remote, PHP, dan                       keputusan terpisah.
                           JavaScript, membatasi
                           akses berkas lokal
                           (chroot) ke folder
                           resources/views/pdf,
                           memakai huruf DejaVu
                           Sans bawaan, dan
                           menyisipkan gambar
                           lampiran serta tanda
                           tangan sebagai data
                           URI dari storage
                           privat.

  D-053       LOCKED       Tampilan PDF Mustahik  Persetujuan     Menutup butir validasi  `mustahik.md`, `kifayah.md`,
                           dan Kifayah (R7.2):    eksplisit       kolom skor Mustahik     kode `web/`
                           kolom "score"          pemilik         (mustahik.md bagian 5
                           Mustahik hanya berisi  proyek, 28      butir 3) dan penulisan
                           tanda centang (✓)      Oktober 2026.   label fixed field
                           pada baris opsi yang                   Kifayah. Tidak ada
                           dipilih, tidak pernah                  perhitungan skor.
                           angka atau skor;                       VAL-006 tetap terbuka.
                           tanggal "Surabaya,
                           ..." di atas tanda
                           tangan Surveyor
                           memakai tanggal
                           survei; kotak kecil
                           di samping nominal
                           pengeluaran Kifayah
                           dicetak kosong
                           seperti sumber sampai
                           VAL-006 diputuskan;
                           label fixed field
                           Kifayah yang salah
                           ketik di sumber
                           dicetak dengan ejaan
                           yang dibetulkan (Ibu
                           Menyusui, Pendidikan
                           Tinggi, PENGELUARAN
                           TAMBAHAN), sedangkan
                           opsi pertanyaan
                           pilihan tetap literal
                           (D-037).

  D-054       LOCKED       Approve (R7.4)         Persetujuan     Seed demo bertambah     `database.md` 9.2/10.2,
                           mensyaratkan Kepala    eksplisit       satu file gambar dan    `roles-permissions.md`, kode
                           memiliki NIP dan       pemilik         satu baris              `web/`
                           template tanda tangan  proyek, 29      signature_templates;
                           aktif; bila salah      Oktober 2026.   tidak ada perubahan
                           satu tidak ada,                        schema. Template nyata
                           Approve ditolak                        Kepala tidak pernah
                           dengan pesan jelas                     dimasukkan.
                           dan status tetap
                           MENUNGGU_PENGESAHAN.
                           Sebelum pengelolaan
                           template oleh Admin
                           (R9.3), DemoSeeder
                           membuat satu template
                           tanda tangan sintetis
                           bertanda DEMO untuk
                           Kepala demo. Stempel
                           digital tidak
                           diterapkan
                           (stamp_template_id
                           NULL) karena
                           kebijakannya masih
                           PENDING VALIDATION.

  D-055       LOCKED       Lifecycle request      Persetujuan     Status permintaan       `workflow.md` 25, 31.4, 35.5;
                           Special Unlock         eksplisit       selalu mencerminkan     `database.md` 13.2;
                           (R8.1): satu Outreach  pemilik         tindakan unlock yang    `design.md`; kode `web/`
                           hanya boleh memiliki   proyek, 29      sebenarnya. Tidak ada
                           satu permintaan        Oktober 2026.   perubahan schema;
                           terbuka (PENDING atau                  permintaan tidak
                           PROCESSING) dari                       mengubah status
                           siapa pun; Admin                       Outreach dan tidak
                           hanya dapat menolak                    mengirim notifikasi
                           permintaan yang                        (Workflow 25.9).
                           sedang PROCESSING,
                           dengan alasan wajib
                           yang terlihat oleh
                           pengaju; permintaan
                           PROCESSING menjadi
                           COMPLETED secara
                           otomatis saat Admin
                           melakukan Lock atau
                           Lock & Ajukan pada
                           Outreach itu setelah
                           Special Unlock
                           (R8.2), bukan
                           ditandai manual.

  D-056       LOCKED       Special Unlock         Persetujuan     Tidak ada perubahan     `workflow.md` 9.6, 18.3, 25;
                           (R8.2): sesi aktif     eksplisit       schema. Unlock, Lock,   `database.md` 11, 13.2, 23.2;
                           ditentukan dari        pemilik         dan Lock & Ajukan       `roles-permissions.md`; kode
                           history_events, yaitu  proyek, 29      dicatat di              `web/`
                           bila event Special     Oktober 2026.   history_events dan
                           Unlock terakhir                        audit_logs. Perubahan
                           adalah                                 data yang
                           SPECIAL_UNLOCKED,                      ditandatangani memaksa
                           tanpa kolom atau                       Lock karena Lock &
                           status baru. Alasan                    Ajukan memvalidasi
                           wajib diisi sekali                     tanda tangan seperti
                           saat Unlock dan                        Finalisasi.
                           berlaku untuk sesi;
                           setiap perubahan data
                           oleh Admin (termasuk
                           autosave yang
                           mengubah data)
                           dicatat sebagai
                           SPECIAL_UNLOCK_EDITED
                           dengan alasan sesi
                           dan field yang
                           berubah. Lock wajib
                           disertai catatan
                           untuk Petugas yang
                           tampil di halaman
                           pengisian. Selama
                           sesi aktif, Kepala
                           tidak dapat Approve
                           atau Return. Unlock
                           memindahkan
                           permintaan PENDING ke
                           PROCESSING. Admin
                           tidak menandatangani;
                           tanda tangan yang
                           dirujuk versi
                           historis tidak
                           dihapus saat data
                           berubah.

  D-057       LOCKED       Master Source/Type     Persetujuan     Tanpa perubahan         `database.md` 4.2, 4.3;
                           (R9.2): tiga Source    eksplisit       schema. Perubahan       `design.md`; `roles-
                           baseline tidak         pemilik         dicatat di audit_logs.  permissions.md`; kode `web/`
                           bertambah di           proyek, 29      Nama baru tampil pada
                           prototype karena alur  Oktober 2026.   daftar dan form
                           form dan paket PDF                     berikutnya; versi
                           ditentukan per Source                  historis tetap memakai
                           (Workflow 9.1 sampai                   nama di snapshot.
                           9.3); Admin dapat                      Menambah Source baru
                           mengganti nama dan me                  memerlukan keputusan
                           ngaktifkan/menonaktif                  alur terlebih dahulu.
                           kan Source. Type
                           dapat ditambah (kode
                           huruf kapital, angka,
                           garis bawah, unik),
                           diganti nama, dan
                           dinonaktifkan. Kode
                           tidak pernah berubah
                           (D-040). Minimal satu
                           Source dan satu Type
                           tetap aktif.

  D-058       LOCKED       Template tanda tangan  Persetujuan     Tanpa perubahan         `database.md` 10.2, 10.3;
                           Kepala dan stempel     eksplisit       schema. Pengesahan      `design.md`; kode `web/`
                           (R9.3): Admin          pemilik         tetap merujuk versi
                           mengunggah gambar PNG  proyek, 29      yang dipakai saat itu.
                           atau JPG (maksimal 2   Oktober 2026.   Tanpa template aktif,
                           MB, isi divalidasi);                   Approve ditolak
                           setiap unggahan                        (D-054). Semua
                           menjadi versi baru                     aktivasi dan
                           yang langsung aktif                    penonaktifan tercatat
                           dan menonaktifkan                      di audit_logs.
                           versi aktif
                           sebelumnya
                           (valid_until diisi);
                           tidak ada edit atau
                           hapus; Admin dapat
                           menonaktifkan versi
                           aktif tanpa
                           pengganti. Stempel
                           dapat dikelola dengan
                           maksimal satu aktif,
                           tetapi belum dicetak
                           pada PDF selama
                           kebijakannya PENDING
                           VALIDATION.

  D-059       LOCKED       Penandaan arsip        Persetujuan     Tanpa perubahan         `database.md` 13.1;
                           (R9.4): Admin          eksplisit       schema. Tanda arsip,    `workflow.md` 27; `design.md`;
                           menandai versi         pemilik         koreksi, dan            kode `web/`
                           terbaru yang disahkan  proyek, 29      verifikasi tercatat di
                           dari Outreach SELESAI  Oktober 2026.   audit_logs. Setelah
                           dengan sistem arsip                    Special Unlock dan
                           (teks bebas, wajib),                   pengesahan ulang,
                           nomor referensi                        versi baru muncul lagi
                           (opsional), dan                        sebagai belum
                           tanggal diarsipkan                     diarsipkan. Cara
                           (wajib, tidak di masa                  verifikasi resmi tetap
                           depan); satu tanda                     menunggu prosedur
                           per versi; batas                       Kelurahan.
                           waktu satu bulan
                           sejak pengesahan
                           ditampilkan dan yang
                           terlewat ditandai.
                           Data arsip dapat
                           dikoreksi Admin dan
                           diaudit. Admin dapat
                           menandai arsip
                           terverifikasi
                           (verified_at) tanpa
                           aturan verifikasi
                           tambahan.

  D-060       LOCKED       Manajemen user         Persetujuan     Tanpa perubahan         `database.md` 4.1; `roles-
                           (R9.1): Admin membuat  eksplisit       schema. Semua           permissions.md`; `design.md`;
                           akun dengan password   pemilik         perubahan akun          kode `web/`
                           awal dan dapat         proyek, 29      tercatat di audit_logs
                           mereset password;      Oktober 2026.   tanpa password;
                           pengguna menggantinya                  koreksi NIP tercatat
                           sendiri (D-044). Role                  terpisah beserta
                           tidak dapat diubah                     alasannya. PDF lama
                           setelah akun dibuat;                   ikut menampilkan NIP
                           akun tidak dihapus,                    hasil koreksi karena
                           hanya dinonaktifkan.                   NIP dibaca saat render
                           Mengaktifkan atau                      (D-041). Format resmi
                           membuat Kepala                         NIP tetap PENDING
                           ditolak selama masih                   VALIDATION.
                           ada Kepala aktif
                           lain; Admin
                           menonaktifkan Kepala
                           lama lebih dulu. NIP
                           hanya untuk Kepala
                           (angka, maksimal 18);
                           NIP yang sudah
                           dipakai pada
                           pengesahan hanya
                           dapat diubah sebagai
                           koreksi administratif
                           dengan alasan wajib.
                           Admin tidak dapat
                           menonaktifkan akunnya
                           sendiri dan minimal
                           satu Admin tetap
                           aktif.

  D-061       —            Implementasi           Keputusan       Tanpa perubahan         `security.md`; `design.md`;
                           pencarian (R10.1):     implementasi    schema. Kriteria        kode `web/`
                           kriteria (nama, NIK,   agent dalam     bertahan selama sesi
                           ID internal, Source,   batas           sampai dihapus.
                           Jenis, rentang         security.md     Dashboard memakai
                           Tanggal Outreach)      dan D-039, 29   batas visibilitas yang
                           dikirim lewat POST     Oktober 2026.   sama dengan daftar.
                           dan disimpan di sesi,
                           bukan di path atau
                           query string. Nama
                           dan ID internal
                           dicocokkan sebagian;
                           NIK harus sama persis
                           agar tidak dapat
                           dipakai menelusuri
                           warga. Untuk Kepala,
                           kriteria isi (nama,
                           NIK) hanya
                           mencocokkan Outreach
                           MENUNGGU_PENGESAHAN
                           dan SELESAI sehingga
                           isi PERLU_PERBAIKAN
                           tidak bocor lewat
                           hasil pencarian.

  D-062       —            Implementasi           Keputusan       Tanpa perubahan         `workflow.md` 30; `design.md`;
                           notifikasi dan         implementasi    schema. Jumlah          kode `web/`
                           timeline (R10.2):      agent dalam     notifikasi belum
                           setiap pengguna hanya  batas Workflow  dibaca tampil di menu
                           melihat notifikasinya  20.2, 30 dan    samping. Pembersihan
                           sendiri; membuka       D-039, 29       notifikasi 30 hari
                           notifikasi             Oktober 2026.   tetap di R10.3.
                           menandainya dibaca
                           lalu mengarah ke
                           Outreach sesuai hak
                           akses saat itu
                           (pengisian bila boleh
                           mengedit, halaman
                           tinjau bila boleh
                           melihat), karena
                           notifikasi bukan
                           sumber kebenaran.
                           Timeline pada halaman
                           tinjau memuat semua
                           event bagi yang boleh
                           melihat isi, termasuk
                           field dan nilai yang
                           diubah Admin selama
                           Special Unlock;
                           Kepala pada
                           PERLU_PERBAIKAN hanya
                           melihat event
                           pengajuan dan
                           keputusan beserta
                           catatannya (D-039).

  D-063       LOCKED       Pembersihan            Persetujuan     Tanpa perubahan schema  `workflow.md` 30.4;
                           notifikasi (R10.3):    eksplisit       dan tanpa mengubah      `setup.md`; kode `web/`
                           perintah               pemilik         pengaturan Windows
                           `notifications:prune`  proyek, 29      oleh agent.
                           menghapus notifikasi   Oktober 2026.   Pembersihan hanya
                           yang berumur lebih                     menghapus baris
                           dari 30 hari sejak                     notifications;
                           created_at dan                         Outreach, History,
                           didaftarkan pada                       Audit, approval,
                           scheduler Laravel                      versi, dan file tidak
                           setiap hari. Di                        tersentuh (Workflow
                           Windows lokal                          30.4).
                           scheduler dijalankan
                           dengan `php artisan
                           schedule:work` selama
                           aplikasi dipakai,
                           atau pemilik sendiri
                           membuat entri Task
                           Scheduler untuk `php
                           artisan
                           schedule:run`.
                           Notifikasi
                           kedaluwarsa
                           disembunyikan dari
                           tampilan dan jumlah
                           belum dibaca walaupun
                           pembersihan belum
                           berjalan.

  D-064       LOCKED       Jawaban validasi       Jawaban         Tanpa perubahan         `outreach.md` 3, 7.2, 8;
                           Kelurahan (29 Oktober  pemilik proyek  schema. Seed            `mustahik.md`; `kifayah.md` 7;
                           2026): (1) label       atas            MasterDataSeeder        `database.md`; `workflow.md`
                           desil mengikuti        pertanyaan      menambah template       31.3; `tasks.md`; kode `web/`
                           pembagian resmi DTST   validasi, 29    package versi 2 dan
                           yang dipakai           Oktober 2026,   menonaktifkan versi 1.
                           Kelurahan,             berdasarkan     Label desil baru
                           menggantikan label     keterangan      berlaku untuk
                           D-019; (2) opsi        Kelurahan.      penyimpanan
                           Mustahik yang                          berikutnya; nilai
                           beririsan dipakai apa                  status_kesejahteraan
                           adanya (VAL-001                        yang sudah tersimpan
                           ditutup); (3) kotak                    tidak diubah. Berkas
                           di samping nominal                     logo disimpan di
                           pengeluaran Kifayah                    docs/reference dan web
                           dicentang bila                         /resources/views/pdf/l
                           nominal bukan 0                        ogos. Bukti tertulis
                           (VAL-006 ditutup);                     persetujuan OUT-VAL-05
                           (4) tidak ada daftar                   masih menunggu.
                           resmi lampiran
                           tambahan per Type,
                           lampiran di luar
                           empat wajib masuk
                           kategori "Lampiran
                           lain" dan saran per
                           Type dihapus; (5)
                           batas 10 MB per
                           berkas dan paling
                           banyak 10 lampiran
                           per Outreach termasuk
                           empat wajib; (6)
                           antrean
                           MENUNGGU_PENGESAHAN
                           boleh diputuskan
                           Kepala aktif yang
                           baru saat Kepala
                           berganti (Workflow
                           31.3); (7) PDF
                           memakai logo Pemkot
                           Surabaya di kiri kop
                           PDF Outreach dan logo
                           Baznas Kota Surabaya
                           di tengah atas PDF
                           Mustahik dan Kifayah
                           untuk Source Baznas
                           maupun CSR. Perubahan
                           tampilan PDF dimuat
                           dalam template PDF
                           versi 2; versi yang
                           sudah difinalisasi
                           tetap dirender dengan
                           template yang
                           dicatatnya.

  D-065       LOCKED       Blok pengesahan PDF    Persetujuan     Perubahan schema:       `outreach.md` 8.4;
                           Outreach: (1) kolom    eksplisit       migration `add_pangkat  `database.md` 4.1, 10.2, 10.3;
                           baru `users.pangkat`   pemilik         _to_users_table`        `workflow.md` 16.6; kode
                           (nullable, hanya       proyek, 29      (amandemen Database     `web/`
                           untuk KEPALA, diisi    Oktober 2026    v1.1, jumlah tabel
                           Admin) dicetak di      (termasuk       tetap 22, mengikuti
                           bawah nama; pangkat    perubahan       preseden D-041). Kolom
                           yang tercetak          schema kolom    stamp_template_id
                           disimpan pada          pangkat).       sudah ada. Perubahan
                           metadata history                       pangkat diaudit
                           APPROVED sehingga                      sebagai USER_UPDATED.
                           kenaikan pangkat
                           tidak mengubah
                           pengesahan lama; (2)
                           nama Kepala dicetak
                           huruf kapital; (3)
                           stempel digital boleh
                           dicetak: stempel
                           aktif dicatat pada `o
                           utreach_approvals.sta
                           mp_template_id` dan
                           dicetak menimpa sisi
                           kiri tanda tangan
                           Lurah; pengesahan
                           tanpa stempel aktif
                           tetap berjalan. Nama,
                           pangkat, dan NIP asli
                           Lurah tidak dicatat
                           di repo, seed, atau
                           dokumen; diisi
                           pemilik lewat menu
                           Pengguna saat
                           dipakai.
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

## Catatan Keputusan — 27 Oktober 2026

D-030 sampai D-042 dicatat berdasarkan jawaban eksplisit pemilik proyek atas
audit kesiapan Tahap 1 (PLAN-002) dan persetujuan rencana sinkronisasi pada 27
Oktober 2026. D-041 adalah satu-satunya perubahan schema (kolom `users.nip`);
D-031 menambah nilai `event_type` dan pengecualian invarian tanpa mengubah
kolom. Teks Workflow v2.1 diselaraskan pada bagian yang disebut D-031,
D-033, D-034, D-035, dan D-038. Tidak ada keputusan yang membuka gate
implementasi atau mengotorisasi bootstrap, migration, atau seed sebelum gate
dibuka. Dokumen terdampak disesuaikan satu file per giliran.

Tambahan 27 Oktober 2026 (setelah gate dibuka): pemilik proyek mengizinkan
agent mengerjakan transkripsi literal opsi Kifayah yang pada D-037 semula
dikerjakan pemilik proyek; hasilnya dikonfirmasi benar oleh pemilik (VAL-003
selesai).
Tata letak blok pengesahan untuk D-041 dikonfirmasi dan dicatat pada
`outreach.md` bagian 8.4 tanpa mencantumkan nama atau NIP pejabat nyata;
tanggal pada blok tersebut adalah tanggal Kepala mengesahkan, bukan Tanggal
Outreach (VAL-004 selesai).
