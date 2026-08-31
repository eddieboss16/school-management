<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Enter Grades — {{ $class->subject->name }}
            </h2>
            <a href="{{ route('teacher.grades.view', $class->id) }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to Grades
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <!-- Class Info -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-4 flex justify-between items-center">
                    <div>
                        <span class="font-semibold text-gray-800">{{ $class->grade->name }} {{ $class->stream->name }}</span>
                        <span class="ml-3 text-sm text-gray-500">{{ $class->class_code }}</span>
                        <span class="ml-3 text-sm text-gray-500">{{ $class->students->count() }} students</span>
                    </div>
                </div>
            </div>

            @if($class->students->count() > 0)
            <form method="POST" action="{{ route('teacher.grades.store', $class->id) }}" id="gradeForm">
                @csrf

                <!-- Assessment Details -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="font-semibold text-gray-800 mb-4">Assessment Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Assessment Name *</label>
                                <input type="text" name="assessment_type" id="assessment_type"
                                    placeholder="e.g., Quiz 1, Midterm, Final Exam" required
                                    value="{{ old('assessment_type') }}"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('assessment_type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                                <input type="date" name="assessment_date" id="assessment_date" required
                                    value="{{ old('assessment_date', now()->format('Y-m-d')) }}"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('assessment_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Max Score *</label>
                                <input type="number" name="max_score" id="max_score" step="0.01" min="1" required
                                    value="{{ old('max_score', 100) }}"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('max_score')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bulk Score Entry Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-semibold text-gray-800">Student Scores</h3>
                            <div class="flex items-center gap-3">
                                <span class="text-sm text-gray-500">Quick fill:</span>
                                <input type="number" id="bulk_score" step="0.01" min="0" placeholder="Score"
                                    class="w-24 text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <button type="button" onclick="fillAll()"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-1.5 px-3 rounded">
                                    Apply to All
                                </button>
                            </div>
                        </div>

                        <table class="min-w-full divide-y divide-gray-200" id="gradesTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Adm No.</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-28">Score</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-20">%</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($class->students->sortBy('name') as $i => $student)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $i + 1 }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $student->admission_number ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $student->name }}</td>
                                        <td class="px-4 py-3">
                                            <input type="number"
                                                name="grades[{{ $student->id }}][score]"
                                                class="score-input w-24 rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500"
                                                step="0.01" min="0" placeholder="—"
                                                value="{{ old('grades.'.$student->id.'.score') }}"
                                                data-student="{{ $student->id }}">
                                        </td>
                                        <td class="px-4 py-3 text-sm font-semibold" id="pct-{{ $student->id }}">
                                            <span class="text-gray-400">—</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="text"
                                                name="grades[{{ $student->id }}][remarks]"
                                                class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500"
                                                placeholder="Optional"
                                                value="{{ old('grades.'.$student->id.'.remarks') }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Class average preview -->
                        <div class="mt-4 pt-4 border-t flex justify-between items-center">
                            <span class="text-sm text-gray-500">Class Average: <strong id="classAvg" class="text-gray-800">—</strong></span>
                            <div class="flex gap-3">
                                <a href="{{ route('teacher.grades.view', $class->id) }}" class="text-gray-600 hover:text-gray-900 py-2 px-4">Cancel</a>
                                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                                    Submit Grades
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-500">No students enrolled in this class.</div>
                </div>
            @endif

        </div>
    </div>

    <script>
        const maxScoreInput = document.getElementById('max_score');

        function calcPct(score, max) {
            if (!score || !max || max <= 0) return null;
            return Math.round((score / max) * 100 * 100) / 100;
        }

        // Boundaries come from config/grading.php so this stays in step with
        // the server-side colouring in App\Support\Grading.
        const gradeBands = @json(\App\Support\Grading::bands());

        function pctColor(p) {
            if (p >= gradeBands.good) return 'text-green-600';
            if (p >= gradeBands.fair) return 'text-yellow-600';
            return 'text-red-600';
        }

        function updatePct(input) {
            const studentId = input.dataset.student;
            const max = parseFloat(maxScoreInput.value);
            const score = parseFloat(input.value);
            const cell = document.getElementById('pct-' + studentId);
            const pct = calcPct(score, max);
            if (pct !== null) {
                cell.innerHTML = '<span class="' + pctColor(pct) + '">' + pct + '%</span>';
            } else {
                cell.innerHTML = '<span class="text-gray-400">—</span>';
            }
            updateClassAvg();
        }

        function updateClassAvg() {
            const max = parseFloat(maxScoreInput.value);
            const inputs = document.querySelectorAll('.score-input');
            let total = 0, count = 0;
            inputs.forEach(inp => {
                const s = parseFloat(inp.value);
                if (!isNaN(s) && s !== '' && max > 0) {
                    total += (s / max) * 100;
                    count++;
                }
            });
            const avg = document.getElementById('classAvg');
            if (count > 0) {
                const a = Math.round(total / count * 100) / 100;
                avg.textContent = a + '% (' + count + ' of ' + inputs.length + ' entered)';
                avg.className = pctColor(a);
            } else {
                avg.textContent = '—';
                avg.className = 'text-gray-800';
            }
        }

        function fillAll() {
            const val = document.getElementById('bulk_score').value;
            if (val === '') return;
            document.querySelectorAll('.score-input').forEach(inp => {
                inp.value = val;
                updatePct(inp);
            });
        }

        // Recalculate all when max score changes
        maxScoreInput.addEventListener('input', () => {
            document.querySelectorAll('.score-input').forEach(inp => updatePct(inp));
        });

        // Live percentage per student
        document.querySelectorAll('.score-input').forEach(inp => {
            inp.addEventListener('input', () => updatePct(inp));
            // Init from old() values on validation failure
            if (inp.value) updatePct(inp);
        });
    </script>
</x-app-layout>
