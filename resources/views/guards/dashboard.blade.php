<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard Dashboard - IETI SEVS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-ieti-green { background-color: #064e29; }
        .text-ieti-green { color: #064e29; }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        @include('partials.sidebar')

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Top Header with Date/Time and Profile -->
            <header class="bg-white shadow px-8 py-4 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Security Guard Dashboard</h1>
                    <p id="dateTime" class="text-sm text-gray-500 mt-1"></p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-800">{{ auth()->user()->username }}</p>
                        <p class="text-xs text-gray-500">{{ ucfirst(auth()->user()->role) }}</p>
                    </div>
                    <div class="w-10 h-10 bg-ieti-green text-white rounded-full flex items-center justify-center font-bold">
                        {{ strtoupper(substr(auth()->user()->username, 0, 1)) }}
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 px-8 py-6 space-y-6 overflow-y-auto">

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl shadow-sm">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Students Verified Today -->
                    <div class="bg-white rounded-xl shadow border border-gray-100 p-6 border-l-4 border-l-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Students Verified Today</p>
                                <p class="text-3xl font-bold text-gray-800 mt-2">{{ $studentsVerifiedToday ?? 0 }}</p>
                            </div>
                            <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Visitors on Campus -->
                    <div class="bg-white rounded-xl shadow border border-gray-100 p-6 border-l-4 border-l-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Visitors on Campus</p>
                                <p class="text-3xl font-bold text-gray-800 mt-2">{{ $visitorsOnCampus ?? 0 }}</p>
                            </div>
                            <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 8.646 4 4 0 010-8.646M15 12H9m6 0a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Entries Denied -->
                    <div class="bg-white rounded-xl shadow border border-gray-100 p-6 border-l-4 border-l-red-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Entries Denied</p>
                                <p class="text-3xl font-bold text-gray-800 mt-2">{{ $entriesDenied ?? 0 }}</p>
                            </div>
                            <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Live Entry Feed Table -->
                <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-bold text-gray-800">Live Entry Feed</h2>
                        <p class="text-xs text-gray-500 mt-1">Last scanned students (Real-time)</p>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-600 uppercase font-semibold">
                                <th class="p-4">Photo</th>
                                <th class="p-4">Name</th>
                                <th class="p-4">Student #</th>
                                <th class="p-4">Time</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Enrollment</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse($entryLogs as $log)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="p-4">
                                        <div class="w-10 h-10 bg-ieti-green text-white rounded-full flex items-center justify-center font-bold text-xs">
                                            {{ strtoupper(substr($log->student->first_name, 0, 1)) }}
                                        </div>
                                    </td>
                                    <td class="p-4 font-semibold text-gray-800">{{ $log->student->first_name }} {{ $log->student->last_name }}</td>
                                    <td class="p-4 text-gray-600">{{ $log->student->student_number }}</td>
                                    <td class="p-4">
                                        <span class="text-sm">
                                            {{ $log->time_in ? \Carbon\Carbon::parse($log->time_in)->format('H:i') : '-' }}
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        @if($log->time_out)
                                            <span class="px-3 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">🔴 OUT</span>
                                        @else
                                            <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">✅ IN</span>
                                        @endif
                                    </td>
                                    <td class="p-4">
                                        <span class="px-3 py-1 {{ $log->student->status === 'Enrolled' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }} text-xs font-semibold rounded-full">
                                            {{ $log->student->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-6 text-center text-gray-400 text-sm">No entry logs yet. Waiting for first scan...</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Register Visitor Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Register Visitor</h2>
                        <form action="{{ route('guard.visitor.store') }}" method="POST" class="space-y-4">
                            @csrf
                            
                            <!-- Full Name -->
                            <div>
                                <label class="text-xs text-gray-600 font-semibold block mb-1">Full Name</label>
                                <input type="text" name="visitor_name" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600" placeholder="Visitor name">
                            </div>

                            <!-- ID Type & Number -->
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs text-gray-600 font-semibold block mb-1">ID Type</label>
                                    <select name="id_type" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                                        <option value="">Select ID</option>
                                        <option value="drivers_license">Driver's License</option>
                                        <option value="passport">Passport</option>
                                        <option value="national_id">National ID</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600 font-semibold block mb-1">ID Number</label>
                                    <input type="text" name="id_number" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600" placeholder="ID #">
                                </div>
                            </div>

                            <!-- Visiting & Pass Duration -->
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs text-gray-600 font-semibold block mb-1">Visiting</label>
                                    <select name="visiting_department" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                                        <option value="">Department</option>
                                        <option value="registrar">Registrar</option>
                                        <option value="admin">Administration</option>
                                        <option value="faculty">Faculty</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600 font-semibold block mb-1">Pass Valid For</label>
                                    <select name="pass_duration" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                                        <option value="1">1 hour</option>
                                        <option value="2" selected>2 hours</option>
                                        <option value="4">4 hours</option>
                                        <option value="8">8 hours</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Available RFID Tag -->
                            <div>
                                <label class="text-xs text-gray-600 font-semibold block mb-1">Assign RFID Tag</label>
                                <select name="rfid_tag" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                                    <option value="">-- Select Available RFID --</option>
                                    @foreach($availableRfids as $rfid)
                                        <option value="{{ $rfid }}">{{ $rfid }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Buttons -->
                            <div class="flex gap-2 pt-2">
                                <button type="submit" class="flex-1 bg-ieti-green hover:bg-green-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                                    Issue Visitor Pass
                                </button>
                                <button type="reset" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
                                    Clear
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Alerts Section -->
                    <div class="space-y-4">
                        <div class="bg-white rounded-xl shadow border border-gray-100 p-6 border-l-4 border-l-red-500">
                            <h2 class="text-lg font-bold text-gray-800 mb-4">⚠️ Alerts</h2>
                            <div class="space-y-3">
                                @if($deniedEntries && count($deniedEntries) > 0)
                                    @foreach($deniedEntries as $denied)
                                        <div class="bg-red-50 border-l-4 border-red-500 p-3 rounded">
                                            <p class="text-sm font-semibold text-red-800">❌ Invalid RFID Detected</p>
                                            <p class="text-xs text-red-600 mt-1">{{ $denied['time'] ?? 'N/A' }}</p>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-gray-400 text-sm">✅ All systems normal. No alerts.</p>
                                @endif
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
                            <h2 class="text-lg font-bold text-gray-800 mb-4">📊 Today's Summary</h2>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                    <span class="text-sm text-gray-700">Total Entries</span>
                                    <span class="font-bold text-lg text-ieti-green">{{ $totalEntries ?? 0 }}</span>
                                </div>
                                <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                    <span class="text-sm text-gray-700">Currently In</span>
                                    <span class="font-bold text-lg text-green-600">{{ $currentlyIn ?? 0 }}</span>
                                </div>
                                <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                    <span class="text-sm text-gray-700">Currently Out</span>
                                    <span class="font-bold text-lg text-red-600">{{ $currentlyOut ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Display Current Date/Time -->
    <script>
        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('dateTime').textContent = now.toLocaleDateString('en-US', options);
        }

        updateDateTime();
        setInterval(updateDateTime, 1000); // Update every second
    </script>

</body>
</html>