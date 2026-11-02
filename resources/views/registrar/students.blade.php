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
                        <div class="col-12 col-md">
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Name or Student #..." class="form-control">
                            </div>
                        </div>
                        <div class="col-8 col-md-3">
                            <select name="year" class="form-select">
                                <option value="All Years">All Years</option>
                                <option value="1" {{ request('year') == '1' ? 'selected' : '' }}>Year 1</option>
                                <option value="2" {{ request('year') == '2' ? 'selected' : '' }}>Year 2</option>
                                <option value="3" {{ request('year') == '3' ? 'selected' : '' }}>Year 3</option>
                                <option value="4" {{ request('year') == '4' ? 'selected' : '' }}>Year 4</option>
                            </select>
                        </div>
                        <div class="col-4 col-md-auto">
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
                                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success fw-bold" style="width:34px;height:34px;">
                                                {{ strtoupper(substr($student->first_name, 0, 1)) }}{{ strtoupper(substr($student->last_name, 0, 1)) }}
                                            </span>
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
                                        <span class="badge rounded-pill {{ $student->status === 'Enrolled' ? 'text-bg-success' : 'text-bg-danger' }}">
                                            {{ $student->status }}
                                        </span>
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
                            <input type="text" name="student_number" id="add_student_number" placeholder="e.g., MAR87654320" required class="form-control text-uppercase">
                            <div class="invalid-feedback d-block text-danger small hidden" id="add_student_error">Must be 3 letters followed by 8 numbers (e.g., MAR87654320)</div>
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
                                <select name="section" id="add_section" required class="form-select">
                                    <option value="">-- Select Section --</option>
                                    <option value="S1B1">S1B1</option>
                                    <option value="S1B2">S1B2</option>
                                    <option value="S4B1">S4B1</option>
                                    <option value="S7C3">S7C3</option>
                                </select>
                            </div>
                        </div>

                        <!-- RFID Tag (Optional) -->
                        <div>
                            <label class="form-label small fw-semibold text-secondary" for="add_rfid_tag">RFID Tag (Optional)</label>
                            <input type="text" name="rfid_tag" id="add_rfid_tag" placeholder="RFID Tag Code" class="form-control">
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
                                <select id="edit_section" name="section" required class="form-select">
                                    <option value="">-- Select Section --</option>
                                    <option value="S1B1">S1B1</option>
                                    <option value="S1B2">S1B2</option>
                                    <option value="S4B1">S4B1</option>
                                    <option value="S7C3">S7C3</option>
                                </select>
                            </div>
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

        function editStudent(student) {
            document.getElementById('editForm').action = '/registrar/students/' + student.id;
            document.getElementById('edit_student_number').value = student.student_number || '';
            document.getElementById('edit_first_name').value = student.first_name || '';
            document.getElementById('edit_last_name').value = student.last_name || '';
            document.getElementById('edit_course').value = student.course || 'BSIT';
            document.getElementById('edit_year_level').value = student.year_level || '';
            document.getElementById('edit_section').value = student.section || '';
            document.getElementById('edit_rfid_tag').value = student.rfid_tag || '';
            document.getElementById('edit_status').value = student.status || 'Enrolled';
            openModal('editModal');
        }

        // Validation for Add Form
        function validateAddForm() {
            let valid = true;
            const studentNum = document.getElementById('add_student_number').value.toUpperCase();
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

            return valid;
        }

        // Validation for Edit Form
        function validateEditForm() {
            let valid = true;
            const studentNum = document.getElementById('edit_student_number').value.toUpperCase();
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

            return valid;
        }
    </script>

</body>
</html>
