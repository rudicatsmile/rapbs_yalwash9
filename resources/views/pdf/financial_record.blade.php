<!DOCTYPE html>
<html>
<head>
    <title>Laporan Transaksi Keuangan</title>
    <style>
        body { font-family: sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .section { margin-bottom: 15px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
        .table th { background-color: #f2f2f2; }
        .table th.text-right, .table td.text-right { text-align: right !important; }
        .bold { font-weight: bold; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; display: inline-block; }
        .badge-success { background-color: #d1fae5; color: #065f46; border: 1px solid #065f46; } /* Green */
        .badge-danger { background-color: #fee2e2; color: #991b1b; border: 1px solid #991b1b; } /* Red */
    </style>
</head>
<body>
    @php
        $kepalaSekolah = \App\Models\SchoolOfficial::where('role', 'kepala_sekolah')
            ->where(function ($query) use ($record) {
                $query->where('department_id', $record->department_id)
                      ->orWhereNull('department_id');
            })
            ->orderByRaw('department_id IS NULL')
            ->first();

        $bendaharaSekolah = \App\Models\SchoolOfficial::where('role', 'bendahara_sekolah')
            ->where(function ($query) use ($record) {
                $query->where('department_id', $record->department_id)
                      ->orWhereNull('department_id');
            })
            ->orderByRaw('department_id IS NULL')
            ->first();

        $bendaharaYayasan = \App\Models\SchoolOfficial::where('role', 'kepala_departemen')
            ->whereNull('department_id')
            ->first();

        $kepalaSekolahName = $kepalaSekolah?->name ?? '(Pejabat Kepala Sekolah belum diatur)';
        $bendaharaSekolahName = $bendaharaSekolah?->name ?? '(Pejabat Bendahara Sekolah belum diatur)';
        $bendaharaYayasanName = $bendaharaYayasan?->name ?? '(Pejabat Bendahara Yayasan belum diatur)';
    @endphp
    <div class="header">
        <h2>YAYASAN AL-WATHONIYAH 9</h2>
        <h3>LAPORAN TRANSAKSI KEUANGAN</h3>
    </div>

    <div class="section">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; width: 150px;"><strong>Nama Record</strong></td>
                <td style="border: none;">: {{ $record->record_name }}</td>
            </tr>
            <tr>
                <td style="border: none;"><strong>Tanggal</strong></td>
                <td style="border: none;">: {{ $record->record_date ? $record->record_date->format('d-m-Y') : '-' }}</td>
            </tr>
            <tr>
                <td style="border: none;"><strong>Departemen</strong></td>
                <td style="border: none;">: {{ $record->department->name ?? '-' }}</td>
            </tr>
            <tr>
                <td style="border: none;"><strong>Status</strong></td>
                <td style="border: none;">:
                    @if($record->status)
                        <span class="badge badge-success">AKTIF</span>
                    @else
                        <span class="badge badge-danger">NON-AKTIF</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h4>Pemasukan</h4>
        <table class="table">
            <tr>
                <th>Sumber</th>
                <th class="text-right">Jumlah</th>
            </tr>
            <tr>
                <td>Fixed Income</td>
                <td class="text-right">Rp {{ number_format($record->income_fixed, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>BOS</td>
                <td class="text-right">Rp {{ number_format($record->income_bos, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Lainnya</td>
                <td class="text-right">Rp {{ number_format($record->income_bos_other ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr class="bold" style="background-color: #f9fafb;">
                <td>Total Pemasukan</td>
                <td class="text-right">Rp {{ number_format($record->income_total, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h4>Pengeluaran</h4>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th>Deskripsi</th>
                    <th class="text-right" style="width: 120px;">Anggaran</th>
                    <th class="text-right" style="width: 120px;">Realisasi</th>
                    <th class="text-right" style="width: 120px;">Sisa</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $record->loadMissing(['realizationExpenseLines', 'expenseItems']);
                    $linesByExpense = $record->realizationExpenseLines->groupBy('expense_item_id');
                @endphp
                @forelse($record->expenseItems as $index => $item)
                    @php
                        $itemLines = $linesByExpense->get($item->id) ?? collect();
                        $aggRealisasi = $itemLines->sum('realisasi');
                        $saldo = ($item->amount ?? 0) - $aggRealisasi;
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                        <td class="text-right"><strong>Rp {{ number_format($aggRealisasi, 0, ',', '.') }}</strong></td>
                        <td class="text-right">Rp {{ number_format($saldo, 0, ',', '.') }}</td>
                    </tr>
                    @if($itemLines->count() > 0)
                        @foreach($itemLines as $k => $line)
                            <tr>
                                <td style="color:#9ca3af;">—</td>
                                <td>
                                    <span style="color:#6b7280;">{{ (string) $line->description }}</span>
                                </td>
                                <td class="text-right" style="color:#9ca3af;">—</td>
                                <td class="text-right">Rp {{ number_format($line->realisasi ?? 0, 0, ',', '.') }}</td>
                                <td class="text-right" style="color:#9ca3af;">—</td>
                            </tr>
                        @endforeach
                    @endif
                @empty
                <tr>
                    <td colspan="5" style="text-align: center;">Tidak ada data pengeluaran.</td>
                </tr>
                @endforelse
                <tr class="bold" style="background-color: #f9fafb;">
                    <td colspan="2" class="text-right">Total Pengeluaran</td>
                    <td class="text-right">Rp {{ number_format($record->total_expense, 0, ',', '.') }}</td>
                    <td class="text-right">
                        @php
                            $totalRealization = $record->realizationExpenseLines->sum('realisasi');
                        @endphp
                        Rp {{ number_format($totalRealization ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="text-right">Rp {{ number_format($record->total_balance ?? ($record->total_expense - ($record->total_realization ?? 0)), 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section" style="margin-top: 20px;">
        <table class="table">
             <tr class="bold" style="background-color: #e5e7eb;">
                <td style="border: none;">SALDO AKHIR (BALANCE)</td>
                <td style="border: none;" class="text-right">Rp {{ number_format($record->income_total - ($record->total_realization ?? 0), 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="section" style="margin-top: 40px;">
        <table style="width: 100%; border: none; text-align: center; font-size: 12px;">
            <tr>
                <td style="border: none; width: 33%;"></td>
                <td style="border: none; width: 33%;"></td>
                <td style="border: none; width: 33%;">
                    Jakarta, {{ now()->locale('id')->isoFormat('D MMMM Y') }}
                </td>
            </tr>
            <tr>
                <td style="border: none; width: 33%;">Mengetahui,</td>
                <td style="border: none; width: 33%;">Menyetujui,</td>
                <td style="border: none; width: 33%;">
                </td>
            </tr>
            <tr>
                <td style="border: none;">Kepala Sekolah</td>
                <td style="border: none;">Bendahara Yayasan</td>
                <td style="border: none;">Bendahara Sekolah</td>
            </tr>
            <tr>
                <td style="border: none; height: 60px;"></td>
                <td style="border: none;"></td>
                <td style="border: none;"></td>
            </tr>
            <tr>
                <td style="border: none;"><strong>{{ $kepalaSekolahName }}</strong></td>
                <td style="border: none;"><strong>{{ $bendaharaYayasanName }}</strong></td>
                <td style="border: none;"><strong>{{ $bendaharaSekolahName }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="footer" style="margin-top: 30px; text-align: right; font-size: 10px; color: #666;">
        <p>Dicetak otomatis oleh Sistem Ef-fin9 pada: {{ now()->format('d-m-Y H:i') }}</p>
    </div>
</body>
</html>
