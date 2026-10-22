# Roles & Permission Boundary --- v1.6

Dokumen ini menetapkan batas otorisasi untuk peran `PETUGAS`, `KEPALA`,
dan `ADMIN`. Alur bisnis dan status mengikuti
`docs/architecture/workflow.md` v2.1. Dokumen ini tidak mengubah
baseline Database v1.1 atau mengotorisasi perubahan skema/migrasi.

## Aturan Otorisasi

``` text
Authentication → role → ownership/scope → status → action permission → resource permission
```

Otorisasi wajib ditegakkan di server. UI hanya mencerminkan izin yang
telah diputuskan server dan bukan batas keamanan tersendiri.

Setiap permintaan harus memeriksa seluruh lapisan otorisasi yang
relevan. Search, filter, URL langsung, endpoint, unduhan, serta akses ke
child resource tidak boleh melewati batas role, ownership/scope, status,
atau izin terhadap resource.

## Matriks Akses

  -----------------------------------------------------------------------------
  Aksi                    PETUGAS           KEPALA            ADMIN
  ----------------------- ----------------- ----------------- -----------------
  Membuat/mengedit Draft  Ya                Tidak             Sesuai scope
  milik sendiri                                               administratif

  Mengedit                Ya                Tidak             Sesuai scope
  `PERLU_PERBAIKAN` milik                                     administratif
  sendiri                                                     

  Melakukan Finalisasi /  Ya, untuk         Tidak             Sesuai scope
  Finalisasi Ulang        Outreach sendiri                    administratif dan
                          dan jika validasi                   aturan workflow
                          lengkap                             

  Meninjau data terkunci  Milik sendiri,    Ya, dalam scope   Ya, dalam scope
                          read-only         Kelurahan         administratif

  Return ke               Tidak             Ya, catatan wajib Tidak
  `PERLU_PERBAIKAN`                                           

  Approve                 Tidak             Ya, dalam scope   Tidak
                                            Kelurahan dan     
                                            hanya jika        
                                            seluruh gate      
                                            approval lolos    

  Mengajukan Request      Ya, untuk         Ya, dalam scope   Tidak diperlukan
  Special Unlock          Outreach yang     Kelurahan         
                          boleh diakses                       

  Melakukan Special       Tidak             Tidak             Ya, sesuai scope
  Unlock/edit secara                                          administratif dan
  langsung                                                    aturan workflow

  Lock / Lock & Ajukan    Tidak             Tidak             Ya, sesuai scope
                                                              administratif dan
                                                              aturan workflow

  Mengelola               Tidak             Tidak             Ya
  user/template/storage                                       

  Print PDF               Tidak             Ya, dalam scope   Ya, dalam scope
                                            Kelurahan         administratif
  -----------------------------------------------------------------------------

Keterangan: - Izin administratif ADMIN tetap dibatasi oleh scope
administratif yang berlaku; izin ini bukan akses global tanpa batas. -
Baris Finalisasi/Finalisasi Ulang tidak mengesampingkan validasi data,
lampiran wajib, tanda tangan, pemeriksaan versi, atau status yang
diizinkan oleh workflow. - Approve hanya dapat dilakukan KEPALA. ADMIN
tidak pernah menjadi approver normal, termasuk ketika ADMIN melakukan
Special Unlock atau Lock & Ajukan. - Bila salah satu PDF wajib gagal
dibuat saat approval, approval harus gagal. Status tetap
`MENUNGGU_PENGESAHAN`, tidak boleh menjadi `SELESAI`, dan notifikasi
`OUTREACH_COMPLETED` tidak boleh dikirim. Kegagalan PDF tidak memberikan
izin untuk melewati gate atau mengubah status secara manual. - Hak print
PDF tidak mengubah kepemilikan data, status bisnis, atau keputusan
approval.

## Batas Per Peran

### PETUGAS

-   Hanya membaca atau mengubah Outreach miliknya sendiri dan hanya
    ketika status serta workflow mengizinkan perubahan.
-   Tidak dapat mengedit data terkunci melalui jalur approval normal.
-   Dapat mengajukan Request Special Unlock, tetapi tidak dapat
    mengaktifkan Special Unlock sendiri.
-   Tidak dapat melakukan Return atau Approve.

### KEPALA

-   Meninjau data terkunci dalam scope Kelurahan.
-   Tidak mengedit field melalui approval normal dan bukan pelaksana
    Special Unlock.
-   Dapat melakukan Return dengan catatan wajib atau Approve sesuai
    workflow.
-   Tidak dapat mengambil alih kewenangan ADMIN untuk Lock / Lock &
    Ajukan atau pengelolaan administratif.

### ADMIN

-   Mengelola data dan melakukan tindakan administratif hanya dalam
    scope yang diberikan.
-   Dapat menjalankan Special Unlock secara langsung sesuai aturan
    workflow.
-   Dapat melakukan Lock / Lock & Ajukan sesuai aturan workflow.
-   Tidak dapat melakukan Approve atau Return sebagai approver normal.
-   Tidak boleh mengabaikan validasi, gate PDF, pemeriksaan versi, atau
    jejak audit hanya karena memiliki hak administratif.

## Aturan Tambahan

-   Tidak ada ownership transfer/take-over pada v1.6 kecuali ditetapkan
    melalui keputusan proyek yang eksplisit.
-   Perubahan role atau scope tidak boleh digunakan untuk melewati
    pembatasan resource yang sudah ada.
-   Izin akses ke PDF dan lampiran harus mengikuti otorisasi terhadap
    Outreach/historical version terkait; mengetahui URL atau ID resource
    saja tidak cukup.
-   Tindakan administratif dan perubahan terkendali harus mengikuti
    pencatatan audit yang ditetapkan dalam workflow. Dokumen ini tidak
    mendefinisikan skema audit baru.
-   Jika ada konflik antara matriks ini dan workflow canonical, jangan
    menebak perilaku baru. Tandai konflik dan rekonsiliasikan sebelum
    implementasi; perubahan ini tidak mengubah baseline database.
