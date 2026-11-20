# Modul 04 — Permintaan & MPS

Fase: F4. Entitas: `docs/02-model-data.md` bagian Permintaan & MPS.

## Tujuan

Menangkap permintaan independen (sales order, forecast) dan mengubahnya menjadi MPS barang jadi — satu-satunya input permintaan independen yang diledakkan MRP.

## Aturan bisnis

1. **Sales order:** hanya item `finished`. Konfirmasi SO mengalokasikan stok tersedia (`allocate`); stok kurang → SO tetap confirmed dengan kekurangan tercatat, bukan ditolak (permintaan tetap harus direncanakan). Fulfillment mengurangi allocated dan on-hand lewat movement (type virtual keluar; referensi SO line).
2. `qty_fulfilled <= qty` per line; SO `fulfilled` bila semua line penuh; pembatalan SO melepas alokasi.
3. **Forecast:** per item `finished` per tanggal; unik per pasangan; boleh ditimpa (update) sebelum dipakai MPS.
4. **MPS entry:** per item `finished` per tanggal per source. Entri `firmed` tidak boleh diubah otomatis oleh proses apa pun; hanya user divisi `planning` secara manual.
5. **Konsumsi forecast oleh SO (anti double-count):** saat generate MPS dari forecast + SO pada periode yang sama, qty MPS = `max(forecast, SO confirmed)` per item-periode, bukan jumlah keduanya. Alasan: SO adalah realisasi forecast; menjumlahkannya menggandakan permintaan.
6. MPS hanya barang jadi. Komponen tidak pernah punya MPS — kebutuhannya murni dependen hasil ledakan BOM.

## Alur utama

1. Input SO / forecast.
2. Planner menyusun MPS: manual, atau generate dari forecast+SO (aturan 5) untuk horizon terpilih.
3. Review grid MPS (item × tanggal), firm entri yang disetujui.
4. MRP (modul 05) membaca MPS `planned` + `firmed`.

## Kriteria penerimaan

- [ ] Konfirmasi SO mengalokasikan stok; pembatalan melepas; fulfillment mengurangi saldo via service (test).
- [ ] Fulfillment melebihi qty ditolak (test).
- [ ] Generate MPS periode dengan forecast 100 + SO 60 menghasilkan 100, bukan 160 (test).
- [ ] Entri firmed tidak berubah oleh generate ulang (test).
- [ ] MPS menolak item non-finished (test).
