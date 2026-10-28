<?php

namespace App\Filament\Resources\MenuResource\Pages;

use App\Filament\Resources\MenuResource;
use App\Filament\Support\EditLegacyRecord;

class EditMenu extends EditLegacyRecord
{
    protected static string $resource = MenuResource::class;
}
