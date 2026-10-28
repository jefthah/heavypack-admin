<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Iklan;

class IklanResource extends LegacyResource
{
    protected static ?string $model = Iklan::class;

    protected static string $moduleKey = 'iklan';

    public static function getPages(): array
    {
        return [
            'index' => IklanResource\Pages\ListIklan::route('/'),
            'create' => IklanResource\Pages\CreateIklan::route('/create'),
            'edit' => IklanResource\Pages\EditIklan::route('/{record}/edit'),
        ];
    }
}
