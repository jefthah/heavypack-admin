<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Layanan;

class LayananResource extends LegacyResource
{
    protected static ?string $model = Layanan::class;

    protected static string $moduleKey = 'layanan';

    public static function getPages(): array
    {
        return [
            'index' => LayananResource\Pages\ListLayanan::route('/'),
        ];
    }
}
