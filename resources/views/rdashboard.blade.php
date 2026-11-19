<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IETI SEVS</title>
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
            <!-- Top Header -->
            <header class="bg-white shadow px-8 py-6 border-b border-gray-200">
                <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
                <p class="text-sm text-gray-500 mt-1">Welcome back! Here's an overview of your system.</p>
            </header>

            <!-- Page Content -->
            <main class="flex-1 px-8 py-6 space-y-6">

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl shadow-sm">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Total Students Card -->
                    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Total Students</p>
                                <p class="text-3xl font-bold text-gray-800 mt-2">{{ $totalStudents }}</p>
                            </div>
                            <div class="bg-blue-100 p-4 rounded-lg">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zM5 20h10v-2a7 7 0 00-10 0v2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Unassigned RFID Card -->
                    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Unassigned RFID Tags</p>
                                <p class="text-3xl font-bold text-gray-800 mt-2">{{ $unassignedRfids }}</p>
                            </div>
                            <div class="bg-yellow-100 p-4 rounded-lg">
                                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Enrolled Today Card -->
                    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Entries Today</p>
                                <p id="entries-today-count" class="text-3xl font-bold text-gray-800 mt-2">{{ $entriesToday ?? 0 }}</p>
                            </div>
                            <div class="bg-green-100 p-4 rounded-lg">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enrollment Status & Today's Entry Summary -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
                        <h2 class="text-lg font-bold text-gray-800">Enrollment Status Overview</h2>
                        <p class="text-xs text-gray-500 mt-1">Student records grouped by current status.</p>
                        <div class="mt-4 space-y-2">
                            @forelse($enrollmentStatus as $status => $count)
                                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                                    <span class="text-sm font-medium text-gray-700">{{ $status ?: 'Unspecified' }}</span>
                                    <span class="text-sm font-bold {{ $status === 'Enrolled' ? 'text-green-700' : 'text-gray-700' }}">{{ $count }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400">No student records yet.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
                        <h2 class="text-lg font-bold text-gray-800">Today's Entry Summary</h2>
                        <p class="text-xs text-gray-500 mt-1">Granted entries today by year and section.</p>
                        <div class="mt-4 space-y-2 max-h-64 overflow-y-auto pr-1">
                            @forelse($entrySummaryToday as $group => $count)
                                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                                    <span class="text-sm font-medium text-gray-700">{{ $group }}</span>
                                    <span class="text-sm font-bold text-green-700">{{ $count }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400">No granted entries recorded today.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Year / Section Breakdown -->
                <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
                    <h2 class="text-lg font-bold text-gray-800">Students by Year & Section</h2>
                    <p class="text-xs text-gray-500 mt-1">Current student counts per year level and section.</p>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        @forelse($sectionBreakdown as $year => $rows)
                            <div class="rounded-lg border border-gray-100">
                                <div class="bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700">Year {{ $year }}</div>
                                <div class="divide-y divide-gray-100">
                                    @foreach($rows as $row)
                                        <div class="flex items-center justify-between px-3 py-2 text-sm">
                                            <span class="text-gray-600">{{ $row->section }}</span>
                                            <span class="font-semibold text-gray-800">{{ $row->total }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">No sections assigned yet.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Missing RFID / Photo -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-bold text-gray-800">Students Missing RFID</h2>
                            <span class="text-xs font-semibold text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded-full">{{ $unassignedRfids }}</span>
                        </div>
                        <div class="mt-4 space-y-2 max-h-64 overflow-y-auto pr-1">
                            @forelse($missingRfidStudents as $student)
                                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">{{ $student->last_name }}, {{ $student->first_name }}</p>
                                        <p class="text-xs text-gray-400">{{ $student->student_number }} · Year {{ $student->year_level }} {{ $student->section }}</p>
                                    </div>
                                    <a href="/registrar/students?search={{ urlencode($student->student_number) }}" class="text-xs font-semibold text-ieti-green hover:underline">Assign</a>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400">All students have an RFID tag assigned.</p>
                            @endforelse
                        </div>
                        @if($unassignedRfids > $missingRfidStudents->count())
                            <a href="/registrar/students" class="mt-3 inline-block text-xs font-semibold text-ieti-green hover:underline">View all {{ $unassignedRfids }} students</a>
                        @endif
                    </div>

                    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
                        <h2 class="text-lg font-bold text-gray-800">Students Missing Photo</h2>
                        <div class="mt-4 space-y-2 max-h-64 overflow-y-auto pr-1">
                            @forelse($missingPhotoStudents as $student)
                                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">{{ $student->last_name }}, {{ $student->first_name }}</p>
                                        <p class="text-xs text-gray-400">{{ $student->student_number }} · Year {{ $student->year_level }} {{ $student->section }}</p>
                                    </div>
                                    <a href="/registrar/students?search={{ urlencode($student->student_number) }}" class="text-xs font-semibold text-ieti-green hover:underline">Upload</a>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400">All students have a photo on file.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Recent Student Activity -->
                <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
                    <h2 class="text-lg font-bold text-gray-800">Recent Student Activity</h2>
                    <p class="text-xs text-gray-500 mt-1">Latest student record changes recorded by staff.</p>
                    <div class="mt-4 divide-y divide-gray-100 max-h-72 overflow-y-auto pr-1">
                        @forelse($recentActivities as $activity)
                            <div class="py-3 flex items-start justify-between gap-3 text-sm">
                                <div>
                                    <p class="font-medium text-gray-800">{{ $activity->action }}</p>
                                    <p class="text-xs text-gray-500">{{ $activity->details }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-xs text-gray-500">{{ $activity->user_name }}</p>
                                    <p class="text-xs text-gray-400">{{ $activity->created_at?->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 py-4">No student activity recorded yet.</p>
                        @endforelse
                    </div>
                </div>

                <script>
                    const entriesTodayCount = document.getElementById('entries-today-count');

                    async function refreshEntriesToday() {
                        if (document.hidden) return;

                        try {
                            const response = await fetch('{{ route('entry-logs.summary') }}', {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                cache: 'no-store'
                            });

                            if (!response.ok) return;

                            const data = await response.json();

                            if (entriesTodayCount && typeof data.total !== 'undefined') {
                                entriesTodayCount.textContent = data.total;
                            }
                        } catch (error) {
                            // Ignore network hiccups; the next poll will retry.
                        }
                    }

                    refreshEntriesToday();
                    setInterval(refreshEntriesToday, 3000);
                </script>

</body>
</html>