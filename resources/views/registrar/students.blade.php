<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records - IETI</title>

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

        .rfid-code {
            font-family: var(--bs-font-monospace);
            font-size: .78rem;
            letter-spacing: .02em;
        }

        .page-card {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 .125rem .5rem rgba(0, 0, 0, .06);
        }

        .modal-content {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, .18);
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

    </style>
</head>
<body class="bg-gray-100 font-sans min-h-screen">

    <div class="flex min-h-screen">
        {{-- 1. Sidebar Navigation --}}
        @include('partials.sidebar')

        {{-- 2. Main Content Area --}}
        <main class="flex-1 p-3 p-md-4 p-lg-5">

            @if(session('success'))
                <div class="alert alert-success d-flex align-items-center gap-2 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger d-flex align-items-center gap-2 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <!-- Action Header -->
            <div class="card page-card mb-4">
                <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 fw-bold text-dark mb-1">
                            <i class="bi bi-people-fill text-success me-1"></i> Student Records
                        </h1>
                        <p class="text-muted small mb-0">Manage enrollments, edit information, assign RFID tags, or trigger semester reset.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-ieti fw-semibold" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="bi bi-person-plus-fill me-1"></i> Add New Student
                        </button>
                        <form action="{{ route('registrar.students.unenrollAll') }}" method="POST" onsubmit="return confirm('WARNING: Are you sure you want to end the semester? This will set ALL students to Unenrolled and clear all assigned RFID tags.');">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger fw-semibold">
                                <i class="bi bi-calendar-x me-1"></i> End of Semester
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Filters & Search -->
            <div class="card page-card mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('registrar.students') }}" class="row g-2 align-items-center">
                        <div class="col-12 col-lg">
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Name or Student #..." class="form-control">
                            </div>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <select name="year" id="filter_year" class="form-select">
                                <option value="All Years" {{ request('year', 'All Years') === 'All Years' ? 'selected' : '' }}>All Years</option>
                                <option value="1" {{ request('year') == '1' ? 'selected' : '' }}>Year 1</option>
                                <option value="2" {{ request('year') == '2' ? 'selected' : '' }}>Year 2</option>
                                <option value="3" {{ request('year') == '3' ? 'selected' : '' }}>Year 3</option>
                                <option value="4" {{ request('year') == '4' ? 'selected' : '' }}>Year 4</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <select name="section" id="filter_section" class="form-select">
                                <option value="All Sections">All Sections</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-auto">
                            <button type="submit" class="btn btn-dark fw-semibold w-100">
                                <i class="bi bi-funnel me-1"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Student Records Table -->
            <div class="card page-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4 py-3">Student #</th>
                                <th class="py-3">Name</th>
                                <th class="py-3">Course / Year / Sec</th>
                                <th class="py-3">RFID Tag</th>
                                <th class="py-3">Status</th>
                                <th class="py-3 pe-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td class="ps-4 fw-semibold text-dark">{{ $student->student_number ?? 'N/A' }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($student->photo)
                                                <img src="{{ asset('storage/' . ltrim($student->photo, '/')) }}" alt="{{ $student->first_name }} {{ $student->last_name }}" class="rounded-circle object-fit-cover" style="width:34px;height:34px;">
                                            @else
                                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success fw-bold" style="width:34px;height:34px;">
                                                    {{ strtoupper(substr($student->first_name, 0, 1)) }}{{ strtoupper(substr($student->last_name, 0, 1)) }}
                                                </span>
                                            @endif
                                            <span class="fw-medium text-dark">{{ $student->first_name }} {{ $student->last_name }}</span>
                                        </div>
                                    </td>
                                    <td class="text-secondary">{{ $student->course }} &middot; Year {{ $student->year_level }} &middot; {{ $student->section }}</td>
                                    <td>
                                        @if($student->rfid_tag)
                                            <span class="badge text-bg-success rfid-code">{{ $student->rfid_tag }}</span>
                                        @else
                                            <span class="badge text-bg-warning">Unassigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge rounded-pill {{ $student->status === 'Enrolled' ? 'text-bg-success' : 'text-bg-danger' }}">
                                                {{ $student->status }}
                                            </span>
                                            <form action="{{ route('registrar.students.photo', $student->id) }}" method="POST" enctype="multipart/form-data" class="d-inline">
                                                @csrf
                                                <input type="file" name="photo" id="photo_{{ $student->id }}" accept="image/*" class="d-none" onchange="this.form.submit()">
                                                <label for="photo_{{ $student->id }}" class="btn btn-sm btn-outline-secondary mb-0" title="Insert photo">
                                                    <i class="bi bi-camera"></i>
                                                </label>
                                            </form>
                                        </div>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" onclick="editStudent({{ json_encode($student) }})" class="btn btn-sm btn-outline-primary" title="Edit / RFID">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <form action="{{ route('registrar.students.destroy', $student->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this student permanently?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-5 text-center text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary"></i>
                                        No student records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination footer --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-4 py-3 border-top bg-white">
                    <p class="small text-muted mb-0">
                        @if ($students->total() > 0)
                            Showing <strong>{{ $students->firstItem() }}</strong>–<strong>{{ $students->lastItem() }}</strong>
                            of <strong>{{ $students->total() }}</strong> student record{{ $students->total() === 1 ? '' : 's' }}
                        @else
                            No student records found.
                        @endif
                    </p>

                    {{ $students->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            </div>

        </main>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('registrar.students.store') }}" method="POST" onsubmit="return validateAddForm()">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="addModalLabel">Add New Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Student Number -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary" for="add_student_number">Student Number (3 Letters + 8 Numbers)</label>
                            <input
                                type="text"
                                name="student_number"
                                id="add_student_number"
                                placeholder="{{ $latestStudentNumber ? 'e.g., '.$latestStudentNumber : 'e.g., MAR87654320' }}"
                                required
                                class="form-control text-uppercase"
                            >
                            @if ($latestStudentNumber)
                                <div class="form-text">Most recent student number entered: <span class="font-monospace fw-semibold">{{ $latestStudentNumber }}</span></div>
                            @endif
                            <div class="text-danger small hidden" id="add_student_error">Must be 3 letters followed by 8 numbers (e.g., {{ $latestStudentNumber ?: 'MAR87654320' }})</div>
                        </div>

                        <!-- First & Last Name -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary" for="add_first_name">First Name</label>
                                <input type="text" name="first_name" id="add_first_name" placeholder="First Name" required class="form-control">
                                <div class="text-danger small hidden" id="add_first_error">Letters only (no numbers)</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary" for="add_last_name">Last Name</label>
                                <input type="text" name="last_name" id="add_last_name" placeholder="Last Name" required class="form-control">
                                <div class="text-danger small hidden" id="add_last_error">Letters only (no numbers)</div>
                            </div>
                        </div>

                        <!-- Course -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary" for="add_course">Course</label>
                            <select name="course" id="add_course" required class="form-select">
                                <option value="">-- Select Course --</option>
                                <option value="BSIT">BSIT (Bachelor of Science in Information Technology)</option>
                            </select>
                        </div>

                        <!-- Year Level & Section -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary" for="add_year_level">Year Level</label>
                                <select name="year_level" id="add_year_level" required class="form-select">
                                    <option value="">-- Select Year --</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary" for="add_section">Section</label>
                                <select name="section" id="add_section" required class="form-select" disabled>
                                    <option value="">-- Select Year First --</option>
                                </select>
                            </div>
                        </div>

                        <!-- Phone Number -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary" for="add_phone_number">Phone Number</label>
                            <input type="tel" name="phone_number" id="add_phone_number" placeholder="e.g., 09171234567" class="form-control" autocomplete="off">
                            <div class="text-danger small hidden" id="add_phone_error">Enter a valid PH mobile number (e.g., 09171234567 or +639171234567)</div>
                            <div class="form-text">Receives an SMS confirmation when the student enters the school premises.</div>
                        </div>

                        <!-- RFID Tag (Optional) -->
                        <div class="rfid-picker">
                            <label class="form-label small fw-semibold text-secondary" for="add_rfid_tag">RFID Tag (Optional)</label>
                            <div class="input-group">
                                <input type="text" name="rfid_tag" id="add_rfid_tag" placeholder="Scan an RFID tag or type it here" class="form-control" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary" id="add_rfid_toggle" aria-label="Show RFID scan options">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                            <div class="rfid-dropdown d-none" id="add_rfid_dropdown">
                                <div class="p-2">
                                    <button type="button" class="btn btn-ieti btn-sm w-100 fw-semibold" id="scan_rfid_btn">
                                        <i class="bi bi-upc-scan me-1"></i> Scan an RFID
                                    </button>
                                    <input type="text" id="scan_rfid_input" class="form-control form-control-sm mt-2 d-none" placeholder="Scan the card or type the tag, then press Enter" autocomplete="off">
                                    <div class="small text-muted mt-1 d-none" id="scan_rfid_hint">Waiting for an RFID scan...</div>
                                    <div class="small text-danger mt-2 d-none" id="add_rfid_warning"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-ieti fw-semibold">Save Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Student / RFID Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editForm" method="POST" onsubmit="return validateEditForm()">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="editModalLabel">Edit Student &amp; RFID Tag</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Student Number -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary" for="edit_student_number">Student Number (3 Letters + 8 Numbers)</label>
                            <input type="text" id="edit_student_number" name="student_number" required class="form-control text-uppercase">
                            <div class="text-danger small hidden" id="edit_student_error">Must be 3 letters followed by 8 numbers (e.g., MAR87654320)</div>
                        </div>

                        <!-- First & Last Name -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary" for="edit_first_name">First Name</label>
                                <input type="text" id="edit_first_name" name="first_name" required class="form-control">
                                <div class="text-danger small hidden" id="edit_first_error">Letters only (no numbers)</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary" for="edit_last_name">Last Name</label>
                                <input type="text" id="edit_last_name" name="last_name" required class="form-control">
                                <div class="text-danger small hidden" id="edit_last_error">Letters only (no numbers)</div>
                            </div>
                        </div>

                        <!-- Course -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary" for="edit_course">Course</label>
                            <select id="edit_course" name="course" required class="form-select">
                                <option value="">-- Select Course --</option>
                                <option value="BSIT">BSIT (Bachelor of Science in Information Technology)</option>
                            </select>
                        </div>

                        <!-- Year Level & Section -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary" for="edit_year_level">Year Level</label>
                                <select id="edit_year_level" name="year_level" required class="form-select">
                                    <option value="">-- Select Year --</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary" for="edit_section">Section</label>
                                <select id="edit_section" name="section" required class="form-select" disabled>
                                    <option value="">-- Select Year First --</option>
                                </select>
                            </div>
                        </div>

                        <!-- Phone Number -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary" for="edit_phone_number">Phone Number</label>
                            <input type="tel" id="edit_phone_number" name="phone_number" placeholder="e.g., 09171234567" class="form-control" autocomplete="off">
                            <div class="text-danger small hidden" id="edit_phone_error">Enter a valid PH mobile number (e.g., 09171234567 or +639171234567)</div>
                        </div>

                        <!-- RFID Tag Assignment -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary" for="edit_rfid_tag">RFID Tag Assignment</label>
                            <input type="text" id="edit_rfid_tag" name="rfid_tag" placeholder="Scan or enter RFID Tag" class="form-control">
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="form-label small fw-semibold text-secondary" for="edit_status">Status</label>
                            <select id="edit_status" name="status" required class="form-select">
                                <option value="Enrolled">Enrolled</option>
                                <option value="Unenrolled">Unenrolled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-ieti fw-semibold">Update Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Bootstrap JS (modals) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Modal Logic & Validation -->
    <script>
        function openModal(id) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).show();
        }

        function closeModal(id) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).hide();
        }

        // RFID picker for the Add Student modal
        const addRfidInput = document.getElementById('add_rfid_tag');
        const addRfidToggle = document.getElementById('add_rfid_toggle');
        const addRfidDropdown = document.getElementById('add_rfid_dropdown');
        const scanRfidBtn = document.getElementById('scan_rfid_btn');
        const scanRfidInput = document.getElementById('scan_rfid_input');
        const scanRfidHint = document.getElementById('scan_rfid_hint');
        const addRfidWarning = document.getElementById('add_rfid_warning');

        const rfidScanModeUrl = '{{ route('registrar.rfid.scan-mode') }}';
        const rfidScanModeCancelUrl = '{{ route('registrar.rfid.scan-mode.cancel') }}';
        const rfidScanLatestUrl = '{{ route('registrar.rfid.scan-latest') }}';
        const csrfToken = document.querySelector('#addModal input[name="_token"]').value;

        const assignedRfidOwners = @json($assignedRfidOwners ?? []);
        let scanCommitTimer = null;

        function normalizeRfid(value) {
            return (value || '').replace(/\s+/g, '').toUpperCase();
        }

        function clearRfidWarning() {
            addRfidWarning.textContent = '';
            addRfidWarning.classList.add('d-none');
        }

        function showRfidWarning(message) {
            addRfidWarning.textContent = message;
            addRfidWarning.classList.remove('d-none');
            openRfidDropdown();
        }

        function commitScannedRfid(value, owner) {
            cancelRegistrationScan();

            const tag = normalizeRfid(value);

            if (!tag) {
                return false;
            }

            const assignedOwner = owner || assignedRfidOwners[tag];

            if (assignedOwner) {
                addRfidInput.value = '';
                scanRfidInput.value = '';
                scanRfidInput.classList.add('d-none');
                scanRfidHint.classList.add('d-none');
                showRfidWarning(tag + ' is already assigned to ' + assignedOwner + '.');
                window.alert('This rfid is already registered');

                return false;
            }

            clearRfidWarning();

            addRfidInput.value = tag;
            scanRfidInput.value = '';
            scanRfidInput.classList.add('d-none');
            scanRfidHint.classList.add('d-none');
            closeRfidDropdown();

            return true;
        }

        function scheduleScanCommit(input) {
            clearTimeout(scanCommitTimer);
            scanCommitTimer = setTimeout(function () {
                if (normalizeRfid(input.value)) {
                    commitScannedRfid(input.value);
                }
            }, 400);
        }

        function openRfidDropdown() {
            addRfidDropdown.classList.remove('d-none');
        }

        function closeRfidDropdown() {
            addRfidDropdown.classList.add('d-none');
            scanRfidInput.classList.add('d-none');
            scanRfidHint.classList.add('d-none');
            clearRfidWarning();
            cancelRegistrationScan();
        }

        addRfidInput.addEventListener('click', openRfidDropdown);
        addRfidInput.addEventListener('input', function () {
            clearRfidWarning();
            scheduleScanCommit(addRfidInput);
        });
        addRfidInput.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();
            commitScannedRfid(addRfidInput.value);
        });
        addRfidToggle.addEventListener('click', function (event) {
            event.preventDefault();

            if (addRfidDropdown.classList.contains('d-none')) {
                openRfidDropdown();
            } else {
                closeRfidDropdown();
            }
        });

        scanRfidBtn.addEventListener('click', function () {
            clearRfidWarning();
            scanRfidInput.value = '';
            scanRfidInput.classList.remove('d-none');
            scanRfidHint.classList.remove('d-none');
            scanRfidInput.focus();
            beginRegistrationScan();
        });

        scanRfidInput.addEventListener('input', function () {
            clearRfidWarning();
            cancelRegistrationScan();
            scheduleScanCommit(scanRfidInput);
        });

        scanRfidInput.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();
            cancelRegistrationScan();
            commitScannedRfid(scanRfidInput.value);
        });

        document.addEventListener('click', function (event) {
            if (!addRfidDropdown.classList.contains('d-none') && !event.target.closest('.rfid-picker')) {
                closeRfidDropdown();
            }
        });

        document.getElementById('addModal').addEventListener('hidden.bs.modal', function () {
            clearTimeout(scanCommitTimer);
            clearRfidWarning();
            closeRfidDropdown();
        });

        document.getElementById('addModal').addEventListener('shown.bs.modal', beginRegistrationScan);

        // Registration scan mode: while armed, the API captures taps for the
        // registrar instead of adding them to the guard's live entry feed.
        let rfidPollTimer = null;
        let rfidPolling = false;
        let registrationScanArmed = false;
        let lastScanNonce = null;

        function parseJsonResponse(response) {
            return response.ok ? response.json() : null;
        }

        function fetchLatestRfid() {
            return fetch(rfidScanLatestUrl, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            }).then(parseJsonResponse).catch(function () {
                return null;
            });
        }

        function beginRegistrationScan() {
            fetch(rfidScanModeUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            }).then(function (response) {
                registrationScanArmed = response.ok;
                startRfidPolling();
            }).catch(function () {
                startRfidPolling();
            });
        }

        function cancelRegistrationScan() {
            stopRfidPolling();

            if (!registrationScanArmed) {
                return;
            }

            registrationScanArmed = false;

            fetch(rfidScanModeCancelUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            }).catch(function () {
                // ignore network errors
            });
        }

        function startRfidPolling() {
            stopRfidPolling();

            rfidPolling = true;
            lastScanNonce = null;

            rfidPollTimer = setInterval(function () {
                if (!rfidPolling) {
                    return;
                }

                fetchLatestRfid().then(function (latest) {
                    if (!rfidPolling || !latest || !latest.uid || latest.nonce === lastScanNonce) {
                        return;
                    }

                    lastScanNonce = latest.nonce;
                    registrationScanArmed = false;
                    stopRfidPolling();
                    commitScannedRfid(latest.uid, latest.owner);
                });
            }, 1000);
        }

        function stopRfidPolling() {
            rfidPolling = false;

            if (rfidPollTimer) {
                clearInterval(rfidPollTimer);
                rfidPollTimer = null;
            }
        }

        const SECTIONS_BY_YEAR = {
            '1': ['S1A1', 'S1B1', 'S1C1'],
            '2': ['S3B1', 'S3C1'],
            '3': ['S5B2', 'S5C2'],
            '4': ['S7C3', 'S7C4'],
        };

        const ALL_SECTIONS = Object.values(SECTIONS_BY_YEAR).flat();

        function populateSectionOptions(yearSelect, sectionSelect, selectedSection) {
            const year = String(yearSelect.value || '');
            const sections = SECTIONS_BY_YEAR[year] || [];

            sectionSelect.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = year ? '-- Select Section --' : '-- Select Year First --';
            sectionSelect.appendChild(placeholder);

            sections.forEach(function (section) {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                sectionSelect.appendChild(option);
            });

            sectionSelect.disabled = sections.length === 0;

            if (selectedSection && sections.includes(selectedSection)) {
                sectionSelect.value = selectedSection;
            } else {
                sectionSelect.value = '';
            }
        }

        function populateFilterSectionOptions(selectedSection) {
            const filterYear = document.getElementById('filter_year');
            const filterSection = document.getElementById('filter_section');
            if (!filterYear || !filterSection) return;

            const year = String(filterYear.value || '');
            const sections = (year && year !== 'All Years')
                ? (SECTIONS_BY_YEAR[year] || [])
                : ALL_SECTIONS;

            filterSection.innerHTML = '';

            const allOption = document.createElement('option');
            allOption.value = 'All Sections';
            allOption.textContent = 'All Sections';
            filterSection.appendChild(allOption);

            sections.forEach(function (section) {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                filterSection.appendChild(option);
            });

            if (selectedSection && selectedSection !== 'All Sections' && sections.includes(selectedSection)) {
                filterSection.value = selectedSection;
            } else {
                filterSection.value = 'All Sections';
            }
        }

        const addYearLevel = document.getElementById('add_year_level');
        const addSection = document.getElementById('add_section');
        const editYearLevel = document.getElementById('edit_year_level');
        const editSection = document.getElementById('edit_section');
        const filterYear = document.getElementById('filter_year');

        addYearLevel?.addEventListener('change', function () {
            populateSectionOptions(addYearLevel, addSection);
        });

        editYearLevel?.addEventListener('change', function () {
            populateSectionOptions(editYearLevel, editSection);
        });

        filterYear?.addEventListener('change', function () {
            populateFilterSectionOptions();
        });

        populateFilterSectionOptions(@json(request('section', 'All Sections')));

        function editStudent(student) {
            document.getElementById('editForm').action = '/registrar/students/' + student.id;
            document.getElementById('edit_student_number').value = student.student_number || '';
            document.getElementById('edit_first_name').value = student.first_name || '';
            document.getElementById('edit_last_name').value = student.last_name || '';
            document.getElementById('edit_course').value = student.course || 'BSIT';
            document.getElementById('edit_year_level').value = student.year_level || '';
            populateSectionOptions(editYearLevel, editSection, student.section || '');
            document.getElementById('edit_phone_number').value = student.phone_number || '';
            document.getElementById('edit_rfid_tag').value = student.rfid_tag || '';
            document.getElementById('edit_status').value = student.status || 'Enrolled';
            openModal('editModal');
        }

        // Validation for Add Form
        function validateAddForm() {
            let valid = true;
            const studentNumberInput = document.getElementById('add_student_number');
            // CSS text-uppercase only changes appearance — normalize the real value before submit.
            const studentNum = studentNumberInput.value.trim().toUpperCase();
            studentNumberInput.value = studentNum;
            const firstName = document.getElementById('add_first_name').value;
            const lastName = document.getElementById('add_last_name').value;

            // Validate Student Number (3 letters + 8 numbers format)
            const studentRegex = /^[A-Z]{3}\d{8}$/;
            if (!studentRegex.test(studentNum)) {
                document.getElementById('add_student_error').classList.remove('hidden');
                valid = false;
            } else {
                document.getElementById('add_student_error').classList.add('hidden');
            }

            // Validate First Name (letters only)
            const nameRegex = /^[a-zA-Z\s\'-]+$/;
            if (!nameRegex.test(firstName)) {
                document.getElementById('add_first_error').classList.remove('hidden');
                valid = false;
            } else {
                document.getElementById('add_first_error').classList.add('hidden');
            }

            // Validate Last Name (letters only)
            if (!nameRegex.test(lastName)) {
                document.getElementById('add_last_error').classList.remove('hidden');
                valid = false;
            } else {
                document.getElementById('add_last_error').classList.add('hidden');
            }

            // Validate Phone Number (optional, PH mobile format)
            const addPhone = document.getElementById('add_phone_number').value.trim();
            const phoneRegex = /^(?:\+?63|0)9\d{9}$/;
            if (addPhone !== '' && !phoneRegex.test(addPhone)) {
                document.getElementById('add_phone_error').classList.remove('hidden');
                valid = false;
            } else {
                document.getElementById('add_phone_error').classList.add('hidden');
            }

            return valid;
        }

        // Validation for Edit Form
        function validateEditForm() {
            let valid = true;
            const studentNumberInput = document.getElementById('edit_student_number');
            // CSS text-uppercase only changes appearance — normalize the real value before submit.
            const studentNum = studentNumberInput.value.trim().toUpperCase();
            studentNumberInput.value = studentNum;
            const firstName = document.getElementById('edit_first_name').value;
            const lastName = document.getElementById('edit_last_name').value;

            // Validate Student Number (3 letters + 8 numbers format)
            const studentRegex = /^[A-Z]{3}\d{8}$/;
            if (!studentRegex.test(studentNum)) {
                document.getElementById('edit_student_error').classList.remove('hidden');
                valid = false;
            } else {
                document.getElementById('edit_student_error').classList.add('hidden');
            }

            // Validate First Name (letters only)
            const nameRegex = /^[a-zA-Z\s\'-]+$/;
            if (!nameRegex.test(firstName)) {
                document.getElementById('edit_first_error').classList.remove('hidden');
                valid = false;
            } else {
                document.getElementById('edit_first_error').classList.add('hidden');
            }

            // Validate Last Name (letters only)
            if (!nameRegex.test(lastName)) {
                document.getElementById('edit_last_error').classList.remove('hidden');
                valid = false;
            } else {
                document.getElementById('edit_last_error').classList.add('hidden');
            }

            // Validate Phone Number (optional, PH mobile format)
            const editPhone = document.getElementById('edit_phone_number').value.trim();
            const phoneRegex = /^(?:\+?63|0)9\d{9}$/;
            if (editPhone !== '' && !phoneRegex.test(editPhone)) {
                document.getElementById('edit_phone_error').classList.remove('hidden');
                valid = false;
            } else {
                document.getElementById('edit_phone_error').classList.add('hidden');
            }

            return valid;
        }
    </script>

</body>
</html>
