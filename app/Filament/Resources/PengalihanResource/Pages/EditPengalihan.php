<?php

namespace App\Filament\Resources\PengalihanResource\Pages;

use App\Filament\Resources\PengalihanResource;
use Filament\Resources\Pages\EditRecord;

class EditPengalihan extends EditRecord
{
    protected static string $resource = PengalihanResource::class;

    protected function getRedirectUrl(): string
    {
        return PengalihanResource::getUrl('index');
    }
}
