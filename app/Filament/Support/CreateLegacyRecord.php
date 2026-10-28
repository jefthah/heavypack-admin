<?php

namespace App\Filament\Support;

use App\Support\LegacyMedia;
use Filament\Resources\Pages\CreateRecord;

abstract class CreateLegacyRecord extends CreateRecord
{
    use UsesCatalogTransactions;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return LegacyMedia::prepare($data, static::getResource()::definition());
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
