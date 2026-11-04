<aside class="w-64 bg-ieti-green text-white min-h-screen flex flex-col shadow-lg">
    <!-- Logo Section -->
    <div class="p-6 border-b border-green-700">
        <h2 class="text-xl font-bold tracking-wider">IETI SEVS</h2>
        <p class="text-xs text-green-100 mt-1">Student Entry Verification System</p>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 py-6 space-y-2">
        <!-- Admin Dashboard -->
        @if(auth()->user()->role === 'Admin')
            <a href="/adashboard" class="flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->is('adashboard') ? 'bg-green-700' : 'hover:bg-green-700 transition' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9M9 21h6a2 2 0 002-2V9l-7-4-7 4v12a2 2 0 002 2z"></path>
                </svg>
                <span class="text-sm font-medium">Admin Dashboard</span>
            </a>
        @endif

        <!-- Dashboard -->
        <a href="{{ (auth()->user()->role === 'Security') ? '/Security/dashboard' : ((auth()->user()->role === 'Registrar') ? '/rdashboard' : '/rdashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->is('Security/dashboard') || request()->is('rdashboard') ? 'bg-green-700' : 'hover:bg-green-700 transition' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9M9 21h6a2 2 0 002-2V9l-7-4-7 4v12a2 2 0 002 2z"></path>
            </svg>
            <span class="text-sm font-medium">{{ auth()->user()->role === 'Admin' ? 'Registrar Dashboard' : 'Dashboard' }}</span>
        </a>

        <!-- Two-Factor Authentication -->
        <a href="{{ route('two-factor.setup') }}" class="flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->is('settings/two-factor') ? 'bg-green-700' : 'hover:bg-green-700 transition' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751A11.959 11.959 0 0112 2.714z"></path>
            </svg>
            <span class="text-sm font-medium">Two-Factor Auth</span>
        </a>

        {{-- Security/Guard Dashboard --}}
        @if(auth()->user()->role === 'Security' || auth()->user()->role === 'Admin')
            <a href="/Security/dashboard" class="flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->is('Security/dashboard') ? 'bg-green-700' : 'hover:bg-green-700 transition' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <span class="text-sm font-medium">Guard Dashboard</span>
            </a>
        @endif

        {{-- Kiosk for Security/Admin --}}
        @if(auth()->user()->role === 'Security' || auth()->user()->role === 'Admin')
            <a href="{{ route('guard.kiosk') }}" target="_blank" rel="noopener" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-green-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                </svg>
                <span class="text-sm font-medium">Entry Kiosk</span>
            </a>
        @endif

        @if(auth()->user()->role === 'Registrar' || auth()->user()->role === 'Admin')
            <!-- Student Records -->
            <a href="/registrar/students" class="flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->path() === 'registrar/students' ? 'bg-green-700' : 'hover:bg-green-700 transition' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zM5 20h10v-2a7 7 0 00-10 0v2z"></path>
                </svg>
                <span class="text-sm font-medium">Student Records</span>
            </a>

            <!-- Entry Logs -->
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
