# AGENTS.md — MRP Ranu

Titik masuk setiap agen AI yang bekerja di repo ini. Baca seluruh file ini sebelum edit pertama.

## Prosedur mulai

1. Baca `docs/00-konteks.md` (bisnis + glosarium) dan `docs/04-hak-akses.md` (kebijakan akses).
2. Baca spesifikasi modul yang disentuh di `docs/modul/`.
3. Cek `docs/03-roadmap.md` — pastikan tugas berada di fase aktif atau diminta eksplisit oleh user.

Selesai bila: kamu bisa menyebut spesifikasi dan kriteria penerimaan mana yang dipenuhi tugas ini.

## Aturan keras

1. **Spesifikasi adalah sumber kebenaran.** Kode bertentangan dengan docs: berhenti, laporkan konflik, tanya user. Jangan pilih salah satu diam-diam. Alasan: docs adalah kontrak lintas sesi; drift tidak terdeteksi tanpa ini.
2. **Perubahan skema hanya lewat migration.** Setiap perubahan skema = migration Laravel + pembaruan `docs/02-model-data.md` pada commit yang sama. Alasan: docs itu satu-satunya referensi desain data sebelum kode ada.
3. **Mutasi stok hanya lewat `StockMovementService`.** Dilarang INSERT `stock_movements` atau UPDATE `stock_balances` di luar service ini. Setiap mutasi double-entry, dalam transaksi DB dengan `SELECT ... FOR UPDATE`. Alasan: invariant konservasi stok (`docs/modul/03-inventory.md`).
4. **Dependensi baru butuh persetujuan.** Paket composer/npm baru: tanya user dulu, sebutkan alternatif yang sudah ada. Alasan: proyek jangka panjang, permukaan dependensi harus terkontrol.
5. **Tanpa fitur spekulatif.** Kerjakan hanya yang ada di spesifikasi atau diminta user. Alasan: scope creep adalah risiko utama proyek jangka panjang.
6. **Tes ikut kode.** Feature test untuk setiap endpoint/aksi; logika perhitungan MRP wajib 100% branch coverage. Alasan: salah hitung MRP = kerugian pembelian/produksi nyata.
7. **Diff bedah.** Jangan refactor kode sekitar yang tidak diminta. Alasan: setiap baris berubah harus bisa ditelusuri ke permintaan.
8. **Uang = integer rupiah, kuantitas = `decimal(15,3)`.** Dilarang float untuk uang dan kuantitas. Alasan: drift pembulatan terakumulasi di ledger stok.
9. **Akses lewat Gate permission.** Dilarang memeriksa nama divisi/kode role di controller, Livewire, atau query. Alasan: kebijakan bisa diubah admin lewat UI; pengecekan hardcoded tidak mengikuti perubahan (`docs/04-hak-akses.md`).

## Konvensi

- Dokumentasi: Bahasa Indonesia. Kode, identifier, pesan commit: English.
- Konvensi kode lengkap: `docs/01-arsitektur.md` — baca sebelum menulis file PHP pertama.
- Kode error mutasi stok: `docs/modul/03-inventory.md` (satu-satunya rumah definisi error code).

## Definisi selesai

Tugas selesai bila semua benar:

- `php artisan test` hijau.
- Kriteria penerimaan spesifikasi terkait terpenuhi — sebutkan mana saja di ringkasan.
- Docs diperbarui bila perilaku/skema berubah.
- Tidak ada aturan keras di atas yang dilanggar.
