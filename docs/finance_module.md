# Panduan Penggunaan Modul Keuangan

Modul ini dirancang untuk membantu Anda mencatat rencana pemasukan dan pengeluaran dengan sistem histori yang terstruktur.

## Fitur Utama

1.  **Rencana Pemasukan**
    - Input jumlah pemasukan.
    - Input persentase alokasi.
    - Perhitungan otomatis nilai fix pemasukan.

2.  **Rencana Pengeluaran**
    - Tambah item pengeluaran tanpa batas.
    - Perhitungan otomatis total pengeluaran.

3.  **Pencatatan Realisasi**
    - Input realisasi per item pengeluaran.
    - Perhitungan otomatis Total Realisasi dan Total Saldo.
    - Validasi input (mencegah nilai negatif).
    - Tampilan total yang informatif dengan tooltip.

4.  **Histori & Laporan**
    - Melihat daftar riwayat keuangan.
    - Menghitung saldo akhir otomatis.

## Cara Menggunakan

### 1. Menambah Data Baru

1.  Buka menu **RAPB Sekolah**.
2.  Klik tombol **New RAPB Sekolah**.
3.  Isi **Header**:
    - **Departemen**: Pilih departemen.
    - **Tanggal**: Pilih tanggal pencatatan.
    - **Nama History**: Beri nama yang mudah diingat (contoh: "Budget Januari 2026").
4.  Isi **Rencana Pemasukan**:
    - Masukkan **Pemasukan (Rp)**.
    - Masukkan **Persentase (%)**.
    - Kolom **Fix** akan terisi otomatis.
5.  Isi **Rencana Pengeluaran**:
    - Klik **Tambah Pengeluaran**.
    - Isi **Keterangan** dan **Jumlah**.
    - **Total Rencana Pengeluaran** akan terupdate otomatis.
6.  Klik **Create** untuk menyimpan.

### 2. Mengelola Data

- **Edit**: Klik ikon pensil pada baris data yang ingin diubah.
- **Hapus**: Centang data dan pilih delete, atau gunakan tombol delete pada baris data.

### 3. Mencatat Realisasi

1.  Buka menu **Realisasi**.
2.  Pilih data RAPB yang ingin diupdate realisasinya (klik tombol Edit/Pensil).
3.  Pada bagian **Rencana Pengeluaran**:
    - Masukkan nilai pada kolom **Realisasi** untuk setiap item.
    - Kolom **Saldo** akan terhitung otomatis (Anggaran - Realisasi).
4.  Perhatikan baris **Total** di bagian bawah:
    - **Total Anggaran**: Jumlah seluruh anggaran.
    - **Total Realisasi**: Jumlah seluruh realisasi yang diinput.
    - **Total Saldo**: Sisa anggaran yang belum terealisasi.
    - _Tip: Arahkan kursor ke nilai total untuk melihat penjelasan rumus._
5.  Klik **Save Changes**.
6.  Sistem akan menyimpan data dan mengarahkan kembali ke tabel daftar realisasi dengan notifikasi sukses.

### 4. Sistem Locking Realisasi (Baru)

Sistem ini menerapkan penguncian data untuk menjaga integritas laporan keuangan:

- **Status Kunci**: Ketika `status_realisasi` diset ke `1` (Final/Locked).
- **Pembatasan**: User dengan role `User` **TIDAK DAPAT** mengedit data tersebut.
- **Indikator Visual**: Tombol edit akan berubah menjadi abu-abu dan tidak dapat diklik, disertai tooltip "Data dikunci (Final)".
- **Hak Akses Admin**: User dengan role `Super Admin`, `Admin`, atau `Editor` tetap dapat mengedit data meskipun dalam status terkunci.

### 5. Histori Perubahan (Tracking)

Setiap perubahan data (Tambah, Edit, Hapus) pada **Financial Records** dan **Realisasi** kini tercatat secara otomatis:

- **Akses Histori**: Klik tombol **History** di pojok kanan atas tabel.
- **Informasi**: Menampilkan siapa yang mengubah, kapan, jenis aksi, dan detail perubahan (data lama vs data baru).
- **Navigasi**: Gunakan pagination untuk melihat riwayat yang lebih lama.

## Struktur Menu & Hak Akses

Fitur ini telah diintegrasikan ke sidebar utama dengan spesifikasi berikut:

- **Grup Menu**: `Financial Management`
- **Label Menu**: `RAPB Sekolah`
- **Ikon**: `Heroicon::OutlinedBanknotes`
- **Urutan**: Prioritas utama dalam grup (Sort: 1)

### Hak Akses (Permissions)

Akses ke menu ini dikontrol oleh role dan permission berikut:

- `ViewAny:FinancialRecord`: Mengizinkan user melihat menu di sidebar dan mengakses halaman index.
- `Create:FinancialRecord`: Mengizinkan user membuat data baru.
- `Update:FinancialRecord`: Mengizinkan user mengedit data.
- `Delete:FinancialRecord`: Mengizinkan user menghapus data.

Pastikan user memiliki role yang mencakup permission di atas agar menu muncul di sidebar.

### Kontrol Akses Berbasis Departemen

Untuk pengguna dengan role `user`, sistem menerapkan pembatasan otomatis:

1.  **View**: User hanya dapat melihat data RAPB Sekolah yang terkait dengan departemen mereka.
2.  **Create/Edit**: Field **Departemen** akan otomatis terisi dan terkunci sesuai dengan departemen user yang sedang login.
3.  **Security**: User tidak dapat mengakses data departemen lain melalui URL atau manipulasi form.

User dengan role `super_admin` atau `admin` memiliki akses penuh ke semua data departemen.
