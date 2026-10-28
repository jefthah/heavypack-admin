<?php

namespace App\Filament\Resources\KeywordResource\Pages;

use App\Filament\Resources\KeywordResource;
use App\Filament\Support\EditLegacyRecord;

class EditKeyword extends EditLegacyRecord
{
    protected static string $resource = KeywordResource::class;
}
