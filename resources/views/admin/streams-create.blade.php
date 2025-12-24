<x-app-layout>
    <x-slot name="header">
        <div class="flexjustify-betweenitems-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add New Stream') }}
            </h2>
            <a href="{{ route('admin.streams') }}" class="text-m text-white hover:text-gray-400">Back</a>
        </div>
        
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.streams.store') }}">
                        @csrf

                        <!-- Grade Selection -->
                        <div class="mb-4">
                            <label for="grade_id" class="block text-sm font-medium text-gray-700">Grade</label>
                            <select name="grade_id" id="grade_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Grade</option>
                                @foreach($grades as $grade)
                                    <option value="{{ $grade->id }}" {{ old('grade_id') == $grade->id ? 'selected' : '' }}>
                                        {{ $grade->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('grade_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Stream Name -->
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">Stream Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="e.g., A, B, C" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Capacity -->
                        <div class="mb-4">
                            <label for="capacity" class="block text-sm font-medium text-gray-700">Capacity (Max Students)</label>
                            <input type="number" name="capacity" id="capacity" value="{{ old('capacity', 40) }}" min="1" max="100" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('capacity')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Buttons -->
                        <div class="flex items-center justify-between">
                            <a href="{{ route('admin.streams') }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Create Stream
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>