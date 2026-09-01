<?php

namespace App\Support;

use App\Models\StudentGrade;
use App\Models\User;

/**
 * A student's position within their stream.
 *
 * This was previously a private method copied verbatim into both
 * ReportCardController and Parent\DashboardController, with a note in
 * CLAUDE.md asking that they be kept in sync — which nothing enforced. The two
 * copies were identical when this was extracted; StreamRankTest now pins every
 * surface to the same answer so they cannot drift apart again.
 */
class StreamRank
{
    /**
     * Rank a student against everyone in their stream for the given term.
     *
     * Averages the student's per-subject averages — the same formula the
     * report card shows — and ranks descending. Ties share a position, and the
     * places they consume are skipped, so two firsts are followed by a third.
     *
     * @return array{0: ?int, 1: ?int} [position, stream size]; [null, null]
     *                                 when the student has no stream.
     */
    public static function forStudent(User $student, ?int $termId = null): array
    {
        if (! $student->stream_id) {
            return [null, null];
        }

        // All students in the same stream
        $streamStudentIds = User::where('usertype', 'student')
            ->where('stream_id', $student->stream_id)
            ->pluck('id');

        $total = $streamStudentIds->count();

        if ($total <= 1) {
            return [1, $total];
        }

        // All grades for all stream students in one query
        $allGradesQuery = StudentGrade::whereIn('student_id', $streamStudentIds);
        if ($termId) {
            $allGradesQuery->where('term_id', $termId);
        }

        $allGrades = $allGradesQuery->get()->groupBy('student_id');

        // Compute overall average per student (average of subject averages — same formula as report card)
        $averages = [];
        foreach ($streamStudentIds as $sid) {
            $studentGrades = $allGrades->get($sid, collect());
            if ($studentGrades->isEmpty()) {
                $averages[$sid] = 0.0;
                continue;
            }
            $byClass       = $studentGrades->groupBy('class_id');
            $subjectAvgs   = $byClass->map(fn ($g) => $g->avg('percentage'));
            $averages[$sid] = round($subjectAvgs->avg(), 2);
        }

        // Sort descending; ties share the same position (dense rank)
        arsort($averages);
        $position = 1;
        $prev     = null;
        $rank     = 1;
        foreach ($averages as $sid => $avg) {
            if ($prev !== null && $avg < $prev) {
                $rank = $position;
            }
            if ($sid === $student->id) {
                return [$rank, $total];
            }
            $prev = $avg;
            $position++;
        }

        return [null, $total];
    }
}
