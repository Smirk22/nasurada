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

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>
                            @foreach($errors->all() as $error)
                                <div class="small">{{ $error }}</div>
                            @endforeach
                        </div>
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
                    <div class="card-header bg-white border-bottom px-3 px-md-4 py-3">
                        <h2 class="h5 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                            <span class="live-dot"></span> Live Entry Feed
                        </h2>
                        <p class="text-muted small mb-0">Last scanned students (real-time, newest first)</p>
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
                                        $isVisitor = !is_null($log->visitor_id) && $log->visitor;
                                        $isUnknown = (is_null($log->student_id) && is_null($log->visitor_id)) || $log->status === 'unknown';
                                        $isExpired = !$isUnknown && $log->status === 'expired';
                                        $isDenied = !$isUnknown && !$isExpired && $log->status === 'denied';
                                        $isBadScan = $isUnknown || $isDenied || $isExpired;
                                        $personName = $log->student
                                            ? trim($log->student->first_name.' '.$log->student->last_name)
                                            : ($log->visitor?->visitor_name);
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
                                            @elseif($isVisitor)
                                                <span class="photo-avatar bg-primary text-white">
                                                    {{ strtoupper(substr($log->visitor->visitor_name, 0, 1)) }}
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
                                                {{ $personName }}
                                                <span class="badge text-bg-danger ms-1">{{ $log->reason === 'enrollment_not_confirmed' ? 'Not Confirmed' : 'Not Enrolled' }}</span>
                                            @elseif($isExpired)
                                                {{ $personName }}
                                                <span class="badge text-bg-warning text-dark ms-1">{{ $isVisitor ? 'Expired Visitor ID' : 'Expired Card' }}</span>
                                            @else
                                                {{ $personName }}
                                                @if($isVisitor)
                                                    <span class="badge text-bg-info ms-1">Visitor</span>
                                                @endif
                                            @endif
                                        </td>

                                        <!-- Student # or Card UID -->
                                        <td class="{{ $isBadScan ? 'text-danger rfid-code fw-semibold' : 'text-muted' }}">
                                            @if($isUnknown)
                                                UID: {{ $log->card_uid ?? 'N/A' }}
                                            @elseif($isVisitor)
                                                Visitor ID: {{ $log->visitor->id_number }}
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
                                            @elseif($isVisitor)
                                                <span class="badge {{ $isExpired ? 'text-bg-warning text-dark' : 'text-bg-info' }}">
                                                    {{ $isExpired ? 'Visitor Expired' : 'Visitor' }}
                                                </span>
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

                                    <!-- Visiting -->
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-secondary" for="visiting_department">Visiting</label>
                                        <select name="visiting_department" id="visiting_department" required class="form-select">
                                            <option value="">Department</option>
                                            <option value="registrar">Registrar</option>
                                            <option value="admin">Administration</option>
                                            <option value="faculty">Faculty</option>
                                        </select>
                                    </div>

                                    <!-- Available RFID Tag -->
                                    <div class="col-12 rfid-picker">
                                        <label class="form-label small fw-semibold text-secondary" for="visitor_rfid_tag">Assign RFID Tag</label>
                                        <div class="input-group">
                                            <input type="text" name="rfid_tag" id="visitor_rfid_tag" placeholder="Scan an RFID tag or type it here" class="form-control" autocomplete="off" required>
                                            <button type="button" class="btn btn-outline-secondary" id="visitor_rfid_toggle" aria-label="Show RFID scan options">
                                                <i class="bi bi-chevron-down"></i>
                                            </button>
                                        </div>
                                        <div class="small text-danger mt-1 d-none" id="visitor_rfid_warning"></div>
                                        <div class="rfid-dropdown d-none" id="visitor_rfid_dropdown">
                                            <div class="p-2">
                                                <button type="button" class="btn btn-ieti btn-sm w-100 fw-semibold" id="scan_visitor_rfid_btn">
                                                    <i class="bi bi-upc-scan me-1"></i> Scan an RFID
                                                </button>
                                                <input type="text" id="scan_visitor_rfid_input" class="form-control form-control-sm mt-2 d-none" placeholder="Scan the card or type the tag, then press Enter" autocomplete="off">
                                                <div class="small text-muted mt-1 d-none" id="scan_visitor_rfid_hint">Waiting for an RFID scan...</div>
                                            </div>
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
                                        <div class="d-flex justify-content-between align-items-center bg-light rounded-3 px-3 py-2">
                                            <span class="text-muted small">Sent Messages</span>
                                            <div class="d-flex align-items-center gap-2">
                                                <span id="sent-messages-count" class="fw-bold fs-5 text-success">{{ $sentMessagesCount ?? 0 }}</span>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-success dropdown-toggle" type="button"
                                                            id="sent-messages-toggle" data-bs-toggle="dropdown"
                                                            data-bs-auto-close="outside" aria-expanded="false">
                                                        <i class="bi bi-envelope-paper me-1"></i>View
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end shadow" id="sent-messages-menu"
                                                         style="min-width: 340px; max-width: min(420px, 90vw); max-height: 320px; overflow-y: auto;"
                                                         aria-labelledby="sent-messages-toggle">
                                                        <h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                                            <span>Messages Sent Today</span>
                                                            <span class="badge text-bg-success">{{ $sentMessagesCount ?? 0 }}</span>
                                                        </h6>
                                                        @forelse($sentMessages as $sent)
                                                            <div class="dropdown-item-text small border-top">
                                                                <div class="d-flex justify-content-between gap-2">
                                                                    <span class="fw-semibold text-dark">
                                                                        {{ $sent->student ? trim($sent->student->first_name.' '.$sent->student->last_name) : 'Unknown student' }}
                                                                    </span>
                                                                    <span class="text-muted text-nowrap">{{ $sent->sent_at?->format('g:i A') }}</span>
                                                                </div>
                                                                <div class="text-muted">{{ $sent->recipient }}</div>
                                                                <div class="text-muted" style="white-space: normal;">{{ $sent->message }}</div>
                                                            </div>
                                                        @empty
                                                            <div class="dropdown-item-text small text-muted text-center py-3">
                                                                <i class="bi bi-envelope-x d-block fs-5 mb-1"></i>
                                                                No messages sent today.
                                                            </div>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>
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
        const visitorForm = document.getElementById('visitorForm');
        const visitorRfidInput = document.getElementById('visitor_rfid_tag');
        const visitorRfidToggle = document.getElementById('visitor_rfid_toggle');
        const visitorRfidDropdown = document.getElementById('visitor_rfid_dropdown');
        const scanVisitorRfidBtn = document.getElementById('scan_visitor_rfid_btn');
        const scanVisitorRfidInput = document.getElementById('scan_visitor_rfid_input');
        const scanVisitorRfidHint = document.getElementById('scan_visitor_rfid_hint');
        const visitorRfidWarning = document.getElementById('visitor_rfid_warning');

        const visitorRfidScanModeUrl = '{{ route('guard.rfid.scan-mode') }}';
        const visitorRfidScanModeCancelUrl = '{{ route('guard.rfid.scan-mode.cancel') }}';
        const visitorRfidScanLatestUrl = '{{ route('guard.rfid.scan-latest') }}';
        const visitorRfidCsrfToken = visitorForm.querySelector('input[name="_token"]').value;
        const assignedRfidOwners = @json($assignedRfidOwners ?? []);

        let visitorScanCommitTimer = null;
        let visitorRfidPollTimer = null;
        let visitorRfidPolling = false;
        let visitorRegistrationArmed = false;
        let visitorRfidScanStarting = false;
        let visitorRfidScanWanted = false;
        let lastVisitorScanNonce = null;

        function normalizeRfid(value) {
            return (value || '').replace(/\s+/g, '').toUpperCase();
        }

        function clearVisitorRfidWarning() {
            visitorRfidWarning.textContent = '';
            visitorRfidWarning.classList.add('d-none');
        }

        function showVisitorRfidWarning(message) {
            visitorRfidWarning.textContent = message;
            visitorRfidWarning.classList.remove('d-none');
        }

        function openVisitorRfidDropdown() {
            visitorRfidDropdown.classList.remove('d-none');
            beginVisitorRegistrationScan();
        }

        function closeVisitorRfidDropdown() {
            visitorRfidDropdown.classList.add('d-none');
            scanVisitorRfidInput.classList.add('d-none');
            scanVisitorRfidHint.classList.add('d-none');
            cancelVisitorRegistrationScan();
        }

        function commitVisitorRfid(value, owner) {
            cancelVisitorRegistrationScan();

            const tag = normalizeRfid(value);

            if (!tag) {
                return false;
            }

            const assignedOwner = owner || assignedRfidOwners[tag];

            if (assignedOwner) {
                visitorRfidInput.value = '';
                scanVisitorRfidInput.value = '';
                scanVisitorRfidInput.classList.add('d-none');
                scanVisitorRfidHint.classList.add('d-none');
                showVisitorRfidWarning(tag + ' is already assigned to ' + assignedOwner + '. Scan another tag.');
                window.alert('This rfid is already registered');
                openVisitorRfidDropdown();

                return false;
            }

            clearVisitorRfidWarning();

            visitorRfidInput.value = tag;
            scanVisitorRfidInput.value = '';
            scanVisitorRfidInput.classList.add('d-none');
            scanVisitorRfidHint.classList.add('d-none');
            closeVisitorRfidDropdown();

            return true;
        }

        function scheduleVisitorScanCommit(input) {
            clearTimeout(visitorScanCommitTimer);
            visitorScanCommitTimer = setTimeout(function () {
                if (normalizeRfid(input.value)) {
                    commitVisitorRfid(input.value);
                }
            }, 400);
        }

        function parseJsonResponse(response) {
            return response.ok ? response.json() : null;
        }

        function fetchLatestVisitorRfid() {
            return fetch(visitorRfidScanLatestUrl, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            }).then(parseJsonResponse).catch(function () {
                return null;
            });
        }

        function beginVisitorRegistrationScan() {
            visitorRfidScanWanted = true;

            if (visitorRegistrationArmed || visitorRfidPolling || visitorRfidScanStarting) {
                return;
            }

            visitorRfidScanStarting = true;

            fetch(visitorRfidScanModeUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': visitorRfidCsrfToken
                }
            }).then(function (response) {
                visitorRfidScanStarting = false;

                if (!visitorRfidScanWanted) {
                    if (response.ok) {
                        visitorRegistrationArmed = true;
                        cancelVisitorRegistrationScan();
                    }

                    return;
                }

                visitorRegistrationArmed = response.ok;
                startVisitorRfidPolling();
            }).catch(function () {
                visitorRfidScanStarting = false;

                if (visitorRfidScanWanted) {
                    startVisitorRfidPolling();
                }
            });
        }

        function cancelVisitorRegistrationScan() {
            visitorRfidScanWanted = false;
            stopVisitorRfidPolling();

            if (!visitorRegistrationArmed) {
                return;
            }

            visitorRegistrationArmed = false;

            fetch(visitorRfidScanModeCancelUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': visitorRfidCsrfToken
                }
            }).catch(function () {
                // ignore network errors
            });
        }

        function startVisitorRfidPolling() {
            stopVisitorRfidPolling();

            visitorRfidPolling = true;
            lastVisitorScanNonce = null;

            visitorRfidPollTimer = setInterval(function () {
                if (!visitorRfidPolling) {
                    return;
                }

                fetchLatestVisitorRfid().then(function (latest) {
                    if (!visitorRfidPolling || !latest || !latest.uid || latest.nonce === lastVisitorScanNonce) {
                        return;
                    }

                    lastVisitorScanNonce = latest.nonce;
                    visitorRegistrationArmed = false;
                    stopVisitorRfidPolling();
                    commitVisitorRfid(latest.uid, latest.owner);
                });
            }, 1000);
        }

        function stopVisitorRfidPolling() {
            visitorRfidPolling = false;

            if (visitorRfidPollTimer) {
                clearInterval(visitorRfidPollTimer);
                visitorRfidPollTimer = null;
            }
        }

        visitorRfidInput.addEventListener('click', openVisitorRfidDropdown);
        visitorRfidInput.addEventListener('input', function () {
            clearVisitorRfidWarning();
            scheduleVisitorScanCommit(visitorRfidInput);
        });
        visitorRfidInput.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();
            commitVisitorRfid(visitorRfidInput.value);
        });

        visitorRfidToggle.addEventListener('click', function (event) {
            event.preventDefault();

            if (visitorRfidDropdown.classList.contains('d-none')) {
                openVisitorRfidDropdown();
            } else {
                closeVisitorRfidDropdown();
            }
        });

        scanVisitorRfidBtn.addEventListener('click', function () {
            clearVisitorRfidWarning();
            scanVisitorRfidInput.value = '';
            scanVisitorRfidInput.classList.remove('d-none');
            scanVisitorRfidHint.classList.remove('d-none');
            scanVisitorRfidInput.focus();
            beginVisitorRegistrationScan();
        });

        scanVisitorRfidInput.addEventListener('input', function () {
            clearVisitorRfidWarning();
            cancelVisitorRegistrationScan();
            scheduleVisitorScanCommit(scanVisitorRfidInput);
        });

        scanVisitorRfidInput.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();
            cancelVisitorRegistrationScan();
            commitVisitorRfid(scanVisitorRfidInput.value);
        });

        document.addEventListener('click', function (event) {
            if (!visitorRfidDropdown.classList.contains('d-none') && !event.target.closest('.rfid-picker')) {
                closeVisitorRfidDropdown();
            }
        });

        visitorForm.addEventListener('reset', function () {
            clearTimeout(visitorScanCommitTimer);
            clearVisitorRfidWarning();
            closeVisitorRfidDropdown();
        });
    </script>

    <script>
        const refreshIds = [
            'stat-verified', 'stat-visitors', 'stat-denied',
            'live-feed', 'alerts-list', 'summary-box'
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

                // Don't close the sent-messages dropdown while the guard is reading it.
                const sentMessagesMenu = document.getElementById('sent-messages-menu');
                const sentMessagesOpen = sentMessagesMenu && sentMessagesMenu.classList.contains('show');

                refreshIds.forEach(id => {
                    if (id === 'summary-box' && sentMessagesOpen) return;

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
