# Format PDF Realisasi (Update: Sumber Duplikat)

Perubahan:
- PDF kini menampilkan seluruh baris **Realisasi** yang diinput untuk setiap **Sumber Anggaran** yang sama (duplikat), bukan hanya satu agregat.

Struktur Tabel Pengeluaran:
- Baris utama per item RAPBS:
  - Kolom: No | Deskripsi | Anggaran | Realisasi (kumulatif) | Sisa
- Baris sub-item (ditampilkan jika ada duplikasi sumber):
  - Kolom: — | “Realisasi #n: <deskripsi>” | — | Realisasi per baris | —

Perhitungan:
- Realisasi kumulatif per item dihitung dari penjumlahan `realization_expense_lines.realisasi` untuk `expense_item_id` terkait.
- Sisa = `item.amount - sum(realisasi per item)`.
- Total Realisasi pada footer tabel berasal dari penjumlahan seluruh `realization_expense_lines.realisasi`.

Referensi:
- View: `resources/views/pdf/financial_record.blade.php`
- Backup lama: `resources/views/pdf/financial_record.backup_2026_04_08.blade.php`
- Model garis realisasi: `App\Models\RealizationExpenseLine`

Catatan:
- Layout dijaga tetap ringkas dan readable; baris sub-item diberi styling tipografi lebih ringan.

