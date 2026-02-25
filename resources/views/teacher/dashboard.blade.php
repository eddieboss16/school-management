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
                                <h4 class="font-semibold">{{ $class->subject->name }}</h4>
                                <p class="text-sm text-gray-600">{{ $class->grade->name }} {{ $class->stream->name }}</p>
                                <p class="text-sm text-gray-500">Students: {{ $class->students->count() }} / {{ $class->capacity }}</p>
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