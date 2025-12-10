<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Stats Card -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

                <!-- Total Users Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-gray-500 text-sm font-medium">Total Users</h3>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalUsers }}</p>
                    </div>
                </div>

                <!-- Total Students Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-gray-500 text-sm font-medium">Total Students</h3>
                        <p class="text-3xl font-bold text-blue-600 mt-2">{{ $totalStudents }}</p>
                    </div>
                </div>

                <!-- Total Teachers Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-gray-500 text-sm font-medium">Total Teachers</h3>
                        <p class="text-3xl font-bold text-green-600 mt-2">{{ $totalTeachers }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Welcome Message -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-xl font-semibold">Welcome, Admin Eddie!</h3>
                    <p class="mt-2 text-gray-600">Manage your school from this dashboard.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>