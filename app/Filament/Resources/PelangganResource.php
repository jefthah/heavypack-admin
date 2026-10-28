<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Pelanggan;

class PelangganResource extends LegacyResource
{
    protected static ?string $model = Pelanggan::class;

    protected static string $moduleKey = 'pelanggan';

    public static function getPages(): array
    {
        return [
            'index' => PelangganResource\Pages\ListPelanggan::route('/'),
            'create' => PelangganResource\Pages\CreatePelanggan::route('/create'),
            'edit' => PelangganResource\Pages\EditPelanggan::route('/{record}/edit'),
        ];
    }
}
