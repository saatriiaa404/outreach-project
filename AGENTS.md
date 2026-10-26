# AGENTS.md — project/

Aturan kerja untuk coding agent (Claude Code atau lainnya) pada repo ini. Sumber kebenaran proyek
ada di `docs/`; peta dokumen ada di `docs/index.md`. Status gate implementasi dicatat di
`docs/project-management/tasks.md` (IMP-001).

## 1. Urutan Baca

1. `README.md` dan `docs/index.md`.
2. `docs/project-management/decisions.md`, `reconciliation.md`, dan `tasks.md`.
3. Dokumen kanonis yang relevan dengan tugas (lihat peta di bagian 10).
4. Checkpoint (`../checkpoint/`, di luar repo) adalah memori perencanaan untuk AI percakapan dan
   bukan sumber implementasi (D-029). Jangan membacanya kecuali pengguna secara eksplisit memintanya.

- Muat konteks bertahap; jangan membaca semua dokumen untuk setiap tugas. Perubahan kecil dan jelas
  tidak perlu memuat seluruh dokumentasi.
- Jangan memulai implementasi substansial hanya dari konteks percakapan bila dokumentasi tersedia.
- Checkpoint tidak menjadi sumber kebenaran. Bila pengguna memintanya dibaca dan file tidak dapat
  diakses, sampaikan keterbatasan itu dan jangan mengarang isinya. Nama checkpoint tetap v1.6 untuk
  revisi dokumentasi biasa; jangan membuat checkpoint bernomor baru tanpa keputusan eksplisit pengguna.

## 2. Otoritas dan Baseline

- Dokumen kanonis di `docs/` adalah sumber kebenaran per topik. Checkpoint hanya snapshot
  perencanaan. Kode bukan sumber kebenaran untuk keputusan baru, dan kode lama bukan bukti perilaku
  yang diinginkan.
- Baseline terkunci: Workflow v2.1 dan Database v1.1 (22 tabel). Jangan mengubah keputusan `LOCKED`,
  schema, field, relasi, constraint, migration, atau seed tanpa persetujuan eksplisit dan pencatatan
  dampaknya.
- `PENDING VALIDATION` bukan keputusan final Kelurahan dan tidak boleh ditulis seolah terverifikasi.
  Keberadaan PDF bukan bukti persetujuan formal pemilik formulir.
- Klasifikasikan informasi bila perlu: KNOWN, CONFIRMED, LOCKED, PENDING VALIDATION, UNKNOWN. Jangan
  mengarang requirement; bila perilaku tidak diketahui, katakan dan tanyakan.
- Jika dua sumber berbeda: catat konflik, sumber, evidence, dan dampaknya; jangan memilih diam-diam.
  Periksa berurutan: `decisions.md`, dokumen kanonis, lalu implementasi.
  Jika konflik konsekuensial belum terselesaikan, tanyakan kepada pengguna.
- Jangan meminta pengguna mengulang keputusan yang sudah jelas, kecuali ada evidence baru yang
  benar-benar bertentangan.

## 3. Lingkup Prototype dan Gate Implementasi

- Prototype bersifat lokal, non-produksi, memakai data dummy, dan didemokan pada satu komputer.
  Hosting, domain, dan penggunaan LAN di luar lingkup kecuali disetujui. Prototype bukan Production;
  jalan pintas prototype bukan arsitektur Production.
- Stack: Laravel 13, Vue 3 + TypeScript, Inertia, Vite, MySQL, Dompdf, Tailwind CSS + shadcn-vue
  (D-013, D-018). Lingkungan: Windows dengan Laragon (D-024). Rincian ada di `architecture.md`.
- Sebelum coding, selesaikan konflik kritis yang dapat mengubah perilaku bisnis, otorisasi,
  keamanan, workflow, atau baseline terkunci. Validasi yang menunggu Kelurahan atau prototype bukan
  penghalang otomatis bagi seluruh coding.
- Mulai implementasi hanya setelah pengguna menyetujui pembukaan gate secara eksplisit berdasarkan
  ringkasan kesiapan. Persetujuan itu tidak mengotorisasi perubahan baseline atau keputusan
  konsekuensial lain. Jangan membuka gate secara otomatis.
