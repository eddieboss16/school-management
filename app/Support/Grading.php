<?php

namespace App\Support;

/**
 * The KCSE 12-point grading scale, in one place. Boundaries and points live
 * in config/grading.php.
 *
 * Every method takes a percentage (0-100), never a raw score.
 */
class Grading
{
    /** Letter grade for a percentage: A, A-, B+, … D-, else E. */
    public static function letter(float|int|null $percentage): string
    {
        return self::bandFor($percentage)['letter'];
    }

    /** KCSE points for a percentage: 12 down to 1. */
    public static function points(float|int|null $percentage): int
    {
        return self::bandFor($percentage)['points'];
    }

    /** The whole band — letter and points — for a percentage. */
    public static function bandFor(float|int|null $percentage): array
    {
        $percentage = (float) $percentage;

        foreach (config('grading.letters') as $minimum => $band) {
            if ($percentage >= $minimum) {
                return $band;
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

    /**
     * Class pair for the PDF template, which ships its own print stylesheet.
     * Collapses to the base letter so A-/A share one style and B+/B/B- share
     * another — the stylesheet defines grade-a through grade-e only.
     */
    public static function pdfBadgeClass(float|int|null $percentage): string
    {
        $base = strtolower(substr(self::letter($percentage), 0, 1));

        return 'grade-badge grade-' . $base;
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
