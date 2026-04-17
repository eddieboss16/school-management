<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Fee Structures</h2>
            <a href="{{ route('admin.dashboard') }}" class="text-m text-white hover:text-gray-400">Back</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">{{ session('error') }}</div>
            @endif

            <!-- Filter by term -->
            <div class="bg-white shadow-sm sm:rounded-lg mb-6 p-4">
                <form method="GET" class="flex items-center gap-4">
                    <label class="text-sm font-medium text-gray-700">Filter by Term:</label>
                    <select name="term_id" onchange="this.form.submit()"
                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">All Terms</option>
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}" {{ $selectedTermId == $term->id ? 'selected' : '' }}>
                                {{ $term->name }} {{ $term->is_active ? '(Active)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Fee Structures</h3>
                        <a href="{{ route('admin.fees.structures.create') }}"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Add Fee Structure
                        </a>
                    </div>

                    @if($structures->count() > 0)
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fee Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Term</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applies To</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount (KES)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($structures as $structure)
                                    <tr>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $structure->name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $structure->term->name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            @if($structure->grade)
                                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">{{ $structure->grade->name }}</span>
                                            @else
                                                <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">All Grades</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                            {{ number_format($structure->amount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $structure->description ?? '—' }}</td>
                                        <td class="px-6 py-4 text-sm font-medium space-x-2">
                                            <a href="{{ route('admin.fees.structures.edit', $structure->id) }}"
                                                class="text-blue-600 hover:text-blue-900">Edit</a>
                                            <form action="{{ route('admin.fees.structures.destroy', $structure->id) }}" method="POST" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900"
                                                    onclick="return confirm('Delete this fee structure?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-gray-500">No fee structures found. Create one to start tracking fees.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
