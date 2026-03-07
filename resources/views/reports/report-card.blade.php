<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card - {{ $student->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body class="bg-gray-100">
    
    <!-- Print Button -->
    <div class="no-print fixed top-4 right-4">
        <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow-lg">
            Print Report Card
        </button>
    </div>

    <!-- Report Card -->
    <div class="max-w-4xl mx-auto my-8 bg-white shadow-lg">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-8">
            <h1 class="text-3xl font-bold text-center mb-2">STUDENT REPORT CARD</h1>
            <p class="text-center text-blue-100">Academic Performance Report</p>
        </div>

        <!-- Student Information -->
        <div class="p-8 border-b-2 border-gray-200">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500">Student Name</p>
                    <p class="text-lg font-semibold">{{ $student->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Email</p>
                    <p class="text-lg font-semibold">{{ $student->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Class</p>
                    <p class="text-lg font-semibold">
                        @if($student->stream)
                            {{ $student->stream->grade->name }} {{ $student->stream->name }}
                        @else
                            Not Assigned
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Report Date</p>
                    <p class="text-lg font-semibold">{{ now()->format('M d, Y') }}</p>
                </div>
            </div>
        </div>

        <!-- Academic Performance -->
        <div class="p-8 border-b-2 border-gray-200">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Academic Performance</h2>
            
            @if(count($subjectAverages) > 0)
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 px-4 py-3 text-left">Subject</th>
                            <th class="border border-gray-300 px-4 py-3 text-center">Assessments</th>
                            <th class="border border-gray-300 px-4 py-3 text-center">Average</th>
                            <th class="border border-gray-300 px-4 py-3 text-center">Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subjectAverages as $classId => $data)
                            <tr>
                                <td class="border border-gray-300 px-4 py-3 font-medium">{{ $data['subject'] }}</td>
                                <td class="border border-gray-300 px-4 py-3 text-center">{{ $data['assessments']->count() }}</td>
                                <td class="border border-gray-300 px-4 py-3 text-center font-semibold">{{ $data['average'] }}%</td>
                                <td class="border border-gray-300 px-4 py-3 text-center">
                                    <span class="px-3 py-1 rounded-full text-sm font-semibold
                                        {{ $data['average'] >= 70 ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $data['average'] >= 50 && $data['average'] < 70 ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $data['average'] < 50 ? 'bg-red-100 text-red-800' : '' }}">
                                        @if($data['average'] >= 70) A
                                        @elseif($data['average'] >= 60) B
                                        @elseif($data['average'] >= 50) C
                                        @elseif($data['average'] >= 40) D
                                        @else F
                                        @endif
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-blue-50 font-bold">
                            <td class="border border-gray-300 px-4 py-3" colspan="2">OVERALL AVERAGE</td>
                            <td class="border border-gray-300 px-4 py-3 text-center text-lg">{{ $overallAverage }}%</td>
                            <td class="border border-gray-300 px-4 py-3 text-center text-lg">
                                @if($overallAverage >= 70) A
                                @elseif($overallAverage >= 60) B
                                @elseif($overallAverage >= 50) C
                                @elseif($overallAverage >= 40) D
                                @else F
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            @else
                <p class="text-gray-500">No grades recorded yet.</p>
            @endif
        </div>

        <!-- Attendance Summary -->
        <div class="p-8 border-b-2 border-gray-200">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Attendance Summary</h2>
            
            <div class="grid grid-cols-4 gap-4">
                <div class="text-center">
                    <p class="text-3xl font-bold text-green-600">{{ $presentCount }}</p>
                    <p class="text-sm text-gray-500">Present</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-red-600">{{ $absentCount }}</p>
                    <p class="text-sm text-gray-500">Absent</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-orange-600">{{ $lateCount }}</p>
                    <p class="text-sm text-gray-500">Late</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-blue-600">{{ $attendancePercentage }}%</p>
                    <p class="text-sm text-gray-500">Attendance Rate</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-8 bg-gray-50">
            <div class="grid grid-cols-2 gap-8">
                <div>
                    <p class="text-sm text-gray-500 mb-2">Class Teacher</p>
                    <div class="border-t-2 border-gray-400 pt-2 mt-8">
                        <p class="text-sm text-gray-600">Signature & Date</p>
                    </div>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-2">Principal</p>
                    <div class="border-t-2 border-gray-400 pt-2 mt-8">
                        <p class="text-sm text-gray-600">Signature & Date</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>