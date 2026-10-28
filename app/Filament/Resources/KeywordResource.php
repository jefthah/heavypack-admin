<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Keyword;

class KeywordResource extends LegacyResource
{
    protected static ?string $model = Keyword::class;

    protected static string $moduleKey = 'keyword';

    public static function getPages(): array
    {
        return [
            'index' => KeywordResource\Pages\ListKeyword::route('/'),
            'create' => KeywordResource\Pages\CreateKeyword::route('/create'),
            'edit' => KeywordResource\Pages\EditKeyword::route('/{record}/edit'),
        ];
    }
}
