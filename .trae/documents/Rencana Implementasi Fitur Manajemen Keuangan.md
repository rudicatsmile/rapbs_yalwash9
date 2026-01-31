# Rencana Implementasi Aplikasi Manajemen Keuangan

Saya akan membangun modul manajemen keuangan ini menggunakan ekosistem Filament yang sudah ada di proyek `kaidov4`. Berikut adalah detail implementasinya:

## 1. Struktur Database (Schema)
Saya akan membuat dua tabel baru untuk menyimpan data secara terstruktur:

### a. Tabel `financial_records` (Header History)
Menyimpan data utama per sesi input.
- `id`: Primary Key
- `user_id`: Relasi ke user pemilik data
- `record_date`: Tanggal input
- `record_name`: Nama history (contoh: "Keuangan Januari 2026")
- `income_amount`: Jumlah pemasukan awal
- `income_percentage`: Persentase alokasi (0-100)
- `income_fixed`: Hasil perhitungan (Pemasukan x Persentase)
- `total_expense`: Total akumulasi pengeluaran (untuk performa query)

### b. Tabel `expense_items` (Detail Pengeluaran)
Menyimpan item-item pengeluaran dalam repeater.
- `id`: Primary Key
- `financial_record_id`: Relasi ke header
- `description`: Keterangan pengeluaran
- `amount`: Jumlah pengeluaran

## 2. Implementasi di Filament (Frontend & Logic)

### a. Resource: `FinancialRecordResource`
Saya akan membuat resource baru yang mencakup halaman List, Create, Edit, dan View.

### b. Form Input (Reactive)
Form akan dibagi menjadi 3 bagian sesuai spesifikasi:
1.  **Header**: DatePicker & TextInput untuk nama.
2.  **Rencana Pemasukan**:
    -   `income_amount` & `income_percentage`: Field dengan validasi numerik dan fitur `live()` update.
    -   `income_fixed`: Field `read-only` yang otomatis terisi berdasarkan rumus: `amount * (percentage / 100)`.
3.  **Rencana Pengeluaran**:
    -   Label Total: Field otomatis yang menjumlahkan semua item di bawahnya.
    -   **Repeater**: Tombol (+) untuk menambah item (Keterangan & Jumlah).
    -   *Logic*: Setiap kali input jumlah diubah, total pengeluaran akan dihitung ulang secara real-time.

### c. Tabel List History
Menampilkan kolom-kolom:
-   Tanggal & Nama History
-   Total Pemasukan (Fixed)
-   Total Pengeluaran
-   **Saldo Akhir**: Kolom kalkulasi (`income_fixed - total_expense`)
-   Action: View, Edit, Delete

## 3. Validasi & Error Handling
-   Memastikan persentase 0-100%.
-   Format mata uang (Rp).
-   Mencegah simpan jika data tidak valid.

## 4. Deliverables Tambahan
-   **Unit Tests**: Test dasar untuk memastikan perhitungan dan penyimpanan data benar.
-   **Dokumentasi**: File Markdown berisi panduan penggunaan dalam Bahasa Indonesia.

Apakah Anda setuju dengan rencana implementasi ini?