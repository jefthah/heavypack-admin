<?php

namespace App\Filament\Resources\MenuResource\Pages;

use App\Filament\Resources\MenuResource;
use App\Filament\Support\CreateLegacyRecord;

class CreateMenu extends CreateLegacyRecord
{
    protected static string $resource = MenuResource::class;
}
