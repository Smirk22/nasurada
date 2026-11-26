<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temporary Pass Logs - IETI SEVS</title>

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

        .page-card {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 .125rem .5rem rgba(0, 0, 0, .06);
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

        .avatar-initials {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .8rem;
        }

        .table thead th {
            white-space: nowrap;
            font-size: .72rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #6c757d;
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
                        <i class="bi bi-person-badge-fill text-primary me-2"></i>Temporary Pass Logs
                    </h1>
                    <p class="text-muted small mb-0">Visitor and temporary-pass scans for {{ today()->format('F j, Y') }}.</p>
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

                <div class="card page-card">
                    <div class="card-header bg-white border-bottom px-3 px-md-4 py-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                        <div>
                            <h2 class="h5 fw-bold text-dark mb-1">
                                <i class="bi bi-clock-history text-success me-2"></i>Today's Temporary Pass Logs
                            </h2>
                            <p class="text-muted small mb-0">Scans recorded from visitor and temporary-pass RFID tags.</p>
                        </div>
                        <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                            {{ $visitorLogs->count() }} {{ Str::plural('record', $visitorLogs->count()) }}
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3">Visitor</th>
                                    <th class="py-3">ID / Purpose</th>
                                    <th class="py-3">Time In</th>
                                    <th class="py-3">Time Out</th>
                                    <th class="py-3">Action</th>
                                    <th class="py-3 pe-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($visitorLogs as $log)
                                    @php
                                        $visitor = $log->visitor;
                                        $isUnknown = is_null($visitor) || $log->status === 'unknown';
                                        $isExpired = ! $isUnknown && $log->status === 'expired';
                                        $isDenied = ! $isUnknown && ! $isExpired && $log->status === 'denied';
                                        $isOut = ! $isUnknown && ! $isDenied && ! $isExpired && $log->time_out !== null;
                                        $visitorName = $visitor?->visitor_name ?? 'Unregistered Tag';
                                        $idType = $visitor ? match($visitor->id_type) {
                                            'drivers_license' => "Driver's License",
                                            'passport' => 'Passport',
                                            'national_id' => 'National ID',
                                            'registration_card' => 'Registration Card',
                                            default => ucfirst(str_replace('_', ' ', (string) $visitor->id_type)),
                                        } : 'Unknown';
                                        $visiting = $visitor ? ($visitor->visiting_department === 'loss_of_rfid_id'
                                            ? 'Loss of RFID/ID'
                                            : ucfirst(str_replace('_', ' ', (string) $visitor->visiting_department))) : null;
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="avatar-initials {{ $isUnknown ? 'bg-danger-subtle text-danger' : ($isExpired ? 'bg-warning-subtle text-warning-emphasis' : 'bg-primary-subtle text-primary') }}">
                                                    {{ $isUnknown ? '?' : strtoupper(substr($visitorName, 0, 1)) }}
                                                </span>
                                                <div>
                                                    <div class="fw-semibold {{ $isUnknown ? 'text-danger' : 'text-dark' }}">{{ $visitorName }}</div>
                                                    <span class="badge text-bg-info">Visitor</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-muted small">
                                            @if($isUnknown)
                                                {{ $log->card_uid ? 'UID: '.$log->card_uid : 'Unknown UID' }}
                                            @else
                                                <div>{{ $idType }}: {{ $visitor->id_number }}</div>
                                                <div class="text-muted">Visiting: {{ $visiting }}</div>
                                            @endif
                                        </td>
                                        <td class="fw-medium text-dark">{{ $log->time_in ? \Carbon\Carbon::parse($log->time_in)->format('h:i:s A') : '-' }}</td>
                                        <td class="text-muted">{{ $log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('h:i:s A') : '-' }}</td>
                                        <td>
                                            @if($isUnknown)
                                                <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle"><i class="bi bi-exclamation-octagon-fill me-1"></i>UNKNOWN</span>
                                            @elseif($isDenied)
                                                <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle"><i class="bi bi-x-circle-fill me-1"></i>DENIED</span>
                                            @elseif($isExpired)
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="bi bi-clock-history me-1"></i>EXPIRED</span>
                                            @elseif($isOut)
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle"><i class="bi bi-box-arrow-right me-1"></i>OUT</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle"><i class="bi bi-box-arrow-in-right me-1"></i>IN</span>
                                            @endif
                                        </td>
                                        <td class="pe-4">
                                            @if($isExpired)
                                                <span class="badge text-bg-warning text-dark">Expired Visitor ID</span>
                                            @elseif($isDenied)
                                                <span class="badge text-bg-danger">Denied</span>
                                            @elseif($isUnknown)
                                                <span class="badge text-bg-danger">Unknown</span>
                                            @elseif($isOut)
                                                <span class="badge text-bg-secondary">Exited</span>
                                            @else
                                                <span class="badge text-bg-success">On Campus</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-5 text-center text-muted">
                                            <i class="bi bi-person-badge fs-3 d-block mb-2"></i>
                                            No temporary pass logs yet for today.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
