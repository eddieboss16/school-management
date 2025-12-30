<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Teacher Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-2xl font-bold mb-4">Welcome.</h3>
                    <div class="p-6 text-gray-400">
                        {{ __("You're logged in as TEACHER") }}
                    </div>
                    <p class="mt-4 text-sm text-gray-500">You will manage your classes from here.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>