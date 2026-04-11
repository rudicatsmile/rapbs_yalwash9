# API / Financial Records

## List & Edit (Filament)

- List: `GET /financial-records`
- Create: `GET /financial-records/create`
- Edit: `GET /financial-records/{record}/edit`

## Duplicate Record

Fitur duplikasi tersedia dari tabel Financial Records (aksi per-baris dan bulk).

Perilaku duplikasi:

- Membuat `financial_records` baru sebagai salinan record asal, dengan `status = 0` dan reset status proses realisasi:
    - `status_realisasi = 0`
    - `is_approved_by_bendahara = 0`
    - `total_realization` disalin dari record asal
    - `total_balance = total_expense - total_realization`
- Menyalin seluruh `expense_items` milik record asal ke record baru:
    - `allocated_amount` dipastikan tidak null pada hasil duplikasi (fallback ke `amount` jika asal null)
    - `is_selected_for_realization` disalin, default `false` jika tidak ada nilai
- Menyalin seluruh `realization_expense_lines` milik record asal ke record baru dan menjaga konsistensi relasi:
    - `financial_record_id` diubah ke ID record baru
    - `expense_item_id` dimapping ke ID item hasil duplikasi (bukan ID item lama)

Validasi bisnis:

- Duplikasi dibatalkan jika `income_total < total_expense`.
