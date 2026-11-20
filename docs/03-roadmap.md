# 03 — Roadmap

Aturan fase: kerjakan hanya fase aktif. Fase berikutnya dimulai hanya bila seluruh kriteria selesai fase aktif terpenuhi dan user menyetujui. Menambah/mengubah fase = keputusan user, catat di file ini.

Nomor fase = nomor modul spesifikasi (`F1` ↔ `modul/01`, dst).

**Fase aktif: F0.**

## F0 — Fondasi

Laravel 12 terpasang, MySQL, Livewire, Pest, auth + divisi + permission (spesifikasi: `docs/04-hak-akses.md`), layout dasar, CI menjalankan `php artisan test`.

Selesai bila:
- `php artisan test` hijau di clone bersih.
- Login/logout berfungsi; kriteria penerimaan `docs/04-hak-akses.md` terpenuhi (matriks default + UI admin + test 200/403).
- Migration users + divisions + permissions + division_permissions ada.

## F1 — Master data

Spesifikasi: `modul/01-master-data.md`. Item (termasuk `source_type`), kategori, gudang + gudang karantina otomatis, supplier, subcontractor, customer; CRUD + validasi + impor CSV.

Selesai bila: seluruh kriteria penerimaan modul 01 lulus feature test.

## F2 — BOM

Spesifikasi: `modul/02-bom.md`. BOM multi-level, versi, aktivasi, deteksi siklus, phantom, supply mode, low-level code.

Selesai bila: kriteria modul 02 lulus; tes membuktikan BOM siklus ditolak dan low-level code benar untuk fixture 3 tingkat.

## F3 — Inventory

Spesifikasi: `modul/03-inventory.md`. `StockMovementService`, saldo (internal + vendor + karantina), transfer, adjustment, opname, rekonsiliasi.

Selesai bila: kriteria modul 03 lulus; tes konkurensi membuktikan tidak ada saldo negatif di bawah akses paralel; rekonsiliasi saldo = Σ movement pada fixture acak.

## F4 — Permintaan & MPS

Spesifikasi: `modul/04-mps.md`. Sales order, forecast, MPS, alokasi.

Selesai bila: kriteria modul 04 lulus.

## F5 — Mesin MRP

Spesifikasi: `modul/05-mrp.md`. `MrpEngine` murni + `MrpRunner`, planned order (purchase/make/subcon), pegging, exception message.

Selesai bila: kriteria modul 05 lulus; 100% branch coverage `MrpEngine`; fixture deterministik mencakup multi-level, phantom, safety stock, LFL/FOQ, lead time offset, scheduled receipt, subcon + komponen supply vendor.

## F6 — Quality Control

Spesifikasi: `modul/06-qc.md`. Penerimaan masuk karantina, inspeksi pass/reject, pelepasan/return ke supplier/vendor/internal.

Selesai bila: kriteria modul 06 lulus; tes membuktikan stok karantina tidak bisa dipakai, reject kembali ke pihak/bagian yang benar, konservasi stok terjaga.

## F7 — Pembelian

Spesifikasi: `modul/07-pembelian.md`. PR dari planned order, PO, penerimaan parsial, stok masuk via QC.

Selesai bila: kriteria modul 07 lulus; tes end-to-end planned order → PR → PO → terima → QC → saldo bertambah.

## F8 — Routing & work center

Spesifikasi: `modul/08-routing.md`. Work center, routing multi-operasi, versi + aktivasi, waktu setup/run.

Selesai bila: kriteria modul 08 lulus.

## F9 — Produksi

Spesifikasi: `modul/09-produksi.md`. WO dari planned order make, snapshot BOM + routing, issue material, completion via QC, progres operasi.

Selesai bila: kriteria modul 09 lulus; tes end-to-end WO → issue → completion → QC → saldo komponen berkurang dan saldo jadi bertambah, konservasi terbukti.

## F10 — Outsourcing

Spesifikasi: `modul/10-outsourcing.md`. SCO dari planned order subcon, dispatch material ke vendor, stok vendor, backflush saat terima hasil, QC (pass/reject ke vendor), return sisa.

Selesai bila: kriteria modul 10 lulus; tes end-to-end SCO → dispatch → terima → QC → saldo vendor dan internal konsisten, konservasi terbukti termasuk kasus kehilangan/reject di vendor.

## F11 — Laporan & dashboard

Spesifikasi: `modul/11-laporan.md`. Termasuk posisi stok vendor, QC, SCO terbuka.

Selesai bila: kriteria modul 11 lulus; angka laporan cocok dengan fixture yang dihitung manual.

## Setelah F11

Kandidat (butuh keputusan user, bukan otomatis): outsourcing per operasi (pecah WO di tengah routing), finite scheduling/CRP, konversi satuan, barcode, akuntansi, multi-mata uang, lot/serial tracking. Lihat daftar di luar cakupan `00-konteks.md`.
