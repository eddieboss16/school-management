<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Parent Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __("Your Children") }}</h3>
                    <ul class="mt-4 space-y-4">
                        @foreach ($children as $child)
                            <li class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                <p class="text-lg font-semibold">{{ $child->name }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $child->email }}</p>
                                <div class="mt-4">
                                    <a href="{{ route('parent.child.grades', $child->id) }}" class="text-blue-500 hover:underline">View Grades</a>
                                    <a href="{{ route('parent.child.attendance', $child->id) }}" class="ml-4 text-blue-500 hover:underline">View Attendance</a>
                                    <a href="{{ route('parent.child.report_card', $child->id) }}" class="ml-4 text-blue-500 hover:underline">View Report Card</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