- Jangan menjalankan bootstrap, membuat migration, atau mengubah schema tanpa persetujuan eksplisit.
- Jangan mengarang field, opsi dropdown, cardinality, scoring, atau aturan validasi tanpa sumber.
  Dropdown hanya bila opsinya ada di PDF atau ketentuan yang disetujui; selain itu gunakan teks
  bebas.
- Gunakan data dummy. Jangan memasukkan PII nyata, secret, token, password, atau kredensial ke
  dokumen, data prototype, maupun commit.

## 4. Aturan Produk dan Keamanan

- Terapkan otorisasi server-side, akses file privat, validasi upload, dan optimistic concurrency
  sesuai `security.md` dan `roles-permissions.md`. UI bukan kontrol keamanan.
- ADMIN tidak membuat Outreach dari nol. Data hanya diedit selama Special Unlock dengan alasan wajib
  dicatat. `Lock & Ajukan` setara Finalisasi, sedangkan Approve dan Return milik KEPALA (D-016).
- Finalisasi diblokir bila lampiran wajib belum lengkap; Draft tetap boleh disimpan. Label lampiran
  dasar: "Foto Klien" (D-015).
- Jika pembuatan PDF wajib gagal, pengesahan gagal dengan alasan jelas: status tetap
  `MENUNGGU_PENGESAHAN`, tidak menjadi `SELESAI`, dan notifikasi `OUTREACH_COMPLETED` tidak dikirim.
- PDF Baznas/CSR berjumlah tiga (Outreach beserta lampiran, Mustahik, Kifayah). Bangga Surabaya
  hanya PDF Outreach beserta lampiran. Lampiran ditempatkan pada halaman terpisah dari formulir
  utama.
- Jaga keterlacakan riwayat, tanda tangan, persetujuan, dan referensi lampiran. Perubahan data
  formulir mengikuti aturan pembaruan tanda tangan dan pengesahan; perubahan lampiran saja tidak
  otomatis mewajibkan tanda tangan ulang.
- Opsi formulir Mustahik mengikuti PDF sumber secara literal; jangan menormalisasi opsi atau
  menghitung skor otomatis (D-022).
- Jangan mengubah aturan produk atau keamanan hanya demi mempermudah implementasi.

## 5. Persetujuan Perubahan

Perubahan konsekuensial menyangkut arsitektur, database/ERD, workflow/state, roles & permissions,
model keamanan, lingkup proyek, atau keputusan `LOCKED`. Perubahan substantif pada requirement atau
file di luar lingkup tugas diperlakukan sama:

Identifikasi dampak → laporkan temuan → usulkan perubahan beserta asumsi dan konsekuensinya → tunggu
persetujuan → terapkan → verifikasi.

- Jangan melewati aturan ini karena perubahan tampak praktis. Pengecualian hanya bila pengguna sudah
  menyetujui perubahan yang sama dan dokumen kanonis sudah diperbarui.
- Perubahan kecil, lokal, dan berisiko rendah dalam lingkup yang sudah diizinkan boleh langsung
  dikerjakan.
- Sebelum perubahan signifikan, sampaikan singkat: apa yang berubah, mengapa, area terdampak, dan
  perlu tidaknya pembaruan dokumen. Jangan menyembunyikan konsekuensi arsitektur, data, workflow,
  atau keamanan di dalam implementasi besar.

## 6. Alur Kerja

Pahami → temukan sumber kebenaran → periksa implementasi yang ada → nilai dampak → rencanakan →
implementasi → verifikasi → sinkronkan dokumen dan tugas.

- Tugas substansial: beri rencana singkat (modul/file terdampak, dependensi, dampak data/workflow,
  validasi/otorisasi, langkah verifikasi). Tugas kecil tidak perlu rencana formal; jangan membuat
  dokumen perencanaan panjang untuk perubahan rutin.
- Cari route, komponen, service, model, validasi, dan pola UI yang sudah ada sebelum membuat yang
  baru, lalu gunakan ulang polanya.
- Buat perubahan sekecil mungkin yang koheren. Hindari refactor luas, jangan menambah dependensi
  baru tanpa alasan jelas, dan pertahankan perilaku lain yang sudah berjalan.
- Kode harus terbaca, konsisten, dan mudah diuji. Ikuti Modular Monolith di `architecture.md`;
  jangan menambah lapisan atau abstraksi tanpa kebutuhan nyata.
