<?php

namespace App\Filament\Resources\ManualResource\Pages;

use App\Filament\Resources\ManualResource;
use App\Filament\Support\CreateLegacyRecord;

class CreateManual extends CreateLegacyRecord
{
    protected static string $resource = ManualResource::class;
}
