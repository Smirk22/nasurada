<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IETI SEVS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --ieti-green: #064e29;
            --ieti-green-hover: #0a6b39;
        }

        .bg-ieti-green { background-color: var(--ieti-green); }
        .text-ieti-green { color: var(--ieti-green); }
        .hover\:bg-green-800:hover { background-color: #166534; }

        body { background-color: #f1f5f9; }

        .page-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
        }

        .stat-card {
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(15, 23, 42, .10);
        }

        .stat-icon {
            width: 46px;
            height: 46px;
            border-radius: .85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .progress-track {
            height: 6px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            border-radius: 999px;
            transition: width .4s ease;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, .18);
            animation: live-pulse 1.6s ease-in-out infinite;
        }
        @keyframes live-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .45; transform: scale(.85); }
        }

        .timeline { position: relative; }
        .timeline::before {
            content: '';
            position: absolute;
            left: 11px;
            top: 10px;
            bottom: 10px;
            width: 2px;
            background: #e2e8f0;
        }
        .timeline-item { position: relative; padding-left: 38px; }
        .timeline-dot {
            position: absolute;
            left: 4px;
            top: 4px;
            width: 16px;
            height: 16px;
            border-radius: 999px;
            background: #10b981;
            border: 3px solid #fff;
            box-shadow: 0 0 0 2px #d1fae5;
        }
    </style>
