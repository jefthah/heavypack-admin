<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\BannerFooter;

class BannerFooterResource extends LegacyResource
{
    protected static ?string $model = BannerFooter::class;

    protected static string $moduleKey = 'banner_footer';

    public static function getPages(): array
    {
        return [
            'index' => BannerFooterResource\Pages\ListBannerFooter::route('/'),
            'create' => BannerFooterResource\Pages\CreateBannerFooter::route('/create'),
            'edit' => BannerFooterResource\Pages\EditBannerFooter::route('/{record}/edit'),
        ];
    }
}
