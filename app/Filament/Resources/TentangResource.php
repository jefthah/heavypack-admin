<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Tentang;

class TentangResource extends LegacyResource
{
    protected static ?string $model = Tentang::class;

    protected static string $moduleKey = 'tentang';

    public static function getPages(): array
    {
        return [
            'index' => TentangResource\Pages\ListTentang::route('/'),
            'create' => TentangResource\Pages\CreateTentang::route('/create'),
            'edit' => TentangResource\Pages\EditTentang::route('/{record}/edit'),
        ];
    }
}
