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
    | Tier colours
    |--------------------------------------------------------------------------
    |
    | Colour is keyed to the base letter, so a percentage is tinted by the
    | grade it earns rather than by a separate percentage threshold. A- is
    | green because it is an A-tier grade, not because it clears 80.
    |
    |   A  green   (A, A-)      75-100
    |   B  blue    (B+, B, B-)  60-74
    |   C  yellow  (C+, C, C-)  45-59
    |   D  orange  (D+, D, D-)  30-44
    |   E  red     (E)           0-29
    |
    | These mirror the .grade-a … .grade-e rules in the report card PDF
    | stylesheet, so an on-screen badge and its printed counterpart always
    | carry the same colour.
    |
    */

    'tier_colours' => [
        'A' => ['badge' => 'bg-green-100 text-green-800',   'text' => 'text-green-600'],
        'B' => ['badge' => 'bg-blue-100 text-blue-800',     'text' => 'text-blue-600'],
        'C' => ['badge' => 'bg-yellow-100 text-yellow-800', 'text' => 'text-yellow-600'],
        'D' => ['badge' => 'bg-orange-100 text-orange-800', 'text' => 'text-orange-600'],
        'E' => ['badge' => 'bg-red-100 text-red-800',       'text' => 'text-red-600'],
    ],

];
