# 01 — Arsitektur & Konvensi Kode

Baca sebelum menulis file PHP pertama. Spesifikasi bisnis per modul ada di `docs/modul/`; file ini hanya keputusan teknis.

## Stack

- PHP 8.3+, Laravel 12, MySQL 8, Livewire 4, Tailwind CSS.
- Tes: Pest. Frontend tanpa build step JS berat; Alpine bawaan Livewire cukup.
- Tanpa paket tambahan tanpa persetujuan user (AGENTS.md aturan #4).

## Struktur modul

Kode dikelompokkan per domain, bukan per tipe teknis:

```
app/
  Domain/
    MasterData/    Models/, Services/, Enums/
    Access/        Permission enum, division gate
    Bom/
    Inventory/     StockMovementService.php  ← satu-satunya penulis stok
    QualityControl/
    Demand/        (sales order, forecast, MPS)
    Mrp/           MrpEngine.php, MrpRunner.php
    Purchasing/
    Routing/
    Production/
    Subcontracting/
    Reporting/
  Http/Controllers/  (atau Livewire components di app/Livewire/{Modul}/)
```

Batas modul:

- Modul lain memanggil service publik modul pemilik; dilarang mengubah model modul lain langsung. Alasan: satu pemilik per invariant.
- `MrpEngine` murni: terima DTO input, keluarkan DTO hasil, tanpa query DB di dalamnya. Persistensi dilakukan `MrpRunner`. Alasan: mesin hitung wajib unit-testable tanpa database (AGENTS.md aturan #6).

## Konvensi kode

- Identifier English; UI string Indonesia lewat file lang, bukan hardcode di view.
- Status dan tipe = PHP backed enum (`enum ItemType: string`), bukan string bebas atau konstanta class.
- Uang: integer rupiah. Kuantitas: `decimal(15,3)` di DB, string/Decimal di PHP — dilarang float.
- Tanggal: Carbon, timezone aplikasi `Asia/Jakarta`. Bucket MRP = tanggal kalender (date, bukan datetime).
- Multi-tulis (mutasi stok, terima PO, selesai WO) wajib dalam `DB::transaction`; baca saldo pakai `lockForUpdate()` sebelum tulis. Alasan: race condition multi-user.
- Master data pakai soft deletes; transaksi (movement, PO, WO, run) tidak pernah dihapus — pembatalan lewat status.
- Validasi di FormRequest atau service boundary; controller/Livewire tidak berisi logika bisnis.
- Pengecekan akses lewat Gate permission (`Permission` enum), bukan nama divisi di controller. Kebijakan satu rumah di `docs/04-hak-akses.md`.

## Format error API/aksi

Kegagalan bisnis mengembalikan kode mesin, bukan pesan bebas. Daftar kode lengkap satu rumah di `docs/modul/03-inventory.md`. Bentuk:

```json
{ "success": false, "code": "INSUFFICIENT_STOCK", "detail": { "available": 5, "requested": 10 } }
```

HTTP: 400 kesalahan bisnis, 404 tidak ditemukan, 403 akses kurang (`FORBIDDEN`), 503 lock timeout.

## Strategi tes

- Unit: `MrpEngine` (net requirement, lot sizing, offset lead time, ledakan BOM, phantom, safety stock) — 100% branch coverage.
- Unit: `StockMovementService` (double-entry, tolak saldo kurang, konkurensi).
- Feature: setiap endpoint/aksi Livewire per kriteria penerimaan di spesifikasi modul.
- Fixture MRP berupa dataset kecil deterministik di `tests/Fixtures/mrp/`; dilarang assert berbasis tanggal "hari ini" — pakai tanggal beku (`Carbon::setTestNow`).

## Git

- Commit message English, imperatif: `add stock movement service`, `fix mrp net requirement rounding`.
- Satu commit = satu perubahan logis; migration + pembaruan docs skema selalu satu commit (AGENTS.md aturan #2).
- Jangan commit file `.env`, credential, atau dump database.
