<?php

namespace App\Filament\Resources\ManualResource\Pages;

use App\Filament\Resources\ManualResource;
use App\Filament\Support\ListLegacyRecords;

class ListManual extends ListLegacyRecords
{
    protected static string $resource = ManualResource::class;
}
