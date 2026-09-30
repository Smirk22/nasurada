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
                                <p class="text-3xl font-bold text-gray-800 mt-2">{{ $entriestoday ?? 0 }}</p>
                            </div>
                            <div class="bg-green-100 p-4 rounded-lg">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="/registrar/students" class="bg-ieti-green hover:bg-green-800 text-white font-semibold px-6 py-3 rounded-lg transition text-center">
                            + Add New Student
                        </a>
                        <a href="/registrar/entry-logs" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg transition text-center">
                            View Entry Logs
                        </a>
                        <a href="/registrar/students" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold px-6 py-3 rounded-lg transition text-center">
                            Manage Students
                        </a>
                    </div>
                </div>

                
</body>
</html>