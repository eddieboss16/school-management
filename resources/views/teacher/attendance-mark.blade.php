<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Mark Attendance
            </h2>
            <a href="{{ route('teacher.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Class Info -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-2">{{ $class->subject->name }}</h3>
                    <p class="text-gray-600">{{ $class->grade->name }} {{ $class->stream->name }} - {{ $class->class_code }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $class->students->count() }} students enrolled</p>
                </div>
            </div>

            @if(count($existingAttendance) > 0)
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <p class="text-yellow-700">
                        <strong>Note:</strong> Attendance already marked for today. Submitting will update the existing records.
                    </p>
                </div>
            @endif

            <!-- Attendance Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('teacher.attendance.store', $class->id) }}">
                        @csrf

                        <div class="mb-6">
                            <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                            <input type="date" name="date" id="date" value="{{ $today->format('Y-m-d') }}" required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if($class->students->count() > 0)
                            <div class="mb-4">
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="font-semibold">Mark Attendance</h4>
                                    <div class="flex gap-2 text-sm">
                                        <button type="button" onclick="markAll('present')" class="text-green-600 hover:text-green-800">Mark All Present</button>
                                        <span class="text-gray-400">|</span>
                                        <button type="button" onclick="markAll('absent')" class="text-red-600 hover:text-red-800">Mark All Absent</button>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    @foreach($class->students->sortBy('name') as $student)
                                        <div class="border rounded-lg p-4">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <p class="font-medium">{{ $student->name }}</p>
                                                    <p class="text-sm text-gray-500">{{ $student->email }}</p>
                                                </div>
                                                <div class="flex gap-2">
                                                    <label class="inline-flex items-center">
                                                        <input type="radio" name="attendance[{{ $student->id }}]" value="present" 
                                                            {{ in_array($student->id, $existingAttendance) ? '' : 'checked' }}
                                                            class="form-radio text-green-600">
                                                        <span class="ml-2 text-sm">Present</span>
                                                    </label>
                                                    <label class="inline-flex items-center">
                                                        <input type="radio" name="attendance[{{ $student->id }}]" value="absent" 
                                                            class="form-radio text-red-600">
                                                        <span class="ml-2 text-sm">Absent</span>
                                                    </label>
                                                    <label class="inline-flex items-center">
                                                        <input type="radio" name="attendance[{{ $student->id }}]" value="late" 
                                                            class="form-radio text-orange-600">
                                                        <span class="ml-2 text-sm">Late</span>
                                                    </label>
                                                    <label class="inline-flex items-center">
                                                        <input type="radio" name="attendance[{{ $student->id }}]" value="excused" 
                                                            class="form-radio text-blue-600">
                                                        <span class="ml-2 text-sm">Excused</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <input type="text" name="notes[{{ $student->id }}]" 
                                                    placeholder="Optional notes (e.g., reason for absence)"
                                                    class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex justify-between items-center pt-4 border-t">
                                <a href="{{ route('teacher.dashboard') }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                                    Submit Attendance
                                </button>
                            </div>
                        @else
                            <p class="text-gray-500">No students enrolled in this class.</p>
                        @endif
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
        function markAll(status) {
            document.querySelectorAll(`input[type="radio"][value="${status}"]`).forEach(radio => {
                radio.checked = true;
            });
        }
    </script>
</x-app-layout>