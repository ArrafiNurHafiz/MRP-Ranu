# Modul 03 — Inventory

Fase: F3. Entitas: `docs/02-model-data.md` bagian Inventory.

## Tujuan

Ledger stok double-entry yang konservatif: stok tidak muncul atau hilang tanpa movement tercatat. Berlaku untuk gudang internal maupun gudang vendor (stok kita di pihak maklon). `StockMovementService` adalah satu-satunya penulis `stock_movements` dan `stock_balances` (AGENTS.md aturan #3).

## Invariant (wajib diuji)

1. **Konservasi:** `saldo_akhir = saldo_awal + Σ masuk − Σ keluar` per item-gudang, termasuk gudang vendor. Rekonsiliasi ulang dari movement harus selalu cocok dengan `stock_balances`.
2. **Double-entry:** setiap movement punya sisi keluar dan/atau masuk; transfer antar gudang mengurangi gudang asal DAN menambah gudang tujuan dalam satu transaksi. Tidak ada movement satu sisi yang menciptakan stok dari udara kecuali type virtual (`po_receipt`, `wo_completion`, `subcon_receipt`, `adjustment`, `opname`, `scrap`) yang memang mendefinisikan sumber/tujuan virtual.
3. **Non-negatif:** `qty_on_hand` tidak pernah < 0, di gudang mana pun. Mutasi keluar melebihi saldo ditolak sebelum tulis apa pun.
4. **Atomicity + locking:** baca saldo dengan `lockForUpdate()` dalam `DB::transaction`, validasi, tulis movement + saldo, commit. Dilarang baca saldo → lepas lock → tulis. Bila mutasi menyentuh dua gudang, lock keduanya berurutan deterministik (urut id) untuk hindari deadlock.
5. **Append-only:** movement tidak diedit/dihapus. Koreksi = movement baru type `adjustment` dengan referensi dokumen koreksi.
6. **Gudang vendor terkunci dokumen:** transfer yang melibatkan gudang `type=vendor` hanya boleh dengan referensi dokumen SCO (dispatch/return). Transfer manual ke/dari gudang vendor ditolak `INVALID_MOVEMENT`. Alasan: stok vendor harus selalu bisa ditelusuri ke order maklon.
7. **Karantina terkunci QC:** stok di gudang `is_quarantine=true` tidak bisa dialokasikan/diissue. Movement keluar dari karantina hanya lewat `qc_release`/`qc_reject`/`purchase_return` dengan referensi QC check berstatus selesai; tanpa itu tolak `QC_REQUIRED`. Alasan: barang belum lulus QC tidak boleh dipakai.

## API service

```php
StockMovementService::move(
    item: Item,
    type: MovementType,
    qty: string,               // decimal, > 0
    from: ?Warehouse,          // null = sisi virtual (supplier/produksi/penyesuaian)
    to: ?Warehouse,            // null = sisi virtual (customer/produksi/penyesuaian)
    reference: Model,          // dokumen sumber
    actor: User,
    unitCost: ?int = null,
    note: ?string = null,
): StockMovement               // throws StockException berisi code
```

Service memvalidasi aturan 3–4 dan 6, menolak `qty <= 0` (`NEGATIVE_QUANTITY`), dan menulis saldo kedua sisi. Alokasi (`qty_allocated`) dikelola service yang sama lewat method `allocate`/`releaseAllocation` dengan invariant `allocated <= on_hand`.

## Kode error (satu rumah — dirujuk modul lain)

| Code | HTTP | Kondisi |
|---|---|---|
| `INSUFFICIENT_STOCK` | 400 | Saldo sumber < qty diminta |
| `NEGATIVE_QUANTITY` | 400 | Qty <= 0 |
| `EXCESSIVE_RECEIPT` | 400 | Terima PO/SCO melebihi qty dokumen |
| `EXCESSIVE_COMPLETION` | 400 | Completion WO melebihi qty WO |
| `EXCESSIVE_ISSUE` | 400 | Issue material melebihi kebutuhan WO tanpa override |
| `EXCESSIVE_DISPATCH` | 400 | Dispatch maklon melebihi kebutuhan SCO tanpa override |
| `EXCESSIVE_RETURN` | 400 | Return maklon melebihi sisa tercatat di vendor |
| `SHORT_VENDOR_STOCK` | 400 | Backflush melebihi saldo vendor; penerimaan ditolak utuh |
| `PRODUCT_NOT_FOUND` | 404 | Item tidak ada |
| `WAREHOUSE_NOT_FOUND` | 404 | Gudang tidak ada |
| `INVALID_MOVEMENT` | 400 | Kombinasi from/to/type tidak valid (mis. transfer tanpa dua gudang, transfer manual ke gudang vendor) |
| `QC_REQUIRED` | 400 | Movement keluar dari karantina tanpa QC check selesai |
| `INVALID_DISPOSITION` | 400 | Disposition reject tidak cocok dengan sumber (return_supplier untuk PO, return_vendor untuk SCO, rework untuk WO) |
| `EXCESSIVE_QC_QUANTITY` | 400 | qty_pass + qty_reject ≠ qty_received |
| `LOCK_TIMEOUT` | 503 | Lock tidak didapat dalam batas |

## Alur utama

- **Transfer antar gudang internal:** pilih item, gudang asal/tujuan, qty → `move(type: transfer)`.
- **Adjustment:** alasan wajib → `move(type: adjustment)` satu sisi.
- **Opname:** buat draft (bekukan `system_qty` per item) → input hitungan fisik → posting menghasilkan satu movement `opname` per item berselisih; selisih 0 tidak menghasilkan movement. Opname gudang vendor = hasil hitung fisik bersama vendor.
- **Kartu stok:** riwayat movement per item-gudang (termasuk vendor), filter type/tanggal, saldo berjalan.

## Kriteria penerimaan

- [ ] Transfer mengurangi asal dan menambah tujuan atomik; gagal di sisi mana pun membatalkan keduanya (test).
- [ ] Keluar melebihi saldo ditolak `INSUFFICIENT_STOCK`, tidak ada tulisan parsial (test).
- [ ] Tes konkurensi: N proses paralel mengeluarkan stok yang sama; total keluar ≤ saldo awal, tidak ada saldo negatif.
- [ ] Rekonsiliasi: fixture 100 movement acak (internal + vendor) → Σ movement = saldo tiap item-gudang (test).
- [ ] Opname posting hanya membuat movement untuk selisih ≠ 0 (test).
- [ ] Transfer manual ke gudang vendor ditolak `INVALID_MOVEMENT` (test).
- [ ] Movement keluar dari karantina tanpa QC check selesai ditolak `QC_REQUIRED` (test).
- [ ] Stok karantina tidak bisa dialokasikan/diissue (test).
- [ ] Movement tidak bisa diedit/dihapus lewat endpoint mana pun (test).
