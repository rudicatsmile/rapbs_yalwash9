# Dokumentasi Perbaikan Sistem Rapbs-Yalwash9

## 1. Masalah Notifikasi Persetujuan Realisasi

### Identifikasi Masalah
Pengguna melaporkan bahwa notifikasi tidak terkirim kepada user departemen tertentu setelah status pengajuan berubah menjadi "Disetujui oleh Bendahara".

### Analisis & Temuan
- **Alur Kerja**: Persetujuan dilakukan oleh Bendahara melalui halaman edit Realisasi dengan mengubah toggle "Disetujui oleh Bendahara".
- **Event**: Toggle ini memicu event `App\Events\RealizationApproved`.
- **Listener**: Event ditangani oleh `App\Listeners\SendRealizationApprovedNotification`.
- **Notifikasi**: Listener mengirimkan `App\Notifications\RealizationApprovedNotification` via database dan email.
- **Validasi**: Pengujian end-to-end menunjukkan sistem berfungsi normal jika user memiliki permission yang sesuai.

### Perbaikan & Solusi
- Memastikan user dengan role `Bendahara` memiliki permission yang cukup (`Update:FinancialRecord`, `View:FinancialRecord`, `ViewAny:FinancialRecord`) untuk melakukan persetujuan.
- Membuat automated test `tests/Feature/NotificationEndToEndTest.php` untuk memverifikasi alur notifikasi secara berkelanjutan.
- Test case mencakup:
  1. Login sebagai Bendahara.
  2. Mengubah status persetujuan realisasi milik staff.
  3. Memverifikasi notifikasi terkirim ke staff yang bersangkutan.
  4. Memverifikasi status di database terupdate.

---

## 2. Masalah Status Financial Record (User Role)

### Identifikasi Masalah
User dengan role "User" mendapatkan nilai field `status` = 0 (Inactive) setelah berhasil submit data `FinancialRecord`, padahal seharusnya Default Active (1).

### Analisis & Temuan
- **Penyebab Utama**:
  - Kolom `status` pada tabel `financial_records` memiliki default value `0` di database.
  - Pada form Filament, field `status` disembunyikan (`hidden`) dan dimatikan (`disabled`) untuk role "User".
  - Karena field tersebut tidak dirender atau disubmit oleh form (karena hidden/disabled), Laravel tidak menerima nilai `status` dalam request.
  - Akibatnya, database menggunakan default value `0`.

### Perbaikan & Solusi
- **Modifikasi Backend Logic**:
  - Mengupdate `App\Filament\Resources\FinancialRecords\Pages\CreateFinancialRecord.php`.
  - Menambahkan logika pada method `mutateFormDataBeforeCreate`:
    ```php
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        // Paksa status menjadi Active (1) jika user adalah 'user' dan field status tidak ada di data form
        if (!isset($data['status']) && auth()->user()->hasRole('user')) {
            $data['status'] = true;
        }

        return $data;
    }
    ```
- **Verifikasi**:
  - Dibuat test case `tests/Feature/DebugStatusTest.php` yang mensimulasikan submit form oleh user.
  - Hasil test menunjukkan record berhasil dibuat dengan `status` = 1 (Active).

### Rekomendasi
- Pastikan role `Bendahara` memiliki permission yang lengkap untuk modul Financial Record dan Realization.
- Pertahankan test case yang telah dibuat untuk mencegah regresi di masa depan.
