<?php

namespace App\Filament\Support;

use Filament\Resources\Pages\CreateRecord;

abstract class CreateLegacyRecord extends CreateRecord
{
    use PreparesLegacyMedia;
    use UsesCatalogTransactions;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->prepareLegacyMedia($data);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
