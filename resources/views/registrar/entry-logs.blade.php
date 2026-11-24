<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entry Logs - IETI SEVS</title>

    {{-- Bootstrap 5 (CDN) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    {{-- Tailwind is still required by the shared sidebar partial --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'ieti-green': '#064e29',
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --ieti-green: #064e29;
            --ieti-green-hover: #0a6b39;
        }

        body { background-color: #f3f4f6; }

        .hidden { display: none !important; }

        .page-card {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 .125rem .5rem rgba(0, 0, 0, .06);
        }

        .btn-ieti {
            background-color: var(--ieti-green);
            border-color: var(--ieti-green);
            color: #fff;
        }
        .btn-ieti:hover,
        .btn-ieti:focus {
            background-color: var(--ieti-green-hover);
            border-color: var(--ieti-green-hover);
            color: #fff;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: .85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .year-toggle {
            border: 0;
            background: transparent;
            width: 100%;
            text-align: left;
            padding: 1rem 1.25rem;
            transition: background-color .15s ease;
        }
        .year-toggle:hover { background-color: #f8fafc; }

        .chevron { transition: transform .2s ease; }
        .rotate-180 { transform: rotate(180deg); }

        .section-row {
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            background: #fff;
            width: 100%;
            text-align: left;
            padding: .85rem 1rem;
            transition: all .15s ease;
        }
        .section-row:hover {
            background-color: #f0fdf4;
            border-color: #86efac;
            transform: translateX(2px);
        }

        .table thead th {
            white-space: nowrap;
            font-size: .72rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #6c757d;
        }
        .table tbody tr { transition: background-color .15s ease; }

        .live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, .18);
            animation: live-pulse 1.5s ease-in-out infinite;
        }
        @keyframes live-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .45; transform: scale(.85); }
        }

        .badge-live { background-color: #dcfce7; color: #166534; }
        .badge-archive { background-color: #fef3c7; color: #92400e; }
        .badge-archive .live-dot {
            background-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, .18);
        }

        .modal-shell {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, .18);
            overflow: hidden;
        }
        .modal-backdrop-custom {
            background: rgba(15, 23, 42, .55);
            backdrop-filter: blur(2px);
        }
    </style>
</head>
<body class="font-sans">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        @include('partials.sidebar')

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Header -->
            <header class="bg-white shadow-sm px-3 px-md-4 py-3 py-md-4 border-bottom border-gray-200">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <h1 class="h3 fw-bold text-dark mb-1">Entry Logs</h1>
                            <p class="text-muted small mb-0">Scans from the guard's live feed, grouped by year and section.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span id="live-badge" class="badge rounded-pill badge-live d-inline-flex align-items-center gap-2 px-3 py-2">
                            <span class="live-dot"></span>
                            <span id="live-label">Live</span>
                        </span>
                        <span class="badge rounded-pill bg-light text-secondary border px-3 py-2 fw-semibold">
                            <i class="bi bi-shield-check me-1"></i> Verification records
                        </span>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 px-3 px-md-4 py-4 py-md-5">

                <!-- Summary Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <div class="card page-card h-100">
                            <div class="card-body d-flex align-items-center gap-3 p-3 p-md-4">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary flex-shrink-0">
                                    <i class="bi bi-check2-circle"></i>
                                </div>
                                <div>
                                    <p class="text-muted small fw-semibold text-uppercase mb-1">Verified entries</p>
                                    <p class="h4 fw-bold text-dark mb-0" id="total-today">{{ $totalToday ?? 0 }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card page-card h-100">
                            <div class="card-body d-flex align-items-center gap-3 p-3 p-md-4">
                                <div class="stat-icon bg-info bg-opacity-10 text-info flex-shrink-0">
                                    <i class="bi bi-diagram-3"></i>
                                </div>
                                <div>
                                    <p class="text-muted small fw-semibold text-uppercase mb-1">Sections with entries</p>
                                    <p class="h4 fw-bold text-dark mb-0" id="sections-with-entries">0</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card page-card h-100">
                            <div class="card-body d-flex align-items-center gap-3 p-3 p-md-4">
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning flex-shrink-0">
                                    <i class="bi bi-calendar2-week"></i>
                                </div>
                                <div>
                                    <p class="text-muted small fw-semibold text-uppercase mb-1">Showing</p>
                                    <p class="h6 fw-bold text-dark mb-0" id="selected-date-label">Today</p>
                                    <p class="small text-muted mb-0"><span id="total-label">Today:</span> verified</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card page-card mb-4">
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-6 col-lg-5">
                                <label for="filterDate" class="form-label small text-muted fw-semibold text-uppercase mb-1">Filter entries by date</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-calendar3 text-muted"></i></span>
                                    <input type="date" id="filterDate" onchange="filterByDate()" class="form-control">
                                </div>
                                <div class="form-text">Selecting a date applies the filter automatically.</div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-7 d-flex flex-wrap gap-2 justify-content-start justify-content-md-end">
                                <button type="button" onclick="setToday()" class="btn btn-ieti btn-sm px-3">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Today
                                </button>
                                <button type="button" onclick="clearDateFilter()" class="btn btn-outline-secondary btn-sm px-3">
                                    <i class="bi bi-x-circle me-1"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Single Student Lookup -->
                <div class="card page-card mb-4">
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-6 col-lg-5">
                                <label for="studentNumberInput" class="form-label small text-muted fw-semibold text-uppercase mb-1">Find one student's entry logs</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-person-badge text-muted"></i></span>
                                    <input type="text" id="studentNumberInput" class="form-control" placeholder="Enter student number (e.g. MAR87654321)" autocomplete="off" onkeydown="if (event.key === 'Enter') { event.preventDefault(); openStudentModal(); }">
                                </div>
                                <div class="form-text">Searches all dates and shows only this student's records.</div>
                                <div class="form-text hidden" id="recentStudentNumberHint">
                                    Most recent student number searched:
                                    <button type="button" id="recentStudentNumber" class="btn btn-link btn-sm p-0 align-baseline font-monospace fw-semibold text-decoration-none" style="color: var(--ieti-green); font-size: inherit;"></button>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-7 d-flex flex-wrap gap-2 justify-content-start justify-content-md-end">
                                <button type="button" onclick="openStudentModal()" class="btn btn-ieti btn-sm px-3">
                                    <i class="bi bi-search me-1"></i> View student logs
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Year / Section groups (built from actual student records) -->
                <div id="year-groups" class="vstack gap-3">
                    @foreach($yearLevels as $year => $sections)
                        @php
                            $yearTotal = collect($sections)->sum(fn ($section) => $sectionCounts[$year . '|' . $section] ?? 0);
                        @endphp

                        <div class="card page-card overflow-hidden">
                            <button type="button" data-year-toggle="{{ $year }}" aria-expanded="false"
                                    onclick="toggleYear({{ $year }})" class="year-toggle d-flex align-items-center justify-content-between gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="stat-icon bg-ieti-green bg-opacity-10 text-ieti-green flex-shrink-0" style="width:40px;height:40px;font-size:.9rem;font-weight:700;">
                                        Y{{ $year }}
                                    </span>
                                    <div>
                                        <h2 class="h6 fw-bold text-dark mb-0">Year {{ $year }}</h2>
                                        <p class="text-muted small mb-0">Click to view sections</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span data-year-total="{{ $year }}" id="year-total-{{ $year }}" class="badge rounded-pill bg-success bg-opacity-10 text-success px-3 py-2">
                                        {{ $yearTotal }} {{ Str::plural('entry', $yearTotal) }}
                                    </span>
                                    <i id="icon-year-{{ $year }}" class="bi bi-chevron-down text-muted chevron"></i>
                                </div>
                            </button>

                            <div id="year-{{ $year }}" class="hidden border-top p-3 p-md-4">
                                @forelse($sections as $section)
                                    <div class="mb-2">
                                        <button type="button"
                                                onclick="openSectionModal('{{ $section }}', {{ $year }})"
                                                class="section-row d-flex align-items-center justify-content-between gap-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="stat-icon bg-primary bg-opacity-10 text-primary flex-shrink-0" style="width:38px;height:38px;font-size:.95rem;">
                                                    <i class="bi bi-people"></i>
                                                </span>
                                                <div>
                                                    <p class="fw-semibold text-dark mb-0">Section {{ $section }}</p>
                                                    <p class="text-muted small mb-0">Click to view entries</p>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span data-section-count="{{ $year }}|{{ $section }}" class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2">
                                                    {{ $sectionCounts[$year . '|' . $section] ?? 0 }} {{ Str::plural('entry', $sectionCounts[$year . '|' . $section] ?? 0) }}
                                                </span>
                                                <i class="bi bi-chevron-right text-primary"></i>
                                            </div>
                                        </button>
                                    </div>
                                @empty
                                    <p class="text-muted small mb-0">No sections registered for this year yet.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>

            </main>
        </div>
    </div>

    <!-- Section Modal (Entry Logs Table) -->
    <div id="sectionModal" class="hidden modal-backdrop-custom fixed inset-0 d-flex align-items-center justify-content-center p-3 p-md-4" style="z-index:1050;">
        <div class="card modal-shell w-100" style="max-width: 64rem; max-height: 88vh;">
            <!-- Modal Header -->
            <div class="d-flex justify-content-between align-items-center text-white px-3 px-md-4 py-3" style="background-color: var(--ieti-green);">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-clock-history fs-5"></i>
                    <h3 id="modalTitle" class="h6 fw-bold mb-0"></h3>
                    <span id="modal-live-badge" class="badge rounded-pill badge-live d-inline-flex align-items-center gap-2 px-3 py-2">
                        <span class="live-dot"></span>
                        <span id="modal-live-label">Live</span>
                    </span>
                </div>
                <button type="button" onclick="closeSectionModal()" class="btn btn-sm btn-light btn-square border-0" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-3 p-md-4 overflow-auto">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Student #</th>
                                <th>Name</th>
                                <th>Year / Section</th>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th class="pe-3">Status</th>
                            </tr>
                        </thead>
                        <tbody id="modalTableBody" class="small">
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination footer -->
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 pt-3 mt-2 border-top">
                    <p id="modalRange" class="text-muted small mb-0">Loading entries...</p>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" id="prevPageBtn" onclick="changePage(-1)" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-chevron-left me-1"></i> Prev
                        </button>
                        <span id="pageInfo" class="small fw-semibold text-dark whitespace-nowrap">Page 1 of 1</span>
                        <button type="button" id="nextPageBtn" onclick="changePage(1)" class="btn btn-sm btn-outline-secondary">
                            Next <i class="bi bi-chevron-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        const SUMMARY_URL = "{{ route('entry-logs.summary', [], false) }}";
        const SECTION_URL = "{{ route('entry-logs.getBySection', ['section' => '__SECTION__'], false) }}";
        const STUDENT_URL = "{{ route('entry-logs.getByStudentNumber', [], false) }}";
        const TODAY = "{{ today()->toDateString() }}";

        // The filter always has a concrete date — clearing it returns to today.
        function selectedDate() {
            return document.getElementById('filterDate').value || TODAY;
        }

        // Remember the last student number looked up here, like the Add Student
        // hint does with the most recently entered number.
        const RECENT_STUDENT_NUMBER_KEY = 'entryLogs.recentStudentNumber';

        function recentStudentNumber() {
            try {
                return localStorage.getItem(RECENT_STUDENT_NUMBER_KEY) || '';
            } catch (error) {
                return '';
            }
        }

        function rememberStudentNumber(number) {
            try {
                localStorage.setItem(RECENT_STUDENT_NUMBER_KEY, number);
            } catch (error) {
                // Storage may be unavailable; the lookup itself still works.
            }
        }

        function renderRecentStudentNumber() {
            const input = document.getElementById('studentNumberInput');
            const hint = document.getElementById('recentStudentNumberHint');
            const button = document.getElementById('recentStudentNumber');
            const recent = recentStudentNumber();

            if (!input || !hint || !button) return;

            if (!recent) {
                hint.classList.add('hidden');
                input.placeholder = 'Enter student number (e.g. MAR87654321)';
                return;
            }

            hint.classList.remove('hidden');
            button.textContent = recent;
            button.setAttribute('aria-label', `Use student number ${recent}`);
            input.placeholder = `e.g., ${recent}`;
        }

        document.getElementById('recentStudentNumber')?.addEventListener('click', function () {
            const input = document.getElementById('studentNumberInput');

            if (input) {
                input.value = recentStudentNumber();
                input.focus();
            }
        });

        let currentSection = null;
        let currentYear = null;
        let currentStudentNumber = null;
        let modalTimer = null;

        // Pagination state for the section modal (server-side pagination)
        let currentPage = 1;
        let lastPage = 1;
        let totalEntries = 0;

        // Toggle Year Sections
        function toggleYear(yearNum) {
            const yearDiv = document.getElementById(`year-${yearNum}`);
            const icon = document.getElementById(`icon-year-${yearNum}`);
            const toggle = document.querySelector(`[data-year-toggle="${yearNum}"]`);

            if (!yearDiv || !icon) return;

            yearDiv.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');

            if (toggle) {
                toggle.setAttribute('aria-expanded', String(!yearDiv.classList.contains('hidden')));
            }
        }

        // Open Section Modal with Entry Logs
        function openSectionModal(section, year) {
            currentSection = section;
            currentYear = year;
            currentStudentNumber = null;

            document.getElementById('modalTitle').textContent = `Year ${year} — Section ${section}`;
            document.getElementById('sectionModal').classList.remove('hidden');

            currentPage = 1;
            lastPage = 1;
            totalEntries = 0;

            loadSectionLogs();

            clearInterval(modalTimer);
            modalTimer = setInterval(loadSectionLogs, 4000);
        }

        // Open the same logs modal in single-student mode, ignoring the date
        // filter so the full history for that student is shown.
        function openStudentModal() {
            const input = document.getElementById('studentNumberInput');
            const number = (input?.value || '').trim();

            if (!number) {
                input?.focus();
                return;
            }

            rememberStudentNumber(number);
            renderRecentStudentNumber();

            currentSection = null;
            currentYear = null;
            currentStudentNumber = number;

            document.getElementById('modalTitle').textContent = `Student ${number}`;
            document.getElementById('sectionModal').classList.remove('hidden');

            currentPage = 1;
            lastPage = 1;
            totalEntries = 0;

            loadSectionLogs();

            clearInterval(modalTimer);
            modalTimer = setInterval(loadSectionLogs, 4000);
        }

        // Fetch the selected section's entries (year-aware and always ordered newest first).
        // The endpoint paginates, so only one page is transferred and rendered at a time.
        async function loadSectionLogs() {
            const isStudentMode = !!currentStudentNumber;

            if (!currentSection && !isStudentMode) return;

            const tableBody = document.getElementById('modalTableBody');
            let url;

            if (isStudentMode) {
                const params = new URLSearchParams({ student_number: currentStudentNumber, page: currentPage });
                url = STUDENT_URL + '?' + params.toString();
            } else {
                const params = new URLSearchParams({ year: currentYear, page: currentPage, date: selectedDate() });
                url = SECTION_URL.replace('__SECTION__', encodeURIComponent(currentSection)) + '?' + params.toString();
            }

            try {
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store'
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(payload.error || 'HTTP ' + response.status);
                }

                if (isStudentMode && payload.student) {
                    const student = payload.student;
                    const fullName = `${student.first_name || ''} ${student.last_name || ''}`.trim();
                    document.getElementById('modalTitle').textContent =
                        `${fullName || 'Student'} (${student.student_number || currentStudentNumber})`;
                }

                // Laravel paginator response: { data: [...], current_page, last_page, total, ... }
                const data = Array.isArray(payload) ? payload : (payload.data || []);
                const isPaginated = !Array.isArray(payload) && typeof payload.last_page === 'number';

                if (isPaginated) {
                    lastPage = Math.max(1, payload.last_page);
                    totalEntries = payload.total || 0;

                    // Entries can disappear while the modal is open (date filter,
                    // deleted records). Step back to the last real page.
                    if (currentPage > lastPage) {
                        currentPage = lastPage;

                        return loadSectionLogs();
                    }
                }

                renderPagination(isPaginated ? payload : null);

                if (data.length === 0) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox d-block fs-2 mb-2"></i>
                                ${isStudentMode ? 'No entry logs found for this student.' : 'No entry logs found for this section yet.'}
                            </td>
                        </tr>`;
                    return;
                }

                tableBody.innerHTML = data.map(log => {
                    const student = log.student || {};
                    const name = `${student.first_name || ''} ${student.last_name || ''}`.trim() || 'Unknown';
                    const status = renderStatus(log);
                    const dateLabel = log.date ? formatDate(log.date) : '-';

                    return `
                        <tr>
                            <td class="ps-3 fw-semibold text-dark">${escapeHtml(student.student_number || '-')}</td>
                            <td>${escapeHtml(name)}</td>
                            <td class="text-muted">Year ${escapeHtml(student.year_level || '-')} &middot; ${escapeHtml(student.section || '-')}</td>
                            <td class="text-muted">${dateLabel}</td>
                            <td>${escapeHtml(log.time_in || '-')}</td>
                            <td class="text-muted">${escapeHtml(log.time_out || '-')}</td>
                            <td class="pe-3">${status}</td>
                        </tr>
                    `;
                }).join('');
            } catch (error) {
                console.error('Error:', error);
                tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${escapeHtml(error.message || 'Error loading data.')}</td></tr>`;
            }
        }

        function renderStatus(log) {
            const status = (log.status || '').toLowerCase();

            if (status === 'unknown') {
                return '<span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2">UNKNOWN</span>';
            }
            if (status === 'denied') {
                return '<span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2">DENIED</span>';
            }
            if (log.time_out) {
                return '<span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2">OUT</span>';
            }
            return '<span class="badge bg-success bg-opacity-10 text-success px-3 py-2">IN</span>';
        }

        // Update the "Showing x–y of z" summary and the Prev / Next buttons.
        function renderPagination(payload) {
            const range = document.getElementById('modalRange');
            const info = document.getElementById('pageInfo');
            const prevBtn = document.getElementById('prevPageBtn');
            const nextBtn = document.getElementById('nextPageBtn');

            if (!range || !info || !prevBtn || !nextBtn) return;

            if (payload) {
                const total = payload.total || 0;
                const from = payload.from || 0;
                const to = payload.to || 0;

                range.textContent = total === 0
                    ? 'No entries'
                    : `Showing ${from}–${to} of ${total} ${total === 1 ? 'entry' : 'entries'}`;
            } else {
                range.textContent = `${totalEntries} ${totalEntries === 1 ? 'entry' : 'entries'}`;
            }

            info.textContent = `Page ${currentPage} of ${lastPage}`;
            prevBtn.disabled = currentPage <= 1;
            nextBtn.disabled = currentPage >= lastPage;
        }

        function changePage(delta) {
            if (!currentSection && !currentStudentNumber) return;

            const target = Math.min(lastPage, Math.max(1, currentPage + delta));

            if (target === currentPage) return;

            currentPage = target;
            loadSectionLogs();
        }

        // Close Modal
        function closeSectionModal() {
            document.getElementById('sectionModal').classList.add('hidden');
            clearInterval(modalTimer);
            modalTimer = null;
            currentSection = null;
            currentYear = null;
            currentStudentNumber = null;
            currentPage = 1;
            lastPage = 1;
            totalEntries = 0;
        }

        // Format a Y-m-d date without the UTC shift of new Date('YYYY-MM-DD').
        function formatDate(dateStr) {
            const parts = String(dateStr || '').split('-');
            if (parts.length !== 3) return escapeHtml(dateStr ?? '-');

            const parsed = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
            if (isNaN(parsed.getTime())) return escapeHtml(dateStr);

            return parsed.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
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
            currentPage = 1;
            showSelectedDateLabel();

            if (currentSection || currentStudentNumber) {
                loadSectionLogs();
            }
            // Explicit clicks must refresh even when the tab reports as hidden.
            refreshSummary(true);
        }

        function setToday() {
            document.getElementById('filterDate').value = '';
            filterByDate();
        }

        function clearDateFilter() {
            document.getElementById('filterDate').value = '';
            currentPage = 1;
            showSelectedDateLabel();

            if (currentSection || currentStudentNumber) {
                loadSectionLogs();
            }
            refreshSummary(true);
        }

        // Give instant feedback while the summary request is in flight.
        function showSelectedDateLabel() {
            const date = selectedDate();
            const label = document.getElementById('total-label');
            const showing = document.getElementById('selected-date-label');

            if (label) label.textContent = date === TODAY ? 'Today:' : `On ${formatDate(date)}:`;
            if (showing) showing.textContent = date === TODAY ? 'Today' : formatDate(date);
        }

        // Keep the section/year counters in sync with the guard's live feed.
        // Background polling skips hidden tabs; user-triggered filters pass force=true.
        async function refreshSummary(force = false) {
            if (!force && document.hidden) return;

            const params = new URLSearchParams({ date: selectedDate() });

            try {
                const response = await fetch(SUMMARY_URL + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store'
                });

                if (!response.ok) return;

                const data = await response.json();
                const counts = data.counts || {};
                const isToday = data.is_today !== false;

                document.querySelectorAll('[data-section-count]').forEach(el => {
                    const count = counts[el.dataset.sectionCount] || 0;
                    el.textContent = `${count} ${count === 1 ? 'entry' : 'entries'}`;
                });

                document.querySelectorAll('[data-year-total]').forEach(el => {
                    let total = 0;
                    document.querySelectorAll(`[data-section-count^="${el.dataset.yearTotal}|"]`).forEach(sectionEl => {
                        total += counts[sectionEl.dataset.sectionCount] || 0;
                    });
                    el.textContent = `${total} ${total === 1 ? 'entry' : 'entries'}`;
                });

                const totalEl = document.getElementById('total-today');
                if (totalEl) totalEl.textContent = data.total ?? 0;

                const sectionsEl = document.getElementById('sections-with-entries');
                if (sectionsEl) sectionsEl.textContent = Object.values(counts).filter(count => count > 0).length;

                const totalLabel = document.getElementById('total-label');
                if (totalLabel) totalLabel.textContent = isToday ? 'Today:' : `On ${formatDate(data.date)}:`;

                const showingEl = document.getElementById('selected-date-label');
                if (showingEl) showingEl.textContent = isToday ? 'Today' : formatDate(data.date);

                ['live-label', 'modal-live-label'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = isToday ? 'Live' : 'Archive';
                });

                ['live-badge', 'modal-live-badge'].forEach(id => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.classList.toggle('badge-archive', !isToday);
                    el.classList.toggle('badge-live', isToday);
                });
            } catch (error) {
                // Network hiccup; try again on the next tick.
            }
        }

        // Click outside the modal or press Escape to close it.
        document.getElementById('sectionModal').addEventListener('click', function (event) {
            if (event.target === this) closeSectionModal();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && (currentSection || currentStudentNumber)) closeSectionModal();
        });

        showSelectedDateLabel();
        renderRecentStudentNumber();
        refreshSummary();
        setInterval(refreshSummary, 4000);
    </script>

</body>
</html>
