<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Garansi;

class GaransiResource extends LegacyResource
{
    protected static ?string $model = Garansi::class;

    protected static string $moduleKey = 'garansi';

    public static function getPages(): array
    {
        return [
            'index' => GaransiResource\Pages\ListGaransi::route('/'),
        ];
    }
}
