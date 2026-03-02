<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Enter Grades
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

            <!-- Grade Entry Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('teacher.grades.store', $class->id) }}">
                        @csrf

                        <!-- Assessment Details -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div>
                                <label for="assessment_type" class="block text-sm font-medium text-gray-700 mb-2">Assessment Type</label>
                                <input type="text" name="assessment_type" id="assessment_type" 
                                    placeholder="e.g., Quiz 1, Midterm, Final Exam" required
                                    value="{{ old('assessment_type') }}"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('assessment_type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="assessment_date" class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                                <input type="date" name="assessment_date" id="assessment_date" required
                                    value="{{ old('assessment_date', now()->format('Y-m-d')) }}"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('assessment_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="max_score" class="block text-sm font-medium text-gray-700 mb-2">Max Score</label>
                                <input type="number" name="max_score" id="max_score" step="0.01" min="1" required
                                    value="{{ old('max_score', 100) }}"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('max_score')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        @if($class->students->count() > 0)
                            <div class="mb-4">
                                <h4 class="font-semibold mb-4">Enter Student Scores</h4>

                                <div class="space-y-3">
                                    @foreach($class->students->sortBy('name') as $student)
                                        <div class="border rounded-lg p-4">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <p class="font-medium">{{ $student->name }}</p>
                                                    <p class="text-sm text-gray-500">{{ $student->email }}</p>
                                                </div>
                                                <div class="flex gap-4 items-center">
                                                    <div>
                                                        <label class="block text-sm text-gray-600 mb-1">Score</label>
                                                        <input type="number" name="grades[{{ $student->id }}][score]" 
                                                            step="0.01" min="0" required
                                                            placeholder="0.00"
                                                            class="w-24 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    </div>
                                                    <div class="flex-1">
                                                        <label class="block text-sm text-gray-600 mb-1">Remarks (Optional)</label>
                                                        <input type="text" name="grades[{{ $student->id }}][remarks]" 
                                                            placeholder="e.g., Excellent work"
                                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex justify-between items-center pt-4 border-t">
                                <a href="{{ route('teacher.dashboard') }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
                                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                                    Submit Grades
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
</x-app-layout>