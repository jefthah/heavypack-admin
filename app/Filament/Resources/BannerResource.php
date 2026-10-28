<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Banner;

class BannerResource extends LegacyResource
{
    protected static ?string $model = Banner::class;

    protected static string $moduleKey = 'banner';

    public static function getPages(): array
    {
        return [
            'index' => BannerResource\Pages\ListBanner::route('/'),
            'create' => BannerResource\Pages\CreateBanner::route('/create'),
            'edit' => BannerResource\Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
