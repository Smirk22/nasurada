<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IETI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-ieti-green { background-color: #064e29; }
        .text-ieti-green { color: #064e29; }
    </style>
</head>
<body class="bg-gray-100 font-sans min-h-screen flex flex-col justify-between">

    <!-- Header -->
    <header class="bg-ieti-green text-white shadow-md rounded-b-2xl px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <span class="font-bold text-lg tracking-wide">IETI - System Administration</span>
            <nav class="flex items-center space-x-6 text-sm font-medium">
                <a href="/adashboard" class="text-yellow-300 font-semibold border-b-2 border-yellow-300 pb-1">Admin Dashboard</a>
                
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="hover:text-red-300 transition text-sm font-semibold ml-2">Log Out</button>
                </form>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto w-full px-6 py-8 flex-1 space-y-6">

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl p-5 shadow border border-gray-100 flex flex-col justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase">System Staff Users</span>
                <div class="text-3xl font-extrabold text-ieti-green mt-2">{{ $totalUsers }}</div>
                <span class="text-xs text-green-600 mt-2 font-medium">Admins, Registrars & Security</span>
            </div>

            <div class="bg-white rounded-xl p-5 shadow border border-gray-100 flex flex-col justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase">Total Students</span>
                <div class="text-3xl font-extrabold text-blue-600 mt-2">{{ $totalStudents }}</div>
                <span class="text-xs text-blue-600 mt-2 font-medium">Enrolled & Unenrolled</span>
            </div>

            <div class="bg-white rounded-xl p-5 shadow border border-gray-100 flex flex-col justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase">Invalid RFID Scans</span>
                <div class="text-3xl font-extrabold text-red-600 mt-2">{{ $invalidRfidScans }}</div>
                <span class="text-xs text-red-600 mt-2 font-medium">Security Alerts Logged</span>
            </div>
        </div>

        <!-- System User Management -->
        <div class="bg-white rounded-2xl p-6 shadow border border-gray-100 space-y-4">
            <div class="flex justify-between items-center">
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
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase font-semibold">
                            <th class="p-3">Name</th>
                            <th class="p-3">Username</th>
                            <th class="p-3">Email</th>
                            <th class="p-3">Assigned Role</th>
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
                                        {{ $user->role === 'Admin' ? 'bg-purple-100 text-purple-800' : ($user->role === 'Registrar' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800') }}">
                                        {{ $user->role ?? 'Registrar' }}
                                    </span>
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
        <div class="bg-white rounded-2xl p-6 shadow border border-gray-100 space-y-4">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Live Activity Monitor</h2>
                    <p class="text-xs text-gray-500">Real-time audit log of Registrar & Security actions.</p>
                </div>
                <span class="text-xs font-semibold text-gray-400 bg-gray-100 px-2.5 py-1 rounded-full">
                    {{ $activities->count() }} Total Logs
                </span>
            </div>

            <!-- Scrollable log container -->
            <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto pr-2 space-y-1">
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
                <input type="password" name="password" placeholder="New Password (Optional)" class="w-full border p-2 rounded text-sm">
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

    <!-- Modal Scripts -->
    <script>
        function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
        function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
        function editUser(user) {
            document.getElementById('editUserForm').action = '/admin/users/' + user.id;
            document.getElementById('edit_user_name').value = user.name || '';
            document.getElementById('edit_user_username').value = user.username || '';
            document.getElementById('edit_user_email').value = user.email || '';
            document.getElementById('edit_user_role').value = user.role || 'Registrar';
            openModal('editUserModal');
        }
    </script>

</body>
</html>