<?php

namespace App\Filament\Resources\PengalihanResource\Pages;

use App\Filament\Resources\PengalihanResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePengalihan extends CreateRecord
{
    protected static string $resource = PengalihanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + ['sumber' => 'manual'];
    }

    protected function getRedirectUrl(): string
    {
        return PengalihanResource::getUrl('index');
    }
}
