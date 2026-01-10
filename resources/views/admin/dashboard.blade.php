<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Stats Card -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">

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

                <!-- Total Streams Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-gray-500 text-sm font-medium">Total Streams</h3>
                        <p class="text-3xl font-bold text-purple-600 mt-2">{{ $totalStreams }}</p>
                    </div>
                </div>

                <!-- Total Subjects Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-gray-500 text-sm font-medium">Total Subjects</h3>
                        <p class="text-3xl font-bold text-orange-600 mt-2">{{ $totalSubjects }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Welcome Message -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-xl font-semibold">Welcome, Admin Eddie.</h3>
                    <p class="mt-2 text-gray-600">Manage your school from this dashboard.</p>
                </div>
            </div>

            <!-- Navigation Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
    <!-- Manage Students Card -->
    <a href="{{ route('admin.students') }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Manage Students</h3>
            <p class="text-gray-600 text-sm">View, add, edit, and delete students</p>
            <p class="text-blue-600 mt-4 font-medium">View Students →</p>
        </div>
    </a>

    <!-- Manage Teachers Card -->
    <a href="{{ route('admin.teachers') }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Manage Teachers</h3>
            <p class="text-gray-600 text-sm">View, add, edit, and delete teachers</p>
            <p class="text-green-600 mt-4 font-medium">View Teachers →</p>
        </div>
    </a>

    <! -- Manage Streams Card -->
    <a href="{{ route('admin.streams') }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Manage Streams</h3>
            <p class="text-gray-600 text-sm">View, add, edit and delete streams</p>
            <p class="text-purple-600 mt-4 font-medium">View Streams</p>
        </div>
    </a>

    <!-- Manage Subjects Card -->
    <a href="{{ route('admin.subjects') }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Manage Subjects</h3>
            <p class="text-gray-600 text-sm">View, add, edit, and delete subjects</p>
            <p class="text-orange-600 mt-4 font-medium">View Subjects →</p>
        </div>
    </a>
</div>
        </div>
    </div>
</x-app-layout>