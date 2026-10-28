<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Komentar;

class KomentarResource extends LegacyResource
{
    protected static ?string $model = Komentar::class;

    protected static string $moduleKey = 'komentar';

    public static function getPages(): array
    {
        return [
            'index' => KomentarResource\Pages\ListKomentar::route('/'),
        ];
    }
}
