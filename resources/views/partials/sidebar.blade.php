<aside class="w-64 bg-ieti-green text-white min-h-screen flex flex-col shadow-lg">
    <!-- Logo Section -->
    <div class="p-6 border-b border-green-700">
        <h2 class="text-xl font-bold tracking-wider">IETI SEVS</h2>
        <p class="text-xs text-green-100 mt-1">Student Entry Verification System</p>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 py-6 space-y-2">
        <!-- Dashboard -->
        <a href="{{ auth()->user()->role === 'guard' ? '/guard/dashboard' : '/rdashboard' }}" class="flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->path() === 'guard/dashboard' || request()->path() === 'rdashboard' ? 'bg-green-700' : 'hover:bg-green-700 transition' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9M9 21h6a2 2 0 002-2V9l-7-4-7 4v12a2 2 0 002 2z"></path>
            </svg>
            <span class="text-sm font-medium">Dashboard</span>
        </a>

        <!-- Student Records (Registrar only) -->
        @if(auth()->user()->role === 'registrar')
            <a href="/registrar/students" class="flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->path() === 'registrar/students' ? 'bg-green-700' : 'hover:bg-green-700 transition' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zM5 20h10v-2a7 7 0 00-10 0v2z"></path>
                </svg>
                <span class="text-sm font-medium">Student Records</span>
            </a>

            <!-- Entry Logs (Registrar only) -->
            <a href="/registrar/entry-logs" class="flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->path() === 'registrar/entry-logs' ? 'bg-green-700' : 'hover:bg-green-700 transition' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-sm font-medium">Entry Logs</span>
            </a>
        @endif
    </nav>

    <!-- Logout Button (Bottom) -->
    <div class="p-4 border-t border-green-700">
        <form action="{{ route('logout') }}" method="POST" class="w-full">
            @csrf
            <button type="submit" class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg bg-red-600 hover:bg-red-700 transition text-sm font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span>Log Out</span>
            </button>
        </form>
    </div>
</aside>