<?php

namespace App\Filament\Support;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

abstract class EditLegacyRecord extends EditRecord
{
    use PreparesLegacyMedia;
    use UsesCatalogTransactions;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->prepareLegacyMedia($data, $this->getRecord());
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->label('Hapus')];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
