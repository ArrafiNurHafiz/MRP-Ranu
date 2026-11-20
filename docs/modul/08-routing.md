# Modul 08 — Routing & Work Center

Fase: F8. Entitas: `docs/02-model-data.md` bagian Routing.

## Tujuan

Mendefinisikan urutan operasi produksi per item: work center, waktu setup dan run per unit. Routing menjadi snapshot WO saat rilis dan dasar laporan beban work center.

## Aturan bisnis

1. Tepat satu routing `active` per item; aktivasi versi baru mengarsipkan yang lama atomik (pola sama dengan BOM).
2. Operasi `internal` wajib punya `work_center_id`; operasi `subcon` wajib punya `subcontractor_id`. `sequence` unik per routing, mulai 1, tanpa lubang.
3. `setup_minutes >= 0`, `run_minutes_per_unit >= 0`; total minimal salah satu > 0 per operasi.
4. Item `source_type=make` wajib punya routing aktif untuk merilis WO (divalidasi di modul 09). Item `subcon` boleh tanpa routing — proses dikelola vendor; routing bila ada hanya referensi.
5. Routing `active` tidak boleh diubah; revisi = versi baru (alasan sama dengan BOM: WO mereferensi routing pada waktu rilis).
6. Operasi `subcon` pada routing tercatat sebagai referensi proses vendor. Eksekusi maklon per operasi (kirim WIP di tengah routing) di luar cakupan v1 — maklon dijalankan sebagai SCO utuh (modul 10). Jangan bangun pecahan operasi diam-diam.
7. `capacity_minutes_per_day` work center informatif untuk laporan beban; bukan constraint penjadwalan (finite scheduling di luar cakupan v1).

## Alur utama

Buat work center → buat routing draft per item → tambah operasi berurutan → aktivasi. Tampilan daftar operasi dengan total waktu per unit.

## Kriteria penerimaan

- [ ] Aktivasi menolak: operasi internal tanpa work center, operasi subcon tanpa vendor, sequence duplikat, waktu negatif (test per kasus).
- [ ] Dua aktivasi berurutan menyisakan tepat satu routing aktif (test).
- [ ] Routing aktif menolak perubahan; versi baru mengarsipkan yang lama atomik (test).
- [ ] Total waktu operasi = setup + run_per_unit tampil benar (test).
