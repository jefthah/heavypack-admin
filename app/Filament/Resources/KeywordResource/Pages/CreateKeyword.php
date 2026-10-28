<?php

namespace App\Filament\Resources\KeywordResource\Pages;

use App\Filament\Resources\KeywordResource;
use App\Filament\Support\CreateLegacyRecord;

class CreateKeyword extends CreateLegacyRecord
{
    protected static string $resource = KeywordResource::class;
}
