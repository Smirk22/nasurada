<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IETI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        .bg-ieti-green { background-color: #064e29; }
        .text-ieti-green { color: #064e29; }

        /* Slim scrollbars for the inner boxes (activity log) */
        .inner-scroll { scrollbar-width: thin; }
        .inner-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .inner-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 9999px; }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        @include('partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Header -->
            <header class="bg-white shadow px-8 py-6 border-b border-gray-200 shrink-0">
                <h1 id="adminPageTitle" class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
                <p id="adminPageSubtitle" class="text-sm text-gray-500 mt-1">System administration and staff account overview.</p>
            </header>

            <!-- Main Content -->
            <main class="flex-1 px-8 py-6 flex flex-col gap-4">

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl shadow-sm shrink-0">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any() && ! $errors->has('backup'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl shadow-sm shrink-0">
                        {{ $errors->first() }}
                    </div>
                @endif

                <!-- Overview tab -->
                <div id="admin-tab-overview" class="flex flex-col gap-4">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 shrink-0">
                    <div class="bg-white rounded-xl p-4 shadow border border-gray-100 flex flex-col justify-between">
                        <span class="text-xs font-semibold text-gray-500 uppercase">System Staff Users</span>
                        <div class="text-3xl font-extrabold text-ieti-green mt-1">{{ $totalUsers }}</div>
                        <span class="text-xs text-green-600 mt-1 font-medium">Admins, Registrars & Security</span>
                    </div>

                    <div class="bg-white rounded-xl p-4 shadow border border-gray-100 flex flex-col justify-between">
                        <span class="text-xs font-semibold text-gray-500 uppercase">Total Students</span>
                        <div class="text-3xl font-extrabold text-blue-600 mt-1">{{ $totalStudents }}</div>
                        <span class="text-xs text-blue-600 mt-1 font-medium">Enrolled & Unenrolled</span>
                    </div>

                    <div class="bg-white rounded-xl p-4 shadow border border-gray-100 flex flex-col justify-between">
                        <span class="text-xs font-semibold text-gray-500 uppercase">Invalid RFID Scans</span>
                        <div class="text-3xl font-extrabold text-red-600 mt-1">{{ $invalidRfidScans }}</div>
                        <span class="text-xs text-red-600 mt-1 font-medium">Security Alerts Logged Today</span>
                    </div>
                </div>

                <!-- RFID Insights -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 shrink-0">
                    <div class="bg-white rounded-xl p-4 shadow border border-gray-100 flex flex-col">
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">Student RFID Registration</h2>
                            <p class="text-xs text-gray-500">Students in the records with and without a registered RFID.</p>
                        </div>
                        <div class="relative h-64 mt-2">
                            <canvas id="studentRfidChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-4 shadow border border-gray-100 flex flex-col">
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">RFID Scan Validity</h2>
                            <p class="text-xs text-gray-500">Valid versus invalid RFID scans recorded in the system.</p>
                        </div>
                        <div class="relative h-64 mt-2">
                            <canvas id="rfidScanChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- System User Management -->
                <div class="bg-white rounded-2xl p-5 shadow border border-gray-100 flex flex-col shrink-0 gap-3">
                    <div class="flex justify-between items-center shrink-0">
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">User Account Management</h2>
                            <p class="text-xs text-gray-500">Manage staff access roles (Admin, Registrar, Security Guard).</p>
                        </div>
                        <button onclick="openModal('addUserModal')" class="bg-ieti-green hover:bg-green-800 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">
                            + Create Staff User
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-gray-50">
                                <tr class="border-b border-gray-100 text-xs text-gray-500 uppercase font-semibold">
                                    <th class="p-3">Name</th>
                                    <th class="p-3">Username</th>
                                    <th class="p-3">Email</th>
                                    <th class="p-3">Assigned Role</th>
                                    <th class="p-3">Temp Password</th>
                                    <th class="p-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                @foreach($users as $user)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="p-3 font-semibold text-gray-800">{{ $user->name }}</td>
                                        <td class="p-3 text-gray-600">{{ $user->username }}</td>
                                        <td class="p-3 text-gray-600">{{ $user->email }}</td>
                                        <td class="p-3">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                    {{ $user->role === 'Admin' ? 'bg-purple-100 text-purple-800' : ($user->role === 'Registrar' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                    {{ $user->role ?? 'Registrar' }}
                                                </span>
                                                @if ($user->must_reset_password)
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800" title="Admin must generate a temporary password to unlock this account">
                                                        Locked — reset password
                                                    </span>
                                                @elseif ($user->locked_until && $user->locked_until->isFuture())
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                                        Locked until {{ $user->locked_until->format('g:i A') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="p-3">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <button
                                                    type="button"
                                                    title="Generate temporary password"
                                                    aria-label="Generate temporary password"
                                                    onclick="openGenerateTempPasswordModal({{ $user->id }}, {{ json_encode($user->email) }}, {{ json_encode($user->username) }})"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-ieti-green hover:bg-green-800 text-white shadow-sm transition"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M15.75 1.5a6.75 6.75 0 0 0-6.651 7.906c.067.39-.032.717-.221.906l-6.5 6.499a3 3 0 0 0-.849 2.121v2.818h2.818a3 3 0 0 0 2.121-.85l6.499-6.5c.19-.189.517-.288.906-.22A6.75 6.75 0 1 0 15.75 1.5Zm0 3a.75.75 0 0 0 0 1.5A2.25 2.25 0 0 1 18 8.25a.75.75 0 0 0 1.5 0 3.75 3.75 0 0 0-3.75-3.75Z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                                @if ($user->temporary_password)
                                                    <code
                                                        data-temp-password="{{ $user->temporary_password }}"
                                                        class="temp-password-value px-2 py-1 bg-amber-50 border border-amber-200 rounded text-xs font-mono text-amber-900 select-all"
                                                    >{{ $user->temporary_password }}</code>
                                                    <button
                                                        type="button"
                                                        onclick="copyTableTempPassword(this)"
                                                        class="px-2 py-1 text-xs font-semibold bg-amber-600 hover:bg-amber-700 text-white rounded"
                                                    >
                                                        Copy
                                                    </button>
                                                @else
                                                    <span class="text-xs text-gray-400">No temp password yet</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="p-3 text-center space-x-2">
                                            <button onclick="editUser({{ json_encode($user) }})" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">Edit Role</button>
                                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this user?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-semibold ml-2">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Live Staff Activity Feed -->
                <div class="bg-white rounded-2xl p-5 shadow border border-gray-100 flex flex-col gap-3">
                    <div class="flex justify-between items-center shrink-0">
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">Live Activity Monitor</h2>
                            <p class="text-xs text-gray-500">Real-time audit log of Registrar & Security actions.</p>
                        </div>
                        <span class="text-xs font-semibold text-gray-400 bg-gray-100 px-2.5 py-1 rounded-full">
                            {{ $activities->count() }} Total Logs
                        </span>
                    </div>

                    <!-- Scrollable log container -->
                    <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto pr-2 inner-scroll">
                        @forelse($activities as $act)
                            <div class="py-3 flex justify-between items-center text-sm">
                                <div class="flex items-center space-x-3">
                                    <span class="font-bold text-xs px-2.5 py-1 rounded-md
                                        {{ strtolower($act->role) === 'admin' ? 'bg-purple-100 text-purple-800' : (strtolower($act->role) === 'registrar' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800') }}">
                                        {{ $act->role }}
                                    </span>
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ $act->user_name }} &mdash; <span class="font-normal text-gray-600">{{ $act->action }}</span></p>
                                        <p class="text-xs text-gray-500">{{ $act->details }}</p>
                                    </div>
                                </div>
                                <span class="text-xs text-gray-400 font-mono whitespace-nowrap ml-4">{{ $act->created_at->diffForHumans() }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 py-6 text-center">No recent staff activity logged.</p>
                        @endforelse
                    </div>
                </div>
                </div>

                <!-- Backups tab -->
                <div id="admin-tab-backups" class="hidden flex flex-col gap-4">
                <!-- Database Backups -->
                <div id="backups" class="bg-white rounded-2xl p-5 shadow border border-gray-100 flex flex-col shrink-0 gap-4">
                    @php($lastBackup = $backupFiles[0] ?? null)

                    <div class="flex flex-wrap justify-between items-start gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-bold text-gray-800">Database Backups</h2>
                                @if (($backupStatus ?? 'never') === 'fresh')
                                    <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-green-100 text-green-800">Up to date</span>
                                @elseif (($backupStatus ?? 'never') === 'stale')
                                    <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-orange-100 text-orange-800">Backup overdue</span>
                                @else
                                    <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-red-100 text-red-800">No backup yet</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                ZIP snapshots of the SQLite database
                                @if (!empty($backupIncludesPhotos)), student photos @endif
                                @if (!empty($backupIncludesEnv)), and .env @endif.
                            </p>
                        </div>
                        <button
                            type="button"
                            onclick="openBackupCreateModal()"
                            class="bg-ieti-green hover:bg-green-800 text-white font-semibold px-4 py-2 rounded-lg text-sm transition"
                        >
                            + Backup Now
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-3">
                            <p class="text-[11px] font-semibold uppercase text-gray-500">Last Backup</p>
                            <p class="mt-1 text-sm font-bold text-gray-800">
                                {{ $lastBackup ? $lastBackup['created_at']->diffForHumans() : 'Never' }}
                            </p>
                            <p class="text-[11px] text-gray-500">
                                {{ $lastBackup ? $lastBackup['created_at']->format('M j, Y g:i A') : 'Create your first backup' }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-3">
                            <p class="text-[11px] font-semibold uppercase text-gray-500">Storage Used</p>
                            <p class="mt-1 text-sm font-bold text-gray-800">{{ $backupTotalSize }}</p>
                            <p class="text-[11px] text-gray-500">{{ count($backupFiles) }} archive(s) saved</p>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-3">
                            <p class="text-[11px] font-semibold uppercase text-gray-500">Retention</p>
                            <p class="mt-1 text-sm font-bold text-gray-800">Keep {{ $backupKeep ?? (int) config('backup.keep', 14) }} newest</p>
                            <p class="text-[11px] text-gray-500">Older archives are pruned automatically</p>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-3">
                            <p class="text-[11px] font-semibold uppercase text-gray-500">Database</p>
                            <p class="mt-1 text-sm font-bold text-gray-800 uppercase">{{ $backupDriver ?? config('database.default') }}</p>
                            <p class="text-[11px] text-gray-500">Automatic backups support SQLite</p>
                        </div>
                    </div>

                    @if ($errors->has('backup'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl text-sm">
                            {{ $errors->first('backup') }}
                        </div>
                    @endif

                    @if(count($backupFiles))
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-gray-50">
                                    <tr class="border-b border-gray-100 text-xs text-gray-500 uppercase font-semibold">
                                        <th class="p-3">Created</th>
                                        <th class="p-3">File</th>
                                        <th class="p-3">Size</th>
                                        <th class="p-3 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-sm">
                                    @foreach($backupFiles as $backup)
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="p-3 text-gray-700 whitespace-nowrap">
                                                <div>{{ $backup['created_at']->format('M j, Y g:i A') }}</div>
                                                <div class="text-[11px] text-gray-400">{{ $backup['created_at']->diffForHumans() }}</div>
                                            </td>
                                            <td class="p-3 font-mono text-xs text-gray-600 break-all">{{ $backup['name'] }}</td>
                                            <td class="p-3 text-gray-600 whitespace-nowrap">{{ $backup['size_human'] }}</td>
                                            <td class="p-3 text-center whitespace-nowrap">
                                                <div class="inline-flex items-center gap-2">
                                                    <a href="{{ route('admin.backups.download', $backup['name']) }}" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">Download</a>
                                                    <button
                                                        type="button"
                                                        class="text-ieti-green hover:text-green-800 text-xs font-semibold"
                                                        onclick="openBackupRestoreModal({{ json_encode($backup['name']) }})"
                                                    >Restore</button>
                                                    <button
                                                        type="button"
                                                        class="text-red-600 hover:text-red-800 text-xs font-semibold"
                                                        onclick="openBackupDeleteModal({{ json_encode($backup['name']) }})"
                                                    >Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center border border-dashed border-gray-200 rounded-xl py-8 px-4">
                            <p class="text-sm font-semibold text-gray-700">No backups yet</p>
                            <p class="text-xs text-gray-400 mt-1">Create a snapshot before making major changes or at the end of each school day.</p>
                            <button type="button" onclick="openBackupCreateModal()" class="mt-3 text-xs font-semibold text-ieti-green hover:underline">
                                Create first backup
                            </button>
                        </div>
                    @endif

                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900 space-y-1">
                        <p>
                            Backups are stored on this computer only, in
                            <code class="font-mono">{{ $backupDirectory }}</code>.
                            Download copies regularly to an external drive or cloud folder.
                        </p>
                        <p>
                            Restore from this panel replaces the live database (a safety snapshot is saved first).
                            For a daily 11:00 PM backup, run <code class="font-mono">install-backup-task.bat</code> once
                            or keep <code class="font-mono">php artisan schedule:work</code> running.
                        </p>
                    </div>
                </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Create Staff Account</h3>
            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-3">
                @csrf
                <input type="text" name="name" placeholder="Full Name" required class="w-full border p-2 rounded text-sm">
                <input type="text" name="username" placeholder="Username" required class="w-full border p-2 rounded text-sm">
                <input type="email" name="email" placeholder="Email Address" required class="w-full border p-2 rounded text-sm">
                <input type="password" name="password" placeholder="Password" required class="w-full border p-2 rounded text-sm">
                <p class="text-xs text-gray-500">Strong password required: at least 12 characters, with uppercase, lowercase, a number, and a symbol.</p>
                <div>
                    <label class="text-xs text-gray-500 font-semibold">Assign Role</label>
                    <select name="role" class="w-full border p-2 rounded text-sm mt-1">
                        <option value="Registrar">Registrar</option>
                        <option value="Security">Security Guard</option>
                        <option value="Admin">Administrator</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="closeModal('addUserModal')" class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-ieti-green text-white rounded-lg font-semibold">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Edit Staff Account</h3>
            <form id="editUserForm" method="POST" class="space-y-3">
                @csrf
                @method('PUT')
                <input type="text" id="edit_user_name" name="name" required class="w-full border p-2 rounded text-sm">
                <input type="text" id="edit_user_username" name="username" required class="w-full border p-2 rounded text-sm">
                <input type="email" id="edit_user_email" name="email" required class="w-full border p-2 rounded text-sm">
                <p id="edit_user_lock_hint" class="hidden text-xs text-red-600 font-semibold">This account is locked. Use the key icon in the Temp Password column to generate a temporary password and unlock login.</p>
                <div>
                    <label class="text-xs text-gray-500 font-semibold">Assign Role</label>
                    <select id="edit_user_role" name="role" class="w-full border p-2 rounded text-sm mt-1">
                        <option value="Registrar">Registrar</option>
                        <option value="Security">Security Guard</option>
                        <option value="Admin">Administrator</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="closeModal('editUserModal')" class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-ieti-green text-white rounded-lg font-semibold">Update User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Backup Create Confirm Modal -->
    <div id="backupCreateModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
            <h3 class="text-lg font-bold text-gray-800">Create backup now?</h3>
            <p class="text-sm text-gray-600 mt-2">
                This will create a ZIP snapshot of the live database
                @if (!empty($backupIncludesPhotos)), student photos @endif
                @if (!empty($backupIncludesEnv)), and .env @endif.
                It may take a moment.
            </p>
            <form id="backupCreateForm" action="{{ route('admin.backups.store') }}" method="POST" class="flex justify-end gap-2 mt-5" onsubmit="return lockBackupSubmit(this, 'Creating…')">
                @csrf
                <button type="button" onclick="closeModal('backupCreateModal')" class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm bg-ieti-green text-white rounded-lg font-semibold">Create Backup</button>
            </form>
        </div>
    </div>

    <!-- Backup Delete Confirm Modal -->
    <div id="backupDeleteModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
            <h3 class="text-lg font-bold text-gray-800">Delete this backup?</h3>
            <p class="text-sm text-gray-600 mt-2">
                <code id="backupDeleteName" class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded"></code>
                will be permanently removed. This cannot be undone.
            </p>
            <form id="backupDeleteForm" method="POST" class="flex justify-end gap-2 mt-5">
                @csrf
                @method('DELETE')
                <button type="button" onclick="closeModal('backupDeleteModal')" class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold">Delete Backup</button>
            </form>
        </div>
    </div>

    <!-- Backup Restore Confirm Modal -->
    <div id="backupRestoreModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
            <h3 class="text-lg font-bold text-gray-800">Restore from backup?</h3>
            <p class="text-sm text-gray-600 mt-2">
                This replaces the live database with
                <code id="backupRestoreName" class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded"></code>.
                A safety snapshot of the current database is saved first. You will be signed out after restore.
            </p>
            <form id="backupRestoreForm" method="POST" class="space-y-3 mt-4" onsubmit="return lockBackupSubmit(this, 'Restoring…')">
                @csrf
                <div>
                    <label for="backupRestoreConfirmation" class="text-xs font-semibold text-gray-600">Type <span class="font-mono">RESTORE</span> to confirm</label>
                    <input
                        type="text"
                        id="backupRestoreConfirmation"
                        name="confirmation"
                        required
                        autocomplete="off"
                        class="mt-1 w-full border p-2 rounded text-sm font-mono"
                        placeholder="RESTORE"
                    >
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal('backupRestoreModal')" class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-ieti-green text-white rounded-lg font-semibold">Restore System</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Generate Temporary Password Confirm Modal -->
    <div id="generateTempPasswordModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
            <div class="flex items-start gap-3 mb-4">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100 text-ieti-green shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5" aria-hidden="true">
                        <path fill-rule="evenodd" d="M15.75 1.5a6.75 6.75 0 0 0-6.651 7.906c.067.39-.032.717-.221.906l-6.5 6.499a3 3 0 0 0-.849 2.121v2.818h2.818a3 3 0 0 0 2.121-.85l6.499-6.5c.19-.189.517-.288.906-.22A6.75 6.75 0 1 0 15.75 1.5Zm0 3a.75.75 0 0 0 0 1.5A2.25 2.25 0 0 1 18 8.25a.75.75 0 0 0 1.5 0 3.75 3.75 0 0 0-3.75-3.75Z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Generate temporary password?</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        A new strong temporary password will be created for
                        <strong id="generateTempPasswordEmail"></strong>
                        (<span id="generateTempPasswordUsernameWrap" class="hidden">(<span id="generateTempPasswordUsername"></span>)</span>.
                    </p>
                    <p class="text-xs text-gray-500 mt-2">It will appear in the Temp Password column so you can copy and give it to the user. Any previous temporary password will be replaced.</p>
                </div>
            </div>
            <form id="generateTempPasswordForm" method="POST" class="flex justify-end space-x-2 mt-4">
                @csrf
                <button type="button" onclick="closeModal('generateTempPasswordModal')" class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm bg-ieti-green text-white rounded-lg font-semibold">Generate Password</button>
            </form>
        </div>
    </div>

    <!-- Modal Scripts -->
    <script>
        function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
        function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
        function lockBackupSubmit(form, label) {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.textContent = label || 'Please wait…';
            }
            return true;
        }
        function openBackupCreateModal() {
            openModal('backupCreateModal');
        }
        function openBackupDeleteModal(fileName) {
            document.getElementById('backupDeleteName').textContent = fileName;
            document.getElementById('backupDeleteForm').action = '/admin/backups/' + encodeURIComponent(fileName);
            openModal('backupDeleteModal');
        }
        function openBackupRestoreModal(fileName) {
            document.getElementById('backupRestoreName').textContent = fileName;
            document.getElementById('backupRestoreForm').action = '/admin/backups/' + encodeURIComponent(fileName) + '/restore';
            document.getElementById('backupRestoreConfirmation').value = '';
            openModal('backupRestoreModal');
            setTimeout(function () {
                document.getElementById('backupRestoreConfirmation').focus();
            }, 50);
        }
        function markTempPasswordCopied(button) {
            const original = button.getAttribute('data-original-label') || button.textContent.trim() || 'Copy';
            button.setAttribute('data-original-label', original);
            button.textContent = 'Copied';
            setTimeout(function () { button.textContent = original; }, 1500);
        }

        function fallbackCopyText(value) {
            const textarea = document.createElement('textarea');
            textarea.value = value;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.top = '0';
            textarea.style.left = '0';
            textarea.style.width = '1px';
            textarea.style.height = '1px';
            textarea.style.padding = '0';
            textarea.style.border = 'none';
            textarea.style.outline = 'none';
            textarea.style.boxShadow = 'none';
            textarea.style.background = 'transparent';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            textarea.setSelectionRange(0, value.length);

            let copied = false;
            try {
                copied = document.execCommand('copy');
            } catch (e) {
                copied = false;
            }

            document.body.removeChild(textarea);
            return copied;
        }

        function copyTableTempPassword(button) {
            const code = button.parentElement
                ? button.parentElement.querySelector('.temp-password-value')
                : null;
            const value = (code && (code.getAttribute('data-temp-password') || code.textContent) || '').trim();

            if (!value) {
                button.textContent = 'Failed';
                setTimeout(function () { button.textContent = 'Copy'; }, 1500);
                return;
            }

            function onSuccess() { markTempPasswordCopied(button); }
            function onFail() {
                button.textContent = 'Failed';
                setTimeout(function () { button.textContent = 'Copy'; }, 1500);
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(value).then(onSuccess).catch(function () {
                    if (fallbackCopyText(value)) {
                        onSuccess();
                    } else {
                        onFail();
                    }
                });
                return;
            }

            if (fallbackCopyText(value)) {
                onSuccess();
            } else {
                onFail();
            }
        }
        function openGenerateTempPasswordModal(userId, email, username) {
            document.getElementById('generateTempPasswordForm').action = '/admin/users/' + userId + '/temporary-password';
            document.getElementById('generateTempPasswordEmail').textContent = email || '';

            const usernameWrap = document.getElementById('generateTempPasswordUsernameWrap');
            const usernameEl = document.getElementById('generateTempPasswordUsername');
            if (username) {
                usernameEl.textContent = username;
                usernameWrap.classList.remove('hidden');
            } else {
                usernameWrap.classList.add('hidden');
            }

            openModal('generateTempPasswordModal');
        }
        function editUser(user) {
            document.getElementById('editUserForm').action = '/admin/users/' + user.id;
            document.getElementById('edit_user_name').value = user.name || '';
            document.getElementById('edit_user_username').value = user.username || '';
            document.getElementById('edit_user_email').value = user.email || '';
            document.getElementById('edit_user_role').value = user.role || 'Registrar';

            const lockHint = document.getElementById('edit_user_lock_hint');
            if (user.must_reset_password) {
                lockHint.classList.remove('hidden');
            } else {
                lockHint.classList.add('hidden');
            }

            openModal('editUserModal');
        }

        function setAdminTab(tab) {
            const overview = document.getElementById('admin-tab-overview');
            const backups = document.getElementById('admin-tab-backups');
            const title = document.getElementById('adminPageTitle');
            const subtitle = document.getElementById('adminPageSubtitle');
            const showBackups = tab === 'backups';

            if (overview) overview.classList.toggle('hidden', showBackups);
            if (backups) backups.classList.toggle('hidden', !showBackups);

            if (title) title.textContent = showBackups ? 'Database Backups' : 'Admin Dashboard';
            if (subtitle) {
                subtitle.textContent = showBackups
                    ? 'Create, download, restore, and manage system backup archives.'
                    : 'System administration and staff account overview.';
            }

            document.querySelectorAll('.admin-tab-link').forEach(function (link) {
                const active = link.getAttribute('data-admin-tab') === tab;
                link.classList.toggle('bg-green-700', active);
            });

            if (showBackups && window.location.hash !== '#backups') {
                history.replaceState(null, '', '#backups');
            } else if (!showBackups && (window.location.hash === '#backups' || window.location.hash === '#overview')) {
                history.replaceState(null, '', window.location.pathname + window.location.search);
            }
        }

        function syncAdminTabFromHash() {
            setAdminTab(window.location.hash === '#backups' ? 'backups' : 'overview');
        }

        document.querySelectorAll('.admin-tab-link').forEach(function (link) {
            link.addEventListener('click', function (event) {
                if (!document.getElementById('admin-tab-overview')) return;

                const tab = link.getAttribute('data-admin-tab');
                if (tab !== 'overview' && tab !== 'backups') return;

                event.preventDefault();
                if (tab === 'backups') {
                    history.pushState(null, '', '#backups');
                } else {
                    history.pushState(null, '', window.location.pathname + window.location.search);
                }
                setAdminTab(tab);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });

        window.addEventListener('hashchange', syncAdminTabFromHash);
        syncAdminTabFromHash();

        // Reload if the browser restores this page from its back/forward cache
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) { window.location.reload(); }
        });

        // RFID pie charts
        new Chart(document.getElementById('studentRfidChart'), {
            type: 'pie',
            data: {
                labels: ['With Registered RFID', 'Without Registered RFID'],
                datasets: [{
                    data: [{{ (int) $studentsWithRfid }}, {{ (int) $studentsWithoutRfid }}],
                    backgroundColor: ['#16a34a', '#f59e0b'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        new Chart(document.getElementById('rfidScanChart'), {
            type: 'pie',
            data: {
                labels: ['Valid Scans', 'Invalid Scans'],
                datasets: [{
                    data: [{{ (int) $validScans }}, {{ (int) $invalidScans }}],
                    backgroundColor: ['#2563eb', '#dc2626'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>

</body>
</html>
