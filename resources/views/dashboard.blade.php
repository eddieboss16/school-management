<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Student Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-2xl font-bold mb-4">Welcome, {{ $userName }}.</h3>
                    <p class="text-gray-600">Email: {{ $userEmail }}</p>
                    <p class="mt-4 text-sm text-gray-500">Your classes, grades and attendance will appear here.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
