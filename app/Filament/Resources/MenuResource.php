<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Menu;

class MenuResource extends LegacyResource
{
    protected static ?string $model = Menu::class;

    protected static string $moduleKey = 'menu';

    public static function getPages(): array
    {
        return [
            'index' => MenuResource\Pages\ListMenu::route('/'),
            'create' => MenuResource\Pages\CreateMenu::route('/create'),
            'edit' => MenuResource\Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
