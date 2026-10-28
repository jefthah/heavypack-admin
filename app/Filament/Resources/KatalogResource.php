<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Katalog;

class KatalogResource extends LegacyResource
{
    protected static ?string $model = Katalog::class;

    protected static string $moduleKey = 'katalog';

    public static function getPages(): array
    {
        return [
            'index' => KatalogResource\Pages\ListKatalog::route('/'),
            'create' => KatalogResource\Pages\CreateKatalog::route('/create'),
            'edit' => KatalogResource\Pages\EditKatalog::route('/{record}/edit'),
        ];
    }
}
