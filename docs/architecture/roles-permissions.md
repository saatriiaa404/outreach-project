# Roles & Permission Boundary --- v1.6

Dokumen ini menetapkan batas otorisasi untuk peran `PETUGAS`, `KEPALA`,
dan `ADMIN`. Alur bisnis dan status mengikuti
`docs/architecture/workflow.md` v2.1. Dokumen ini tidak mengubah
baseline Database v1.1 atau mengotorisasi perubahan skema/migrasi.

Revisi 23 Oktober 2026: batas peran ADMIN diselaraskan dengan
keputusan D-016 (`docs/project-management/decisions.md`). Workflow v2.1
dan Database v1.1 tidak berubah.

Revisi 27 Oktober 2026: visibilitas, dashboard, dan pencarian (D-039),
pengelolaan master Source/Type dan larangan unduh PDF final oleh PETUGAS
(D-040), arsip dan backup manual oleh ADMIN (D-038), serta pengelolaan NIP
Kepala (D-041) ditambahkan pada matriks. Workflow v2.1 tidak berubah.

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
  Membuat/mengedit Draft  Ya                Tidak             Tidak (tidak
  milik sendiri                                               membuat Outreach
                                                              dari nol)

  Mengedit                Ya                Tidak             Tidak (edit hanya
  `PERLU_PERBAIKAN` milik                                     lewat Special
  sendiri                                                     Unlock)

  Melakukan Finalisasi /  Ya, untuk         Tidak             Hanya lewat Lock
  Finalisasi Ulang        Outreach sendiri                    & Ajukan setelah
                          dan jika validasi                   Special Unlock,
                          lengkap                             validasi penuh

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

  Mengelola               Tidak             Tidak             Ya, termasuk NIP
  user/template/storage                                       Kepala (D-041)

  Print / unduh PDF       Tidak, termasuk   Ya, dalam scope   Ya, dalam scope
                          PDF final (D-040) Kelurahan         administratif

  Melihat Outreach        Milik sendiri     Tidak (D-039)     Ya, dalam scope
  `DRAFT`                                                     administratif

  Melihat Outreach        Milik sendiri     Hanya status dan  Ya, dalam scope
  `PERLU_PERBAIKAN`                         catatan Return    administratif
                                            pada riwayat,
                                            tanpa isi yang
                                            sedang diedit
                                            (D-039)

  Mengisi                 Ya, milik sendiri Tidak             Hanya selama
  Mustahik/Kifayah dan    selama status                       Special Unlock
  mengelola lampiran      editable

  Dashboard dan pencarian Outreach milik    Sesuai batas      Semua Outreach
                          sendiri (D-039)   visibilitas       dalam scope
                                            KEPALA (D-039)    administratif
                                                              (D-039)

  Mengelola master        Tidak             Tidak             Ya; `code` tidak
  Source/Type                                                 dapat diubah
                                                              (D-040)

  Menandai status arsip   Tidak             Tidak             Ya, manual
                                                              (D-038)

  Backup mingguan         Tidak             Tidak             Ya, manual
  database dan lampiran                                       (D-038)
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

## Scope Administratif ADMIN

Selaras dengan D-016 (`docs/project-management/decisions.md`):

-   Scope administratif ADMIN adalah seluruh Outreach dalam scope
    Kelurahan, tanpa batas kepemilikan PETUGAS. Ini bukan akses global
    di luar Kelurahan dan tidak memberi hak membuat Outreach dari nol,
    Approve, atau Return.
-   `Lock` setelah Special Unlock mengembalikan Outreach ke
    `PERLU_PERBAIKAN` bila masih membutuhkan pekerjaan Petugas/Warga.
-   `Lock & Ajukan untuk Pengesahan` adalah submission action yang
    setara Finalisasi: validasi penuh, pemeriksaan versi, historical
    version baru, dan status `MENUNGGU_PENGESAHAN`. Hanya Outreach yang
    telah difinalisasi, termasuk lewat `Lock & Ajukan`, masuk antrean
    KEPALA.

## Visibilitas, Dashboard, dan Pencarian

Selaras dengan D-039:

-   KEPALA tidak melihat Outreach berstatus `DRAFT`. Untuk
    `PERLU_PERBAIKAN`, KEPALA hanya melihat status dan catatan Return
    pada riwayat, tanpa isi yang sedang diedit Petugas.
-   Dashboard minimum: PETUGAS melihat Outreach miliknya per status;
    KEPALA melihat antrean `MENUNGGU_PENGESAHAN` dan Outreach `SELESAI`;
    ADMIN melihat semua Outreach per status, permintaan Special Unlock,
    dan status arsip.
-   Pencarian memakai nama, NIK, ID internal, Source, Type, status, dan
    rentang tanggal. Hasil pencarian selalu dibatasi scope dan
    visibilitas role yang sama dengan dashboard dan URL langsung.
-   NIK boleh menjadi kriteria pencarian, tetapi tidak menjadi
    identifier URL (lihat `security.md`).

## Batas Per Peran

### PETUGAS

-   Hanya membaca atau mengubah Outreach miliknya sendiri dan hanya
    ketika status serta workflow mengizinkan perubahan.
-   Tidak dapat mengedit data terkunci melalui jalur approval normal.
-   Dapat mengajukan Request Special Unlock, tetapi tidak dapat
    mengaktifkan Special Unlock sendiri.
-   Tidak dapat melakukan Return atau Approve.
-   Tidak mengunduh atau mencetak PDF final (D-040).

### KEPALA

-   Meninjau data terkunci dalam scope Kelurahan.
-   Tidak mengedit field melalui approval normal dan bukan pelaksana
    Special Unlock.
-   Dapat melakukan Return dengan catatan wajib atau Approve sesuai
    workflow.
-   Tidak dapat mengambil alih kewenangan ADMIN untuk Lock / Lock &
    Ajukan atau pengelolaan administratif.
-   Tidak melihat Draft; untuk `PERLU_PERBAIKAN` hanya melihat status
    dan catatan Return (D-039).

### ADMIN

-   Mengelola data dan melakukan tindakan administratif hanya dalam
    scope yang diberikan.
-   Tidak membuat Outreach dari nol; data hanya diedit selama Special
    Unlock dengan alasan wajib dicatat.
-   Dapat menjalankan Special Unlock secara langsung sesuai aturan
    workflow.
-   Dapat melakukan Lock / Lock & Ajukan sesuai aturan workflow.
-   Tidak dapat melakukan Approve atau Return sebagai approver normal.
-   Tidak boleh mengabaikan validasi, gate PDF, pemeriksaan versi, atau
    jejak audit hanya karena memiliki hak administratif.
-   Satu-satunya peran yang mengelola master Source/Type; `code` tidak
    dapat diubah dan penonaktifan memakai `active` (D-040).
-   Melakukan penandaan arsip dan backup mingguan secara manual (D-038).
-   Mengelola NIP Kepala; perubahan NIP dicatat pada audit (D-041).

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
