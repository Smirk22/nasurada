<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entry Logs - IETI SEVS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-ieti-green { background-color: #064e29; }
        .text-ieti-green { color: #064e29; }
        @keyframes live-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }
        .live-dot { animation: live-pulse 1.5s ease-in-out infinite; }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        @include('partials.sidebar')

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Top Header -->
            <header class="bg-white shadow px-8 py-6 border-b border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Entry Logs</h1>
                    <p class="text-sm text-gray-500 mt-1">Scans from the guard's live feed, grouped by the student's year and section.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-2 bg-green-50 text-green-700 px-3 py-1.5 rounded-full text-xs font-semibold border border-green-200">
                        <span class="live-dot inline-block w-2 h-2 rounded-full bg-green-500"></span>
                        Live
                    </span>
                    <span class="text-sm text-gray-600">
                        Today: <span id="total-today" class="font-bold text-ieti-green">{{ $totalToday ?? 0 }}</span> verified
                    </span>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 px-8 py-6 space-y-6">

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow border border-gray-100 p-4 flex flex-col md:flex-row gap-4 md:items-center">
                    <div class="flex-1">
                        <label for="filterDate" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Filter entries by date</label>
                        <input type="date" id="filterDate" class="border rounded-lg px-3 py-2 text-sm w-full md:w-auto focus:outline-none focus:ring-2 focus:ring-green-600">
                    </div>
                    <div class="flex gap-2">
                        <button onclick="filterByDate()" class="bg-ieti-green hover:bg-green-800 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">Filter</button>
                        <button onclick="clearDateFilter()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2 rounded-lg text-sm font-semibold transition">Clear</button>
                    </div>
                </div>

                <!-- Year / Section groups (built from actual student records) -->
                <div id="year-groups" class="space-y-6">
                    @foreach($yearLevels as $year => $sections)
                        @php
                            $yearTotal = collect($sections)->sum(fn ($section) => $sectionCounts[$year . '|' . $section] ?? 0);
                        @endphp

                        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                            <button onclick="toggleYear({{ $year }})" class="w-full flex items-center justify-between px-6 py-4 bg-gray-50 hover:bg-gray-100 transition border-b border-gray-100">
                                <div class="flex items-center gap-3">
                                    <h2 class="text-lg font-bold text-gray-800">Year {{ $year }}</h2>
                                    <span data-year-total="{{ $year }}" id="year-total-{{ $year }}" class="text-xs font-semibold text-green-700 bg-green-100 px-2 py-1 rounded-full">
                                        {{ $yearTotal }} today
                                    </span>
                                </div>
                                <svg id="icon-year-{{ $year }}" class="w-6 h-6 text-gray-600 transform transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                            </button>
                            <div id="year-{{ $year }}" class="hidden space-y-4 p-6">
                                @forelse($sections as $section)
                                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                                        <button onclick="openSectionModal('{{ $section }}', {{ $year }})" class="w-full flex items-center justify-between px-4 py-3 bg-blue-50 hover:bg-blue-100 transition">
                                            <div class="text-left">
                                                <p class="font-semibold text-gray-800">Section {{ $section }}</p>
                                                <p class="text-xs text-gray-500">Click to view entries</p>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span data-section-count="{{ $year }}|{{ $section }}" class="text-xs font-semibold text-blue-700 bg-blue-100 px-2 py-1 rounded-full">
                                                    {{ $sectionCounts[$year . '|' . $section] ?? 0 }} entries today
                                                </span>
                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </div>
                                        </button>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-400">No sections registered for this year yet.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>

            </main>
        </div>
    </div>

    <!-- Section Modal (Entry Logs Table) -->
    <div id="sectionModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl w-full max-w-5xl max-h-[85vh] overflow-y-auto shadow-2xl">
            <!-- Modal Header -->
            <div class="sticky top-0 bg-ieti-green text-white px-6 py-4 flex justify-between items-center z-10">
                <div class="flex items-center gap-3">
                    <h3 id="modalTitle" class="text-lg font-bold"></h3>
                    <span class="inline-flex items-center gap-2 bg-white/15 px-2 py-1 rounded-full text-[10px] font-semibold uppercase tracking-wide">
                        <span class="live-dot inline-block w-1.5 h-1.5 rounded-full bg-green-300"></span>
                        Live
                    </span>
                </div>
                <button onclick="closeSectionModal()" class="text-white hover:bg-green-700 px-2 py-1 rounded">✕</button>
            </div>

            <!-- Modal Content -->
            <div class="p-6">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-600 uppercase font-semibold">
                            <th class="p-3">Student #</th>
                            <th class="p-3">Name</th>
                            <th class="p-3">Year / Section</th>
                            <th class="p-3">Date</th>
                            <th class="p-3">Time In</th>
                            <th class="p-3">Time Out</th>
                            <th class="p-3">Status</th>
                        </tr>
                    </thead>
                    <tbody id="modalTableBody" class="divide-y divide-gray-100 text-sm">
                        <!-- Populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        const SUMMARY_URL = "{{ route('entry-logs.summary', [], false) }}";
        const SECTION_URL = "{{ route('entry-logs.getBySection', ['section' => '__SECTION__'], false) }}";

        let currentSection = null;
        let currentYear = null;
        let modalTimer = null;

        // Toggle Year Sections
        function toggleYear(yearNum) {
            const yearDiv = document.getElementById(`year-${yearNum}`);
            const icon = document.getElementById(`icon-year-${yearNum}`);

            if (!yearDiv || !icon) return;

            yearDiv.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        }

        // Open Section Modal with Entry Logs
        function openSectionModal(section, year) {
            currentSection = section;
            currentYear = year;

            document.getElementById('modalTitle').textContent = `Year ${year} - Section ${section}`;
            document.getElementById('sectionModal').classList.remove('hidden');

            loadSectionLogs();

            clearInterval(modalTimer);
            modalTimer = setInterval(loadSectionLogs, 4000);
        }

        // Fetch the selected section's entries (year-aware and always ordered newest first)
        async function loadSectionLogs() {
            if (!currentSection) return;

            const tableBody = document.getElementById('modalTableBody');
            const params = new URLSearchParams({ year: currentYear });

            const date = document.getElementById('filterDate').value;
            if (date) params.set('date', date);

            const url = SECTION_URL.replace('__SECTION__', encodeURIComponent(currentSection)) + '?' + params.toString();

            try {
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store'
                });

                if (!response.ok) throw new Error('HTTP ' + response.status);

                const data = await response.json();

                if (data.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="7" class="p-4 text-center text-gray-400">No entry logs found for this section yet.</td></tr>';
                    return;
                }

                tableBody.innerHTML = data.map(log => {
                    const student = log.student || {};
                    const name = `${student.first_name || ''} ${student.last_name || ''}`.trim() || 'Unknown';
                    const status = renderStatus(log);
                    const dateLabel = log.date ? formatDate(log.date) : '-';

                    return `
                        <tr class="hover:bg-gray-50">
                            <td class="p-3 font-semibold text-gray-800">${escapeHtml(student.student_number || '-')}</td>
                            <td class="p-3">${escapeHtml(name)}</td>
                            <td class="p-3 text-gray-600">Year ${escapeHtml(student.year_level || '-')} &middot; ${escapeHtml(student.section || '-')}</td>
                            <td class="p-3 text-gray-600">${dateLabel}</td>
                            <td class="p-3 text-gray-800">${escapeHtml(log.time_in || '-')}</td>
                            <td class="p-3 text-gray-400">${escapeHtml(log.time_out || '-')}</td>
                            <td class="p-3">${status}</td>
                        </tr>
                    `;
                }).join('');
            } catch (error) {
                console.error('Error:', error);
                tableBody.innerHTML = '<tr><td colspan="7" class="p-4 text-center text-red-400">Error loading data.</td></tr>';
            }
        }

        function renderStatus(log) {
            const status = (log.status || '').toLowerCase();

            if (status === 'unknown') {
                return '<span class="px-3 py-1 bg-red-200 text-red-900 text-xs font-bold rounded-full">UNKNOWN</span>';
            }
            if (status === 'denied') {
                return '<span class="px-3 py-1 bg-red-100 text-red-800 text-xs font-bold rounded-full">DENIED</span>';
            }
            if (log.time_out) {
                return '<span class="px-3 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">OUT</span>';
            }
            return '<span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">IN</span>';
        }

        // Close Modal
        function closeSectionModal() {
            document.getElementById('sectionModal').classList.add('hidden');
            clearInterval(modalTimer);
            modalTimer = null;
            currentSection = null;
            currentYear = null;
        }

        // Format Date
        function formatDate(dateStr) {
            const parsed = new Date(dateStr);
            if (isNaN(parsed.getTime())) return escapeHtml(dateStr);

            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return parsed.toLocaleDateString('en-US', options);
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Filter by Date (applies to the open section, if any)
        function filterByDate() {
            if (currentSection) {
                loadSectionLogs();
            }
            refreshSummary();
        }

        function clearDateFilter() {
            document.getElementById('filterDate').value = '';
            if (currentSection) {
                loadSectionLogs();
            }
            refreshSummary();
        }

        // Keep the section/year counters in sync with the guard's live feed
        async function refreshSummary() {
            if (document.hidden) return;

            try {
                const response = await fetch(SUMMARY_URL, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store'
                });

                if (!response.ok) return;

                const data = await response.json();
                const counts = data.counts || {};

                document.querySelectorAll('[data-section-count]').forEach(el => {
                    const count = counts[el.dataset.sectionCount] || 0;
                    el.textContent = `${count} ${count === 1 ? 'entry' : 'entries'} today`;
                });

                document.querySelectorAll('[data-year-total]').forEach(el => {
                    let total = 0;
                    document.querySelectorAll(`[data-section-count^="${el.dataset.yearTotal}|"]`).forEach(sectionEl => {
                        total += counts[sectionEl.dataset.sectionCount] || 0;
                    });
                    el.textContent = `${total} today`;
                });

                const totalEl = document.getElementById('total-today');
                if (totalEl) totalEl.textContent = data.total ?? 0;
            } catch (error) {
                // Network hiccup; try again on the next tick.
            }
        }

        setInterval(refreshSummary, 4000);
    </script>

</body>
</html>
