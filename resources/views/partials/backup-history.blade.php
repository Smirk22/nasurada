<div class="bg-white rounded-2xl p-5 shadow border border-gray-100 flex flex-col gap-4">
    <div>
        <h2 class="text-lg font-bold text-gray-800">Backup History</h2>
        <p class="text-xs text-gray-500 mt-1">Latest 20 backup create, download, restore, delete, and failure events.</p>
    </div>

    @if($backupHistory->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50">
                    <tr class="border-b border-gray-100 text-xs text-gray-500 uppercase font-semibold">
                        <th class="p-3">When</th>
                        <th class="p-3">User</th>
                        <th class="p-3">Action</th>
                        <th class="p-3">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @foreach($backupHistory as $event)
                        @php
                            $badgeClass = match($event->action) {
                                'Created Database Backup' => 'bg-green-100 text-green-800',
                                'Downloaded Database Backup' => 'bg-blue-100 text-blue-800',
                                'Restored Database Backup' => 'bg-amber-100 text-amber-800',
                                'Deleted Database Backup' => 'bg-red-100 text-red-800',
                                'Failed Database Backup Action' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-3 text-gray-700 whitespace-nowrap">
                                <div>{{ $event->created_at->format('M j, Y g:i A') }}</div>
                                <div class="text-[11px] text-gray-400">{{ $event->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <div class="font-semibold text-gray-800">{{ $event->user_name }}</div>
                                <div class="text-[11px] text-gray-400">{{ $event->role }}</div>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full {{ $badgeClass }}">{{ $event->action }}</span>
                            </td>
                            <td class="p-3 text-gray-500">{{ $event->details }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center border border-dashed border-gray-200 rounded-xl py-8 px-4">
            <p class="text-sm font-semibold text-gray-700">No backup history yet</p>
            <p class="text-xs text-gray-400 mt-1">Creating, downloading, restoring, or deleting a backup will appear here.</p>
        </div>
    @endif
</div>
