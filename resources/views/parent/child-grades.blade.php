<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $child->name }}'s Grades
            </h2>
            <a href="{{ route('parent.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if($grades->count() > 0)
                @foreach($grades as $classId => $classGrades)
                    @php
                        $class = $classGrades->first()->class;
                        $averagePercentage = round($classGrades->avg('percentage'), 1);
                    @endphp

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold">{{ $class->subject->name }}</h3>
                                    <p class="text-sm text-gray-600">Teacher: {{ $class->teacher->name }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">Average</p>
                                    <p class="text-3xl font-bold
                                        {{ $averagePercentage >= 70 ? 'text-green-600' : '' }}
                                        {{ $averagePercentage >= 50 && $averagePercentage < 70 ? 'text-yellow-600' : '' }}
                                        {{ $averagePercentage < 50 ? 'text-red-600' : '' }}">
                                        {{ $averagePercentage }}%
                                    </p>
                                </div>
                            </div>

                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assessment</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Percentage</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($classGrades->sortByDesc('assessment_date') as $grade)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $grade->assessment_type }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $grade->assessment_date->format('M d, Y') }}
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
                        <p>No grades recorded yet for {{ $child->name }}.</p>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
