<?php

namespace App\Filament\Support;

use App\Support\LegacyMedia;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

abstract class EditLegacyRecord extends EditRecord
{
    use UsesCatalogTransactions;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return LegacyMedia::prepare($data, static::getResource()::definition(), $this->getRecord());
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
