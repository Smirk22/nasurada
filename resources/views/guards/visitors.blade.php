<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor's List - IETI SEVS</title>

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

        .visitor-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .8rem;
            background-color: #0d6efd;
            color: #fff;
        }

        .table thead th {
            white-space: nowrap;
            font-size: .72rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #6c757d;
        }

        .rfid-code {
            font-family: var(--bs-font-monospace);
            font-size: .8rem;
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
                        <i class="bi bi-person-vcard text-success me-2"></i>Visitor's List
                    </h1>
                    <p class="text-muted small mb-0">Registered visitors and the RFID tags assigned to them.</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('guard.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                    </a>
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

                <div class="row g-3 g-lg-4 mb-4">
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card page-card h-100 border-start border-4 border-primary">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted small fw-semibold mb-1">Registered Visitors</p>
                                    <p class="display-6 fw-bold text-dark mb-0">{{ $visitors->count() }}</p>
                                </div>
                                <span class="stat-icon bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center rounded-3"
                                      style="width: 52px; height: 52px; font-size: 1.4rem;">
                                    <i class="bi bi-people-fill"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card page-card h-100 border-start border-4 border-secondary">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted small fw-semibold mb-1">Removed Visitors</p>
                                    <p class="display-6 fw-bold text-dark mb-0">{{ $removedCount }}</p>
                                </div>
                                <span class="stat-icon bg-secondary-subtle text-secondary d-inline-flex align-items-center justify-content-center rounded-3"
                                      style="width: 52px; height: 52px; font-size: 1.4rem;">
                                    <i class="bi bi-person-dash-fill"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card page-card">
                    <div class="card-header bg-white border-bottom px-3 px-md-4 py-3">
                        <h2 class="h5 fw-bold text-dark mb-1">
                            <i class="bi bi-list-check text-success me-2"></i>Active Visitor Passes
                        </h2>
                        <p class="text-muted small mb-0">
                            Removing a visitor frees their RFID tag. Until it is assigned to a new visitor, scanning it shows as an unregistered tag.
                        </p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3">Visitor</th>
                                    <th class="py-3">ID Type</th>
                                    <th class="py-3">ID Number</th>
                                    <th class="py-3">Visiting</th>
                                    <th class="py-3">RFID Tag</th>
                                    <th class="py-3">Registered</th>
                                    <th class="py-3 pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($visitors as $visitor)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="visitor-avatar">
                                                    {{ strtoupper(substr($visitor->visitor_name, 0, 1)) }}
                                                </span>
                                                <div>
                                                    <div class="fw-semibold text-dark">{{ $visitor->visitor_name }}</div>
                                                    <span class="badge text-bg-info">Visitor</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-muted">
                                            {{ match($visitor->id_type) {
                                                'drivers_license' => "Driver's License",
                                                'passport' => 'Passport',
                                                'national_id' => 'National ID',
                                                default => ucfirst(str_replace('_', ' ', $visitor->id_type)),
                                            } }}
                                        </td>
                                        <td class="text-muted">{{ $visitor->id_number }}</td>
                                        <td class="text-muted">{{ ucfirst($visitor->visiting_department) }}</td>
                                        <td>
                                            <span class="badge text-bg-light border rfid-code">{{ $visitor->rfid_tag }}</span>
                                        </td>
                                        <td class="text-muted small">
                                            {{ $visitor->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                        </td>
                                        <td class="pe-4 text-end">
                                            <form action="{{ route('guard.visitors.destroy', $visitor) }}" method="POST"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Remove this visitor from the Visitor\'s List? Their RFID tag will be released and treated as unregistered until it is assigned again.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-person-dash me-1"></i>Remove
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-5 text-center text-muted">
                                            <i class="bi bi-person-plus fs-3 d-block mb-2"></i>
                                            No registered visitors yet. Register a visitor from the Guard Dashboard.
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
