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
