# Modul 05 — Mesin MRP

Fase: F5. Entitas: `docs/02-model-data.md` bagian MRP.

## Tujuan

Menghitung kebutuhan material time-phased dari MPS melalui ledakan BOM multi-level, menghasilkan planned order beli/buat/maklon beserta pegging dan exception message. Mesin deterministik: input sama → output sama.

## Arsitektur

- `MrpEngine::run(MrpInput): MrpOutput` — murni, tanpa DB. Input: daftar item + parameter (termasuk `source_type`), BOM aktif + supply mode, saldo on-hand, scheduled receipts (PO/WO/SCO terbuka + planned order firmed), MPS, horizon, tanggal mulai. Output: records per item-periode + planned orders + pegging + exceptions.
- `MrpRunner` — mengumpulkan input dari DB, memanggil engine, menyimpan `mrp_runs`/`mrp_records`/`planned_orders` dalam satu transaksi. Run immutable setelah selesai.

## Algoritma (urutan wajib)

1. **Kumpulkan permintaan independen:** MPS (planned + firmed) per item `finished` per tanggal.
2. **Urutkan item berdasarkan `low_level_code` menaik.** Item diproses tuntas seluruh horizon sebelum item level berikutnya. Alasan: kebutuhan dependen induk harus sudah final sebelum komponen dihitung.
3. **Per item, per tanggal (bucket harian), hitung berurutan:**
   - `gross_requirement` = permintaan independen (bila finished) + Σ kebutuhan dependen dari planned release induk.
   - `scheduled_receipts` = PO terbuka (sisa qty, `expected_date`) + WO berjalan (sisa qty, `due_date`) + SCO sent (sisa qty, `due_date`) + planned order `firmed` run sebelumnya.
   - `projected_on_hand` = projected periode sebelumnya + scheduled receipts + planned order receipt − gross requirement. Periode pertama mulai dari on-hand saat ini. Stok di gudang vendor TIDAK dihitung sebagai on-hand tersedia (material sudah terikat SCO).
   - `net_requirement` = `max(0, gross_requirement + safety_stock − projected_sebelum_penerimaan_baru)`. Safety stock diperhitungkan sekali di awal horizon, bukan diulang tiap periode.
   - Bila net > 0 → planned order receipt sebesar hasil lot sizing (aturan di bawah), tanggal = periode net.
4. **Lot sizing:**
   - `lfl`: qty = net requirement.
   - `foq`: qty = `fixed_order_qty`, dibulatkan naik ke kelipatan `order_multiple` bila diisi, dan tidak kurang dari `min_order_qty`. Satu order FOQ per periode net; sisa lebih menjadi projected on-hand periode berikutnya (bukan order tambahan).
5. **Offset lead time:** `release_date = due_date − lead_time_days` (hari kalender). Planned order release dicatat pada tanggal itu. Untuk item `subcon`, `lead_time_days` item = lama proses vendor + transportasi.
6. **Ledakan BOM:** setiap planned order release item induk menimbulkan gross requirement komponen pada **tanggal release induk** (komponen dibutuhkan saat produksi/maklon mulai): `qty_komponen = qty_order × qty_per × (1 + scrap_pct/100)`, dibulatkan 3 desimal. **Komponen `supply_mode=vendor` diabaikan** — vendor menyediakan dari materialnya sendiri, bukan permintaan bagi kita.
7. **Phantom:** tidak menghasilkan planned order; kebutuhannya langsung diteruskan ke komponen anak phantom pada tanggal yang sama, rekursif. Supply mode anak phantom tetap dihormati.
8. **Pegging:** setiap planned order mencatat `parent_order_id` ke order induk yang memicunya; level teratas menunjuk MPS entry.
9. **Exception message** per planned order: `LATE_RELEASE` (release_date < hari ini), `NO_BOM` (item make/subcon tanpa BOM aktif → run gagal untuk item itu, tercatat, bukan diam-diam nol), `BEYOND_HORIZON`.

## Aturan bisnis

1. Run baru = regenerasi penuh. Planned order `planned` (tidak firmed) dari run lama ditandai usang; yang `firmed` dibawa sebagai scheduled receipt dan tidak diduplikasi (invariant `02-model-data.md`).
2. Tipe planned order = `items.source_type`: `buy`→`purchase`, `make`→`make`, `subcon`→`subcon`. Item make/subcon tanpa BOM aktif = exception `NO_BOM`, bukan diabaikan.
3. Horizon default 90 hari, dapat diubah per run; kebutuhan di luar horizon tetap dihitung untuk projected tapi tidak menghasilkan planned order di luar horizon (tandai exception `BEYOND_HORIZON`).
4. Semua tanggal bucket = date kalender `Asia/Jakarta`; tes memakai `Carbon::setTestNow`.

## Kriteria penerimaan

- [ ] Fixture LFL: net = gross − stok, order tepat sebesar kekurangan (test).
- [ ] Fixture FOQ + min + multiple: pembulatan benar, sisa jadi stok proyeksi (test).
- [ ] Fixture 3 tingkat: komponen level 2 menerima gross dari release level 1, urutan low-level code terbukti (test).
- [ ] Phantom ditembus tanpa planned order (test).
- [ ] Scrap menaikkan kebutuhan komponen, bukan qty order induk (test).
- [ ] Lead time offset: due 20, lead 5 → release 15 (test).
- [ ] Safety stock memicu order meski gross 0 bila projected < safety (test).
- [ ] Planned order firmed run lama muncul sebagai scheduled receipt, tidak duplikat (test).
- [ ] Item make tanpa BOM → exception NO_BOM (test).
- [ ] Item `source_type=subcon` → planned order `subcon`; komponennya tetap diledakkan (test).
- [ ] Komponen `supply_mode=vendor` tidak menghasilkan gross requirement maupun planned order (test).
- [ ] Stok di gudang vendor tidak mengurangi net requirement (test).
- [ ] 100% branch coverage `MrpEngine`.
