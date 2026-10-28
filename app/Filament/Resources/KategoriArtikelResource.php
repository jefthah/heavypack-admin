<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\KategoriArtikel;

class KategoriArtikelResource extends LegacyResource
{
    protected static ?string $model = KategoriArtikel::class;

    protected static string $moduleKey = 'kategori_artikel';

    public static function getPages(): array
    {
        return [
            'index' => KategoriArtikelResource\Pages\ListKategoriArtikel::route('/'),
            'create' => KategoriArtikelResource\Pages\CreateKategoriArtikel::route('/create'),
            'edit' => KategoriArtikelResource\Pages\EditKategoriArtikel::route('/{record}/edit'),
        ];
    }
}
