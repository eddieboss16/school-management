<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Teacher Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Welcome -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-2xl font-bold">Welcome, {{ auth()->user()->name }}!</h3>
                    <p class="text-gray-600">Here's your teaching overview.</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <p class="text-gray-500 text-sm">My Classes</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $totalClasses }}</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <p class="text-gray-500 text-sm">Total Students</p>
                    <p class="text-3xl font-bold text-green-600">{{ $totalStudents }}</p>
                </div>
            </div>

            <!-- Classes -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">My Classes</h3>
                    
                    @if($classes->count() > 0)
                        @foreach($classes as $class)
                            <div class="border rounded p-4 mb-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold">{{ $class->subject->name }}</h4>
                                        <p class="text-sm text-gray-600">{{ $class->grade->name }} {{ $class->stream->name }}</p>
                                        <p class="text-sm text-gray-500">Students: {{ $class->students->count() }} / {{ $class->capacity }}</p>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <div class="flex gap-2">
                                            <a href="{{ route('teacher.attendance.mark', $class->id) }}" class="bg-blue-500 hover:bg-blue-700 font-bold py-2 px-4 rounded text-sm">Mark Attendance</a>
                                            <a href="{{ route('teacher.attendance.history', $class->id) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">View History</a>
                                        </div>
                                        <div class="flex gap-2">
                                            <a href="{{ route('teacher.grades.enter', $class->id) }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                                                Enter Grades
                                            </a>
                                            <a href="{{ route('teacher.grades.view', $class->id) }}" 
                                            class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded text-sm">
                                                View Grades
                                            </a>
                                        </div>
                                    </div> 
                                </div>  
                            </div>
                        @endforeach
                    @else
                        <p class="text-gray-500">No classes assigned yet.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>