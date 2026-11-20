# Modul 09 — Produksi

Fase: F9. Entitas: `docs/02-model-data.md` bagian Produksi. Prasyarat: modul 08 (routing), modul 06 (QC).

## Tujuan

Mengubah planned order make menjadi WO: snapshot BOM + routing saat rilis, issue material mengurangi stok komponen, completion masuk karantina dan dilepas QC ke stok jadi, progres operasi tercatat. Konservasi terbukti end-to-end.

## Aturan bisnis

1. Release planned order `make` → WO dengan snapshot dari BOM aktif dan routing aktif saat itu:
   - `wo_materials`: `qty_required = qty_wo × qty_per × (1 + scrap_pct/100)` per komponen (phantom sudah ditembus saat snapshot). Semua komponen WO internal dari stok — `supply_mode=vendor` tidak berlaku di sini (itu jalur maklon, modul 10).
   - `wo_operations`: urutan operasi routing, `run_minutes_total = run_minutes_per_unit × qty_wo`.
   - Item tanpa routing aktif → rilis ditolak (modul 08 aturan 4). Perubahan BOM/routing setelah rilis tidak mengubah WO.
2. WO `released` menjadi scheduled receipt pada `due_date` di MRP run berikutnya.
3. Issue material: movement `wo_issue` (from = gudang, to = null/virtual produksi) lewat service; `qty_issued + qty_baru <= qty_required` kecuali override eksplisit user dengan alasan tercatat (tolak default `EXCESSIVE_ISSUE`). Saldo kurang → `INSUFFICIENT_STOCK`, WO tidak berubah.
4. Issue pertama mengubah WO → `in_progress`.
5. Progres operasi: tandai `wo_operations.status = done` berurutan (operasi sebelumnya harus done); `completed_at` tercatat. Ini pencatatan progres, bukan gate issue/completion.
6. Completion: movement `wo_completion` (from = null/virtual produksi, to = gudang karantina gudang hasil). QC (modul 06) menentukan hasil: pass → `qc_release` ke gudang FG menambah saldo jadi; reject → `rework` (kembali ke produksi) atau `scrap` (alasan wajib). Hanya qty pass yang menghitung target WO; total completion pass `<= wo.qty` (tolak `EXCESSIVE_COMPLETION`). Completion ulang diperbolehkan untuk mengganti yang reject.
7. WO `completed` bila akumulasi qty pass = wo.qty; `closed` manual bila semua material terissue. WO cancelled melepas alokasi; material yang sudah terissue dikembalikan lewat movement adjustment referensi WO (bukan edit movement lama).
8. Nomor WO digenerate sistem (`WO-2026-0001`), unik.

## Alur utama

Planned order make → release WO (snapshot BOM + routing) → issue material per komponen (bisa parsial, dari gudang mana pun yang punya stok) → tandai progres operasi → completion ke karantina → QC pass/reject → pass lepas ke FG / reject rework-scrap → close.

## Kriteria penerimaan

- [ ] End-to-end: WO → issue → completion → QC pass → saldo komponen berkurang tepat Σ issue, saldo jadi bertambah tepat Σ qty pass (test konservasi).
- [ ] Snapshot BOM/routing: ubah BOM/routing aktif setelah rilis, `wo_materials`/`wo_operations` tidak berubah (test).
- [ ] Rilis item tanpa routing aktif ditolak (test).
- [ ] Issue melebihi kebutuhan ditolak tanpa override; dengan override + alasan tercatat (test).
- [ ] Issue melebihi saldo ditolak `INSUFFICIENT_STOCK`, WO tidak berubah (test).
- [ ] Completion masuk karantina, tidak langsung menambah saldo FG (test).
- [ ] QC reject → rework/scrap, tidak menambah saldo FG; WO tetap incomplete (test).
- [ ] Completion melebihi qty WO (dihitung yang pass) ditolak `EXCESSIVE_COMPLETION` (test).
- [ ] Operasi ditandai done melompati operasi sebelumnya ditolak (test).
- [ ] WO released muncul sebagai scheduled receipt di MRP run berikutnya (test integrasi).
- [ ] Phantom tidak muncul di `wo_materials` — komponennya yang muncul (test).
