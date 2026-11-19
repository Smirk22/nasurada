<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - {{ $settings['site_name'] }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        .bg-ieti-green { background-color: #064e29; }
        .text-ieti-green { color: #064e29; }
        .inner-scroll { scrollbar-width: thin; }
        .inner-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .inner-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 9999px; }
    </style>
</head>
<body class="bg-gray-100 font-sans">

<div class="flex min-h-screen">
    @include('partials.sidebar')

    <div class="flex-1 flex flex-col min-w-0">
        <header class="bg-white shadow px-8 py-6 border-b border-gray-200 shrink-0">
            <div class="flex flex-wrap justify-between items-center gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Super Admin Control Center</h1>
                    <p class="text-sm text-gray-500 mt-1">Full system overview, accounts, audit trail, backups and settings.</p>
                </div>
                <div class="text-right">
                    <span class="inline-block px-3 py-1 rounded-full bg-indigo-100 text-indigo-800 text-xs font-bold uppercase tracking-wide">Super Admin</span>
                    <p class="text-xs text-gray-500 mt-1">{{ auth()->user()->name }} ({{ auth()->user()->username }})</p>
                </div>
            </div>
        </header>

        <main class="flex-1 px-8 py-6 flex flex-col gap-4">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl shadow-sm shrink-0">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl shadow-sm shrink-0 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            @if($settings['maintenance_mode'])
                <div class="bg-amber-100 border border-amber-400 text-amber-900 px-4 py-3 rounded-xl shadow-sm shrink-0 text-sm">
                    <strong>Maintenance mode is ON.</strong> Only Super Admins can sign in right now. Turn it off in the Settings tab when you are done.
                </div>
            @endif

            <!-- Tabs -->
            <div class="flex flex-wrap gap-2 shrink-0">
                @foreach(['overview' => 'Overview', 'accounts' => 'Accounts', 'activity' => 'Audit Log', 'backups' => 'Backups', 'settings' => 'Settings'] as $key => $label)
                    <a href="#{{ $key }}" data-sa-tab="{{ $key }}" class="px-4 py-2 rounded-lg text-sm font-semibold transition bg-white text-gray-600 hover:bg-gray-200 border border-gray-200">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <!-- Overview -->
            <section id="tab-overview" class="flex flex-col gap-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 shrink-0">
                    <div class="bg-white rounded-xl p-4 shadow border border-gray-100">
                        <span class="text-xs font-semibold text-gray-500 uppercase">Accounts</span>
                        <div class="text-3xl font-extrabold text-ieti-green mt-1">{{ $stats['accounts'] }}</div>
                        <span class="text-xs text-gray-500 mt-1 font-medium">All staff</span>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow border border-gray-100">
                        <span class="text-xs font-semibold text-gray-500 uppercase">Super Admins</span>
                        <div class="text-3xl font-extrabold text-indigo-600 mt-1">{{ $stats['superadmins'] }}</div>
                        <span class="text-xs text-gray-500 mt-1 font-medium">Highest privilege</span>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow border border-gray-100">
                        <span class="text-xs font-semibold text-gray-500 uppercase">Students</span>
                        <div class="text-3xl font-extrabold text-blue-600 mt-1">{{ $stats['students'] }}</div>
                        <span class="text-xs text-gray-500 mt-1 font-medium">Enrolled & unenrolled</span>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow border border-gray-100">
                        <span class="text-xs font-semibold text-gray-500 uppercase">Entries Today</span>
                        <div class="text-3xl font-extrabold text-emerald-700 mt-1">{{ $stats['entries_today'] }}</div>
                        <span class="text-xs text-gray-500 mt-1 font-medium">All scan statuses</span>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow border border-gray-100">
                        <span class="text-xs font-semibold text-gray-500 uppercase">Alerts Today</span>
                        <div class="text-3xl font-extrabold text-red-600 mt-1">{{ $stats['alerts_today'] }}</div>
                        <span class="text-xs text-red-600 mt-1 font-medium">Denied / unknown / expired</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 shrink-0">
                    <div class="bg-white rounded-xl p-4 shadow border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-800">Granted Entries (Last 7 Days)</h2>
                        <p class="text-xs text-gray-500">System-wide gate activity.</p>
                        <div class="relative h-64 mt-2"><canvas id="saEntriesChart"></canvas></div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-800">Staff by Role</h2>
                        <p class="text-xs text-gray-500">Account distribution across the system.</p>
                        <div class="relative h-64 mt-2"><canvas id="saRolesChart"></canvas></div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow border border-gray-100">
                    <h2 class="text-lg font-bold text-gray-800">System Health</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3">
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-3">
                            <p class="text-[11px] font-semibold uppercase text-gray-500">Locked Accounts</p>
                            <p class="mt-1 text-sm font-bold text-gray-800">{{ $stats['locked_accounts'] }}</p>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-3">
                            <p class="text-[11px] font-semibold uppercase text-gray-500">Latest Backup</p>
                            <p class="mt-1 text-sm font-bold text-gray-800">
                                {{ $backupFiles ? $backupFiles[0]['created_at']->diffForHumans() : 'Never' }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-3">
                            <p class="text-[11px] font-semibold uppercase text-gray-500">Maintenance Mode</p>
                            <p class="mt-1 text-sm font-bold {{ $settings['maintenance_mode'] ? 'text-amber-600' : 'text-emerald-700' }}">
                                {{ $settings['maintenance_mode'] ? 'Enabled' : 'Disabled' }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Accounts -->
            <section id="tab-accounts" class="hidden flex-col gap-4">
                <div class="bg-white rounded-2xl p-5 shadow border border-gray-100 flex flex-col gap-3">
                    <div class="flex flex-wrap justify-between items-center gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">Full Account & Role Management</h2>
                            <p class="text-xs text-gray-500">Manage every account, including Admins and other Super Admins.</p>
                        </div>
                        <button onclick="openSaModal('saAddUserModal')" class="bg-ieti-green hover:bg-green-800 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">
                            + Create Account
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-gray-50">
                                <tr class="border-b border-gray-100 text-xs text-gray-500 uppercase font-semibold">
                                    <th class="p-3">Name</th>
                                    <th class="p-3">Username</th>
                                    <th class="p-3">Email</th>
                                    <th class="p-3">Role</th>
                                    <th class="p-3">Status</th>
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
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                {{ $user->role === 'SuperAdmin' ? 'bg-indigo-100 text-indigo-800' : ($user->role === 'Admin' ? 'bg-purple-100 text-purple-800' : ($user->role === 'Registrar' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800')) }}">
                                                {{ $user->role ?? 'Registrar' }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-xs">
                                            @if($user->must_reset_password)
                                                <span class="px-2 py-1 rounded-full bg-red-100 text-red-800 font-semibold">Locked — reset required</span>
                                            @elseif($user->locked_until && $user->locked_until->isFuture())
                                                <span class="px-2 py-1 rounded-full bg-orange-100 text-orange-800 font-semibold">Locked until {{ $user->locked_until->format('g:i A') }}</span>
                                            @else
                                                <span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-800 font-semibold">Active</span>
                                            @endif
                                        </td>
                                        <td class="p-3">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <button type="button" title="Generate temporary password" onclick="openTempPasswordModal({{ $user->id }}, {{ json_encode($user->email) }})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-ieti-green hover:bg-green-800 text-white shadow-sm transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4" aria-hidden="true"><path fill-rule="evenodd" d="M15.75 1.5a6.75 6.75 0 0 0-6.651 7.906c.067.39-.032.717-.221.906l-6.5 6.499a3 3 0 0 0-.849 2.121v2.818h2.818a3 3 0 0 0 2.121-.85l6.499-6.5c.19-.189.517-.288.906-.22A6.75 6.75 0 1 0 15.75 1.5Zm0 3a.75.75 0 0 0 0 1.5A2.25 2.25 0 0 1 18 8.25a.75.75 0 0 0 1.5 0 3.75 3.75 0 0 0-3.75-3.75Z" clip-rule="evenodd" /></svg>
                                                </button>
                                                @if($user->temporary_password)
                                                    <code class="px-2 py-1 bg-amber-50 border border-amber-200 rounded text-xs font-mono text-amber-900 select-all">{{ $user->temporary_password }}</code>
                                                @else
                                                    <span class="text-xs text-gray-400">None</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="p-3 text-center whitespace-nowrap">
                                            <button onclick="editSaUser({{ json_encode($user) }})" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">Edit Role</button>
                                            <form action="{{ route('superadmin.users.destroy', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete {{ $user->username }}? This cannot be undone.');">
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
            </section>

            <!-- Audit Log -->
            <section id="tab-activity" class="hidden flex-col gap-4">
                <div class="bg-white rounded-2xl p-5 shadow border border-gray-100 flex flex-col gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">System-Wide Audit Log</h2>
                        <p class="text-xs text-gray-500">Every staff action recorded across all dashboards.</p>
                    </div>

                    <form method="GET" action="{{ route('superadmin.dashboard') }}#activity" class="flex flex-wrap items-end gap-3">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Search</label>
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="User, action or details" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Role</label>
                            <select name="role" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">All roles</option>
                                @foreach(['SuperAdmin', 'Admin', 'Registrar', 'Security'] as $role)
                                    <option value="{{ $role }}" {{ request('role') === $role ? 'selected' : '' }}>{{ $role }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-ieti-green text-white rounded-lg text-sm font-semibold hover:opacity-90">Filter</button>
                        @if(request('q') || request('role'))
                            <a href="{{ route('superadmin.dashboard') }}#activity" class="px-4 py-2 text-sm text-gray-500 hover:underline">Clear</a>
                        @endif
                    </form>

                    <div class="divide-y divide-gray-100 max-h-[32rem] overflow-y-auto pr-2 inner-scroll">
                        @forelse($activities as $act)
                            <div class="py-3 flex justify-between items-center text-sm">
                                <div class="flex items-center space-x-3">
                                    <span class="font-bold text-xs px-2.5 py-1 rounded-md
                                        {{ strtolower($act->role) === 'superadmin' ? 'bg-indigo-100 text-indigo-800' : (strtolower($act->role) === 'admin' ? 'bg-purple-100 text-purple-800' : (strtolower($act->role) === 'registrar' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800')) }}">
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
                            <p class="text-xs text-gray-400 py-6 text-center">No activity matches these filters.</p>
                        @endforelse
                    </div>

                    <div>{{ $activities->links() }}</div>
                </div>
            </section>

            <!-- Backups -->
            <section id="tab-backups" class="hidden flex-col gap-4">
                <div class="bg-white rounded-2xl p-5 shadow border border-gray-100 flex flex-col gap-4">
                    <div class="flex flex-wrap justify-between items-start gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-bold text-gray-800">Database Backups</h2>
                                @if($backupStatus === 'fresh')
                                    <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-green-100 text-green-800">Up to date</span>
                                @elseif($backupStatus === 'stale')
                                    <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-orange-100 text-orange-800">Backup overdue</span>
                                @else
                                    <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-red-100 text-red-800">No backup yet</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                ZIP snapshots of the SQLite database @if($settings['backup_include_photos']), student photos @endif @if($settings['backup_include_env']), and .env @endif.
                            </p>
                        </div>
                        <button type="button" onclick="openSaModal('saBackupCreateModal')" class="bg-ieti-green hover:bg-green-800 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">
                            + Backup Now
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-3">
                            <p class="text-[11px] font-semibold uppercase text-gray-500">Last Backup</p>
                            <p class="mt-1 text-sm font-bold text-gray-800">{{ $backupFiles ? $backupFiles[0]['created_at']->diffForHumans() : 'Never' }}</p>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-3">
                            <p class="text-[11px] font-semibold uppercase text-gray-500">Storage Used</p>
                            <p class="mt-1 text-sm font-bold text-gray-800">{{ $backupTotalSize }}</p>
                            <p class="text-[11px] text-gray-500">{{ count($backupFiles) }} archive(s)</p>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-3">
                            <p class="text-[11px] font-semibold uppercase text-gray-500">Retention</p>
                            <p class="mt-1 text-sm font-bold text-gray-800">Keep {{ $settings['backup_keep'] }} newest</p>
                        </div>
                    </div>

                    @if($errors->has('backup'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl text-sm">{{ $errors->first('backup') }}</div>
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
                                                <a href="{{ route('admin.backups.download', $backup['name']) }}" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">Download</a>
                                                <button type="button" class="text-ieti-green hover:text-green-800 text-xs font-semibold ml-2" onclick="openBackupAction('saBackupRestoreModal', 'saBackupRestoreForm', {{ json_encode($backup['name']) }}, 'restore')">Restore</button>
                                                <button type="button" class="text-red-600 hover:text-red-800 text-xs font-semibold ml-2" onclick="openBackupAction('saBackupDeleteModal', 'saBackupDeleteForm', {{ json_encode($backup['name']) }}, 'delete')">Delete</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center border border-dashed border-gray-200 rounded-xl py-8 px-4">
                            <p class="text-sm font-semibold text-gray-700">No backups yet</p>
                            <p class="text-xs text-gray-400 mt-1">Create a snapshot before making major system changes.</p>
                        </div>
                    @endif

                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900 space-y-1">
                        <p>Backups are stored on this computer in <code class="font-mono">{{ $backupDirectory }}</code>. Download copies regularly.</p>
                        <p>Restoring replaces the live database; a safety snapshot is saved first and you will be signed out.</p>
                    </div>
                </div>
            </section>

            <!-- Settings -->
            <section id="tab-settings" class="hidden flex-col gap-4">
                <form method="POST" action="{{ route('superadmin.settings.update') }}" class="bg-white rounded-2xl p-5 shadow border border-gray-100 flex flex-col gap-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Application Settings</h2>
                        <p class="text-xs text-gray-500">Changes apply immediately across every dashboard.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Site Name</label>
                            <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required maxlength="100" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <p class="text-[11px] text-gray-400 mt-1">Shown in page titles and the sidebar.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Backups to Keep</label>
                            <input type="number" name="backup_keep" value="{{ old('backup_keep', $settings['backup_keep']) }}" required min="1" max="90" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <p class="text-[11px] text-gray-400 mt-1">Older archives are pruned automatically.</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="flex items-start space-x-3 p-3 rounded-xl border border-gray-100 bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="backup_include_photos" value="1" {{ $settings['backup_include_photos'] ? 'checked' : '' }} class="mt-0.5 h-4 w-4 accent-green-700">
                            <span>
                                <span class="block text-sm font-semibold text-gray-800">Include student photos in backups</span>
                                <span class="block text-xs text-gray-500">Keeps stored photos recoverable with the database.</span>
                            </span>
                        </label>
                        <label class="flex items-start space-x-3 p-3 rounded-xl border border-gray-100 bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="backup_include_env" value="1" {{ $settings['backup_include_env'] ? 'checked' : '' }} class="mt-0.5 h-4 w-4 accent-green-700">
                            <span>
                                <span class="block text-sm font-semibold text-gray-800">Include .env in backups</span>
                                <span class="block text-xs text-gray-500">Required to decrypt two-factor secrets. Keep archives private.</span>
                            </span>
                        </label>
                        <label class="flex items-start space-x-3 p-3 rounded-xl border border-amber-200 bg-amber-50 cursor-pointer">
                            <input type="checkbox" name="maintenance_mode" value="1" {{ $settings['maintenance_mode'] ? 'checked' : '' }} class="mt-0.5 h-4 w-4 accent-amber-600">
                            <span>
                                <span class="block text-sm font-semibold text-amber-900">Maintenance mode</span>
                                <span class="block text-xs text-amber-800">Shows a maintenance page to everyone except Super Admins. Sign-in stays open.</span>
                            </span>
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-5 py-2 bg-ieti-green text-white rounded-lg text-sm font-semibold hover:opacity-90">Save Settings</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</div>

<!-- Create Account Modal -->
<div id="saAddUserModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Create Staff Account</h3>
        <form action="{{ route('superadmin.users.store') }}" method="POST" class="space-y-3">
            @csrf
            <input type="text" name="name" placeholder="Full Name" required class="w-full border p-2 rounded text-sm">
            <input type="text" name="username" placeholder="Username" required class="w-full border p-2 rounded text-sm">
            <input type="email" name="email" placeholder="Email Address" required class="w-full border p-2 rounded text-sm">
            <input type="password" name="password" placeholder="Password" required class="w-full border p-2 rounded text-sm">
            <p class="text-xs text-gray-500">Strong password: at least 12 characters with uppercase, lowercase, number and symbol.</p>
            <div>
                <label class="text-xs text-gray-500 font-semibold">Assign Role</label>
                <select name="role" class="w-full border p-2 rounded text-sm mt-1">
                    @foreach(['SuperAdmin', 'Admin', 'Registrar', 'Security'] as $role)
                        <option value="{{ $role }}">{{ $role }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end space-x-2 mt-4">
                <button type="button" onclick="closeSaModal('saAddUserModal')" class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm bg-ieti-green text-white rounded-lg font-semibold">Create Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Account Modal -->
<div id="saEditUserModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Edit Account</h3>
        <form id="saEditUserForm" method="POST" class="space-y-3">
            @csrf
            @method('PUT')
            <input type="text" id="sa_edit_name" name="name" required class="w-full border p-2 rounded text-sm">
            <input type="text" id="sa_edit_username" name="username" required class="w-full border p-2 rounded text-sm">
            <input type="email" id="sa_edit_email" name="email" required class="w-full border p-2 rounded text-sm">
            <div>
                <label class="text-xs text-gray-500 font-semibold">Assign Role</label>
                <select id="sa_edit_role" name="role" class="w-full border p-2 rounded text-sm mt-1">
                    @foreach(['SuperAdmin', 'Admin', 'Registrar', 'Security'] as $role)
                        <option value="{{ $role }}">{{ $role }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end space-x-2 mt-4">
                <button type="button" onclick="closeSaModal('saEditUserModal')" class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm bg-ieti-green text-white rounded-lg font-semibold">Update Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Temporary Password Modal -->
<div id="saTempPasswordModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-lg font-bold text-gray-800">Generate temporary password?</h3>
        <p class="text-sm text-gray-600 mt-2">A new strong temporary password will be created for <strong id="saTempPasswordEmail"></strong>. The user will be asked to set a new password on their next login.</p>
        <form id="saTempPasswordForm" method="POST" class="flex justify-end space-x-2 mt-5">
            @csrf
            <button type="button" onclick="closeSaModal('saTempPasswordModal')" class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">Cancel</button>
            <button type="submit" class="px-4 py-2 text-sm bg-ieti-green text-white rounded-lg font-semibold">Generate Password</button>
        </form>
    </div>
</div>

<!-- Backup Create Modal -->
<div id="saBackupCreateModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-lg font-bold text-gray-800">Create backup now?</h3>
        <p class="text-sm text-gray-600 mt-2">This creates a ZIP snapshot of the live database and may take a moment.</p>
        <form action="{{ route('admin.backups.store') }}" method="POST" class="flex justify-end gap-2 mt-5">
            @csrf
            <button type="button" onclick="closeSaModal('saBackupCreateModal')" class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">Cancel</button>
            <button type="submit" class="px-4 py-2 text-sm bg-ieti-green text-white rounded-lg font-semibold">Create Backup</button>
        </form>
    </div>
</div>

<!-- Backup Delete Modal -->
<div id="saBackupDeleteModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-lg font-bold text-gray-800">Delete this backup?</h3>
        <p class="text-sm text-gray-600 mt-2"><code id="saBackupDeleteName" class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded"></code> will be permanently removed.</p>
        <form id="saBackupDeleteForm" method="POST" class="flex justify-end gap-2 mt-5">
            @csrf
            @method('DELETE')
            <button type="button" onclick="closeSaModal('saBackupDeleteModal')" class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">Cancel</button>
            <button type="submit" class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold">Delete Backup</button>
        </form>
    </div>
</div>

<!-- Backup Restore Modal -->
<div id="saBackupRestoreModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-lg font-bold text-gray-800">Restore from backup?</h3>
        <p class="text-sm text-gray-600 mt-2">This replaces the live database with <code id="saBackupRestoreName" class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded"></code>. A safety snapshot is saved first and you will be signed out.</p>
        <form id="saBackupRestoreForm" method="POST" class="space-y-3 mt-4">
            @csrf
            <div>
                <label class="text-xs font-semibold text-gray-600">Type <span class="font-mono">RESTORE</span> to confirm</label>
                <input type="text" name="confirmation" required autocomplete="off" class="mt-1 w-full border p-2 rounded text-sm font-mono" placeholder="RESTORE">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeSaModal('saBackupRestoreModal')" class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm bg-ieti-green text-white rounded-lg font-semibold">Restore System</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Tab handling (kept in sync with the URL hash)
    const saTabs = ['overview', 'accounts', 'activity', 'backups', 'settings'];

    function activateSaTab(name) {
        const active = saTabs.includes(name) ? name : 'overview';
        saTabs.forEach(function (tab) {
            const section = document.getElementById('tab-' + tab);
            const link = document.querySelector('[data-sa-tab="' + tab + '"]');
            if (section) section.classList.toggle('hidden', tab !== active);
            if (link) {
                link.classList.toggle('bg-ieti-green', tab === active);
                link.classList.toggle('text-white', tab === active);
                link.classList.toggle('bg-white', tab !== active);
                link.classList.toggle('text-gray-600', tab !== active);
            }
        });
    }

    function syncSaTab() {
        activateSaTab(window.location.hash.replace('#', ''));
    }

    window.addEventListener('hashchange', syncSaTab);
    document.querySelectorAll('[data-sa-tab]').forEach(function (link) {
        link.addEventListener('click', function () { setTimeout(syncSaTab, 0); });
    });
    syncSaTab();

    // Modals
    function openSaModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeSaModal(id) { document.getElementById(id).classList.add('hidden'); }

    function editSaUser(user) {
        document.getElementById('saEditUserForm').action = '/superadmin/users/' + user.id;
        document.getElementById('sa_edit_name').value = user.name || '';
        document.getElementById('sa_edit_username').value = user.username || '';
        document.getElementById('sa_edit_email').value = user.email || '';
        document.getElementById('sa_edit_role').value = user.role || 'Registrar';
        openSaModal('saEditUserModal');
    }

    function openTempPasswordModal(id, email) {
        document.getElementById('saTempPasswordForm').action = '/superadmin/users/' + id + '/temporary-password';
        document.getElementById('saTempPasswordEmail').textContent = email;
        openSaModal('saTempPasswordModal');
    }

    function openBackupAction(modalId, formId, fileName, action) {
        const name = String(fileName);
        if (action === 'restore') {
            document.getElementById('saBackupRestoreName').textContent = name;
            document.getElementById(formId).action = '/admin/backups/' + encodeURIComponent(name) + '/restore';
        } else {
            document.getElementById('saBackupDeleteName').textContent = name;
            document.getElementById(formId).action = '/admin/backups/' + encodeURIComponent(name);
        }
        openSaModal(modalId);
    }

    // Charts
    const saEntriesLabels = @json($analyticsLabels);
    const saEntriesData = @json($analyticsData);
    const saRoleLabels = @json(array_keys($roleBreakdown));
    const saRoleData = @json(array_values($roleBreakdown));

    if (document.getElementById('saEntriesChart')) {
        new Chart(document.getElementById('saEntriesChart'), {
            type: 'bar',
            data: {
                labels: saEntriesLabels,
                datasets: [{
                    label: 'Granted entries',
                    data: saEntriesData,
                    backgroundColor: '#047857',
                    borderRadius: 6,
                }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
        });
    }

    if (document.getElementById('saRolesChart')) {
        new Chart(document.getElementById('saRolesChart'), {
            type: 'doughnut',
            data: {
                labels: saRoleLabels,
                datasets: [{
                    data: saRoleData,
                    backgroundColor: ['#4f46e5', '#7e22ce', '#047857', '#d97706'],
                }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });
    }
</script>
</body>
</html>
