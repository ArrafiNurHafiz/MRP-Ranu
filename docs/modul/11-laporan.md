# Modul 11 — Laporan & Dashboard

Fase: F11. Tanpa tabel baru — query atas tabel yang ada (`02-model-data.md`).

## Tujuan

Angka yang bisa dipercaya untuk keputusan: posisi stok (internal, vendor, karantina), QC, kesehatan rencana, kinerja pembelian/produksi/maklon. Semua laporan read-only, dapat difilter tanggal/gudang/item/vendor, diekspor CSV.

## Laporan wajib

1. **Posisi stok:** per item-gudang (internal + vendor + karantina): on-hand, allocated, available, nilai. Stok karantina ditandai "belum usable".
2. **Stok di vendor:** per vendor per item: dispatched, consumed (backflush), returned, sisa di vendor, referensi SCO aktif. Alarm: sisa material menganggur melebihi umur SCO.
3. **QC hasil:** per sumber (PO/SCO/WO): qty diterima, diperiksa, pass, reject, metode, inspector, disposition; laju reject per supplier/vendor/item.
4. **Stok karantina aging:** item di karantina yang belum dilepas > N hari (N default 3), dengan referensi QC check dan sumber.
5. **Kartu stok:** riwayat movement per item-gudang dengan saldo berjalan (versi cetak/ekspor dari modul 03).
6. **Hasil MRP:** grid item × periode dari run terpilih: gross, scheduled, projected, net, planned receipt/release; planned order per status dan tipe (purchase/make/subcon).
7. **Exception MRP:** daftar exception run terakhir (LATE_RELEASE, NO_BOM, BEYOND_HORIZON) dengan tautan ke dokumen terkait.
8. **PO terbuka:** per supplier: qty pesan vs accepted (setelah QC), jatuh tempo, keterlambatan.
9. **WO berjalan:** per item: qty, issued vs required, pass QC vs qty, progres operasi, umur WO.
10. **SCO terbuka:** per vendor: qty order vs accepted (setelah QC), material di vendor, jatuh tempo, keterlambatan.
11. **Beban work center:** total menit (setup + run) WO released per work center per periode, dibandingkan `capacity_minutes_per_day` — informatif, bukan penjadwalan otomatis.
12. **Rekonsiliasi stok:** Σ movement vs `stock_balances` per item-gudang termasuk vendor/karantina; selisih ≠ 0 ditampilkan merah. Hasil sehat = semua nol.

## Dashboard

Ringkasan satu layar: PO/WO/SCO terlambat, item di bawah safety stock, stok menganggur di vendor, stok karantina menunggu QC, laju reject QC, exception MRP run terakhir, hasil rekonsiliasi terakhir. Setiap kartu menaut ke laporan rincian.

## Aturan bisnis

1. Laporan read-only (diuji).
2. Angka laporan harus cocok dengan perhitungan manual atas fixture yang sama.
3. Filter tanggal memakai timezone `Asia/Jakarta`; nilai uang integer rupiah, tampil format `Rp`.

## Kriteria penerimaan

- [ ] 12 laporan + dashboard tersedia dengan filter dan ekspor CSV.
- [ ] Fixture dihitung manual: posisi stok, stok vendor, QC hasil, hasil MRP, PO/SCO/WO terbuka cocok persis (test).
- [ ] Rekonsiliasi mendeteksi selisih yang disengaja pada fixture rusak, termasuk gudang vendor/karantina (test).
- [ ] Semua endpoint laporan menolak metode tulis (test).
