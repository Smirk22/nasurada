<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records - IETI</title>
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
</head>
<body class="bg-gray-100 font-sans min-h-screen">

    <div class="flex min-h-screen">
        {{-- 1. Sidebar Navigation --}}
        @include('partials.sidebar') {{-- Update path if your sidebar is stored elsewhere (e.g. 'registrar.partials.sidebar') --}}

        {{-- 2. Main Content Area --}}
        <main class="flex-1 p-8 space-y-6 overflow-y-auto">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

        <!-- Action Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-2xl shadow border border-gray-100">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Student Records</h1>
                <p class="text-xs text-gray-500 mt-1">Manage enrollments, edit information, assign RFID tags, or trigger semester reset.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button onclick="openModal('addModal')" class="bg-ieti-green hover:bg-green-800 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">
                    + Add New Student
                </button>
                <form action="{{ route('registrar.students.unenrollAll') }}" method="POST" onsubmit="return confirm('WARNING: Are you sure you want to end the semester? This will set ALL students to Unenrolled and clear all assigned RFID tags.');">
                    @csrf
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">
                         End of Semester (Unenroll All)
                    </button>
                </form>
            </div>
        </div>

        <!-- Filters & Search -->
        <form method="GET" action="{{ route('registrar.students') }}" class="bg-white p-4 rounded-xl shadow border border-gray-100 flex flex-col md:flex-row gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Name or Student #..." class="border rounded-lg px-3 py-2 text-sm flex-1 focus:outline-none focus:ring-2 focus:ring-green-600">
            <select name="year" class="border rounded-lg px-3 py-2 text-sm">
                <option value="All Years">All Years</option>
                <option value="1" {{ request('year') == '1' ? 'selected' : '' }}>Year 1</option>
                <option value="2" {{ request('year') == '2' ? 'selected' : '' }}>Year 2</option>
                <option value="3" {{ request('year') == '3' ? 'selected' : '' }}>Year 3</option>
                <option value="4" {{ request('year') == '4' ? 'selected' : '' }}>Year 4</option>
            </select>
            <button type="submit" class="bg-gray-800 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-gray-900 transition">Filter</button>
        </form>

        <!-- Student Records Table -->
        <div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase font-semibold">
                        <th class="p-4">Student #</th>
                        <th class="p-4">Name</th>
                        <th class="p-4">Course / Year / Sec</th>
                        <th class="p-4">RFID Tag</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($students as $student)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-4 font-semibold text-gray-800">{{ $student->student_number ?? 'N/A' }}</td>
                            <td class="p-4 font-medium text-gray-900">{{ $student->first_name }} {{ $student->last_name }}</td>
                            <td class="p-4 text-gray-600">{{ $student->course }} - {{ $student->year_level }}{{ $student->section }}</td>
                            <td class="p-4">
                                @if($student->rfid_tag)
                                    <span class="bg-green-100 text-green-800 font-mono text-xs px-2 py-1 rounded-md">{{ $student->rfid_tag }}</span>
                                @else
                                    <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-md">Unassigned</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $student->status === 'Enrolled' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $student->status }}
                                </span>
                            </td>
                            <td class="p-4 text-center space-x-2">
                                <button onclick="editStudent({{ json_encode($student) }})" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">Edit / RFID</button>
                                <form action="{{ route('registrar.students.destroy', $student->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this student permanently?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-semibold ml-2">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-gray-400 text-sm">No student records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </main>

    <!-- Add Student Modal -->
    <div id="addModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Add New Student</h3>
            <form action="{{ route('registrar.students.store') }}" method="POST" class="space-y-3" onsubmit="return validateAddForm()">
                @csrf

                <!-- Student Number -->
                <div>
                    <label class="text-xs text-gray-600 font-semibold block mb-1">Student Number (Format: 3 Letters + 8 Numbers)</label>
                    <input type="text" name="student_number" id="add_student_number" placeholder="e.g., MAR87654320" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600 uppercase">
                    <small class="text-red-500 hidden" id="add_student_error">Must be 3 letters followed by 8 numbers (e.g., MAR87654320)</small>
                </div>

                <!-- First & Last Name -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs text-gray-600 font-semibold block mb-1">First Name</label>
                        <input type="text" name="first_name" id="add_first_name" placeholder="First Name" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                        <small class="text-red-500 hidden" id="add_first_error">Letters only (no numbers)</small>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600 font-semibold block mb-1">Last Name</label>
                        <input type="text" name="last_name" id="add_last_name" placeholder="Last Name" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                        <small class="text-red-500 hidden" id="add_last_error">Letters only (no numbers)</small>
                    </div>
                </div>

                <!-- Course (Dropdown - BSIT only) -->
                <div>
                    <label class="text-xs text-gray-600 font-semibold block mb-1">Course</label>
                    <select name="course" id="add_course" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                        <option value="">-- Select Course --</option>
                        <option value="BSIT">BSIT (Bachelor of Science in Information Technology)</option>
                    </select>
                </div>

                <!-- Year Level (Dropdown - 1-4 only) -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs text-gray-600 font-semibold block mb-1">Year Level</label>
                        <select name="year_level" id="add_year_level" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                            <option value="">-- Select Year --</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>

                    <!-- Section (Dropdown - predefined sections only) -->
                    <div>
                        <label class="text-xs text-gray-600 font-semibold block mb-1">Section</label>
                        <select name="section" id="add_section" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
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
                    <label class="text-xs text-gray-600 font-semibold block mb-1">RFID Tag (Optional)</label>
                    <input type="text" name="rfid_tag" id="add_rfid_tag" placeholder="RFID Tag Code" class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>

                <!-- Buttons -->
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="closeModal('addModal')" class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-ieti-green text-white rounded-lg font-semibold hover:bg-green-800 transition">Save Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Student / RFID Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Edit Student & RFID Tag</h3>
            <form id="editForm" method="POST" class="space-y-3" onsubmit="return validateEditForm()">
                @csrf
                @method('PUT')

                <!-- Student Number -->
                <div>
                    <label class="text-xs text-gray-600 font-semibold block mb-1">Student Number (Format: 3 Letters + 8 Numbers)</label>
                    <input type="text" id="edit_student_number" name="student_number" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600 uppercase">
                    <small class="text-red-500 hidden" id="edit_student_error">Must be 3 letters followed by 8 numbers (e.g., MAR87654320)</small>
                </div>

                <!-- First & Last Name -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs text-gray-600 font-semibold block mb-1">First Name</label>
                        <input type="text" id="edit_first_name" name="first_name" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                        <small class="text-red-500 hidden" id="edit_first_error">Letters only (no numbers)</small>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600 font-semibold block mb-1">Last Name</label>
                        <input type="text" id="edit_last_name" name="last_name" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                        <small class="text-red-500 hidden" id="edit_last_error">Letters only (no numbers)</small>
                    </div>
                </div>

                <!-- Course (Dropdown - BSIT only) -->
                <div>
                    <label class="text-xs text-gray-600 font-semibold block mb-1">Course</label>
                    <select id="edit_course" name="course" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                        <option value="">-- Select Course --</option>
                        <option value="BSIT">BSIT (Bachelor of Science in Information Technology)</option>
                    </select>
                </div>

                <!-- Year Level (Dropdown - 1-4 only) -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs text-gray-600 font-semibold block mb-1">Year Level</label>
                        <select id="edit_year_level" name="year_level" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                            <option value="">-- Select Year --</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>

                    <!-- Section (Dropdown - predefined sections only) -->
                    <div>
                        <label class="text-xs text-gray-600 font-semibold block mb-1">Section</label>
                        <select id="edit_section" name="section" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                            <option value="">-- Select Section --</option>
                            <option value="S1B1">S1B1</option>
                            <option value="S1B2">S1B2</option>
                            <option value="S4B1">S4B1</option>
                            <option value="S7C3">S7C3</option>
                        </select>
                    </div>
                </div>

                <!-- RFID Tag Assignment -->
                <div>
                    <label class="text-xs text-gray-600 font-semibold block mb-1">RFID Tag Assignment</label>
                    <input type="text" id="edit_rfid_tag" name="rfid_tag" placeholder="Scan or enter RFID Tag" class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>

                <!-- Status -->
                <div>
                    <label class="text-xs text-gray-600 font-semibold block mb-1">Status</label>
                    <select id="edit_status" name="status" required class="w-full border p-2 rounded text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                        <option value="Enrolled">Enrolled</option>
                        <option value="Unenrolled">Unenrolled</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-ieti-green text-white rounded-lg font-semibold hover:bg-green-800 transition">Update Record</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Logic & Validation -->
    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
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