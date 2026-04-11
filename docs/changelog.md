# Changelog

## 2026-04-11

### Financial Records: Duplicate Record (Update Struktur DB)

- Duplikasi record kini menyalin field baru pada `expense_items`:
    - `allocated_amount` (dipastikan tidak null pada hasil duplikasi)
    - `is_selected_for_realization` (default `false` jika tidak ada nilai)
- Duplikasi record kini menyalin data terkait pada tabel `realization_expense_lines` dengan relasi yang konsisten (mapping `expense_item_id` ke item hasil duplikasi).
- Ditambahkan validasi anggaran sebelum duplikasi: duplikasi dibatalkan jika `income_total < total_expense`.
- Duplikasi record kini membuat record baru dengan `status = 0` dan menyalin `total_realization` dari record asal.
- Ditambahkan pengujian otomatis untuk memastikan seluruh skenario duplikasi sesuai perilaku baru.
