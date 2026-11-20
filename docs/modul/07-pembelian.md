# Modul 07 — Pembelian

Fase: F7. Entitas: `docs/02-model-data.md` bagian Pembelian. Prasyarat: modul 06 (QC).

## Tujuan

Mengubah planned order purchase menjadi PR → PO → penerimaan → QC → stok usable. Stok masuk hanya lewat `StockMovementService`.

## Aturan bisnis

1. Planned order `purchase` dapat di-release menjadi PR (satu PR boleh menggabungkan beberapa planned order item berbeda; satu planned order tidak boleh masuk dua PR). Release mengubah status planned order → `released`.
2. PR `approved` dapat dikonversi menjadi PO per supplier. Konversi menutup PR (`converted`) dan mencatat referensi balik.
3. PO `sent` ke supplier adalah komitmen: qty PO line menjadi scheduled receipt pada `expected_date` di run MRP berikutnya.
4. Penerimaan fisik: per PO line, `qty_received + qty_baru <= qty` — tolak `EXCESSIVE_RECEIPT`. Penerimaan menulis movement `po_receipt` (from = virtual supplier, to = gudang karantina gudang penerima, `unit_cost` = harga PO line) lewat service. Qty diterima fisik belum menambah usable stock.
5. QC (modul 06) atas receipt: pass → `qc_release` karantina → gudang penerima menambah usable stock; reject → `return_supplier`/`scrap` via movement QC. Qty diterima yang dihitung terhadap PO line = qty lulus QC. PO `partial` bila ada line yang accepted < qty; `received` bila semua line accepted penuh.
6. Pembatalan PO line yang sudah diterima sebagian tidak boleh membuat `qty_received > qty`; PO dengan penerimaan tidak boleh dibatalkan total — hanya sisa yang belum diterima yang boleh di-close.
7. Nomor PR/PO digenerate sistem, unik, berurutan per tahun (mis. `PO-2026-0001`); tidak boleh diisi manual.

## Alur utama

Planned order → release PR → approve PR → convert PO (pilih supplier, harga, expected date) → send → terima fisik ke karantina → QC pass/reject → pass lepas ke gudang usable / reject return supplier atau scrap → status PO otomatis.

## Kriteria penerimaan

- [ ] End-to-end: planned order → PR → PO → terima → QC pass → saldo usable bertambah sebesar qty pass (test).
- [ ] Terima melebihi qty line ditolak `EXCESSIVE_RECEIPT`, tidak ada tulisan parsial (test).
- [ ] Receipt tanpa QC tidak menambah usable stock dan tidak bisa dialokasikan (test).
- [ ] QC reject → return_supplier/scrap; PO accepted hanya bertambah untuk pass (test).
- [ ] Terima parsial → status `partial`; penuh → `received` (test).
- [ ] Planned order yang sudah masuk PR tidak bisa masuk PR kedua (test).
- [ ] PO sent muncul sebagai scheduled receipt di MRP run berikutnya (test integrasi).
- [ ] Divisi `purchasing`, `warehouse`, `quality` sesuai matriks `docs/04-hak-akses.md`; divisi lain ditolak (test).