- Selama rekonsiliasi dokumentasi: kerjakan satu file Markdown per giliran dan pertahankan nama
  serta lokasi file. Jangan membuat atau mengubah checkpoint; checkpoint dikelola pengguna di luar
  repo (D-029).

## 7. Git dan Penyuntingan File (D-017)

- Agent yang bekerja langsung pada repo lokal boleh mengedit file di dalam `project/` setelah
  perubahan disetujui sesuai aturan di atas. Jangan mengedit file di luar `project/` (termasuk
  folder checkpoint) tanpa persetujuan eksplisit.
- Commit per task yang selesai dan terverifikasi, dengan pesan yang jelas. Jangan `git push`, force-
  push, atau mengubah riwayat Git tanpa persetujuan eksplisit pengguna.
- Tanpa akses langsung ke repo (percakapan biasa), berikan file revisi yang dapat diunduh agar
  pengguna menggantinya sendiri.

## 8. Sinkronisasi Dokumen dan Pelacakan

- Bila implementasi mengubah perilaku terdokumentasi, perbarui dokumen kanonis yang terkait. Contoh:
  field Outreach baru → `forms/outreach.md`, `database.md`, `design.md`; perubahan workflow →
  `workflow.md`, `roles-permissions.md`, `design.md`, dan `PRD.md` bila perilaku produk berubah.
  Jangan memperbarui dokumen tak terkait demi simetri.
- Keputusan penting yang disetujui: perbarui sumber kanonis, catat di `decisions.md` (apa, mengapa,
  area, tanggal, terkunci atau tidak), lalu perbarui dokumen turunan. Jangan mencatat preferensi
  coding kecil sebagai keputusan.
- `tasks.md` diperbarui saat task selesai, terblokir, atau berubah lingkup/status, dan hanya bila
  evidence memenuhi kriteria selesai. Jangan menulis ulang untuk pekerjaan sepele.

## 9. Verifikasi dan Laporan

- Jangan menyatakan selesai hanya karena kode atau file berubah. Jalankan pemeriksaan yang relevan
  (uji otomatis, tipe, lint, build, otorisasi, validasi, transisi workflow, upload dan akses file,
  UI, regresi), periksa diff, lalu laporkan. Jangan mengklaim pengujian yang tidak dijalankan.
- Laporan tugas substansial: Perubahan, Verifikasi, Dokumentasi, Sisa (keterbatasan, blocker, tindak
  lanjut). Tugas sepele cukup laporan singkat.
- Setelah perubahan dokumen, periksa tautan silang dan konsistensi dengan keputusan yang berlaku,
  serta tandai hal yang masih menunggu validasi.

## 10. Peta Konteks per Tugas

Ini rute, bukan kewajiban membaca semua file. Path relatif terhadap `docs/`.

| Tugas | Baca seperlunya |
|---|---|
| Produk / requirement | `product/PRD.md`, `product/scope.md`, `domain/glossary.md` |
| Outreach | `domain/forms/outreach.md`, `architecture/database.md`, `architecture/workflow.md`, `ux/design.md` |
| Mustahik | `domain/forms/mustahik.md`, `architecture/database.md`, `architecture/workflow.md`, `ux/design.md` |
| Kifayah | `domain/forms/kifayah.md`, `architecture/database.md`, `architecture/workflow.md`, `ux/design.md` |
| Otorisasi / peran | `architecture/roles-permissions.md`, `workflow.md`, `security.md`, `database.md` |
| UI / UX | `ux/design.md` dan dokumen form atau workflow terkait |
| Arsitektur / infrastruktur | `architecture/architecture.md`, `database.md`, `security.md` |
| Pengujian | `engineering/testing.md` dan dokumen fitur yang diuji |

## 11. Prinsip Akhir

Utamakan konsistensi proyek, kebenaran, keterbacaan, dan kemudahan pemeliharaan, bukan kecepatan
menghasilkan kode. Saat ragu: baca dokumen kanonis, periksa implementasi, identifikasi dampak, dan
tanyakan sebelum perubahan konsekuensial. Jangan mengarang requirement, jangan menimpa keputusan
`LOCKED` secara diam-diam, dan jangan menyembunyikan perubahan arsitektur atau data yang penting.
