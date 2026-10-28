<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Manual;

class ManualResource extends LegacyResource
{
    protected static ?string $model = Manual::class;

    protected static string $moduleKey = 'manual';

    public static function getPages(): array
    {
        return [
            'index' => ManualResource\Pages\ListManual::route('/'),
            'create' => ManualResource\Pages\CreateManual::route('/create'),
            'edit' => ManualResource\Pages\EditManual::route('/{record}/edit'),
        ];
    }
}
