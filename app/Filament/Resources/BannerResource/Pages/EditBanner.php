<?php

namespace App\Filament\Resources\BannerResource\Pages;

use App\Filament\Resources\BannerResource;
use App\Filament\Support\EditLegacyRecord;

class EditBanner extends EditLegacyRecord
{
    protected static string $resource = BannerResource::class;
}
