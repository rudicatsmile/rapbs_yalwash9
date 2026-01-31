<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">ID</p>
            <p class="text-base text-gray-900 dark:text-white">{{ $activity->id }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Event</p>
            <p class="text-base text-gray-900 dark:text-white">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ match($activity->event) {
                    'created' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                    'updated' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                    'deleted' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                } }}">
                    {{ ucfirst($activity->event) }}
                </span>
            </p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">User</p>
            <p class="text-base text-gray-900 dark:text-white">{{ $activity->causer?->name ?? 'System' }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Date</p>
            <p class="text-base text-gray-900 dark:text-white">{{ $activity->created_at->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Log Name</p>
            <p class="text-base text-gray-900 dark:text-white">{{ $activity->log_name }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Subject Type</p>
            <p class="text-base text-gray-900 dark:text-white">{{ class_basename($activity->subject_type) }}</p>
        </div>
    </div>

    <div>
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Description</p>
        <p class="text-base text-gray-900 dark:text-white">{{ $activity->description }}</p>
    </div>

    @if($activity->properties)
        <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Activity Details</h4>
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 space-y-3">
                @foreach($activity->properties as $key => $value)
                    <div class="border-b border-gray-200 dark:border-gray-700 last:border-0 pb-3 last:pb-0">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">{{ str_replace('_', ' ', $key) }}</p>
                        <div class="mt-1">
                            @if(is_array($value))
                                <details class="cursor-pointer">
                                    <summary class="text-sm text-gray-700 dark:text-gray-300">Show details...</summary>
                                    <pre class="mt-2 text-xs bg-white dark:bg-gray-800 rounded p-2 overflow-auto"><code>{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                </details>
                            @else
                                <p class="text-sm text-gray-900 dark:text-white break-all">{{ $value }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
