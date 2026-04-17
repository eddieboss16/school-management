<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Fee Balances</h2>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">← Back</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">{{ session('success') }}</div>
            @endif

            <!-- Filters -->
            <div class="bg-white shadow-sm sm:rounded-lg mb-6 p-4">
                <form method="GET" class="flex flex-wrap items-end gap-4">
                    <!-- Term -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Term</label>
                        <select name="term_id" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 text-sm">
                            <option value="">Select Term</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}" {{ $selectedTermId == $term->id ? 'selected' : '' }}>
                                    {{ $term->name }} {{ $term->is_active ? '(Active)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Grade filter -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Grade</label>
                        <select name="grade_id" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 text-sm">
                            <option value="">All Grades</option>
                            @foreach($grades as $grade)
                                <option value="{{ $grade->id }}" {{ $filterGradeId == $grade->id ? 'selected' : '' }}>
                                    {{ $grade->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Stream filter -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Stream</label>
                        <select name="stream_id" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 text-sm">
                            <option value="">All Streams</option>
                            @foreach($streams as $stream)
                                <option value="{{ $stream->id }}" {{ $filterStreamId == $stream->id ? 'selected' : '' }}>
                                    {{ $stream->grade->name }} {{ $stream->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status filter -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 text-sm">
                            <option value="">All</option>
                            <option value="outstanding" {{ $filterStatus === 'outstanding' ? 'selected' : '' }}>Outstanding</option>
                            <option value="cleared" {{ $filterStatus === 'cleared' ? 'selected' : '' }}>Cleared</option>
                        </select>
                    </div>

                    <div class="ml-auto flex gap-2 items-end">
                        @if($selectedTermId)
                            <a href="{{ route('admin.fees.balances.export', array_filter(['term_id' => $selectedTermId])) }}"
                                class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded">
                                Export CSV
                            </a>
                        @endif
                        <a href="{{ route('admin.fees.balances') }}"
                            class="bg-white border border-gray-300 text-gray-700 text-sm py-2 px-4 rounded hover:bg-gray-50">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            @if(!$selectedTermId)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    Select a term above to view fee balances.
                </div>
            @else
                <!-- Summary cards -->
                @php
                    $totalExpected = collect($balances)->sum('expected');
                    $totalPaid     = collect($balances)->sum('paid');
                    $totalBalance  = collect($balances)->sum('balance');
                    $clearedCount  = collect($balances)->filter(fn($b) => $b['balance'] <= 0)->count();
                    $outstandingCount = collect($balances)->filter(fn($b) => $b['balance'] > 0)->count();
                @endphp
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <p class="text-xs text-gray-500">Total Expected</p>
                        <p class="text-2xl font-bold text-gray-800">KES {{ number_format($totalExpected, 2) }}</p>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <p class="text-xs text-gray-500">Total Collected</p>
                        <p class="text-2xl font-bold text-green-600">KES {{ number_format($totalPaid, 2) }}</p>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <p class="text-xs text-gray-500">Total Outstanding</p>
                        <p class="text-2xl font-bold text-red-600">KES {{ number_format($totalBalance, 2) }}</p>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <p class="text-xs text-gray-500">Cleared / Outstanding</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $clearedCount }} / {{ $outstandingCount }}</p>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Adm No.</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Expected</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Paid</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($students as $student)
                                    @php $b = $balances[$student->id]; @endphp
                                    <tr class="{{ $b['balance'] > 0 ? 'bg-red-50' : '' }}">
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $student->admission_number ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $student->name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            {{ $student->stream ? $student->stream->grade->name . ' ' . $student->stream->name : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-700">{{ number_format($b['expected'], 2) }}</td>
                                        <td class="px-4 py-3 text-sm text-right text-green-700 font-medium">{{ number_format($b['paid'], 2) }}</td>
                                        <td class="px-4 py-3 text-sm text-right font-bold {{ $b['balance'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                            {{ number_format($b['balance'], 2) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($b['balance'] <= 0)
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Cleared</span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Outstanding</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <a href="{{ route('admin.fees.student', ['id' => $student->id, 'term_id' => $selectedTermId]) }}"
                                                class="text-blue-600 hover:text-blue-900">Details</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">No students match the selected filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        @if($students->hasPages())
                            <div class="mt-4">
                                {{ $students->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
