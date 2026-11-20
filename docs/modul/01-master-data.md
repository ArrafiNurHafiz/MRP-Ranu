# Modul 01 — Master Data

Fase: F1. Entitas: `docs/02-model-data.md` bagian Master data.

## Tujuan

Satu sumber data induk untuk item, kategori, gudang, supplier, subcontractor (vendor maklon), customer. Semua modul lain mereferensi ke sini; tidak ada modul lain yang mendefinisikan item sendiri.

## Aturan bisnis

1. `items.code` unik, wajib, format bebas tapi tidak boleh berubah setelah item dipakai transaksi (movement/BOM/PO/WO/SO). Alasan: code adalah kunci referensi dokumen historis. Ubah nama boleh, ubah code tidak.
2. `items.type` menentukan peran fisik: `raw` tidak boleh punya BOM aktif; `wip`/`finished` boleh. `items.source_type` menentukan pemenuhan: `buy` → planned order purchase, `make` → make (wajib routing aktif saat rilis WO), `subcon` → maklon. Default: `raw`→`buy`, `wip`/`finished`→`make`; boleh diubah user.
3. `lot_policy = foq` mewajibkan `fixed_order_qty > 0`. `min_order_qty` dan `order_multiple` opsional; bila diisi harus > 0.
4. `lead_time_days >= 0`; 0 berarti tersedia seketika (offset MRP = 0 hari).
5. `safety_stock >= 0`.
6. Master yang sudah dipakai transaksi tidak boleh dihapus keras — soft delete + `is_active = false`; item nonaktif tidak boleh dipilih di dokumen baru tapi dokumen lama tetap valid.
7. Impor CSV: validasi per baris, baris gagal dilaporkan dengan nomor baris + alasan, baris valid tetap diproses (partial import). Duplikat `code` dalam file = seluruh file ditolak sebelum tulis apa pun.
8. Pembuatan subcontractor otomatis membuat satu gudang virtual `type=vendor` (transaksi atomik). Gudang vendor tidak bisa dibuat/diedit manual; nonaktifkan vendor hanya bila saldo gudang vendornya nol.
9. Pembuatan gudang internal otomatis membuat satu gudang karantina berpasangan (`is_quarantine=true`, transaksi atomik). Gudang karantina tidak bisa dibuat/diedit/dihapus manual; saldo di sana hanya bergerak lewat QC (modul 06).

## Alur utama

CRUD standar (list + cari + filter tipe/status, buat, ubah, nonaktifkan) untuk keenam entitas. Impor CSV item dengan template kolom: `code,name,type,source_type,unit,lead_time_days,safety_stock,lot_policy,fixed_order_qty`.

## Kriteria penerimaan

- [ ] CRUD keenam entitas berfungsi dengan validasi aturan 1–6 dan 8–9; setiap penolakan diuji feature test.
- [ ] Item dengan transaksi menolak perubahan code dan hapus keras (test).
- [ ] Buat vendor → gudang vendor otomatis ada; nonaktifkan vendor dengan saldo tersisa ditolak (test).
- [ ] Buat gudang internal → gudang karantina otomatis ada; gudang karantina tidak bisa diedit/dihapus (test).
- [ ] Impor CSV: fixture berisi baris valid + invalid menghasilkan laporan per baris; duplikat code menolak seluruh file (test).
- [ ] Divisi selain `admin` ditolak menulis master data; akses diatur lewat Gate (test, `docs/04-hak-akses.md`).
