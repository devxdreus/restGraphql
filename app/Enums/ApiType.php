<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum ApiType: string implements HasLabel, HasColor
{
    case Rest = 'rest';
    case Graphql = 'graphql';
    case Integrated = 'integrated';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Rest => 'Rest',
            self::Graphql => 'Graphql',
            self::Integrated => 'Integrated',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Rest => 'success',
            self::Graphql => 'info',
            self::Integrated => 'primary',
        };
    }
}
