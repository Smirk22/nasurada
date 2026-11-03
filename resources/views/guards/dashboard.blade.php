<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard Dashboard - IETI SEVS</title>

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

        body {
            background-color: #f3f4f6;
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

        .page-card {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 .125rem .5rem rgba(0, 0, 0, .06);
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: .9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .avatar-badge {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            background-color: var(--ieti-green);
            color: #fff;
        }

        .table thead th {
            white-space: nowrap;
            font-size: .72rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #6c757d;
        }

        .table tbody tr {
            transition: background-color .15s ease;
        }

        .feed-scroll {
            max-height: 420px;
            overflow-y: auto;
        }
        .feed-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background-color: #f8f9fa;
        }

        .photo-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .8rem;
        }
        .photo-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .rfid-code {
            font-family: var(--bs-font-monospace);
            font-size: .78rem;
        }

        .rfid-picker {
            position: relative;
        }

        .rfid-dropdown {
            position: absolute;
            top: calc(100% + .25rem);
            left: 0;
            right: 0;
            z-index: 1060;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: .5rem;
            box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .15);
            overflow: hidden;
        }

        .rfid-list {
            max-height: 240px;
            overflow-y: auto;
        }

        .rfid-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: .55rem .75rem;
            border: 0;
            border-bottom: 1px solid #f1f3f5;
            background: transparent;
            color: #212529;
            font-family: var(--bs-font-monospace);
            font-size: .82rem;
            text-align: left;
        }

        .rfid-option:last-child {
            border-bottom: 0;
        }

        .rfid-option:hover,
        .rfid-option:focus,
        .rfid-option.selected {
            background: #eef7f1;
            color: var(--ieti-green);
            outline: none;
        }

        .rfid-empty {
            padding: .75rem;
            color: #6c757d;
            font-size: .82rem;
            text-align: center;
        }

        @keyframes live-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .35; transform: scale(.8); }
        }
        .live-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #198754;
            animation: live-pulse 1.5s ease-in-out infinite;
        }
    </style>
