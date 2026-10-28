<?php

namespace App\Filament\Resources\KatalogResource\Pages;

use App\Filament\Resources\KatalogResource;
use App\Filament\Support\CreateLegacyRecord;

class CreateKatalog extends CreateLegacyRecord
{
    protected static string $resource = KatalogResource::class;
}
