<?php

namespace App\Filament\Resources\ManualResource\Pages;

use App\Filament\Resources\ManualResource;
use App\Filament\Support\EditLegacyRecord;

class EditManual extends EditLegacyRecord
{
    protected static string $resource = ManualResource::class;
}