</head>
<body class="font-sans text-slate-800 antialiased">

    @php
        $hour = (int) now()->format('H');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $enrolledCount = (int) ($enrollmentStatus['Enrolled'] ?? 0);
        $sectionCount = collect($sectionBreakdown)->sum(fn ($rows) => $rows->count());
        $maxEntry = max(1, (int) ($entrySummaryToday->max() ?? 0));
    @endphp

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        @include('partials.sidebar')

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">

            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-slate-200 px-4 sm:px-6 lg:px-8 py-5 md:py-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="stat-icon bg-emerald-50 text-emerald-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-ieti-green">Registrar Workspace</p>
                            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 mt-0.5">
                                {{ $greeting }}, {{ auth()->user()->name }}
                            </h1>
                            <p class="text-sm text-slate-500 mt-1">
                                {{ now()->format('l, M j, Y') }} · Here's an overview of student records and gate activity.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="hidden sm:inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                            <span class="live-dot"></span>
                            Live gate feed
                        </span>
                        <a href="{{ route('entry-logs.index') }}"
                           class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Entry Logs
                        </a>
                        <a href="{{ route('registrar.students') }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-ieti-green px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-800 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Manage Students
                        </a>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6 md:py-8 space-y-6">

                @if(session('success'))
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-sm">
                        {{ session('success') }}
                    </div>
                @endif

                <section id="registrar-tab-overview" class="space-y-6">
                    <!-- Key Metrics -->
                    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="page-card stat-card p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Students</p>
                                <p class="text-3xl font-extrabold text-slate-900 mt-2">{{ $totalStudents }}</p>
                                <p class="text-xs text-slate-400 mt-1">All registered records</p>
                            </div>
                            <div class="stat-icon bg-blue-50 text-blue-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zM5 20h10v-2a7 7 0 00-10 0v2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="page-card stat-card p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Enrolled</p>
                                <p class="text-3xl font-extrabold text-emerald-700 mt-2">{{ $enrolledCount }}</p>
                                <p class="text-xs text-slate-400 mt-1">
                                    {{ $totalStudents ? round($enrolledCount / $totalStudents * 100) : 0 }}% of all students
                                </p>
                            </div>
                            <div class="stat-icon bg-emerald-50 text-emerald-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="page-card stat-card p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sections</p>
                                <p class="text-3xl font-extrabold text-indigo-700 mt-2">{{ $sectionCount }}</p>
                                <p class="text-xs text-slate-400 mt-1">Across {{ $sectionBreakdown->count() }} year levels</p>
                            </div>
                            <div class="stat-icon bg-indigo-50 text-indigo-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="page-card stat-card p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Entries Today</p>
                                <p id="entries-today-count" class="text-3xl font-extrabold text-ieti-green mt-2">{{ $entriesToday ?? 0 }}</p>
                                <p class="text-xs text-emerald-600 mt-1 inline-flex items-center gap-1.5">
                                    <span class="live-dot"></span> Updating live
                                </p>
                            </div>
                            <div class="stat-icon bg-emerald-50 text-emerald-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Main Overview -->
                <section class="grid grid-cols-1 xl:grid-cols-5 gap-6">
                    <div class="xl:col-span-3 space-y-6">

                        <!-- Enrollment Status -->
                        <div class="page-card p-6">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900">Enrollment Status</h2>
                                    <p class="text-xs text-slate-500 mt-0.5">Student records grouped by current status.</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                    {{ $enrollmentStatus->count() }} status{{ $enrollmentStatus->count() === 1 ? '' : 'es' }}
                                </span>
                            </div>

                            <div class="mt-5 space-y-4">
                                @forelse($enrollmentStatus as $status => $count)
                                    @php
                                        $percent = $totalStudents ? round($count / $totalStudents * 100) : 0;
                                        $isEnrolled = strtolower((string) $status) === 'enrolled';
                                    @endphp
                                    <div>
                                        <div class="flex items-center justify-between gap-3 text-sm">
                                            <span class="font-medium text-slate-700">{{ $status ?: 'Unspecified' }}</span>
                                            <span class="font-bold {{ $isEnrolled ? 'text-emerald-700' : 'text-slate-700' }}">
                                                {{ $count }}
                                                <span class="text-xs font-medium text-slate-400">({{ $percent }}%)</span>
                                            </span>
                                        </div>
                                        <div class="progress-track mt-2">
                                            <div class="progress-fill {{ $isEnrolled ? 'bg-emerald-500' : 'bg-slate-400' }}" style="width: {{ $percent }}%"></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-400">No student records yet.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Year & Section Breakdown -->
                        <div class="page-card p-6">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900">Students by Year & Section</h2>
                                    <p class="text-xs text-slate-500 mt-0.5">Current distribution across year levels.</p>
                                </div>
                                <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                                    {{ $sectionCount }} sections
                                </span>
                            </div>

                            <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                                @forelse($sectionBreakdown as $year => $rows)
                                    @php $yearTotal = $rows->sum('total'); @endphp
                                    <div class="rounded-xl border border-slate-100 overflow-hidden">
                                        <div class="flex items-center justify-between gap-2 bg-slate-50 px-4 py-2.5">
                                            <span class="text-sm font-bold text-slate-700">Year {{ $year }}</span>
                                            <span class="text-xs font-medium text-slate-500">{{ $yearTotal }} students</span>
                                        </div>
                                        <div class="divide-y divide-slate-100">
                                            @foreach($rows as $row)
                                                @php $percent = $totalStudents ? round($row->total / $totalStudents * 100) : 0; @endphp
                                                <div class="px-4 py-3">
                                                    <div class="flex items-center justify-between text-sm">
                                                        <span class="text-slate-600">{{ $row->section }}</span>
                                                        <span class="font-semibold text-slate-800">{{ $row->total }}</span>
                                                    </div>
                                                    <div class="progress-track mt-2">
                                                        <div class="progress-fill bg-ieti-green" style="width: {{ $percent }}%"></div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-400">No sections assigned yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="xl:col-span-2 space-y-6">

                        <!-- Today's Entry Summary -->
                        <div class="page-card p-6">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900">Today's Entry Summary</h2>
                                    <p class="text-xs text-slate-500 mt-0.5">Granted entries by year and section.</p>
                                </div>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                                    {{ $entrySummaryToday->sum() }}
                                </span>
                            </div>

                            <div class="mt-5 space-y-4 max-h-80 overflow-y-auto pr-1">
                                @forelse($entrySummaryToday as $group => $count)
                                    @php $percent = round($count / $maxEntry * 100); @endphp
                                    <div>
                                        <div class="flex items-center justify-between gap-3 text-sm">
                                            <span class="font-medium text-slate-700">{{ $group }}</span>
                                            <span class="font-bold text-emerald-700">{{ $count }}</span>
                                        </div>
                                        <div class="progress-track mt-2">
                                            <div class="progress-fill bg-emerald-500" style="width: {{ $percent }}%"></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-400">No granted entries recorded today.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Recent Student Activity -->
                        <div class="page-card p-6">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900">Recent Student Activity</h2>
                                    <p class="text-xs text-slate-500 mt-0.5">Latest student-record changes from registrar activity.</p>
                                </div>
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                    Latest {{ $recentActivities->count() }}
                                </span>
                            </div>

                            <div class="timeline mt-5">
                                @forelse($recentActivities as $activity)
                                    <div class="timeline-item pb-4 last:pb-0">
                                        <span class="timeline-dot"></span>
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-slate-800">{{ $activity->action }}</p>
                                                <p class="text-xs text-slate-500 mt-0.5 break-words">{{ $activity->details }}</p>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <p class="text-xs font-medium text-slate-600">{{ $activity->user_name }}</p>
                                                <p class="text-[11px] text-slate-400">{{ $activity->created_at?->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-400">No student activity recorded yet.</p>
                                @endforelse
                            </div>
                        </div>

                    </div>
                </section>
                </section>

                <section id="registrar-tab-export" class="hidden space-y-6">
                    <div class="page-card p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Export Records</h2>
                                <p class="text-xs text-slate-500 mt-0.5">Download student records for offline use.</p>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                                {{ $totalStudents }} total record{{ $totalStudents === 1 ? '' : 's' }}
                            </span>
                        </div>

                        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4">
                            <label for="export_section" class="block text-xs font-bold uppercase tracking-wide text-slate-500">
                                Filter by section
                            </label>
                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                <select id="export_section" name="section" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm focus:border-ieti-green focus:ring-ieti-green">
                                    <option value="All Sections">All Sections</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section }}">{{ $section }}</option>
                                    @endforeach
                                </select>
                                <p id="export_scope_note" class="text-xs text-slate-500">Exports the full student list.</p>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Format</p>
                                <p class="mt-1 text-sm font-bold text-slate-800">Excel (.xls)</p>
                                <p class="text-[11px] text-slate-400 mt-1">Opens in Excel and spreadsheet apps</p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Scope</p>
                                <p id="export_scope_title" class="mt-1 text-sm font-bold text-slate-800">All student records</p>
                                <p id="export_scope_detail" class="text-[11px] text-slate-400 mt-1">Exports the full student list</p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Photos</p>
                                <p class="mt-1 text-sm font-bold text-slate-800">Path included</p>
                                <p class="text-[11px] text-slate-400 mt-1">Image files are not embedded</p>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <a id="download_student_records" href="{{ route('registrar.students.export') }}" data-export-base-url="{{ route('registrar.students.export') }}"
                               class="inline-flex items-center gap-2 rounded-lg bg-ieti-green px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-800 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l4-4m-4 4l-4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"></path>
                                </svg>
                                Download Student Records
                            </a>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                        The export includes student number, name, course, year and section, phone number, RFID tag, status, school year, enrollment confirmation, card type, validity date, photo path, and timestamps.
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script>
        const registrarTabLinks = document.querySelectorAll('.registrar-tab-link');
        const registrarSections = {
            overview: document.getElementById('registrar-tab-overview'),
            export: document.getElementById('registrar-tab-export')
        };

        function registrarSectionFromHash() {
            return window.location.hash === '#export-records' ? 'export' : 'overview';
        }

        function setRegistrarSection(section, updateHash = true) {
            Object.entries(registrarSections).forEach(([key, element]) => {
                if (element) element.classList.toggle('hidden', key !== section);
            });

            registrarTabLinks.forEach((link) => {
                link.classList.toggle('bg-green-700', link.dataset.registrarTabLink === section);
            });

            if (updateHash) {
                history.replaceState(null, '', section === 'export' ? '#export-records' : window.location.pathname + window.location.search);
            }
        }

        registrarTabLinks.forEach((link) => {
            link.addEventListener('click', (event) => {
                if (!document.getElementById('registrar-tab-overview')) return;

                const section = link.dataset.registrarTabLink;
                if (section !== 'overview' && section !== 'export') return;

                event.preventDefault();
                history.pushState(null, '', section === 'export' ? '#export-records' : window.location.pathname + window.location.search);
                setRegistrarSection(section, false);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });

        window.addEventListener('hashchange', () => setRegistrarSection(registrarSectionFromHash(), false));
        setRegistrarSection(registrarSectionFromHash(), false);

        const exportSectionSelect = document.getElementById('export_section');
        const downloadStudentRecords = document.getElementById('download_student_records');
        const exportScopeTitle = document.getElementById('export_scope_title');
        const exportScopeDetail = document.getElementById('export_scope_detail');
        const exportScopeNote = document.getElementById('export_scope_note');

        function updateExportSectionFilter() {
            if (!exportSectionSelect || !downloadStudentRecords) return;

            const section = exportSectionSelect.value;
            const url = new URL(downloadStudentRecords.dataset.exportBaseUrl, window.location.origin);

            if (section && section !== 'All Sections') {
                url.searchParams.set('section', section);
            }

            downloadStudentRecords.href = url.pathname + url.search;

            const isAllSections = section === 'All Sections';
            if (exportScopeTitle) exportScopeTitle.textContent = isAllSections ? 'All student records' : 'Section '.concat(section);
            if (exportScopeDetail) exportScopeDetail.textContent = isAllSections ? 'Exports the full student list' : 'Exports only this section';
            if (exportScopeNote) exportScopeNote.textContent = isAllSections
                ? 'Exports the full student list.'
                : 'Only students in '.concat(section, ' will be downloaded.');
        }

        exportSectionSelect?.addEventListener('change', updateExportSectionFilter);
        updateExportSectionFilter();

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
