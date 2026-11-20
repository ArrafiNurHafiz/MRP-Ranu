# 04 — Hak Akses per Divisi

Satu rumah kebijakan akses. Setiap aksi sistem dicek lewat permission, bukan lewat nama divisi yang tersebar di controller. User mewarisi permission dari divisi yang sudah ditetapkan admin.

## Model

- `divisions` = unit organisasi (planning, purchasing, production, warehouse, quality, sales, admin). User masuk tepat satu divisi (`users.division_id`).
- `permissions` = satu key per aksi (format `module.resource.action`), sumber key ada di enum PHP `App\Domain\Access\Enums\Permission`. Dilarang mendefinisikan key permission di tempat lain.
- `division_permissions` = pivot pemberian akses. **Tidak ada baris = tidak ada akses** (default deny). Admin mengatur centang per divisi lewat UI matriks; perubahan disimpan atomik dalam satu transaksi (hapus lama + tulis baru sesuai pilihan).
- Divisi `admin` lolos semua pengecekan lewat satu `Gate::before`. Ini satu-satunya bypass di sistem; divisi lain harus diberi akses eksplisit.

## Cara pakai

- Registrasi gate otomatis dari enum `Permission` di `AuthServiceProvider`, satu `Gate::define` per key.
- Controller/Livewire/aksi memanggil `Gate::allows(Permission::KUNCI->value)` atau middleware `can:...`. Dilarang `if ($user->division->code === ...)` di luar layer access control — alasan: kebijakan berubah lewat UI, bukan lewat kode.
- Penolakan: HTTP 403, body `{ "success": false, "code": "FORBIDDEN" }`.
- Menu/navigasi disembunyikan bila permission tidak ada; penyembunyian UI bukan pengganti pengecekan di server.

## Matriks default (seed — boleh diubah admin lewat UI)

Tabel ini menyatakan aksi yang **diizinkan** untuk divisi. Divisi `admin` = semua aksi. Seluruh aksi yang tidak tercantum = ditolak.

| Modul / aksi | planning | purchasing | production | warehouse | quality | sales |
|---|---|---|---|---|---|---|
| Master: lihat item/BOM/routing | lihat | lihat item | lihat | lihat item | — | lihat item |
| Master: ubah item | ya | — | — | — | — | — |
| Master: supplier / subcontractor | — | ya | — | — | — | — |
| Master: customer | — | — | — | — | — | ya |
| Master: gudang & divisi | — | — | — | — | — | — |
| BOM & routing: ubah | ya | — | routing saja | — | — | — |
| Inventory: lihat stok | ya | ya | ya | ya | ya | ya |
| Inventory: mutasi / adjustment / opname | — | — | — | ya | — | — |
| SO & forecast | — | — | — | — | — | ya |
| MPS & jalankan MRP / firm order | ya | — | — | — | — | — |
| PR & PO | — | ya | — | — | — | — |
| Posting terima pembelian | — | — | — | ya | — | — |
| WO / issue / completion | — | — | ya | issue & terima fisik | — | — |
| SCO / dispatch / terima maklon | — | — | — | ya | — | — |
| QC: inspeksi, release, reject | — | — | — | — | ya | — |
| QC: override hasil | — | — | — | — | — | — |
| Laporan: lihat & ekspor | ya | ya | ya | ya | ya | ya |

Catatan matriks:
- `warehouse` melakukan issue material dan menerima fisik WO, tetapi **tidak** membuat/mengubah WO (itu `production`).
- `quality` tidak mengubah jadwal atau dokumen order; hanya hasil QC. Override QC khusus admin.
- `planning` mengubah BOM dan item; routing diubah `production` karena menyangkut work center dan proses.

## Kapan mengubah kebijakan

- Default di atas hanya seed awal. Perubahan akses = aksi admin lewat UI matriks, tercatat di `division_permissions`.
- Menambah permission baru = tambah key di enum `Permission`, seeder, lalu berikan ke divisi yang relevan lewat UI; perubahan enum wajib disertai test yang menolak divisi lain.
- Akses tingkat baris (misal warehouse hanya lihat gudangnya sendiri) **di luar cakupan v1**. Jika dibutuhkan, tambahkan scope ke pivot divisi-gudang + filter query terpusat; jangan disisipkan per controller.

## Kriteria penerimaan (dikerjakan di F0)

- [x] Tujuh divisi ter-seed; user tanpa divisi ditolak login (`division_id` NOT NULL di DB; divisi nonaktif ditolak login).
- [x] Matriks default terpasang; feature test setiap kombinasi divisi × permission (Gate 200/403 ekuivalen) sesuai tabel. Tes 200/403 endpoint per modul dikerjakan di fase modul masing-masing.
- [x] UI admin mengubah akses satu divisi; perubahan langsung berlaku tanpa deploy, dan tidak menulis permission divisi lain (test).
- [x] Divisi non-admin tidak bisa membuka/menjalankan aksi yang tidak diberikan, termasuk lewat endpoint langsung (test).
- [x] `Gate::before` hanya untuk divisi `admin` (test).
