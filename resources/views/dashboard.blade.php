<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Student Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Welcome Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-2xl font-bold mb-2">Welcome, {{ $student->name }}!</h3>
                    <div class="text-gray-600 space-y-1">
                        <p><strong>Admission Number:</strong> {{ $student->admission_number ?? 'Not assigned' }}</p>
                        <p><strong>Email:</strong> {{ $student->email }}</p>
                        @if($student->stream)
                            <p><strong>Class:</strong> {{ $student->stream->grade->name }} {{ $student->stream->name }}</p>
                        @else
                            <p class="text-orange-600"><strong>Class:</strong> Not assigned to a stream yet</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Stats Card -->
            <div class="grid grid-cols-1 md:grid-col-2 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <p class="text-gray-500 text-sm">Enrolled Classes</p>
                        <p class="text-3xl font-bold text-blue-600">{{ $totalClasses }}</p>
                    </div>
                </div>
            
                <!-- Attendance Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="text-gray-500 text-sm">Attendance Rate</p>
                        <p class="text-3xl font-bold text-blue-600">{{ $attendancePercentage }}%</p>
                        <div class="flex gap-4 mt-2 text-xs text-gray-500">
                            <span>Present: {{ $presentCount }}</span>
                            <span>Absent: {{ $absentCount }}</span>
                            <span>LateCount: {{ $lateCount }}</span>
                        </div>
                    </div>
                    <div class="p-6">
                    
                    </div>

                    <!-- View Attendance Button -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6 flex justify-between">
                            <a href="{{ route('student.attendance') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                View My Attendance History
                            </a>
                            <a href="{{ route('student.grades') }}" class="text-green-600 hover:text-green-800 font-medium">
                                View My Grades →
                            </a>
                            <a href="{{ route('student.report') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                View My Report Card →
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Classes -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">My Classes</h3>
                    
                    @if($classes->count() > 0)
                        <div class="space-y-4">
                            @foreach($classes as $class)
                                <div class="border rounded-lg p-4 hover:shadow-md transition">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-semibold text-lg text-gray-900">{{ $class->subject->name }}</h4>
                                            <p class="text-gray-600 text-sm">{{ $class->grade->name }} {{ $class->stream->name }}</p>
                                            <p class="text-gray-500 text-xs mt-1">Class Code: {{ $class->class_code }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm text-gray-500">Teacher</p>
                                            <p class="font-medium text-gray-900">{{ $class->teacher->name }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-3 border-t">
                                        <p class="text-xs text-gray-500">
                                            <strong>Classmates:</strong> {{ $class->students->count() }} students enrolled
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500">You are not enrolled in any classes yet.</p>
                            <p class="text-sm text-gray-400 mt-2">Please contact your administrator or class teacher.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>