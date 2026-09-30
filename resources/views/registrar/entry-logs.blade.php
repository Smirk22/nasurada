<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entry Logs - IETI SEVS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-ieti-green { background-color: #064e29; }
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
                <h1 class="text-3xl font-bold text-gray-800">Entry Logs</h1>
                <p class="text-sm text-gray-500 mt-1">View student Time In/Out records by Year and Section.</p>
            </header>

            <!-- Page Content -->
            <main class="flex-1 px-8 py-6 space-y-6">

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow border border-gray-100 p-4 flex flex-col md:flex-row gap-4">
                    <input type="date" id="filterDate" class="border rounded-lg px-3 py-2 text-sm flex-1 focus:outline-none focus:ring-2 focus:ring-green-600">
                    <button onclick="filterByDate()" class="bg-ieti-green hover:bg-green-800 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">Filter</button>
                </div>

                <!-- Year 1 (Collapsible) -->
                <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                    <button onclick="toggleYear(1)" class="w-full flex items-center justify-between px-6 py-4 bg-gray-50 hover:bg-gray-100 transition border-b border-gray-100">
                        <h2 class="text-lg font-bold text-gray-800">Year 1</h2>
                        <svg id="icon-year-1" class="w-6 h-6 text-gray-600 transform transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    </button>
                    <div id="year-1" class="hidden space-y-4 p-6">
                        <!-- Sections for Year 1 -->
                        @foreach(['S1B1', 'S1B2'] as $section)
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <button onclick="openSectionModal('{{ $section }}', 1)" class="w-full flex items-center justify-between px-4 py-3 bg-blue-50 hover:bg-blue-100 transition">
                                    <div class="text-left">
                                        <p class="font-semibold text-gray-800">Section {{ $section }}</p>
                                        <p class="text-xs text-gray-500">Click to view entries</p>
                                    </div>
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Year 2 (Collapsible) -->
                <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                    <button onclick="toggleYear(2)" class="w-full flex items-center justify-between px-6 py-4 bg-gray-50 hover:bg-gray-100 transition border-b border-gray-100">
                        <h2 class="text-lg font-bold text-gray-800">Year 2</h2>
                        <svg id="icon-year-2" class="w-6 h-6 text-gray-600 transform transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    </button>
                    <div id="year-2" class="hidden space-y-4 p-6">
                        <!-- Sections for Year 2 -->
                        @foreach(['S4B1'] as $section)
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <button onclick="openSectionModal('{{ $section }}', 2)" class="w-full flex items-center justify-between px-4 py-3 bg-blue-50 hover:bg-blue-100 transition">
                                    <div class="text-left">
                                        <p class="font-semibold text-gray-800">Section {{ $section }}</p>
                                        <p class="text-xs text-gray-500">Click to view entries</p>
                                    </div>
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Year 3 & 4 (Collapsible) -->
                <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                    <button onclick="toggleYear(3)" class="w-full flex items-center justify-between px-6 py-4 bg-gray-50 hover:bg-gray-100 transition border-b border-gray-100">
                        <h2 class="text-lg font-bold text-gray-800">Year 3 & 4</h2>
                        <svg id="icon-year-3" class="w-6 h-6 text-gray-600 transform transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    </button>
                    <div id="year-3" class="hidden space-y-4 p-6">
                        <!-- Sections for Year 3 & 4 -->
                        @foreach(['S7C3'] as $section)
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <button onclick="openSectionModal('{{ $section }}', 3)" class="w-full flex items-center justify-between px-4 py-3 bg-blue-50 hover:bg-blue-100 transition">
                                    <div class="text-left">
                                        <p class="font-semibold text-gray-800">Section {{ $section }}</p>
                                        <p class="text-xs text-gray-500">Click to view entries</p>
                                    </div>
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Section Modal (Entry Logs Table) -->
    <div id="sectionModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl w-full max-w-4xl max-h-96 overflow-y-auto shadow-2xl">
            <!-- Modal Header -->
            <div class="sticky top-0 bg-ieti-green text-white px-6 py-4 flex justify-between items-center">
                <h3 id="modalTitle" class="text-lg font-bold"></h3>
                <button onclick="closeSectionModal()" class="text-white hover:bg-green-700 px-2 py-1 rounded">✕</button>
            </div>

            <!-- Modal Content -->
            <div class="p-6">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-600 uppercase font-semibold">
                            <th class="p-3">Student #</th>
                            <th class="p-3">Name</th>
                            <th class="p-3">Date</th>
                            <th class="p-3">Time In</th>
                            <th class="p-3">Time Out</th>
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
        // Toggle Year Sections
        function toggleYear(yearNum) {
            const yearDiv = document.getElementById(`year-${yearNum}`);
            const icon = document.getElementById(`icon-year-${yearNum}`);
            
            yearDiv.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        }

        // Open Section Modal with Entry Logs
        function openSectionModal(section, year) {
            const modal = document.getElementById('sectionModal');
            const modalTitle = document.getElementById('modalTitle');
            const tableBody = document.getElementById('modalTableBody');

            modalTitle.textContent = `Year ${year} - Section ${section}`;

            // Fetch entry logs for this section
            fetch(`/registrar/entry-logs/section/${section}`)
                .then(response => response.json())
                .then(data => {
                    tableBody.innerHTML = '';
                    
                    if (data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-400">No entry logs found.</td></tr>';
                        modal.classList.remove('hidden');
                        return;
                    }

                    data.forEach(log => {
                        const row = `
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-semibold text-gray-800">${log.student.student_number}</td>
                                <td class="p-3">${log.student.first_name} ${log.student.last_name}</td>
                                <td class="p-3">${formatDate(log.date)}</td>
                                <td class="p-3">${log.time_in || '-'}</td>
                                <td class="p-3">${log.time_out || '-'}</td>
                            </tr>
                        `;
                        tableBody.innerHTML += row;
                    });

                    modal.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    tableBody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-red-400">Error loading data.</td></tr>';
                    modal.classList.remove('hidden');
                });
        }

        // Close Modal
        function closeSectionModal() {
            document.getElementById('sectionModal').classList.add('hidden');
        }

        // Format Date
        function formatDate(dateStr) {
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateStr).toLocaleDateString('en-US', options);
        }

        // Filter by Date
        function filterByDate() {
            const date = document.getElementById('filterDate').value;
            if (date) {
                // Implementation for date filter - you can add this later
                console.log('Filtering by date:', date);
            }
        }
    </script>

</body>
</html>