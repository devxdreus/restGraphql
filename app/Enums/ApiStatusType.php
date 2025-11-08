<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum ApiStatusType: string implements HasLabel, HasColor, HasIcon
{
    case Processing = 'processing';
    case Success = 'success';
    case Failed = 'failed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Processing => 'Processing',
            self::Success => 'Success',
            self::Failed => 'Failed',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Processing => 'gray',
            self::Success => 'success',
            self::Failed => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Processing => 'heroicon-o-clock',
            self::Success => 'heroicon-o-check-circle',
            self::Failed => 'heroicon-o-x-circle',
        };
    }
}