</head>
<body class="font-sans min-h-screen">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        @include('partials.sidebar')

        <!-- Main Content -->
        <div class="flex-1 d-flex flex-column" style="min-width: 0;">
            <!-- Top Header -->
            <header class="bg-white shadow-sm px-4 px-lg-5 py-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">
                        <i class="bi bi-shield-check text-success me-2"></i>Security Guard Dashboard
                    </h1>
                    <p id="dateTime" class="text-muted small mb-0"></p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end">
                        <p class="fw-semibold text-dark mb-0">{{ auth()->user()->username }}</p>
                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">
                            {{ ucfirst(auth()->user()->role) }}
                        </span>
                    </div>
                    <div class="avatar-badge">
                        {{ strtoupper(substr(auth()->user()->username, 0, 1)) }}
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-3 p-md-4 p-lg-5">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Stats Cards -->
                <div class="row g-3 g-lg-4 mb-4">
                    <!-- Students Verified Today -->
                    <div class="col-12 col-md-4">
                        <div class="card page-card h-100 border-start border-4 border-success">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted small fw-semibold mb-1">Students Verified Today</p>
                                    <p id="stat-verified" class="display-6 fw-bold text-dark mb-0">{{ $studentsVerifiedToday ?? 0 }}</p>
                                </div>
                                <span class="stat-icon bg-success-subtle text-success">
                                    <i class="bi bi-person-check-fill"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Visitors on Campus -->
                    <div class="col-12 col-md-4">
                        <div class="card page-card h-100 border-start border-4 border-primary">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted small fw-semibold mb-1">Visitors on Campus</p>
                                    <p id="stat-visitors" class="display-6 fw-bold text-dark mb-0">{{ $visitorsOnCampus ?? 0 }}</p>
                                </div>
                                <span class="stat-icon bg-primary-subtle text-primary">
                                    <i class="bi bi-people-fill"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Entries Denied -->
                    <div class="col-12 col-md-4">
                        <div class="card page-card h-100 border-start border-4 border-danger">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted small fw-semibold mb-1">Entries Denied</p>
                                    <p id="stat-denied" class="display-6 fw-bold text-dark mb-0">{{ $entriesDenied ?? 0 }}</p>
                                </div>
                                <span class="stat-icon bg-danger-subtle text-danger">
                                    <i class="bi bi-shield-exclamation"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Entry Feed -->
                <div class="card page-card mb-4">
                    <div class="card-header bg-white border-bottom px-3 px-md-4 py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                        <div>
                            <h2 class="h5 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                <span class="live-dot"></span> Live Entry Feed
                            </h2>
                            <p class="text-muted small mb-0">Last scanned students (real-time, newest first)</p>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div id="feed-meta" class="d-flex flex-wrap align-items-center gap-2">
                                @if($feedResetAt)
                                    <span class="badge text-bg-warning">
                                        <i class="bi bi-funnel me-1"></i>New scans since {{ \Carbon\Carbon::parse($feedResetAt)->format('g:i A') }}
                                    </span>
                                    <form action="{{ route('guard.feed.reset') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="action" value="restore">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Show all
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <form action="{{ route('guard.feed.reset') }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Clear the current live entry feed? New scans will still appear.');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-eraser me-1"></i>Reset Feed
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive feed-scroll">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3">Photo</th>
                                    <th class="py-3">Name</th>
                                    <th class="py-3">Student #</th>
                                    <th class="py-3">Time In</th>
                                    <th class="py-3">Time Out</th>
                                    <th class="py-3">Status</th>
                                    <th class="py-3 pe-4">Enrollment</th>
                                </tr>
                            </thead>
                            <tbody id="live-feed">
                                @forelse($entryLogs as $log)
                                    @php
                                        $isUnknown = is_null($log->student_id) || $log->status === 'unknown';
                                        $isExpired = !$isUnknown && $log->status === 'expired';
                                        $isDenied = !$isUnknown && !$isExpired && $log->status === 'denied';
                                        $isBadScan = $isUnknown || $isDenied || $isExpired;
                                    @endphp

                                    <tr class="{{ $isBadScan ? 'table-danger' : '' }}">
                                        <!-- Photo / Icon -->
                                        <td class="ps-4">
                                            @if(!$isUnknown && $log->student && $log->student->photo)
                                                <span class="photo-avatar">
                                                    <img src="{{ asset('storage/' . ltrim($log->student->photo, '/')) }}" alt="Photo">
                                                </span>
                                            @elseif(!$isUnknown && $log->student)
                                                <span class="photo-avatar bg-success text-white">
                                                    {{ strtoupper(substr($log->student->first_name, 0, 1)) }}
                                                </span>
                                            @else
                                                <span class="photo-avatar bg-danger text-white">
                                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Name -->
                                        <td class="fw-semibold {{ $isBadScan ? 'text-danger' : 'text-dark' }}">
                                            @if($isUnknown)
                                                Unregistered Tag
                                            @elseif($isDenied)
                                                {{ $log->student->first_name }} {{ $log->student->last_name }}
                                                <span class="badge text-bg-danger ms-1">{{ $log->reason === 'enrollment_not_confirmed' ? 'Not Confirmed' : 'Not Enrolled' }}</span>
                                            @elseif($isExpired)
                                                {{ $log->student->first_name }} {{ $log->student->last_name }}
                                                <span class="badge text-bg-warning text-dark ms-1">Expired Card</span>
                                            @else
                                                {{ $log->student->first_name }} {{ $log->student->last_name }}
                                            @endif
                                        </td>

                                        <!-- Student # or Card UID -->
                                        <td class="{{ $isBadScan ? 'text-danger rfid-code fw-semibold' : 'text-muted' }}">
                                            @if($isUnknown)
                                                UID: {{ $log->card_uid ?? 'N/A' }}
                                            @else
                                                {{ $log->student->student_number }}
                                            @endif
                                        </td>

                                        <!-- Time In -->
                                        <td class="text-dark">
                                            {{ $log->time_in ? \Carbon\Carbon::parse($log->time_in)->timezone(config('app.timezone'))->format('g:i A') : '-' }}
                                        </td>

                                        <!-- Time Out -->
                                        <td class="text-muted">
                                            {{ $log->time_out ? \Carbon\Carbon::parse($log->time_out)->timezone(config('app.timezone'))->format('g:i A') : '-' }}
                                        </td>

                                        <!-- Status -->
                                        <td>
                                            @if($isUnknown)
                                                <span class="badge rounded-pill text-bg-danger">Unknown</span>
                                            @elseif($isDenied)
                                                <span class="badge rounded-pill text-bg-danger">Denied</span>
                                            @elseif($isExpired)
                                                <span class="badge rounded-pill text-bg-danger">Expired</span>
                                            @elseif($log->time_out)
                                                <span class="badge rounded-pill text-bg-warning text-dark">Out</span>
                                            @else
                                                <span class="badge rounded-pill text-bg-success">In</span>
                                            @endif
                                        </td>

                                        <!-- Enrollment -->
                                        <td class="pe-4">
                                            @if($isUnknown)
                                                <span class="badge text-bg-danger">Not in Database</span>
                                            @elseif($isExpired)
                                                <span class="badge text-bg-warning text-dark">{{ ucfirst($log->student->card_type) }} Expired</span>
                                            @else
                                                <span class="badge {{ $log->student->status === 'Enrolled' ? 'text-bg-primary' : 'text-bg-secondary' }}">
                                                    {{ $log->student->status }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-5 text-center text-muted">
                                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                            No entry logs yet. Waiting for the first scan...
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Register Visitor & Alerts -->
                <div class="row g-3 g-lg-4">
                    <!-- Register Visitor -->
                    <div class="col-12 col-lg-6">
                        <div class="card page-card h-100">
                            <div class="card-body p-4">
                                <h2 class="h5 fw-bold text-dark mb-3">
                                    <i class="bi bi-person-vcard text-success me-2"></i>Register Visitor
                                </h2>

                                <form id="visitorForm" action="{{ route('guard.visitor.store') }}" method="POST" class="row g-3">
                                    @csrf

                                    <!-- Full Name -->
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-secondary" for="visitor_name">Full Name</label>
                                        <input type="text" name="visitor_name" id="visitor_name" required
                                               class="form-control" placeholder="Visitor name">
                                    </div>

                                    <!-- ID Type & Number -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-secondary" for="id_type">ID Type</label>
                                        <select name="id_type" id="id_type" required class="form-select">
                                            <option value="">Select ID</option>
                                            <option value="drivers_license">Driver's License</option>
                                            <option value="passport">Passport</option>
                                            <option value="national_id">National ID</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-secondary" for="id_number">ID Number</label>
                                        <input type="text" name="id_number" id="id_number" required
                                               class="form-control" placeholder="ID #">
                                    </div>

                                    <!-- Visiting & Pass Duration -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-secondary" for="visiting_department">Visiting</label>
                                        <select name="visiting_department" id="visiting_department" required class="form-select">
                                            <option value="">Department</option>
                                            <option value="registrar">Registrar</option>
                                            <option value="admin">Administration</option>
                                            <option value="faculty">Faculty</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-secondary" for="pass_duration">Pass Valid For</label>
                                        <select name="pass_duration" id="pass_duration" required class="form-select">
                                            <option value="1">1 hour</option>
                                            <option value="2" selected>2 hours</option>
                                            <option value="4">4 hours</option>
                                            <option value="8">8 hours</option>
                                        </select>
                                    </div>

                                    <!-- Available RFID Tag -->
                                    <div class="col-12 rfid-picker">
                                        <label class="form-label small fw-semibold text-secondary" for="visitor_rfid_tag">Assign RFID Tag</label>
                                        <div class="input-group">
                                            <input type="text" name="rfid_tag" id="visitor_rfid_tag" placeholder="Click to choose or scan an RFID tag" class="form-control" autocomplete="off" required>
                                            <button type="button" class="btn btn-outline-secondary" id="visitor_rfid_toggle" aria-label="Show available RFID tags">
                                                <i class="bi bi-chevron-down"></i>
                                            </button>
                                        </div>
                                        <div class="rfid-dropdown d-none" id="visitor_rfid_dropdown">
                                            <div class="p-2 border-bottom bg-light">
                                                <button type="button" class="btn btn-ieti btn-sm w-100 fw-semibold" id="scan_visitor_rfid_btn">
                                                    <i class="bi bi-upc-scan me-1"></i> Scan an RFID
                                                </button>
                                                <input type="text" id="scan_visitor_rfid_input" class="form-control form-control-sm mt-2 d-none" placeholder="Scan the card or type the tag, then press Enter" autocomplete="off">
                                                <div class="small text-muted mt-1 d-none" id="scan_visitor_rfid_hint">Waiting for an RFID scan...</div>
                                            </div>
                                            <div class="rfid-list" id="visitor_rfid_list"></div>
                                        </div>
                                    </div>

                                    <!-- Buttons -->
                                    <div class="col-12 d-flex gap-2 pt-1">
                                        <button type="submit" class="btn btn-ieti fw-semibold flex-fill">
                                            <i class="bi bi-check2-circle me-1"></i>Issue Visitor Pass
                                        </button>
                                        <button type="reset" class="btn btn-outline-secondary fw-semibold flex-fill">
                                            Clear
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Alerts & Today's Summary -->
                    <div class="col-12 col-lg-6">
                        <div id="alerts-list" class="d-flex flex-column gap-3 h-100">
                            <!-- Alerts -->
                            <div class="card page-card border-start border-4 border-danger">
                                <div class="card-body p-4">
                                    <h2 class="h5 fw-bold text-dark mb-3">
                                        <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Alerts
                                    </h2>
                                    <div class="d-flex flex-column gap-2">
                                        @if($deniedEntries && count($deniedEntries) > 0)
                                            @foreach($deniedEntries as $denied)
                                                <div class="alert alert-danger py-2 px-3 mb-0 d-flex align-items-start gap-2">
                                                    <i class="bi bi-x-circle-fill mt-1"></i>
                                                    <div>
                                                        <p class="fw-semibold mb-0 small">Invalid RFID Detected</p>
                                                        <p class="mb-0 small">{{ $denied['time'] ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <p class="text-muted small mb-0">
                                                <i class="bi bi-check-circle-fill text-success me-1"></i>
                                                All systems normal. No alerts.
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="card page-card">
                                <div class="card-body p-4">
                                    <h2 class="h5 fw-bold text-dark mb-3">
                                        <i class="bi bi-bar-chart-fill text-success me-2"></i>Today's Summary
                                    </h2>
                                    <div id="summary-box" class="d-flex flex-column gap-2">
                                        <div class="d-flex justify-content-between align-items-center bg-light rounded-3 px-3 py-2">
                                            <span class="text-muted small">Total Entries</span>
                                            <span class="fw-bold fs-5 text-success">{{ $totalEntries ?? 0 }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center bg-light rounded-3 px-3 py-2">
                                            <span class="text-muted small">Currently In</span>
                                            <span class="fw-bold fs-5 text-success">{{ $currentlyIn ?? 0 }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center bg-light rounded-3 px-3 py-2">
                                            <span class="text-muted small">Currently Out</span>
                                            <span class="fw-bold fs-5 text-danger">{{ $currentlyOut ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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
        setInterval(updateDateTime, 1000);
    </script>

    <!-- Visitor RFID Picker -->
    <script>
        const visitorRfidInput = document.getElementById('visitor_rfid_tag');
        const visitorRfidToggle = document.getElementById('visitor_rfid_toggle');
        const visitorRfidDropdown = document.getElementById('visitor_rfid_dropdown');
        const visitorRfidList = document.getElementById('visitor_rfid_list');
        const scanVisitorRfidBtn = document.getElementById('scan_visitor_rfid_btn');
        const scanVisitorRfidInput = document.getElementById('scan_visitor_rfid_input');
        const scanVisitorRfidHint = document.getElementById('scan_visitor_rfid_hint');

        const visitorAvailableRfids = @json($availableRfids ?? []);
        let visitorSelectedRfid = '';

        function renderVisitorRfidList() {
            visitorRfidList.innerHTML = '';

            if (visitorAvailableRfids.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'rfid-empty';
                empty.textContent = 'No available RFID tags. Scan one to add it here.';
                visitorRfidList.appendChild(empty);
                return;
            }

            visitorAvailableRfids.forEach(function (tag) {
                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'rfid-option' + (tag === visitorSelectedRfid ? ' selected' : '');

                const label = document.createElement('span');
                label.textContent = tag;
                option.appendChild(label);

                if (tag === visitorSelectedRfid) {
                    const check = document.createElement('i');
                    check.className = 'bi bi-check2-circle';
                    option.appendChild(check);
                }

                option.addEventListener('click', function () {
                    selectVisitorRfid(tag);
                });

                visitorRfidList.appendChild(option);
            });
        }

        function selectVisitorRfid(tag) {
            visitorSelectedRfid = tag;
            visitorRfidInput.value = tag;
            renderVisitorRfidList();
            closeVisitorRfidDropdown();
        }

        function openVisitorRfidDropdown() {
            visitorRfidDropdown.classList.remove('d-none');
            renderVisitorRfidList();
        }

        function closeVisitorRfidDropdown() {
            visitorRfidDropdown.classList.add('d-none');
            scanVisitorRfidInput.classList.add('d-none');
            scanVisitorRfidHint.classList.add('d-none');
        }

        visitorRfidInput.addEventListener('click', openVisitorRfidDropdown);
        visitorRfidToggle.addEventListener('click', function (event) {
            event.preventDefault();

            if (visitorRfidDropdown.classList.contains('d-none')) {
                openVisitorRfidDropdown();
            } else {
                closeVisitorRfidDropdown();
            }
        });

        scanVisitorRfidBtn.addEventListener('click', function () {
            scanVisitorRfidInput.value = '';
            scanVisitorRfidInput.classList.remove('d-none');
            scanVisitorRfidHint.classList.remove('d-none');
            scanVisitorRfidInput.focus();
        });

        scanVisitorRfidInput.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();

            const tag = scanVisitorRfidInput.value.replace(/\s+/g, '').toUpperCase();

            if (!tag) {
                return;
            }

            if (!visitorAvailableRfids.includes(tag)) {
                visitorAvailableRfids.unshift(tag);
            }

            visitorSelectedRfid = tag;
            visitorRfidInput.value = tag;
            scanVisitorRfidInput.value = '';
            scanVisitorRfidInput.classList.add('d-none');
            scanVisitorRfidHint.classList.add('d-none');
            visitorRfidList.scrollTop = 0;
            renderVisitorRfidList();
        });

        document.addEventListener('click', function (event) {
            if (!visitorRfidDropdown.classList.contains('d-none') && !event.target.closest('.rfid-picker')) {
                closeVisitorRfidDropdown();
            }
        });

        document.getElementById('visitorForm').addEventListener('reset', function () {
            visitorSelectedRfid = '';
            closeVisitorRfidDropdown();
            renderVisitorRfidList();
        });
    </script>

    <script>
        const refreshIds = [
            'stat-verified', 'stat-visitors', 'stat-denied',
            'feed-meta', 'live-feed', 'alerts-list', 'summary-box'
        ];

        async function refreshDashboard() {
            if (document.hidden) return;   // skip while the tab isn't visible

            try {
                const res = await fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'no-store'
                });

                // Session expired: the server redirected to the login page
                if (res.redirected) {
                    window.location.reload();
                    return;
                }
                if (!res.ok) return;

                const html = await res.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');

                refreshIds.forEach(id => {
                    const fresh = doc.getElementById(id);
                    const current = document.getElementById(id);
                    if (fresh && current) {
                        current.innerHTML = fresh.innerHTML;
                    }
                });
            } catch (e) {
                // network hiccup: try again on the next tick
            }
        }

        setInterval(refreshDashboard, 3000);   // every 3 seconds
    </script>

</body>
</html>
