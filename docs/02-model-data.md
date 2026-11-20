# 02 — Model Data

Sumber kebenaran desain data. Setiap perubahan skema wajib memperbarui file ini pada commit yang sama (AGENTS.md aturan #2). Field di bawah adalah desain awal; migration boleh menambah detail teknis (index, FK) tanpa mengubah makna.

Konvensi umum: PK `id` bigint auto; `created_at`/`updated_at` semua tabel; master data soft deletes; kuantitas `decimal(15,3)`; uang integer rupiah.

## Master data

### items
`code` (unik), `name`, `type` enum(`raw`,`wip`,`finished`), `source_type` enum(`make`,`buy`,`subcon`) — default `raw`→`buy`, `wip`/`finished`→`make`; menentukan tipe planned order. `unit` (string, satu satuan per item — konversi satuan di luar cakupan v1), `category_id`, `is_active`, `lead_time_days` int (untuk `subcon`: lama proses vendor + transportasi), `safety_stock`, `lot_policy` enum(`lfl`,`foq`), `fixed_order_qty` (wajib bila `foq`), `min_order_qty`, `order_multiple`, `low_level_code` int (dihitung sistem, bukan input user).

### item_categories
`name`, `parent_id` nullable (hierarki opsional).

### warehouses
`code` (unik), `name`, `type` enum(`internal`,`vendor`), `subcontractor_id` nullable (wajib bila `vendor`, unik per vendor), `is_quarantine` bool (default false; gudang karantina berpasangan dengan gudang internal, auto-created), `is_active`. Gudang `vendor` dibuat otomatis saat vendor dibuat; tidak bisa dibuat manual. Gudang karantina tidak bisa dihapus/diedit manual.

### suppliers
`code` (unik), `name`, `contact`, `lead_time_days` default untuk item yang dipasok, `is_active`.

### subcontractors
`code` (unik), `name`, `contact`, `lead_time_days`, `is_active`. Pembuatan vendor otomatis membuat gudang virtual `type=vendor`.

### customers
`code` (unik), `name`, `contact`, `is_active`.

### users
Bawaan Laravel + `division_id` FK ke `divisions` (wajib). Hak akses mengikuti divisi, bukan kolom role statis. Kebijakan di `docs/04-hak-akses.md`.

### divisions
`code` (unik), `name`, `is_active`. Seed: admin, planning, purchasing, production, warehouse, quality, sales.

### permissions
`key` (unik, format `module.resource.action`), `module`, `description`. Sumber key = enum PHP `App\Domain\Access\Enums\Permission`; tabel ini diisi seeder dari enum.

### division_permissions
`division_id`, `permission_id`. Unik (`division_id`,`permission_id`). Tidak ada baris = tidak ada akses. Divisi `admin` bypass lewat satu `Gate::before`.

## BOM

### boms
`parent_item_id`, `version` int, `status` enum(`draft`,`active`,`archived`), `notes`.
**Invariant:** tepat satu BOM `active` per `parent_item_id`. Aktivasi versi baru mengarsipkan yang lama dalam satu transaksi.

### bom_lines
`bom_id`, `component_item_id`, `qty_per` (> 0), `scrap_pct` `decimal(5,2)` default 0, `supply_mode` enum(`stock`,`vendor`) default `stock`, `is_phantom` bool, `position`.
**Invariant:** graf BOM tanpa siklus — diverifikasi saat aktivasi; aktivasi gagal bila siklus terdeteksi. Komponen tidak boleh sama dengan induk. `supply_mode=vendor` hanya bermakna bila induk di-maklon; untuk produksi internal semua komponen dari stok.

## Routing

### work_centers
`code` (unik), `name`, `capacity_minutes_per_day` int nullable (informatif untuk laporan beban; finite scheduling di luar cakupan v1), `is_active`.

### routings
`item_id`, `version` int, `status` enum(`draft`,`active`,`archived`).
**Invariant:** tepat satu routing `active` per item; pola aktivasi sama dengan BOM. Item `source_type=make` wajib punya routing aktif saat WO dirilis; item `subcon` boleh tanpa routing (proses dikelola vendor).

### routing_operations
`routing_id`, `sequence` int (unik per routing), `name`, `operation_type` enum(`internal`,`subcon`), `work_center_id` nullable (wajib bila `internal`), `subcontractor_id` nullable (wajib bila `subcon`), `setup_minutes` int >= 0, `run_minutes_per_unit` `decimal(10,3)` >= 0.
Catatan: `operation_type=subcon` tercatat untuk referensi; eksekusi maklon per operasi di luar cakupan v1 (maklon level order utuh, lihat `modul/10-outsourcing.md`).

## Inventory

### stock_balances
`item_id`, `warehouse_id`, `qty_on_hand`, `qty_allocated`. Unik (`item_id`,`warehouse_id`). Berlaku untuk gudang internal maupun vendor.
**Invariant:**
- `qty_on_hand >= 0` selalu; mutasi yang membuatnya negatif ditolak `INSUFFICIENT_STOCK`.
- `qty_allocated <= qty_on_hand`.
- Saldo setiap saat = jumlah tertanda seluruh `stock_movements` item-gudang tersebut (bisa direkonsiliasi ulang).
- Kolom ini hanya ditulis `StockMovementService` (AGENTS.md aturan #3).
- Stok di gudang karantina (`is_quarantine=true`) tidak boleh dialokasikan atau diissue; keluar hanya lewat movement QC.

### stock_movements
`item_id`, `type` enum(`po_receipt`,`wo_issue`,`wo_completion`,`transfer`,`adjustment`,`opname`,`scrap`,`subcon_receipt`,`subcon_backflush`,`qc_release`,`qc_reject`,`purchase_return`), `from_warehouse_id` nullable, `to_warehouse_id` nullable, `qty` (> 0), `unit_cost` nullable, `reference_type`/`reference_id` (polymorphic ke dokumen sumber), `created_by`, `note`.
**Invariant:** double-entry — tepat satu sisi gudang keluar dan/atau satu sisi masuk; sisi virtual (supplier, customer, produksi, penyesuaian) diwakili sisi NULL + `type`. Transfer yang melibatkan gudang `vendor` hanya boleh dengan referensi dokumen SCO (dispatch/return) — dilarang transfer manual ke/dari gudang vendor. Append-only; koreksi lewat movement baru, bukan edit.

### stock_opnames
`warehouse_id`, `opname_date`, `status` enum(`draft`,`posted`), `created_by`. Opname gudang vendor dilakukan bersama vendor (hasil hitung fisik vendor).
### stock_opname_lines
`opname_id`, `item_id`, `system_qty` (beku saat draft dibuat), `counted_qty`. Posting menghasilkan movement `opname` selisih per item.

## Quality Control

### qc_checks
`number` (unik), `source_type` enum(`po_receipt`,`subcon_receipt`,`production_completion`), `source_id` (receipt line / completion id), `item_id`, `qty_received`, `qty_inspected`, `qty_pass`, `qty_reject`, `method` enum(`full`,`sampling`), `status` enum(`pending`,`passed`,`rejected`,`partial`), `inspector_id`, `checked_at`, `note`.
**Invariant:** `qty_pass + qty_reject = qty_received`; tepat satu QC check per sumber; status `passed`/`rejected` immutable setelah movement pelepasan diposting.

### qc_reject_lines
`qc_check_id`, `qty`, `disposition` enum(`return_supplier`,`return_vendor`,`rework`,`scrap`), `movement_id` (referensi movement pelepasan), `reason`.
**Invariant:** `return_supplier` hanya untuk `source_type=po_receipt`; `return_vendor` hanya untuk `subcon_receipt`; `rework` hanya untuk `production_completion`. Σ qty reject lines = `qty_reject`.

## Permintaan & MPS

### sales_orders
`customer_id`, `order_date`, `status` enum(`draft`,`confirmed`,`fulfilled`,`cancelled`).
### sales_order_lines
`sales_order_id`, `item_id` (harus `finished`), `qty`, `due_date`, `qty_fulfilled`.
**Invariant:** `qty_fulfilled <= qty`; konfirmasi SO mengalokasikan stok bila tersedia.

### forecasts
`item_id` (`finished`), `period_date`, `qty`, `source` string. Unik (`item_id`,`period_date`).

### mps_entries
`item_id` (`finished`), `period_date`, `qty`, `source` enum(`manual`,`forecast`,`so`), `status` enum(`planned`,`firmed`). Unik (`item_id`,`period_date`,`source`).
MPS adalah satu-satunya sumber permintaan independen yang diledakkan MRP (lihat `modul/05-mrp.md`).

## MRP

### mrp_runs
`run_at`, `horizon_days`, `status` enum(`running`,`completed`,`failed`), `params` JSON (snapshot parameter), `created_by`. Immutable setelah selesai.

### mrp_records
`run_id`, `item_id`, `period_date`, `gross_requirement`, `scheduled_receipts`, `projected_on_hand`, `net_requirement`, `planned_order_receipt`, `planned_order_release`. Unik (`run_id`,`item_id`,`period_date`).

### planned_orders
`run_id` nullable (null bila hasil firm manual), `item_id`, `type` enum(`purchase`,`make`,`subcon`) — dari `items.source_type`, `qty`, `release_date`, `due_date`, `status` enum(`planned`,`firmed`,`released`,`closed`,`cancelled`), `parent_order_id` nullable (pegging).
**Invariant:** planned order `firmed` diperlakukan sebagai scheduled receipt pada run berikutnya; run baru tidak boleh menduplikasi kuantitas yang sudah di-firm untuk item+periode yang sama.

## Pembelian

### purchase_requisitions
`number` (unik), `status` enum(`draft`,`approved`,`converted`,`cancelled`), `created_by`.
### purchase_requisition_lines
`pr_id`, `item_id`, `qty`, `needed_date`, `planned_order_id` nullable.
### purchase_orders
`number` (unik), `supplier_id`, `order_date`, `status` enum(`draft`,`approved`,`sent`,`partial`,`received`,`closed`,`cancelled`), `created_by`.
### purchase_order_lines
`po_id`, `item_id`, `qty`, `unit_price` integer rupiah, `expected_date`, `qty_received`.
**Invariant:** `qty_received <= qty` (tolak `EXCESSIVE_RECEIPT`); penerimaan hanya lewat `StockMovementService` type `po_receipt`.
### purchase_receipts
`po_id`, `receipt_date`, `warehouse_id`, `created_by`.
### purchase_receipt_lines
`receipt_id`, `po_line_id`, `qty`.

## Produksi

### work_orders
`number` (unik), `item_id` (`source_type=make`), `qty`, `bom_id` (BOM aktif saat rilis), `routing_id` (routing aktif saat rilis), `status` enum(`planned`,`released`,`in_progress`,`completed`,`closed`,`cancelled`), `start_date`, `due_date`, `planned_order_id` nullable.
### wo_materials
Snapshot komponen saat rilis: `wo_id`, `component_item_id`, `qty_required` (sudah termasuk scrap), `qty_issued`. Semua komponen WO internal dari stok (`supply_mode=stock`).
**Invariant:** `qty_issued <= qty_required` kecuali override eksplisit user dengan alasan tercatat; issue hanya lewat `StockMovementService` type `wo_issue`.
### wo_operations
Snapshot routing saat rilis: `wo_id`, `sequence`, `name`, `work_center_id`, `setup_minutes`, `run_minutes_total` (= `run_minutes_per_unit × qty_wo`), `status` enum(`pending`,`done`), `completed_at` nullable.
### production_completions
`wo_id`, `warehouse_id`, `qty`, `completed_at`. Movement type `wo_completion` ke karantina; qty yang dihitung mencapai target WO = qty lulus QC.
**Invariant:** total completion lulus QC `<= wo.qty` (tolak `EXCESSIVE_COMPLETION`). Completion yang di-reject tidak dihitung; WO perlu completion tambahan.

## Outsourcing (maklon)

### subcontract_orders
`number` (unik, `SCO-2026-0001`), `subcontractor_id`, `item_id` (hasil, `wip`/`finished`), `qty`, `service_price` integer rupiah per unit (jasa maklon; material `supply_mode=vendor` sudah termasuk harga jasa), `order_date`, `start_date`, `due_date`, `status` enum(`draft`,`sent`,`partial`,`received`,`closed`,`cancelled`), `bom_id` (snapshot saat rilis), `planned_order_id` nullable, `created_by`.
### sco_materials
Snapshot komponen saat rilis: `sco_id`, `component_item_id`, `qty_required` (termasuk scrap), `supply_mode` enum(`stock`,`vendor`), `qty_dispatched`, `qty_consumed` (backflush), `qty_returned`.
**Invariant:**
- Komponen `stock`: `qty_dispatched <= qty_required` (kecuali override beralasan); `qty_consumed + qty_returned <= qty_dispatched`.
- Komponen `vendor`: tidak ada dispatch/consumption di ledger kita (material milik vendor).
### subcontract_dispatches
`sco_id`, `warehouse_id` (gudang internal asal), `dispatch_date`, `created_by`.
### subcontract_dispatch_lines
`dispatch_id`, `sco_material_id`, `qty`. Movement: `transfer` internal → gudang vendor, referensi dispatch.
### subcontract_receipts
`sco_id`, `warehouse_id` (gudang internal tujuan), `receipt_date`, `created_by`.
### subcontract_receipt_lines
`receipt_id`, `qty`. Saat receipt diposting, atomik: movement `subcon_receipt` (vendor virtual → gudang tujuan) sebesar qty hasil + movement `subcon_backflush` (keluar dari gudang vendor) per komponen `stock` sebesar `qty_receipt × qty_per × (1 + scrap_pct/100)`.
**Invariant:** total receipt lulus QC `<= sco.qty` (tolak `EXCESSIVE_RECEIPT`); backflush tidak boleh membuat saldo vendor negatif (tolak `SHORT_VENDOR_STOCK` — dispatch tambahan atau adjustment kehilangan dulu). Receipt yang di-reject mengurangi sisa yang harus dikirim vendor, bukan menambah saldo usable.

## Reporting

Tanpa tabel baru di v1 — laporan adalah query atas tabel di atas. Bila later butuh agregat materialized, tambah di sini dulu.
