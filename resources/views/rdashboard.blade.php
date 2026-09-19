<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Dashboard - IETI</title>
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
            <span class="font-bold text-lg tracking-wide">IETI - Student Entry Verification</span>
            <nav class="flex items-center space-x-6 text-sm font-medium">
                <a href="/rdashboard" class="text-yellow-300 font-semibold border-b-2 border-yellow-300 pb-1">Dashboard</a>
                <a href="/registrar/students" class="hover:text-yellow-200 transition">Student Records</a>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="hover:text-red-300 transition text-sm font-semibold ml-2">Log Out</button>
                </form>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto w-full px-6 py-8 flex-1 space-y-6">

        <!-- Banner Card -->
        <div class="bg-ieti-green text-white rounded-2xl p-6 shadow-lg flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">Registrar Dashboard</h1>
                <p class="text-sm text-green-100 mt-1">Hello!, <span class="font-semibold text-yellow-300">{{ Auth::user()->username ?? 'Registrar' }}</span></p>
            </div>
            <a href="/registrar/students" class="bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold px-4 py-2 rounded-lg text-sm transition">
                View Student Records &rarr;
            </a>
        </div>

        <!-- Summary Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl p-5 shadow border border-gray-100 flex flex-col justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase">Total Students</span>
                <div class="text-3xl font-extrabold text-ieti-green mt-2">{{ $totalStudents }}</div>
                <span class="text-xs text-green-600 mt-2 font-medium">&uarr; Active Records</span>
            </div>

            <div class="bg-white rounded-xl p-5 shadow border border-gray-100 flex flex-col justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase">Today's Entry Logs</span>
                <div class="text-3xl font-extrabold text-ieti-green mt-2">0</div>
                <span class="text-xs text-gray-400 mt-2 font-medium">Turnstile scans today</span>
            </div>

            <div class="bg-white rounded-xl p-5 shadow border border-gray-100 flex flex-col justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase">Unassigned RFIDs</span>
                <div class="text-3xl font-extrabold text-yellow-600 mt-2">{{ $unassignedRfids }}</div>
                <span class="text-xs text-yellow-600 mt-2 font-medium">Pending pairing</span>
            </div>

            <div class="bg-white rounded-xl p-5 shadow border border-gray-100 flex flex-col justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase">Incidents Logged</span>
                <div class="text-3xl font-extrabold text-red-600 mt-2">0</div>
                <span class="text-xs text-green-600 mt-2 font-medium">All clear</span>
            </div>
        </div>

     
   

    </main>

  
    <footer class="bg-ieti-green text-white text-center py-4 rounded-t-2xl text-xs text-green-100">
       
    </footer>

</body>
</html>