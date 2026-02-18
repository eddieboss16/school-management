<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Class') }}
            </h2>
            <a href="{{ route('admin.classes') }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to Classes
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.classes.update', $class->id) }}">
                        @csrf
                        @method('PUT')

                        <!-- Grade Selection -->
                        <div class="mb-4">
                            <label for="grade_id" class="block text-sm font-medium text-gray-700">Grade</label>
                            <select name="grade_id" id="grade_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Grade</option>
                                @foreach($grades as $grade)
                                    <option value="{{ $grade->id }}" {{ old('grade_id', $class->grade_id) == $grade->id ? 'selected' : '' }}>
                                        {{ $grade->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('grade_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Stream Selection -->
                        <div class="mb-4">
                            <label for="stream_id" class="block text-sm font-medium text-gray-700">Stream</label>
                            <select name="stream_id" id="stream_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Stream</option>
                                @foreach($streams as $stream)
                                    <option value="{{ $stream->id }}" {{ old('stream_id', $class->stream_id) == $stream->id ? 'selected' : '' }}>
                                        {{ $stream->grade->name }} - {{ $stream->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('stream_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Subject Selection -->
                        <div class="mb-4">
                            <label for="subject_id" class="block text-sm font-medium text-gray-700">Subject</label>
                            <select name="subject_id" id="subject_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Subject</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ old('subject_id', $class->subject_id) == $subject->id ? 'selected' : '' }}>
                                        {{ $subject->name }} ({{ $subject->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('subject_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Teacher Selection -->
                        <div class="mb-4">
                            <label for="teacher_id" class="block text-sm font-medium text-gray-700">Teacher</label>
                            <select name="teacher_id" id="teacher_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Teacher</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ old('teacher_id', $class->teacher_id) == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('teacher_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Capacity -->
                        <div class="mb-4">
                            <label for="capacity" class="block text-sm font-medium text-gray-700">Capacity (Max Students)</label>
                            <input type="number" name="capacity" id="capacity" value="{{ old('capacity', $class->capacity) }}" min="1" max="100" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('capacity')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Current Class Code Display -->
                        <div class="mb-4 p-4 bg-gray-50 rounded-md">
                            <p class="text-sm text-gray-700">
                                <strong>Current Class Code:</strong> {{ $class->class_code }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">The class code will be automatically updated based on your changes.</p>
                        </div>

                        <!-- Buttons -->
                        <div class="flex items-center justify-between">
                            <a href="{{ route('admin.classes') }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Update Class
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>