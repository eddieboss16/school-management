<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Manage Enrollments') }} - {{ $class->class_code }}
            </h2>
            <a href="{{ route('admin.classes') }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to Classes
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Class Info Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Class Information</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Grade/Stream</p>
                            <p class="font-medium">{{ $class->grade->name }} {{ $class->stream->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Subject</p>
                            <p class="font-medium">{{ $class->subject->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Teacher</p>
                            <p class="font-medium">{{ $class->teacher->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Enrollment</p>
                            <p class="font-medium">{{ $class->students->count() }} / {{ $class->capacity }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Student Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Enroll Students</h3>
                        @if($availableStudents->count() > 0 && $class->students->count() < $class->capacity)
                            <form action="{{ route('admin.enrollments.bulk', $class->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm"
                                    onclick="return confirm('Enroll all {{ $availableStudents->count() }} available students?')">
                                    Enroll All Students
                                </button>
                            </form>
                        @endif
                    </div>

                    @if($availableStudents->count() > 0)
                        @if($class->students->count() < $class->capacity)
                            <!-- Search Box -->
                            <div class="mb-4">
                                <input type="text" id="studentSearch" placeholder="Search students by name or email..."
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <form method="POST" action="{{ route('admin.enrollments.store', $class->id) }}">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <select name="student_id" id="studentSelect" required size="10"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        @foreach($availableStudents as $student)
                                            <option value="{{ $student->id }}" data-name="{{ strtolower($student->name) }}" data-email="{{ strtolower($student->email) }}">
                                                {{ $student->admission_number ?? 'N/A' }} - {{ $student->name }} ({{ $student->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-sm text-gray-500 mt-1" id="studentCount">{{ $availableStudents->count() }} students available</p>
                                    @error('student_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" class="w-50 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Enroll Selected Student
                                </button>
                            </div>
                        </form>

                        <!-- JavaScript for filtering -->
                        <script>
                            document.getElementById('studentSearch').addEventListener('input', function(e) {
                                const searchTerm = e.target.value.toLowerCase();
                                const select = document.getElementById('studentSelect');
                                const options = select.querySelectorAll('option');
                                let visibleCount = 0;
                                
                                options.forEach(option => {
                                    const name = option.getAttribute('data-name');
                                    const email = option.getAttribute('data-email');
                                    
                                    if (name.includes(searchTerm) || email.includes(searchTerm)) {
                                        option.style.display = '';
                                        visibleCount++;
                                    } else {
                                        option.style.display = 'none';
                                    }
                                });
                                
                                // Update count of visible students
                                document.getElementById('studentCount').textContent = visibleCount + ' students found';
                            });
                        </script>
                        @else
                            <p class="text-orange-600 font-medium">⚠️ Class is at full capacity ({{ $class->capacity }} students)</p>
                        @endif
                    @else
                        <p class="text-gray-500">No available students from {{ $class->grade->name }} {{ $class->stream->name }} to enroll.</p>
                    @endif
                </div>
            </div>

            <!-- Enrolled Students List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Enrolled Students ({{ $class->students->count() }})</h3>
                    
                    @if($class->students->count() > 0)
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admission No.</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stream</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($class->students as $index => $student)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $index + 1 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $student->admission_number ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $student->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($student->stream)
                                                {{ $student->stream->grade->name }} {{ $student->stream->name }}
                                            @else
                                                <span class="text-gray-400">No stream</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <form action="{{ route('admin.enrollments.destroy', [$class->id, $student->id]) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900"
                                                    onclick="return confirm('Remove {{ $student->name }} from this class?')">
                                                    Remove
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-gray-500">No students enrolled yet.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>