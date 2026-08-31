<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Letter grade boundaries
    |--------------------------------------------------------------------------
    |
    | Minimum percentage for each letter, highest band first. A percentage
    | below every boundary falls through to 'fallback'. These are the
    | boundaries used on report cards and in the grade listings.
    |
    | NOTE: GradesPostedNotification deliberately does NOT read this. It uses
    | its own finer 11-band scale (A/A-/B+/B/B-/C+/C/C-/D+/D/D-/E) with
    | different cut-offs, so the same percentage yields a different letter in
    | the email than on the report card. Reconciling the two is a behaviour
    | change, not a refactor, and has not been decided.
    |
    */

    'letters' => [
        70 => 'A',
        60 => 'B',
        50 => 'C',
        40 => 'D',
    ],

    'fallback' => 'F',

    /*
    |--------------------------------------------------------------------------
    | Colour bands
    |--------------------------------------------------------------------------
    |
    | Minimum percentage to be tinted "good" (green) or "fair" (amber).
    | Anything below 'fair' renders as "poor" (red). These intentionally
    | coincide with the A and C letter boundaries.
    |
    */

    'bands' => [
        'good' => 70,
        'fair' => 50,
    ],

];
