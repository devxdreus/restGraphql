<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
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
            self::Integrated => 'danger',
        };
    }

    public function getBorderColor(): string|array|null
    {
        return match ($this) {
            self::Rest => $this->colorToRgba(Color::Teal[400]),
            self::Graphql => $this->colorToRgba(Color::Blue[400]),
            self::Integrated => $this->colorToRgba(Color::Red[400]),
        };
    }

    public function getBackgroundColor(): string|array|null
    {
        return match ($this) {
            self::Rest => $this->colorToRgba(Color::Teal[400], 0.8),
            self::Graphql => $this->colorToRgba(Color::Blue[400], 0.8),
            self::Integrated => $this->colorToRgba(Color::Red[400], 0.8),
        };
    }

    private function colorToRgba(string $hex, float $alpha = 1): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "rgba($r, $g, $b, $alpha)";
    }
}
