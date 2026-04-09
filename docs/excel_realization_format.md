#+#+#+#+ Format Excel Realisasi (Update: Sumber Duplikat)

Perubahan:
- Aksi di tabel Realisasi kini bernama **Download excel** dan menghasilkan file `.xlsx`.
- Isi excel mengikuti struktur ringkas seperti PDF (info record → pemasukan → pengeluaran).

Bagian Pengeluaran:
- Baris utama per item RAPBS:
  - No | Deskripsi | Anggaran | Realisasi (kumulatif) | Sisa
- Baris detail (sub-row) untuk setiap realisasi yang diinput (ketika sumber dipakai multiple kali):
  - No kosong | Deskripsi realisasi | Anggaran kosong | Realisasi per baris | Sisa kosong

Perhitungan:
- Realisasi kumulatif per item dihitung dari penjumlahan `realization_expense_lines.realisasi` per `expense_item_id`.
- Sisa = `item.amount - sum(realisasi per item)`.
- Total Realisasi pada bagian Total Pengeluaran berasal dari penjumlahan seluruh `realization_expense_lines.realisasi` (jika ada), fallback ke `financial_records.total_realization` jika belum ada lines.

Referensi:
- Builder baris: `app/Filament/Exports/RealizationExcelRowsBuilder.php`
- Aksi tabel: `app/Filament/Resources/RealizationResource/Tables/RealizationTable.php`

