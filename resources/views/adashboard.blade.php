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
                <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
                <p class="text-sm text-gray-500 mt-1">System administration and staff account overview.</p>
            </header>

            <!-- Main Content -->
            <main class="flex-1 px-8 py-6 flex flex-col gap-4">

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl shadow-sm shrink-0">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl shadow-sm shrink-0">
                        {{ $errors->first() }}
                    </div>
                @endif

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
