<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Artikel;

class ArtikelResource extends LegacyResource
{
    protected static ?string $model = Artikel::class;

    protected static string $moduleKey = 'artikel';

    public static function getPages(): array
    {
        return [
            'index' => ArtikelResource\Pages\ListArtikel::route('/'),
            'create' => ArtikelResource\Pages\CreateArtikel::route('/create'),
            'edit' => ArtikelResource\Pages\EditArtikel::route('/{record}/edit'),
        ];
    }
}
