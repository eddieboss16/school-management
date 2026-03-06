<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                View Grades
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
                        <a href="{{ route('teacher.grades.enter', $class->id) }}" 
                           class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Enter New Grades
                        </a>
                    </div>
                </div>
            </div>

            <!-- Grades by Assessment -->
            @if($grades->count() > 0)
                @foreach($grades as $assessmentType => $assessmentGrades)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                            <div>
                                <h4 class="font-semibold text-lg">{{ $assessmentType }}</h4>
                                <p class="text-sm text-gray-500">
                                    {{ $assessmentGrades->first()->assessment_date->format('M d, Y') }} 
                                    | Max Score: {{ $assessmentGrades->first()->max_score }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="text-right mr-4">
                                    <p class="text-sm text-gray-500">Average</p>
                                    <p class="text-2xl font-bold text-blue-600">
                                        {{ round($assessmentGrades->avg('percentage'), 1) }}%
                                    </p>
                                </div>
                                <a href="{{ route('teacher.grades.edit', [$class->id, $assessmentType]) }}" 
                                class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded text-sm">
                                    Edit
                                </a>
                                <form action="{{ route('teacher.grades.destroy', [$class->id, $assessmentType]) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded text-sm"
                                        onclick="return confirm('Delete all grades for {{ $assessmentType }}? This cannot be undone.')">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>

                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Percentage</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($assessmentGrades->sortBy('student.name') as $index => $grade)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $index + 1 }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $grade->student->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $grade->score }} / {{ $grade->max_score }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                    {{ $grade->percentage >= 70 ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $grade->percentage >= 50 && $grade->percentage < 70 ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $grade->percentage < 50 ? 'bg-red-100 text-red-800' : '' }}">
                                                    {{ $grade->percentage }}%
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                {{ $grade->remarks ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-500">
                        <p>No grades entered yet for this class.</p>
                        <a href="{{ route('teacher.grades.enter', $class->id) }}" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">
                            Enter Grades Now →
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>