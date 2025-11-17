<?php

namespace App\Enums;

enum QueryType: string
{
    case Simple = 'simple';
    case Complex = 'complex';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
