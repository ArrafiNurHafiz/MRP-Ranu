# Modul 10 — Outsourcing (Maklon)

Fase: F10. Entitas: `docs/02-model-data.md` bagian Outsourcing. Prasyarat: modul 03 (ledger + gudang vendor), 02 (BOM + supply mode), 05 (planned order subcon), 06 (QC).

## Tujuan

Maklon penuh: kirim material ke vendor, lacak stok yang berada di vendor, terima hasil dengan backflush komponen, QC pass/reject, kembalikan sisa material. Semua pergerakan lewat ledger yang sama — stok di vendor bukan catatan terpisah.

## Model stok vendor

Setiap subcontractor punya satu gudang virtual `type=vendor` (dibuat otomatis). Stok kita di vendor = `stock_balances` pada gudang itu. Konservasi berlaku global: material tidak hilang saat pindah ke vendor, hanya berpindah node ledger.

## Aturan bisnis

1. Planned order `subcon` di-release menjadi SCO. Snapshot `sco_materials` dari BOM aktif saat rilis, termasuk `supply_mode` per komponen (boleh override per komponen saat rilis: stok sendiri ↔ vendor sediakan, dengan alasan tercatat).
2. Komponen `supply_mode=stock`: kita kirim (dispatch). Komponen `supply_mode=vendor`: vendor sediakan dari materialnya sendiri — tidak ada movement di ledger kita, biaya sudah dalam `service_price`. Komponen vendor tidak memicu kebutuhan MRP (modul 05).
3. **Dispatch:** movement `transfer` gudang internal → gudang vendor, referensi dokumen dispatch. Saldo internal kurang → `INSUFFICIENT_STOCK`. `qty_dispatched + qty_baru <= qty_required` kecuali override beralasan (tolak default `EXCESSIVE_DISPATCH`). Transfer manual ke gudang vendor tanpa dokumen SCO ditolak `INVALID_MOVEMENT`.
4. SCO `sent` = komitmen: qty SCO menjadi scheduled receipt pada `due_date` di MRP run berikutnya.
5. **Penerimaan hasil (atomik dalam satu transaksi):**
   - Movement `subcon_receipt`: sisi virtual vendor → **gudang karantina** internal tujuan, sebesar qty hasil fisik.
   - Backflush: per komponen `stock`, movement `subcon_backflush` keluar dari gudang vendor sebesar `qty_receipt × qty_per × (1 + scrap_pct/100)`; `qty_consumed` bertambah. Backflush terjadi untuk seluruh qty fisik, sebelum QC selesai.
   - Backflush melebihi saldo vendor → tolak seluruh penerimaan `SHORT_VENDOR_STOCK`. Penanganan: dispatch tambahan, atau adjustment kehilangan di vendor (type `scrap`/`adjustment` referensi SCO, alasan wajib) lalu terima ulang.
   - Total receipt fisik `<= sco.qty` (tolak `EXCESSIVE_RECEIPT`).
6. **QC (modul 06):** pass → `qc_release` karantina → gudang internal, qty dihitung sebagai accepted SCO. Reject → movement `transfer` karantina → gudang vendor (disposition `return_vendor`), qty accepted tidak bertambah, vendor wajib mengirim ulang dalam SCO yang sama. Komponen sudah ter-backflush saat receipt fisik — vendor menanggung penggantian materialnya. Receipt parsial accepted → `partial`; accepted penuh → `received`.
7. **Return sisa material:** movement `transfer` gudang vendor → internal, referensi SCO. `qty_returned + qty_baru <= qty_dispatched − qty_consumed` dan saldo vendor cukup (tolak `EXCESSIVE_RETURN`). Return tanpa sisa tercatat ditolak.
8. Kehilangan/kerusakan di vendor = adjustment keluar dari gudang vendor dengan alasan wajib dan referensi SCO — bukan edit movement lama, bukan pengurangan diam-diam.
9. SCO cancelled: material yang sudah di-dispatch wajib di-return dulu (saldo vendor untuk SCO tersebut harus nol) sebelum pembatalan diterima.
10. Nomor SCO digenerate sistem (`SCO-2026-0001`), unik.

## Alur utama

Planned order subcon → release SCO (snapshot BOM + supply mode) → dispatch komponen stok ke vendor → (proses vendor) → terima hasil ke karantina + backflush → QC pass/reject ke gudang/ke vendor → return sisa material → close. Pantau stok vendor per item kapan pun.

## Kriteria penerimaan

- [ ] End-to-end: SCO → dispatch → terima → QC pass → saldo internal berkurang tepat Σ dispatch, saldo vendor = dispatch − backflush − return, saldo jadi bertambah tepat Σ qty pass (test konservasi).
- [ ] Komponen `supply_mode=vendor` tidak menghasilkan movement dan tidak memicu planned order komponen (test).
- [ ] Backflush melebihi saldo vendor menolak seluruh penerimaan, tidak ada tulisan parsial (test `SHORT_VENDOR_STOCK`).
- [ ] Kehilangan di vendor lewat adjustment beralasan membuat backflush berikutnya succeeds (test).
- [ ] QC reject return ke gudang vendor; accepted SCO tidak bertambah; vendor kirim ulang (test).
- [ ] Return melebihi sisa tercatat ditolak `EXCESSIVE_RETURN` (test).
- [ ] Transfer manual ke gudang vendor ditolak `INVALID_MOVEMENT` (test).
- [ ] SCO cancelled dengan saldo vendor tersisa ditolak (test).
- [ ] SCO sent muncul sebagai scheduled receipt di MRP run berikutnya (test integrasi).
- [ ] Override dispatch melebihi kebutuhan dan override supply mode tercatat alasannya (test).
