<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Mpesa = 'mpesa';
    case Bank = 'bank';

    /**
     * Backing values, for validation rules and the schema definition.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Mpesa => 'M-Pesa',
            self::Bank => 'Bank Transfer',
        };
    }

    /**
     * Tailwind badge classes. Returned from PHP at runtime, so the app/ glob
     * must stay in tailwind.config.js `content` or these get purged from a
     * production build. Same arrangement as App\Support\Grading.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Cash => 'bg-gray-100 text-gray-700',
            self::Mpesa => 'bg-green-100 text-green-700',
            self::Bank => 'bg-blue-100 text-blue-700',
        };
    }
}
