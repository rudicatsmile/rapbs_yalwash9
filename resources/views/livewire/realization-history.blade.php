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
            font-size: 0.875rem;
            text-align: left;
            color: #6b7280;
        }
        .history-modal-container thead {
            background-color: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            text-transform: uppercase;
            font-size: 0.75rem;
            color: #374151;
        }
        .history-modal-container th {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.05em;
        }
        .history-modal-container tbody tr {
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.15s;
        }
        .history-modal-container tbody tr:last-child {
            border-bottom: none;
        }
        .history-modal-container tbody tr:hover {
            background-color: #f9fafb;
        }
        .history-modal-container td {
            padding: 1rem 1.5rem;
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

        /* Soft Delete Styles */
        .deleted-row {
            background-color: #fef2f2 !important;
            opacity: 0.75;
        }
        .toggle-container {
            padding: 0.75rem 0;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        .action-btn {
            width: 2.25rem;
            height: 2.25rem;
            padding: 0;
            border-radius: 50%;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        .icon-btn-svg {
            width: 1.4rem;
            height: 1.4rem;
        }
        .btn-delete {
            color: #b91c1c;
            background-color: #fee2e2;
            border-color: #fca5a5;
        }
        .btn-delete:hover {
            background-color: #fecaca;
        }
        .btn-restore {
            color: #047857;
            background-color: #d1fae5;
            border-color: #6ee7b7;
        }
        .btn-restore:hover {
            background-color: #a7f3d0;
        }
    </style>

    <div class="toggle-container">
        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
            <input type="checkbox" wire:click="toggleShowDeleted" @if($showDeleted) checked @endif class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
            <span>Tampilkan Data Terhapus</span>
        </label>
    </div>

    <table>
        <thead>
            <tr>
                <th scope="col" style="width: 15%;">Waktu</th>
                <th scope="col" style="width: 40%;">Data Realisasi</th>
                <th scope="col" style="width: 35%;">Detail Perubahan</th>
                <th scope="col" style="width: 10%;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tracks as $track)
                <tr class="{{ $track->trashed() ? 'deleted-row' : '' }}">
                    <!-- Date & Time -->
                    <td class="whitespace-nowrap">
                        <div class="date-container">
                            <span class="date-text">{{ $track->created_at->format('d M Y') }}</span>
                            <span class="time-text">{{ $track->created_at->format('H:i') }}</span>
                            <span class="time-text">{{ $track->creator->name ?? 'System' }}</span>
                            @if($track->trashed())
                                <span class="text-xs text-red-600 font-bold mt-1 uppercase tracking-wider">(Deleted)</span>
                            @endif
                        </div>
                    </td>

                    <!-- Snapshot Data -->
                    <td>
                        @if(!empty($track->snapshot_data) && is_array($track->snapshot_data))
                            @php
                                $snap = $track->snapshot_data;
                                $realizationTotal = $snap['total_realization'] ?? 0;
                                $balanceTotal = $snap['total_balance'] ?? 0;
                            @endphp

                            <!-- Realization Section -->
                            <div class="snapshot-card">
                                <div class="snapshot-header income">
                                    <div class="flex items-center gap-2 flex-1">
                                        <svg class="icon-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        Realisasi
                                    </div>
                                </div>
                                <div class="snapshot-body">
                                    <div class="snapshot-total" style="color: #047857; margin-top: 0; border-top: none; padding-top: 0;">
                                        <span>Total Realisasi</span>
                                        <span>Rp {{ number_format($realizationTotal, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Balance Section -->
                            <div class="snapshot-card">
                                <div class="snapshot-header expense">
                                    <div class="flex items-center gap-2 flex-1">
                                        <svg class="icon-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Sisa Saldo
                                    </div>
                                </div>
                                <div class="snapshot-body">
                                    <div class="snapshot-total" style="color: #b91c1c; margin-top: 0; border-top: none; padding-top: 0;">
                                        <span>Total Saldo</span>
                                        <span>Rp {{ number_format($balanceTotal, 0, ',', '.') }}</span>
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
                            <div x-data="{
                                expanded: localStorage.getItem('changes_realization_{{ $track->id }}') === 'true',
                                toggle() { this.expanded = !this.expanded; localStorage.setItem('changes_realization_{{ $track->id }}', this.expanded); }
                            }">
                                <button type="button" @click="toggle()" class="flex items-center gap-2 w-full text-left text-xs font-semibold text-gray-600 hover:text-gray-800 focus:outline-none mb-2 bg-gray-50 p-2 rounded border border-gray-200">
                                    <span>{{ count($track->changes_summary) }} Perubahan</span>
                                     <svg class="icon-sm transform transition-transform duration-200" :class="expanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div x-cloak x-show="expanded" x-transition.duration.300ms>
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
                            </div>
                        @else
                            <span class="no-changes">Tidak ada rincian perubahan</span>
                        @endif
                    </td>

                    <!-- Actions -->
                    <td class="whitespace-nowrap">
                        @if($track->trashed())
                            <button type="button" wire:click="restore({{ $track->id }})" wire:confirm="Apakah Anda yakin ingin mengembalikan data ini?" class="action-btn btn-restore" title="Restore Data">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="icon-btn-svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                </svg>
                            </button>
                        @else
                            <button type="button" wire:click="delete({{ $track->id }})" wire:confirm="Apakah Anda yakin ingin menghapus data ini secara soft delete?" class="action-btn btn-delete" title="Hapus Data">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="icon-btn-svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="empty-state">
                        Belum ada riwayat realisasi untuk data ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $tracks->links() }}
    </div>
</div>
