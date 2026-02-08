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

### 6. Dashboard Laporan Keuangan

Dashboard interaktif tersedia untuk menganalisis kinerja keuangan secara visual dan tabular.

- **Akses**: Menu `Dashboard Laporan Keuangan` di grup `Financial Management`.
- **Fitur Filter**:
    - **Tahun**: Pilih tahun anggaran.
    - **Periode**: Pilih Kuartal (Q1-Q4) atau Setahun Penuh.
    - **Departemen**: Filter khusus untuk laporan detail per departemen.
- **Komponen Laporan**:
    1.  **Rencana Penerimaan & Pengeluaran**: Grafik batang dan tabel ringkasan per departemen.
    2.  **Tren Realisasi**: Grafik garis tren realisasi bulanan dan tabel pencapaian.
    3.  **Perbandingan (Plan vs Real)**: Grafik kombinasi dan tabel selisih.
    4.  **Detail Per Departemen**:
        - **Komposisi Anggaran**: Pie chart sumber dana (BOS vs Mandiri).
        - **Pola Realisasi**: Grafik area akumulasi realisasi.
        - **Analisis Varians**: Tabel detail item dengan highlight jika selisih > 10%.
- **Export Data**: Klik tombol **Export Excel/CSV** pada tabel untuk mengunduh laporan.

### 7. Integrasi Dashboard pada Manajemen Role

Ringkasan kinerja keuangan juga ditampilkan langsung pada halaman pembuatan dan pengeditan Role (`Shield > Roles`).

- **Lokasi**: Bagian bawah halaman Create Role dan Edit Role.
- **Konten**:
    - **Overview Stats**: Total Anggaran, Pengeluaran, Realisasi, dan Sisa Anggaran.
    - **Grafik Rencana**: Visualisasi perbandingan rencana penerimaan vs pengeluaran.
    - **Grafik Realisasi**: Tren realisasi pengeluaran.
    - **Tabel Varians** (Hanya di Edit Page): Detail selisih anggaran per departemen.
- **Keamanan**: Hanya pengguna dengan hak akses `view_financial_dashboard` yang dapat melihat widget ini.
