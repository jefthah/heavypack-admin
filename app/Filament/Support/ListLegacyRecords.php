<?php

namespace App\Filament\Support;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

abstract class ListLegacyRecords extends ListRecords
{
    protected function getHeaderActions(): array
    {
        return static::getResource()::canCreate() ? [CreateAction::make()->label('Tambah '.static::getResource()::getModelLabel())] : [];
    }
}
