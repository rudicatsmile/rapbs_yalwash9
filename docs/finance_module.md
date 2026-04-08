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
    - Pilih **Sumber** dari daftar RAPBS (Daftar Pengeluaran). Dropdown langsung menampilkan semua opsi saat dibuka (tanpa perlu mengetik).
    - Kolom **Anggaran**, **Saldo**, dan detail sumber akan terisi otomatis setelah sumber dipilih.
    - Masukkan nilai pada kolom **Realisasi** untuk setiap item.
    - Kolom **Saldo** akan terhitung otomatis (Anggaran - Realisasi).
    - Baris yang ditambahkan akan tetap muncul setelah disimpan, termasuk jika nilai Realisasi diisi 0.

**Catatan layout (UI):**

- Pada breakpoint `md` ke atas, kolom **Keterangan**, **Sumber**, **Anggaran**, **Realisasi**, **Saldo** berada dalam satu baris agar sejajar secara horizontal.
- Detail sumber dan warning ditampilkan di baris berikutnya (full width) agar tidak menggeser kolom Anggaran/Realisasi/Saldo.
- Implementasi menggunakan class `realization-expense-repeater` untuk penyesuaian spacing/padding di repeater.

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

### 8. Notifikasi WhatsApp Saat Status Tidak Aktif (Create)

Saat membuat data baru pada menu **RAPB Sekolah** (`/financial-records/create`), toggle **Status Aktif** dapat diubah ke posisi tidak aktif (warna merah). Ketika status menjadi tidak aktif, sistem akan mengirim notifikasi WhatsApp ke nomor departemen yang dipilih pada form.

**Isi notifikasi mencakup:**

- Nama departemen tujuan
- Informasi status berubah menjadi tidak aktif
- Ringkasan data financial record (nama history, tanggal, bulan, total pemasukan)
- Timestamp perubahan status

**Syarat agar notifikasi terkirim:**

- Field **Departemen** wajib dipilih terlebih dahulu
- Nomor telepon pada data Departemen harus valid (format: `+62...`, `62...`, atau `08...`)
- Konfigurasi WhatsApp API harus terisi (`WHATSAPP_BASE_URL` dan `WHATSAPP_TOKEN`)

**Perilaku sistem:**

- Jika nomor departemen tidak valid / departemen tidak ditemukan, notifikasi WhatsApp tidak akan dikirim dan user akan mendapat notifikasi error di halaman
- Jika pengiriman WhatsApp gagal (mis. koneksi/token), sistem menampilkan notifikasi gagal dan mencatat log percobaan pengiriman
- Untuk mencegah spam, pengiriman WhatsApp hanya dilakukan sekali per sesi toggle tidak aktif, dan akan dapat dikirim ulang setelah status dikembalikan aktif lalu dinonaktifkan lagi

### 9. Lampiran Financial Record (Upload / View / Delete)

Pada form **RAPB Sekolah** (`/financial-records/create` dan `/financial-records/{id}/edit`), tersedia section **Lampiran Financial Record** untuk mengunggah dokumen pendukung.

**Fitur utama:**

- Upload multiple file (drag & drop)
- Validasi tipe file: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG
- Batas ukuran: maksimal 10MB per file
- Preview/daftar file di komponen upload (dengan open/download)
- Hapus file dari komponen upload

**Catatan teknis:**

- Penyimpanan file menggunakan Media Library (`media` table) untuk menyimpan metadata (nama, size, mime type, tanggal upload) dan file disimpan pada disk `public`.
- Untuk melihat isi lampiran tanpa download, gunakan tombol **View** yang membuka halaman preview di aplikasi.

**Endpoint preview lampiran:**

- `GET /financial-records/{record}/attachments/{media}/preview` (halaman preview)
- `GET /financial-records/{record}/attachments/{media}/file` (stream file inline)

Endpoint ini melakukan validasi akses (role/permission dan batasan departemen untuk role `user`) sebelum menampilkan file.
