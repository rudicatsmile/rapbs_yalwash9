<div class="history-modal-container">
    <style>
        [x-cloak] { display: none !important; }
        .history-modal-container {
            font-family: inherit;
            overflow-x: auto;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        .history-modal-container table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem; /* text-sm */
            text-align: left;
            color: #6b7280; /* text-gray-500 */
        }
        .history-modal-container thead {
            background-color: #f9fafb; /* bg-gray-50 */
            border-bottom: 1px solid #e5e7eb; /* border-gray-200 */
            text-transform: uppercase;
            font-size: 0.75rem; /* text-xs */
            color: #374151; /* text-gray-700 */
        }
        .history-modal-container th {
            padding: 0.75rem 1.5rem; /* px-6 py-3 */
            font-weight: 600;
            letter-spacing: 0.05em;
        }
        .history-modal-container tbody tr {
            border-bottom: 1px solid #e5e7eb; /* divide-y */
            transition: background-color 0.15s;
        }
        .history-modal-container tbody tr:last-child {
            border-bottom: none;
        }
        .history-modal-container tbody tr:hover {
            background-color: #f9fafb; /* hover:bg-gray-50 */
        }
        .history-modal-container td {
            padding: 1rem 1.5rem; /* px-6 py-4 */
            vertical-align: top;
        }
        .whitespace-nowrap {
            white-space: nowrap;
        }

        /* Date Column */
        .date-container {
            display: flex;
            flex-direction: column;
        }
        .date-text {
            font-weight: 500;
            color: #111827;
        }
        .time-text {
            font-size: 0.75rem;
            color: #6b7280;
            font-family: monospace;
            margin-top: 0.125rem;
        }

        /* Detail Box */
        .detail-box {
            background-color: #f9fafb;
            border: 1px solid #f3f4f6;
            border-radius: 0.375rem;
            padding: 0.5rem;
            font-size: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .detail-title {
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 0.25rem;
        }
        .detail-content {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.375rem;
            line-height: 1.5;
        }
        .val-old {
            color: #ef4444;
            background-color: #fef2f2;
            padding: 0 0.25rem;
            border-radius: 0.25rem;
            text-decoration: line-through;
            text-decoration-color: rgba(239, 68, 68, 0.3);
        }
        .val-new {
            color: #059669;
            background-color: #ecfdf5;
            padding: 0 0.25rem;
            border-radius: 0.25rem;
            font-weight: 500;
        }
        .arrow-icon {
            width: 0.75rem;
            height: 0.75rem;
            color: #9ca3af;
        }
        .text-break-all {
            word-break: break-all;
        }
        .empty-state {
            text-align: center;
            padding: 2rem 1.5rem;
            color: #6b7280;
            font-style: italic;
            background-color: #f9fafb;
        }
        .no-changes {
            font-size: 0.75rem;
            color: #9ca3af;
            font-style: italic;
        }

        /* Snapshot Styles */
        .snapshot-card {
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            margin-bottom: 0.75rem;
            overflow: hidden;
            background-color: #fff;
        }
        .snapshot-card:last-child {
            margin-bottom: 0;
        }
        .snapshot-header {
            padding: 0.5rem 0.75rem;
            font-weight: 600;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .snapshot-header.income { background-color: #ecfdf5; color: #047857; }
        .snapshot-header.expense { background-color: #fef2f2; color: #b91c1c; }

        .snapshot-body {
            padding: 0.5rem;
            font-size: 0.75rem;
        }
        .snapshot-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 0.375rem 0;
            border-bottom: 1px dashed #f3f4f6;
            gap: 1rem;
        }
        .snapshot-row:last-of-type { border-bottom: none; }
        .snapshot-label { color: #6b7280; flex: 1; }
        .snapshot-value { font-weight: 500; color: #1f2937; white-space: nowrap; }
        .snapshot-total {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 2px solid #e5e7eb;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
        }
        .icon-sm {
            width: 1rem;
            height: 1rem;
        }
    </style>

    <table>
        <thead>
            <tr>
                <th scope="col" style="width: 15%;">Waktu</th>
                <th scope="col" style="width: 45%;">Data</th>
                <th scope="col" style="width: 40%;">Detail Perubahan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($record->tracks()->with('creator')->latest()->get() as $track)
                <tr>
                    <!-- Date & Time -->
                    <td class="whitespace-nowrap">
                        <div class="date-container">
                            <span class="date-text">{{ $track->created_at->format('d M Y') }}</span>
                            <span class="time-text">{{ $track->created_at->format('H:i') }}</span>
                            <span class="time-text">{{ $track->creator->name ?? 'System' }}</span>
                        </div>
                    </td>

                    <!-- Snapshot Data -->
                    <td>
                        @if(!empty($track->snapshot_data) && is_array($track->snapshot_data))
                            @php
                                $snap = $track->snapshot_data;
                                $incomeTotal = $snap['income_total'] ?? 0;
                                $expenseTotal = $snap['total_expense'] ?? 0;
                                // Sort expense items by description for consistent ordering
                                $expenses = collect($snap['expense_items'] ?? [])->sortBy('description');
                            @endphp

                            <!-- Income Section -->
                            <div class="snapshot-card"
                                 x-data="{
                                     expanded: localStorage.getItem('snap_in_{{ $track->id }}') === 'true',
                                     toggle() { this.expanded = !this.expanded; localStorage.setItem('snap_in_{{ $track->id }}', this.expanded); }
                                 }"
                            >
                                <div class="snapshot-header income cursor-pointer hover:opacity-80 transition" @click="toggle()">
                                    <div class="flex items-center gap-2 flex-1">
                                        <svg class="icon-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                        </svg>
                                        Pemasukan
                                    </div>
                                    <svg class="icon-sm transform transition-transform duration-200" :class="expanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                                <div class="snapshot-body">
                                    <div x-cloak x-show="expanded" x-transition.duration.300ms class="space-y-1 mb-2">
                                        <div class="snapshot-row">
                                            <span class="snapshot-label">Fixed Income</span>
                                            <span class="snapshot-value">Rp {{ number_format($snap['income_fixed'] ?? 0, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="snapshot-row">
                                            <span class="snapshot-label">BOS</span>
                                            <span class="snapshot-value">Rp {{ number_format($snap['income_bos'] ?? 0, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                    <div class="snapshot-total" style="color: #047857;">
                                        <span>Total Pemasukan</span>
                                        <span>Rp {{ number_format($incomeTotal, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Expense Section -->
                            <div class="snapshot-card"
                                 x-data="{
                                     expanded: localStorage.getItem('snap_out_{{ $track->id }}') === 'true',
                                     toggle() { this.expanded = !this.expanded; localStorage.setItem('snap_out_{{ $track->id }}', this.expanded); }
                                 }"
                            >
                                <div class="snapshot-header expense cursor-pointer hover:opacity-80 transition" @click="toggle()">
                                    <div class="flex items-center gap-2 flex-1">
                                        <svg class="icon-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                                        </svg>
                                        Pengeluaran
                                    </div>
                                    <svg class="icon-sm transform transition-transform duration-200" :class="expanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                                <div class="snapshot-body">
                                    <div x-cloak x-show="expanded" x-transition.duration.300ms class="space-y-1 mb-2">
                                        @forelse($expenses as $item)
                                            <div class="snapshot-row">
                                                <span class="snapshot-label">{{ $item['description'] ?? '-' }}</span>
                                                <span class="snapshot-value">Rp {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}</span>
                                            </div>
                                        @empty
                                            <div class="text-center text-gray-400 italic py-2">Tidak ada data pengeluaran</div>
                                        @endforelse
                                    </div>
                                    <div class="snapshot-total" style="color: #b91c1c;">
                                        <span>Total Pengeluaran</span>
                                        <span>Rp {{ number_format($expenseTotal, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="empty-state" style="padding: 1rem; background: none;">
                                <span class="no-changes">Data snapshot tidak tersedia</span>
                            </div>
                        @endif
                    </td>

                    <!-- Changes Summary -->
                    <td>
                        @if(!empty($track->changes_summary))
                            <div>
                                @foreach($track->changes_summary as $key => $change)
                                    <div class="detail-box">
                                        <div class="detail-title">
                                            {{ ucwords(str_replace(['field_', 'expense_item_', '_'], ['Field: ', 'Item: ', ' '], $key)) }}
                                        </div>
                                        <div class="detail-content">
                                            @if(is_array($change) && isset($change['old']) && isset($change['new']))
                                                <span class="val-old">
                                                    @if(is_numeric($change['old']))
                                                        {{ number_format($change['old'], 0, ',', '.') }}
                                                    @else
                                                        {{ \Illuminate\Support\Str::limit($change['old'], 40) }}
                                                    @endif
                                                </span>
                                                <svg class="arrow-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                </svg>
                                                <span class="val-new">
                                                    @if(is_numeric($change['new']))
                                                        {{ number_format($change['new'], 0, ',', '.') }}
                                                    @else
                                                        {{ \Illuminate\Support\Str::limit($change['new'], 40) }}
                                                    @endif
                                                </span>
                                            @elseif(is_array($change) && isset($change['new']))
                                                <span class="val-new">
                                                    Added:
                                                    @if(is_numeric($change['new']))
                                                        {{ number_format($change['new'], 0, ',', '.') }}
                                                    @else
                                                        {{ \Illuminate\Support\Str::limit($change['new'], 60) }}
                                                    @endif
                                                </span>
                                            @else
                                                <span class="text-break-all">{{ json_encode($change) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="no-changes">Tidak ada rincian perubahan</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="empty-state">
                        Belum ada riwayat tracking untuk data ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
