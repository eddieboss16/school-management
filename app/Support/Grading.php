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

    /**
     * The colour tier a percentage falls into: the base letter of its grade,
     * so A- sits in the A tier and B+ in the B tier.
     */
    public static function tier(float|int|null $percentage): string
    {
        return strtoupper(substr(self::letter($percentage), 0, 1));
    }

    /** Tailwind pill classes — background + text, used in table badges. */
    public static function badgeClass(float|int|null $percentage): string
    {
        return self::colours($percentage)['badge'];
    }

    /** Tailwind text colour, used on large standalone percentages. */
    public static function textClass(float|int|null $percentage): string
    {
        return self::colours($percentage)['text'];
    }

    /** @return array{badge: string, text: string} */
    private static function colours(float|int|null $percentage): array
    {
        $tiers = config('grading.tier_colours');

        return $tiers[self::tier($percentage)] ?? end($tiers);
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

    /**
     * The scale flattened for the grade-entry form's client-side colouring:
     * descending lower bounds, each with the text colour of its tier, ending
     * with a 0 entry so any percentage matches.
     *
     * @return list<array{min: int, text: string}>
     */
    public static function scaleForJs(): array
    {
        $tiers = config('grading.tier_colours');
        $out   = [];

        foreach (config('grading.letters') as $minimum => $band) {
            $tier  = strtoupper(substr($band['letter'], 0, 1));
            $out[] = ['min' => (int) $minimum, 'text' => ($tiers[$tier] ?? end($tiers))['text']];
        }

        $fallbackTier = strtoupper(substr(config('grading.fallback.letter'), 0, 1));
        $out[] = ['min' => 0, 'text' => ($tiers[$fallbackTier] ?? end($tiers))['text']];

        return $out;
    }
}
