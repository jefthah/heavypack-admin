<?php

namespace App\Filament\Resources\BannerResource\Pages;

use App\Filament\Resources\BannerResource;
use App\Filament\Support\ListLegacyRecords;

class ListBanner extends ListLegacyRecords
{
    protected static string $resource = BannerResource::class;
}
