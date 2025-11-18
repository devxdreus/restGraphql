<?php

namespace App\Helpers;

use Filament\Support\Icons\Heroicon;

class PerformanceBadge
{
    public static function getColor(int|float $integrated, int|float $rest, int|float $graphQl): string
    {
        if ($integrated < $rest && $integrated < $graphQl) {
            return 'success';
        }

        if ($integrated < $rest || $integrated < $graphQl) {
            return 'warning';
        }

        return 'danger';
    }

    public static function getIcon(int|float $integrated, int|float $rest, int|float $graphQl): string|Heroicon
    {
        if ($integrated < $rest && $integrated < $graphQl) {
            return Heroicon::OutlinedArrowTrendingDown;
        }

        if ($integrated < $rest || $integrated < $graphQl) {
            return Heroicon::OutlinedArrowTrendingDown;
        }

        return Heroicon::OutlinedArrowTrendingUp;
    }
}
