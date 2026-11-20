# 00 — Konteks Proyek

## Apa yang dibangun

Sistem informasi MRP (Material Requirements Planning) untuk **Ranu**, perusahaan manufaktur diskrit: produk dirakit dari komponen melalui BOM bertingkat. Sistem menghitung kebutuhan material dari jadwal produksi, lalu mendorong pembelian, produksi internal, dan outsourcing (maklon) ke vendor.

Proyek jangka panjang. Dibangun bertahap sesuai `03-roadmap.md`; jangan mendahului fase tanpa keputusan user.

## Keputusan terkunci

| Keputusan | Nilai | Alasan |
|---|---|---|
| Stack | Laravel 12 + MySQL 8 + Livewire 4 | CRUD-heavy ERP, ekosistem matang |
| Jenis manufaktur | Diskrit/rakitan, BOM multi-level | Jawaban user |
| Cakupan | Penuh: master data → MRP → pembelian → routing → produksi → outsourcing → laporan | Proyek jangka panjang, maksimalkan sejak awal |
| Outsourcing | Didukung penuh: kirim material ke vendor, lacak stok di vendor, terima hasil | Permintaan eksplisit user |
| Sumber material | Per komponen BOM: dari stok sendiri (`stock`) atau disediakan vendor (`vendor`) | Permintaan eksplisit user |
| Routing | Work center + operasi + waktu setup/run, snapshot ke WO | Permintaan eksplisit user |
| Mata uang | IDR, disimpan sebagai integer rupiah | Hindari drift pembulatan float |
| Zona waktu | `Asia/Jakarta` | Operasi tunggal di Indonesia |
| Bucket waktu MRP | Harian | Lead time diskrit per hari |
| Gudang | Multi-gudang sejak awal; gudang virtual per vendor untuk stok di vendor | Konservasi ledger tunggal |
| Bahasa | UI Indonesia, kode English | User berbahasa Indonesia |

## Glosarium

| Istilah | Arti |
|---|---|
| Item | Material unik: bahan baku (`raw`), barang setengah jadi (`wip`), barang jadi (`finished`) |
| Source type | Cara pemenuhan item: `make` (produksi internal), `buy` (beli), `subcon` (maklon ke vendor) |
| BOM | Bill of Materials — daftar komponen + kuantitas untuk membuat satu unit item induk |
| BOM aktif | Satu-satunya versi BOM yang dipakai perhitungan untuk item induk |
| Supply mode | Sumber komponen: `stock` (dari gudang kita) atau `vendor` (disediakan vendor maklon) |
| Phantom | Sub-rakitan yang tidak disetok; kebutuhannya langsung ditembus ke komponen anaknya |
| MPS | Master Production Schedule — jadwal produksi barang jadi per periode |
| MRP | Perhitungan kebutuhan material time-phased dari MPS melalui ledakan BOM |
| Gross requirement | Kebutuhan kotor item pada satu periode, sebelum stok diperhitungkan |
| Scheduled receipt | Penerimaan yang sudah pasti datang: PO/WO/SCO terbuka atau planned order firmed |
| Projected on-hand | Proyeksi stok akhir periode setelah semua penerimaan dan kebutuhan |
| Net requirement | Kekurangan bersih setelah stok, scheduled receipt, dan safety stock diperhitungkan |
| Planned order | Usulan order (beli/buat/maklon) hasil MRP; belum mengikat sampai di-firm/release |
| Lead time | Hari kalender dari order dirilis sampai barang tersedia |
| Safety stock | Stok minimum yang harus dipertahankan; MRP memesan untuk mempertahankannya |
| Lot sizing | Kebijakan jumlah order: LFL (lot-for-lot), FOQ (fixed order quantity) |
| Low-level code | Kedalaman terdalam item di seluruh BOM; menentukan urutan proses MRP |
| Pegging | Jejak dari planned order kembali ke sumber kebutuhan (MPS/SO induk) |
| PR | Purchase Requisition — usulan pembelian internal |
| PO | Purchase Order — order pembelian ke supplier |
| WO | Work Order — perintah produksi internal |
| SCO | Subcontract Order — perintah maklon ke vendor |
| Vendor / subcontractor | Pihak eksternal yang memproses material menjadi barang jadi/setengah jadi |
| Stok vendor | Material kita yang berada di vendor; tercatat di gudang virtual tipe `vendor` |
| Backflush | Pengurangan otomatis komponen di vendor saat hasil maklon diterima, sesuai BOM |
| Routing | Urutan operasi produksi suatu item, dengan work center dan waktu |
| Work center | Mesin/lini/tempat kerja tempat operasi dijalankan |
| On-hand | Stok fisik tercatat di gudang (internal maupun vendor) |
| Allocated | Stok yang sudah dijanjikan untuk WO/permintaan lain |
| Available | On-hand dikurangi allocated; dasar cek kecukupan stok |
| QC | Quality Control — inspeksi barang masuk sebelum bisa dipakai |
| Karantina | Gudang virtual berpasangan dengan gudang internal; stok di sana belum lulus QC |
| Disposition | Tindakan atas barang reject: return_supplier, return_vendor, rework, scrap |

## Pengguna dan divisi

Akses diatur per divisi, bukan peran statis. Kebijakan lengkap, matriks default, dan cara konfigurasi ada di `04-hak-akses.md`. Ringkasan: user masuk satu divisi; permission diberikan ke divisi lewat UI admin; divisi `admin` lolos semua; default deny untuk sisanya.

Divisi default: `admin`, `planning`, `purchasing`, `production`, `warehouse`, `quality`, `sales`.

## Di luar cakupan v1

Akuntansi/GL, payroll, multi-mata uang, konversi satuan ukur, penjadwalan kapasitas terbatas (finite scheduling/CRP), outsourcing per operasi (pecah WO di tengah routing — saat ini maklon level order utuh), barcode/scanner, aplikasi mobile, multi-perusahaan, lot/serial tracking.

Menambah salah satu = keputusan user + pembaruan roadmap ini. Jangan bangun diam-diam.
