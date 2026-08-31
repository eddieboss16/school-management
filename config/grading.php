<?php

return [

    /*
    |--------------------------------------------------------------------------
    | KCSE 12-point grading scale
    |--------------------------------------------------------------------------
    |
    | The standard Kenyan secondary school scale. Keys are the inclusive lower
    | bound of each band, highest first; a percentage below every bound falls
    | through to 'fallback'.
    |
    |   A  80-100 (12)    C+ 55-59 (7)
    |   A- 75-79  (11)    C  50-54 (6)
    |   B+ 70-74  (10)    C- 45-49 (5)
    |   B  65-69   (9)    D+ 40-44 (4)
    |   B- 60-64   (8)    D  35-39 (3)
    |                     D- 30-34 (2)
    |                     E   0-29 (1)
    |
    | There is no F on this scale — E is the fail grade.
    |
    */

    'letters' => [
        80 => ['letter' => 'A',  'points' => 12],
        75 => ['letter' => 'A-', 'points' => 11],
        70 => ['letter' => 'B+', 'points' => 10],
        65 => ['letter' => 'B',  'points' => 9],
        60 => ['letter' => 'B-', 'points' => 8],
        55 => ['letter' => 'C+', 'points' => 7],
        50 => ['letter' => 'C',  'points' => 6],
        45 => ['letter' => 'C-', 'points' => 5],
        40 => ['letter' => 'D+', 'points' => 4],
        35 => ['letter' => 'D',  'points' => 3],
        30 => ['letter' => 'D-', 'points' => 2],
    ],

    'fallback' => ['letter' => 'E', 'points' => 1],

    /*
    |--------------------------------------------------------------------------
    | Colour bands
    |--------------------------------------------------------------------------
    |
    | Purely presentational tinting of percentages, deliberately kept
    | independent of the letter bands: green from 70 (B+), amber from 50 (C),
    | red below. These are the thresholds the grade views have always used and
    | are unrelated to KCSE grading itself.
    |
    */

    'bands' => [
        'good' => 70,
        'fair' => 50,
    ],

];
