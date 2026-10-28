<?php

namespace App\Filament\Resources\KeywordResource\Pages;

use App\Filament\Resources\KeywordResource;
use App\Filament\Support\ListLegacyRecords;

class ListKeyword extends ListLegacyRecords
{
    protected static string $resource = KeywordResource::class;
}
