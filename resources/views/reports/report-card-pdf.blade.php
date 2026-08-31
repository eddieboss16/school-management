<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card - {{ $student->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; }

        .header {
            background-color: #2563eb;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 { font-size: 22px; font-weight: bold; margin-bottom: 4px; }
        .header p { font-size: 12px; color: #bfdbfe; }

        .section { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
        .section h2 { font-size: 14px; font-weight: bold; margin-bottom: 12px; color: #111827; }

        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-cell { display: table-cell; width: 50%; padding: 4px 0; }
        .info-label { font-size: 10px; color: #6b7280; }
        .info-value { font-size: 13px; font-weight: 600; }

        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th { background-color: #f3f4f6; padding: 8px 10px; text-align: left; font-weight: 600; border: 1px solid #d1d5db; }
        td { padding: 7px 10px; border: 1px solid #d1d5db; }
        .overall-row { background-color: #eff6ff; font-weight: bold; }

        .grade-badge { padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .grade-a { background-color: #d1fae5; color: #065f46; }
        .grade-b { background-color: #dbeafe; color: #1e40af; }
        .grade-c { background-color: #fef9c3; color: #713f12; }
        .grade-d { background-color: #ffedd5; color: #9a3412; }
        .grade-e { background-color: #fee2e2; color: #991b1b; }

        .attendance-grid { display: table; width: 100%; }
        .att-cell { display: table-cell; text-align: center; width: 25%; }
        .att-number { font-size: 24px; font-weight: bold; }
        .att-label { font-size: 10px; color: #6b7280; }
        .att-green { color: #16a34a; }
        .att-red { color: #dc2626; }
        .att-orange { color: #ea580c; }
        .att-blue { color: #2563eb; }

        .footer { padding: 20px; background-color: #f9fafb; }
        .sig-table { width: 100%; }
        .sig-cell { width: 50%; padding: 0 20px; vertical-align: top; }
        .sig-label { font-size: 10px; color: #6b7280; margin-bottom: 40px; }
        .sig-line { border-top: 1px solid #9ca3af; padding-top: 4px; font-size: 10px; color: #6b7280; }
    </style>
</head>
<body>

    <div class="header">
        <h1>STUDENT REPORT CARD</h1>
        <p>Academic Performance Report</p>
    </div>

    <div class="section">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell">
                    <div class="info-label">Student Name</div>
                    <div class="info-value">{{ $student->name }}</div>
                </div>
                <div class="info-cell">
                    <div class="info-label">Admission Number</div>
                    <div class="info-value">{{ $student->admission_number ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell" style="padding-top:10px;">
                    <div class="info-label">Class</div>
                    <div class="info-value">
                        @if($student->stream)
                            {{ $student->stream->grade->name }} - Stream {{ $student->stream->name }}
                        @else
                            Not Assigned
                        @endif
                    </div>
                </div>
                <div class="info-cell" style="padding-top:10px;">
                    <div class="info-label">Term</div>
                    <div class="info-value">{{ isset($term) && $term ? $term->name : 'All Terms' }}</div>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell" style="padding-top:10px;">
                    <div class="info-label">Class Position</div>
                    <div class="info-value">
                        @if(isset($streamPosition) && $streamPosition !== null)
                            {{ $streamPosition }} / {{ $streamSize }}
                        @else
                            N/A
                        @endif
                    </div>
                </div>
                <div class="info-cell" style="padding-top:10px;">
                    <div class="info-label">Report Date</div>
                    <div class="info-value">{{ now()->format('M d, Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Academic Performance</h2>
        @if(count($subjectAverages) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th style="text-align:center">Assessments</th>
                        <th style="text-align:center">Average</th>
                        <th style="text-align:center">Grade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subjectAverages as $classId => $data)
                        <tr>
                            <td>{{ $data['subject'] }}</td>
                            <td style="text-align:center">{{ $data['assessments']->count() }}</td>
                            <td style="text-align:center; font-weight:600">{{ $data['average'] }}%</td>
                            <td style="text-align:center">
                                <span class="{{ \App\Support\Grading::pdfBadgeClass($data['average']) }}">{{ \App\Support\Grading::letter($data['average']) }}</span>
                            </td>
                        </tr>
                    @endforeach
                    <tr class="overall-row">
                        <td colspan="2"><strong>OVERALL AVERAGE</strong></td>
                        <td style="text-align:center; font-size:13px"><strong>{{ $overallAverage }}%</strong></td>
                        <td style="text-align:center; font-size:13px">
                            <span class="{{ \App\Support\Grading::pdfBadgeClass($overallAverage) }}">{{ \App\Support\Grading::letter($overallAverage) }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        @else
            <p style="color:#6b7280">No grades recorded yet.</p>
        @endif
    </div>

    <div class="section">
        <h2>Attendance Summary</h2>
        <div class="attendance-grid">
            <div class="att-cell">
                <div class="att-number att-green">{{ $presentCount }}</div>
                <div class="att-label">Present</div>
            </div>
            <div class="att-cell">
                <div class="att-number att-red">{{ $absentCount }}</div>
                <div class="att-label">Absent</div>
            </div>
            <div class="att-cell">
                <div class="att-number att-orange">{{ $lateCount }}</div>
                <div class="att-label">Late</div>
            </div>
            <div class="att-cell">
                <div class="att-number att-blue">{{ $attendancePercentage }}%</div>
                <div class="att-label">Attendance Rate</div>
            </div>
        </div>
    </div>

    <div class="footer">
        <table class="sig-table">
            <tr>
                <td class="sig-cell">
                    <div class="sig-label">Class Teacher</div>
                    <div class="sig-line">Signature &amp; Date</div>
                </td>
                <td class="sig-cell">
                    <div class="sig-label">Principal</div>
                    <div class="sig-line">Signature &amp; Date</div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
