<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Sparepart;

class SparepartResource extends LegacyResource
{
    protected static ?string $model = Sparepart::class;

    protected static string $moduleKey = 'sparepart';

    public static function getPages(): array
    {
        return [
            'index' => SparepartResource\Pages\ListSparepart::route('/'),
            'create' => SparepartResource\Pages\CreateSparepart::route('/create'),
            'edit' => SparepartResource\Pages\EditSparepart::route('/{record}/edit'),
        ];
    }
}
