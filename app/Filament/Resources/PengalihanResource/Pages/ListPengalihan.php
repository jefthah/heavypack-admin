<?php

namespace App\Filament\Resources\PengalihanResource\Pages;

use App\Filament\Resources\PengalihanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPengalihan extends ListRecords
{
    protected static string $resource = PengalihanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah pengalihan')->icon('heroicon-m-plus')];
    }
}
