<?php

namespace App\Filament\Resources\BannerResource\Pages;

use App\Filament\Resources\BannerResource;
use App\Filament\Support\CreateLegacyRecord;

class CreateBanner extends CreateLegacyRecord
{
    protected static string $resource = BannerResource::class;
}
