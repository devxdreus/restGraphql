<?php

namespace App\Filament\Resources\QueryResource\Pages;

use App\Filament\Resources\QueryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuery extends CreateRecord
{
    protected static string $resource = QueryResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
