# Modul 02 — BOM

Fase: F2. Entitas: `docs/02-model-data.md` bagian BOM.

## Tujuan

BOM multi-level dengan versi, satu BOM aktif per item induk, deteksi siklus, phantom, dan perhitungan low-level code untuk urutan MRP.

## Aturan bisnis

1. Tepat satu BOM `active` per item induk. Aktivasi versi baru dalam satu transaksi: arsipkan yang lama, aktifkan yang baru. Tidak ada jendela waktu dua BOM aktif.
2. Aktivasi memvalidasi: graf BOM global tanpa siklus (DFS dari induk melalui seluruh BOM aktif + draft yang diaktivasi); komponen ≠ induk; `qty_per > 0`; item induk bertipe `wip`/`finished`; semua komponen `is_active`.
3. `scrap_pct` adalah persentase tambahan komponen: kebutuhan efektif = `qty_per × (1 + scrap_pct/100)`. Dibulatkan ke 3 desimal saat ledakan MRP, bukan disimpan ulang di BOM.
4. Phantom (`is_phantom` pada line): sub-rakitan yang tidak pernah disetok dan tidak dibuat planned order-nya. MRP menembus langsung ke komponen anak phantom pada tanggal yang sama. Item phantom tetap boleh punya BOM aktif sendiri.
5. `supply_mode` per line: `stock` = komponen dari gudang kita; `vendor` = disediakan vendor maklon (tidak memicu kebutuhan MRP bagi kita, tidak di-dispatch). Default `stock`. Mode `vendor` hanya bermakna bila induk di-maklon; untuk WO internal semua komponen dari stok. Override per dokumen dimungkinkan saat rilis SCO (modul 10).
6. `low_level_code` item = kedalaman terdalam item itu di seluruh graf BOM aktif (induk level 0 bila tidak jadi komponen di mana pun). Dihitung ulang setiap aktivasi/arsip BOM, disimpan di `items.low_level_code`. MRP memproses item urut low-level code menaik.
7. BOM `draft` bebas diubah; BOM `active` tidak boleh diubah — perubahan = versi baru. Alasan: WO/SCO dan run MRP mereferensi BOM aktif pada waktunya.
8. WO/SCO yang sudah rilis menyimpan snapshot BOM (`wo_materials`/`sco_materials`); perubahan BOM setelah rilis tidak mengubah order berjalan.

## Alur utama

Buat BOM draft → tambah lines → aktivasi (validasi aturan 2, hitung ulang low-level code) → versi baru bila revisi. Tampilan pohon BOM multi-level (read-only explode) untuk verifikasi visual.

## Kriteria penerimaan

- [ ] Aktivasi menolak siklus, komponen=induk, qty<=0, komponen nonaktif (test per kasus).
- [ ] Dua aktivasi berurutan menyisakan tepat satu BOM aktif (test).
- [ ] Low-level code benar pada fixture 3 tingkat dengan satu komponen dipakai di dua kedalaman (test).
- [ ] Perubahan BOM aktif ditolak; versi baru mengarsipkan yang lama atomik (test).
- [ ] Pohon explode menampilkan phantom dengan penanda dan menembus ke anaknya.
- [ ] Line `supply_mode=vendor` tersimpan dan tampil di pohon; default `stock` (test).
