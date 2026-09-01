<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Issues admission numbers of the form STD{year}{sequence}.
 *
 * The sequence is held in the `admission_sequences` table, one row per year,
 * and is claimed inside a transaction with the row locked. The previous
 * approach re-derived the number from the highest existing student on every
 * insert, which had three failure modes:
 *
 *   - it read the last three characters, so STD{year}1000 came back as 000
 *     and restarted the sequence at 001;
 *   - it ordered by the number as a string, so "STD{year}999" sorted above
 *     "STD{year}1000" and the high-water mark stopped advancing;
 *   - two admissions submitted at the same time read the same maximum and
 *     computed the same next number.
 *
 * `users.admission_number` is uniquely indexed, so a lost race surfaces as a
 * duplicate key error rather than two students sharing a number.
 */
class AdmissionNumber
{
    public const PREFIX = 'STD';

    /** Minimum width of the sequence; longer numbers simply get wider. */
    private const PAD = 3;

    /**
     * Claim and return the next admission number for the given year.
     */
    public static function next(?int $year = null): string
    {
        $year ??= (int) date('Y');

        self::ensureSequenceExists($year);

        return DB::transaction(function () use ($year) {
            $sequence = DB::table('admission_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            $candidate = (int) $sequence->next_number;

            // A number may have been assigned by hand since the counter was
            // seeded, so step over anything already taken rather than handing
            // out a value the unique index will reject.
            while (self::isTaken(self::format($year, $candidate))) {
                $candidate++;
            }

            DB::table('admission_sequences')
                ->where('year', $year)
                ->update(['next_number' => $candidate + 1, 'updated_at' => now()]);

            return self::format($year, $candidate);
        });
    }

    /** STD2026001, STD20261000 — padded to at least three digits. */
    public static function format(int $year, int $number): string
    {
        return self::PREFIX.$year.str_pad((string) $number, self::PAD, '0', STR_PAD_LEFT);
    }

    /**
     * Highest sequence already issued for a year, read from the students
     * themselves. Used only to seed the counter the first time a year is used,
     * so an existing dataset is never reissued a number.
     */
    public static function highestIssued(int $year): int
    {
        $prefix = self::PREFIX.$year;
        $highest = 0;

        // Queried through the query builder rather than the model: a
        // soft-deleted student still holds their number, because the unique
        // index does not care that deleted_at is set.
        $numbers = DB::table('users')
            ->where('admission_number', 'like', $prefix.'%')
            ->pluck('admission_number');

        foreach ($numbers as $number) {
            $suffix = substr((string) $number, strlen($prefix));

            if ($suffix !== '' && ctype_digit($suffix)) {
                $highest = max($highest, (int) $suffix);
            }
        }

        return $highest;
    }

    private static function ensureSequenceExists(int $year): void
    {
        if (DB::table('admission_sequences')->where('year', $year)->exists()) {
            return;
        }

        // insertOrIgnore: two first-of-the-year admissions can reach here at
        // once, and both compute the same starting value, so losing this race
        // is harmless.
        DB::table('admission_sequences')->insertOrIgnore([
            'year' => $year,
            'next_number' => self::highestIssued($year) + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private static function isTaken(string $admissionNumber): bool
    {
        return DB::table('users')->where('admission_number', $admissionNumber)->exists();
    }
}
