<?php

namespace App\Filament\Resources\ApiTestResource\Pages;

use App\Filament\Resources\ApiTestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApiTest extends CreateRecord
{
    protected static string $resource = ApiTestResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
