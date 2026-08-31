<?php

namespace App\Support;

/**
 * One home for the grading scale that used to be hardcoded inline across the
 * report and grade-listing templates. Boundaries live in config/grading.php.
 *
 * Every method takes a percentage (0-100), never a raw score.
 */
class Grading
{
    /** Letter grade for a percentage: A/B/C/D, else F. */
    public static function letter(float|int|null $percentage): string
    {
        $percentage = (float) $percentage;

        foreach (config('grading.letters') as $minimum => $letter) {
            if ($percentage >= $minimum) {
                return $letter;
            }
        }

        return config('grading.fallback');
    }

    /** Tailwind pill classes — background + text, used in table badges. */
    public static function badgeClass(float|int|null $percentage): string
    {
        return match (self::band($percentage)) {
            'good' => 'bg-green-100 text-green-800',
            'fair' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-red-100 text-red-800',
        };
    }

    /** Tailwind text colour, used on large standalone percentages. */
    public static function textClass(float|int|null $percentage): string
    {
        return match (self::band($percentage)) {
            'good' => 'text-green-600',
            'fair' => 'text-yellow-600',
            default => 'text-red-600',
        };
    }

    /** Class pair for the PDF template, which ships its own print stylesheet. */
    public static function pdfBadgeClass(float|int|null $percentage): string
    {
        return 'grade-badge grade-' . strtolower(self::letter($percentage));
    }

    /** Which colour band a percentage falls into. */
    public static function band(float|int|null $percentage): string
    {
        $percentage = (float) $percentage;
        $bands      = config('grading.bands');

        return match (true) {
            $percentage >= $bands['good'] => 'good',
            $percentage >= $bands['fair'] => 'fair',
            default => 'poor',
        };
    }

    /** Boundaries for the client-side colouring in the grade entry form. */
    public static function bands(): array
    {
        return config('grading.bands');
    }
}
