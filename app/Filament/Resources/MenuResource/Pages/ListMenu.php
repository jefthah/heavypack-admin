<?php

namespace App\Filament\Resources\MenuResource\Pages;

use App\Filament\Resources\MenuResource;
use App\Filament\Support\ListLegacyRecords;

class ListMenu extends ListLegacyRecords
{
    protected static string $resource = MenuResource::class;
}
