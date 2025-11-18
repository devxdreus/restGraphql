<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum QueryType: string implements HasLabel
{
    case Simple = 'simple';
    case Complex = 'complex';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Simple => 'Sederhana',
            self::Complex => 'Kompleks'
        };
    }
}
