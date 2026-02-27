<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Attendance History
            </h2>
            <a href="{{ route('teacher.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Class Info -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold">{{ $class->subject->name }}</h3>
                            <p class="text-gray-600">{{ $class->grade->name }} {{ $class->stream->name }}</p>
                        </div>
                        <a href="{{ route('teacher.attendance.mark', $class->id) }}" 
                           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Mark Today's Attendance
                        </a>
                    </div>
                </div>
            </div>

            <!-- Attendance Records -->
            @if($attendanceRecords->count() > 0)
                @foreach($attendanceRecords as $date => $records)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                        <div class="p-6">
                            <h4 class="font-semibold mb-4">{{ \Carbon\Carbon::parse($date)->format('l, M d, Y') }}</h4>
                            
                            <div class="grid grid-cols-4 gap-4 mb-4">
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-green-600">{{ $records->where('status', 'present')->count() }}</p>
                                    <p class="text-sm text-gray-500">Present</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-red-600">{{ $records->where('status', 'absent')->count() }}</p>
                                    <p class="text-sm text-gray-500">Absent</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-orange-600">{{ $records->where('status', 'late')->count() }}</p>
                                    <p class="text-sm text-gray-500">Late</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-blue-600">{{ $records->where('status', 'excused')->count() }}</p>
                                    <p class="text-sm text-gray-500">Excused</p>
                                </div>
                            </div>

                            <details class="mt-4">
                                <summary class="cursor-pointer text-blue-600 text-sm font-medium hover:text-blue-800">
                                    View Details
                                </summary>
                                <div class="mt-3 space-y-2">
                                    @foreach($records->sortBy('student.name') as $record)
                                        <div class="flex justify-between items-center text-sm">
                                            <span>{{ $record->student->name }}</span>
                                            <span class="px-2 py-1 rounded text-xs font-medium
                                                {{ $record->status === 'present' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $record->status === 'absent' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ $record->status === 'late' ? 'bg-orange-100 text-orange-800' : '' }}
                                                {{ $record->status === 'excused' ? 'bg-blue-100 text-blue-800' : '' }}">
                                                {{ ucfirst($record->status) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-500">
                        <p>No attendance records for the last 30 days.</p>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>